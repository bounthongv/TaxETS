<?php

class TESEZEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id, string $type): array {
        $stmt = $this->pdo->prepare("SELECT * FROM import_sez_data WHERE batch_id = ? AND type = ?");
        $stmt->execute([$batch_id, $type]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($records as $row) {
            try {
                $result = $this->calculateSEZTE($row);

                $updateStmt = $this->pdo->prepare("UPDATE import_sez_data SET benchmark_tax = ?, te_amount = ?, provision_number = ?, calculated_at = NOW() WHERE id = ?");
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

    public function calculateSEZTE(array $row): array {
        $vat_rate = 10.0; // Default VAT Benchmark Rate
        $te_amount = 0.0;
        $prov_numbers = [];

        if ($row['type'] === 'Developer') {
            // For Developers, TE is the VAT NOT paid on basic and other infrastructure construction
            $infra_basic = (float)$row['amount_infra_basic'];
            $infra_other = (float)$row['amount_infra_other'];
            
            if ($infra_basic > 0) {
                $te_amount += $infra_basic * ($vat_rate / 100);
                $prov_numbers[] = 'VAT-D1';
            }
            if ($infra_other > 0) {
                $te_amount += $infra_other * ($vat_rate / 100);
                $prov_numbers[] = 'VAT-D2';
            }
        } else {
            // For Investors, TE is VAT NOT paid on utility usage and internal infra development
            $utility = (float)$row['amount_utility_usage'];
            $infra_dev = (float)$row['amount_infra_dev'];

            if ($utility > 0) {
                $te_amount += $utility * ($vat_rate / 100);
                $prov_numbers[] = 'VAT-I1';
            }
            if ($infra_dev > 0) {
                $te_amount += $infra_dev * ($vat_rate / 100);
                $prov_numbers[] = 'VAT-I2';
            }
        }

        return [
            'benchmark_tax' => round($te_amount, 2), // Since actual tax is 0 (exempt), Benchmark = TE
            'te_amount' => round($te_amount, 2),
            'provision_number' => !empty($prov_numbers) ? implode(', ', $prov_numbers) : 'None'
        ];
    }

    public function getBatchSummary(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total, SUM(te_amount) as total_te, SUM(amount_infra_basic + amount_infra_other + amount_utility_usage + amount_infra_dev) as total_investment FROM import_sez_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_te' => 0, 'total_investment' => 0];
    }
}
