<?php
/**
 * Tax Expenditure Calculation Engine
 */
class TEEngine {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function calculateBatch(string $batch_id): array {
        $this->pdo->prepare("DELETE tr FROM te_profit_result tr JOIN companies c ON tr.company_id = c.id WHERE c.import_batch_id = ?")->execute([$batch_id]);
        $companies = $this->pdo->prepare("SELECT * FROM companies WHERE import_batch_id = ?");
        $companies->execute([$batch_id]);
        $total_calculated = 0; $total_te = 0.0; $errors = [];
        foreach ($companies->fetchAll() as $company) {
            try {
                $result = $this->calculateCompany($company);
                $stmt = $this->pdo->prepare("INSERT INTO te_profit_result (company_id, benchmark_rate_applied, benchmark_pt, pt_te, matched_provisions, profit_tax_te) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([ $company["id"], $result["benchmark_rate"], $result["benchmark_pt"], $result["pt_te"], $result["matched_provisions"], $result["profit_tax_te"] ]);
                $total_te += $result["profit_tax_te"]; $total_calculated++;
            } catch (Exception $e) { $errors[] = "Company ID {$company['id']}: " . $e->getMessage(); }
        }
        return ["calculated" => $total_calculated, "total_te" => $total_te, "errors" => $errors];
    }

    private function calculateCompany(array $c): array {
        $year = (int)$c["tax_year"]; $benchmark_pt = 0.0; $benchmark_rate = 0.0;
        if (!$c["is_vat_holder"]) {
            $base_rule = $this->lookupMandatoryRate($year, $c["sector"]);
            $std_rate = $this->lookupStandardRate($year, $c["sector"]);
            if ($base_rule && $std_rate) { $benchmark_pt = $c["revenue"] * ($base_rule / 100) * ($std_rate / 100); $benchmark_rate = $std_rate; }
        } else {
            $sme_rate = $this->lookupSMERate($year, $c["sector"], $c["revenue"]);
            if ($sme_rate !== null) { $benchmark_pt = $c["revenue"] * ($sme_rate / 100); $benchmark_rate = $sme_rate; }
            else {
                $std_rate = $this->lookupStandardRate($year, $c["sector"]);
                if ($std_rate && $c["net_profit"] >= 0) { $benchmark_pt = ($c["net_profit"] + $c["re_invested_profit"]) * ($std_rate / 100); $benchmark_rate = $std_rate; }
            }
        }
        $pt_te = ($benchmark_pt > 0 && $c["pt_paid"] <= $benchmark_pt) ? $benchmark_pt - $c["pt_paid"] : 0.0;
        $matched = $this->matchProvisions($c);
        return [ "benchmark_rate" => $benchmark_rate, "benchmark_pt" => round($benchmark_pt, 2), "pt_te" => round($pt_te, 2), "matched_provisions" => implode(", ", $matched), "profit_tax_te" => round($pt_te, 2) ];
    }

    private function lookupStandardRate(int $year, string $sector): ?float {
        $cat = "Standard";
        if (stripos($sector, "tobacco") !== false) $cat = "Tobacco";
        elseif (stripos($sector, "mining") !== false || stripos($sector, "electricity") !== false) $cat = "Mining/Electricity";
        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_profit_standard WHERE start_year <= ? AND end_year >= ? AND category = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$year, $year, $cat]);
        $row = $stmt->fetch(); return $row ? (float)$row["rate_percentage"] : null;
    }

    private function lookupMandatoryRate(int $year, string $sector): ?float {
        $stmt = $this->pdo->prepare("SELECT profit_base_rate FROM bm_profit_mandatory WHERE start_year <= ? AND end_year >= ? AND ? LIKE CONCAT('%', sector, '%') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$year, $year, $sector]); $row = $stmt->fetch();
        if (!$row) { $stmt2 = $this->pdo->prepare("SELECT profit_base_rate FROM bm_profit_mandatory WHERE start_year <= ? AND end_year >= ? ORDER BY id DESC LIMIT 1"); $stmt2->execute([$year, $year]); $row = $stmt2->fetch(); }
        return $row ? (float)$row["profit_base_rate"] : null;
    }

    private function lookupSMERate(int $year, string $sector, float $revenue): ?float {
        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_profit_sme WHERE start_year <= ? AND end_year >= ? AND ? >= turnover_min AND (turnover_max IS NULL OR ? <= turnover_max) ORDER BY turnover_min DESC LIMIT 1");
        $stmt->execute([$year, $year, $revenue, $revenue]); $row = $stmt->fetch(); return $row ? (float)$row["rate_percentage"] : null;
    }

    private function matchProvisions(array $c): array {
        $matched = []; $provisions = $this->pdo->query("SELECT p.*, GROUP_CONCAT(CONCAT(cond.field_name,'|',cond.operator,'|',cond.value_1,'|',COALESCE(cond.value_2,'')) ORDER BY cond.id SEPARATOR ';;') AS conditions FROM profit_provisions p LEFT JOIN profit_provision_conditions cond ON cond.provision_id = p.id GROUP BY p.id ORDER BY p.id")->fetchAll();
        foreach ($provisions as $prov) {
            if (empty($prov["conditions"])) continue; $all_pass = true;
            foreach (explode(";;", $prov["conditions"]) as $cond_str) {
                [$field, $op, $val1, $val2] = explode("|", $cond_str . "|||"); if (empty($field)) continue; $company_val = $c[$field] ?? null;
                $eval_date = new DateTime($c["tax_year"] . "-12-31");
                if ($op === "YEARS_PASSED_LESS_THAN") { if (empty($company_val)) { $all_pass = false; break; } $years = $eval_date->diff($this->parseDate($company_val))->y; if (!($years < (float)$val1)) { $all_pass = false; break; } }
                elseif ($op === "TAX_HOLIDAY_ENDED") { if (empty($company_val)) { $all_pass = false; break; } $end_date = clone $this->parseDate($company_val); $end_date->modify("+".(int)$c["tax_holiday_years"]." years"); if (!($end_date <= $eval_date)) { $all_pass = false; break; } }
                elseif ($op === "EDATE_MONTHS_NOT_EXCEEDED") { if (empty($company_val)) { $all_pass = false; break; } $lim = clone $this->parseDate($company_val); $lim->modify("+".(int)$val1." months"); if (!($lim <= $eval_date)) { $all_pass = false; break; } }
                elseif ($op === "EDATE_MONTHS_BETWEEN") { if (empty($company_val)) { $all_pass = false; break; } $d1 = clone $this->parseDate($company_val); $d1->modify("+".(int)$val1." months"); $d2 = clone $this->parseDate($company_val); $d2->modify("+".(int)$val2." months"); if (!($eval_date >= $d1 && $eval_date <= $d2)) { $all_pass = false; break; } }
                elseif ($op === "BETWEEN") { if (!($company_val >= (float)$val1 && $company_val <= (float)$val2)) { $all_pass = false; break; } }
                else {
                    $expr = false;
                    switch ($op) { case "=": $expr = ((string)$company_val === (string)$val1); break; case ">=": $expr = ($company_val >= (float)$val1); break; case "<=": $expr = ($company_val <= (float)$val1); break; case ">": $expr = ($company_val > (float)$val1); break; case "<": $expr = ($company_val < (float)$val1); break; }
                    if (!$expr) { $all_pass = false; break; }
                }
            }
            if ($all_pass) $matched[] = $prov["provision_number"];
        }
        return $matched;
    }

    private function parseDate($val): DateTime { if (is_numeric($val)) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val); return new DateTime($val); }
}
?>
