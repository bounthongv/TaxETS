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
        $error_log = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $ptin = trim($sheet->getCell("C" . $row)->getCalculatedValue() ?? "");
            if (empty($ptin)) { $skipped++; continue; }

            $num = function($col) use ($sheet, $row) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (is_numeric($v)) return (float)$v;
                return (float)str_replace(',', '', (string)($v ?? '0'));
            };

            $excel_date_val = $sheet->getCell("A" . $row)->getCalculatedValue();
            $excel_year = $tax_year;
            $filing_date = null;
            if (is_numeric($excel_date_val)) {
                try {
                    $d = Date::excelToDateTimeObject($excel_date_val);
                    $excel_year = (int)$d->format("Y");
                    $filing_date = $d->format("Y-m-d");
                } catch (Exception $e) {}
            }

            $ss_val = trim($sheet->getCell("AB" . $row)->getCalculatedValue() ?? "");
            $is_ss = !empty($ss_val) ? 1 : 0;

            // Validate SS Member field
            $upper_ss = strtoupper($ss_val);
            if (!empty($ss_val) && $upper_ss !== "YES" && $upper_ss !== "NO") {
                $error_log[] = "Row $row: Invalid Social Security value '$ss_val' (expected YES/NO)";
            }

            $data = [
                "batch_id"       => $batch_id,
                "tax_year"       => $excel_year > 0 ? $excel_year : $tax_year,
                "employee_name"  => $sheet->getCell("B" . $row)->getCalculatedValue(),
                "filing_date"    => $filing_date,
                "ptin"           => $ptin,
                "amount_21"      => $num("D"),
                "amount_22"      => $num("F"),
                "amount_23_1"    => $num("H"),
                "amount_23_2"    => $num("J"),
                "amount_24"      => $num("L"),
                "amount_25"      => $num("N"),
                "amount_26"      => $num("P"),
                "amount_27"      => $num("S"),
                "amount_28_1"    => $num("U"),
                "amount_28_2"    => $num("X"),
                "amount_29"      => $num("Z"),
                "is_ss_member"   => $is_ss,
                "expert_te_21"   => $num("E"),
                "expert_te_22"   => $num("G"),
                "expert_te_23_1" => $num("I"),
                "expert_te_23_2" => $num("K"),
                "expert_te_24"   => $num("M"),
                "expert_te_25"   => $num("O"),
                "expert_te_26"   => $num("R"),
                "expert_te_27"   => $num("T"),
                "expert_te_28_1" => $num("W"),
                "expert_te_28_2" => $num("Y"),
                "expert_te_29"   => $num("AA"),
                "expert_te_total"=> $num("AC")
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO import_pit_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $imported++;
        }

        $message = "<strong>Import Success!</strong> Imported $imported records.<br><br>";
        $message .= "Skipped $skipped rows (missing PTIN).<br>";

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
$recent = $pdo->query("SELECT batch_id, MIN(tax_year) as tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_pit_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2"></i> Individual Tax Data Import</h2>
    <p class="text-muted">Upload the Individual Tax Excel template or manage manual entries.</p>
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
    <div class="card shadow-sm border-0 border-top border-4 border-primary">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-primary"></i> Upload Excel File</div>
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
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2 small">
                <a href="generate_individual_template.php" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download Template with Dropdowns</a>
            </div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
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
            <tr><td>A</td><td>Filing Date</td></tr>
            <tr><td>B</td><td>Employee Name</td></tr>
            <tr><td>C</td><td>PTIN (Personal Tax ID)</td></tr>
            <tr><td>D / E</td><td>Overtime/Night Shift (Amount / Expert TE)</td></tr>
            <tr><td>F / G</td><td>Severance/Redundancy (Amount / Expert TE)</td></tr>
            <tr><td>H / I</td><td>Rental Building (Amount / Expert TE)</td></tr>
            <tr><td>J / K</td><td>Rental Land/Other (Amount / Expert TE)</td></tr>
            <tr><td>L / M</td><td>Consulting/Service (Amount / Expert TE)</td></tr>
            <tr><td>N / O</td><td>Contractor Income (Amount / Expert TE)</td></tr>
            <tr><td>P / R</td><td>Shares Transfer (Amount / Expert TE)</td></tr>
            <tr><td>S / T</td><td>Dividends (Amount / Expert TE)</td></tr>
            <tr><td>U / W</td><td>Interest Loan (Amount / Expert TE)</td></tr>
            <tr><td>X / Y</td><td>Interest Bonds (Amount / Expert TE)</td></tr>
            <tr><td>Z / AA</td><td>Gifts/Bonus (Amount / Expert TE)</td></tr>
            <tr><td>AB</td><td>Social Security Member (YES/NO)</td></tr>
            <tr><td>AC</td><td>Expert TE Total</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Individual Tax Data Found</h5>
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
                <td><span class="badge bg-primary rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_individual.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_individual.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="pit">
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
        <h5 class="modal-title">Manual Data Entry for Individual Tax</h5>
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
        <button type="button" class="btn btn-primary" onclick="goToManualEntry()">Manage Records</button>
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
    window.location.href = `view_individual.php?batch=MANUAL_ENTRY_PIT_${year}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
