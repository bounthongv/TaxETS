<?php
// public/process_import.php

session_start();

// Set high limits to prevent timeouts and memory issues during large file processing
ini_set('max_execution_time', 1800); // 30 minutes
ini_set('memory_limit', '1024M');    // 1GB

require '../src/Services/Database.php';
require '../src/Services/TaxCalculator.php';
use TaxETS\Services\Database;
use TaxETS\Services\TaxCalculator;

// Define a log file path
$logFile = __DIR__ . '/import_log.log';

// Helper function for logging
function writeLog($message, $logFile) {
    // Clear the log file for new import sessions
    static $cleared = false;
    if (!$cleared) {
        file_put_contents($logFile, '');
        $cleared = true;
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

writeLog('Script started.', $logFile);

try {
    // --- Define Python Environment Paths ---
    $projectRoot = realpath(__DIR__ . '/..');
    $venvPath = $projectRoot . '/venv';

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $pythonExecutable = $isWindows ? $venvPath . '/Scripts/python.exe' : $venvPath . '/bin/python';
    // --- End Paths ---

    writeLog('Python executable path: ' . $pythonExecutable, $logFile);

    // 1. Verify the file upload is valid
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profitTaxFile']) || $_FILES['profitTaxFile']['error'] !== UPLOAD_ERR_OK) {
        throw new \Exception("Invalid file upload.");
    }
    writeLog('File upload verified.', $logFile);

    $file = $_FILES['profitTaxFile'];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'xlsx') {
        throw new \Exception("Invalid file type. Only .xlsx files are accepted.");
    }
    writeLog('File type verified: ' . $extension, $logFile);

    // 2. Move the uploaded file to a temporary, known location
    $tempDir = sys_get_temp_dir();
    $tempFilePath = $tempDir . '/' . uniqid('taxets_import_') . '.xlsx';
    if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        throw new \Exception('Failed to move uploaded file.');
    }
    writeLog('Uploaded file moved to: ' . $tempFilePath, $logFile);

    // 3. Call the Python script using the virtual environment's interpreter
    $pythonScriptPath = $projectRoot . '/parse_excel.py';
    if (!file_exists($pythonScriptPath)) {
        throw new \Exception('Could not find the Python parser script (parse_excel.py).');
    }
    if (!file_exists($pythonExecutable)) {
        throw new \Exception("Python virtual environment not found. Please run the setup commands from the command line.");
    }
    writeLog('Python script path: ' . $pythonScriptPath, $logFile);

    $escapedFilePath = escapeshellarg($tempFilePath);
    $command = escapeshellcmd($pythonExecutable) . " " . escapeshellarg($pythonScriptPath) . " " . $escapedFilePath . " 2>&1";
    writeLog('Executing command: ' . $command, $logFile);

    // Execute the command and capture the output
    $jsonOutput = shell_exec($command);
    writeLog('Python script execution finished.', $logFile);

    // Clean up the temporary Excel file
    unlink($tempFilePath);
    writeLog('Temporary Excel file deleted.', $logFile);

    // 4. Process the JSON output
    if (empty($jsonOutput)) {
        throw new \Exception("The Python script returned no data. This could indicate a Python error or an empty result. Raw output: " . htmlspecialchars($jsonOutput));
    }
    writeLog('Python script returned data. Attempting JSON decode.', $logFile);

    $dataToImport = json_decode($jsonOutput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("Failed to decode JSON from Python script: " . json_last_error_msg() . ". Raw output: " . htmlspecialchars($jsonOutput));
    }
    writeLog('JSON decoded successfully. Number of records: ' . count($dataToImport), $logFile);

    // 5. Begin database transaction
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();
    writeLog('Database transaction started.', $logFile);

    if (empty($dataToImport)) {
        throw new \Exception("No data was parsed from the Excel file. The file might be empty or in an unexpected format.");
    }

    // Prepare statements for this loop - explicitly define the columns to match the database schema
    $insertDataSql = "INSERT INTO `calculation_data_profit_tax` (
        `tin`, `company_name`, `calculation_year`, `revenue`, `expense`, `pt_paid`,
        `reinvested_profit_amount`, `reinvest_date`, `province`, `district`, `sector`,
        `zone`, `is_vat_holder`, `staff_count`, `total_assets_billion`,
        `annual_turnover_billion`, `investment_license_date`, `date_first_revenue`,
        `registration_date`, `stock_listing_date`, `tax_holiday_period_years`,
        `is_human_resource_dev`, `is_innovative_green_tech`, `is_sez_developer`,
        `is_sez_investor`, `is_in_sez_specified_activity`, `is_public_benefit_income`,
        `is_asset_rent_compliant`, `is_real_estate_transfer`, `ipl_activity_flags`,
        `applied_te_ids_from_import`, `te_1`, `te_2`, `te_3`, `te_4`, `te_5`,
        `te_6`, `te_7`, `te_8`, `te_9`, `te_10`, `te_11`, `te_12`, `te_13`,
        `te_14`, `te_15`, `te_16`, `te_17`, `te_18`, `te_19`, `te_20`, `te_other`
    ) VALUES (
        :tin, :company_name, :calculation_year, :revenue, :expense, :pt_paid,
        :reinvested_profit_amount, :reinvest_date, :province, :district, :sector,
        :zone, :is_vat_holder, :staff_count, :total_assets_billion,
        :annual_turnover_billion, :investment_license_date, :date_first_revenue,
        :registration_date, :stock_listing_date, :tax_holiday_period_years,
        :is_human_resource_dev, :is_innovative_green_tech, :is_sez_developer,
        :is_sez_investor, :is_in_sez_specified_activity, :is_public_benefit_income,
        :is_asset_rent_compliant, :is_real_estate_transfer, :ipl_activity_flags,
        :applied_te_ids_from_import, :te_1, :te_2, :te_3, :te_4, :te_5,
        :te_6, :te_7, :te_8, :te_9, :te_10, :te_11, :te_12, :te_13,
        :te_14, :te_15, :te_16, :te_17, :te_18, :te_19, :te_20, :te_other
    )";
    $insertDataStmt = $pdo->prepare($insertDataSql);

    $deleteDataStmt = $pdo->prepare("DELETE FROM `calculation_data_profit_tax` WHERE `tin` = :tin AND `calculation_year` = :calculation_year");
    $deleteResultsStmt = $pdo->prepare("DELETE FROM `calculation_results_profit_tax` WHERE `tin` = :tin AND `calculation_year` = :calculation_year");

    $insertResultsSql = "INSERT INTO `calculation_results_profit_tax` (source_data_id, tin, calculation_year, system_net_profit, system_benchmark_tax, applied_te_provision_id, system_actual_tax_payable, system_pt_te, cross_check_difference, calculation_timestamp) VALUES (:source_data_id, :tin, :calculation_year, :system_net_profit, :system_benchmark_tax, :applied_te_provision_id, :system_actual_tax_payable, :system_pt_te, :cross_check_difference, NOW())";
    $insertResultsStmt = $pdo->prepare($insertResultsSql);
    writeLog('SQL statements prepared.', $logFile);

    $taxCalculator = new TaxCalculator($pdo);
    writeLog('TaxCalculator instantiated.', $logFile);

    $rowsImported = 0;
    $rowsCalculated = 0;
    foreach ($dataToImport as $companyData) {
        $tin = $companyData['tin'] ?? null;
        $year = $companyData['calculation_year'] ?? null;

        if (empty($tin) || empty($year)) {
            writeLog('Skipping row due to empty TIN or calculation_year.', $logFile);
            continue;
        }

        try {
            // Apply Business Rules for Missing Data
            if (!isset($companyData['is_accounting_holder']) || $companyData['is_accounting_holder'] === null) {
                $companyData['is_accounting_holder'] = 0;
            }
            if (empty($companyData['staff_count'])) {
                $companyData['staff_count'] = 150;
            }
            $companyData['business_activity_description'] = $companyData['business_activity_description'] ?? $companyData['sector'];

            // Create a filtered array with only the fields that exist in the database
            $filteredData = [
                'tin' => $companyData['tin'] ?? null,
                'company_name' => $companyData['company_name'] ?? null,
                'calculation_year' => $companyData['calculation_year'] ?? null,
                'revenue' => $companyData['revenue'] ?? null,
                'expense' => $companyData['expense'] ?? null,
                'pt_paid' => $companyData['pt_paid'] ?? null,
                'reinvested_profit_amount' => $companyData['reinvested_profit_amount'] ?? null,
                'reinvest_date' => $companyData['reinvest_date'] ?? null,
                'province' => $companyData['province'] ?? null,
                'district' => $companyData['district'] ?? null,
                'sector' => $companyData['sector'] ?? null,
                'zone' => $companyData['zone'] ?? null,
                'is_vat_holder' => $companyData['is_vat_holder'] ?? null,
                'staff_count' => $companyData['staff_count'] ?? null,
                'total_assets_billion' => $companyData['total_assets_billion'] ?? null,
                'annual_turnover_billion' => $companyData['annual_turnover_billion'] ?? null,
                'investment_license_date' => $companyData['investment_license_date'] ?? null,
                'date_first_revenue' => $companyData['date_first_revenue'] ?? null,
                'registration_date' => $companyData['registration_date'] ?? null,
                'stock_listing_date' => $companyData['stock_listing_date'] ?? null,
                'tax_holiday_period_years' => $companyData['tax_holiday_period_years'] ?? null,
                'is_human_resource_dev' => $companyData['is_human_resource_dev'] ?? null,
                'is_innovative_green_tech' => $companyData['is_innovative_green_tech'] ?? null,
                'is_sez_developer' => $companyData['is_sez_developer'] ?? null,
                'is_sez_investor' => $companyData['is_sez_investor'] ?? null,
                'is_in_sez_specified_activity' => $companyData['is_in_sez_specified_activity'] ?? null,
                'is_public_benefit_income' => $companyData['is_public_benefit_income'] ?? null,
                'is_asset_rent_compliant' => $companyData['is_asset_rent_compliant'] ?? null,
                'is_real_estate_transfer' => $companyData['is_real_estate_transfer'] ?? null,
                'ipl_activity_flags' => $companyData['ipl_activity_flags'] ?? null,
                'applied_te_ids_from_import' => $companyData['applied_te_ids_from_import'] ?? null,
                'te_1' => $companyData['te_1'] ?? null,
                'te_2' => $companyData['te_2'] ?? null,
                'te_3' => $companyData['te_3'] ?? null,
                'te_4' => $companyData['te_4'] ?? null,
                'te_5' => $companyData['te_5'] ?? null,
                'te_6' => $companyData['te_6'] ?? null,
                'te_7' => $companyData['te_7'] ?? null,
                'te_8' => $companyData['te_8'] ?? null,
                'te_9' => $companyData['te_9'] ?? null,
                'te_10' => $companyData['te_10'] ?? null,
                'te_11' => $companyData['te_11'] ?? null,
                'te_12' => $companyData['te_12'] ?? null,
                'te_13' => $companyData['te_13'] ?? null,
                'te_14' => $companyData['te_14'] ?? null,
                'te_15' => $companyData['te_15'] ?? null,
                'te_16' => $companyData['te_16'] ?? null,
                'te_17' => $companyData['te_17'] ?? null,
                'te_18' => $companyData['te_18'] ?? null,
                'te_19' => $companyData['te_19'] ?? null,
                'te_20' => $companyData['te_20'] ?? null,
                'te_other' => $companyData['te_other'] ?? null
            ];

            // Insert/Update `calculation_data_profit_tax` and get ID
            $deleteDataStmt->execute([':tin' => $tin, ':calculation_year' => $year]);
            $insertDataStmt->execute($filteredData);
            $sourceDataId = $pdo->lastInsertId();
            $companyData['id'] = $sourceDataId;
            $rowsImported++;

            // 1. Calculate Benchmark Tax
            $benchmarkResults = $taxCalculator->calculateBenchmarkTax($companyData);
            $benchmarkTax = $benchmarkResults['benchmark_tax'];

            // 2. Get the actual tax paid from the Excel file
            $taxPaidFromExcel = (float)($companyData['pt_paid'] ?? 0.0);

            // 3. Calculate the "Actual TE" based on the user's clarified logic
            $actualTE = max(0, $benchmarkTax - $taxPaidFromExcel);

            // 4. For cross-check, calculate what the tax should be based on the system's rule engine
            $taxPayableBySystem = $taxCalculator->calculateTaxPayableByProvision(
                $companyData,
                $benchmarkTax,
                $benchmarkResults['is_micro_enterprise'],
                $benchmarkResults['actual_profit'],
                $benchmarkResults['standard_profit_tax_rate']
            );

            // 5. Calculate the cross-check difference
            $crossCheckDifference = $taxPayableBySystem - $taxPaidFromExcel;

            // 6. Identify the provision from the Excel file for statistical purposes
            $statisticalProvisionId = $taxCalculator->identifyAppliedProvisionId($companyData);

            // 7. Store all results
            $deleteResultsStmt->execute([':tin' => $tin, ':calculation_year' => $year]);
            $insertResultsStmt->execute([
                ':source_data_id' => $sourceDataId,
                ':tin' => $tin,
                ':calculation_year' => $year,
                ':system_net_profit' => $benchmarkResults['actual_profit'],
                ':system_benchmark_tax' => $benchmarkTax,
                ':applied_te_provision_id' => $statisticalProvisionId,
                ':system_actual_tax_payable' => $taxPaidFromExcel,
                ':system_pt_te' => $actualTE,
                ':cross_check_difference' => $crossCheckDifference
            ]);
            $rowsCalculated++;
        } catch (\Exception $calcE) {
            writeLog("Calculation Error for TIN: {$tin}, Year: {$year} - " . $calcE->getMessage(), $logFile);
        }
    }
    writeLog("$rowsImported rows imported and $rowsCalculated rows calculated and results stored.", $logFile);

    // 6. Commit transaction and redirect
    $pdo->commit();
    writeLog('Database transaction committed.', $logFile);

    $message = urlencode("$rowsImported rows imported and $rowsCalculated calculations performed successfully.");
    writeLog('Redirecting to success page.', $logFile);
    header("Location: import_profit_tax.php?status=success&message=$message");
    exit();

} catch (\Throwable $e) { // Catch Throwable to include ParseError
    writeLog('Caught a throwable: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine(), $logFile);
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        writeLog('Database transaction rolled back.', $logFile);
    }
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
        writeLog('Temporary Excel file deleted during error handling.', $logFile);
    }
    header("Location: import_profit_tax.php?status=error&message=" . urlencode("Import Error: " . $e->getMessage()));
    exit();
}
writeLog('Script finished.', $logFile);