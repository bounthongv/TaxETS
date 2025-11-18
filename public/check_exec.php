<?php
// public/check_exec.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Server Configuration Check</h1>";

// Check if shell_exec is disabled
$disabled_functions = explode(',', ini_get('disable_functions'));
$is_disabled = in_array('shell_exec', array_map('trim', $disabled_functions));

if (!$is_disabled) {
    echo "<p style='color:green; font-weight:bold;'>shell_exec() is ENABLED.</p>";
    
    echo "<p>Attempting to find and run Python from the virtual environment...</p>";
    
    $projectRoot = realpath(__DIR__ . '/..');
    $venvPath = $projectRoot . '/venv';
    
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';

    if (file_exists($pythonExecutable)) {
        echo "<p style='color:green;'>Found Python executable at: " . htmlspecialchars($pythonExecutable) . "</p>";
        
        $command = escapeshellcmd($pythonExecutable) . " --version 2>&1";
        echo "<p>Running command: <code>" . htmlspecialchars($command) . "</code></p>";
        
        $output = shell_exec($command);
        
        echo "<p><b>Output:</b></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";

    } else {
        echo "<p style='color:red; font-weight:bold;'>ERROR: Could not find the Python executable.</p>";
        echo "<p>Expected path: " . htmlspecialchars($pythonExecutable) . "</p>";
        echo "<p>Please ensure you have run the manual setup commands correctly from the project's root directory.</p>";
    }

} else {
    echo "<p style='color:red; font-weight:bold;'>shell_exec() is DISABLED.</p>";
    echo "<p>This is the cause of the HTTP 500 error.</p>";
    echo "<p>To fix this, you must edit your server's <code>php.ini</code> file, find the <code>disable_functions</code> directive, and remove <code>shell_exec</code> from the list. You will then need to restart your web server (e.g., Apache or Nginx).</p>";
}
