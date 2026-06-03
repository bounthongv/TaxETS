<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_pit_engine.php";

$pdo = getDbConnection();
$engine = new TEPitEngine($pdo);

// Clear existing results
$pdo->exec("TRUNCATE TABLE te_individual_result");

// Get all records
$records = $pdo->query("SELECT * FROM import_pit_data")->fetchAll(PDO::FETCH_ASSOC);
echo "Records to process: " . count($records) . PHP_EOL;

$total_calculated = 0;
$total_te = 0.0;
$errors = [];

foreach ($records as $row) {
    try {
        $result = $engine->calculateIndividual($row);

        $existing = $pdo->prepare("SELECT id FROM te_individual_result WHERE tin = ? AND tax_year = ?");
        $existing->execute([$row['ptin'], $row['tax_year']]);
        $existingRow = $existing->fetch();

        if ($existingRow) {
            $updateStmt = $pdo->prepare("UPDATE te_individual_result SET individual_name = ?, filing_date = ?, employment_income = ?, other_income = ?, actual_tax_paid = ?, benchmark_calculated_tax = ?, te_amount = ?, matched_provisions = ? WHERE id = ?");
            $updateStmt->execute([
                $row['employee_name'],
                $row['filing_date'],
                $result['employment_income'],
                $result['other_income'],
                $result['actual_tax_paid'],
                $result['benchmark_tax'],
                $result['te_amount'],
                $result['matched_provisions'],
                $existingRow['id']
            ]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO te_individual_result (tax_year, tin, individual_name, filing_date, employment_income, other_income, actual_tax_paid, benchmark_calculated_tax, te_amount, matched_provisions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $row['tax_year'],
                $row['ptin'],
                $row['employee_name'],
                $row['filing_date'],
                $result['employment_income'],
                $result['other_income'],
                $result['actual_tax_paid'],
                $result['benchmark_tax'],
                $result['te_amount'],
                $result['matched_provisions']
            ]);
        }

        $total_te += $result['te_amount'];
        $total_calculated++;
    } catch (Exception $e) {
        $errors[] = "PTIN {$row['ptin']}: " . $e->getMessage();
    }
}

echo "Calculated: $total_calculated" . PHP_EOL;
echo "Total System TE: " . number_format($total_te, 0) . PHP_EOL;
if (!empty($errors)) {
    echo "Errors: " . PHP_EOL;
    foreach ($errors as $e) echo "  $e" . PHP_EOL;
}

// Comparison table
echo PHP_EOL . str_pad("", 155, "=") . PHP_EOL;
$fmt = "%-18s %-14s %-6s %-14s %-14s %-12s %-14s %-14s %-14s %-12s %s";
echo sprintf($fmt, "Name", "PTIN", "Year", "EmpInc", "OtherInc", "ActTax", "BenchTax", "SysTE", "ExpTE", "Diff", "St") . PHP_EOL;
echo str_pad("", 155, "-") . PHP_EOL;

$total_sys_te = 0;
$total_exp_te = 0;
$discrepancies = 0;

$results = $pdo->query("SELECT r.*, d.expert_te_total 
    FROM te_individual_result r 
    JOIN import_pit_data d ON r.tin = d.ptin COLLATE utf8mb4_unicode_ci AND r.tax_year = d.tax_year
    ORDER BY r.tin")->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $r) {
    $exp_te = (float)$r['expert_te_total'];
    $sys_te = (float)$r['te_amount'];
    $diff = $sys_te - $exp_te;
    $total_sys_te += $sys_te;
    $total_exp_te += $exp_te;
    $status = ($sys_te == $exp_te) ? "✓" : "✗";
    if ($sys_te != $exp_te) $discrepancies++;
    
    echo sprintf($fmt,
        substr($r['individual_name'], 0, 16),
        $r['tin'],
        $r['tax_year'],
        number_format($r['employment_income'], 0),
        number_format($r['other_income'], 0),
        number_format($r['actual_tax_paid'], 0),
        number_format($r['benchmark_calculated_tax'], 0),
        number_format($sys_te, 0),
        number_format($exp_te, 0),
        number_format($diff, 0),
        $status) . PHP_EOL;
}

echo str_pad("", 155, "=") . PHP_EOL;
$total_diff = $total_sys_te - $total_exp_te;
echo sprintf($fmt,
    "TOTALS", "", "",
    "", "",
    "",
    "",
    number_format($total_sys_te, 0),
    number_format($total_exp_te, 0),
    number_format($total_diff, 0),
    "") . PHP_EOL;
echo "Discrepancies: $discrepancies / " . count($results) . PHP_EOL;
