<?php
// public/process_import_simple.php - Simplified process that works with working server config
// This approach handles the file upload with basic PHP and then calls Python for accurate parsing

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Directly include the Database class
require_once '../src/Services/Database.php';
use TaxETS\Services\Database;

try {
    // Give the script up to 5 minutes to run
    set_time_limit(300);

    // 1. Verify the file upload is valid (using the original field name that was working)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profitTaxFile']) || $_FILES['profitTaxFile']['error'] !== UPLOAD_ERR_OK) {
        // For compatibility, also check for the import_file field name
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Invalid file upload. Expected field name 'profitTaxFile' or 'import_file'.");
        } else {
            $file = $_FILES['import_file'];
        }
    } else {
        $file = $_FILES['profitTaxFile'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'xlsx') {
        throw new \Exception("Invalid file type. Only .xlsx files are accepted.");
    }

    // 2. Move the uploaded file to a temporary location
    $tempDir = sys_get_temp_dir();
    $tempFilePath = $tempDir . '/' . uniqid('taxets_import_') . '.xlsx';
    if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        throw new \Exception('Failed to move uploaded file.');
    }

    // 3. Call the Python script to process the Excel file
    $projectRoot = realpath(__DIR__ . '/..');
    $pythonScriptPath = $projectRoot . '/parse_excel.py';
    
    if (!file_exists($pythonScriptPath)) {
        throw new \Exception('Could not find the Python parser script (parse_excel.py).');
    }
    
    // Determine Python executable
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $venvPath = $projectRoot . '/venv';
    $pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';
    
    // Fallback to system Python if venv doesn't exist
    if (!file_exists($pythonExecutable)) {
        $pythonExecutable = $isWindows ? 'python.exe' : 'python';
    }

    // Check if shell_exec is available
    $disabled_functions = explode(',', ini_get('disable_functions'));
    $is_shell_exec_disabled = in_array('shell_exec', array_map('trim', $disabled_functions));
    if ($is_shell_exec_disabled) {
        throw new \Exception("shell_exec is disabled in PHP configuration. Python processing requires shell_exec to be enabled.");
    }

    $escapedFilePath = escapeshellarg($tempFilePath);
    $command = escapeshellcmd($pythonExecutable) . " " . escapeshellarg($pythonScriptPath) . " " . $escapedFilePath . " 2>&1";

    $jsonOutput = shell_exec($command);

    if ($jsonOutput === null) {
        throw new \Exception("Error executing Python command or no output received.");
    }

    // Clean up the temporary file
    unlink($tempFilePath);

    // 4. Process the JSON output from Python
    if (empty($jsonOutput)) {
        throw new \Exception("The Python script returned no data. Command output: " . $jsonOutput);
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

    // Prepare the insert statement
    $dbColumns = array_keys($dataToImport[0]);
    $columnList = implode(', ', array_map(function($c) { return "`$c`"; }, $dbColumns));
    $placeholderList = ':' . implode(', :', $dbColumns);
    $sql = "INSERT INTO `calculation_data_profit_tax` ($columnList) VALUES ($placeholderList)";
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

    // 6. Commit and redirect
    $pdo->commit();

    $message = urlencode("$rowsImported rows imported successfully via Python parser.");
    header("Location: import_profit_tax.php?status=success&message=$message");
    exit();

} catch (\Exception $e) {
    // Clean up temp file if it still exists
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMessage = urlencode("Import Error: " . $e->getMessage());
    error_log("Import Error: " . $e->getMessage());
    header("Location: import_profit_tax.php?status=error&message=$errorMessage");
    exit();
}