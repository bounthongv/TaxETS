<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$spreadsheet = IOFactory::load(__DIR__ . '/docs/zone.xlsx');
$sheet = $spreadsheet->getActiveSheet();

echo "Rows: " . $sheet->getHighestRow() . PHP_EOL;
echo "Columns: " . $sheet->getHighestColumn() . PHP_EOL . PHP_EOL;

// Show header row
echo "Header: ";
for ($c = 'A'; $c <= $sheet->getHighestColumn(); $c++) {
    echo $sheet->getCell($c . '1')->getValue() . " | ";
}
echo PHP_EOL . PHP_EOL;

// Show first 10 data rows
for ($r = 2; $r <= min(11, $sheet->getHighestRow()); $r++) {
    echo "Row $r: ";
    for ($c = 'A'; $c <= $sheet->getHighestColumn(); $c++) {
        echo $sheet->getCell($c . $r)->getValue() . " | ";
    }
    echo PHP_EOL;
}