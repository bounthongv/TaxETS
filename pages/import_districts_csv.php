<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

$file = __DIR__ . "/../docs/Lao_Districts_List.csv";

if (!file_exists($file)) {
    die("File not found: $file");
}

$handle = fopen($file, "r");
$count = 0;
$skipped = 0;
$header = true;

while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    if ($header) { $header = false; continue; }
    if (empty($data[1])) continue;
    
    $province_code = trim($data[0]);
    $district_code = trim($data[1]);
    $district_name = trim($data[2]);
    
    if (!$province_code || !$district_code) { continue; }
    
    $province_id = null;
    $p = $pdo->prepare("SELECT id FROM provinces WHERE province_code = ?");
    $p->execute([$province_code]);
    $prov = $p->fetch();
    $province_id = $prov["id"] ?? null;
    
    if ($province_id) {
        $stmt = $pdo->prepare("INSERT INTO districts (district_code, district_name, province_id, active) VALUES (?, ?, ?, 1) 
            ON DUPLICATE KEY UPDATE district_name = VALUES(district_name), province_id = VALUES(province_id)");
        $stmt->execute([$district_code, $district_name, $province_id]);
        $count++;
    } else {
        $skipped++;
    }
}
fclose($handle);

echo "Imported $count districts successfully!";
if ($skipped > 0) { echo " (skipped $skipped - no province matched)"; }
?>