<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE repo_gdp_revenue SET gdp_year = ?, gdp_value = ?, revenue_value = ?, note = ? WHERE id = ?");
        $stmt->execute([$_POST['gdp_year'], $_POST['gdp_value'], $_POST['revenue_value'], $_POST['note'], $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO repo_gdp_revenue (gdp_year, gdp_value, revenue_value, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['gdp_year'], $_POST['gdp_value'], $_POST['revenue_value'], $_POST['note']]);
    }
}

header("Location: repo_gdp.php");
exit;
