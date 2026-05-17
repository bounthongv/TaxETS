<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$sql = file_get_contents(__DIR__ . '/../db/update_land_nontax_benchmarks.sql');

try {
    // split SQL by semicolon to execute one by one to avoid PDO issues with multiple statements
    // But PDO exec can handle multiple statements if configured.
    // However, some drivers might fail. Let's try simple exec first.
    $pdo->exec($sql);
    echo "Migration successful!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
