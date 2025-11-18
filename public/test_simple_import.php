<?php
// public/test_simple_import.php - Simplified version to isolate the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Simple Import Process</h2>";

session_start();
echo "Session started successfully<br>";

// Test file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST request received<br>";
    
    if (!isset($_FILES['import_file'])) {
        echo "No file uploaded<br>";
        exit;
    }
    
    $file = $_FILES['import_file'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . "<br>";
    echo "File error: " . $file['error'] . "<br>";
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "File upload error: " . $file['error'] . "<br>";
        exit;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    echo "File extension: " . $extension . "<br>";
    
    if (strtolower($extension) !== 'xlsx') {
        echo "Invalid file type<br>";
        exit;
    }
    
    echo "File validation passed<br>";
    
    // Try to move the file
    $tempDir = sys_get_temp_dir();
    $tempFilePath = $tempDir . '/' . uniqid('taxets_import_test_') . '.xlsx';
    echo "Attempting to move file to: " . $tempFilePath . "<br>";
    
    if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        echo "File moved successfully<br>";
        
        // Get project root
        $projectRoot = realpath(__DIR__ . '/..');
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Find Python executable
        $venvPath = $projectRoot . '/venv';
        $pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';
        
        if (!file_exists($pythonExecutable)) {
            $pythonExecutable = $isWindows ? 'python.exe' : 'python';
        }
        
        echo "Python executable: " . $pythonExecutable . "<br>";
        
        // Test Python execution
        $pythonScriptPath = $projectRoot . '/parse_excel.py';
        if (!file_exists($pythonScriptPath)) {
            echo "Python script not found: " . $pythonScriptPath . "<br>";
        } else {
            echo "Python script found: " . $pythonScriptPath . "<br>";
            
            // Test the command execution
            $escapedFilePath = escapeshellarg($tempFilePath);
            $command = escapeshellcmd($pythonExecutable) . " " . escapeshellarg($pythonScriptPath) . " " . $escapedFilePath . " 2>&1";
            echo "Command to execute: " . $command . "<br>";
            
            $disabled_functions = explode(',', ini_get('disable_functions'));
            $is_shell_exec_disabled = in_array('shell_exec', array_map('trim', $disabled_functions));
            
            if ($is_shell_exec_disabled) {
                echo "ERROR: shell_exec is disabled in PHP configuration<br>";
            } else {
                echo "Executing command...<br>";
                $jsonOutput = shell_exec($command);
                echo "Command executed. Output length: " . strlen($jsonOutput) . "<br>";
                echo "Command output: " . substr($jsonOutput, 0, 500) . "...<br>";
                
                if (empty($jsonOutput)) {
                    echo "ERROR: No output from Python script<br>";
                } else {
                    echo "Python script returned data<br>";
                    $dataToImport = json_decode($jsonOutput, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo "ERROR: JSON decode failed - " . json_last_error_msg() . "<br>";
                    } else {
                        echo "JSON decoded successfully, first few records:<br>";
                        echo "<pre>" . htmlspecialchars(print_r(array_slice($dataToImport, 0, 2), true)) . "</pre>";
                    }
                }
            }
        }
        
        // Clean up temp file
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
            echo "Temporary file cleaned up<br>";
        }
    } else {
        echo "Failed to move uploaded file<br>";
    }
} else {
    echo "Testing with GET request - submit a file:<br>";
    echo '<form action="" method="POST" enctype="multipart/form-data">';
    echo '<input type="file" name="import_file" accept=".xlsx">';
    echo '<button type="submit">Test Upload</button>';
    echo '</form>';
}