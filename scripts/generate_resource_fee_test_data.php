<?php
/** Generate Resource Fee test data */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
$resources = $pdo->query("SELECT item_no, item_name, rate_percentage FROM bm_natural_resource WHERE active = 1 ORDER BY item_no")->fetchAll();

$tpl = IOFactory::load(__DIR__ . '/../tests/Resource_Fee_Template.xlsx');
$ws = $tpl->setActiveSheetIndex(0);

for ($c = 1; $c <= 17; $c++) $ws->setCellValueByColumnAndRow($c, 2, null);

$companies = [
    ['123456789-000', 'Lao Mining Co., Ltd.'],
    ['880782489-900', 'Vientiane Mineral Extraction Co.'],
    ['543163802-900', 'Savannakhet Quarry Co.'],
    ['456977486-900', 'Northern Gemstones Ltd.'],
    ['580761167-900', 'Southern Resources Corp.'],
    ['583429006-900', 'Central Mining Group'],
    ['111222333-000', 'Luangprabang Gold Mining'],
    ['987654321-000', 'Champasak Construction Materials'],
];

$tgtRow = 2;
foreach ($companies as $c) {
    $numRecords = rand(2, 3);
    for ($i = 0; $i < $numRecords; $i++) {
        $ri = array_rand($resources);
        $rt = $resources[$ri];
        $qty = rand(100, 50000);
        $benchRate = (float)$rt['rate_percentage'];
        $randFactor = 0.8 + (rand(0, 10000) / 10000) * 0.2;
        $contractedRate = round($benchRate * $randFactor, 2);
        $randFee = rand(90, 110);
        $fee = round($qty * $contractedRate * $randFee / 100);

        $year = 2025;
        $month = rand(1, 12);
        $day = rand(1, 28);
        $rdate = sprintf('%d-%02d-%02d', $year, $month, $day);
        $licenseDate = sprintf('%d-01-15', rand(2015, 2023));

        $ws->setCellValue('A' . $tgtRow, $c[0]);
        $ws->setCellValue('B' . $tgtRow, $licenseDate);
        $ws->setCellValue('C' . $tgtRow, $rt['item_no'] . ' | ' . $rt['item_name']);
        $ws->setCellValue('D' . $tgtRow, $year);
        $ws->setCellValue('E' . $tgtRow, $rdate);
        $ws->setCellValue('F' . $tgtRow, $benchRate);
        $ws->setCellValue('G' . $tgtRow, $contractedRate);
        $ws->setCellValue('H' . $tgtRow, $qty);
        $ws->setCellValue('I' . $tgtRow, $fee);
        $ws->setCellValue('J' . $tgtRow, 'LAK');
        $ws->setCellValue('K' . $tgtRow, 1);
        $ws->setCellValue('L' . $tgtRow, 'No');
        $tgtRow++;
    }
}

$outDir = __DIR__ . '/../docs/download-template-test';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($tpl);
$writer->save($outDir . '/Resource_Fee_Test_Data.xlsx');

$total = $tgtRow - 2;
echo "Saved $total rows to docs/download-template-test/Resource_Fee_Test_Data.xlsx\n";
