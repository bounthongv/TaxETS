<?php

class TEVatEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM import_vat_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($records as $row) {
            try {
                $result = $this->calculateVat($row);

                $updateStmt = $this->pdo->prepare("UPDATE import_vat_data SET expert_te = ?, provision_number = ? WHERE id = ?");
                $updateStmt->execute([
                    $result['calculated_te'],
                    $result['matched_provisions'],
                    $row['id']
                ]);

                $total_te += $result['calculated_te'];
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

    public function calculateVat(array $row): array {
        $filing_period = $row['filing_period'];
        $standard_rate = $this->lookupVatRate($filing_period);

        $sales_standard = (float)($row['sales_standard'] ?? 0);
        $sales_zero_rate = (float)($row['sales_zero_rate'] ?? 0);
        $sales_exempt = (float)($row['sales_exempt'] ?? 0);
        $total_input_vat = (float)($row['total_input_vat'] ?? 0);
        $vat_payable = (float)($row['vat_payable'] ?? 0);

        $total_sales = $sales_standard + $sales_zero_rate + $sales_exempt;
        $benchmark_output_vat = $total_sales * ($standard_rate / 100);

        $calculated_vat_payable = max(0, $benchmark_output_vat - $total_input_vat);

        $system_te = $calculated_vat_payable - $vat_payable;
        if ($system_te < 0) {
            $system_te = 0;
        }

        // We map the repository report using the provision number already provided 
        // in the imported Excel data rather than dynamically matching conditions.
        return [
            'standard_rate' => $standard_rate,
            'total_sales' => round($total_sales, 2),
            'benchmark_output_vat' => round($benchmark_output_vat, 2),
            'calculated_vat_payable' => round($calculated_vat_payable, 2),
            'calculated_te' => round($system_te, 2),
            'matched_provisions' => $row['provision_number'] ?? ''
        ];
    }

    private function lookupVatRate(string $filing_period): float {
        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_vat WHERE start_date <= ? AND end_date >= ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$filing_period, $filing_period]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $fallback = $this->pdo->query("SELECT rate_percentage FROM bm_vat ORDER BY end_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            return $fallback ? (float)$fallback['rate_percentage'] : 10.0;
        }

        return (float)$row['rate_percentage'];
    }

    // matchProvisions method removed: VAT mapping relies on the explicit 
    // provision classification provided during the Excel import process.

    public function getBatchSummary(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total, SUM(expert_te) as total_te, SUM(sales_standard) as total_standard, SUM(sales_zero_rate) as total_zero, SUM(sales_exempt) as total_exempt FROM import_vat_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_te' => 0, 'total_standard' => 0, 'total_zero' => 0, 'total_exempt' => 0];
    }
}
