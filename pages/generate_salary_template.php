<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Salary Tax Import");

// --- Column Headers (Row 1) ---
$headers = [
    "A" => "TIN",
    "B" => "Tax Year",
    "C" => "Filing Type",
    "D" => "Filing Period",
    "E" => "Input Date",
    "F" => "Total Salaries & Wages (Cash)",
    "G" => "Other Fringe Benefits",
    "H" => "Total Taxable Amount",
    "I" => "Tax Exempt Amount",
    "J" => "Tax Amount",
    "K" => "Adjustment Amount",
    "L" => "Carryforward Amount",
    "M" => "Total Amount Due",
    "N" => "Provision Number"
];

foreach ($headers as $col => $label) {
    $sheet->getCell($col . "1")->setValue($label);
}

// Style header row
$headerStyle = [
    "font" => ["bold" => true, "size" => 11, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "198754"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
];
$sheet->getStyle("A1:N1")->applyFromArray($headerStyle);

// Set column widths
$colWidths = ["A" => 18, "B" => 10, "C" => 14, "D" => 14, "E" => 14, "F" => 20, "G" => 20, "H" => 20, "I" => 18, "J" => 16, "K" => 18, "L" => 18, "M" => 18, "N" => 16];
foreach ($colWidths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

// Number format for amounts
$amountCols = ["F","G","H","I","J","K","L","M"];
foreach ($amountCols as $col) {
    $sheet->getStyle($col . "2:" . $col . "101")->getNumberFormat()->setFormatCode("#,##0.00");
}

// Data validation: Filing Type dropdown
$validationType = $sheet->getCell("C2")->getDataValidation();
$validationType->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
$validationType->setFormula1('"Monthly,Yearly"');
$validationType->setAllowBlank(true);
$validationType->setShowDropDown(false);
$sheet->setDataValidation("C2:C101", $validationType);

// Freeze panes
$sheet->freezePane("A2");

// Example row (Row 2)
$sheet->getCell("A2")->setValue("TIN-XXXXX");
$sheet->getCell("B2")->setValue(2026);
$sheet->getCell("C2")->setValue("Monthly");
$sheet->getCell("D2")->setValue("05/2026");
$sheet->getCell("E2")->setValue("2026-05-15");
$sheet->getCell("F2")->setValue(50000000);
$sheet->getCell("H2")->setValue(50000000);
$sheet->getCell("N2")->setValue("T21");

$exampleStyle = [
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
];
$sheet->getStyle("A2:N2")->applyFromArray($exampleStyle);

// Light border for all data rows
$sheet->getStyle("A2:N101")->applyFromArray([
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

// Add instruction sheet
$instructions = $spreadsheet->createSheet();
$instructions->setTitle("Instructions");
$instructions->getCell("A1")->setValue("Salary Tax Import Template — Instructions");
$instructions->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$instructions->getColumnDimension("A")->setWidth(60);

$instructionText = [
    "",
    "COLUMN GUIDE:",
    "A: TIN — Tax Identification Number (required)",
    "B: Tax Year (optional — derived from Filing Period if blank)",
    "C: Filing Type — use dropdown (Monthly/Yearly)",
    "D: Filing Period — format MM/YYYY (e.g. 05/2026)",
    "E: Input Date — format YYYY-MM-DD",
    "F: Total Salaries & Wages (Cash) in LAK",
    "G: Other Fringe Benefits in LAK",
    "H: Total Taxable Amount = F + G in LAK",
    "I: Tax Exempt Amount in LAK",
    "J: Tax Amount Paid in LAK",
    "K: Adjustment Amount in LAK",
    "L: Carryforward Amount in LAK",
    "M: Total Amount Due = J + K - L in LAK",
    "N: Provision Number (e.g. T21, T22)",
    "",
    "NOTES:",
    "- Rows without TIN (column A) will be skipped",
    "- Use the Filing Type dropdown for consistent data",
    "- The example row (Row 2) can be deleted before import",
    "- All amounts in LAK (Kip)"
];

foreach ($instructionText as $i => $txt) {
    $instructions->getCell("A" . ($i + 2))->setValue($txt);
    if (str_starts_with($txt, "COLUMN GUIDE:") || str_starts_with($txt, "NOTES:")) {
        $instructions->getStyle("A" . ($i + 2))->getFont()->setBold(true);
    }
}

// Output
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"salary_tax_template.xlsx\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
