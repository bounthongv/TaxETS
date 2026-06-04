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
    "A" => "TIN",
    "B" => "CompanyName",
    "C" => "District",
    "D" => "Province",
    "E" => "TaxItem",
    "F" => "Year",
    "G" => "Receiptdate",
    "H" => "Concessionarea",
    "I" => "BenchmarkRate",
    "J" => "ContractedRate",
    "K" => "ConcessionFeePaid",
    "L" => "ProvisionName"
];

foreach ($headers as $col => $label) {
    $sheet->getCell($col . "1")->setValue($label);
}

$sheet->getStyle("A1:L1")->applyFromArray([
    "font" => ["bold" => true, "size" => 11, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "198754"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

$widths = [
    "A" => 18, "B" => 30, "C" => 20, "D" => 20, "E" => 18, "F" => 12,
    "G" => 14, "H" => 18, "I" => 18, "J" => 18, "K" => 22, "L" => 28
];
foreach ($widths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

foreach (["H", "I", "J", "K"] as $col) {
    $sheet->getStyle($col . "2:" . $col . "101")->getNumberFormat()->setFormatCode("#,##0.0000");
}
$sheet->getStyle("G2:G101")->getNumberFormat()->setFormatCode("yyyy-mm-dd");

$sheet->freezePane("A2");

$example = [
    "TIN-XXXXX",
    "Example Company",
    "Example District",
    "Example Province",
    "",
    2026,
    "2026-01-15",
    100.0000,
    50.0000,
    25.0000,
    2500.0000,
    "Rental state land concession"
];
$sheet->fromArray($example, null, "A2");
$sheet->getStyle("A2:L2")->applyFromArray([
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);
$sheet->getStyle("A2:L101")->applyFromArray([
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
    "A: TIN - Tax Identification Number (required)",
    "B: CompanyName - taxpayer/company name when available",
    "C: District - text, mapped to district dictionary when possible",
    "D: Province - text, mapped to province dictionary when possible",
    "E: TaxItem - optional; importer stores no separate field for this yet",
    "F: Year - optional; if blank, the selected tax year on the import page is used",
    "G: Receiptdate - receipt/confirmation date, format YYYY-MM-DD",
    "H: Concessionarea - concession area in hectares",
    "I: BenchmarkRate - benchmark rate used for benchmark value calculation",
    "J: ContractedRate - contracted rate when available",
    "K: ConcessionFeePaid - concession fee paid",
    "L: ProvisionName - optional; leave blank when policy/provision is not confirmed",
    "",
    "NOTES:",
    "- Select Tax Year on the import page before upload",
    "- Rows without TIN in column A are skipped",
    "- Fully blank rows are ignored",
    "- Benchmark Value is calculated by the system as Concessionarea multiplied by BenchmarkRate",
    "- Non-Tax TE is left as 0 when ProvisionName is blank",
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
