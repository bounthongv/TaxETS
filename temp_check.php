<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = getDbConnection();
echo "Districts count: " . $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn() . PHP_EOL;
echo "Provinces count: " . $pdo->query("SELECT COUNT(*) FROM provinces")->fetchColumn() . PHP_EOL;
echo "Districts with zone: " . $pdo->query("SELECT COUNT(*) FROM districts WHERE zone IS NOT NULL")->fetchColumn() . PHP_EOL;
echo "Districts with zone=1: " . $pdo->query("SELECT COUNT(*) FROM districts WHERE zone = 1")->fetchColumn() . PHP_EOL;
echo "Districts with zone=2: " . $pdo->query("SELECT COUNT(*) FROM districts WHERE zone = 2")->fetchColumn() . PHP_EOL;