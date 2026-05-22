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
        // TE = Tax Exempt Amount * Standard Benchmark Rate (e.g., 10%)
        // Or Benchmark = Tax Amount + (Tax Exempt Amount * Rate)
        
        $tax_exempt_amount = (float)($row['tax_exempt_amount'] ?? 0);
        $tax_amount = (float)($row['tax_amount'] ?? 0);
        $total_taxable_amount = (float)($row['total_taxable_amount'] ?? 0);

        // Standard Benchmark Rate for Salary Tax (could be fetched from a config table)
        // Here we default to 10% as a reasonable average for progressive PIT benchmarks 
        // if no specific bracket info is available.
        $benchmark_rate = 10.0; 

        // Benchmark Tax = (Taxable Amount + Exempt Amount) * Rate
        // But since Taxable Amount already had some tax paid ($tax_amount), 
        // it's more accurate to say TE is the tax NOT paid on the exempt amount.
        $te_amount = $tax_exempt_amount * ($benchmark_rate / 100);
        $benchmark_tax = $tax_amount + $te_amount;

        return [
            'benchmark_tax' => round($benchmark_tax, 2),
            'te_amount' => round($te_amount, 2),
            'provision_number' => $row['provision_number'] ?: 'Multiple'
        ];
    }

    public function getBatchSummary(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total, SUM(te_amount) as total_te, SUM(total_taxable_amount) as total_taxable, SUM(tax_exempt_amount) as total_exempt FROM import_salary_tax_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_te' => 0, 'total_taxable' => 0, 'total_exempt' => 0];
    }
}
