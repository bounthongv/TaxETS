<?php
// public/fixed_process_import.php - Updated version with direct file inclusion

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Directly include the Database class file instead of relying on autoloader
require_once '../src/Services/Database.php';
use TaxETS\Services\Database;

try {
    // Give the script up to 5 minutes to run, to prevent timeouts from Python
    set_time_limit(300);

    // --- Define Python Environment Paths ---
    $projectRoot = realpath(__DIR__ . '/..');
    $venvPath = $projectRoot . '/venv';

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';
    
    // Fallback to system Python if venv doesn't exist
    if (!file_exists($pythonExecutable)) {
        $pythonExecutable = $isWindows ? 'python.exe' : 'python';
    }
    // --- End Paths ---

    // 1. Verify the file upload is valid
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        throw new \Exception("Invalid file upload.");
    }

    $file = $_FILES['import_file'];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'xlsx') {
        throw new \Exception("Invalid file type. Only .xlsx files are accepted.");
    }

    // 2. Move the uploaded file to a temporary, known location
    $tempDir = sys_get_temp_dir();
    $tempFilePath = $tempDir . '/' . uniqid('taxets_import_') . '.xlsx';
    if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        throw new \Exception('Failed to move uploaded file.');
    }

    // 3. Call the Python script using the virtual environment's interpreter
    $pythonScriptPath = $projectRoot . '/parse_excel.py';
    if (!file_exists($pythonScriptPath)) {
        throw new \Exception('Could not find the Python parser script (parse_excel.py).');
    }
    if (!file_exists($pythonExecutable)) {
        throw new \Exception("Python executable not found. Please ensure Python is installed and accessible.");
    }

    $escapedFilePath = escapeshellarg($tempFilePath);
    $command = escapeshellcmd($pythonExecutable) . " " . escapeshellarg($pythonScriptPath) . " " . $escapedFilePath . " 2>&1";

    $jsonOutput = shell_exec($command);

    if ($jsonOutput === null) {
        throw new \Exception("Error executing Python command or no output received.");
    }

    unlink($tempFilePath);

    // 4. Process the JSON output
    if (empty($jsonOutput)) {
        throw new \Exception("The Python script returned no data. Raw output: " . $jsonOutput);
    }

    $dataToImport = json_decode($jsonOutput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("Failed to decode JSON from Python script: " . json_last_error_msg() . ". Raw output: " . htmlspecialchars($jsonOutput));
    }

    // 5. Begin database transaction
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    if (empty($dataToImport)) {
        throw new \Exception("No data was parsed from the Excel file. The file might be empty or in an unexpected format.");
    }

    $dbColumns = array_keys($dataToImport[0]);
    $sql = "INSERT INTO `calculation_data_profit_tax` (" . implode(', ', array_map(function($c) { return "`$c`"; }, $dbColumns)) . ") VALUES (:" . implode(', :', $dbColumns) . ")";
    $insertStmt = $pdo->prepare($sql);
    $deleteStmt = $pdo->prepare("DELETE FROM `calculation_data_profit_tax` WHERE `tin` = :tin AND `calculation_year` = :year");

    $rowsImported = 0;
    foreach ($dataToImport as $row) {
        $tin = $row['tin'] ?? null;
        $year = $row['calculation_year'] ?? null;

        if (empty($tin) || empty($year)) {
            continue;
        }

        $deleteStmt->execute(['tin' => $tin, 'year' => $year]);
        $insertStmt->execute($row);
        $rowsImported++;
    }

    // 6. Commit transaction and redirect
    $pdo->commit();

    $message = urlencode("$rowsImported rows imported successfully via Python parser.");
    header("Location: import_profit_tax.php?status=success&message=$message");
    exit();

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    $errorMessage = urlencode("Import Error: " . $e->getMessage());
    error_log("Import Error: " . $e->getMessage()); // Also log the error to server logs
    header("Location: import_profit_tax.php?status=error&message=$errorMessage");
    exit();
}