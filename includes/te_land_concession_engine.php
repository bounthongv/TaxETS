<?php
class TELandConcessionEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        $records = $this->pdo->prepare("SELECT * FROM repo_land_concession_data WHERE import_batch_id = ?");
        $records->execute([$batch_id]);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($records->fetchAll(PDO::FETCH_ASSOC) as $row) {
            try {
                $result = $this->calculateRecord($row);
                $stmt = $this->pdo->prepare("
                    UPDATE repo_land_concession_data
                    SET benchmark_value_usd = ?, non_tax_te_usd = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $result["benchmark_value_usd"],
                    $result["non_tax_te_usd"],
                    $row["id"]
                ]);
                $total_te += $result["non_tax_te_usd"];
                $total_calculated++;
            } catch (Exception $e) {
                $errors[] = "TIN {$row['tin']} (ID: {$row['id']}): " . $e->getMessage();
            }
        }

        return [
            "calculated" => $total_calculated,
            "total_te" => $total_te,
            "errors" => $errors
        ];
    }

    public function getBatchSummary(string $batch_id): array {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total,
                COALESCE(SUM(concession_area_ha), 0) as total_area_ha,
                COALESCE(SUM(concession_fee_paid_usd), 0) as total_paid_usd,
                COALESCE(SUM(benchmark_value_usd), 0) as total_benchmark_usd,
                COALESCE(SUM(non_tax_te_usd), 0) as total_te_usd
            FROM repo_land_concession_data
            WHERE import_batch_id = ?
        ");
        $stmt->execute([$batch_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            "total" => 0,
            "total_area_ha" => 0,
            "total_paid_usd" => 0,
            "total_benchmark_usd" => 0,
            "total_te_usd" => 0
        ];
    }

    private function calculateRecord(array $row): array {
        $area = (float)($row["concession_area_ha"] ?? 0);
        $benchmarkRate = (float)($row["benchmark_rate_usd"] ?? 0);
        $paid = (float)($row["concession_fee_paid_usd"] ?? 0);
        $provisionName = trim((string)($row["provision_name"] ?? ""));

        if ($area <= 0) {
            throw new Exception("Concession area must be greater than zero");
        }
        if ($benchmarkRate <= 0) {
            throw new Exception("Benchmark rate must be greater than zero");
        }
        if ($paid < 0) {
            throw new Exception("Paid concession fee cannot be negative");
        }

        $benchmarkValue = $area * $benchmarkRate;
        $teAmount = $provisionName === "" ? 0.0 : max(0, $benchmarkValue - $paid);

        return [
            "benchmark_value_usd" => round($benchmarkValue, 4),
            "non_tax_te_usd" => round($teAmount, 4)
        ];
    }

    private function calculateCompany(array $company): array {
        $year = (int)date("Y");
        
        $article = $company["land_concession_article"] ?? null;
        $item = $company["land_concession_item"] ?? null;
        $zone = (int)($company["land_concession_zone"] ?? 1);
        
        $bm = $this->lookupBenchmarkRate($article, $item, $year);
        if (!$bm) {
            throw new Exception("No benchmark rate found for Article: $article, Item: $item");
        }

        // Determine rate based on zone
        $rate = 0;
        if ($zone == 1) $rate = $bm["rate_zone1"];
        elseif ($zone == 2) $rate = $bm["rate_zone2"];
        elseif ($zone == 3) $rate = $bm["rate_zone3"];
        else $rate = $bm["rate_zone1"];

        $land_area = (float)($company["land_area_sqm"] ?? 0);
        
        // Convert USD to Kip if needed (assuming benchmark is in USD as per our seeds)
        // In a real system, we'd have an exchange rate table. 
        // For now, we'll assume the benchmark is the base value.
        $land_value_kip = $land_area * (float)$rate;

        $provision = $this->lookupProvision($company["business_type"] ?? null);
        $exemption_years = $provision ? (int)$provision["exemption_years"] : 0;
        
        // Exemption value calculation (this might be more complex in reality, e.g. 100% for N years)
        // Here we'll stick to a simple formula for demonstration
        $exemption_value = $exemption_years > 0 ? $land_value_kip * 0.5 : 0; // 50% exemption if any provision applies? Just an example.

        $te_land_concession = $exemption_value; // The TE is the amount NOT paid due to the provision

        return [
            "zone_type" => "Zone " . $zone,
            "benchmark_rate" => $rate,
            "land_value_kip" => $land_value_kip,
            "exemption_years" => $exemption_years,
            "exemption_value" => $exemption_value,
            "te_land_concession" => $te_land_concession
        ];
    }

    private function lookupBenchmarkRate(?string $article, ?string $item, int $year): ?array {
        if (!$article || !$item) return null;
        
        $stmt = $this->pdo->prepare("SELECT * FROM bm_land_concession 
            WHERE article_no = ? AND item_no = ? 
            AND start_year <= ? AND (end_year IS NULL OR end_year >= ?) 
            AND active = 1 LIMIT 1");
        $stmt->execute([$article, $item, $year, $year]);
        return $stmt->fetch() ?: null;
    }

    private function lookupProvision(?string $business_type): ?array {
        if (!$business_type) return null;
        
        $stmt = $this->pdo->prepare("SELECT * FROM land_concession_provisions WHERE category = ? AND active = 1 LIMIT 1");
        $stmt->execute([$business_type]);
        return $stmt->fetch() ?: null;
    }
}
