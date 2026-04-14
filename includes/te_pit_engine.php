<?php

class TEPitEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        $this->pdo->prepare("DELETE tir FROM te_individual_result tir JOIN import_pit_data ipd ON tir.tin = ipd.ptin AND tir.tax_year = ipd.tax_year WHERE ipd.batch_id = ?")->execute([$batch_id]);

        $stmt = $this->pdo->prepare("SELECT * FROM import_pit_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($records as $row) {
            try {
                $result = $this->calculateIndividual($row);

                $existing = $this->pdo->prepare("SELECT id FROM te_individual_result WHERE tin = ? AND tax_year = ?");
                $existing->execute([$row['ptin'], $row['tax_year']]);
                $existingRow = $existing->fetch();

                if ($existingRow) {
                    $updateStmt = $this->pdo->prepare("UPDATE te_individual_result SET individual_name = ?, filing_date = ?, employment_income = ?, other_income = ?, actual_tax_paid = ?, benchmark_calculated_tax = ?, te_amount = ?, matched_provisions = ? WHERE id = ?");
                    $updateStmt->execute([
                        $row['employee_name'],
                        $row['filing_date'],
                        $result['employment_income'],
                        $result['other_income'],
                        $result['actual_tax_paid'],
                        $result['benchmark_tax'],
                        $result['te_amount'],
                        $result['matched_provisions'],
                        $existingRow['id']
                    ]);
                } else {
                    $insertStmt = $this->pdo->prepare("INSERT INTO te_individual_result (tax_year, tin, individual_name, filing_date, employment_income, other_income, actual_tax_paid, benchmark_calculated_tax, te_amount, matched_provisions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insertStmt->execute([
                        $row['tax_year'],
                        $row['ptin'],
                        $row['employee_name'],
                        $row['filing_date'],
                        $result['employment_income'],
                        $result['other_income'],
                        $result['actual_tax_paid'],
                        $result['benchmark_tax'],
                        $result['te_amount'],
                        $result['matched_provisions']
                    ]);
                }

                $total_te += $result['te_amount'];
                $total_calculated++;
            } catch (Exception $e) {
                $errors[] = "PTIN {$row['ptin']}: " . $e->getMessage();
            }
        }

        return [
            'calculated' => $total_calculated,
            'total_te' => $total_te,
            'errors' => $errors
        ];
    }

    public function calculateIndividual(array $row): array {
        $year = (int)$row['tax_year'];
        $employment_income = $this->sumProvisionAmounts($row, ['21', '22', '24', '25', '29']);
        $other_income = $this->sumProvisionAmounts($row, ['23_1', '23_2', '26', '27', '28_1', '28_2']);

        $benchmark_employment_tax = $this->calculateProgressiveTax($year, $employment_income);
        $benchmark_other_tax = $this->calculateFlatRateTax($year, $row);
        $total_benchmark_tax = $benchmark_employment_tax + $benchmark_other_tax;

        $actual_tax_paid = $this->calculateActualTaxPaid($row);
        $te_amount = max(0, $total_benchmark_tax - $actual_tax_paid);

        $matched_provisions = $this->matchProvisions($row);

        return [
            'employment_income' => round($employment_income, 2),
            'other_income' => round($other_income, 2),
            'actual_tax_paid' => round($actual_tax_paid, 2),
            'benchmark_tax' => round($total_benchmark_tax, 2),
            'te_amount' => round($te_amount, 2),
            'matched_provisions' => implode(', ', $matched_provisions)
        ];
    }

    private function sumProvisionAmounts(array $row, array $provisions): float {
        $total = 0.0;
        foreach ($provisions as $prov) {
            $col = 'amount_' . $prov;
            if (isset($row[$col])) {
                $total += (float)$row[$col];
            }
        }
        return $total;
    }

    private function calculateProgressiveTax(int $year, float $income): float {
        if ($income <= 0) {
            return 0.0;
        }

        $stmt = $this->pdo->prepare("SELECT min_income, max_income, rate_percentage FROM bm_pit_employment WHERE start_year <= ? AND end_year >= ? ORDER BY min_income ASC");
        $stmt->execute([$year, $year]);
        $brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($brackets)) {
            return 0.0;
        }

        $tax = 0.0;

        foreach ($brackets as $bracket) {
            $min = (float)$bracket['min_income'];
            // Since min_income is usually "previous_max + 1" (e.g. 1300001), 
            // the actual tax threshold we measure from is min - 1.
            $threshold_start = $min > 0 ? $min - 1 : 0;
            $max = $bracket['max_income'] !== null ? (float)$bracket['max_income'] : PHP_FLOAT_MAX;
            $rate = (float)$bracket['rate_percentage'] / 100;

            if ($income > $threshold_start) {
                $taxable_in_bracket = min($income - $threshold_start, $max - $threshold_start);
                $tax += $taxable_in_bracket * $rate;
            }
        }

        return $tax;
    }

    private function calculateFlatRateTax(int $year, array $row): float {
        $provisionRateMap = [
            '23_1' => 'Rental Income',
            '23_2' => 'Rental Income',
            '26' => 'Shares Transfer',
            '27' => 'Dividends',
            '28_1' => 'Loan Interest (non-bank)',
            '28_2' => 'Loan Interest (non-bank)'
        ];

        $total_tax = 0.0;

        foreach ($provisionRateMap as $col_suffix => $income_type) {
            $col = 'amount_' . $col_suffix;
            if (isset($row[$col]) && (float)$row[$col] > 0) {
                $rate = $this->lookupFlatRate($year, $income_type);
                $total_tax += (float)$row[$col] * ($rate / 100);
            }
        }

        return $total_tax;
    }

    private function lookupFlatRate(int $year, string $income_type): float {
        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_pit_flat_rates WHERE start_year <= ? AND end_year >= ? AND income_type = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$year, $year, $income_type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['rate_percentage'] : 10.0;
    }

    private function calculateActualTaxPaid(array $row): float {
        $expertTE = (float)($row['expert_te_total'] ?? 0);
        $income = $this->sumProvisionAmounts($row, ['21', '22', '23_1', '23_2', '24', '25', '26', '27', '28_1', '28_2', '29']);
        return max(0, $income * 0.10 - $expertTE);
    }

    public function matchProvisions(array $row): array {
        $matched = [];
        $provisionColumns = [
            '21' => 'amount_21',
            '22' => 'amount_22',
            '23' => 'amount_23_1',
            '23.1' => 'amount_23_1',
            '23.2' => 'amount_23_2',
            '24' => 'amount_24',
            '25' => 'amount_25',
            '26' => 'amount_26',
            '27' => 'amount_27',
            '28' => 'amount_28_1',
            '28.1' => 'amount_28_1',
            '28.2' => 'amount_28_2',
            '29' => 'amount_29'
        ];

        foreach ($provisionColumns as $provNum => $col) {
            if (isset($row[$col]) && (float)$row[$col] > 0) {
                if (!in_array($provNum, $matched)) {
                    $matched[] = $provNum;
                }
            }
        }

        if (isset($row['is_ss_member']) && (int)$row['is_ss_member'] === 1) {
            $matched[] = '30';
        }

        return array_unique($matched);
    }
}
