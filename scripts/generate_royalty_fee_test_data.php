<?php
/** Generate Royalty Fee test data with some TE > 0 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$tpl = IOFactory::load(__DIR__ . '/../tests/Royalty_Fee_Template.xlsx');
$ws = $tpl->setActiveSheetIndex(0);
for ($c = 1; $c <= 16; $c++) $ws->setCellValueByColumnAndRow($c, 2, null);

$data = [
    // TN, Name, Sale, BenchmarkRate, FeeCollected (fee < benchmark → TE > 0)
    // benchmark_fee = sale * rate/100. TE = max(0, benchmark_fee - fee)
    // sale=100M, rate=5%, benchmark=5M. fee=3M → TE=2M
    ['123456789-000', 'Lao Electricity Co., Ltd.',      100000000, 5, 3000000],
    ['123456789-000', 'Lao Electricity Co., Ltd.',      250000000, 5, 10000000],
    ['880782489-900', 'Vientiane Power Generation',     500000000, 5, 20000000, 'TE>0: fee<benchmark'],
    ['880782489-900', 'Vientiane Power Generation',     75000000,  5, 5000000],
    ['543163802-900', 'Savannakhet Hydro Co.',          800000000, 8, 60000000],
    ['543163802-900', 'Savannakhet Hydro Co.',          120000000, 8, 8000000],
    ['543163802-900', 'Savannakhet Hydro Co.',          450000000, 8, 35000000],
    ['456977486-900', 'Northern Wind Power',            300000000, 4, 15000000],
    ['456977486-900', 'Northern Wind Power',            60000000,  4, 2000000],
    ['580761167-900', 'Southern Solar Energy',          90000000,  3, 3000000],
    ['580761167-900', 'Southern Solar Energy',          200000000, 3, 5000000],
    // Records with TE=0 (fee = benchmark or higher)
    ['111222333-000', 'Central Power Grid Co.',         50000000,  5, 2500000],  // exactly at benchmark
    ['111222333-000', 'Central Power Grid Co.',         100000000, 5, 6000000],  // fee > benchmark
    ['987654321-000', 'Power Transmission Co., Ltd.',   150000000, 8, 15000000], // fee > benchmark
    ['987654321-000', 'Power Transmission Co., Ltd.',   80000000,  8, 6400000],  // exactly at benchmark
];

$tgtRow = 2;
foreach ($data as $r) {
    $year = 2025;
    $m = rand(1, 12); $d = rand(1, 28);
    $sale = $r[2];
    $bmRate = $r[3];
    $fee = $r[4];
    $note = $r[5] ?? '';

    $ws->setCellValue('A' . $tgtRow, $r[0]);
    $ws->setCellValue('B' . $tgtRow, sprintf('%d-01-15', rand(2015, 2023)));
    $ws->setCellValue('C' . $tgtRow, $year);
    $ws->setCellValue('D' . $tgtRow, sprintf('%d-%02d-%02d', $year, $m, $d));
    $ws->setCellValue('E' . $tgtRow, $bmRate);
    $ws->setCellValue('F' . $tgtRow, $bmRate); // contracted = benchmark in this test
    $ws->setCellValue('G' . $tgtRow, $sale);
    $ws->setCellValue('H' . $tgtRow, $fee);
    $ws->setCellValue('I' . $tgtRow, 'LAK');
    $ws->setCellValue('J' . $tgtRow, 1);
    $ws->setCellValue('K' . $tgtRow, 'No');
    $tgtRow++;
}

$out = __DIR__ . '/../docs/download-template-test/Royalty_Fee_Test_Data.xlsx';
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($tpl);
$writer->save($out);
echo 'Saved ' . ($tgtRow - 2) . " rows to: $out\n";
echo "\nExpect TE > 0 for:\n";
foreach ($data as $r) {
    $sale = $r[2]; $rate = $r[3]; $fee = $r[4];
    $benchmark = $sale * $rate / 100;
    $te = max(0, $benchmark - $fee);
    if ($te > 0) echo "  {$r[0]}: sale=$sale rate=$rate% benchmark=$benchmark fee=$fee TE=$te\n";
}
