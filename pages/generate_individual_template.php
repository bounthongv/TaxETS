<?php
/**
 * ⚠ DEPRECATED — This generator uses an OLD template structure.
 * Use generate_pit_template.php instead (expert-confirmed PIT template v1.0).
 * 
 * Kept for reference only. See docs/PIT-template-apis-standard.xlsx
 * and pages/generate_pit_template.php for the current version.
 */
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Individual Tax Import");

// --- Column Headers (Row 1) ---
$headers = [
    "A" => "Filing Date",
    "B" => "Employee Name",
    "C" => "PTIN",
    "D" => "Overtime/Night Shift (Amount)",
    "E" => "Overtime/Night Shift (Expert TE)",
    "F" => "Severance/Redundancy (Amount)",
    "G" => "Severance/Redundancy (Expert TE)",
    "H" => "Rental Building (Amount)",
    "I" => "Rental Building (Expert TE)",
    "J" => "Rental Land/Other (Amount)",
    "K" => "Rental Land/Other (Expert TE)",
    "L" => "Consulting/Service (Amount)",
    "M" => "Consulting/Service (Expert TE)",
    "N" => "Contractor Income (Amount)",
    "O" => "Contractor Income (Expert TE)",
    "P" => "Shares Transfer (Amount)",
    "R" => "Shares Transfer (Expert TE)",
    "S" => "Dividends (Amount)",
    "T" => "Dividends (Expert TE)",
    "U" => "Interest Loan (Amount)",
    "W" => "Interest Loan (Expert TE)",
    "X" => "Interest Bonds (Amount)",
    "Y" => "Interest Bonds (Expert TE)",
    "Z" => "Gifts/Bonus (Amount)",
    "AA" => "Gifts/Bonus (Expert TE)",
    "AB" => "Social Security Member (YES/NO)",
    "AC" => "Expert TE Total"
];

foreach ($headers as $col => $label) {
    $cell = $sheet->getCell($col . "1");
    $cell->setValue($label);
}

// Style header row
$headerStyle = [
    "font" => ["bold" => true, "size" => 11, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "0D6EFD"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
];
$sheet->getStyle("A1:AC1")->applyFromArray($headerStyle);

// Set column widths
$colWidths = [
    "A" => 14, "B" => 28, "C" => 18,
    "D" => 20, "E" => 20, "F" => 20, "G" => 20,
    "H" => 20, "I" => 20, "J" => 20, "K" => 20,
    "L" => 20, "M" => 20, "N" => 20, "O" => 20,
    "P" => 20, "R" => 20, "S" => 18, "T" => 18,
    "U" => 18, "W" => 18, "X" => 18, "Y" => 18,
    "Z" => 18, "AA" => 18, "AB" => 24, "AC" => 18
];
foreach ($colWidths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

// Number format for amounts
$amountCols = ["D","E","F","G","H","I","J","K","L","M","N","O","P","R","S","T","U","W","X","Y","Z","AA","AC"];
foreach ($amountCols as $col) {
    $sheet->getStyle($col . "2:" . $col . "101")->getNumberFormat()->setFormatCode("#,##0.00");
}

// Data validation: Social Security Member dropdown (YES/NO)
$validation = $sheet->getCell("AB2")->getDataValidation();
$validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
$validation->setFormula1('"YES,NO"');
$validation->setAllowBlank(true);
$validation->setShowDropDown(false);
$sheet->setDataValidation("AB2:AB101", $validation);

// Freeze panes
$sheet->freezePane("A2");

// Example row (Row 2) with sample data
$sheet->getCell("A2")->setValue("2024-01-15");
$sheet->getCell("B2")->setValue("Example Employee");
$sheet->getCell("C2")->setValue("PTIN-XXXXX");
$sheet->getCell("D2")->setValue(5000000);
$sheet->getCell("AB2")->setValue("YES");

// Bold example row
$exampleStyle = [
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
];
$sheet->getStyle("A2:AC2")->applyFromArray($exampleStyle);
$sheet->getStyle("A2")->getNumberFormat()->setFormatCode("YYYY-MM-DD");

// Light border for all data rows
$sheet->getStyle("A2:AC101")->applyFromArray([
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

// Add instruction sheet
$instructions = $spreadsheet->createSheet();
$instructions->setTitle("Instructions");
$instructions->getCell("A1")->setValue("Individual Tax Import Template — Instructions");
$instructions->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$instructions->getColumnDimension("A")->setWidth(60);

$instructionText = [
    "",
    "COLUMN GUIDE:",
    "A: Filing Date (format: YYYY-MM-DD)",
    "B: Employee Name (text)",
    "C: PTIN — Personal Tax Identification Number (text)",
    "D-AC: Amount fields — numeric values in LAK",
    "AB: Social Security Member — use dropdown (YES/NO)",
    "AC: Expert TE Total — sum of all expert TE columns",
    "",
    "NOTES:",
    "- Rows without PTIN (column C) will be skipped",
    "- Amount fields accept numbers with or without commas",
    "- The example row (Row 2) can be deleted before import",
    "- All amount columns should be in LAK (Kip)"
];

foreach ($instructionText as $i => $txt) {
    $instructions->getCell("A" . ($i + 2))->setValue($txt);
    if (str_starts_with($txt, "COLUMN GUIDE:") || str_starts_with($txt, "NOTES:")) {
        $instructions->getStyle("A" . ($i + 2))->getFont()->setBold(true);
    }
}

// Output the file
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"individual_tax_template.xlsx\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
