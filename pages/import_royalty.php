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
        $batch_id = "BATCH_ROYALTY_" . date("YmdHis");
        $imported = 0; $skipped = 0;
        $error_log = [];

        // Column mapping: A=TIN, B=Tax Year/License Date, C=Sale Value, D=Rate, E=Fee
        $resource_types = $pdo->query("SELECT item_no, item_name FROM bm_natural_resource WHERE active = 1")->fetchAll();
        $rt_map = [];
        foreach ($resource_types as $rt) {
            $rt_map[strtoupper(trim($rt['item_no']))] = true;
            $rt_map[strtoupper(trim($rt['item_name']))] = true;
        }
        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue() ?? "");
            if (empty($tin)) { $skipped++; continue; }

            $num = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (is_numeric($v)) return (float)$v;
                return (float)str_replace(',', '', (string)($v ?? '0'));
            };

            $b_val = $sheet->getCell("B" . $row)->getCalculatedValue();
            $license_date = null;
            $tax_year = null;

            // Column B may be a date (license_date) or a year number
            if (is_numeric($b_val)) {
                if ($b_val > 1900 && $b_val < 2100 && $b_val == (int)$b_val) {
                    $tax_year = (int)$b_val;
                } else {
                    try {
                        $dt = Date::excelToDateTimeObject($b_val);
                        $license_date = $dt->format("Y-m-d");
                        $tax_year = (int)$dt->format("Y");
                    } catch (Exception $e) {
                        $tax_year = (int)$b_val;
                    }
                }
            } else {
                $ts = strtotime($b_val);
                if ($ts !== false) {
                    $license_date = date("Y-m-d", $ts);
                    $tax_year = (int)date("Y", $ts);
                } else {
                    $tax_year = (int)$b_val;
                }
            }

            if (!$tax_year) $tax_year = (int)date("Y");

            $sale_value = $num("C");
            $actual_rate_val = $num("D");
            $fee_collected_val = $num("E");

            if ($sale_value <= 0) {
                $error_log[] = "Row $row: Non-positive Electricity Sale Value ($sale_value)";
            }
            if ($actual_rate_val <= 0) {
                $error_log[] = "Row $row: Non-positive Actual Rate ($actual_rate_val)";
            }
            if ($fee_collected_val < 0) {
                $error_log[] = "Row $row: Negative Fee Collected ($fee_collected_val)";
            }

            $data = [
                "batch_id"               => $batch_id,
                "tax_year"               => $tax_year,
                "tin"                    => $tin,
                "license_date"           => $license_date,
                "electricity_sale_value" => $sale_value,
                "actual_rate"            => $actual_rate_val,
                "fee_collected"          => $fee_collected_val
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO import_royalty_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }

        $message = "<strong>Import Success!</strong> Imported $imported records.<br>";
        $message .= "Skipped $skipped rows (missing TIN).<br>";

        if (!empty($error_log)) {
            $log_content = "IMPORT DIAGNOSTIC LOG - " . date("Y-m-d H:i:s") . "\r\n";
            $log_content .= "Batch: $batch_id\r\n";
            $log_content .= "----------------------------------------\r\n";
            $log_content .= implode("\r\n", $error_log);

            if (!is_dir(__DIR__ . "/../data/logs")) mkdir(__DIR__ . "/../data/logs", 0777, true);
            file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);

            $message .= "<br><a href='download_log.php?log_id=$batch_id' target='_blank' class='btn btn-sm btn-outline-danger mt-2'><i class='fas fa-download me-1'></i> Download Error Log</a>";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT batch_id, MAX(tax_year) as tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_royalty_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();
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
    <h2><i class="fas fa-file-import me-2 text-danger"></i> Non-Tax: Royalty Fee Data Import</h2>
    <p class="text-muted">Upload natural resource royalty fee collection data for Tax Expenditure estimation.</p>
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
    <div class="card shadow-sm border-0 border-top border-4 border-danger">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-danger"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2 small">
                <a href="<?= BASE_URL ?>/docs/Royalty%20fee_Template.xlsx" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download Template</a>
            </div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-danger text-white btn-lg shadow-sm fw-bold" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
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
            <tr><td>B</td><td>Tax Year / License Date</td></tr>
            <tr><td>C</td><td>Electricity Sale Value (LAK)</td></tr>
            <tr><td>D</td><td>Actual Rate (%)</td></tr>
            <tr><td>E</td><td>Fee Collected (LAK)</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Royalty Fee Data Found</h5>
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
                    <small class="font-monospace text-danger"><?= htmlspecialchars($r["batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><?= $r["tax_year"] ?></td>
                <td><span class="badge bg-danger text-white rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_royalty.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_royalty.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="royalty">
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
        <h5 class="modal-title">Manual Data Entry for Royalty Fee</h5>
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
        <button type="button" class="btn btn-danger text-white" onclick="goToManualEntry()">Manage Records</button>
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
    window.location.href = `view_royalty.php?batch=MANUAL_ENTRY_ROYALTY_${year}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
