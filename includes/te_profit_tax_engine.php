<?php
/**
 * Tax Expenditure Calculation Engine
 * Implements the configurable rule-based approach from the 1-plan.md design.
 */
class TEEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Run the full TE calculation for a specific import batch.
     * Returns a summary array.
     */
    public function calculateBatch(string $batch_id): array {
        // Clear previous results for this batch
        $this->pdo->prepare("DELETE tr FROM te_profit_result tr JOIN companies c ON tr.company_id = c.id WHERE c.import_batch_id = ?")->execute([$batch_id]);

        $companies = $this->pdo->prepare("SELECT * FROM companies WHERE import_batch_id = ?");
        $companies->execute([$batch_id]);

        $total_calculated = 0;
        $total_te = 0.0;
        $errors = [];

        foreach ($companies->fetchAll() as $company) {
            try {
                $result = $this->calculateCompany($company);
                $stmt = $this->pdo->prepare("INSERT INTO te_profit_result (company_id, benchmark_rate_applied, benchmark_pt, pt_te, matched_provisions, profit_tax_te, expert_te) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $company["id"],
                    $result["benchmark_rate"],
                    $result["benchmark_pt"],
                    $result["pt_te"],
                    $result["matched_provisions"],
                    $result["profit_tax_te"],
                    $company["expert_te"] ?? null
                ]);
                $total_te += $result["profit_tax_te"];
                $total_calculated++;
            } catch (Exception $e) {
                $errors[] = "Company ID {$company['id']}: " . $e->getMessage();
            }
        }

        return [
            "calculated" => $total_calculated,
            "total_te" => $total_te,
            "errors" => $errors
        ];
    }

    /**
     * Calculate TE for a single company row.
     */
    private function calculateCompany(array $c): array {
        $year = (int)$c["tax_year"];
        $benchmark_pt = 0.0;
        $benchmark_rate = 0.0;

        // -----------------------------------------------------------------
        // STEP 1: Determine Benchmark PT based on VAT Holder status
        // -----------------------------------------------------------------
        if (!$c["is_vat_holder"]) {
            // --- PATH A: Mandatory / Non-VAT Holder ---
            // 2-step: Estimated Income * Profit Base Rate = Estimated Profit, then * Standard Rate
            $base_rule = $this->lookupMandatoryRate($year, $c["sector"]);
            $std_rate = $this->lookupStandardRate($year, $c["sector"]);
            if ($base_rule && $std_rate) {
                // Note: for mandatory payers, revenue column = estimated income
                $estimated_profit = $c["revenue"] * ($base_rule / 100);
                $benchmark_pt = $estimated_profit * ($std_rate / 100);
                $benchmark_rate = $std_rate;
            }
        } else {
            // --- PATH B: VAT Holder ---
            $sme_rate = $this->lookupSMERate($year, $c["sector"], $c["revenue"]);
            if ($sme_rate !== null) {
                // PATH B1: Micro Enterprise / SME
                $benchmark_pt = $c["revenue"] * ($sme_rate / 100);
                $benchmark_rate = $sme_rate;
            } else {
                // PATH B2: Standard Large Enterprise
                $std_rate = $this->lookupStandardRate($year, $c["sector"]);
                if ($std_rate && $c["net_profit"] >= 0) {
                    $taxable = $c["net_profit"] + $c["re_invested_profit"];
                    $benchmark_pt = $taxable * ($std_rate / 100);
                    $benchmark_rate = $std_rate;
                }
            }
        }

        // -----------------------------------------------------------------
        // STEP 2: Calculate PT TE
        // -----------------------------------------------------------------
        $pt_te = 0.0;
        if ($benchmark_pt > 0 && $c["pt_paid"] <= $benchmark_pt) {
            $pt_te = $benchmark_pt - $c["pt_paid"];
        }

        // -----------------------------------------------------------------
        // STEP 3: Classify TE by Provision
        // -----------------------------------------------------------------
        $matched_provisions = $this->matchProvisions($c);

        return [
            "benchmark_rate"     => $benchmark_rate,
            "benchmark_pt"       => round($benchmark_pt, 2),
            "pt_te"              => round($pt_te, 2),
            "matched_provisions" => implode(", ", $matched_provisions),
            "profit_tax_te"      => round($pt_te, 2),
        ];
    }

    /**
     * Lookup standard PT rate from bm_profit_standard
     */
    private function lookupStandardRate(int $year, string $sector): ?float {
        // Determine category from sector
        $category = "Standard";
        if (stripos($sector, "tobacco") !== false) $category = "Tobacco";
        if (stripos($sector, "mining") !== false || stripos($sector, "electricity") !== false) $category = "Mining/Electricity";

        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_profit_standard WHERE start_year <= ? AND end_year >= ? AND category = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$year, $year, $category]);
        $row = $stmt->fetch();
        return $row ? (float)$row["rate_percentage"] : null;
    }

    /**
     * Lookup mandatory profit base rate from bm_profit_mandatory
     */
    private function lookupMandatoryRate(int $year, string $sector): ?float {
        $stmt = $this->pdo->prepare("SELECT profit_base_rate FROM bm_profit_mandatory WHERE start_year <= ? AND end_year >= ? AND ? LIKE CONCAT('%', sector, '%') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$year, $year, $sector]);
        $row = $stmt->fetch();
        // Fallback: try broader match
        if (!$row) {
            $stmt2 = $this->pdo->prepare("SELECT profit_base_rate FROM bm_profit_mandatory WHERE start_year <= ? AND end_year >= ? ORDER BY id DESC LIMIT 1");
            $stmt2->execute([$year, $year]);
            $row = $stmt2->fetch();
        }
        return $row ? (float)$row["profit_base_rate"] : null;
    }

    /**
     * Lookup SME rate from bm_profit_sme. Returns null if not an SME.
     */
    private function lookupSMERate(int $year, string $sector, float $revenue): ?float {
        $stmt = $this->pdo->prepare("SELECT rate_percentage FROM bm_profit_sme WHERE start_year <= ? AND end_year >= ? AND ? >= turnover_min AND (turnover_max IS NULL OR ? <= turnover_max) ORDER BY turnover_min DESC LIMIT 1");
        $stmt->execute([$year, $year, $revenue, $revenue]);
        $row = $stmt->fetch();
        return $row ? (float)$row["rate_percentage"] : null;
    }

    /**
     * Evaluate all configured provisions against a company record.
     * Returns array of matching provision numbers.
     */
    private function matchProvisions(array $c): array {
        $matched = [];
        $year = (int)$c["tax_year"];
        $provisions = $this->pdo->query("SELECT p.*, GROUP_CONCAT(CONCAT(cond.field_name,'|',cond.operator,'|',cond.value_1,'|',COALESCE(cond.value_2,'')) ORDER BY cond.id SEPARATOR ';;') AS conditions FROM profit_provisions p LEFT JOIN profit_provision_conditions cond ON cond.provision_id = p.id WHERE $year >= p.start_year AND $year <= p.end_year GROUP BY p.id ORDER BY p.id")->fetchAll();

        foreach ($provisions as $prov) {
            if (empty($prov["conditions"])) continue; // Skip provisions with no rules
            $all_pass = true;

            foreach (explode(";;", $prov["conditions"]) as $cond_str) {
                [$field, $op, $val1, $val2] = explode("|", $cond_str . "|||");
                if (empty($field)) continue;

                $company_val = $c[$field] ?? null;

                if ($op === "YEARS_PASSED_LESS_THAN") {
                    // Check if the number of years since a date field is < val1
                    if (empty($company_val)) { $all_pass = false; break; }
                    $date = is_numeric($company_val) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($company_val) : new DateTime($company_val);
                    $years = (new DateTime())->diff($date)->y;
                    if (!($years < (float)$val1)) { $all_pass = false; break; }
                } elseif ($op === "BETWEEN") {
                    if (!($company_val >= (float)$val1 && $company_val <= (float)$val2)) { $all_pass = false; break; }
                } else {
                    // Standard operators: =, >=, <=, >, <
                    $expr = false;
                    switch ($op) {
                        case "=":  $expr = ((string)$company_val === (string)$val1); break;
                        case ">=": $expr = ($company_val >= (float)$val1); break;
                        case "<=": $expr = ($company_val <= (float)$val1); break;
                        case ">":  $expr = ($company_val > (float)$val1); break;
                        case "<":  $expr = ($company_val < (float)$val1); break;
                    }
                    if (!$expr) { $all_pass = false; break; }
                }
            }

            if ($all_pass) {
                $matched[] = $prov["provision_number"];
            }
        }
        return $matched;
    }
}
?>
