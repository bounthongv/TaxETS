<?php

namespace TaxETS\Services;

use PDO;
use PDOException;

class TaxCalculator
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function calculateBenchmarkTax(array $companyData): array
    {
        $calculationYear = $companyData['calculation_year'] ?? null;
        $annualTurnover = $companyData['annual_turnover_billion'] ?? 0.0;
        $sector = $companyData['sector'] ?? null;
        $isAccountingHolder = $companyData['is_accounting_holder'] ?? 0;
        $actualProfit = ($companyData['revenue'] ?? 0) - ($companyData['expense'] ?? 0);
        $estimatedTurnover = $companyData['annual_turnover_billion'] ?? 0.0;
        $businessActivityDescription = $companyData['business_activity_description'] ?? $sector;

        if ($calculationYear === null || $sector === null) {
            throw new \Exception("Missing required data (calculation_year or sector) for benchmark calculation.");
        }

        $annualTurnoverActual = $annualTurnover * 1000000000;

        // --- FIX: Use unique named placeholders for the date to avoid HY093 error ---
        $sql = "SELECT id, standard_profit_tax_rate, micro_enterprise_turnover_threshold FROM benchmark_profit_tax_regimes WHERE :year_date1 >= valid_from AND (:year_date2 <= valid_to OR valid_to IS NULL) LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':year_date1' => $calculationYear . '-01-01',
            ':year_date2' => $calculationYear . '-12-31'
        ]);
        $regime = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$regime) {
            throw new \Exception("No active tax regime found for year: " . $calculationYear);
        }

        $regimeId = (int)$regime['id'];
        $standardProfitTaxRate = (float)$regime['standard_profit_tax_rate'];
        $microEnterpriseTurnoverThreshold = (float)$regime['micro_enterprise_turnover_threshold'];

        $benchmarkTax = 0.0;
        $isMicroEnterprise = false;

        if ($annualTurnoverActual <= $microEnterpriseTurnoverThreshold) {
            $isMicroEnterprise = true;
            $stmt = $this->pdo->prepare("SELECT calculation_method, production_rate, commerce_rate, services_rate FROM benchmark_micro_enterprise_turnover_tax_rules WHERE regime_id = :regime_id AND :annual_turnover BETWEEN min_turnover AND max_turnover");
            $stmt->execute([':regime_id' => $regimeId, ':annual_turnover' => $annualTurnoverActual]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rule) {
                $benchmarkTax = $actualProfit * $standardProfitTaxRate;
            } else {
                switch ($rule['calculation_method']) {
                    case 'ZERO':
                        $benchmarkTax = 0.0;
                        break;
                    case 'LUMP_SUM':
                        $benchmarkTax = 0.0;
                        break;
                    case 'RATE':
                        $rate = 0.0;
                        if (stripos($sector, 'Production') !== false || stripos($sector, 'Agriculture') !== false || stripos($sector, 'Industry') !== false) {
                            $rate = (float)$rule['production_rate'];
                        } elseif (stripos($sector, 'Commerce') !== false) {
                            $rate = (float)$rule['commerce_rate'];
                        } else {
                            $rate = (float)$rule['services_rate'];
                        }
                        $benchmarkTax = $annualTurnoverActual * $rate;
                        break;
                }
            }
        } else {
            if ($isAccountingHolder == 1) {
                $finalTaxRate = $standardProfitTaxRate;

                $stmt = $this->pdo->prepare("SELECT profit_tax_rate FROM benchmark_standard_sector_rates WHERE regime_id = :regime_id AND sector_name = :sector_name");
                $stmt->execute([':regime_id' => $regimeId, ':sector_name' => $sector]);
                $sectorRate = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($sectorRate) {
                    $finalTaxRate = (float)$sectorRate['profit_tax_rate'];
                }
                $benchmarkTax = $actualProfit * $finalTaxRate;

            } else {
                if ($businessActivityDescription === null) {
                    throw new \Exception("Missing business_activity_description for mandatory estimation.");
                }

                $stmt = $this->pdo->prepare("SELECT profit_estimation_rate FROM benchmark_mandatory_estimation_rates WHERE regime_id = :regime_id AND activity_description = :activity_description");
                $stmt->execute([':regime_id' => $regimeId, ':activity_description' => $businessActivityDescription]);
                $estimationRate = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$estimationRate) {
                    throw new \Exception("Profit estimation rate not found for activity: " . $businessActivityDescription);
                }
                $profitEstimationRate = (float)$estimationRate['profit_estimation_rate'];

                $estimatedProfit = ($estimatedTurnover * 1000000000) * $profitEstimationRate;
                $benchmarkTax = $estimatedProfit * $standardProfitTaxRate;
            }
        }

        return [
            'benchmark_tax' => round($benchmarkTax, 2),
            'is_micro_enterprise' => $isMicroEnterprise,
            'actual_profit' => $actualProfit,
            'standard_profit_tax_rate' => $standardProfitTaxRate
        ];
    }
    
    public function calculateTaxPayableByProvision(array $companyData, float $benchmarkTax, bool $isMicroEnterprise, float $actualProfit, float $standardProfitTaxRate): float
    {
        $calculationYear = $companyData['calculation_year'] ?? null;
        if ($calculationYear === null) {
            throw new \Exception("Missing calculation year for TE application.");
        }

        $taxPayableByProvision = $benchmarkTax;
        $eligibleTEs = [];

        // --- FIX: Use unique named placeholders for the date to avoid HY093 error ---
        $stmt = $this->pdo->prepare("SELECT id, te_type, priority FROM te_provisions WHERE tax_type = 'Profit Tax' AND :year_date1 >= effective_from_date AND (:year_date2 <= effective_to_date OR effective_to_date IS NULL)");
        $stmt->execute([
            ':year_date1' => $calculationYear . '-01-01',
            ':year_date2' => $calculationYear . '-12-31'
        ]);
        $potentialTEs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($potentialTEs as $teProvision) {
            $teProvisionId = (int)$teProvision['id'];
            $isEligible = true;

            $condStmt = $this->pdo->prepare("SELECT data_field_name, operator, value FROM te_conditions WHERE te_provision_id = :te_provision_id");
            $condStmt->execute([':te_provision_id' => $teProvisionId]);
            $conditions = $condStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($conditions as $condition) {
                $dataFieldName = $condition['data_field_name'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                $companyValue = null;

                switch ($dataFieldName) {
                    case 'years_since_registration':
                        $companyValue = $this->_getYearsSinceDate($companyData['registration_date'] ?? null, $calculationYear);
                        break;
                    case 'years_since_first_revenue':
                        $companyValue = $this->_getYearsSinceDate($companyData['date_first_revenue'] ?? null, $calculationYear);
                        break;
                    case 'years_since_stock_listing':
                        $companyValue = $this->_getYearsSinceDate($companyData['stock_listing_date'] ?? null, $calculationYear);
                        break;
                    case 'is_after_tax_holiday':
                        $companyValue = $this->_isAfterTaxHoliday($companyData, $calculationYear);
                        break;
                    case 'years_since_holiday_end':
                        $companyValue = $this->_getYearsSinceHolidayEnd($companyData, $calculationYear);
                        break;
                    case 'is_micro_enterprise_in_vat':
                        $companyData['is_micro_enterprise'] = $isMicroEnterprise;
                        $companyValue = $this->_isMicroEnterpriseInVat($companyData);
                        break;
                    case 'invested_in_ipl_art9_activity':
                        $companyValue = $this->_getInvestedInIplArt9Activity($companyData);
                        break;
                    case 'is_eligible_for_additional_sez_exemption':
                        $companyValue = $this->_isEligibleForAdditionalSezExemption($companyData);
                        break;
                    default:
                        $companyValue = $companyData[$dataFieldName] ?? null;
                        break;
                }

                if (is_bool($companyValue)) {
                    $companyValue = $companyValue ? 1 : 0;
                }

                if (!$this->_evaluateCondition($companyValue, $operator, $value)) {
                    $isEligible = false;
                    break;
                }
            }

            if ($isEligible) {
                $eligibleTEs[] = $teProvision;
            }
        }

        if (!empty($eligibleTEs)) {
            usort($eligibleTEs, function ($a, $b) {
                $typeOrder = ['Exemption' => 1, 'Deduction' => 2, 'Rate Relief' => 3, 'Deferral' => 4];
                $orderA = $typeOrder[$a['te_type']] ?? 99;
                $orderB = $typeOrder[$b['te_type']] ?? 99;

                if ($orderA == $orderB) {
                    return $a['priority'] <=> $b['priority'];
                }
                return $orderA <=> $orderB;
            });
            $finalApplicableTE = $eligibleTEs[0];
            $appliedTeId = (int)$finalApplicableTE['id'];

            $effectStmt = $this->pdo->prepare("SELECT effect_type, parameter_name, value FROM te_effects WHERE te_provision_id = :te_provision_id");
            $effectStmt->execute([':te_provision_id' => $appliedTeId]);
            $effect = $effectStmt->fetch(PDO::FETCH_ASSOC);

            if ($effect) {
                switch ($effect['effect_type']) {
                    case 'SET_TAX_RATE':
                        $newRate = (float)$effect['value'];
                        $taxPayableByProvision = $actualProfit * $newRate;
                        break;
                    case 'EXEMPTION':
                        $taxPayableByProvision = 0.0;
                        break;
                    case 'REDUCE_TAXABLE_BASE':
                        $reductionAmount = ($effect['value'] === 'reinvested_profit_amount') ? ($companyData['reinvested_profit_amount'] ?? 0.0) : (float)$effect['value'];
                        $newTaxableProfit = max(0, $actualProfit - $reductionAmount);
                        $taxPayableByProvision = $newTaxableProfit * $standardProfitTaxRate;
                        break;
                }
            }
        }
        
        return round($taxPayableByProvision, 2);
    }
    
    public function identifyAppliedProvisionId(array $companyData): ?int
    {
        $appliedTeIdsJson = $companyData['applied_te_ids_from_import'] ?? '[]';
        $appliedTeIds = json_decode($appliedTeIdsJson, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($appliedTeIds) && !empty($appliedTeIds)) {
            if(is_numeric($appliedTeIds[0])){
                 return (int)$appliedTeIds[0];
            }
        }
        return null;
    }

    private function _evaluateCondition($companyValue, $operator, $value): bool
    {
        switch ($operator) {
            case '=': return ($companyValue == $value);
            case '!=': return ($companyValue != $value);
            case '>': return ($companyValue > $value);
            case '>=': return ($companyValue >= $value);
            case '<': return ($companyValue < $value);
            case '<=': return ($companyValue <= $value);
            case 'IN':
                $valueArray = explode(';', $value);
                return in_array($companyValue, $valueArray);
            case 'BETWEEN':
                $valueParts = explode(';', $value);
                if (count($valueParts) == 2) {
                    $min = (float)$valueParts[0];
                    $max = (float)$valueParts[1];
                    return ($companyValue >= $min && $companyValue <= $max);
                }
                return false;
            default: return false;
        }
    }

    private function _getYearsSinceDate(?string $dateString, int $calculationYear): ?int
    {
        if (empty($dateString)) {
            return null;
        }
        try {
            $date = new \DateTime($dateString);
            $year = (int)$date->format('Y');
            return $calculationYear - $year;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function _isAfterTaxHoliday(array $companyData, int $calculationYear): bool
    {
        $taxHolidayPeriod = $companyData['tax_holiday_period_years'] ?? 0;
        $yearsSinceFirstRevenue = $this->_getYearsSinceDate($companyData['date_first_revenue'] ?? null, $calculationYear);

        if ($yearsSinceFirstRevenue === null) {
            return false;
        }

        return $yearsSinceFirstRevenue > $taxHolidayPeriod;
    }

    private function _getYearsSinceHolidayEnd(array $companyData, int $calculationYear): ?int
    {
        $taxHolidayPeriod = $companyData['tax_holiday_period_years'] ?? 0;
        $yearsSinceFirstRevenue = $this->_getYearsSinceDate($companyData['date_first_revenue'] ?? null, $calculationYear);

        if ($yearsSinceFirstRevenue === null) {
            return null;
        }

        if ($yearsSinceFirstRevenue <= $taxHolidayPeriod) {
            return 0;
        }

        return $yearsSinceFirstRevenue - $taxHolidayPeriod;
    }

    private function _isMicroEnterpriseInVat(array $companyData): bool
    {
        $isMicroEnterprise = $companyData['is_micro_enterprise'] ?? false;
        $isVatHolder = $companyData['is_vat_holder'] ?? 0;

        return $isMicroEnterprise && ($isVatHolder == 1);
    }

    private function _getInvestedInIplArt9Activity(array $companyData): ?string
    {
        $iplFlagsJson = $companyData['ipl_activity_flags'] ?? null;
        if (empty($iplFlagsJson)) {
            return null;
        }

        $flags = json_decode($iplFlagsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $investedActivities = [];
        foreach ($flags as $activityKey => $isInvested) {
            if ($isInvested == 1) {
                if (preg_match('/activity_(\d+)/', $activityKey, $matches)) {
                    $investedActivities[] = (int)$matches[1];
                }
            }
        }
        sort($investedActivities);
        return empty($investedActivities) ? null : implode(';', $investedActivities);
    }

    private function _isEligibleForAdditionalSezExemption(array $companyData): bool
    {
        return false;
    }
}