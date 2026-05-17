<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    
    $fields = [
        'tin' => trim($_POST['tin']),
        'company_name' => trim($_POST['company_name']),
        'year' => (int)$_POST['year'],
        'revenue' => (float)$_POST['revenue'],
        'expense' => (float)$_POST['expense'],
        'net_profit' => (float)$_POST['net_profit'],
        'tax_paid' => (float)$_POST['tax_paid'],
        'te_dummy' => trim($_POST['te_dummy']),
        'tax_rate_paid' => (float)$_POST['tax_rate_paid'],
        'total_assets' => (float)$_POST['total_assets'],
        'vat_system_status' => isset($_POST['vat_system_status']) ? 1 : 0,
        'reinvest_net_profit' => (float)$_POST['reinvest_net_profit'],
        'reinvest_date' => !empty($_POST['reinvest_date']) ? $_POST['reinvest_date'] : null,
        'is_public_income' => (int)$_POST['is_public_income'],
        'is_asset_rent' => (int)$_POST['is_asset_rent'],
        'is_real_estate_transfer' => (int)$_POST['is_real_estate_transfer'],
        'is_vat_enterprise' => (int)$_POST['is_vat_enterprise'],
        'total_assets_bn' => (float)$_POST['total_assets_bn'],
        'annual_turnover_bn' => (float)$_POST['annual_turnover_bn']
    ];

    $cols = array_keys($fields);
    $vals = array_values($fields);

    if ($id) {
        $set = implode(" = ?, ", $cols) . " = ?";
        $stmt = $pdo->prepare("UPDATE repo_taxris SET $set WHERE id = ?");
        $vals[] = $id;
        $stmt->execute($vals);
    } else {
        $placeholders = implode(", ", array_fill(0, count($cols), "?"));
        $stmt = $pdo->prepare("INSERT INTO repo_taxris (" . implode(", ", $cols) . ") VALUES ($placeholders)");
        $stmt->execute($vals);
    }
}

header("Location: repo_taxris.php");
exit;
