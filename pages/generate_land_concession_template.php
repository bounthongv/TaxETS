<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Land Concession Import");

$headers = [
    "A" => "No",
    "B" => "TIN",
    "C" => "District",
    "D" => "Province",
    "E" => "Company Name",
    "F" => "Confirm Date",
    "G" => "Concession Area (ha)",
    "H" => "Benchmark Rate (USD/ha)",
    "I" => "Contracted Rate (USD/ha)",
    "J" => "Concession Fee Paid (USD)",
    "K" => "Benchmark Value (USD)",
    "L" => "Non-Tax TE (USD)",
    "M" => "Provision Name"
];

foreach ($headers as $col => $label) {
    $sheet->getCell($col . "1")->setValue($label);
}

$sheet->getStyle("A1:M1")->applyFromArray([
    "font" => ["bold" => true, "size" => 11, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "198754"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

$widths = [
    "A" => 8, "B" => 18, "C" => 20, "D" => 20, "E" => 30, "F" => 14,
    "G" => 18, "H" => 22, "I" => 22, "J" => 22, "K" => 22, "L" => 18, "M" => 28
];
foreach ($widths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

foreach (["G", "H", "I", "J", "K", "L"] as $col) {
    $sheet->getStyle($col . "2:" . $col . "101")->getNumberFormat()->setFormatCode("#,##0.0000");
}
$sheet->getStyle("F2:F101")->getNumberFormat()->setFormatCode("yyyy-mm-dd");

$sheet->freezePane("A2");

$example = [
    1,
    "TIN-XXXXX",
    "Example District",
    "Example Province",
    "Example Company",
    "2026-01-15",
    100.0000,
    50.0000,
    25.0000,
    2500.0000,
    5000.0000,
    2500.0000,
    "Rental state land concession"
];
$sheet->fromArray($example, null, "A2");
$sheet->getStyle("A2:M2")->applyFromArray([
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);
$sheet->getStyle("A2:M101")->applyFromArray([
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

$instructions = $spreadsheet->createSheet();
$instructions->setTitle("Instructions");
$instructions->getCell("A1")->setValue("Land Concession Import Template - Instructions");
$instructions->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$instructions->getColumnDimension("A")->setWidth(78);

$instructionText = [
    "",
    "COLUMN GUIDE:",
    "A: No - row number only; importer does not store this field",
    "B: TIN - Tax Identification Number (required)",
    "C: District - text, mapped to district dictionary when possible",
    "D: Province - text, mapped to province dictionary when possible",
    "E: Company Name",
    "F: Confirm Date - format YYYY-MM-DD",
    "G: Concession Area in hectares",
    "H: Benchmark Rate in USD per hectare",
    "I: Contracted Rate in USD per hectare",
    "J: Concession Fee Paid in USD",
    "K: Benchmark Value in USD, optional; recalculation can update it",
    "L: Non-Tax TE in USD, optional; recalculation can update it",
    "M: Provision Name",
    "",
    "NOTES:",
    "- Select Tax Year on the import page before upload",
    "- Rows without TIN in column B are skipped",
    "- Fully blank rows are ignored",
    "- Delete or replace the example row before import"
];

foreach ($instructionText as $i => $text) {
    $cell = "A" . ($i + 2);
    $instructions->getCell($cell)->setValue($text);
    if (str_ends_with($text, ":")) {
        $instructions->getStyle($cell)->getFont()->setBold(true);
    }
}

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"land_concession_template.xlsx\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
