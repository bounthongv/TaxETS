<?php
// public/test_db_connection.php - Test database connection separately

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection</h2>";

// Test config file
$configPath = __DIR__ . '/../src/config.php';
echo "Config file path: " . $configPath . "<br>";
echo "Config file exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "<br>";

if (file_exists($configPath)) {
    require_once $configPath;
    echo "\$dbConfig defined: " . (isset($dbConfig) ? 'YES' : 'NO') . "<br>";
    if (isset($dbConfig)) {
        echo "DB Config contents:<br>";
        print_r($dbConfig);
    }
}

echo "<br>Testing Database.php file:<br>";
$databasePath = __DIR__ . '/../src/Services/Database.php';
echo "Database.php path: " . $databasePath . "<br>";
echo "Database.php exists: " . (file_exists($databasePath) ? 'YES' : 'NO') . "<br>";

if (file_exists($databasePath)) {
    require_once $databasePath;
    echo "Database.php loaded successfully<br>";
    
    // Try to create an instance
    try {
        $database = TaxETS\Services\Database::getInstance();
        echo "Database instance created<br>";
        
        // Try to get connection
        $pdo = $database->getConnection();
        echo "Database connection successful<br>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "Simple query result: ";
        print_r($result);
        
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . "<br>";
        echo "Trace: " . $e->getTraceAsString() . "<br>";
    }
} else {
    echo "Database.php file not found!<br>";
}