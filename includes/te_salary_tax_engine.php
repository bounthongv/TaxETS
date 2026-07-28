<?php

class TESalaryTaxEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM import_salary_tax_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($records as $row) {
            try {
                $result = $this->calculateSalaryTax($row);

                $updateStmt = $this->pdo->prepare("UPDATE import_salary_tax_data SET benchmark_tax = ?, te_amount = ?, provision_number = ?, calculated_at = NOW() WHERE id = ?");
                $updateStmt->execute([
                    $result['benchmark_tax'],
                    $result['te_amount'],
                    $result['provision_number'],
                    $row['id']
                ]);

                $total_te += $result['te_amount'];
                $total_calculated++;
            } catch (Exception $e) {
                $errors[] = "TIN {$row['tin']} (ID: {$row['id']}): " . $e->getMessage();
            }
        }

        return [
            'calculated' => $total_calculated,
            'total_te' => $total_te,
            'errors' => $errors
        ];
    }

    public function calculateSalaryTax(array $row): array {
        // For aggregate salary tax, we use the reported Tax Exempt Amount as the basis for TE.
        // TE = Tax Exempt Amount * Benchmark Rate (from bm_salary_rates)
        // Benchmark = Tax Amount + TE

        $tax_exempt_amount = (float)($row['tax_exempt_amount'] ?? 0);
        $tax_amount = (float)($row['tax_amount'] ?? 0);
        $tax_year = (int)($row['tax_year'] ?? date('Y'));
        $provision_number = $row['provision_number'] ?? '';
        $filing_period = $row['filing_period'] ?? '';

        // Determine the reference date: use filing_period if available, else Jan 1 of tax year
        $ref_date = null;
        if (!empty($filing_period)) {
            $ref_date = date('Y-m-d', strtotime($filing_period));
        }
        if (empty($ref_date) || $ref_date === '1970-01-01') {
            $ref_date = date('Y-m-d', mktime(0, 0, 0, 1, 1, $tax_year));
        }

        // Look up the benchmark rate from the reference table
        $benchmark_rate = $this->lookupRate($ref_date, $provision_number);

        // TE = exempt amount × benchmark rate
        $te_amount = $tax_exempt_amount * ($benchmark_rate / 100);
        $benchmark_tax = $tax_amount + $te_amount;

        return [
            'benchmark_tax' => round($benchmark_tax, 2),
            'te_amount' => round($te_amount, 2),
            'provision_number' => $provision_number ?: 'Multiple'
        ];
    }

    /**
     * Look up the benchmark rate for a given date and provision number.
     * Uses start_date/end_date range (with fallback to start_year/end_year for legacy data).
     */
    private function lookupRate(string $ref_date, string $provision_number): float {
        if (empty($provision_number)) {
            $provision_number = 'Multiple';
        }

        // Try exact provision match — date-based lookup with year fallback
        $stmt = $this->pdo->prepare("
            SELECT rate_percentage FROM bm_salary_rates
            WHERE provision_number = ?
            AND (
                (start_date IS NOT NULL AND ? BETWEEN start_date AND end_date)
                OR
                (start_date IS NULL AND start_year <= YEAR(?) AND end_year >= YEAR(?))
            )
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$provision_number, $ref_date, $ref_date, $ref_date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return (float)$row['rate_percentage'];
        }

        // Fallback: try 'Multiple' / default
        $stmt2 = $this->pdo->prepare("
            SELECT rate_percentage FROM bm_salary_rates
            WHERE provision_number = 'Multiple'
            AND (
                (start_date IS NOT NULL AND ? BETWEEN start_date AND end_date)
                OR
                (start_date IS NULL AND start_year <= YEAR(?) AND end_year >= YEAR(?))
            )
            ORDER BY id DESC LIMIT 1
        ");
        $stmt2->execute([$ref_date, $ref_date, $ref_date]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($row2) {
            return (float)$row2['rate_percentage'];
        }

        throw new Exception("No salary benchmark rate configured for provision {$provision_number} on {$ref_date}");
    }

    public function getBatchSummary(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total, SUM(te_amount) as total_te, SUM(total_taxable_amount) as total_taxable, SUM(tax_exempt_amount) as total_exempt FROM import_salary_tax_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_te' => 0, 'total_taxable' => 0, 'total_exempt' => 0];
    }
}
