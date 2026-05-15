<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    
    $fields = [
        'tin' => trim($_POST['tin']),
        'project_name' => trim($_POST['project_name']),
        'investment_license_date' => !empty($_POST['investment_license_date']) ? $_POST['investment_license_date'] : null,
        'activities' => trim($_POST['activities']),
        'incentives' => trim($_POST['incentives']),
        'sector_id' => !empty($_POST['sector_id']) ? $_POST['sector_id'] : null,
        'tax_holiday_period' => trim($_POST['tax_holiday_period'])
    ];

    $cols = array_keys($fields);
    $vals = array_values($fields);

    if ($id) {
        $set = implode(" = ?, ", $cols) . " = ?";
        $stmt = $pdo->prepare("UPDATE repo_mpi SET $set WHERE id = ?");
        $vals[] = $id;
        $stmt->execute($vals);
    } else {
        $placeholders = implode(", ", array_fill(0, count($cols), "?"));
        $stmt = $pdo->prepare("INSERT INTO repo_mpi (" . implode(", ", $cols) . ") VALUES ($placeholders)");
        $stmt->execute($vals);
    }
}

header("Location: repo_mpi.php");
exit;
