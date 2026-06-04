<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";

$pdo = getDbConnection();
$errors = [];
$report_filters = reportFilterInput();

// Year selection
$all_years = [];
try {
    $queries = [
        "SELECT DISTINCT tax_year FROM companies WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM te_individual_result WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM import_salary_tax_data WHERE tax_year > 0",
        "SELECT DISTINCT YEAR(filing_period) FROM import_vat_data WHERE filing_period IS NOT NULL",
        "SELECT DISTINCT tax_year FROM import_sez_data WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM import_resource_data WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM import_royalty_data WHERE tax_year > 0",
    ];
    foreach ($queries as $q) {
        $stmt = $pdo->query($q);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $y = (int)$row[0];
            if ($y > 1900 && $y < 2100) $all_years[] = $y;
        }
    }
    $all_years = array_unique($all_years);
    sort($all_years);
} catch (Exception $e) { $errors[] = $e->getMessage(); }

$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0;
$to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;
if (!$from_year || !$to_year) {
    if (!empty($all_years)) { $from_year = min($all_years); $to_year = max($all_years); }
    else { $from_year = (int)date('Y') - 4; $to_year = (int)date('Y'); }
}
$display_years = range($from_year, $to_year);

// ==================== COLLECT DATA PER TAX TYPE ====================
// Structure: $data[typeKey] = [ 'label' => ..., 'icon' => ..., 'provisions' => [ provKey => [ year => te ] ], 'year_totals' => [ year => te ] ]

$taxTypes = [];
function addTypeData(array &$taxTypes, string $key, string $label, string $icon, array $provisionData, array $yearTotals) {
    $taxTypes[$key] = [
        'label' => $label,
        'icon' => $icon,
        'provisions' => $provisionData,
        'year_totals' => $yearTotals,
    ];
}

try {
    // ----- 1. CIT (Profit Tax) -----
    $provisionData = []; $yearTotals = [];
    // Provisions with labels
    $pStmt = $pdo->prepare("SELECT provision_number, legal_reference FROM profit_provisions WHERE start_year <= ? AND end_year >= ?");
    $pStmt->execute([$to_year, $from_year]);
    $citProvs = $pStmt->fetchAll();
    $citProvLabels = [];
    foreach ($citProvs as $p) {
        $citProvLabels[$p['provision_number']] = $p['provision_number'] . ' - ' . $p['legal_reference'];
    }

    $citDate = reportBatchDateExpression('c', 'import_batch_id');
    $citParams = [$from_year, $to_year];
    $citDateCondition = reportImportDateCondition($citDate, $report_filters, $citParams);
    $stmt = $pdo->prepare("SELECT p.provision_number, c.tax_year, SUM(tr.profit_tax_te) as te
        FROM te_profit_result tr
        JOIN companies c ON tr.company_id = c.id
        JOIN profit_provisions p ON FIND_IN_SET(p.provision_number, REPLACE(tr.matched_provisions, ', ', ','))
        WHERE c.tax_year BETWEEN ? AND ? {$citDateCondition} AND tr.profit_tax_te > 0 AND tr.matched_provisions IS NOT NULL AND tr.matched_provisions != ''
        GROUP BY p.provision_number, c.tax_year");
    $stmt->execute($citParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pn = $row['provision_number'];
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'CIT-' . $pn;
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    // Unclassified
    $citUnclassifiedParams = [$from_year, $to_year];
    $citUnclassifiedDateCondition = reportImportDateCondition($citDate, $report_filters, $citUnclassifiedParams);
    $stmt = $pdo->prepare("SELECT c.tax_year, SUM(tr.profit_tax_te) as te
        FROM te_profit_result tr
        JOIN companies c ON tr.company_id = c.id
        WHERE c.tax_year BETWEEN ? AND ? {$citUnclassifiedDateCondition} AND tr.profit_tax_te > 0 AND (tr.matched_provisions IS NULL OR tr.matched_provisions = '')
        GROUP BY c.tax_year");
    $stmt->execute($citUnclassifiedParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'CIT-Unclassified';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    // Grand total check
    $citTotalParams = [$from_year, $to_year];
    $citTotalDateCondition = reportImportDateCondition($citDate, $report_filters, $citTotalParams);
    $stmt = $pdo->prepare("SELECT c.tax_year, SUM(tr.profit_tax_te) as te FROM te_profit_result tr JOIN companies c ON tr.company_id = c.id WHERE c.tax_year BETWEEN ? AND ? {$citTotalDateCondition} AND tr.profit_tax_te > 0 GROUP BY c.tax_year");
    $stmt->execute($citTotalParams);
    $checkTotals = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $checkTotals[(int)$row['tax_year']] = (float)$row['te']; }
    foreach ($checkTotals as $y => $t) { if (abs($t - ($yearTotals[$y] ?? 0)) > 0.01) $yearTotals[$y] = ($yearTotals[$y] ?? 0) + ($t - ($yearTotals[$y] ?? 0)); }

    $citProvLabels['Unclassified'] = 'Unclassified / Other';
    addTypeData($taxTypes, 'CIT', 'Corporate Income Tax (CIT)', 'fa-building', $provisionData, $yearTotals);

    // ----- 2. PIT (Individual Income Tax) -----
    $provisionData = []; $yearTotals = [];
    $pStmt = $pdo->query("SELECT provision_number, legal_basis FROM individual_provisions");
    $pitProvLabels = [];
    while ($p = $pStmt->fetch(PDO::FETCH_ASSOC)) {
        $pitProvLabels[$p['provision_number']] = $p['provision_number'] . ' - ' . $p['legal_basis'];
    }
    $pitDate = "(SELECT MAX(ipd.import_date) FROM import_pit_data ipd WHERE ipd.ptin COLLATE utf8mb4_unicode_ci = r.tin COLLATE utf8mb4_unicode_ci AND ipd.tax_year = r.tax_year)";
    $pitParams = [$from_year, $to_year];
    $pitDateCondition = reportImportDateCondition($pitDate, $report_filters, $pitParams);
    $stmt = $pdo->prepare("SELECT p.provision_number, r.tax_year, SUM(r.te_amount) as te
        FROM te_individual_result r
        JOIN individual_provisions p ON FIND_IN_SET(p.provision_number COLLATE utf8mb4_general_ci, REPLACE(r.matched_provisions, ', ', ','))
        WHERE r.tax_year BETWEEN ? AND ? {$pitDateCondition} AND r.te_amount > 0 AND r.matched_provisions IS NOT NULL AND r.matched_provisions != ''
        GROUP BY p.provision_number, r.tax_year");
    $stmt->execute($pitParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pn = $row['provision_number'];
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'PIT-' . $pn;
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $pitUnclassifiedParams = [$from_year, $to_year];
    $pitUnclassifiedDateCondition = reportImportDateCondition($pitDate, $report_filters, $pitUnclassifiedParams);
    $stmt = $pdo->prepare("SELECT r.tax_year, SUM(r.te_amount) as te FROM te_individual_result r WHERE r.tax_year BETWEEN ? AND ? {$pitUnclassifiedDateCondition} AND r.te_amount > 0 AND (r.matched_provisions IS NULL OR r.matched_provisions = '') GROUP BY r.tax_year");
    $stmt->execute($pitUnclassifiedParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'PIT-Unclassified';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $pitProvLabels['Unclassified'] = 'Unclassified / Other';
    addTypeData($taxTypes, 'PIT', 'Individual Income Tax (PIT)', 'fa-user', $provisionData, $yearTotals);

    // ----- 3. Salary Tax -----
    $provisionData = []; $yearTotals = [];
    $salaryDate = reportBatchDateExpression('import_salary_tax_data', 'batch_id', 'import_date');
    $salaryParams = [$from_year, $to_year];
    $salaryDateCondition = reportImportDateCondition($salaryDate, $report_filters, $salaryParams);
    $stmt = $pdo->prepare("SELECT provision_number, tax_year, SUM(te_amount) as te FROM import_salary_tax_data WHERE tax_year BETWEEN ? AND ? {$salaryDateCondition} AND te_amount > 0 AND provision_number IS NOT NULL AND provision_number != '' GROUP BY provision_number, tax_year");
    $stmt->execute($salaryParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pn = $row['provision_number'];
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'Salary-' . $pn;
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $salaryUnclassifiedParams = [$from_year, $to_year];
    $salaryUnclassifiedDateCondition = reportImportDateCondition($salaryDate, $report_filters, $salaryUnclassifiedParams);
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_salary_tax_data WHERE tax_year BETWEEN ? AND ? {$salaryUnclassifiedDateCondition} AND te_amount > 0 AND (provision_number IS NULL OR provision_number = '') GROUP BY tax_year");
    $stmt->execute($salaryUnclassifiedParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'Salary-Unclassified';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    addTypeData($taxTypes, 'Salary', 'Salary Tax', 'fa-wallet', $provisionData, $yearTotals);

    // ----- 4. Domestic VAT -----
    $provisionData = []; $yearTotals = [];
    $pStmt = $pdo->query("SELECT provision_number, legal_basis FROM vat_provisions");
    $vatProvLabels = [];
    while ($p = $pStmt->fetch(PDO::FETCH_ASSOC)) {
        $vatProvLabels[$p['provision_number']] = $p['provision_number'] . ' - ' . $p['legal_basis'];
    }
    $vatDate = reportBatchDateExpression('import_vat_data', 'batch_id', 'import_date');
    $vatParams = [$from_year, $to_year];
    $vatDateCondition = reportImportDateCondition($vatDate, $report_filters, $vatParams);
    $stmt = $pdo->prepare("SELECT provision_number, YEAR(filing_period) as yr, SUM(expert_te) as te FROM import_vat_data WHERE YEAR(filing_period) BETWEEN ? AND ? {$vatDateCondition} AND expert_te > 0 AND provision_number IS NOT NULL AND provision_number != '' GROUP BY provision_number, yr");
    $stmt->execute($vatParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pn = $row['provision_number'];
        $year = (int)$row['yr'];
        $te = (float)$row['te'];
        $pKey = 'VAT-' . $pn;
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $vatUnclassifiedParams = [$from_year, $to_year];
    $vatUnclassifiedDateCondition = reportImportDateCondition($vatDate, $report_filters, $vatUnclassifiedParams);
    $stmt = $pdo->prepare("SELECT YEAR(filing_period) as yr, SUM(expert_te) as te FROM import_vat_data WHERE YEAR(filing_period) BETWEEN ? AND ? {$vatUnclassifiedDateCondition} AND expert_te > 0 AND (provision_number IS NULL OR provision_number = '') GROUP BY yr");
    $stmt->execute($vatUnclassifiedParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['yr'];
        $te = (float)$row['te'];
        $pKey = 'VAT-Unclassified';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $vatProvLabels['Unclassified'] = 'Unclassified / Other';
    addTypeData($taxTypes, 'VAT', 'Domestic VAT', 'fa-chart-pie', $provisionData, $yearTotals);

    // ----- 5. SEZ Developer -----
    $provisionData = []; $yearTotals = [];
    $sezDate = reportBatchDateExpression('import_sez_data', 'batch_id', 'import_date');
    $sezDevParams = [$from_year, $to_year];
    $sezDevDateCondition = reportImportDateCondition($sezDate, $report_filters, $sezDevParams);
    $stmt = $pdo->prepare("SELECT provision_number, tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? {$sezDevDateCondition} AND te_amount > 0 AND type = 'Developer' AND provision_number IS NOT NULL AND provision_number != '' GROUP BY provision_number, tax_year");
    $stmt->execute($sezDevParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pn = $row['provision_number'];
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'SEZDev-' . $pn;
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $sezDevUnclassifiedParams = [$from_year, $to_year];
    $sezDevUnclassifiedDateCondition = reportImportDateCondition($sezDate, $report_filters, $sezDevUnclassifiedParams);
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? {$sezDevUnclassifiedDateCondition} AND te_amount > 0 AND type = 'Developer' AND (provision_number IS NULL OR provision_number = '') GROUP BY tax_year");
    $stmt->execute($sezDevUnclassifiedParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'SEZDev-Unclassified';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    addTypeData($taxTypes, 'SEZDev', 'SEZ Developer', 'fa-hard-hat', $provisionData, $yearTotals);

    // ----- 6. SEZ Investor -----
    $provisionData = []; $yearTotals = [];
    $sezInvParams = [$from_year, $to_year];
    $sezInvDateCondition = reportImportDateCondition($sezDate, $report_filters, $sezInvParams);
    $stmt = $pdo->prepare("SELECT provision_number, tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? {$sezInvDateCondition} AND te_amount > 0 AND type = 'Investor' AND provision_number IS NOT NULL AND provision_number != '' GROUP BY provision_number, tax_year");
    $stmt->execute($sezInvParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pn = $row['provision_number'];
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'SEZInv-' . $pn;
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    $sezInvUnclassifiedParams = [$from_year, $to_year];
    $sezInvUnclassifiedDateCondition = reportImportDateCondition($sezDate, $report_filters, $sezInvUnclassifiedParams);
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? {$sezInvUnclassifiedDateCondition} AND te_amount > 0 AND type = 'Investor' AND (provision_number IS NULL OR provision_number = '') GROUP BY tax_year");
    $stmt->execute($sezInvUnclassifiedParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'SEZInv-Unclassified';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    addTypeData($taxTypes, 'SEZInv', 'SEZ Investor', 'fa-helmet-safety', $provisionData, $yearTotals);

    // ----- 7. Land Concession -----
    $provisionData = []; $yearTotals = [];
    $landParams = [$from_year, $to_year];
    $landDateCondition = reportImportDateCondition($citDate, $report_filters, $landParams);
    $stmt = $pdo->prepare("SELECT c.tax_year, SUM(r.te_land_concession) as te FROM te_land_concession_result r JOIN companies c ON r.company_id = c.id WHERE c.tax_year BETWEEN ? AND ? {$landDateCondition} AND r.te_land_concession > 0 GROUP BY c.tax_year");
    $stmt->execute($landParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'Land';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    addTypeData($taxTypes, 'Land', 'Land Concession', 'fa-tree', $provisionData, $yearTotals);

    // ----- 8. Resource Fee -----
    $provisionData = []; $yearTotals = [];
    $resourceDate = reportBatchDateExpression('import_resource_data', 'batch_id', 'import_date');
    $resourceParams = [$from_year, $to_year];
    $resourceDateCondition = reportImportDateCondition($resourceDate, $report_filters, $resourceParams);
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_resource_data WHERE tax_year BETWEEN ? AND ? {$resourceDateCondition} AND te_amount > 0 GROUP BY tax_year");
    $stmt->execute($resourceParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'Resource';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    addTypeData($taxTypes, 'Resource', 'Resource Fee', 'fa-oil-can', $provisionData, $yearTotals);

    // ----- 9. Royalty Fee -----
    $provisionData = []; $yearTotals = [];
    $royaltyDate = reportBatchDateExpression('import_royalty_data', 'batch_id', 'import_date');
    $royaltyParams = [$from_year, $to_year];
    $royaltyDateCondition = reportImportDateCondition($royaltyDate, $report_filters, $royaltyParams);
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_royalty_data WHERE tax_year BETWEEN ? AND ? {$royaltyDateCondition} AND te_amount > 0 GROUP BY tax_year");
    $stmt->execute($royaltyParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];
        $pKey = 'Royalty';
        if (!isset($provisionData[$pKey])) $provisionData[$pKey] = [];
        $provisionData[$pKey][$year] = ($provisionData[$pKey][$year] ?? 0) + $te;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $te;
    }
    addTypeData($taxTypes, 'Royalty', 'Royalty Fee', 'fa-gem', $provisionData, $yearTotals);

} catch (Exception $e) { $errors[] = $e->getMessage(); }

// ==================== COMPUTE GRAND TOTALS ====================
$grandYearTotals = [];
foreach ($taxTypes as $tt) {
    foreach ($tt['year_totals'] as $y => $t) {
        $grandYearTotals[$y] = ($grandYearTotals[$y] ?? 0) + $t;
    }
}

// ==================== PROVISION LABELS ====================
function provLabel(string $typeKey, string $pKey): string {
    global $citProvLabels, $pitProvLabels, $vatProvLabels;
    $num = substr($pKey, strpos($pKey, '-') + 1);
    if ($typeKey === 'CIT') return $citProvLabels[$num] ?? $pKey;
    if ($typeKey === 'PIT') return $pitProvLabels[$num] ?? $pKey;
    if ($typeKey === 'VAT') return $vatProvLabels[$num] ?? $pKey;
    if ($typeKey === 'Land') return 'Land Concession';
    if ($typeKey === 'Resource') return 'Resource Fee';
    if ($typeKey === 'Royalty') return 'Royalty Fee';
    if (strpos($num, 'Unclassified') !== false) return 'Unclassified / Other';
    return 'Prov #' . $num;
}

// ==================== EXPORT ====================
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Total TE by Provision");
    $sheet->mergeCells('A1:' . chr(65 + count($display_years)) . '1');
    $sheet->setCellValue('A1', "Total TE by Provision ({$from_year} - {$to_year})");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $rowIdx = 3;
    // Headers
    $sheet->setCellValue("A{$rowIdx}", 'Tax Type / Provision');
    $colIdx = 2;
    foreach ($display_years as $y) { $sheet->setCellValueByColumnAndRow($colIdx, $rowIdx, (string)$y); $colIdx++; }
    $sheet->setCellValueByColumnAndRow($colIdx, $rowIdx, 'Row Total');
    $sheet->getStyle("A{$rowIdx}:" . $sheet->getCellByColumnAndRow($colIdx, $rowIdx)->getColumn() . "{$rowIdx}")->getFont()->setBold(true);
    $rowIdx++;

    $allGrandTotal = 0;
    $typesWithData = array_filter($taxTypes, fn($tt) => !empty($tt['provisions']));
    foreach ($typesWithData as $typeKey => $tt) {
        // Type header
        $sheet->setCellValue("A{$rowIdx}", strtoupper($tt['label']));
        $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
        $rowIdx++;

        foreach ($tt['provisions'] as $pKey => $yearData) {
            $sheet->setCellValue("A{$rowIdx}", '  ' . provLabel($typeKey, $pKey));
            $cIdx = 2; $rowTotal = 0;
            foreach ($display_years as $y) {
                $val = $yearData[$y] ?? 0;
                $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
                $cIdx++; $rowTotal += $val;
            }
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $rowTotal);
            $rowIdx++;
        }

        // Type total
        $sheet->setCellValue("A{$rowIdx}", '  TOTAL');
        $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
        $cIdx = 2; $typeTotal = 0;
        foreach ($display_years as $y) {
            $val = $tt['year_totals'][$y] ?? 0;
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
            $cIdx++; $typeTotal += $val;
        }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $typeTotal);
        $sheet->getStyle($sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn() . $rowIdx)->getFont()->setBold(true);
        $rowIdx++; $allGrandTotal += $typeTotal;
    }

    // Grand total
    $sheet->setCellValue("A{$rowIdx}", 'GRAND TOTAL');
    $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true)->setSize(12);
    $cIdx = 2; $gt = 0;
    foreach ($display_years as $y) { $v = $grandYearTotals[$y] ?? 0; $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $v); $cIdx++; $gt += $v; }
    $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $gt);
    $sheet->getStyle($sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn() . $rowIdx)->getFont()->setBold(true)->setSize(12);

    foreach (range('A', $sheet->getCellByColumnAndRow(min($cIdx, 26), $rowIdx)->getColumn()) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Total_TE_by_Provision_' . $from_year . '-' . $to_year . '.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet); $writer->save('php://output');
    exit;
}

require_once __DIR__ . "/../includes/header.php";

// Chart data: totals per tax type per year
$chartData = [];
$typeTotalData = [];
foreach ($taxTypes as $typeKey => $tt) {
    $row = ['type' => $typeKey, 'label' => $tt['label']];
    foreach ($display_years as $y) { $row[(string)$y] = (float)($tt['year_totals'][$y] ?? 0); }
    $chartData[] = $row;
    $typeTotalData[$typeKey] = $tt['year_totals'];
}

$typesWithData = array_filter($taxTypes, fn($tt) => !empty($tt['provisions']));
$totalProvWithData = 0;
foreach ($taxTypes as $tt) { $totalProvWithData += count($tt['provisions']); }
?>
<div class="row mb-4">
    <div class="col-md-8"><h2 class="fw-bold text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i> Total TE by Provision</h2><p class="text-muted">Aggregated tax expenditure across all tax types, classified by provision.</p></div>
    <div class="col-md-4 text-end">
        <button id="expandAllBtn" class="btn btn-outline-primary shadow-sm me-2"><i class="fas fa-expand me-2"></i> Expand All</button>
        <a href="?<?= reportAppendFilters(["export" => 1, "from_year" => $from_year, "to_year" => $to_year]) ?>" class="btn btn-success shadow-sm"><i class="fas fa-file-excel me-2"></i> Export Excel</a>
        <button id="exportPdfBtn" class="btn btn-danger shadow-sm ms-2"><i class="fas fa-file-pdf me-2"></i> Export PDF</button>
    </div>
</div>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= htmlspecialchars(implode('; ', $errors)) ?></div><?php endif; ?>

<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;"><div class="card-body bg-light rounded-3">
<form method="GET" class="row align-items-end g-3">
    <div class="col-md-2"><label class="form-label small fw-bold text-muted text-uppercase">From Year</label><select name="from_year" class="form-select border-0 shadow-sm"><?php foreach ($all_years as $y): ?><option value="<?= $y ?>" <?= $y == $from_year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label small fw-bold text-muted text-uppercase">To Year</label><select name="to_year" class="form-select border-0 shadow-sm"><?php foreach ($all_years as $y): ?><option value="<?= $y ?>" <?= $y == $to_year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?></select></div>
    <?= reportImportDateFilterControl("report_total_provision.php", $from_year, $to_year) ?>
    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-2"></i> Update</button></div>
    <div class="col-md-2"><a href="report_total_provision.php" class="btn btn-outline-secondary w-100 border-0">Reset</a></div>
</form></div></div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Total TE</span><i class="fas fa-money-bill-wave fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= number_format(array_sum($grandYearTotals), 0) ?></span><small class="text-white-50 mt-1"><?= $from_year ?>&ndash;<?= $to_year ?></small></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Tax Types</span><i class="fas fa-tag fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= count($typesWithData) ?></span><small class="text-white-50 mt-1">With TE data</small></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Provisions</span><i class="fas fa-list fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= $totalProvWithData ?></span><small class="text-white-50 mt-1">With TE data</small></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#fa709a 0%,#fee140 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Years</span><i class="fas fa-calendar fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= count($display_years) ?></span><small class="text-white-50 mt-1"><?= $from_year ?>&ndash;<?= $to_year ?></small></div></div></div>
</div>

<div id="reportContent" class="card shadow-sm border-0 mb-4" style="border-radius:12px;"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0 matrix-table" id="totalProvisionTable"><thead class="bg-primary text-white"><tr class="align-middle"><th class="ps-4 py-3" style="min-width:280px;">Tax Type / Provision</th><?php foreach ($display_years as $year): ?><th class="text-end py-3"><?= $year ?></th><?php endforeach; ?><th class="text-end pe-4 py-3 bg-dark">Row Total</th></tr></thead><tbody>
<?php
$totalRowTotal = array_sum($grandYearTotals);
foreach ($taxTypes as $typeKey => $tt):
    if (empty($tt['provisions'])) continue;
    $typeRowTotal = array_sum($tt['year_totals']);
    $typeId = 'type-' . $typeKey;
?>
<tr class="bg-light type-header" data-target="#<?= $typeId ?>" role="button"><td class="ps-4 py-3 fw-bold fs-6"><i class="fas <?= $tt['icon'] ?> me-2 text-primary"></i><?= htmlspecialchars($tt['label']) ?> <span class="badge bg-primary ms-2"><?= number_format($typeRowTotal, 0) ?></span></td>
<?php foreach ($display_years as $year): ?><td class="text-end fw-bold py-3"><?= number_format($tt['year_totals'][$year] ?? 0, 0) ?></td><?php endforeach; ?>
<td class="text-end pe-4 py-3 fw-bold bg-white"><?= number_format($typeRowTotal, 0) ?></td></tr>
<tr id="<?= $typeId ?>" class="collapse provision-group"><td colspan="<?= 2 + count($display_years) ?>" class="p-0">
<table class="table table-sm align-middle mb-0 inner-table"><tbody>
<?php foreach ($tt['provisions'] as $pKey => $yearData):
    $provRowTotal = array_sum($yearData);
    $label = provLabel($typeKey, $pKey);
?>
<tr><td class="ps-5 text-muted"><?= htmlspecialchars($label) ?></td>
<?php foreach ($display_years as $year): $val = $yearData[$year] ?? 0; ?>
<td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
<?php endforeach; ?>
<td class="text-end pe-4 fw-bold"><?= $provRowTotal > 0 ? number_format($provRowTotal, 0) : '-' ?></td></tr>
<?php endforeach; ?>
</tbody></table></td></tr>
<?php endforeach; ?>
</tbody><tfoot class="bg-dark border-top-2 border-dark"><tr class="fw-bold text-white align-middle"><td class="ps-4 py-3 fs-5">GRAND TOTAL</td>
<?php $gt = 0; foreach ($display_years as $year): $v = $grandYearTotals[$year] ?? 0; $gt += $v; ?>
<td class="text-end py-3 fs-5"><?= number_format($v, 0) ?></td><?php endforeach; ?>
<td class="text-end pe-4 py-3 fs-5 bg-black"><?= number_format($gt, 0) ?></td></tr></tfoot></table></div></div></div>

<div class="card shadow-sm mb-4" style="border-radius:12px;"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> TE by Tax Type (<?= $from_year ?>&ndash;<?= $to_year ?>)</h5>
<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary chart-type-btn active" data-type="bar"><i class="fas fa-chart-bar"></i> Bar</button><button class="btn btn-outline-primary chart-type-btn" data-type="line"><i class="fas fa-chart-line"></i> Line</button><button class="btn btn-outline-primary chart-type-btn" data-type="pie"><i class="fas fa-chart-pie"></i> Pie</button></div></div>
<div id="chartContainer" style="width:100%;height:360px;"><canvas id="teTotalChart"></canvas></div>
<div id="pieChartsContainer" class="row g-3" style="display:none;"></div>
</div></div>

<style>
.matrix-table{font-size:.9rem}.matrix-table thead th{border:none;font-weight:700;text-transform:uppercase;letter-spacing:.5px;font-size:.78rem}.matrix-table tbody td{border-bottom:1px solid #f2f2f2}.matrix-table tfoot td{font-size:1.1rem;border-top:2px solid #222}.type-header td{background-color:#f8f9fa!important;border-bottom:2px solid #dee2e6!important}.type-header:hover{background-color:#e9ecef!important}.inner-table td{border:none!important;padding:.4rem .5rem!important}.inner-table tr:not(:last-child) td{border-bottom:1px solid #f0f0f0!important}.provision-group{background:transparent!important}.table-hover tbody tr:hover td{background-color:#f0f7ff}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var chartData=<?= json_encode($chartData) ?>,chartYears=<?= json_encode($display_years) ?>,chart=null,pieCharts=[],currentChartType='bar';
var COLORS=['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ac','#6b6ecf','#d4a6c8','#a1c9f4','#ffb482','#8cd17d','#f1ce63','#499894','#e377c2','#b5b5b5','#7f7f7f','#c5b0d5','#ffbb78','#98df8a','#ff9898'];
function getColor(i){return COLORS[i%COLORS.length]}
function formatValue(v){if(v>=1e12)return(v/1e12).toFixed(1)+'T';if(v>=1e9)return(v/1e9).toFixed(1)+'B';if(v>=1e6)return(v/1e6).toFixed(1)+'M';if(v>=1e3)return(v/1e3).toFixed(1)+'K';return v.toFixed(0)}
function setChartType(t){currentChartType=t;document.querySelectorAll('.chart-type-btn').forEach(function(b){b.classList.toggle('active',b.getAttribute('data-type')===t)});renderChart()}
function switchChartType(t){return new Promise(function(r){setChartType(t);setTimeout(r,1200)})}
function renderChart(){if(chart){chart.destroy();chart=null}pieCharts.forEach(function(c){c.destroy()});pieCharts=[];var fd=chartData.filter(function(d){return chartYears.some(function(y){return Math.abs(d[y])>0})});if(fd.length===0)fd=chartData;if(currentChartType==='line'){document.getElementById('chartContainer').style.display='block';document.getElementById('pieChartsContainer').style.display='none';document.getElementById('pieChartsContainer').innerHTML='';var ds=fd.map(function(d,i){return{label:d.label,data:chartYears.map(function(y){return d[y]||0}),borderColor:getColor(i),backgroundColor:getColor(i),fill:false,tension:.2,spanGaps:true}});chart=new Chart(document.getElementById('teTotalChart'),{type:'line',data:{labels:chartYears,datasets:ds},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top',labels:{boxWidth:12,padding:10,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+Number(ctx.raw).toLocaleString()}}}},scales:{x:{title:{display:true,text:'Year',font:{size:11}},ticks:{font:{size:11}}},y:{beginAtZero:true,ticks:{callback:function(v){return formatValue(v)},font:{size:11}}}}}})}else if(currentChartType==='bar'){document.getElementById('chartContainer').style.display='block';document.getElementById('pieChartsContainer').style.display='none';document.getElementById('pieChartsContainer').innerHTML='';var ds=chartYears.map(function(y,i){return{label:String(y),data:fd.map(function(d){return d[y]||0}),backgroundColor:getColor(i)}});chart=new Chart(document.getElementById('teTotalChart'),{type:'bar',data:{labels:fd.map(function(d){return d.label}),datasets:ds},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top',labels:{boxWidth:12,padding:10,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+Number(ctx.raw).toLocaleString()}}}},scales:{x:{ticks:{font:{size:10}}},y:{beginAtZero:true,ticks:{callback:function(v){return formatValue(v)},font:{size:11}}}}}})}else if(currentChartType==='pie'){document.getElementById('chartContainer').style.display='none';document.getElementById('pieChartsContainer').style.display='flex';document.getElementById('pieChartsContainer').innerHTML='';chartYears.forEach(function(year){var yd=fd.map(function(d){return{label:d.label,value:d[year]||0}}).filter(function(i){return i.value>0});if(yd.length===0)return;var col=document.createElement('div');col.className='col-lg-4 col-md-6 col-sm-12';var ctn=document.createElement('div');ctn.className='pie-chart-container';var cv=document.createElement('canvas');ctn.appendChild(cv);col.appendChild(ctn);document.getElementById('pieChartsContainer').appendChild(col);var p=new Chart(cv,{type:'pie',data:{labels:yd.map(function(i){return i.label}),datasets:[{data:yd.map(function(i){return i.value}),backgroundColor:yd.map(function(_,i){return getColor(i)})}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'right',labels:{boxWidth:8,padding:4,font:{size:9}}},title:{display:true,text:'Year '+year,font:{size:11},padding:{top:4,bottom:2}},tooltip:{callbacks:{label:function(ctx){var total=ctx.dataset.data.reduce(function(a,b){return a+b},0);return ctx.label+': '+Number(ctx.raw).toLocaleString()+' ('+((ctx.raw/total)*100).toFixed(1)+'%)'}}}}}});pieCharts.push(p)})}}
document.querySelectorAll('.chart-type-btn').forEach(function(b){b.addEventListener('click',function(){setChartType(this.getAttribute('data-type'))})});
renderChart();
document.getElementById('expandAllBtn').addEventListener('click',function(){var btn=this;var collapsed=document.querySelectorAll('.provision-group.collapse:not(.show)');var expanded=document.querySelectorAll('.provision-group.collapse.show');if(collapsed.length>0){document.querySelectorAll('.provision-group.collapse').forEach(function(e){e.classList.add('show')});btn.innerHTML='<i class="fas fa-compress me-2"></i> Collapse All'}else{document.querySelectorAll('.provision-group.collapse').forEach(function(e){e.classList.remove('show')});btn.innerHTML='<i class="fas fa-expand me-2"></i> Expand All'}});
document.querySelectorAll('.type-header').forEach(function(el){el.addEventListener('click',function(){var target=document.querySelector(this.getAttribute('data-target'));if(target){target.classList.toggle('show')}})});
document.getElementById('exportPdfBtn')?.addEventListener('click',function(){var btn=this;btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';document.querySelectorAll('.provision-group').forEach(function(e){e.classList.add('show')});var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';s.onload=function(){var s2=document.createElement('script');s2.src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';s2.onload=function(){var {jsPDF}=window.jspdf;var pdf=new jsPDF('l','mm','a4');var pw=pdf.internal.pageSize.getWidth();switchChartType('bar').then(function(){setTimeout(function(){html2canvas(document.getElementById('reportContent'),{scale:2,backgroundColor:'#ffffff'}).then(function(tc){var td=tc.toDataURL('image/png');var tp=pdf.getImageProperties(td);var tw=pw-20;var th=(tp.height*tw)/tp.width;pdf.addImage(td,'PNG',10,10,tw,th);var cc=document.querySelector('#chartContainer canvas');if(cc){setTimeout(function(){var md=cc.toDataURL('image/png');pdf.addPage();var mp=pdf.getImageProperties(md);var mw=pw-20;var mh=(mp.height*mw)/mp.width;pdf.addImage(md,'PNG',10,10,mw,mh);pdf.save('Total_TE_by_Provision_<?= $from_year ?>-<?= $to_year ?>.pdf');btn.disabled=false;btn.innerHTML='<i class="fas fa-file-pdf me-2"></i> Export PDF'},800)}else{pdf.save('Total_TE_by_Provision_<?= $from_year ?>-<?= $to_year ?>.pdf');btn.disabled=false;btn.innerHTML='<i class="fas fa-file-pdf me-2"></i> Export PDF'}})},500)})};document.head.appendChild(s2)};document.head.appendChild(s)});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
