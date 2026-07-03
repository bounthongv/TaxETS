<?php
/**
 * Generate Resource Fee Import Template (.xlsx)
 * Expert-confirmed Resource Fee-template-apis v1.0
 * 17 columns A-Q, 5 sheets (Validation Lists & Data Dictionary hidden via veryHidden)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Font, Alignment, Border};
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$pdo = getDbConnection();
$resourceTypes = $pdo->query("SELECT item_no, item_name, rate_percentage FROM bm_natural_resource WHERE active = 1 ORDER BY item_no")->fetchAll();

$spreadsheet = new Spreadsheet();

define('CLR_REQUIRED',  '1F4E79');
define('CLR_OPTIONAL',  '64748B');
define('CLR_FALLBACK',  'C65911');
define('CLR_INPUT_REQ', 'D6E0F0');
define('CLR_INPUT_OPT', 'FFFFFF');
define('CLR_INPUT_FB',  'FCE4D6');

function styleHeader(Worksheet $ws, string $cell, string $color, string $fontColor = 'FFFFFF'): void {
    $ws->getStyle($cell)->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
        'font' => ['bold' => true, 'color' => ['rgb' => $fontColor], 'size' => 10],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]],
    ]);
}

function styleBody(Worksheet $ws, string $range, string $color): void {
    $ws->getStyle($range)->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
        'font' => ['size' => 10],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
}

// ============================================================
// SHEET 1: Resource Fee Import
// ============================================================
$ws = $spreadsheet->setActiveSheetIndex(0);
$ws->setTitle('Resource Fee Import');
$ws->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_LAYOUT);

$columns = [
    ['A', 'TIN',                        'required', 20],
    ['B', 'Date_Investment_License',     'required', 20],
    ['C', 'Type_of_natural_resource',    'required', 45],
    ['D', 'Year',                        'required', 10],
    ['E', 'Reciept Date',                'required', 16],
    ['F', 'Resource_fee_rate (Benchmark)','required', 24],
    ['G', 'Resource_fee_rate (Contracted)','required', 24],
    ['H', 'Sale quantity (Tons)',        'required', 20],
    ['I', 'Resource_fee_collected',      'required', 22],
    ['J', 'Paid currency',               'optional', 16],
    ['K', 'Exchange rate',               'optional', 16],
    ['L', 'Use User Fallback?',          'fallback', 22],
    ['M', 'User Benchmark Rate',         'fallback', 22],
    ['N', 'User Benchmark Fee',          'fallback', 22],
    ['O', 'User TE',                     'fallback', 22],
    ['P', 'User Fallback Reason',         'fallback', 30],
    ['Q', 'User Comment',                'fallback', 30],
];

$colorMap = [
    'required' => ['header' => CLR_REQUIRED,  'body' => CLR_INPUT_REQ],
    'optional' => ['header' => CLR_OPTIONAL,  'body' => CLR_INPUT_OPT],
    'fallback' => ['header' => CLR_FALLBACK,   'body' => CLR_INPUT_FB],
];

foreach ($columns as [$col, $header, $group, $width]) {
    $ws->getColumnDimension($col)->setWidth($width);
}

foreach ($columns as [$col, $header, $group, $width]) {
    $cell = $col . '1';
    $ws->setCellValue($cell, $header);
    styleHeader($ws, $cell, $colorMap[$group]['header']);
}

// Row 2: Example data
$ws->setCellValue('A2', '123456789-000');
$ws->setCellValue('B2', '2026-01-01');
$ws->setCellValue('C2', '1 | Gemstones');
$ws->setCellValue('D2', 2026);
$ws->setCellValue('E2', date('Y-m-d'));
$ws->setCellValue('F2', 10);
$ws->setCellValue('G2', 10);
$ws->setCellValue('H2', 1000);
$ws->setCellValue('I2', 50000000);
$ws->setCellValue('J2', 'USD');
$ws->setCellValue('K2', 1);
$ws->setCellValue('L2', 'No');
$ws->setCellValue('M2', '');
$ws->setCellValue('N2', '');
$ws->setCellValue('O2', '');
$ws->setCellValue('P2', '');
$ws->setCellValue('Q2', '');

$lastBodyRow = 1001;
foreach ($columns as [$col, $header, $group, $width]) {
    styleBody($ws, $col . '2:' . $col . $lastBodyRow, $colorMap[$group]['body']);
}

foreach (['F', 'G', 'H', 'I', 'K', 'M', 'N', 'O'] as $col) {
    $ws->getStyle($col . '2:' . $col . $lastBodyRow)->getNumberFormat()->setFormatCode('#,##0');
    $ws->getStyle($col . '2:' . $col . $lastBodyRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

// ============================================================
// Data Validations
// ============================================================
// Resource type dropdown (C)
if ($resourceTypes) {
    $rTypes = [];
    foreach ($resourceTypes as $rt) {
        $rTypes[] = $rt['item_no'] . ' | ' . $rt['item_name'];
    }
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setFormula1("'Validation Lists'!\$A\$2:\$A\$" . (count($resourceTypes) + 1));
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation('C2:C' . $lastBodyRow, $dv);
}

// Yes/No for Use User Fallback?
$dv = new DataValidation();
$dv->setType(DataValidation::TYPE_LIST);
$dv->setFormula1('"Yes,No"');
$dv->setAllowBlank(true);
$dv->setShowDropDown(true);
$ws->setDataValidation('L2:L' . $lastBodyRow, $dv);

// Numeric validation (F, G, H, I, K, M, N, O)
$dv = new DataValidation();
$dv->setType(DataValidation::TYPE_DECIMAL);
$dv->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
$dv->setFormula1('0');
$dv->setAllowBlank(true);
$ws->setDataValidation('F2:I' . $lastBodyRow . ' K2:K' . $lastBodyRow . ' M2:O' . $lastBodyRow, $dv);

// ============================================================
// SHEET 2: Instructions
// ============================================================
$ws2 = $spreadsheet->createSheet();
$ws2->setTitle('Instructions');
$ws2->getProtection()->setPassword('TaxETS2026');
$ws2->getProtection()->setSheet(true);

$instructions = [
    ['Resource Fee Import Template - Instructions'],
    [''],
    ['Information Type', 'Color / Style', 'Meaning', 'Examples / Notes'],
    ['Primary Required', 'Dark blue header, light blue input cells', 'Required for Resource Fee TE calculation.', 'TIN, License Date, Resource Type, Year, Reciept Date, Benchmark/Contracted Rates, Sale Qty, Fee Collected'],
    ['Primary Optional', 'Slate/gray-blue header, white input cells', 'Supporting info for audit / reference.', 'Paid Currency, Exchange Rate'],
    ['User Fallback', 'Orange header, light amber input cells', 'User override values; used only when system cannot calculate normally.', 'Use User Fallback?, User Benchmark Rate, User Benchmark Fee, User TE, User Fallback Reason, User Comment'],
    [''],
    ['Column Ref', 'Short Name', 'Full Description'],
    ['C', 'Type_of_natural_resource', 'Type of natural resource (select from dropdown)'],
    ['E', 'Reciept Date', 'Date of the receipt/payment'],
    ['F', 'Resource_fee_rate (Benchmark)', 'Benchmark resource fee rate'],
    ['G', 'Resource_fee_rate (Contracted)', 'Contracted resource fee rate'],
    ['H', 'Sale quantity (Tons)', 'Quantity of natural resource sold (in metric tons)'],
    ['I', 'Resource_fee_collected', 'Total resource fee actually collected'],
    ['J', 'Paid currency', 'Currency code for the fee (e.g., USD, THB, LAK)'],
    ['K', 'Exchange rate', 'Exchange rate to convert to USD'],
    [''],
    ['Rule', 'Description'],
    ['Template version', 'RESOURCE_FEE_IMPORT v1.0 - Expert-confirmed'],
    ['Dropdown fields', 'Type_of_natural_resource, Use User Fallback? (Yes/No)'],
    ['Mandatory fields', 'TIN, License Date, Resource Type, Year, Reciept Date, Rates, Sale Quantity, Fee Collected'],
    ['Protection password', 'TaxETS2026'],
];

$ws2->fromArray($instructions, null, 'A1', true);
$ws2->getColumnDimension('A')->setWidth(22);
$ws2->getColumnDimension('B')->setWidth(50);
$ws2->getColumnDimension('C')->setWidth(55);
$ws2->getColumnDimension('D')->setWidth(50);

// ============================================================
// SHEET 3: Validation Lists
// ============================================================
$ws3 = $spreadsheet->createSheet();
$ws3->setTitle('Validation Lists');

$ws3->setCellValue('A1', 'Resource Type Item');
$ws3->setCellValue('B1', 'Item No');
$ws3->setCellValue('C1', 'Item Name');
$ws3->setCellValue('D1', 'Benchmark Rate %');
$ws3->setCellValue('F1', 'Yes/No');

$r = 2;
foreach ($resourceTypes as $rt) {
    $ws3->setCellValue('A' . $r, $rt['item_no'] . ' | ' . $rt['item_name']);
    $ws3->setCellValue('B' . $r, $rt['item_no']);
    $ws3->setCellValue('C' . $r, $rt['item_name']);
    $ws3->setCellValue('D' . $r, $rt['rate_percentage']);
    $r++;
}

$ws3->setCellValue('F2', 'Yes');
$ws3->setCellValue('F3', 'No');

$ws3->getColumnDimension('A')->setWidth(60);
$ws3->getColumnDimension('B')->setWidth(12);
$ws3->getColumnDimension('C')->setWidth(60);
$ws3->getColumnDimension('D')->setWidth(18);
$ws3->getColumnDimension('F')->setWidth(10);

// ============================================================
// SHEET 4: Data Dictionary
// ============================================================
$ws4 = $spreadsheet->createSheet();
$ws4->setTitle('Data Dictionary');
$ws4->setCellValue('A1', 'Item No');
$ws4->setCellValue('B1', 'Item Name');
$ws4->setCellValue('C1', 'Rate %');
$r = 2;
foreach ($resourceTypes as $rt) {
    $ws4->setCellValue('A' . $r, $rt['item_no']);
    $ws4->setCellValue('B' . $r, $rt['item_name']);
    $ws4->setCellValue('C' . $r, $rt['rate_percentage']);
    $r++;
}
$ws4->getColumnDimension('A')->setWidth(12);
$ws4->getColumnDimension('B')->setWidth(60);
$ws4->getColumnDimension('C')->setWidth(12);

// ============================================================
// SHEET 5: Change Log
// ============================================================
$ws5 = $spreadsheet->createSheet();
$ws5->setTitle('Change Log');
$ws5->setCellValue('A1', 'Version');
$ws5->setCellValue('B1', 'Date');
$ws5->setCellValue('C1', 'Owner');
$ws5->setCellValue('D1', 'Change');
$ws5->setCellValue('A2', '1.0');
$ws5->setCellValue('B2', date('Y-m-d'));
$ws5->setCellValue('C2', 'APIS / Tax-ETS');
$ws5->setCellValue('D2', 'Expert-confirmed Resource Fee import template v1.0 - 17 columns A-Q');

$ws5->getColumnDimension('A')->setWidth(12);
$ws5->getColumnDimension('B')->setWidth(15);
$ws5->getColumnDimension('C')->setWidth(30);
$ws5->getColumnDimension('D')->setWidth(70);

// Freeze panes
$ws->freezePane('A3');
$ws2->freezePane('A3');
$ws3->freezePane('A2');
$ws4->freezePane('A2');
$ws5->freezePane('A2');

// Hide Validation Lists and Data Dictionary
$ws3->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$ws4->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$ws3->getProtection()->setPassword('TaxETS2026');
$ws3->getProtection()->setSheet(true);

// Output
$spreadsheet->setActiveSheetIndex(0);

if (PHP_SAPI === 'cli') {
    $outputDir = __DIR__ . '/../tests';
    if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);
    $filename = $outputDir . '/Resource_Fee_Template.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "Template saved: $filename\n";
    exit(0);
}

$filename = 'Resource_Fee_Template.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
