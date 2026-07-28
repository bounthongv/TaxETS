<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle calculation redirect back from calculator.php
if (isset($_GET["calc_done"])) {
    $count = (int)($_GET["count"] ?? 0);
    $total = (float)($_GET["total_te"] ?? 0);
    $err = (int)($_GET["errors"] ?? 0);
    $batch_name = htmlspecialchars($_GET["batch"] ?? "");
    if ($err > 0) {
        $message = "Calculation completed with <span class='text-warning'>{$err} issue(s)</span>: "
                 . "<strong>{$count} companies</strong> processed for batch <code>{$batch_name}</code>. "
                 . "Total TE = <strong>" . number_format($total, 0) . " LAK</strong>";
        $msg_type = "warning";
    } elseif ($count > 0) {
        $message = "Calculation complete! Batch <code>{$batch_name}</code>: "
                 . "<strong>{$count} companies</strong> processed. "
                 . "Total TE = <strong>" . number_format($total, 0) . " LAK</strong>";
    }
}

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    try {
        $file = $_FILES["excel_file"];
        $tax_year = (int)$_POST["tax_year"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error.");
        }
        if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
            throw new Exception("Invalid file type.");
        }

        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getActiveSheet();
        $batch_id = "BATCH_" . date("YmdHis");
        $imported = 0; $skipped = 0;
        $unmapped_prov = 0; $unmapped_dist = 0; $unmapped_sect = 0;
        $error_log = [];
        $duplicate_log = [];
        $ok = true;

        // --- Phase 1: Duplicate Check (prevent double-import) ---
        // Scan all rows first, collect TIN + tax_year pairs, check against DB
        $dup_check_rows = [];
        $firstDataRow = 2; // configurable
        $highestRow = $sheet->getHighestRow();
        for ($row = $firstDataRow; $row <= $highestRow; $row++) {
            $tin = trim($sheet->getCell("B" . $row)->getCalculatedValue() ?? '');
            if (empty($tin)) continue;
            $excel_year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
            if ($excel_year <= 0) continue;
            $dup_check_rows[] = [
                "row" => $row,
                "tin" => $tin,
                "year" => $excel_year,
                "name" => trim($sheet->getCell("C" . $row)->getCalculatedValue() ?? ''),
            ];
        }

        if (!empty($dup_check_rows)) {
            // Build batch-parameterised query to check existing records
            $placeholders = [];
            $params = [];
            foreach ($dup_check_rows as $i => $dr) {
                $placeholders[] = "(? , ?)";
                $params[] = $dr["tin"];
                $params[] = $dr["year"];
            }
            $placeholders_str = implode(", ", $placeholders);
            $existing = $pdo->prepare("
                SELECT c.tin, c.tax_year, c.company_name, c.import_batch_id
                FROM companies c
                WHERE (c.tin, c.tax_year) IN ($placeholders_str)
                ORDER BY c.tin, c.tax_year
            ");
            $existing->execute($params);
            $dup_map = [];
            foreach ($existing->fetchAll() as $ex) {
                $key = $ex["tin"] . "|" . $ex["tax_year"];
                if (!isset($dup_map[$key])) {
                    $dup_map[$key] = [];
                }
                $dup_map[$key][] = $ex;
            }

            $dup_count = 0;
            foreach ($dup_check_rows as $dr) {
                $key = $dr["tin"] . "|" . $dr["year"];
                if (isset($dup_map[$key])) {
                    $dup_count++;
                    $batch_ids = array_unique(array_column($dup_map[$key], "import_batch_id"));
                    $error_log[] = "Row {$dr['row']}: TIN '{$dr['tin']}' / Year {$dr['year']} — already exists in batch(es): " . implode(", ", $batch_ids);
                    $duplicate_log[] = "{$dr['row']},{$dr['tin']},{$dr['year']}," . str_replace(",", ";", $dr['name']) . "," . implode("; ", $batch_ids);
                }
            }

            if ($dup_count > 0) {
                // Write duplicate log file
                $dup_log_content = "Row,TIN,Year,Company Name,Existing Batch(es)
\n" . implode("
\n", $duplicate_log) . "
\n";
                $dup_log_path = __DIR__ . "/../data/logs/{$batch_id}_duplicates.csv";
                file_put_contents($dup_log_path, $dup_log_content);

                $message = "<div class='alert alert-danger'><strong>⛔ Import Blocked!</strong> Found <strong>$dup_count</strong> duplicate record(s) that already exist in the database.<br>";
                $message .= "Please review the details below, then either:<br>";
                $message .= "1. <strong>Clean up the database</strong> by deleting the existing batch(es) first (Admin only), or<br>";
                $message .= "2. <strong>Remove the duplicate rows</strong> from your Excel file and try again.<br><br>";
                $message .= "<a href='download_log.php?log_id={$batch_id}_duplicates' class='btn btn-sm btn-danger'><i class='fas fa-download me-1'></i> Download Duplicate Report (CSV)</a></div>";

                if (!empty($error_log)) {
                    $log_content = "DUPLICATE CHECK LOG - " . date("Y-m-d H:i:s") . "
\n";
                    $log_content .= "Batch: $batch_id
\n";
                    $log_content .= "File: " . $file["name"] . "
\n
\n";
                    $log_content .= implode("
\n", $error_log);
                    file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);
                    $message .= "<br><br><a href='download_log.php?log_id=$batch_id' class='btn btn-sm btn-outline-danger'><i class='fas fa-download me-1'></i> Download Detailed Error Log</a>";
                }
                $ok = false;
            }
            unset($existing);
        }

        // --- Pre-load Dictionary for Smart Mapping ---
        $prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
        $dist_rows = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id")->fetchAll();
        $sect_rows = $pdo->query("SELECT id, sector_name FROM business_sectors")->fetchAll();

        $prov_map = []; foreach ($prov_rows as $r) { $prov_map[strtoupper(trim($r['pro_name']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']]; }
        $prov_aliases = [
            // Province 01 - Vientiane Capital
            'VIENTIANE'                => '01',
            'VIENTIANE CAPITAL'        => '01',
            'VIENTIANE CAPITAL PROVINCE' => '01',
            'VIENTIANE PREFECTURE'     => '01',
            'NAXAYTHONG'               => '01',
            // Province 02 - Phongsaly
            'PHONGSALY'                => '02',
            'PHONGSALI'                => '02',
            // Province 03 - Luangnamtha
            'LUANGNAMTHA'              => '03',
            'LUANG NAMTHA'             => '03',
            'LUANGNAMTA'               => '03',
            // Province 04 - Oudomxay
            'OUDOMXAY'                 => '04',
            'OUDOMXAI'                 => '04',
            'UDOMXAY'                  => '04',
            // Province 05 - Bokeo
            'BOKEO'                    => '05',
            // Province 06 - Luangprabang
            'LUANGPRABANG'             => '06',
            'LUANGPHRABANG'            => '06',
            'LUANG PRABANG'            => '06',
            'LUANG PHRA BANG'          => '06',
            'LUANGPHRABANG'            => '06',
            // Province 07 - Huaphanh
            'HUAPHANH'                 => '07',
            'HUAPHAN'                  => '07',
            'HOUAPHAN'                 => '07',
            'HOUAPHANH'                => '07',
            // Province 08 - Sayaboury
            'SAYABOURY'                => '08',
            'SAYABURI'                 => '08',
            'XAYABOURY'                => '08',
            'XAYABURI'                 => '08',
            // Province 09 - Xiengkhouang
            'XIANGKHOUANG'             => '09',
            'XIENGKHOUANG'             => '09',
            'XIENG KHOUNG'             => '09',
            'XIANG KHOANG'             => '09',
            // Province 10 - Vientiane Province
            'VIENTIANE PROVINCE'       => '10',
            // Province 11 - Borikhamxay
            'BOLIKHAMSAI'              => '11',
            'BOLIKHAMXAI'              => '11',
            'BORIKHAMXAY'              => '11',
            'BORIKHAMXAI'              => '11',
            // Province 12 - Khamouane
            'KHAMOUANE'                => '12',
            'KHAMMOUANE'               => '12',
            'KHAMMUANE'                => '12',
            // Province 13 - Savannakhet
            'SAVANNAKHET'              => '13',
            'SAVANAKHET'               => '13',
            // Province 14 - Saravanh
            'SARAVANH'                 => '14',
            'SARAVANE'                 => '14',
            // Province 15 - Xekong
            'SEKONG'                   => '15',
            'XEKONG'                   => '15',
            // Province 16 - Champasak
            'CHAMPASAK'                => '16',
            'CHAMPASSAK'               => '16',
            'CHAMPASSACK'              => '16',
            // Province 17 - Attapeu
            'ATTAPEU'                  => '17',
            'ATTAPU'                   => '17',
            'ATTAPUE'                  => '17',
            // Province 18 - Xaisomboun
            'XAISOMBOUN'               => '18',
            'XAYSOMBOUN'               => '18',
            'XAISOMBOU'                => '18',
        ];
        $sect_map = []; foreach ($sect_rows as $r) { $sect_map[strtoupper(trim($r['sector_name']))] = $r['id']; }
        $sect_aliases = [
            'CONSTRUCTION'       => 'Infrastructure & Construction',
            'INFRASTRUCTURE'     => 'Infrastructure & Construction',
            'AGRICULTURE'        => 'Agriculture',
            'AGRICULTURE & PROCESSING' => 'Agriculture & Processing',
            'AGRI'               => 'Agriculture',
            'TRADE'              => 'Commerce',
            'TRADING'            => 'Commerce',
            'SERVICE'            => 'Service',
            'SERVICES'           => 'Service',
            'HOTEL'              => 'Hotel and Restaurant',
            'RESTAURANT'         => 'Hotel and Restaurant',
            'BANK'               => 'Banking',
            'BANKING'            => 'Banking',
            'MINING'             => 'Mining',
            'ENERGY'             => 'Energy',
            'EDUCATION'          => 'Education',
            'CONSULTANCY'        => 'Consultancy',
            'CONSULTING'         => 'Consultancy',
            'MANUFACTURING'      => 'Manufacturing',
            'MANUFACTURE'        => 'Manufacturing',
            'INDUSTRY'           => 'Industrial & Manufacturing',
            'INDUSTRIAL'         => 'Industrial & Manufacturing',
            'PRODUCTION'         => 'Production',
            'HEALTH'             => 'Public health',
            'PUBLIC HEALTH'      => 'Public health',
            'ELECTRICITY'        => 'Electricity',
            'REAL ESTATE'        => 'Real estate activities',
            'PROPERTY'           => 'Real estate activities',
            'PROFESSIONAL'       => 'Professional, scientific and technical activities',
            'SCIENCE'            => 'Professional, scientific and technical activities',
            'HANDICRAFT'         => 'Industry and Handicraft',
            'HOUSEHOLD'          => 'Activities of households',
            'EXTRATERRITORIAL'   => 'Activities of extraterritorial organizations and bodies',
            'PUBLIC ADMIN'       => 'Public administration and defence; compulsory social security',
            'DEFENCE'            => 'Public administration and defence; compulsory social security',
            'ARTS'               => 'Arts, entertainment and recreation',
            'ENTERTAINMENT'      => 'Arts, entertainment and recreation',
            'WATER SUPPLY'       => 'Water supply; sewerage, waste management and remediation activities',
            'WASTE'              => 'Water supply; sewerage, waste management and remediation activities',
        ];
        
        $dist_map = []; 
        $dist_by_province = [];
        foreach ($dist_rows as $r) { 
            $dist_map[$r['pro_id'] . '|' . strtoupper(trim($r['dis_name']))] = $r['dis_id']; 
            $dist_by_province[$r['pro_id']][] = ['dis_id' => $r['dis_id'], 'name' => strtoupper(trim($r['dis_name']))];
        }

        // Detect template format: expert template has headers on row 4, data from row 5
        $firstDataRow = 2;
        if (trim($sheet->getCell("A4")->getCalculatedValue() ?? '') === 'Tax Year') {
            $firstDataRow = 5; // Expert-standard template
        }

        // Skip main import if duplicate check blocked it
        if ($ok):

        for ($row = $firstDataRow; $row <= $sheet->getHighestRow(); $row++) {
            $tin = trim($sheet->getCell("B" . $row)->getCalculatedValue() ?? '');
            if (empty($tin)) { $skipped++; continue; }

            $flag = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                return ($v == 1 || strtolower(trim($v ?? '')) === "yes") ? 1 : 0;
            };
            $dateVal = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (!$v) return null;
                if (is_numeric($v)) {
                    $d = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v);
                    return $d->format("Y-m-d");
                }
                return $v;
            };

            $act1 = $flag("AB"); $act2 = $flag("AC"); $act3 = $flag("AD");
            $act4 = $flag("AE"); $act5 = $flag("AF"); $act6 = $flag("AG");
            $act7 = $flag("AH"); $act8 = $flag("AI"); $act9 = $flag("AJ");
            $flag_act_1_4_7_8_9 = ($act1 || $act4 || $act7 || $act8 || $act9) ? 1 : 0;
            $flag_act_2_3_5_6   = ($act2 || $act3 || $act5 || $act6) ? 1 : 0;

            $excel_year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
            
            $raw_prov = trim($sheet->getCell("E" . $row)->getCalculatedValue() ?? '');
            // Strip "code | " prefix from dropdown (e.g., "01 | Vientiane Capital" → "Vientiane Capital")
            $raw_prov = preg_replace('/^\d+\s*\|\s*/', '', $raw_prov);
            // Strip "Province"/"Prefecture" suffix so "Vientiane Capital Province" → "Vientiane Capital"
            // BUT preserve "Vientiane Province" which has distinct meaning from Vientiane Capital
            $stripped_prov = preg_replace('/\s+(Province|Prefecture)\s*$/i', '', $raw_prov);
            if (strtoupper($stripped_prov) !== 'VIENTIANE') {
                $raw_prov = $stripped_prov;
            }
            $raw_dist = trim($sheet->getCell("F" . $row)->getCalculatedValue() ?? '');
            $raw_dist = preg_replace('/^\d+\s*\|\s*/', '', $raw_dist);
            $raw_sect = trim($sheet->getCell("H" . $row)->getCalculatedValue() ?? '');
            $raw_sect = preg_replace('/^\d+\s*\|\s*/', '', $raw_sect);
            // Parse Investment Zone (column I): "Zone 1", "Zone 2", "Zone 3" or empty
            $zoneVal = trim($sheet->getCell("I" . $row)->getCalculatedValue() ?? '');
            $zone_1 = ($zoneVal === 'Zone 1') ? 1 : 0;
            $zone_2 = ($zoneVal === 'Zone 2') ? 1 : 0;
            $zone_3 = ($zoneVal === 'Zone 3') ? 1 : 0;

            $upper_prov = strtoupper($raw_prov);
            $prov_match = $prov_map[$upper_prov] ?? null;
            if (!$prov_match && isset($prov_aliases[$upper_prov])) {
                $alias_pro_id = $prov_aliases[$upper_prov];
                foreach ($prov_rows as $pr) {
                    if ($pr['pro_id'] == $alias_pro_id) {
                        $prov_match = ['pro_id' => $pr['pro_id'], 'name' => $pr['pro_name']];
                        break;
                    }
                }
            }
            if (!$prov_match && strlen($upper_prov) >= 3) {
                $best_score = 999; $best_match = null;
                foreach ($prov_map as $pname => $pdata) {
                    $score = levenshtein($upper_prov, $pname);
                    if ($score < $best_score) { $best_score = $score; $best_match = $pdata; }
                }
                if ($best_score <= 3 && $best_match) $prov_match = $best_match;
            }
            $pro_id = $prov_match['pro_id'] ?? null;
            if (!$pro_id && !empty($raw_prov)) { 
                $unmapped_prov++; 
                $error_log[] = "Row $row: Unknown Province '$raw_prov'";
            }
            $official_province = $prov_match['name'] ?? $raw_prov;

            $dis_id = null;
            if ($pro_id && !empty($raw_dist)) {
                $clean_dist = preg_replace('/\s+District$/i', '', trim($raw_dist));
                $upper_dist = strtoupper($clean_dist);
                $dis_id = $dist_map[$pro_id . '|' . $upper_dist] ?? null;
                if (!$dis_id && isset($dist_by_province[$pro_id])) {
                    $best_score = 999; $best_dis_id = null;
                    foreach ($dist_by_province[$pro_id] as $dd) {
                        $score = levenshtein($upper_dist, $dd['name']);
                        if ($score < $best_score) { $best_score = $score; $best_dis_id = $dd['dis_id']; }
                    }
                    if ($best_score <= 3 && $best_dis_id) $dis_id = $best_dis_id;
                }
            }
            if (!$dis_id && !empty($raw_dist)) {
                $unmapped_dist++;
                $error_log[] = "Row $row: Unknown District '$raw_dist' in Province '$official_province'";
            }

            $official_district = '';
            if ($dis_id) {
                $official_district = $pdo->query("SELECT district_name FROM districts WHERE district_code = " . $pdo->quote($dis_id))->fetchColumn();
            }

            $sector_id = $sect_map[strtoupper($raw_sect)] ?? null;
            if (!$sector_id && !empty($raw_sect)) {
                // Try sector alias table
                $alias_key = strtoupper($raw_sect);
                $alias_target = $sect_aliases[$alias_key] ?? null;
                if ($alias_target) {
                    $sector_id = $sect_map[strtoupper($alias_target)] ?? null;
                }
            }
            if (!$sector_id && !empty($raw_sect)) {
                // Fuzzy match via levenshtein
                $upper_sect = strtoupper(trim($raw_sect));
                $best_score = 999; $best_sect_id = null; $best_sect_name = '';
                foreach ($sect_map as $sname => $sid) {
                    $score = levenshtein($upper_sect, $sname);
                    if ($score < $best_score) { $best_score = $score; $best_sect_id = $sid; $best_sect_name = $sname; }
                }
                $threshold = strlen($upper_sect) > 10 ? 5 : 3;
                if ($best_score <= $threshold && $best_sect_id) {
                    $sector_id = $best_sect_id;
                    $raw_sect = $best_sect_name; // use official name
                }
            }
            if (!$sector_id && !empty($raw_sect)) {
                $unmapped_sect++;
                $error_log[] = "Row $row: Unknown Sector '$raw_sect'";
            }

            $data = [
                "import_batch_id"             => $batch_id,
                "tax_year"                    => $excel_year > 0 ? $excel_year : $tax_year,
                "tin"                         => $tin,
                "company_name"                => $sheet->getCell("C" . $row)->getCalculatedValue(),
                "pro_id"                      => $pro_id,
                "province"                    => $official_province,
                "dis_id"                      => $dis_id,
                "district"                    => $official_district ?: $raw_dist,
                "sector_id"                   => $sector_id,
                "sector"                      => $raw_sect,
                "zone_1"                      => $zone_1,
                "zone_2"                      => $zone_2,
                "zone_3"                      => $zone_3,
                "revenue"                     => (float)$sheet->getCell("J" . $row)->getCalculatedValue(),
                "expense"                     => (float)$sheet->getCell("K" . $row)->getCalculatedValue(),
                "net_profit"                  => (float)$sheet->getCell("L" . $row)->getCalculatedValue(),
                "pt_paid"                     => (float)$sheet->getCell("M" . $row)->getCalculatedValue(),
                "loss_carryforward"           => (float)$sheet->getCell("N" . $row)->getCalculatedValue(),
                "re_invested_profit"          => (float)$sheet->getCell("O" . $row)->getCalculatedValue(),
                "reinvest_date"               => $dateVal("P"),
                "registration_date"           => $dateVal("Q"),
                "tax_holiday_years"           => (int)$sheet->getCell("R" . $row)->getCalculatedValue(),
                "investment_license_date"     => $dateVal("D"),
                "flag_hr_dev"                 => $flag("T"),
                "flag_eco_friendly"           => $flag("U"),
                "flag_sez_developer"          => $flag("V"),
                "flag_sez_investor"           => $flag("W"),
                "flag_act_production_services" => $flag("X"),
                "flag_public_benefit"         => $flag("Y"),
                "flag_compliant_rental"       => $flag("Z"),
                "flag_real_estate_transfer"   => $flag("AA"),
                "flag_act_1_4_7_8_9"         => $flag_act_1_4_7_8_9,
                "flag_act_2_3_5_6"           => $flag_act_2_3_5_6,
                "is_vat_holder"               => $flag("S"),
                "reinvest_amount"             => (float)$sheet->getCell("O" . $row)->getCalculatedValue(), // Same as re-invested profit
                "total_assets"                => (float)$sheet->getCell("AK" . $row)->getCalculatedValue() * 1000000000,
                "annual_turnover"             => (float)$sheet->getCell("AL" . $row)->getCalculatedValue() * 1000000000,
                "staff_count"                 => (int)$sheet->getCell("AM" . $row)->getCalculatedValue(),
                "stock_exchange_listing_date" => $dateVal("AN"),
                // Expert TE: not in the standard import template; remains NULL
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO companies ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }

        $message = "<strong>Import Success!</strong> Imported $imported companies.<br><br>";
        $message .= "<strong>Validation Report:</strong><br>";
        $message .= ($unmapped_prov == 0 ? "✅ All Provinces mapped.<br>" : "⚠️ $unmapped_prov unknown Provinces.<br>");
        $message .= ($unmapped_dist == 0 ? "✅ All Districts mapped.<br>" : "⚠️ $unmapped_dist unknown Districts.<br>");
        $message .= ($unmapped_sect == 0 ? "✅ All Sectors mapped.<br>" : "⚠️ $unmapped_sect unknown Sectors.<br>");
        
        if (!empty($error_log)) {
            $log_content = "IMPORT DIAGNOSTIC LOG - " . date("Y-m-d H:i:s") . "
\n";
            $log_content .= "Batch: $batch_id
\n";
            $log_content .= "----------------------------------------
\n";
            $log_content .= implode("
\n", $error_log);
            
            // Save to persistent file
            if (!is_dir(__DIR__ . "/../data/logs")) mkdir(__DIR__ . "/../data/logs", 0777, true);
            file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);

            $message .= "<br><a href='download_log.php?log_id=$batch_id' target='_blank' class='btn btn-sm btn-outline-danger mt-2'><i class='fas fa-download me-1'></i> Download Detailed Error Log</a>";
        }
    endif; // $ok (skip main import if duplicates found)
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$recent = $pdo->query("SELECT import_batch_id, 
                       COUNT(*) as total_rows,
                       MIN(tax_year) as min_year,
                       MAX(tax_year) as max_year,
                       GROUP_CONCAT(DISTINCT tax_year ORDER BY tax_year SEPARATOR ',') as year_list,
                       MAX(id) as lid 
                       FROM companies 
                       GROUP BY import_batch_id 
                       ORDER BY lid DESC 
                       LIMIT 15")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2"></i> CIT Data Import</h2>
    <p class="text-muted">Upload the CIT Excel template or manage manual entries.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-start border-4 border-<?= $msg_type ?>">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-upload me-2"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Tax Year</label>
            <select name="tax_year" class="form-select" required>
              <?php for ($y = date("Y"); $y >= 2015; $y--): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2 small">
                <a href="generate_cit_template.php" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download Template</a>
            </div>
          </div>
          <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg" id="importBtn">Import</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-4 text-center text-muted">No data imported yet.</div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch / Source</th><th>Year</th><th>Rows</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): 
                $years = explode(',', $r["year_list"]); 
                $is_manual = (strpos($r["import_batch_id"], 'MANUAL') !== false); 
                $log_file = __DIR__ . "/../data/logs/" . $r["import_batch_id"] . ".log";
                $has_log = file_exists($log_file);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace"><?= htmlspecialchars($r["import_batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td>
                    <?php if ($r["min_year"] == $r["max_year"]): ?>
                        <?= $r["min_year"] ?>
                    <?php else: ?>
                        <?php foreach ($years as $y): ?>
                        <span class="badge bg-secondary me-1"><?= $y ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-success rounded-pill"><?= $r["total_rows"] ?></span></td>
                <td>
                  <a href="view_companies.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <form method="POST" action="calculator.php" class="d-inline" 
                        onsubmit="var b=this.querySelector('button');b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>';b.disabled=true">
                    <input type="hidden" name="action" value="calculate">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["import_batch_id"]) ?>">
                    <input type="hidden" name="return_to" value="import_cit">
                    <button class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></button>
                  </form>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["import_batch_id"]) ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manual Data Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Select the Tax Year for the manual records you want to manage.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Tax Year</label>
          <select id="manualTaxYear" class="form-select">
            <?php for ($y = date("Y"); $y >= 2015; $y--): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="goToManualEntry()">Manage Records</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById("uploadForm").addEventListener("submit", function() {
    document.getElementById("importBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Importing...";
    document.getElementById("importBtn").disabled = true;
});

function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    window.location.href = `view_companies.php?batch=MANUAL_ENTRY_CIT_${year}_${stamp}&auto_add=1&year=${year}`;
}</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
