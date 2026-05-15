<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();

$file = __DIR__ . "/../docs/Lao_Districts_List.xlsx";

echo "Loading Excel file...<br>";
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

$count = 0;
foreach ($sheet->toArray() as $i => $row) {
    if ($i === 0) continue; // skip header
    if (empty($row[0])) continue;
    
    $code = trim($row[0]);
    $name = trim($row[1] ?? "");
    $province_name = trim($row[2] ?? "");
    
    $province_id = null;
    if ($province_name) {
        $p = $pdo->prepare("SELECT id FROM provinces WHERE province_name = ?");
        $p->execute([$province_name]);
        $prov = $p->fetch();
        $province_id = $prov["id"] ?? null;
    }
    
    $stmt = $pdo->prepare("INSERT INTO districts (district_code, district_name, province_id, active) VALUES (?, ?, ?, 1) 
        ON DUPLICATE KEY UPDATE district_name = VALUES(district_name), province_id = VALUES(province_id)");
    $stmt->execute([$code, $name, $province_id]);
    $count++;
}

echo "Imported $count districts successfully!";
?>