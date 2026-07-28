<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    try {
        $file = $_FILES["excel_file"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error.");
        }
        if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
            throw new Exception("Invalid file type.");
        }

        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getActiveSheet();
        $batch_id = "SEZINV_BATCH_" . date("YmdHis");
        $imported = 0; $skipped = 0;
        $error_log = [];
        $duplicate_log = [];
        $ok = true;

        // --- Phase 1: Duplicate Check ---
        $dup_check_rows = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $tin = trim($sheet->getCell("B" . $row)->getCalculatedValue() ?? '');
            if (empty($tin)) continue;
            $year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
            if ($year <= 0) continue;
            $dup_check_rows[] = ["row" => $row, "tin" => $tin, "year" => $year];
        }
        if (!empty($dup_check_rows)) {
            $ph = []; $prm = [];
            foreach ($dup_check_rows as $dr) { $ph[] = "(?, ?)"; $prm[] = $dr["tin"]; $prm[] = $dr["year"]; }
            $existing = $pdo->prepare("SELECT s.tin, s.tax_year, s.batch_id FROM import_sez_data s WHERE (s.tin, s.tax_year) IN (" . implode(", ", $ph) . ")");
            $existing->execute($prm);
            $dup_map = [];
            foreach ($existing->fetchAll() as $ex) { $key = $ex["tin"] . "|" . $ex["tax_year"]; if (!isset($dup_map[$key])) $dup_map[$key] = []; $dup_map[$key][] = $ex; }
            $dup_count = 0;
            foreach ($dup_check_rows as $dr) {
                $key = $dr["tin"] . "|" . $dr["year"];
                if (isset($dup_map[$key])) {
                    $dup_count++;
                    $batch_ids = array_unique(array_column($dup_map[$key], "batch_id"));
                    $error_log[] = "Row {$dr['row']}: TIN '{$dr['tin']}' / Year {$dr['year']} in batch(es): " . implode(", ", $batch_ids);
                    $duplicate_log[] = "{$dr['row']},{$dr['tin']},{$dr['year']},," . implode("; ", $batch_ids);
                }
            }
            if ($dup_count > 0) {
                file_put_contents(__DIR__ . "/../data/logs/{$batch_id}_duplicates.csv", "Row,TIN,Year,Existing Batch(es)\n" . implode("\n", $duplicate_log) . "\n");
                $message = "<div class='alert alert-danger'><strong>⛔ Import Blocked!</strong> Found <strong>$dup_count</strong> duplicate record(s).<br><br><a href='download_log.php?log_id={$batch_id}_duplicates' class='btn btn-sm btn-danger'><i class='fas fa-download me-1'></i> Download Duplicate Report (CSV)</a></div>";
                $log_content = "DUPLICATE CHECK LOG - " . date("Y-m-d H:i:s") . "\nBatch: $batch_id\n\n" . implode("\n", $error_log);
                file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);
                $message .= "<br><a href='download_log.php?log_id=$batch_id' class='btn btn-sm btn-outline-danger'><i class='fas fa-download me-1'></i> Download Error Log</a>";
                $ok = false;
            }
            unset($existing);
        }

        $normalizeHeader = function($value) {
            return strtolower(preg_replace('/[^a-z0-9]+/i', '', trim((string)($value ?? ''))));
        };
        $headerB = $normalizeHeader($sheet->getCell("B1")->getCalculatedValue());
        $headerC = $normalizeHeader($sheet->getCell("C1")->getCalculatedValue());
        $headerD = $normalizeHeader($sheet->getCell("D1")->getCalculatedValue());
        $isLegacyTemplate = (
            $headerB === 'amountconstructionroadelectricitywater' &&
            $headerC === 'amountconstructionotherinfrastructure' &&
            $headerD === 'yeardata'
        );
        $isNewTemplate = ($isLegacyTemplate === false && $normalizeHeader($sheet->getCell("T1")->getCalculatedValue() ?? '') === 'usercomment');
        $isOld6Col = ($isLegacyTemplate === false && $isNewTemplate === false);

        // --- Pre-load Dictionary for Smart Mapping ---
        $prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
        $dist_rows = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id")->fetchAll();

        $prov_map = [];
        $prov_code_map = [];
        foreach ($prov_rows as $r) {
            $prov_map[strtoupper(trim($r['pro_name']))] = ['id' => $r['pro_id'], 'name' => $r['pro_name']];
            $prov_code_map[strtoupper(trim($r['pro_id']))] = ['id' => $r['pro_id'], 'name' => $r['pro_name']];
        }
        $prov_aliases = [
            'VIENTIANE'          => '01',
            'BOLIKHAMSAI'        => '11', 'BOLIKHAMXAI'  => '11', 'BORIKHAMXAY' => '11',
            'XIANGKHOUANG'       => '09', 'XIENGKHOUANG' => '09',
            'BOKEO'              => '05', 'LUANGPRABANG'  => '06', 'LUANGPHRABANG' => '06',
            'LUANGNAMTHA'        => '03', 'OUDOMXAY'      => '04',
            'SAYABOURY'          => '08', 'SAYABURI'      => '08', 'XAYABOURY' => '08',
            'SARAVANE'           => '14', 'SEKONG'        => '15', 'XEKONG' => '15',
            'ATTAPEU'            => '17', 'ATTAPU'        => '17', 'XAISOMBOUN' => '18',
        ];
        $dist_map = [];
        $dist_code_map = [];
        $dist_by_province = [];
        foreach ($dist_rows as $r) {
            $dist_map[$r['pro_id'] . '|' . strtoupper(trim($r['dis_name']))] = $r['dis_id'];
            $dist_code_map[strtoupper(trim($r['dis_id']))] = [
                'id' => $r['dis_id'],
                'pro_id' => $r['pro_id'],
                'name' => $r['dis_name']
            ];
            $dist_by_province[$r['pro_id']][] = ['id' => $r['dis_id'], 'name' => strtoupper(trim($r['dis_name']))];
        }

        $highestRow = $sheet->getHighestRow();

        // Skip main import if duplicates found
        if ($ok):
        for ($row = 2; $row <= $highestRow; $row++) {
            $hasData = false;
            foreach (range('A', $isNewTemplate ? 'T' : ($isLegacyTemplate ? 'D' : 'G')) as $col) {
                $cellValue = trim((string)($sheet->getCell($col . $row)->getCalculatedValue() ?? ''));
                if ($cellValue !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }

            $tin = trim($sheet->getCell("B" . $row)->getCalculatedValue() ?? "");
            if (empty($tin)) {
                $skipped++;
                $error_log[] = "Row $row: Missing TIN";
                continue;
            }

            $num = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (is_numeric($v)) return (float)$v;
                return (float)str_replace(',', '', (string)($v ?? '0'));
            };

            $yn = function($col) use ($sheet, $row) {
                $v = strtolower(trim((string)($sheet->getCell($col . $row)->getCalculatedValue() ?? '')));
                return in_array($v, ['yes', 'y', '1', 'true']) ? 1 : 0;
            };

            $tax_year = $isLegacyTemplate
                ? (int)$sheet->getCell("D" . $row)->getCalculatedValue()
                : (int)$sheet->getCell("A" . $row)->getCalculatedValue();
            if ($tax_year <= 0) {
                $skipped++;
                $error_log[] = "Row $row: Missing or invalid Tax Year";
                continue;
            }

            // For new template, check if this row is an Investor record
            if ($isNewTemplate) {
                $isInv = $yn("J");
                if (!$isInv) {
                    $skipped++;
                    continue; // Skip non-investor rows
                }
            }

            // License Date
            $license_date_val = $isLegacyTemplate ? null : $sheet->getCell("D" . $row)->getCalculatedValue();
            $license_date = null;
            if (is_numeric($license_date_val)) {
                try { $license_date = Date::excelToDateTimeObject($license_date_val)->format("Y-m-d"); } catch (Exception $e) {}
            } elseif (!empty($license_date_val)) {
                try { $license_date = (new DateTime((string)$license_date_val))->format("Y-m-d"); } catch (Exception $e) {}
            }

            // Province & District resolution (same logic for all template types)
            $raw_prov = '';
            $raw_dist = '';
            if ($isLegacyTemplate) {
                $raw_prov = trim($sheet->getCell("A" . $row)->getCalculatedValue() ?? '');
            } elseif ($isNewTemplate) {
                $raw_prov = trim($sheet->getCell("E" . $row)->getCalculatedValue() ?? '');
                $raw_dist = trim($sheet->getCell("F" . $row)->getCalculatedValue() ?? '');
            } else {
                $raw_prov = trim($sheet->getCell("D" . $row)->getCalculatedValue() ?? '');
                $raw_dist = trim($sheet->getCell("E" . $row)->getCalculatedValue() ?? '');
            }

            if (preg_match('/^([0-9A-Za-z]+)\s*\|/', $raw_prov, $m)) { $raw_prov = $m[1]; }
            if (preg_match('/^([0-9A-Za-z]+)\s*\|/', $raw_dist, $m)) { $raw_dist = $m[1]; }

            // Province Resolution (same as before)
            $upper_prov = strtoupper($raw_prov);
            $prov_match = $prov_code_map[$upper_prov] ?? ($prov_map[$upper_prov] ?? null);
            if (!$prov_match && isset($prov_aliases[$upper_prov])) {
                $alias_id = $prov_aliases[$upper_prov];
                foreach ($prov_rows as $pr) {
                    if ($pr['pro_id'] == $alias_id) { $prov_match = ['id' => $pr['pro_id'], 'name' => $pr['pro_name']]; break; }
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
            $pro_id = $prov_match['id'] ?? null;
            $official_province = $prov_match['name'] ?? $raw_prov;
            if (!$pro_id && !empty($raw_prov)) {
                $error_log[] = "Row $row: Unknown Province '$raw_prov'";
            }

            // District Resolution (same as before)
            $dis_id = null;
            $official_district = $raw_dist;
            $upper_raw_dist = strtoupper($raw_dist);
            if (!empty($upper_raw_dist) && isset($dist_code_map[$upper_raw_dist])) {
                $district_match = $dist_code_map[$upper_raw_dist];
                $dis_id = $district_match['id'];
                $official_district = $district_match['name'];
                if (!$pro_id && !empty($district_match['pro_id']) && isset($prov_code_map[strtoupper($district_match['pro_id'])])) {
                    $prov_match = $prov_code_map[strtoupper($district_match['pro_id'])];
                    $pro_id = $prov_match['id'];
                    $official_province = $prov_match['name'];
                }
            } elseif ($pro_id && !empty($raw_dist)) {
                $clean_dist = preg_replace('/\s+District$/i', '', $raw_dist);
                $upper_dist = strtoupper($clean_dist);
                $dis_id = $dist_map[$pro_id . '|' . $upper_dist] ?? null;
                if (!$dis_id && isset($dist_by_province[$pro_id])) {
                    $best_score = 999; $best_dis = null;
                    foreach ($dist_by_province[$pro_id] as $dd) {
                        $score = levenshtein($upper_dist, $dd['name']);
                        if ($score < $best_score) { $best_score = $score; $best_dis = $dd['id']; }
                    }
                    if ($best_score <= 3) $dis_id = $best_dis;
                }
            }
            if (!$dis_id && !empty($raw_dist)) {
                $error_log[] = "Row $row: Unknown District '$raw_dist' in Province '$official_province'";
            }
            if ($dis_id) {
                $official_district = $pdo->query("SELECT district_name FROM districts WHERE district_code = " . $pdo->quote($dis_id))->fetchColumn();
            }

            // Build data array based on template type
            if ($isNewTemplate) {
                $data = [
                    "batch_id"           => $batch_id,
                    "tax_year"           => $tax_year,
                    "tin"                => $tin,
                    "company_name"       => trim($sheet->getCell("C" . $row)->getCalculatedValue() ?? ''),
                    "license_date"       => $license_date,
                    "pro_id"             => $pro_id,
                    "province"           => $official_province,
                    "dis_id"             => $dis_id,
                    "district"           => $official_district ?: $raw_dist,
                    "village"            => trim($sheet->getCell("G" . $row)->getCalculatedValue() ?? ''),
                    "sez_name"           => trim($sheet->getCell("H" . $row)->getCalculatedValue() ?? ''),
                    "sez_developer"      => $yn("I"),
                    "sez_investor"       => $yn("J"),
                    "sector"             => trim($sheet->getCell("K" . $row)->getCalculatedValue() ?? ''),
                    "amount_infra_basic" => $num("L"),
                    "amount_infra_other" => $num("M"),
                    "amount_utility_usage" => $num("N"),
                    "amount_infra_dev"   => $num("O"),
                    "use_user_fallback"  => $yn("P"),
                    "user_benchmark_rate" => $num("Q") ?: null,
                    "user_te"            => $num("R") ?: null,
                    "user_fallback_reason" => trim($sheet->getCell("S" . $row)->getCalculatedValue() ?? ''),
                    "user_comment"       => trim($sheet->getCell("T" . $row)->getCalculatedValue() ?? ''),
                    "type"               => 'Investor',
                ];
            } elseif ($isLegacyTemplate) {
                $data = [
                    "batch_id"           => $batch_id,
                    "tax_year"           => $tax_year,
                    "tin"                => $tin,
                    "license_date"       => $license_date,
                    "pro_id"             => $pro_id,
                    "province"           => $official_province,
                    "dis_id"             => $dis_id,
                    "district"           => $official_district ?: $raw_dist,
                    "type"               => 'Developer',
                    "amount_infra_basic" => $num("B"),
                    "amount_infra_other" => $num("C")
                ];
            } else { // Old 6-col template
                $data = [
                    "batch_id"           => $batch_id,
                    "tax_year"           => $tax_year,
                    "tin"                => $tin,
                    "license_date"       => $license_date,
                    "pro_id"             => $pro_id,
                    "province"           => $official_province,
                    "dis_id"             => $dis_id,
                    "district"           => $official_district ?: $raw_dist,
                    "type"               => 'Developer',
                    "amount_infra_basic" => $num("F"),
                    "amount_infra_other" => $num("G")
                ];
            }

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO import_sez_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }
        endif; // $ok (skip main import if duplicates found)

        if ($imported > 0) {
            $message = "<strong>Import Success!</strong> Imported $imported records.<br>";
            if ($skipped > 0) $message .= "Skipped $skipped rows (non-investor records or missing critical fields).<br>";
            $message .= "<a href='view_sez_inv.php?batch=" . urlencode($batch_id) . "' class='btn btn-sm btn-outline-primary mt-2 me-2'><i class='fas fa-eye me-1'></i> View Imported Data</a>";
            $message .= "<a href='te_sez_inv.php?batch=" . urlencode($batch_id) . "' class='btn btn-sm btn-outline-success mt-2 me-2'><i class='fas fa-calculator me-1'></i> Open TE Calculation</a>";
        } else {
            $message = "<strong>No SEZ Investor records imported.</strong><br>";
            if ($isNewTemplate) $message .= "Only records with SEZ Investor = Yes (column J) are imported. ";
            $message .= "The uploaded file may contain only headers or no developer records.";
            $msg_type = "warning";
        }

        if (!empty($error_log)) {
            $log_content = "IMPORT DIAGNOSTIC LOG - " . date("Y-m-d H:i:s") . "\r\n";
            $log_content .= "Batch: $batch_id\r\n";
            $log_content .= "----------------------------------------\r\n";
            $log_content .= implode("\r\n", $error_log);

            if (!is_dir(__DIR__ . "/../data/logs")) mkdir(__DIR__ . "/../data/logs", 0777, true);
            file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);

            $message .= "<br><a href='download_log.php?log_id=" . urlencode($batch_id) . "' target='_blank' class='btn btn-sm btn-outline-danger mt-2'><i class='fas fa-download me-1'></i> Download Error Log</a>";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT batch_id, MAX(tax_year) as tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_sez_data WHERE type='Investor' GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2"></i> SEZ Investor Data Import</h2>
    <p class="text-muted">Upload the SEZ Investor Excel template to import utility/infrastructure usage data.</p>
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
    <div class="card shadow-sm border-0 border-top border-4 border-info">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-info"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2 small">
                <a href="generate_sez_vat_template.php" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download SEZ VAT Template (v1.0)</a>
            </div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-info text-white btn-lg shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3 shadow-sm">
      <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-table me-2"></i> Excel Column Mapping</div>
      <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light"><tr><th>Col</th><th>Field</th><th>Type</th></tr></thead>
          <tbody>
            <tr class="table-secondary"><td colspan="3" class="fw-bold small">PRIMARY REQUIRED</td></tr>
            <tr><td>A</td><td>Tax Year</td><td>Number</td></tr>
            <tr><td>B</td><td>TIN</td><td>Text</td></tr>
            <tr><td>C</td><td>Company Name</td><td>Text</td></tr>
            <tr><td>D</td><td>License Date</td><td>Date</td></tr>
            <tr><td>E</td><td>Province</td><td>Dropdown</td></tr>
            <tr><td>F</td><td>District</td><td>Dropdown</td></tr>
            <tr class="table-info"><td colspan="3" class="fw-bold small">PRIMARY CONDITIONAL — Infrastructure Amounts</td></tr>
            <tr><td>G</td><td>Village</td><td>Text</td></tr>
            <tr><td>H</td><td>SEZ name</td><td>Text</td></tr>
            <tr><td>J</td><td>SEZ Investor</td><td>Yes/No</td></tr>
            <tr><td>J</td><td>SEZ Investor</td><td>Yes/No</td></tr>
            <tr><td>K</td><td>Sector</td><td>Text</td></tr>
            <tr><td>L</td><td>Basic Infrastructure LAK</td><td>Numeric</td></tr>
            <tr><td>M</td><td>Other Infrastructure LAK</td><td>Numeric</td></tr>
            <tr><td>N</td><td>Utility Usage LAK</td><td>Numeric</td></tr>
            <tr><td>O</td><td>Support Infrastructure LAK</td><td>Numeric</td></tr>
            <tr class="table-warning"><td colspan="3" class="fw-bold small">USER FALLBACK</td></tr>
            <tr><td>P</td><td>Use User Fallback?</td><td>Yes/No</td></tr>
            <tr><td>Q</td><td>User Benchmark Rate</td><td>Numeric</td></tr>
            <tr><td>R</td><td>User TE</td><td>Numeric</td></tr>
            <tr><td>S</td><td>User Fallback Reason</td><td>Text</td></tr>
            <tr><td>T</td><td>User Comment</td><td>Text</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No SEZ Investor Data Found</h5>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch / Source</th><th>Year</th><th>Records</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <?php
                $is_manual = (strpos($r["batch_id"], 'MANUAL') !== false);
                $log_file = __DIR__ . "/../data/logs/" . $r["batch_id"] . ".log";
                $has_log = file_exists($log_file);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace text-info"><?= htmlspecialchars($r["batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><?= $r["tax_year"] ?></td>
                <td><span class="badge bg-success rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_sez_inv.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_sez_inv.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="sez_inv">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["batch_id"]) ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
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
        <h5 class="modal-title">Manual Data Entry for SEZ Investors</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Select the Tax Year for the manual records you want to manage.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Tax Year</label>
          <select id="manualTaxYear" class="form-select">
            <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-info text-white" onclick="goToManualEntry()">Manage Records</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById("uploadForm").addEventListener("submit", function() {
    let btn = document.getElementById("importBtn");
    btn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Importing...";
    btn.classList.add("disabled");
});

function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    window.location.href = `view_sez_inv.php?batch=MANUAL_ENTRY_SEZINV_${year}_${stamp}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
