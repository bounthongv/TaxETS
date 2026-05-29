<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();

$pdo->exec('DROP TABLE IF EXISTS `district`');
$pdo->exec('DROP TABLE IF EXISTS `province`');
echo "Old tables (district, province) dropped successfully.\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Remaining tables:\n";
foreach ($tables as $t) {
    echo "  - $t\n";
}
