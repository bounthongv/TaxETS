<?php
// public/debug_import.php - Debug script to identify the source of 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Debug Import Process</h2>";

// Test 1: Basic PHP functionality
echo "<h3>1. Testing basic PHP functionality</h3>";
echo "PHP version: " . phpversion() . "<br>";
echo "OS: " . PHP_OS . "<br>";
echo "Success: Basic PHP working<br>";

// Test 2: File system access
echo "<h3>2. Testing file system access</h3>";
$projectRoot = realpath(__DIR__ . '/..');
echo "Project root: " . $projectRoot . "<br>";

$parseExcelPath = $projectRoot . '/parse_excel.py';
echo "Python script path: " . $parseExcelPath . "<br>";
echo "Python script exists: " . (file_exists($parseExcelPath) ? 'YES' : 'NO') . "<br>";

$venvPath = $projectRoot . '/venv';
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';
echo "Python executable path: " . $pythonExecutable . "<br>";
echo "Python executable exists: " . (file_exists($pythonExecutable) ? 'YES' : 'NO') . "<br>";

// Test Python fallback
if (!file_exists($pythonExecutable)) {
    $pythonExecutable = $isWindows ? 'python.exe' : 'python';
    echo "Using system Python: " . $pythonExecutable . "<br>";
}
echo "Final Python executable (exists): " . (file_exists($pythonExecutable) || $pythonExecutable === 'python' || $pythonExecutable === 'python.exe' ? 'YES' : 'NO') . "<br>";

// Test 3: Shell execution capability
echo "<h3>3. Testing shell execution</h3>";
$disabled_functions = explode(',', ini_get('disable_functions'));
$is_shell_exec_disabled = in_array('shell_exec', array_map('trim', $disabled_functions));
echo "shell_exec disabled: " . ($is_shell_exec_disabled ? 'YES' : 'NO') . "<br>";

if (!$is_shell_exec_disabled) {
    echo "Testing Python version command...<br>";
    $command = escapeshellcmd($pythonExecutable) . " --version 2>&1";
    echo "Command: " . $command . "<br>";
    $output = shell_exec($command);
    echo "Python output: " . $output . "<br>";
} else {
    echo "shell_exec is disabled - this would cause the import to fail<br>";
}

// Test 4: Database connection
echo "<h3>4. Testing database connection</h3>";
try {
    require '../src/Services/Database.php';
    use TaxETS\Services\Database;
    
    $pdo = Database::getInstance()->getConnection();
    echo "Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

echo "<h3>5. Testing other required functions</h3>";
echo "file_get_contents available: " . (function_exists('file_get_contents') ? 'YES' : 'NO') . "<br>";
echo "move_uploaded_file available: " . (function_exists('move_uploaded_file') ? 'YES' : 'NO') . "<br>";
echo "json_decode available: " . (function_exists('json_decode') ? 'YES' : 'NO') . "<br>";
echo "session_start available: " . (function_exists('session_start') ? 'YES' : 'NO') . "<br>";

echo "<br><h3>Debug complete. If you see this, the script ran without fatal errors.</h3>";
echo "Now you'll need to check if shell_exec actually works in your environment and if the database credentials are correct.";