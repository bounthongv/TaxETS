<?php
class TELandConcessionEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function calculateBatch(string $batch_id): array {
        $this->pdo->prepare("DELETE FROM te_land_concession_result WHERE company_id IN (SELECT id FROM companies WHERE import_batch_id = ?)")
            ->execute([$batch_id]);

        $companies = $this->pdo->prepare("SELECT * FROM companies WHERE import_batch_id = ? AND land_area_sqm > 0");
        $companies->execute([$batch_id]);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($companies->fetchAll() as $company) {
            try {
                $result = $this->calculateCompany($company);
                $stmt = $this->pdo->prepare("INSERT INTO te_land_concession_result 
                    (company_id, zone_type, benchmark_rate, land_value_kip, exemption_years, exemption_value, te_land_concession) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $company["id"],
                    $result["zone_type"],
                    $result["benchmark_rate"],
                    $result["land_value_kip"],
                    $result["exemption_years"],
                    $result["exemption_value"],
                    $result["te_land_concession"]
                ]);
                $total_te += $result["te_land_concession"];
                $total_calculated++;
            } catch (Exception $e) {
                $errors[] = "Company ID {$company['id']} (Tax ID: {$company['tax_id']}): " . $e->getMessage();
            }
        }

        return [
            "calculated" => $total_calculated,
            "total_te" => $total_te,
            "errors" => $errors
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