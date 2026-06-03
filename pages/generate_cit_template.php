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
$sheet->setTitle("CIT Import");

$headers = [
    "A" => "Tax Year",
    "B" => "Investment License Date",
    "C" => "Company Name",
    "D" => "TIN",
    "E" => "Province",
    "F" => "District",
    "G" => "Zone 1",
    "H" => "Zone 2",
    "I" => "Zone 3",
    "J" => "Sector",
    "K" => "Revenue",
    "L" => "Expense",
    "M" => "Net Profit",
    "N" => "Re-Invested Profit",
    "O" => "Profit Tax Paid",
    "S" => "Tax Holiday Years",
    "T" => "Registration Date",
    "U" => "HR Development",
    "V" => "Eco Friendly",
    "W" => "SEZ Developer",
    "X" => "SEZ Investor",
    "Y" => "Production / Services",
    "Z" => "Public Benefit",
    "AA" => "Compliant Rental",
    "AB" => "Real Estate Transfer",
    "AC" => "Activity 1",
    "AD" => "Activity 2",
    "AE" => "Activity 3",
    "AF" => "Activity 4",
    "AG" => "Activity 5",
    "AH" => "Activity 6",
    "AI" => "Activity 7",
    "AJ" => "Activity 8",
    "AK" => "Activity 9",
    "AL" => "VAT Holder",
    "AM" => "Reinvest Date",
    "AN" => "Reinvest Amount",
    "AO" => "Total Assets (billion LAK)",
    "AP" => "Annual Turnover (billion LAK)",
    "AQ" => "Staff Count",
    "AR" => "Stock Exchange Listing Date",
    "BO" => "Expert TE"
];

foreach ($headers as $col => $label) {
    $sheet->getCell($col . "1")->setValue($label);
}

$sheet->getStyle("A1:BO1")->applyFromArray([
    "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "0D6EFD"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

foreach (range("A", "Z") as $col) {
    $sheet->getColumnDimension($col)->setWidth(16);
}
foreach (["AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","BO"] as $col) {
    $sheet->getColumnDimension($col)->setWidth(18);
}
$sheet->getColumnDimension("C")->setWidth(28);
$sheet->getColumnDimension("J")->setWidth(26);

foreach (["K","L","M","N","O","AN","BO"] as $col) {
    $sheet->getStyle($col . "2:" . $col . "101")->getNumberFormat()->setFormatCode("#,##0.00");
}
$sheet->getStyle("B2:B101")->getNumberFormat()->setFormatCode("yyyy-mm-dd");
$sheet->getStyle("T2:T101")->getNumberFormat()->setFormatCode("yyyy-mm-dd");
$sheet->getStyle("AM2:AM101")->getNumberFormat()->setFormatCode("yyyy-mm-dd");
$sheet->getStyle("AR2:AR101")->getNumberFormat()->setFormatCode("yyyy-mm-dd");

foreach (["G","H","I","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL"] as $col) {
    $validation = $sheet->getCell($col . "2")->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validation->setFormula1('"Yes,No,1,0"');
    $validation->setAllowBlank(true);
    $validation->setShowDropDown(false);
    $sheet->setDataValidation($col . "2:" . $col . "101", $validation);
}

$sheet->freezePane("A2");
$sheet->getStyle("A2:BO101")->applyFromArray([
    "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN]]
]);

$example = [
    "A" => 2026,
    "B" => "2026-01-15",
    "C" => "Example Company",
    "D" => "TIN-XXXXX",
    "E" => "Vientiane Capital",
    "F" => "Chanthabuly",
    "G" => "Yes",
    "J" => "Manufacturing",
    "K" => 1000000000,
    "L" => 800000000,
    "M" => 200000000,
    "O" => 20000000,
    "S" => 0,
    "T" => "2025-01-01",
    "AO" => 10,
    "AP" => 1,
    "AQ" => 50,
    "BO" => 0
];
foreach ($example as $col => $value) {
    $sheet->getCell($col . "2")->setValue($value);
}
$sheet->getStyle("A2:BO2")->applyFromArray([
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]]
]);

$instructions = $spreadsheet->createSheet();
$instructions->setTitle("Instructions");
$instructions->getCell("A1")->setValue("CIT Import Template - Instructions");
$instructions->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$instructions->getColumnDimension("A")->setWidth(90);

$lines = [
    "",
    "Use this template with Import > CIT Data Import.",
    "The importer reads fixed columns, so keep the column letters unchanged.",
    "Rows without TIN in column D are skipped.",
    "Tax Year can be set in column A; if blank, the import page Tax Year is used.",
    "Yes/No or 1/0 can be used for flag columns.",
    "Total Assets and Annual Turnover are entered in billion LAK; the importer converts them to LAK.",
    "Expert TE is in column BO and is shown only on calculation pages for the admin user."
];
foreach ($lines as $i => $line) {
    $instructions->getCell("A" . ($i + 2))->setValue($line);
}

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"cit_template.xlsx\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
