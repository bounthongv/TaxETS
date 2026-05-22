<?php

class TEResourceEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM import_resource_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($records as $row) {
            try {
                $result = $this->calculateResourceTE($row);

                $updateStmt = $this->pdo->prepare("UPDATE import_resource_data SET benchmark_rate = ?, benchmark_fee = ?, te_amount = ?, calculated_at = NOW() WHERE id = ?");
                $updateStmt->execute([
                    $result['benchmark_rate'],
                    $result['benchmark_fee'],
                    $result['te_amount'],
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

    public function calculateResourceTE(array $row): array {
        $actual_rate = (float)$row['actual_rate'];
        $fee_collected = (float)$row['fee_collected'];
        $year = (int)$row['tax_year'];
        $resource_type = trim($row['resource_type']);

        // Find benchmark rate
        // We match by item_no or item_name
        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_natural_resource WHERE (item_no = ? OR item_name = ?) AND start_year <= ? AND (end_year IS NULL OR end_year >= ?) LIMIT 1");
        $stmt->execute([$resource_type, $resource_type, $year, $year]);
        $benchmark_rate = (float)$stmt->fetchColumn();

        if (!$benchmark_rate) {
            // Default rate if not found (needs validation from MOF expert)
            $benchmark_rate = 5.0; 
        }

        $te_amount = 0.0;
        $benchmark_fee = $fee_collected;

        if ($actual_rate < $benchmark_rate && $actual_rate > 0) {
            $base_value = $fee_collected / ($actual_rate / 100);
            $benchmark_fee = $base_value * ($benchmark_rate / 100);
            $te_amount = $benchmark_fee - $fee_collected;
        }

        return [
            'benchmark_rate' => $benchmark_rate,
            'benchmark_fee' => round($benchmark_fee, 2),
            'te_amount' => round($te_amount, 2)
        ];
    }

    public function getBatchSummary(string $batch_id): array {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total, SUM(te_amount) as total_te, SUM(fee_collected) as total_collected, MAX(calculated_at) as last_calc FROM import_resource_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_te' => 0, 'total_collected' => 0, 'last_calc' => null];
    }
}
