<?php
// public/syntax_check.php - Check for syntax errors in critical files

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing PHP Syntax of Critical Files</h2>";

$files_to_check = [
    '../src/Services/Database.php',
    'autoload.php',
    'process_import.php',
    '../src/config.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    echo "Checking: $full_path<br>";
    
    if (!file_exists($full_path)) {
        echo "<span style='color: red;'>File does not exist!</span><br><br>";
        continue;
    }
    
    // Test syntax using php -l command
    $output = shell_exec('php -l ' . escapeshellarg($full_path) . ' 2>&1');
    
    if (strpos($output, 'No syntax errors detected') !== false) {
        echo "<span style='color: green;'>Syntax OK</span><br><br>";
    } else {
        echo "<span style='color: red;'>Syntax ERROR: " . htmlspecialchars($output) . "</span><br><br>";
    }
}

echo "Done checking syntax.";
?>