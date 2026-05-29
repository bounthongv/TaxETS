<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
try {
    $pdo = getDbConnection();
    print_r($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo $e->getMessage();
}
