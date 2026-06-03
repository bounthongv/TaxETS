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

// Migration: derive tax_year from filing_period for existing records
$pdo->exec("UPDATE import_salary_tax_data SET tax_year = REGEXP_SUBSTR(filing_period, '[0-9]{4}') WHERE (tax_year IS NULL OR tax_year = 0) AND filing_period REGEXP '[0-9]{4}'");

$message = "";
$msg_type = "success";

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
        $batch_id = "SALARY_BATCH_" . date("YmdHis");
        $imported = 0; $skipped = 0;
        $error_log = [];

        $max_row = $sheet->getHighestRow();
        $empty_streak = 0;
        for ($row = 2; $row <= $max_row; $row++) {
            $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue() ?? "");

            $is_empty_row = true;
            foreach (range("A", "N") as $col) {
                $cell_value = $sheet->getCell($col . $row)->getCalculatedValue();
                if (trim((string)($cell_value ?? "")) !== "") {
                    $is_empty_row = false;
                    break;
                }
            }

            if ($is_empty_row) {
                $empty_streak++;
                if ($empty_streak >= 20) break; // Stop after trailing empty template rows.
                continue;
            }

            if (empty($tin)) {
                $empty_streak = 0;
                $skipped++;
                $error_log[] = "Row $row: TIN is required";
                continue;
            }
            $empty_streak = 0;

            $num = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (is_numeric($v)) return (float)$v;
                return (float)str_replace(',', '', (string)($v ?? '0'));
            };

            $input_date_val = $sheet->getCell("E" . $row)->getCalculatedValue();
            $input_date = null;
            if (is_numeric($input_date_val)) {
                try {
                    $d = Date::excelToDateTimeObject($input_date_val);
                    $input_date = $d->format("Y-m-d");
                } catch (Exception $e) {}
            } elseif (!empty($input_date_val)) {
                try {
                    $d = new DateTime((string)$input_date_val);
                    $input_date = $d->format("Y-m-d");
                } catch (Exception $e) {
                    $error_log[] = "Row $row: Invalid input date '" . $input_date_val . "'";
                }
            }

            $b_val = $sheet->getCell("B" . $row)->getCalculatedValue();
            $period = trim($sheet->getCell("D" . $row)->getCalculatedValue() ?? "");
            $row_year = $b_val ? (int)$b_val : 0;
            if ($row_year <= 0 && preg_match('#(\d{4})#', $period, $m)) {
                $row_year = (int)$m[1];
            }
            if ($row_year <= 0) {
                $row_year = $tax_year;
            }

            $data = [
                "batch_id"                  => $batch_id,
                "tax_year"                  => $row_year,
                "tin"                       => $tin,
                "filing_type"               => $sheet->getCell("C" . $row)->getCalculatedValue(),
                "filing_period"             => $period,
                "input_date"                => $input_date,
                "total_salaries_wages_cash" => $num("F"),
                "other_fringe_benefits"     => $num("G"),
                "total_taxable_amount"      => $num("H"),
                "tax_exempt_amount"         => $num("I"),
                "tax_amount"                => $num("J"),
                "adjustment_amount"         => $num("K"),
                "carryforward_amount"       => $num("L"),
                "total_amount_due"          => $num("M"),
                "provision_number"          => trim($sheet->getCell("N" . $row)->getCalculatedValue() ?? "")
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO import_salary_tax_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }

        if ($imported > 0) {
            $message = "<strong>Import Success!</strong> Imported $imported records.<br>";
        } else {
            $message = "<strong>No Salary Tax records imported.</strong> Add data rows to the template and upload again.<br>";
            $msg_type = "warning";
        }
        if ($skipped > 0) {
            $message .= "Warning: Skipped $skipped row(s) with data but no TIN.<br>";
        }
        $message .= "Processed up to row " . ($row - 1) . " of $max_row total rows in sheet.<br>";

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
$recent = $pdo->query("SELECT batch_id, MIN(tax_year) as tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_salary_tax_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2 text-success"></i> Salary Tax Data Import</h2>
    <p class="text-muted">Upload monthly salary tax filings from the standardized Excel template.</p>
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
    <div class="card shadow-sm border-0 border-top border-4 border-success">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-success"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Tax Year</label>
            <select name="tax_year" class="form-select" required>
              <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
              <option value="<?= $y ?>"><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-bold mb-0">Excel File (.xlsx)</label>
                <a href="generate_salary_template.php" class="btn btn-sm btn-link text-success p-0 text-decoration-none fw-bold small"><i class="fas fa-download me-1"></i> Download Template</a>
            </div>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-success btn-lg shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
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
            <tr><td>B</td><td>Tax Year <small class="text-muted">(optional — derived from Period)</small></td></tr>
            <tr><td>C</td><td>Filing Type (Monthly/Yearly)</td></tr>
            <tr><td>D</td><td>Filing Period (MM/YYYY)</td></tr>
            <tr><td>E</td><td>Input Date</td></tr>
            <tr><td>F</td><td>Salaries & Wages (Cash)</td></tr>
            <tr><td>G</td><td>Other Fringe Benefits</td></tr>
            <tr><td>H</td><td>Total Taxable Amount</td></tr>
            <tr><td>I</td><td>Tax Exempt Amount</td></tr>
            <tr><td>J</td><td>Tax Amount Paid</td></tr>
            <tr><td>K</td><td>Adjustment Amount</td></tr>
            <tr><td>L</td><td>Carryforward Amount</td></tr>
            <tr><td>M</td><td>Total Amount Due</td></tr>
            <tr><td>N</td><td>Provision Number (e.g. T21)</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Salary Tax Data Found</h5>
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
                    <small class="font-monospace"><?= htmlspecialchars($r["batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><?= $r["tax_year"] ?></td>
                <td><span class="badge bg-success rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_salary.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_salary_tax.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="salary">
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
        <h5 class="modal-title">Manual Data Entry for Salary Tax</h5>
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
        <button type="button" class="btn btn-success" onclick="goToManualEntry()">Manage Records</button>
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
    window.location.href = `view_salary.php?batch=MANUAL_ENTRY_SALARY_${year}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
