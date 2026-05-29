<?php
/**
 * Repair Script: Fix district and province mappings in companies table
 * 
 * Issues fixed:
 * 1. Strips "District" suffix from companies.district text
 * 2. Fixes province name aliases (Bolikhamsai→Borikhamxay, Xiangkhouang→Xiengkhouang)
 * 3. Fuzzy-matches district names against the districts dictionary table
 * 4. Syncs the district text name with the official name from districts table
 */

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/db.php";

$pdo = getDbConnection();
echo "=== District Repair Script ===\n\n";

// --- Step 1: Build province alias map ---
echo "--- Step 1: Building province alias map ---\n";
$prov_rows = $pdo->query("SELECT province_code, province_name FROM provinces")->fetchAll();

$prov_map = []; // exact match: UPPERCASE name → province_code
$prov_aliases = []; // common alternative spellings
foreach ($prov_rows as $r) {
    $prov_map[strtoupper(trim($r['province_name']))] = $r['province_code'];
}

// Common alternative province name spellings
$prov_aliases = [
    'BOLIKHAMSAI'    => '11',  // Borikhamxay
    'BOLIKHAMXAI'    => '11',
    'BORIKHAMXAY'    => '11',
    'XIANGKHOUANG'   => '09',  // Xiengkhouang
    'XIENGKHOUANG'   => '09',
    'VIENTIANE'      => '01',  // Vientiane Capital (if just "VIENTIANE")
    'VIENTIANE CAPITAL' => '01',
    'VIENTIANE CAPITOL' => '01',
    'BOKEO'          => '05',  // Bokeo
    'LUANGPRABANG'   => '06',
    'LUANGPHRABANG'  => '06',
    'LUANG NAMTHA'   => '03',
    'LUANGNAMTHA'    => '03',
    'OUDOMXAY'       => '04',
    'SAYABOURY'      => '08',
    'SAYABURI'       => '08',
    'XAYABOURY'      => '08',
    'XAYABURI'       => '08',
    'SARAVANE'       => '14',
    'SARAVANH'       => '14',
    'SEKONG'         => '15',
    'XEKONG'         => '15',
    'ATTAPEU'        => '17',
    'ATTAPU'         => '17',
    'XAISOMBOUN'     => '18',
    'SAISOMBOUN'     => '18',
];

$prov_fixed = 0;
$prov_skip = 0;

$companies = $pdo->query("SELECT id, province, pro_id, district, dis_id FROM companies WHERE (pro_id IS NULL OR pro_id = '' OR dis_id IS NULL OR dis_id = '') ORDER BY id")->fetchAll();
echo "Found " . count($companies) . " records with missing province or district mapping.\n\n";

foreach ($companies as $c) {
    $changes = [];
    $needs_province_update = false;
    $needs_district_update = false;
    
    // --- Fix Province ---
    $raw_prov = trim($c['province'] ?? '');
    $pro_id = $c['pro_id'];
    
    if (empty($pro_id) && !empty($raw_prov)) {
        $upper_prov = strtoupper($raw_prov);
        
        // Try exact match first
        if (isset($prov_map[$upper_prov])) {
            $pro_id = $prov_map[$upper_prov];
            echo "  [{$c['id']}] Province via exact: \"{$raw_prov}\" → pro_id='{$pro_id}'\n";
        }
        // Try alias map
        elseif (isset($prov_aliases[$upper_prov])) {
            $pro_id = $prov_aliases[$upper_prov];
            echo "  [{$c['id']}] Province via alias: \"{$raw_prov}\" → pro_id='{$pro_id}'\n";
        }
        // Try fuzzy match
        else {
            $best_match = null;
            $best_score = 999;
            foreach ($prov_map as $name => $code) {
                $score = levenshtein($upper_prov, $name);
                if ($score < $best_score) {
                    $best_score = $score;
                    $best_match = $code;
                }
                // Also try aliases
                foreach ($prov_aliases as $alias => $alias_code) {
                    $score = levenshtein($upper_prov, $alias);
                    if ($score < $best_score) {
                        $best_score = $score;
                        $best_match = $alias_code;
                    }
                }
            }
            if ($best_score <= 3 && $best_match !== null) {
                $pro_id = $best_match;
                echo "  [{$c['id']}] Province via fuzzy (score={$best_score}): \"{$raw_prov}\" → pro_id='{$pro_id}'\n";
            } else {
                echo "  [{$c['id']}] WARNING: Province \"{$raw_prov}\" could not be matched!\n";
            }
        }
        
        if (!empty($pro_id)) {
            $stmt = $pdo->prepare("UPDATE companies SET pro_id = ? WHERE id = ?");
            $stmt->execute([$pro_id, $c['id']]);
            $prov_fixed++;
            $needs_province_update = true;
        }
    } elseif (!empty($pro_id)) {
        $prov_skip++;
    }
    
    // --- Fix District ---
    $raw_dist = trim($c['district'] ?? '');
    $dis_id = $c['dis_id'];
    
    if (empty($dis_id) && !empty($raw_dist) && !empty($pro_id)) {
        // Step 1: Strip " District" or " district" suffix
        $clean_dist = preg_replace('/\s+District$/i', '', $raw_dist);
        $upper_dist = strtoupper(trim($clean_dist));
        
        // Build district map for this province
        $dist_rows = $pdo->prepare("SELECT district_code, district_name FROM districts d JOIN provinces p ON d.province_id = p.id WHERE p.province_code = ?");
        $dist_rows->execute([$pro_id]);
        $all_dists = $dist_rows->fetchAll();
        
        $dist_map = [];
        foreach ($all_dists as $d) {
            $dist_map[strtoupper(trim($d['district_name']))] = $d['district_code'];
        }
        
        // Try exact match on cleaned name
        if (isset($dist_map[$upper_dist])) {
            $dis_id = $dist_map[$upper_dist];
            echo "  [{$c['id']}] District via exact: \"{$raw_dist}\" → \"{$clean_dist}\" → dis_id='{$dis_id}'\n";
        }
        // Try fuzzy match
        else {
            $best_match = null;
            $best_score = 999;
            foreach ($dist_map as $name => $code) {
                $score = levenshtein($upper_dist, $name);
                if ($score < $best_score) {
                    $best_score = $score;
                    $best_match = $code;
                }
            }
            if ($best_score <= 3 && $best_match !== null) {
                $dis_id = $best_match;
                echo "  [{$c['id']}] District via fuzzy (score={$best_score}): \"{$raw_dist}\" → \"{$clean_dist}\" → dis_id='{$dis_id}'\n";
            } else {
                echo "  [{$c['id']}] WARNING: District \"{$raw_dist}\" (cleaned: \"{$clean_dist}\") could not be matched for province '{$pro_id}'!\n";
            }
        }
        
        if (!empty($dis_id)) {
            // Get the official district name from the dictionary
            $official_name = $pdo->query("SELECT district_name FROM districts WHERE district_code = " . $pdo->quote($dis_id))->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE companies SET dis_id = ?, district = ? WHERE id = ?");
            $stmt->execute([$dis_id, $official_name, $c['id']]);
            $needs_district_update = true;
            echo "  [{$c['id']}] Updated district text: \"{$raw_dist}\" → \"{$official_name}\"\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Province fixes: {$prov_fixed}\n";
echo "District fixes: (included in detail above)\n";

// --- Step 2: Clean any remaining "District" suffix from already-mapped records ---
echo "\n--- Step 2: Cleaning 'District' suffix from already-mapped records ---\n";
$rows_to_clean = $pdo->query("SELECT id, district, dis_id FROM companies WHERE district LIKE '% District' AND dis_id IS NOT NULL AND dis_id != ''")->fetchAll();
echo "Found " . count($rows_to_clean) . " mapped records with 'District' suffix.\n";

foreach ($rows_to_clean as $r) {
    $clean_name = preg_replace('/\s+District$/i', '', trim($r['district']));
    // Get the official name
    $official_name = $pdo->query("SELECT district_name FROM districts WHERE district_code = " . $pdo->quote($r['dis_id']))->fetchColumn();
    if ($official_name) {
        $stmt = $pdo->prepare("UPDATE companies SET district = ? WHERE id = ?");
        $stmt->execute([$official_name, $r['id']]);
        echo "  [{$r['id']}] Cleaned: \"{$r['district']}\" → \"{$official_name}\"\n";
    }
}

echo "\nDone!\n";
