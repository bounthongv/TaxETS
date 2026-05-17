<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$sql = file_get_contents(__DIR__ . '/../db/seed_land_nontax_complete.sql');

try {
    $pdo->exec($sql);
    echo "Full benchmark seeding successful!";
} catch (PDOException $e) {
    echo "Full benchmark seeding failed: " . $e->getMessage();
}
