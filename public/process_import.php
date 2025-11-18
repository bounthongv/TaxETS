<?php
// public/process_import.php

session_start();

require '../vendor/autoload.php'; // For PhpSpreadsheet
require '../src/Services/Database.php';
use TaxETS\Services\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Helper function to clean numeric values
function cleanNumber($value) {
    if (is_null($value) || $value === '') return null;
    $cleaned = preg_replace('/[^-0-9\.]/', '', $value);
    return is_numeric($cleaned) ? (float) $cleaned : null;
}

// Helper function to parse date values
function formatDate($value) {
    if (empty($value)) return null;
    // PhpSpreadsheet returns dates as numeric values (Excel serial dates)
    if (is_numeric($value)) {
        try {
            $utcDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $utcDate->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    // Otherwise, try to parse as a string date
    try {
        return (new DateTime($value))->format('Y-m-d');
    } catch (\Exception $e) {
        return null; // Return null if parsing fails
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profitTaxFile']) || $_FILES['profitTaxFile']['error'] !== UPLOAD_ERR_OK) {
        throw new \Exception("Invalid file upload.");
    }

    $file = $_FILES['profitTaxFile'];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'xlsx') {
        throw new \Exception("Invalid file type. Only .xlsx files are accepted.");
    }

    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();

    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // Column mapping (Excel column letter to database field name)
    $columnMap = [
        'A' => 'calculation_year',
        'B' => 'investment_license_date',
        'C' => 'date_first_revenue',
        'D' => 'company_name',
        'E' => 'tin',
        'F' => 'province',
        'G' => 'district',
        'H' => 'zone_1', // Zone 1
        'I' => 'zone_2', // Zone 2
        'J' => 'zone_3', // Zone 3
        'K' => 'sector',
        'L' => 'revenue',
        'M' => 'expense',
        'O' => 'reinvested_profit_amount',
        'P' => 'pt_paid',
        'T' => 'tax_holiday_period_years',
        'U' => 'registration_date',
        'V' => 'is_human_resource_dev',
        'W' => 'is_innovative_green_tech',
        'X' => 'is_sez_developer',
        'Y' => 'is_sez_investor',
        'Z' => 'is_in_sez_specified_activity',
        'AA' => 'is_public_benefit_income',
        'AB' => 'is_asset_rent_compliant',
        'AC' => 'is_real_estate_transfer',
        // AD to AL are IPL activity flags
        'AD' => 'ipl_activity_1',
        'AE' => 'ipl_activity_2',
        'AF' => 'ipl_activity_3',
        'AG' => 'ipl_activity_4',
        'AH' => 'ipl_activity_5',
        'AI' => 'ipl_activity_6',
        'AJ' => 'ipl_activity_7',
        'AK' => 'ipl_activity_8',
        'AL' => 'ipl_activity_9',
        'AM' => 'is_vat_holder',
        'AN' => 'reinvest_date',
        'AP' => 'total_assets_billion',
        'AQ' => 'annual_turnover_billion',
        'AR' => 'staff_count',
        'AS' => 'stock_listing_date',
        'BO' => 'applied_te_ids_from_import', // Column BO
    ];

    // Prepare the SQL statement
    $dbColumns = [
        'calculation_year', 'investment_license_date', 'date_first_revenue', 'company_name', 'tin',
        'province', 'district', 'zone', 'sector', 'revenue', 'expense', 'reinvested_profit_amount',
        'pt_paid', 'tax_holiday_period_years', 'registration_date', 'is_human_resource_dev',
        'is_innovative_green_tech', 'is_sez_developer', 'is_sez_investor', 'is_in_sez_specified_activity',
        'is_public_benefit_income', 'is_asset_rent_compliant', 'is_real_estate_transfer',
        'ipl_activity_flags', 'is_vat_holder', 'reinvest_date', 'total_assets_billion',
        'annual_turnover_billion', 'staff_count', 'stock_listing_date', 'applied_te_ids_from_import'
    ];
    $sql = "INSERT INTO `calculation_data_profit_tax` (" . implode(', ', $dbColumns) . ") VALUES (:" . implode(', :', $dbColumns) . ")";
    $insertStmt = $pdo->prepare($sql);

    $deleteStmt = $pdo->prepare("DELETE FROM `calculation_data_profit_tax` WHERE `tin` = :tin AND `calculation_year` = :year");

    $rowsImported = 0;
    // Start from row 2 to skip headers
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];
        foreach ($columnMap as $colLetter => $dbField) {
            $cellValue = $worksheet->getCell($colLetter . $row)->getCalculatedValue();
            $rowData[$dbField] = $cellValue;
        }

        $tin = $rowData['tin'];
        $year = $rowData['calculation_year'];

        if (empty($tin) || empty($year)) {
            continue;
        }

        $deleteStmt->execute(['tin' => $tin, 'year' => $year]);

        // Handle Zone
        $zone = null;
        if ($rowData['zone_1'] == 1) $zone = 1;
        elseif ($rowData['zone_2'] == 1) $zone = 2;
        elseif ($rowData['zone_3'] == 1) $zone = 3;

        // Handle IPL Activity Flags
        $iplFlags = [];
        for ($i = 1; $i <= 9; $i++) {
            $flagKey = 'activity_' . $i;
            $iplFlags[$flagKey] = ($rowData['ipl_activity_' . $i] == 1) ? 1 : 0;
        }

        // Handle Applied TE IDs
        $appliedTeIds = [];
        $rawTeString = $rowData['applied_te_ids_from_import'];
        if ($rawTeString !== null && $rawTeString !== '') {
            $teStrings = explode(',', $rawTeString);
            foreach ($teStrings as $teString) {
                $trimmedTeString = trim($teString);
                if (str_contains(strtolower($trimmedTeString), 'other')) {
                    $appliedTeIds[] = 21;
                } elseif (is_numeric($trimmedTeString)) {
                    $appliedTeIds[] = (int)$trimmedTeString;
                }
            }
        }

        // Clean and finalize data for insertion
        $cleanedData = [
            'tin' => $rowData['tin'],
            'company_name' => $rowData['company_name'],
            'calculation_year' => (int) $rowData['calculation_year'],
            'revenue' => cleanNumber($rowData['revenue']),
            'expense' => cleanNumber($rowData['expense']),
            'pt_paid' => cleanNumber($rowData['pt_paid']),
            'reinvested_profit_amount' => cleanNumber($rowData['reinvested_profit_amount']),
            'reinvest_date' => formatDate($rowData['reinvest_date']),
            'province' => $rowData['province'],
            'district' => $rowData['district'],
            'sector' => $rowData['sector'],
            'zone' => $zone,
            'is_vat_holder' => ($rowData['is_vat_holder'] == '1') ? 1 : 0,
            'staff_count' => (int) $rowData['staff_count'],
            'total_assets_billion' => cleanNumber($rowData['total_assets_billion']),
            'annual_turnover_billion' => cleanNumber($rowData['annual_turnover_billion']),
            'investment_license_date' => formatDate($rowData['investment_license_date']),
            'date_first_revenue' => formatDate($rowData['date_first_revenue']),
            'registration_date' => formatDate($rowData['registration_date']),
            'stock_listing_date' => formatDate($rowData['stock_listing_date']),
            'tax_holiday_period_years' => (int) $rowData['tax_holiday_period_years'],
            'is_human_resource_dev' => ($rowData['is_human_resource_dev'] == '1') ? 1 : 0,
            'is_innovative_green_tech' => ($rowData['is_innovative_green_tech'] == '1') ? 1 : 0,
            'is_sez_developer' => ($rowData['is_sez_developer'] == '1') ? 1 : 0,
            'is_sez_investor' => ($rowData['is_sez_investor'] == '1') ? 1 : 0,
            'is_in_sez_specified_activity' => ($rowData['is_in_sez_specified_activity'] == '1') ? 1 : 0,
            'is_public_benefit_income' => ($rowData['is_public_benefit_income'] == '1') ? 1 : 0,
            'is_asset_rent_compliant' => ($rowData['is_asset_rent_compliant'] == '1') ? 1 : 0,
            'is_real_estate_transfer' => ($rowData['is_real_estate_transfer'] == '1') ? 1 : 0,
            'ipl_activity_flags' => json_encode($iplFlags),
            'applied_te_ids_from_import' => json_encode(array_unique($appliedTeIds)),
        ];

        $insertStmt->execute($cleanedData);
        $rowsImported++;
    }

    $pdo->commit();

    $message = urlencode("$rowsImported rows imported successfully.");
    header("Location: import_profit_tax.php?status=success&message=$message");
    exit();

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorMessage = urlencode("Import Error: " . $e->getMessage());
    header("Location: import_profit_tax.php?status=error&message=$errorMessage");
    exit();
}
