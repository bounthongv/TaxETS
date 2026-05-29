<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = getDbConnection();
    
    // Find missing districts
    $stmt = $pdo->query("SELECT id, province, district, pro_id FROM companies WHERE dis_id IS NULL AND district IS NOT NULL");
    $missing = $stmt->fetchAll();
    
    echo "Found " . count($missing) . " companies with missing dis_id.\n";
    
    // Fetch all districts for fuzzy matching
    $districts = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id")->fetchAll();
    
    $updateStmt = $pdo->prepare("UPDATE companies SET dis_id = ? WHERE id = ?");
    
    foreach ($missing as $c) {
        $raw_d = strtoupper(trim($c['district']));
        $pro_id = $c['pro_id'];
        
        foreach ($districts as $d) {
            // Match by pro_id and fuzzy name match
            if ($d['pro_id'] == $pro_id) {
                $dict_name = strtoupper(trim($d['dis_name']));
                // Direct match or simple inclusion match
                if ($raw_d === $dict_name || str_contains($raw_d, $dict_name) || str_contains($dict_name, $raw_d)) {
                    $updateStmt->execute([$d['dis_id'], $c['id']]);
                    echo "Mapped: " . $c['district'] . " -> " . $d['dis_name'] . " (ID: " . $d['dis_id'] . ")\n";
                    break;
                }
            }
        }
    }
    echo "Done.\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
