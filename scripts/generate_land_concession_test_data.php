<?php
/**
 * Generate Land Concession test data using PhpSpreadsheet
 * Takes the generated template and fills it with source data
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Province code -> name
$provNames = [
    '01'=>'Vientiane Capital','02'=>'Phongsaly','03'=>'Luangnamtha','04'=>'Oudomxay',
    '05'=>'Bokeo','06'=>'Luangprabang','07'=>'Huaphanh','08'=>'Sayaboury',
    '09'=>'Xiengkhouang','10'=>'Vientiane Province','11'=>'Borikhamxay','12'=>'Khamouane',
    '13'=>'Savannakhet','14'=>'Saravanh','15'=>'Xekong','16'=>'Champasak',
    '17'=>'Attapeu','18'=>'Xaisomboun',
];

$provNamesLower = [];
foreach ($provNames as $k => $v) {
    $provNamesLower[strtolower(str_replace(' ', '', $v))] = $k;
}
$provNamesLower['viangchan'] = '01';
$provNamesLower['bolikhamxay'] = '11';
$provNamesLower['khammouane'] = '12';
$provNamesLower['houaphanh'] = '07';
$provNamesLower['xayaboury'] = '08';
$provNamesLower['xiengkhuang'] = '09';
$provNamesLower['champasack'] = '16';
$provNamesLower['salavanh'] = '14';
$provNamesLower['xienghuang'] = '09';
$provNamesLower['khammuan'] = '12';
$provNamesLower['sekong'] = '15';

// Load source data
$src = IOFactory::load(__DIR__ . '/../docs/540 projects/NonTax_540.xlsx');
$srcSheet = $src->getActiveSheet();
$highestRow = $srcSheet->getHighestRow();

// Load template
$tpl = IOFactory::load(__DIR__ . '/../tests/Land_Concession_Template.xlsx');
$ws = $tpl->setActiveSheetIndex(0);

// Get descriptions from Validation Lists (col K)
$vl = $tpl->getSheetByName('Validation Lists');
$descriptions = [];
for ($r = 2; $r <= $vl->getHighestRow(); $r++) {
    $v = $vl->getCell('K' . $r)->getValue();
    if ($v) $descriptions[] = $v;
}

// Clear example row
for ($c = 1; $c <= 19; $c++) {
    $ws->setCellValueByColumnAndRow($c, 2, null);
}

// Process source rows
$tgtRow = 2;
$unmapped = [];
$provCounts = [];

for ($srcRow = 2; $srcRow <= $highestRow; $srcRow++) {
    $tin = trim((string)($srcSheet->getCell('A' . $srcRow)->getValue() ?? ''));
    $area = $srcSheet->getCell('H' . $srcRow)->getValue();
    if (empty($tin) || empty($area)) continue;
    
    $provRaw = strtolower(str_replace(' ', '', trim((string)($srcSheet->getCell('D' . $srcRow)->getValue() ?? ''))));
    $pcode = $provNamesLower[$provRaw] ?? null;
    
    // If province can't be mapped, use raw name as-is (don't skip)
    if ($pcode) {
        $province = $pcode . ' | ' . ($provNames[$pcode] ?? '');
    } else {
        $province = trim((string)($srcSheet->getCell('D' . $srcRow)->getValue() ?? ''));
        if (empty($province)) $province = 'Unknown';
    }
    
    if ($pcode) $provCounts[$pcode] = ($provCounts[$pcode] ?? 0) + 1;
    
    $company = trim((string)($srcSheet->getCell('B' . $srcRow)->getValue() ?? ''));
    $district = trim((string)($srcSheet->getCell('C' . $srcRow)->getValue() ?? ''));
    
    $rdate = $srcSheet->getCell('G' . $srcRow)->getValue();
    if ($rdate instanceof DateTime) {
        $rdateStr = $rdate->format('Y-m-d');
    } elseif (is_numeric($rdate)) {
        try { $rdateStr = Date::excelToDateTimeObject($rdate)->format('Y-m-d'); } catch (Exception $e) { $rdateStr = ''; }
    } else {
        $rdateStr = substr((string)$rdate, 0, 10);
    }
    
    $areaVal = is_numeric($area) ? (float)$area : 0;
    $bmRate = is_numeric($v = $srcSheet->getCell('I' . $srcRow)->getValue()) ? (float)$v : null;
    $ctRate = is_numeric($v = $srcSheet->getCell('J' . $srcRow)->getValue()) ? (float)$v : null;
    $fee = is_numeric($v = $srcSheet->getCell('K' . $srcRow)->getValue()) ? (float)$v : null;
    
    // Assign deterministic description based on TIN hash
    $descIdx = abs(crc32($tin)) % count($descriptions);
    $desc = $descriptions[$descIdx];
    
    $ws->setCellValue('A' . $tgtRow, $tin);
    $ws->setCellValue('B' . $tgtRow, $company);
    $ws->setCellValue('C' . $tgtRow, $province);
    $ws->setCellValue('D' . $tgtRow, $district);
    $ws->setCellValue('E' . $tgtRow, $desc);
    $ws->setCellValue('F' . $tgtRow, 2023);
    $ws->setCellValue('G' . $tgtRow, $rdateStr);
    $ws->setCellValue('H' . $tgtRow, round($areaVal));
    if ($bmRate) $ws->setCellValue('I' . $tgtRow, round($bmRate));
    if ($ctRate) $ws->setCellValue('J' . $tgtRow, round($ctRate));
    if ($fee) $ws->setCellValue('K' . $tgtRow, round($fee));
    
    $tgtRow++;
}

// Save
$outDir = __DIR__ . '/../docs/download-template-test';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);
$outFile = $outDir . '/Land_Concession_Test_Data.xlsx';

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($tpl);
$writer->save($outFile);

$total = $tgtRow - 2;
echo "Written $total rows to: $outFile\n";
if ($unmapped) echo "Unmapped provinces: " . implode(', ', array_keys($unmapped)) . "\n";
echo "Province breakdown:\n";
foreach ($provCounts as $pc => $cnt) {
    echo "  $pc (" . ($provNames[$pc] ?? '?') . "): $cnt\n";
}
