<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
        $batch_id = "RESOURCE_BATCH_" . date("YmdHis");
        $imported = 0; $skipped = 0;
        $error_log = [];
        $duplicate_log = [];
        $ok = true;

        // --- Phase 1: Duplicate Check ---
        $highestRow = $sheet->getHighestRow();
        $dup_check_rows = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue() ?? "");
            if (empty($tin)) continue;
            // Determine tax year
            $isNewTemplate = (isset($headerQ) && $headerQ === 'usercomment');
            if (!$isNewTemplate) { $headerQ = $normalizeHeader($sheet->getCell("Q1")->getCalculatedValue() ?? ''); $isNewTemplate = ($headerQ === 'usercomment'); }
            $year = $isNewTemplate ? (int)$sheet->getCell("D" . $row)->getCalculatedValue() : (int)$sheet->getCell("F" . $row)->getCalculatedValue();
            if ($year <= 0) continue;
            $dup_check_rows[] = ["row" => $row, "tin" => $tin, "year" => $year];
        }
        if (!empty($dup_check_rows)) {
            $ph = []; $prm = [];
            foreach ($dup_check_rows as $dr) { $ph[] = "(?, ?)"; $prm[] = $dr["tin"]; $prm[] = $dr["year"]; }
            $existing = $pdo->prepare("SELECT r.tin, r.tax_year, r.batch_id FROM import_resource_data r WHERE (r.tin, r.tax_year) IN (" . implode(", ", $ph) . ")");
            $existing->execute($prm);
            $dup_map = [];
            foreach ($existing->fetchAll() as $ex) { $key = $ex["tin"] . "|" . $ex["tax_year"]; if (!isset($dup_map[$key])) $dup_map[$key] = []; $dup_map[$key][] = $ex; }
            $dup_count = 0;
            foreach ($dup_check_rows as $dr) {
                $key = $dr["tin"] . "|" . $dr["year"];
                if (isset($dup_map[$key])) { $dup_count++;
                    $bids = array_unique(array_column($dup_map[$key], "batch_id"));
                    $error_log[] = "Row {$dr['row']}: TIN '{$dr['tin']}' / Year {$dr['year']} in batch(es): " . implode(", ", $bids);
                    $duplicate_log[] = "{$dr['row']},{$dr['tin']},{$dr['year']},," . implode("; ", $bids);
                }
            }
            if ($dup_count > 0) {
                file_put_contents(__DIR__ . "/../data/logs/{$batch_id}_duplicates.csv", "Row,TIN,Year,Existing Batch(es)\n" . implode("\n", $duplicate_log) . "\n");
                $message = "<div class='alert alert-danger'><strong>⛔ Import Blocked!</strong> Found <strong>$dup_count</strong> duplicates.<br><br><a href='download_log.php?log_id={$batch_id}_duplicates' class='btn btn-sm btn-danger'><i class='fas fa-download me-1'></i> Download Report</a></div>";
                $log_content = "DUPLICATE CHECK LOG - " . date("Y-m-d H:i:s") . "\nBatch: $batch_id\n\n" . implode("\n", $error_log);
                file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);
                $message .= "<br><a href='download_log.php?log_id=$batch_id' class='btn btn-sm btn-outline-danger'><i class='fas fa-download me-1'></i> Download Error Log</a>";
                $ok = false;
            }
            unset($existing);
        }

        if ($ok):

        // Detect template type
        $normalizeHeader = function($value) {
            return strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$value));
        };
        $headerQ = $normalizeHeader($sheet->getCell("Q1")->getCalculatedValue() ?? '');
        $isNewTemplate = ($headerQ === 'usercomment');

        // Pre-load resource type dictionary for Smart Mapping
        $resource_types = $pdo->query("SELECT item_no, item_name FROM bm_natural_resource WHERE active = 1")->fetchAll();
        $rt_by_no = [];
        $rt_by_name = [];
        foreach ($resource_types as $rt) {
            $rt_by_no[strtoupper(trim($rt['item_no']))] = $rt['item_no'];
            $rt_by_name[strtoupper(trim($rt['item_name']))] = $rt['item_no'];
        }

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $hasData = false;
            foreach (range('A', $isNewTemplate ? 'Q' : 'F') as $col) {
                $cellValue = trim((string)($sheet->getCell($col . $row)->getCalculatedValue() ?? ''));
                if ($cellValue !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }

            $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue() ?? "");
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

            $dateVal = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (!$v) return null;
                if (is_numeric($v)) {
                    try { return Date::excelToDateTimeObject($v)->format("Y-m-d"); } catch (Exception $e) { return null; }
                }
                try { return (new DateTime((string)$v))->format("Y-m-d"); } catch (Exception $e) { return null; }
            };

            $license_date_val = $isNewTemplate ? $sheet->getCell("B" . $row)->getCalculatedValue() : $sheet->getCell("B" . $row)->getCalculatedValue();
            $license_date = $dateVal("B");

            $tax_year = $isNewTemplate ? (int)$sheet->getCell("D" . $row)->getCalculatedValue() : (int)$sheet->getCell("F" . $row)->getCalculatedValue();
            if ($tax_year <= 0) {
                $skipped++;
                $error_log[] = "Row $row: Missing or invalid Year";
                continue;
            }

            $raw_type = trim($sheet->getCell("C" . $row)->getCalculatedValue() ?? "");
            if (preg_match('/^([^|]+)\s*\|/', $raw_type, $matches)) {
                $raw_type = trim($matches[1]);
            }
            $upper_type = strtoupper($raw_type);
            $resolved_type = $rt_by_no[$upper_type] ?? $rt_by_name[$upper_type] ?? null;
            if (!$resolved_type && strlen($upper_type) >= 2) {
                $best_score = 999; $best_match = null;
                foreach ($rt_by_name as $name => $no) {
                    $score = levenshtein($upper_type, $name);
                    if ($score < $best_score) { $best_score = $score; $best_match = $no; }
                }
                if ($best_score <= 3 && $best_match) $resolved_type = $best_match;
            }
            if (!$resolved_type && !empty($raw_type)) {
                $error_log[] = "Row $row: Unknown Resource Type '$raw_type'";
            }
            $resource_type = $resolved_type ?: $raw_type;

            if ($isNewTemplate) {
                $bench_rate = $num("F");
                $contracted_rate = $num("G");
                $sale_qty = $num("H");
                $fee_collected_val = $num("I");
                $receipt_date = $dateVal("E");

                $data = [
                    "batch_id"      => $batch_id,
                    "tax_year"      => $tax_year,
                    "receipt_date"  => $receipt_date,
                    "tin"           => $tin,
                    "license_date"  => $license_date,
                    "resource_type" => $resource_type,
                    "actual_rate"   => $bench_rate,
                    "contracted_rate" => $contracted_rate,
                    "sale_quantity" => $sale_qty,
                    "fee_collected" => $fee_collected_val,
                    "paid_currency" => trim($sheet->getCell("J" . $row)->getCalculatedValue() ?? ''),
                    "exchange_rate" => $num("K") ?: null,
                    "use_user_fallback" => $yn("L"),
                    "user_benchmark_rate" => $num("M") ?: null,
                    "user_benchmark_fee"  => $num("N") ?: null,
                    "user_te"       => $num("O") ?: null,
                    "user_fallback_reason" => trim($sheet->getCell("P" . $row)->getCalculatedValue() ?? ''),
                    "user_comment"  => trim($sheet->getCell("Q" . $row)->getCalculatedValue() ?? ''),
                ];
            } else {
                $actual_rate_val = $num("D");
                $fee_collected_val = $num("E");
                if ($actual_rate_val <= 0) {
                    $error_log[] = "Row $row: Non-positive Actual Rate ($actual_rate_val)";
                }
                if ($fee_collected_val < 0) {
                    $error_log[] = "Row $row: Negative Fee Collected ($fee_collected_val)";
                }
                $data = [
                    "batch_id"      => $batch_id,
                    "tax_year"      => $tax_year,
                    "tin"           => $tin,
                    "license_date"  => $license_date,
                    "resource_type" => $resource_type,
                    "actual_rate"   => $actual_rate_val,
                    "fee_collected" => $fee_collected_val,
                ];
            }

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO import_resource_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }
        endif; // $ok (skip main import if duplicates found)

        if ($imported > 0) {
            $message = "<strong>Import Success!</strong> Imported $imported records.<br>";
            $message .= "Skipped $skipped rows.<br>";
            $message .= "<a href='view_resource.php?batch=" . urlencode($batch_id) . "' class='btn btn-sm btn-outline-primary mt-2 me-2'><i class='fas fa-eye me-1'></i> View Imported Data</a>";
            $message .= "<a href='te_nontax.php?batch=" . urlencode($batch_id) . "' class='btn btn-sm btn-outline-success mt-2 me-2'><i class='fas fa-calculator me-1'></i> Open TE Calculation</a>";
        } else {
            $message = "<strong>No Resource Fee records imported.</strong><br>The uploaded workbook appears to contain only headers or no complete data rows.";
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
$recent = $pdo->query("SELECT batch_id, MAX(tax_year) as tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_resource_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();
$log_check = [];
if (!empty($recent)) {
    foreach ($recent as $r) {
        if (file_exists(__DIR__ . "/../data/logs/" . $r["batch_id"] . ".log")) {
            $log_check[$r["batch_id"]] = true;
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2 text-warning"></i> Non-Tax: Resource Fee Data Import</h2>
    <p class="text-muted">Upload natural resource fee collection data for Tax Expenditure estimation.</p>
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
    <div class="card shadow-sm border-0 border-top border-4 border-warning">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-warning"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2 small">
                <a href="generate_resource_fee_template.php" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download Resource Fee Template (v1.0)</a>
            </div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-warning text-dark btn-lg shadow-sm fw-bold" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3 shadow-sm">
      <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-table me-2"></i> Excel Column Mapping</div>
      <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light"><tr><th>Col</th><th>Field</th></tr></thead>
          <tbody>
            <tr><td>A</td><td>TIN</td></tr>
            <tr><td>B</td><td>Investment License Date</td></tr>
            <tr><td>C</td><td>Natural Resource Type</td></tr>
            <tr><td>D</td><td>Resource Fee Rate (%)</td></tr>
            <tr><td>E</td><td>Resource Fee Collected (LAK)</td></tr>
            <tr><td>F</td><td>Tax Year</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Resource Fee Data Found</h5>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch / Source</th><th>Year</th><th>Records</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <?php
                $is_manual = (strpos($r["batch_id"], 'MANUAL') !== false);
                $has_log = isset($log_check[$r["batch_id"]]);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace text-warning"><?= htmlspecialchars($r["batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><?= $r["tax_year"] ?></td>
                <td><span class="badge bg-warning text-dark rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_resource.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_nontax.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="resource">
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
        <h5 class="modal-title">Manual Data Entry for Resource Fee</h5>
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
        <button type="button" class="btn btn-warning text-dark" onclick="goToManualEntry()">Manage Records</button>
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
    window.location.href = `view_resource.php?batch=MANUAL_ENTRY_RESOURCE_${year}_${stamp}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
