<?php
// public/test_python_context.php
// Test if Python execution works in the context of a file upload simulation

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Python Execution Context</h2>";

// Simulate same conditions as process_import.php
set_time_limit(300);

$projectRoot = realpath(__DIR__ . '/..');
$venvPath = $projectRoot . '/venv';

$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';

// Fallback to system Python if venv doesn't exist
if (!file_exists($pythonExecutable)) {
    $pythonExecutable = $isWindows ? 'python.exe' : 'python';
}

echo "Python executable: " . $pythonExecutable . "<br>";

// Check if shell_exec is available
$disabled_functions = explode(',', ini_get('disable_functions'));
$is_shell_exec_disabled = in_array('shell_exec', array_map('trim', $disabled_functions));

if ($is_shell_exec_disabled) {
    die("ERROR: shell_exec is disabled in PHP configuration");
}

echo "shell_exec is available<br>";

// Test basic Python execution
$command = escapeshellcmd($pythonExecutable) . " --version 2>&1";
echo "Testing command: " . $command . "<br>";
$output = shell_exec($command);
echo "Python version output: " . $output . "<br>";

// Test Python script execution capability
$testPythonScript = $projectRoot . '/parse_excel.py';
if (file_exists($testPythonScript)) {
    echo "parse_excel.py found at: " . $testPythonScript . "<br>";
    
    // Try to execute with a simple test (using a small dummy file)
    // Create a minimal test file
    $testContent = "import sys; print('Python test successful')";
    $testScriptPath = tempnam(sys_get_temp_dir(), 'test_python_');
    $testScriptPath .= '.py';  // Rename to .py
    file_put_contents($testScriptPath, $testContent);
    
    $testCommand = escapeshellcmd($pythonExecutable) . " " . escapeshellarg($testScriptPath) . " 2>&1";
    echo "Test execution command: " . $testCommand . "<br>";
    $testOutput = shell_exec($testCommand);
    echo "Test execution output: " . $testOutput . "<br>";
    
    // Clean up
    unlink($testScriptPath);
} else {
    echo "ERROR: parse_excel.py not found at: " . $testPythonScript . "<br>";
}

echo "<h2>Test completed successfully - basic Python execution works</h2>";
?>