<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = getDbConnection();
    $sql = "ALTER TABLE companies 
            ADD COLUMN IF NOT EXISTS pro_id VARCHAR(10) AFTER province,
            ADD COLUMN IF NOT EXISTS dis_id VARCHAR(50) AFTER district,
            ADD COLUMN IF NOT EXISTS sector_id INT AFTER sector";
    $pdo->exec($sql);
    echo "Schema updated successfully.\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
