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
$sheet->setTitle("VAT Import");

// --- Column Headers (Row 1) ---
$headers = [
    "A" => "Province",
    "B" => "TIN",
    "C" => "Company Name",
    "D" => "Filing Type",
    "E" => "Filing Period",
    "F" => "Input Date",
    "G" => "Rate (auto)",
    "H" => "Domestic Non-Exempt Purchases",
    "I" => "Domestic Exempt Purchases / Exempt Sales",
    "J" => "Expert TE"
];

foreach ($headers as $col => $label) {
    $sheet->getCell($col . "1")->setValue($label);
}

// Style header row
$headerStyle = [
    "font" => ["bold" => true, "size" => 11, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "0D6EFD"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
];
$sheet->getStyle("A1:J1")->applyFromArray($headerStyle);

// Set column widths
$colWidths = ["A" => 18, "B" => 18, "C" => 28, "D" => 14, "E" => 16, "F" => 14, "G" => 10, "H" => 22, "I" => 24, "J" => 16];
foreach ($colWidths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

// Number format for amounts
$amountCols = ["H","I","J"];
foreach ($amountCols as $col) {
    $sheet->getStyle($col . "2:" . $col . "101")->getNumberFormat()->setFormatCode("#,##0.00");
}

// Data validation: Filing Type dropdown
$validationType = $sheet->getCell("D2")->getDataValidation();
$validationType->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
$validationType->setFormula1('"Monthly,Yearly"');
$validationType->setAllowBlank(true);
$validationType->setShowDropDown(false);
$sheet->setDataValidation("D2:D101", $validationType);

// Freeze panes
$sheet->freezePane("A2");

// Example row (Row 2)
$sheet->getCell("A2")->setValue("Vientiane");
$sheet->getCell("B2")->setValue("TIN-XXXXX");
$sheet->getCell("C2")->setValue("Example Company");
$sheet->getCell("D2")->setValue("Monthly");
$sheet->getCell("E2")->setValue("2026-05-01");
$sheet->getCell("F2")->setValue("2026-05-15");
$sheet->getCell("G2")->setValue("10%");
$sheet->getCell("H2")->setValue(50000000);
$sheet->getCell("I2")->setValue(2000000);
$sheet->getCell("J2")->setValue(200000);

$exampleStyle = [
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
];
$sheet->getStyle("A2:J2")->applyFromArray($exampleStyle);

// Light border for all data rows
$sheet->getStyle("A2:J101")->applyFromArray([
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

// Add instruction sheet
$instructions = $spreadsheet->createSheet();
$instructions->setTitle("Instructions");
$instructions->getCell("A1")->setValue("Domestic VAT Import Template — Instructions");
$instructions->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$instructions->getColumnDimension("A")->setWidth(60);

$instructionText = [
    "",
    "COLUMN GUIDE:",
    "A: Province (text, will be auto-mapped to dictionary)",
    "B: TIN — Tax Identification Number (required)",
    "C: Company Name",
    "D: Filing Type — use dropdown (Monthly/Yearly)",
    "E: Filing Period — date (YYYY-MM-DD or MM/YYYY)",
    "F: Input Date — date (YYYY-MM-DD)",
    "G: Rate (auto-calculated by system)",
    "H: Domestic Non-Exempt Purchases in LAK",
    "I: Domestic Exempt Purchases / Exempt Sales in LAK",
    "J: Expert TE in LAK",
    "",
    "NOTES:",
    "- Rows without TIN (column B) will be skipped",
    "- Province auto-mapped via Smart Mapping (aliases + fuzzy match)",
    "- District is not imported; use the View page to assign districts",
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
header("Content-Disposition: attachment; filename=\"vat_template.xlsx\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
