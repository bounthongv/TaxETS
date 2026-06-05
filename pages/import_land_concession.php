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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    try {
        $file = $_FILES["excel_file"];
        $tax_year = (int)$_POST["tax_year"];
        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getActiveSheet();
        $batch_id = "LAND_BATCH_" . date("YmdHis");
        
        $imported = 0; $skipped = 0;
        $unmapped_prov = 0; $unmapped_dist = 0;
        $error_log = [];

        // 1. Pre-load Dictionary for Resolution
        $prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
        $dist_rows = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id")->fetchAll();
        
        $prov_map = [];
        foreach ($prov_rows as $r) {
            $prov_map[strtoupper(trim($r['pro_name']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']];
            $prov_map[strtoupper(trim($r['pro_id']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']];
        }
        $dist_map = []; 
        $dist_by_code = [];
        $dist_by_province = [];
        foreach ($dist_rows as $r) { 
            $dist_map[$r['pro_id'] . '|' . strtoupper(trim($r['dis_name']))] = $r['dis_id']; 
            $dist_by_code[strtoupper(trim($r['dis_id']))] = ['dis_id' => $r['dis_id'], 'pro_id' => $r['pro_id'], 'name' => $r['dis_name']];
            $dist_by_province[$r['pro_id']][] = ['dis_id' => $r['dis_id'], 'name' => strtoupper(trim($r['dis_name']))];
        }

        $normalizeHeader = function($value) {
            return strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$value));
        };
        $headers = [];
        for ($col = 1; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()); $col++) {
            $header = $normalizeHeader($sheet->getCellByColumnAndRow($col, 1)->getCalculatedValue());
            if ($header !== '') {
                $headers[$header] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            }
        }
        $colFor = function(array $names, string $fallback) use ($headers, $normalizeHeader) {
            foreach ($names as $name) {
                $key = $normalizeHeader($name);
                if (isset($headers[$key])) {
                    return $headers[$key];
                }
            }
            return $fallback;
        };

        $colTin = $colFor(["TIN"], "B");
        $colCompany = $colFor(["CompanyName", "Company Name"], "E");
        $colDistrict = $colFor(["District"], "C");
        $colProvince = $colFor(["Province"], "D");
        $colYear = $colFor(["Year", "Tax Year"], "");
        $colConfirmDate = $colFor(["Receiptdate", "Receipt Date", "Confirm Date"], "F");
        $colArea = $colFor(["Concessionarea", "Concession Area", "Concession Area (ha)"], "G");
        $colBenchmarkRate = $colFor(["BenchmarkRate", "Benchmark Rate", "Benchmark Rate (USD/ha)"], "H");
        $colContractedRate = $colFor(["ContractedRate", "Contracted Rate", "Contracted Rate (USD/ha)"], "I");
        $colFeePaid = $colFor(["ConcessionFeePaid", "FeePaid", "Concession Fee Paid", "Concession Fee Paid (USD)"], "J");
        $colBenchmarkValue = $colFor(["Benchmark Value", "Benchmark Value (USD)"], "");
        $colNonTaxTe = $colFor(["Non-Tax TE", "Non-Tax TE (USD)", "NonTaxTE"], "");
        $colProvisionName = $colFor(["ProvisionName", "Provision Name"], "M");

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $hasData = false;
            for ($colIndex = 1; $colIndex <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()); $colIndex++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $cellValue = trim((string)($sheet->getCell($col . $row)->getCalculatedValue() ?? ''));
                if ($cellValue !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }

            $tin = trim($sheet->getCell($colTin . $row)->getCalculatedValue() ?? '');
            if (empty($tin)) {
                $skipped++;
                $error_log[] = "Row $row: Missing TIN";
                continue;
            }

            $raw_prov = trim($sheet->getCell($colProvince . $row)->getCalculatedValue() ?? '');
            $raw_dist = trim($sheet->getCell($colDistrict . $row)->getCalculatedValue() ?? '');
            if (preg_match('/^([^|]+)\s*\|/', $raw_prov, $matches)) {
                $raw_prov = trim($matches[1]);
            }
            if (preg_match('/^([^|]+)\s*\|/', $raw_dist, $matches)) {
                $raw_dist = trim($matches[1]);
            }
            
            // Resolve IDs
            $upper_prov = strtoupper($raw_prov);
            $prov_match = $prov_map[$upper_prov] ?? null;
            
            // Fuzzy fallback for province
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
                $code_match = $dist_by_code[$upper_dist] ?? null;
                $dis_id = ($code_match && $code_match['pro_id'] === $pro_id) ? $code_match['dis_id'] : ($dist_map[$pro_id . '|' . $upper_dist] ?? null);
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

            $dateVal = function($col) use ($sheet, $row) {
                if ($col === "") return null;
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (!$v) return null;
                if (is_numeric($v)) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format("Y-m-d");
                $ts = strtotime((string)$v);
                return $ts === false ? null : date("Y-m-d", $ts);
            };
            $cellVal = function($col) use ($sheet, $row) {
                if ($col === "") return null;
                return $sheet->getCell($col . $row)->getCalculatedValue();
            };
            $numberVal = function($col) use ($cellVal) {
                $value = $cellVal($col);
                return is_numeric($value) ? (float)$value : 0.0;
            };
            $rowTaxYear = $colYear !== "" ? (int)$cellVal($colYear) : 0;
            if ($rowTaxYear <= 0) {
                $rowTaxYear = $tax_year;
            }

            $provisionName = $cellVal($colProvisionName);
            if (is_string($provisionName) && preg_match('/^[^|]+\|\s*(.+)$/', $provisionName, $matches)) {
                $provisionName = trim($matches[1]);
            }

            $data = [
                "import_batch_id" => $batch_id,
                "tax_year" => $rowTaxYear,
                "tin" => $tin,
                "company_name" => $cellVal($colCompany),
                "pro_id" => $pro_id,
                "province" => $official_province,
                "dis_id" => $dis_id,
                "district" => $dis_id ? ($pdo->query("SELECT district_name FROM districts WHERE district_code = ". $pdo->quote($dis_id))->fetchColumn() ?: $raw_dist) : $raw_dist,
                "confirm_date" => $dateVal($colConfirmDate),
                "concession_area_ha" => $numberVal($colArea),
                "benchmark_rate_usd" => $numberVal($colBenchmarkRate),
                "contracted_rate_usd" => $numberVal($colContractedRate),
                "concession_fee_paid_usd" => $numberVal($colFeePaid),
                "benchmark_value_usd" => $numberVal($colBenchmarkValue),
                "non_tax_te_usd" => $numberVal($colNonTaxTe),
                "provision_name" => $provisionName,
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO repo_land_concession_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }

        if ($imported > 0) {
            $message = "<strong>Import Success!</strong> Imported $imported records.<br>";
            $message .= "Skipped $skipped rows.<br>";
            $message .= "Province mapping: " . ($unmapped_prov == 0 ? "all mapped" : "$unmapped_prov unknown") . ".<br>";
            $message .= "District mapping: " . ($unmapped_dist == 0 ? "all mapped" : "$unmapped_dist unknown") . ".<br>";
            $message .= "<a href='repo_land_concession.php?batch=" . urlencode($batch_id) . "' class='btn btn-sm btn-outline-primary mt-2 me-2'><i class='fas fa-eye me-1'></i> View Imported Data</a>";
            $message .= "<a href='calculate_land_concession.php?batch=" . urlencode($batch_id) . "' class='btn btn-sm btn-outline-success mt-2 me-2'><i class='fas fa-calculator me-1'></i> Open TE Calculation</a>";
        } else {
            $message = "<strong>No Land Concession records imported.</strong><br>The uploaded workbook appears to contain only headers or no complete data rows.";
            $msg_type = "warning";
        }
        
        if (!empty($error_log)) {
            $log_content = "LAND CONCESSION IMPORT DIAGNOSTIC - " . date("Y-m-d H:i:s") . "\r\n";
            $log_content .= "Batch: $batch_id\r\n";
            $log_content .= "----------------------------------------\r\n";
            $log_content .= implode("\r\n", $error_log);
            
            // Save to persistent file
            if (!is_dir(__DIR__ . "/../data/logs")) mkdir(__DIR__ . "/../data/logs", 0777, true);
            file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);

            $message .= "<br><a href='download_log.php?log_id=" . urlencode($batch_id) . "' target='_blank' class='btn btn-sm btn-outline-danger mt-2'><i class='fas fa-download me-1'></i> Download Detailed Error Log</a>";
        }

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$recent = $pdo->query("SELECT import_batch_id, tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM repo_land_concession_data GROUP BY import_batch_id, tax_year ORDER BY lid DESC LIMIT 15")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-mountain me-2 text-success"></i> Land Concession Data Import</h2>
    <p class="text-muted">Import data requirements for Land Concession TE estimation.</p>
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
                <a href="generate_land_concession_template.php" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download Template</a>
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
              <?php foreach ($recent as $r): ?>
              <?php 
                $is_manual = (strpos($r["import_batch_id"], 'MANUAL') !== false); 
                $log_file = __DIR__ . "/../data/logs/" . $r["import_batch_id"] . ".log";
                $has_log = file_exists($log_file);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace"><?= htmlspecialchars($r["import_batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><?= $r["tax_year"] ?></td>
                <td><span class="badge bg-success rounded-pill"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="repo_land_concession.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="calculate_land_concession.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["import_batch_id"]) ?>">
                    <input type="hidden" name="type" value="land">
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
    window.location.href = `repo_land_concession.php?batch=MANUAL_ENTRY_LAND_${year}_${stamp}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
