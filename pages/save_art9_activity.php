<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    
    $fields = [
        'project_id' => trim($_POST['project_id']),
        'short_name' => trim($_POST['short_name']),
        'short_name_en' => trim($_POST['short_name_en']),
        'content' => trim($_POST['content']),
        'more_info' => trim($_POST['more_info']),
        'tax_rule_id' => !empty($_POST['tax_rule_id']) ? $_POST['tax_rule_id'] : null
    ];

    $cols = array_keys($fields);
    $vals = array_values($fields);

    if ($id) {
        $set = implode(" = ?, ", $cols) . " = ?";
        $stmt = $pdo->prepare("UPDATE bm_art9_activities SET $set WHERE id = ?");
        $vals[] = $id;
        $stmt->execute($vals);
    } else {
        $placeholders = implode(", ", array_fill(0, count($cols), "?"));
        $stmt = $pdo->prepare("INSERT INTO bm_art9_activities (" . implode(", ", $cols) . ") VALUES ($placeholders)");
        $stmt->execute($vals);
    }
}

header("Location: benchmark_art9.php");
exit;
