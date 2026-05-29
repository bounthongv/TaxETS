<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = getDbConnection();
    
    // Clear "Department" string from province column
    $stmt = $pdo->prepare("UPDATE import_vat_data SET province = NULL WHERE province = 'Department'");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "Cleared 'Department' placeholder from $count records in import_vat_data.\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
