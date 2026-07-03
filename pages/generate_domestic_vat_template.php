<?php
/**
 * Generate VAT Import Template (.xlsx)
 * Expert-confirmed Domestic VAT-template-apis v1.0
 * 17 columns A-Q, 5 sheets
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Font, Alignment, Border, NumberFormat, Protection};
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$pdo = getDbConnection();

// Fetch data
$provinces = $pdo->query("SELECT province_code, province_name FROM provinces ORDER BY province_code")->fetchAll();
$provisions = $pdo->query("SELECT provision_number, legal_basis, description, type_of_te FROM vat_provisions ORDER BY provision_number")->fetchAll();
$bmRates = $pdo->query("SELECT start_date, end_date, rate_percentage FROM bm_vat ORDER BY start_date")->fetchAll();

$spreadsheet = new Spreadsheet();

// ============================================================
// HELPER: color groups (matching expert template)
// ============================================================
define('CLR_PRIMARY_REQUIRED', '1F4E79');   // dark blue header
define('CLR_PRIMARY_OPTIONAL', '64748B');     // slate header
define('CLR_FALLBACK',        'C65911');      // orange header
define('CLR_INPUT_DATA',      'D6E0F0');      // light blue input
define('CLR_INPUT_FALLBACK',  'FCE4D6');      // light amber input
define('CLR_WHITE',           'FFFFFF');      // white
define('CLR_INSTR_HEADER',    '1F4E79');
define('CLR_INSTR_BODY',      'F2F2F2');

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
// SHEET 1: VAT Import
// ============================================================
$ws = $spreadsheet->setActiveSheetIndex(0);
$ws->setTitle('Domestic VAT Import');
$ws->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_PAGE_LAYOUT);

// Column definitions: [col, header, group, width]
$columns = [
    ['A', 'Province',               'required', 30],
    ['B', 'TIN',                    'required', 20],
    ['C', 'Company Name',           'optional', 35],
    ['D', 'Filing Type',            'optional', 18],
    ['E', 'Filing Period',          'required', 18],
    ['F', 'Input Date',             'optional', 18],
    ['G', 'Description',            'optional', 35],
    ['H', 'VAT Rate %',             'optional', 14],
    ['I', 'Domestic Sale Exempt LAK','required', 25],
    ['J', 'User TE',                'fallback', 20],
    ['K', 'Provision Number',       'required', 18],
    ['L', 'User Benchmark Rate',    'fallback', 22],
    ['M', 'User Benchmark VAT',     'fallback', 22],
    ['N', 'Use User Fallback?',     'fallback', 22],
    ['O', 'System Benchmark Rate %','optional', 24],
    ['P', 'User Fallback Reason',   'fallback', 28],
    ['Q', 'User Comment',           'fallback', 30],
];

$colorMap = [
    'required' => ['header' => CLR_PRIMARY_REQUIRED, 'body' => CLR_INPUT_DATA],
    'optional' => ['header' => CLR_PRIMARY_OPTIONAL,  'body' => CLR_WHITE],
    'fallback' => ['header' => CLR_FALLBACK,           'body' => CLR_INPUT_FALLBACK],
];

// Set column widths
foreach ($columns as [$col, $header, $group, $width]) {
    $ws->getColumnDimension($col)->setWidth($width);
}

// Row 1: Headers
$row = 1;
foreach ($columns as $i => [$col, $header, $group, $width]) {
    $cell = $col . $row;
    $ws->setCellValue($cell, $header);
    $c = $colorMap[$group]['header'];
    styleHeader($ws, $cell, $c);
}

// Row 2: Example data
$row = 2;
$ws->setCellValue('A2', '01 | Vientiane Capital');
$ws->setCellValue('B2', '123456789-000');
$ws->setCellValue('C2', 'Example Company Co., Ltd.');
$ws->setCellValue('D2', 'Monthly');
$ws->setCellValue('E2', '05/2026');
$ws->setCellValue('F2', '2026-05-15');
$ws->setCellValue('G2', 'Domestic exempt sale of agricultural products');
$ws->setCellValue('H2', 10);
$ws->setCellValue('I2', 100000000);
$ws->setCellValue('J2', '');
$ws->setCellValue('K2', 39);
$ws->setCellValue('L2', '');
$ws->setCellValue('M2', '');
$ws->setCellValue('N2', 'No');
$ws->setCellValue('O2', 10);
$ws->setCellValue('P2', '');
$ws->setCellValue('Q2', 'Example record for testing');

// Style body rows (data rows start at row 2)
$lastBodyRow = 1001; // Provide 1000 data rows
foreach ($columns as $i => [$col, $header, $group, $width]) {
    $range = $col . '2:' . $col . $lastBodyRow;
    styleBody($ws, $range, $colorMap[$group]['body']);
}

// Text alignment
foreach ($columns as $i => [$col, $header, $group, $width]) {
    $colRange = $col . '2:' . $col . $lastBodyRow;
    if (in_array($col, ['H', 'I', 'J', 'L', 'M', 'O'])) {
        $ws->getStyle($colRange)->getNumberFormat()->setFormatCode('#,##0');
        $ws->getStyle($colRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}

// ============================================================
// Data Validations — STEP 5: Match expert XML exactly (no extra attributes)
// ============================================================
// Numeric validation (H,I,J,L,M,O) — match expert: only type, operator, formula1, allowBlank
$dv = new DataValidation();
$dv->setType(DataValidation::TYPE_DECIMAL);
$dv->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
$dv->setFormula1('0');
$dv->setAllowBlank(true);
// Do NOT set showDropDown or showErrorMessage to avoid extra attributes
$ws->setDataValidation('H2:J' . $lastBodyRow . ' L2:M' . $lastBodyRow . ' O2:O' . $lastBodyRow, $dv);

// Province dropdown — sheet reference
if ($provinces) {
    $provCount = count($provinces);
    $dv3 = new DataValidation();
    $dv3->setType(DataValidation::TYPE_LIST);
    $dv3->setFormula1("'Validation Lists'!\$A\$2:\$A\$" . ($provCount + 1));
    $dv3->setAllowBlank(true);
    $dv3->setShowDropDown(true);   // true -> writes showDropDown="0" in XML (shows arrow)
    // Do NOT set showErrorMessage to avoid errorStyle attribute
    $ws->setDataValidation('A2:A' . $lastBodyRow, $dv3);
}

// Provision Number dropdown — sheet reference
$provNums = $pdo->query("SELECT provision_number FROM vat_provisions ORDER BY provision_number")->fetchAll();
$pnCount = count($provNums);
$dv4 = new DataValidation();
$dv4->setType(DataValidation::TYPE_LIST);
$dv4->setFormula1("'Validation Lists'!\$E\$2:\$E\$" . ($pnCount + 1));
$dv4->setAllowBlank(true);
$dv4->setShowDropDown(true);   // true -> writes showDropDown="0" in XML
// Do NOT set showErrorMessage
$ws->setDataValidation('K2:K' . $lastBodyRow, $dv4);

// Yes/No dropdown — short inline (under 255 chars)
$dv2 = new DataValidation();
$dv2->setType(DataValidation::TYPE_LIST);
$dv2->setFormula1('"Yes,No"');
$dv2->setAllowBlank(true);
$dv2->setShowDropDown(true);   // true -> writes showDropDown="0" in XML
// Do NOT set showErrorMessage
$ws->setDataValidation('N2:N' . $lastBodyRow, $dv2);

// ============================================================
// SHEET 2: Instructions
// ============================================================
$ws2 = $spreadsheet->createSheet();
$ws2->setTitle('Instructions');
$ws2->getProtection()->setPassword('TaxETS2026');
$ws2->getProtection()->setSheet(true);
$ws2->getProtection()->setSort(true);
$ws2->getProtection()->setObjects(true);
$ws2->getProtection()->setScenarios(true);

$instructions = [
    ['Information Type', 'Color', 'Meaning', 'Examples'],
    ['Primary Required', 'Dark blue header, light blue input cells', 'Required for current Domestic VAT import and reporting.', 'Province, TIN, Filing Period, Domestic Sale Exempt, Provision Number'],
    ['Primary Optional', 'Slate/gray-blue header, white input cells', 'Official or supporting filing information useful for audit.', 'Company Name, Filing Type, Input Date, VAT Rate, Description, System Benchmark Rate'],
    ['User Fallback', 'Orange header, light amber input cells', 'Optional user-provided TE or benchmark values used only when primary information is incomplete.', 'User TE, User Benchmark Rate, User Benchmark VAT, Use User Fallback?, User Fallback Reason, User Comment'],
    ['', '', '', ''],
    ['Rule', 'Description'],
    ['Current compatibility', 'Columns A:K match the current Domestic VAT importer. The expert file uses A:I; User TE and Provision Number (J:K) are fallback and reference fields.'],
    ['Use primary first', 'Enter raw exempt domestic sale and official filing fields before using fallback values.'],
    ['No simple Excel calculations', 'Do not add columns that only multiply exempt sales by VAT rate. The system calculates those values.'],
    ['Fallback rule', 'Use User TE or User Benchmark values only when agreed user judgement is required.'],
    ['Fallback reason', 'Required when Use User Fallback? is Yes.'],
    ['Dropdown fields', 'Province, Provision Number, Use User Fallback? should use dropdowns.'],
    ['Mandatory fields', 'Province, TIN, Filing Period, Domestic Sale Exempt, and Provision Number.'],
    ['Template version', 'DOMESTIC_VAT_IMPORT v1.0'],
    ['Protection password', 'TaxETS2026 — used only to prevent accidental edits to read-only sheets.'],
];

$ws2->fromArray($instructions, null, 'A1', true);

// Style Instructions
$ws2->getColumnDimension('A')->setWidth(28);
$ws2->getColumnDimension('B')->setWidth(50);
$ws2->getColumnDimension('C')->setWidth(55);
$ws2->getColumnDimension('D')->setWidth(50);

// ============================================================
// SHEET 3: Validation Lists
// ============================================================
$ws3 = $spreadsheet->createSheet();
$ws3->setTitle('Validation Lists');
$ws3->getProtection()->setPassword('TaxETS2026');
$ws3->getProtection()->setSheet(true);
$ws3->getProtection()->setSort(true);
$ws3->getProtection()->setObjects(true);
$ws3->getProtection()->setScenarios(true);

$ws3->setCellValue('A1', 'Province Item');
$ws3->setCellValue('B1', 'Province Code');
$ws3->setCellValue('C1', 'Province Name');
$ws3->setCellValue('E1', 'Provision Number');
$ws3->setCellValue('F1', 'Legal Basis');
$ws3->setCellValue('G1', 'Type');

$r = 2;
foreach ($provinces as $p) {
    $ws3->setCellValue('A' . $r, $p['province_code'] . ' | ' . $p['province_name']);
    $ws3->setCellValue('B' . $r, $p['province_code']);
    $ws3->setCellValue('C' . $r, $p['province_name']);
    $r++;
}

$rProv = 2;
foreach ($provisions as $p) {
    $ws3->setCellValue('E' . $rProv, $p['provision_number']);
    $ws3->setCellValue('F' . $rProv, $p['legal_basis']);
    $ws3->setCellValue('G' . $rProv, $p['type_of_te']);
    $rProv++;
}

// Benchmark rates
$ws3->setCellValue('I1', 'Start Date');
$ws3->setCellValue('J1', 'End Date');
$ws3->setCellValue('K1', 'Rate %');
$rBR = 2;
foreach ($bmRates as $br) {
    $ws3->setCellValue('I' . $rBR, $br['start_date']);
    $ws3->setCellValue('J' . $rBR, $br['end_date']);
    $ws3->setCellValue('K' . $rBR, $br['rate_percentage']);
    $rBR++;
}

$ws3->getColumnDimension('A')->setWidth(25);
$ws3->getColumnDimension('B')->setWidth(15);
$ws3->getColumnDimension('C')->setWidth(25);
$ws3->getColumnDimension('E')->setWidth(15);
$ws3->getColumnDimension('F')->setWidth(40);
$ws3->getColumnDimension('G')->setWidth(18);
$ws3->getColumnDimension('I')->setWidth(15);
$ws3->getColumnDimension('J')->setWidth(15);
$ws3->getColumnDimension('K')->setWidth(12);

// ============================================================
// SHEET 4: Data Dictionary
// ============================================================
$ws4 = $spreadsheet->createSheet();
$ws4->setTitle('Data Dictionary');
$ws4->getProtection()->setPassword('TaxETS2026');
$ws4->getProtection()->setSheet(true);
$ws4->getProtection()->setSort(true);
$ws4->getProtection()->setObjects(true);
$ws4->getProtection()->setScenarios(true);

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
$ws5->getProtection()->setPassword('TaxETS2026');
$ws5->getProtection()->setSheet(true);
$ws5->getProtection()->setSort(true);
$ws5->getProtection()->setObjects(true);
$ws5->getProtection()->setScenarios(true);

$ws5->setCellValue('A1', 'Version');
$ws5->setCellValue('B1', 'Date');
$ws5->setCellValue('C1', 'Owner');
$ws5->setCellValue('D1', 'Change');
$ws5->setCellValue('A2', '1.0');
$ws5->setCellValue('B2', date('Y-m-d'));
$ws5->setCellValue('C2', 'APIS / Tax-ETS');
$ws5->setCellValue('D2', 'Expert-confirmed Domestic VAT import template v1.0 — 17 columns A-Q.');

$ws5->getColumnDimension('A')->setWidth(12);
$ws5->getColumnDimension('B')->setWidth(15);
$ws5->getColumnDimension('C')->setWidth(30);
$ws5->getColumnDimension('D')->setWidth(60);

// ============================================================
// Freeze panes on all sheets
// ============================================================
$ws->freezePane('A3'); // Freeze header + example row
$ws2->freezePane('A2');
$ws3->freezePane('A2');
$ws4->freezePane('A2');
$ws5->freezePane('A2');

// ============================================================
// Output
// ============================================================
$spreadsheet->setActiveSheetIndex(0);

if (PHP_SAPI === 'cli') {
    // CLI mode: save to tests directory
    $outputDir = __DIR__ . '/../tests';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    $filename = $outputDir . '/Domestic_VAT_Template.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "Template saved: $filename\n";
    exit(0);
}

// Browser download
$filename = 'Domestic_VAT_Template.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
