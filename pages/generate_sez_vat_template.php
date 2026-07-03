<?php
/**
 * Generate SEZ VAT Import Template (.xlsx)
 * Expert-confirmed SEZ-VAT-template-apis v1.0
 * 20 columns A-T, 5 sheets (Validation Lists & Data Dictionary hidden via veryHidden)
 * Combined template for both SEZ Developers and SEZ Investors
 *
 * Column mapping:
 *   A: Tax Year               (required)
 *   B: TIN                    (required)
 *   C: Company Name           (optional)
 *   D: License Date           (required)
 *   E: Province               (required) [dropdown]
 *   F: District               (required) [dropdown]
 *   G: Village                (optional)
 *   H: SEZ name               (optional)
 *   I: SEZ Developer          (optional) [Yes/No flag]
 *   J: SEZ Investor           (optional) [Yes/No flag]
 *   K: Sector                 (optional)
 *   L: Basic Infrastructure LAK  (conditional) [numeric]
 *   M: Other Infrastructure LAK  (conditional) [numeric]
 *   N: Utility Usage LAK         (conditional) [numeric]
 *   O: Support Infrastructure LAK (conditional) [numeric]
 *   P: Use User Fallback?        (fallback) [Yes/No]
 *   Q: User Benchmark Rate       (fallback)
 *   R: User TE                   (fallback)
 *   S: User Fallback Reason      (fallback)
 *   T: User Comment              (fallback)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Font, Alignment, Border, NumberFormat, Protection};
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$pdo = getDbConnection();

// Fetch data
$provinces = $pdo->query("SELECT province_code, province_name FROM provinces ORDER BY province_code")->fetchAll();
$districts = $pdo->query("SELECT d.district_code, d.district_name, p.province_code FROM districts d LEFT JOIN provinces p ON d.province_id = p.id ORDER BY d.province_id, d.district_code")->fetchAll();

$spreadsheet = new Spreadsheet();

// Color helpers
define('CLR_REQUIRED',  '1F4E79');
define('CLR_OPTIONAL',  '64748B');
define('CLR_COND',      '5B9BD5');
define('CLR_FALLBACK',  'C65911');
define('CLR_INPUT_REQ', 'D6E0F0');
define('CLR_INPUT_OPT', 'FFFFFF');
define('CLR_INPUT_COND','F4F9FC');
define('CLR_INPUT_FB',  'FCE4D6');
define('CLR_INSTR_HDR', '1F4E79');
define('CLR_INSTR_BODY','F2F2F2');

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
// SHEET 1: SEZ VAT (Main Import)
// ============================================================
$ws = $spreadsheet->setActiveSheetIndex(0);
$ws->setTitle('SEZ VAT');
$ws->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_LAYOUT);

$columns = [
    ['A', 'Tax Year',                'required', 14],
    ['B', 'TIN',                     'required', 20],
    ['C', 'Company Name',            'optional', 35],
    ['D', 'License Date',            'required', 18],
    ['E', 'Province',                'required', 30],
    ['F', 'District',                'required', 30],
    ['G', 'Village',                 'optional', 25],
    ['H', 'SEZ name',                'optional', 25],
    ['I', 'SEZ Developer',           'optional', 16],
    ['J', 'SEZ Investor',            'optional', 16],
    ['K', 'Sector',                  'optional', 22],
    ['L', 'Basic Infrastructure LAK',  'cond',    25],
    ['M', 'Other Infrastructure LAK',  'cond',    25],
    ['N', 'Utility Usage LAK',         'cond',    25],
    ['O', 'Support Infrastructure LAK','cond',    25],
    ['P', 'Use User Fallback?',        'fallback', 22],
    ['Q', 'User Benchmark Rate',       'fallback', 22],
    ['R', 'User TE',                   'fallback', 20],
    ['S', 'User Fallback Reason',      'fallback', 28],
    ['T', 'User Comment',              'fallback', 30],
];

$colorMap = [
    'required' => ['header' => CLR_REQUIRED,  'body' => CLR_INPUT_REQ],
    'optional' => ['header' => CLR_OPTIONAL,  'body' => CLR_INPUT_OPT],
    'cond'     => ['header' => CLR_COND,      'body' => CLR_INPUT_COND],
    'fallback' => ['header' => CLR_FALLBACK,   'body' => CLR_INPUT_FB],
];

// Set column widths
foreach ($columns as [$col, $header, $group, $width]) {
    $ws->getColumnDimension($col)->setWidth($width);
}

// Row 1: Headers
foreach ($columns as $i => [$col, $header, $group, $width]) {
    $cell = $col . '1';
    $ws->setCellValue($cell, $header);
    styleHeader($ws, $cell, $colorMap[$group]['header']);
}

// Row 2: Example data
$ws->setCellValue('A2', 2026);
$ws->setCellValue('B2', '123456789-000');
$ws->setCellValue('C2', 'SEZ Developer Co., Ltd.');
$ws->setCellValue('D2', date('Y-m-d'));
$ws->setCellValue('E2', '01 | Vientiane Capital');
$ws->setCellValue('F2', '0101 | Chanthabuly');
$ws->setCellValue('G2', '');
$ws->setCellValue('H2', 'Saysettha Development Zone');
$ws->setCellValue('I2', 'Yes');
$ws->setCellValue('J2', 'No');
$ws->setCellValue('K2', 'Industrial & Manufacturing');
$ws->setCellValue('L2', 100000000);
$ws->setCellValue('M2', 50000000);
$ws->setCellValue('N2', '');
$ws->setCellValue('O2', '');
$ws->setCellValue('P2', 'No');
$ws->setCellValue('Q2', '');
$ws->setCellValue('R2', '');
$ws->setCellValue('S2', '');
$ws->setCellValue('T2', 'Example record');

// Style body rows
$lastBodyRow = 1001;
foreach ($columns as $i => [$col, $header, $group, $width]) {
    $range = $col . '2:' . $col . $lastBodyRow;
    styleBody($ws, $range, $colorMap[$group]['body']);
}

// Number format for amounts
foreach (['L', 'M', 'N', 'O', 'Q', 'R'] as $col) {
    $ws->getStyle($col . '2:' . $col . $lastBodyRow)
        ->getNumberFormat()->setFormatCode('#,##0');
    $ws->getStyle($col . '2:' . $col . $lastBodyRow)
        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

// ============================================================
// Data Validations
// ============================================================
// Province dropdown
if ($provinces) {
    $provItems = [];
    foreach ($provinces as $p) {
        $provItems[] = $p['province_code'] . ' | ' . $p['province_name'];
    }
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setFormula1("'Validation Lists'!\$A\$2:\$A\$" . (count($provinces) + 1));
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation('E2:E' . $lastBodyRow, $dv);
}

// District dropdown
if ($districts) {
    $distItems = [];
    foreach ($districts as $d) {
        $distItems[] = $d['district_code'] . ' | ' . $d['district_name'];
    }
    $distItems = array_unique($distItems);
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setFormula1("'Validation Lists'!\$E\$2:\$E\$" . (count($distItems) + 1));
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation('F2:F' . $lastBodyRow, $dv);
}

// Yes/No for SEZ Developer (I), SEZ Investor (J), Use User Fallback? (P)
foreach (['I', 'J', 'P'] as $col) {
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setFormula1('"Yes,No"');
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $ws->setDataValidation($col . '2:' . $col . $lastBodyRow, $dv);
}

// Numeric validation (L,M,N,O,Q,R)
$dv = new DataValidation();
$dv->setType(DataValidation::TYPE_DECIMAL);
$dv->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
$dv->setFormula1('0');
$dv->setAllowBlank(true);
$ws->setDataValidation('L2:O' . $lastBodyRow . ' Q2:R' . $lastBodyRow, $dv);

// ============================================================
// SHEET 2: Instructions
// ============================================================
$ws2 = $spreadsheet->createSheet();
$ws2->setTitle('Instructions');
$ws2->getProtection()->setPassword('TaxETS2026');
$ws2->getProtection()->setSheet(true);

$instructions = [
    ['SEZ VAT Import Template - Instructions'],
    [''],
    ['Information Type', 'Color / Style', 'Meaning', 'Examples / Notes'],
    ['Primary Required', 'Dark blue header, light blue input cells', 'Required for SEZ VAT TE calculation and reporting.', 'TIN, Tax Year, License Date, Province, District'],
    ['Primary Conditional', 'Medium blue header, near-white input cells', 'Used only when the record type (Developer/Investor) matches.', 'Basic Infrastructure (Developer), Other Infrastructure (Developer), Utility Usage (Investor), Support Infrastructure (Investor)'],
    ['Primary Optional', 'Slate/gray-blue header, white input cells', 'Supporting info useful for audit / reference.', 'Company Name, Village, SEZ name, Sector, SEZ Developer, SEZ Investor'],
    ['User Fallback', 'Orange header, light amber input cells', 'User override values; used only when system cannot calculate normally.', 'Use User Fallback?, User Benchmark Rate, User TE, User Fallback Reason, User Comment'],
    [''],
    ['Column Ref', 'Short Name', 'Full Description'],
    ['L', 'Basic Infrastructure LAK', 'Amount of construction of road, electricity system, water supply, wastewater treatment and waste disposal system (LAK)'],
    ['M', 'Other Infrastructure LAK', 'Amount of construction of any other infrastructure apart from Column L (LAK)'],
    ['N', 'Utility Usage LAK', 'Amount of the use of electricity and water in production (LAK)'],
    ['O', 'Support Infrastructure LAK', 'Amount of the construction and development of infrastructures to support business operations of investors in sectors that are not 100% production for export (LAK)'],
    [''],
    ['Rule', 'Description'],
    ['Template version', 'SEZ_VAT_IMPORT v1.0 - Merged Developer & Investor template'],
    ['Developer / Investor flags', 'Col I (SEZ Developer) = Yes for developer rows, Col J (SEZ Investor) = Yes for investor rows. A row can be both, either, or neither.'],
    ['Dropdown fields', 'Province, District, SEZ Developer (Yes/No), SEZ Investor (Yes/No), Use User Fallback? (Yes/No)'],
    ['Mandatory fields', 'TIN, Tax Year, License Date, Province, District, and at least one infrastructure amount based on type (Developer: L or M; Investor: N or O)'],
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

$ws3->getColumnDimension('A')->setWidth(25);
$ws3->getColumnDimension('B')->setWidth(15);
$ws3->getColumnDimension('C')->setWidth(25);
$ws3->getColumnDimension('E')->setWidth(25);
$ws3->getColumnDimension('F')->setWidth(15);
$ws3->getColumnDimension('G')->setWidth(25);
$ws3->getColumnDimension('H')->setWidth(20);
$ws3->getColumnDimension('J')->setWidth(10);

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
$ws5->setCellValue('D2', 'Expert-confirmed SEZ VAT import template v1.0 - 20 columns A-T (combined Developer & Investor)');

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
// Hide Validation Lists and Data Dictionary (veryHidden)
// ============================================================
$ws3->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
$ws4->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

// Protect Validation Lists sheet
$ws3->getProtection()->setPassword('TaxETS2026');
$ws3->getProtection()->setSheet(true);

// ============================================================
// Output
// ============================================================
$spreadsheet->setActiveSheetIndex(0);

if (PHP_SAPI === 'cli') {
    $outputDir = __DIR__ . '/../tests';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    $filename = $outputDir . '/SEZ_VAT_Template.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "Template saved: $filename\n";
    exit(0);
}

$filename = 'SEZ_VAT_Template.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
