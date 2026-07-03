<?php
/**
 * Generate Land Concession Import Template (.xlsx)
 * Expert-confirmed Land Concession-template-apis v1.0
 * 19 columns A-S, 5 sheets (Validation Lists & Data Dictionary hidden via veryHidden)
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

$provinces = $pdo->query("SELECT province_code, province_name FROM provinces ORDER BY province_code")->fetchAll();
$districts = $pdo->query("SELECT d.district_code, d.district_name, p.province_code FROM districts d LEFT JOIN provinces p ON d.province_id = p.id ORDER BY d.province_id, d.district_code")->fetchAll();
$provisions = $pdo->query("SELECT * FROM land_concession_provisions")->fetchAll();
$bmLand = $pdo->query("SELECT article_no, article_name, item_no, item_name FROM bm_land_concession WHERE active = 1 ORDER BY article_no, item_no")->fetchAll();

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
// SHEET 1: Land Concession Import
// ============================================================
$ws = $spreadsheet->setActiveSheetIndex(0);
$ws->setTitle('Land Concession Import');
$ws->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_LAYOUT);

$columns = [
    ['A', 'TIN',                    'required', 20],
    ['B', 'CompanyName',            'optional', 35],
    ['C', 'Province',               'required', 30],
    ['D', 'District',               'required', 30],
    ['E', 'Description',            'optional', 45],
    ['F', 'Year',                   'required', 10],
    ['G', 'Receiptdate',            'required', 16],
    ['H', 'Concessionarea (ha)',    'required', 20],
    ['I', 'BenchmarkRate (USD)',    'required', 22],
    ['J', 'ContractedRate (USD)',   'required', 22],
    ['K', 'ConcessionFeePaid',      'required', 22],
    ['L', 'Paid Currency',          'optional', 16],
    ['M', 'Exchange Rate',          'optional', 16],
    ['N', 'Use User Fallback?',     'fallback', 22],
    ['O', 'User Benchmark Rate',    'fallback', 22],
    ['P', 'User Benchmark Value',   'fallback', 22],
    ['Q', 'User Non-Tax TE',        'fallback', 22],
    ['R', 'User Fallback Reason',   'fallback', 30],
    ['S', 'User Comment',           'fallback', 30],
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
$ws->setCellValue('A2', '086535834-000');
$ws->setCellValue('B2', 'Example Company Co., Ltd.');
$ws->setCellValue('C2', '01 | Vientiane Capital');
$ws->setCellValue('D2', '0101 | Chanthabuly');
$ws->setCellValue('E2', 'Article 6/1 | Pharmaceutical manufacturing plants');
$ws->setCellValue('F2', 2026);
$ws->setCellValue('G2', date('Y-m-d'));
$ws->setCellValue('H2', 100);
$ws->setCellValue('I2', 100);
$ws->setCellValue('J2', 100);
$ws->setCellValue('K2', 10000);
$ws->setCellValue('L2', 'USD');
$ws->setCellValue('M2', 1);
$ws->setCellValue('N2', 'No');
$ws->setCellValue('O2', '');
$ws->setCellValue('P2', '');
$ws->setCellValue('Q2', '');
$ws->setCellValue('R2', '');
$ws->setCellValue('S2', 'Example record');

$lastBodyRow = 1001;
foreach ($columns as [$col, $header, $group, $width]) {
    styleBody($ws, $col . '2:' . $col . $lastBodyRow, $colorMap[$group]['body']);
}

foreach (['H', 'I', 'J', 'K', 'M', 'O', 'P', 'Q'] as $col) {
    $ws->getStyle($col . '2:' . $col . $lastBodyRow)->getNumberFormat()->setFormatCode('#,##0');
    $ws->getStyle($col . '2:' . $col . $lastBodyRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

// ============================================================
// Data Validations
// ============================================================
// Province dropdown (C)
if ($provinces) {
    $provEndRow = count($provinces) + 1;
    $items = [];
    foreach ($provinces as $p) {
        $items[] = $p['province_code'] . ' | ' . $p['province_name'];
    }
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setFormula1("'Validation Lists'!\$A\$2:\$A\$" . $provEndRow);
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation('C2:C' . $lastBodyRow, $dv);
}

// Cascading District dropdown (D) — single range with relative row reference
if ($districts) {
    $distEndRow = count($districts) + 1;
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    // Using $C2 (relative row) so Excel adjusts per row in the range D2:D1001
    $dv->setFormula1("OFFSET('Validation Lists'!\$E\$2,MATCH(LEFT(\$C2,2),'Validation Lists'!\$H\$2:\$H\$" . $distEndRow . ",0)-1,0,COUNTIF('Validation Lists'!\$H\$2:\$H\$" . $distEndRow . ",LEFT(\$C2,2)),1)");
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation('D2:D' . $lastBodyRow, $dv);
}

// Description dropdown (E) — from bm_land_concession
if ($bmLand) {
    $descItems = [];
    foreach ($bmLand as $b) {
        $descItems[] = $b['article_no'] . ' / ' . $b['item_no'] . ' | ' . $b['item_name'];
    }
    $descEndRow = count($descItems) + 1;
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setFormula1("'Validation Lists'!\$K\$2:\$K\$" . $descEndRow);
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation('E2:E' . $lastBodyRow, $dv);
}
// Yes/No for Use User Fallback?
$dv = new DataValidation();
$dv->setType(DataValidation::TYPE_LIST);
$dv->setFormula1('"Yes,No"');
$dv->setAllowBlank(true);
$dv->setShowDropDown(true);
$ws->setDataValidation('N2:N' . $lastBodyRow, $dv);

// Numeric validation (H,K,M,O,P,Q)
$dv = new DataValidation();
$dv->setType(DataValidation::TYPE_DECIMAL);
$dv->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
$dv->setFormula1('0');
$dv->setAllowBlank(true);
$ws->setDataValidation('H2:K' . $lastBodyRow . ' M2:M' . $lastBodyRow . ' O2:Q' . $lastBodyRow, $dv);

// ============================================================
// SHEET 2: Instructions
// ============================================================
$ws2 = $spreadsheet->createSheet();
$ws2->setTitle('Instructions');
$ws2->getProtection()->setPassword('TaxETS2026');
$ws2->getProtection()->setSheet(true);

$instructions = [
    ['Land Concession Import Template - Instructions'],
    [''],
    ['Information Type', 'Color / Style', 'Meaning', 'Examples / Notes'],
    ['Primary Required', 'Dark blue header, light blue input cells', 'Required for Land Concession TE calculation.', 'TIN, District, Province, Year, Receiptdate, Concession Area, Benchmark/Contracted Rate, Fee Paid'],
    ['Primary Optional', 'Slate/gray-blue header, white input cells', 'Supporting info useful for audit and reference.', 'CompanyName, Description, Paid Currency, Exchange Rate'],
    ['User Fallback', 'Orange header, light amber input cells', 'User override values; used only when system cannot calculate normally.', 'Use User Fallback?, User Benchmark Rate, User Benchmark Value, User Non-Tax TE, User Fallback Reason, User Comment'],
    [''],
    ['Column Ref', 'Short Name', 'Full Description'],
    ['C', 'Province', 'Province of the concession (select from dropdown)'],
    ['D', 'District', 'District within selected province (cascading dropdown, filters by province)'],
    ['E', 'Description', 'Description of the concession type (e.g., Article No. / Item No. | Name)'],
    ['H', 'Concessionarea (ha)', 'Total concession area in hectares'],
    ['I', 'BenchmarkRate (USD)', 'Benchmark rate in USD per hectare'],
    ['J', 'ContractedRate (USD)', 'Contracted rate in USD per hectare'],
    ['K', 'ConcessionFeePaid', 'Total concession fee paid (in original currency)'],
    ['L', 'Paid Currency', 'Currency code for the paid fee (e.g., USD, THB, LAK)'],
    ['M', 'Exchange Rate', 'Exchange rate to convert paid fee to USD'],
    [''],
    ['Rule', 'Description'],
    ['Template version', 'LAND_CONCESSION_IMPORT v1.0 - Expert-confirmed'],
    ['Dropdown fields', 'Province, District, Use User Fallback? (Yes/No)'],
    ['Mandatory fields', 'TIN, District, Province, Year, Receiptdate, Concession Area, Benchmark Rate, Contracted Rate, Concession Fee Paid'],
    ['Currency conversion', 'If ConcessionFeePaid is in a currency other than USD, enter Paid Currency (L) and Exchange Rate (M) to convert to USD.'],
    ['Protection password', 'TaxETS2026 - used only to prevent accidental edits to read-only sheets.'],
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

$ws3->setCellValue('A1', 'Province Item');
$ws3->setCellValue('B1', 'Province Code');
$ws3->setCellValue('C1', 'Province Name');
$ws3->setCellValue('E1', 'District Item');
$ws3->setCellValue('F1', 'District Code');
$ws3->setCellValue('G1', 'District Name');
$ws3->setCellValue('H1', 'Province Code (parent)');
$ws3->setCellValue('J1', 'Yes/No');
$ws3->setCellValue('K1', 'Description Item');

$r = 2;
foreach ($provinces as $p) {
    $ws3->setCellValue('A' . $r, $p['province_code'] . ' | ' . $p['province_name']);
    $ws3->setCellValue('B' . $r, $p['province_code']);
    $ws3->setCellValue('C' . $r, $p['province_name']);
    $r++;
}

$rDist = 2;
foreach ($districts as $d) {
    $ws3->setCellValue('E' . $rDist, $d['district_code'] . ' | ' . $d['district_name']);
    $ws3->setCellValue('F' . $rDist, $d['district_code']);
    $ws3->setCellValue('G' . $rDist, $d['district_name']);
    $ws3->setCellValue('H' . $rDist, $d['province_code']);
    $rDist++;
}

$ws3->setCellValue('J2', 'Yes');
$ws3->setCellValue('J3', 'No');

$rDesc = 2;
if ($bmLand) {
    foreach ($bmLand as $b) {
        $ws3->setCellValue('K' . $rDesc, $b['article_no'] . ' / ' . $b['item_no'] . ' | ' . $b['item_name']);
        $rDesc++;
    }
}

$ws3->getColumnDimension('A')->setWidth(25);
$ws3->getColumnDimension('B')->setWidth(15);
$ws3->getColumnDimension('C')->setWidth(25);
$ws3->getColumnDimension('E')->setWidth(25);
$ws3->getColumnDimension('F')->setWidth(15);
$ws3->getColumnDimension('G')->setWidth(25);
$ws3->getColumnDimension('H')->setWidth(20);
$ws3->getColumnDimension('J')->setWidth(10);
$ws3->getColumnDimension('K')->setWidth(80);

// ============================================================
// SHEET 4: Data Dictionary
// ============================================================
$ws4 = $spreadsheet->createSheet();
$ws4->setTitle('Data Dictionary');

$ws4->setCellValue('A1', 'Province Code');
$ws4->setCellValue('B1', 'Province Name');
$ws4->setCellValue('C1', 'Dropdown Value');
$r = 2;
foreach ($provinces as $p) {
    $ws4->setCellValue('A' . $r, $p['province_code']);
    $ws4->setCellValue('B' . $r, $p['province_name']);
    $ws4->setCellValue('C' . $r, $p['province_code'] . ' | ' . $p['province_name']);
    $r++;
}

$ws4->getColumnDimension('A')->setWidth(15);
$ws4->getColumnDimension('B')->setWidth(25);
$ws4->getColumnDimension('C')->setWidth(30);

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
$ws5->setCellValue('D2', 'Expert-confirmed Land Concession import template v1.0 - 19 columns A-S');

$ws5->getColumnDimension('A')->setWidth(12);
$ws5->getColumnDimension('B')->setWidth(15);
$ws5->getColumnDimension('C')->setWidth(30);
$ws5->getColumnDimension('D')->setWidth(70);

// ============================================================
// Freeze panes
// ============================================================
$ws->freezePane('A3');
$ws2->freezePane('A3');
$ws3->freezePane('A2');
$ws4->freezePane('A2');
$ws5->freezePane('A2');

// ============================================================
// Hide Validation Lists and Data Dictionary
// ============================================================
$ws3->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$ws4->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$ws3->getProtection()->setPassword('TaxETS2026');
$ws3->getProtection()->setSheet(true);

// ============================================================
// Output
// ============================================================
$spreadsheet->setActiveSheetIndex(0);

if (PHP_SAPI === 'cli') {
    $outputDir = __DIR__ . '/../tests';
    if (!is_dir($outputDir)) { mkdir($outputDir, 0777, true); }
    $filename = $outputDir . '/Land_Concession_Template.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "Template saved: $filename\n";
    exit(0);
}

$filename = 'Land_Concession_Template.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
