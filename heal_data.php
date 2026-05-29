<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = getDbConnection();
    
    // 1. Expand Sectors Dictionary if needed
    echo "Expanding Sectors dictionary...\n";
    $missing_sectors = ['Agriculture', 'Commerce', 'Manufacturing', 'Services', 'Mining', 'Electricity', 'Production'];
    foreach ($missing_sectors as $s) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO business_sectors (sector_name) VALUES (?)");
        $stmt->execute([$s]);
    }

    // 2. Define Alias Map for Smart Healing
    $aliases = [
        'PROVINCE' => [
            'VIENTIANE CAPITAL' => ['VIENTIANE CAPITAL PROVINCE', 'VIENTIANE CAP', 'VTE CAPITAL', 'ນະຄອນຫຼວງວຽງຈັນ'],
            'VIENTIANE PROVINCE' => ['VIENTIANE', 'VTE PROVINCE', 'ວຽງຈັນ'],
            'LOUANGPRABANG' => ['LUANG PRABANG', 'LPB'],
            'SAVANNAKHET' => ['SAVAN', 'SVK'],
            'CHAMPASSAK' => ['CHAMPASAK', 'CPS'],
        ],
        'SECTOR' => [
            'Agriculture' => ['Agriculture & Processing', 'AGRI'],
            'Trade & Commerce' => ['Commerce', 'TRADE'],
            'Industrial & Manufacturing' => ['Manufacturing', 'INDUSTRY', 'INDUSTRIAL'],
            'Services & Tourism' => ['Services', 'SERVICE'],
        ]
    ];

    // 3. Fetch Dictionaries for ID lookup
    $provinces = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll(PDO::FETCH_KEY_PAIR);
    $sectors = $pdo->query("SELECT id, sector_name FROM business_sectors")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $provLookup = array_change_key_case(array_flip($provinces), CASE_UPPER);
    $sectLookup = array_change_key_case(array_flip($sectors), CASE_UPPER);

    // 4. Start "Healing" Process
    echo "Healing existing data...\n";
    $companies = $pdo->query("SELECT id, province, sector FROM companies WHERE pro_id IS NULL OR sector_id IS NULL")->fetchAll();
    
    $updateStmt = $pdo->prepare("UPDATE companies SET pro_id = ?, sector_id = ?, province = ?, sector = ? WHERE id = ?");
    
    $healed = 0;
    foreach ($companies as $c) {
        $raw_p = strtoupper(trim($c['province'] ?? ''));
        $raw_s = strtoupper(trim($c['sector'] ?? ''));
        
        $new_pro_id = null;
        $new_sect_id = null;
        $standard_p = $c['province'];
        $standard_s = $c['sector'];

        // Try direct lookup
        $new_pro_id = $provLookup[$raw_p] ?? null;
        $new_sect_id = $sectLookup[$raw_s] ?? null;

        // Try Alias healing for Province
        if (!$new_pro_id) {
            foreach ($aliases['PROVINCE'] as $standard => $variations) {
                foreach ($variations as $v) {
                    if ($raw_p === strtoupper($v)) {
                        $new_pro_id = $provLookup[strtoupper($standard)] ?? null;
                        $standard_p = $standard;
                        break 2;
                    }
                }
            }
        }

        // Try Alias healing for Sector
        if (!$new_sect_id) {
            foreach ($aliases['SECTOR'] as $standard => $variations) {
                foreach ($variations as $v) {
                    if ($raw_s === strtoupper($v)) {
                        $new_sect_id = $sectLookup[strtoupper($standard)] ?? null;
                        $standard_s = $standard;
                        break 2;
                    }
                }
            }
        }

        // If direct lookup and alias failed, try "contains" matching (fuzzier)
        if (!$new_pro_id) {
            foreach ($provLookup as $name => $id) {
                if (str_contains($raw_p, strtoupper($name)) || str_contains(strtoupper($name), $raw_p)) {
                    $new_pro_id = $id;
                    $standard_p = $name;
                    break;
                }
            }
        }
        if (!$new_sect_id) {
            foreach ($sectLookup as $name => $id) {
                if (str_contains($raw_s, strtoupper($name)) || str_contains(strtoupper($name), $raw_s)) {
                    $new_sect_id = $id;
                    $standard_s = $name;
                    break;
                }
            }
        }

        if ($new_pro_id || $new_sect_id) {
            $updateStmt->execute([
                $new_pro_id ?: null, 
                $new_sect_id ?: null,
                $new_pro_id ? $standard_p : $c['province'],
                $new_sect_id ? $standard_s : $c['sector'],
                $c['id']
            ]);
            $healed++;
        }
    }

    echo "Healing complete! $healed records improved.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
