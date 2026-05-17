<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$sql = file_get_contents(__DIR__ . '/../db/update_companies_for_nontax.sql');

try {
    $pdo->exec($sql);
    echo "Companies update successful!";
} catch (PDOException $e) {
    echo "Companies update failed: " . $e->getMessage();
}
