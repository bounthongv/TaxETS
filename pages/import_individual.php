<?php
/**
 * Individual Tax (PIT) Data Import
 * Reads the expert-confirmed PIT template (PIT-template-apis v1.0).
 * Template columns (35 cols A-AI):
 *   A=Filing Date, B=Tax Year, C=PTIN, D=Individual Name
 *   E-S = Provision amounts #21-#30 + condition flags
 *   T-AI = User Fallback fields
 */

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

// Helper: parse Yes/No text to 1/0
function yn($v): int {
    $s = strtoupper(trim((string)($v ?? '')));
    return ($s === 'YES' || $s === 'Y' || $s === '1') ? 1 : 0;
}

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    try {
        $file = $_FILES["excel_file"];
        $tax_year_default = (int)$_POST["tax_year"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error.");
        }
        if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
            throw new Exception("Invalid file type.");
        }

        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getSheetByName("PIT Import");
        if (!$sheet) {
            $sheet = $spreadsheet->getActiveSheet();
        }
        $batch_id = "BATCH_" . date("YmdHis");
        $imported = 0; $skipped = 0;
        $error_log = [];
        $duplicate_log = [];
        $max_row = $sheet->getHighestRow();
        $ok = true;

        // --- Phase 1: Duplicate Check ---
        $dup_check_rows = [];
        for ($row = 5; $row <= $max_row; $row++) {
            $ptin = trim($sheet->getCell("C" . $row)->getCalculatedValue() ?? '');
            if (empty($ptin)) continue;
            $year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
            if ($year <= 0) continue;
            $dup_check_rows[] = [
                "row" => $row,
                "ptin" => $ptin,
                "year" => $year,
                "name" => trim($sheet->getCell("D" . $row)->getCalculatedValue() ?? ''),
            ];
        }

        if (!empty($dup_check_rows)) {
            $placeholders = []; $params = [];
            foreach ($dup_check_rows as $dr) {
                $placeholders[] = "(? , ?)"; $params[] = $dr["ptin"]; $params[] = $dr["year"];
            }
            $existing = $pdo->prepare("
                SELECT p.ptin, p.tax_year, p.individual_name, p.batch_id
                FROM import_pit_data p
                WHERE (p.ptin, p.tax_year) IN (" . implode(", ", $placeholders) . ")
                ORDER BY p.ptin, p.tax_year
            ");
            $existing->execute($params);
            $dup_map = [];
            foreach ($existing->fetchAll() as $ex) {
                $key = $ex["ptin"] . "|" . $ex["tax_year"];
                if (!isset($dup_map[$key])) $dup_map[$key] = [];
                $dup_map[$key][] = $ex;
            }

            $dup_count = 0;
            foreach ($dup_check_rows as $dr) {
                $key = $dr["ptin"] . "|" . $dr["year"];
                if (isset($dup_map[$key])) {
                    $dup_count++;
                    $batch_ids = array_unique(array_column($dup_map[$key], "batch_id"));
                    $error_log[] = "Row {$dr['row']}: PTIN '{$dr['ptin']}' / Year {$dr['year']} — exists in batch(es): " . implode(", ", $batch_ids);
                    $duplicate_log[] = "{$dr['row']},{$dr['ptin']},{$dr['year']}," . str_replace(",", ";", $dr['name']) . "," . implode("; ", $batch_ids);
                }
            }

            if ($dup_count > 0) {
                file_put_contents(__DIR__ . "/../data/logs/{$batch_id}_duplicates.csv",
                    "Row,PTIN,Year,Name,Existing Batch(es)\n" . implode("\n", $duplicate_log) . "\n");
                $message = "<div class='alert alert-danger'><strong>⛔ Import Blocked!</strong> Found <strong>$dup_count</strong> duplicate record(s) already in the database.<br>";
                $message .= "Review the details below, then either:<br>";
                $message .= "1. <strong>Clean up the database</strong> by deleting the existing batch(es) (Admin only), or<br>";
                $message .= "2. <strong>Remove the duplicate rows</strong> from your Excel file.<br><br>";
                $message .= "<a href='download_log.php?log_id={$batch_id}_duplicates' class='btn btn-sm btn-danger'><i class='fas fa-download me-1'></i> Download Duplicate Report (CSV)</a></div>";
                $log_content = "DUPLICATE CHECK LOG - " . date("Y-m-d H:i:s") . "\nBatch: $batch_id\nFile: " . $file["name"] . "\n\n" . implode("\n", $error_log);
                file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);
                $message .= "<br><a href='download_log.php?log_id=$batch_id' class='btn btn-sm btn-outline-danger'><i class='fas fa-download me-1'></i> Download Error Log</a>";
                $ok = false;
            }
            unset($existing);
        }

        // Helper: numeric from cell
        $num = function($col) use ($sheet) {
            static $row = 0;
            // This will be bound dynamically inside the loop via closure
            // Actually let's use a different approach
            return function($col, $row) use ($sheet) {
                $v = $sheet->getCell($col . $row)->getCalculatedValue();
                if (is_numeric($v)) return (float)$v;
                return (float)str_replace(',', '', (string)($v ?? '0'));
            };
        };
        $getNum = function($col, $row) use ($sheet) {
            $v = $sheet->getCell($col . $row)->getCalculatedValue();
            if (is_numeric($v)) return (float)$v;
            return (float)str_replace(',', '', (string)($v ?? '0'));
        };
        $getStr = function($col, $row) use ($sheet) {
            return trim($sheet->getCell($col . $row)->getCalculatedValue() ?? "");
        };

        // Prepare SQL once
        $cols = [
            "batch_id", "tax_year", "filing_date", "ptin", "individual_name",
            "amount_21","amount_22","amount_23_1","amount_23_2",
            "amount_24","amount_25","amount_26","amount_27","amount_28_1","amount_28_2","amount_29",
            "is_stock_listed","is_banking_system","is_ss_member","ss_contribution","use_fallback",
            "user_te_21","user_te_22","user_te_23_1","user_te_23_2",
            "user_te_24","user_te_25","user_te_26","user_te_27","user_te_28_1","user_te_28_2","user_te_29","user_te_30","user_te_total",
            "user_fallback_reason","user_comment",
        ];
        $cn = implode(", ", $cols);
        $ph = implode(", ", array_fill(0, count($cols), "?"));
        $stmt = $pdo->prepare("INSERT INTO import_pit_data ($cn) VALUES ($ph)");

        if ($ok):
        $empty_streak = 0;
        for ($row = 5; $row <= $max_row; $row++) {  // Row 5 = first data row
            $ptin = $getStr("C", $row);

            // Check if whole row is empty
            $is_empty = true;
            foreach (["A","B","C","D"] as $c) {
                if ($getStr($c, $row) !== "") { $is_empty = false; break; }
            }
            if ($is_empty) {
                // Check amount cols too
                foreach (["E","K","Q","S"] as $c) {
                    if ($getNum($c, $row) != 0) { $is_empty = false; break; }
                }
            }
            if ($is_empty) {
                $empty_streak++;
                if ($empty_streak >= 20) break;
                continue;
            }
            $empty_streak = 0;
            if (empty($ptin)) {
                $skipped++;
                $error_log[] = "Row $row: PTIN is required";
                continue;
            }

            // --- Parse Filing Date ---
            $filing_date = null;
            $excel_year = $tax_year_default;
            $raw_date = $sheet->getCell("A" . $row)->getCalculatedValue();
            if (is_numeric($raw_date)) {
                try {
                    $d = Date::excelToDateTimeObject($raw_date);
                    $filing_date = $d->format("Y-m-d");
                } catch (Exception $e) {}
            } elseif (!empty($raw_date)) {
                $filing_date = date("Y-m-d", strtotime((string)$raw_date) ?: time());
            }

            // --- Parse Tax Year ---
            $raw_year = $sheet->getCell("B" . $row)->getCalculatedValue();
            if (is_numeric($raw_year) && (int)$raw_year > 2000) {
                $excel_year = (int)$raw_year;
            }

            // --- Amounts (E, F, G, H, I, J, K, M, N, P, Q, S) ---
            $amount_21 = $getNum("E", $row);
            $amount_22 = $getNum("F", $row);
            $amount_23_1 = $getNum("G", $row);
            $amount_23_2 = $getNum("H", $row);
            $amount_24 = $getNum("I", $row);
            $amount_25 = $getNum("J", $row);
            $amount_26 = $getNum("K", $row);
            $amount_27 = $getNum("M", $row);
            $amount_28_1 = $getNum("N", $row);
            $amount_28_2 = $getNum("P", $row);
            $amount_29 = $getNum("Q", $row);

            // --- Condition Flags (L, O, R) ---
            $is_stock_listed  = yn($getStr("L", $row));
            $is_banking_system = yn($getStr("O", $row));
            $is_ss_member     = yn($getStr("R", $row));

            // --- Social Security Contribution (S) ---
            $ss_contribution = $getNum("S", $row);

            // --- Fallback flag (T) ---
            $use_fallback = yn($getStr("T", $row));

            // --- User TE values (U-AF) ---
            $user_te_21   = ($use_fallback && $getStr("U", $row) !== "") ? $getNum("U", $row) : null;
            $user_te_22   = ($use_fallback && $getStr("V", $row) !== "") ? $getNum("V", $row) : null;
            $user_te_23_1 = ($use_fallback && $getStr("W", $row) !== "") ? $getNum("W", $row) : null;
            $user_te_23_2 = ($use_fallback && $getStr("X", $row) !== "") ? $getNum("X", $row) : null;
            $user_te_24   = ($use_fallback && $getStr("Y", $row) !== "") ? $getNum("Y", $row) : null;
            $user_te_25   = ($use_fallback && $getStr("Z", $row) !== "") ? $getNum("Z", $row) : null;
            $user_te_26   = ($use_fallback && $getStr("AA", $row) !== "") ? $getNum("AA", $row) : null;
            $user_te_27   = ($use_fallback && $getStr("AB", $row) !== "") ? $getNum("AB", $row) : null;
            $user_te_28_1 = ($use_fallback && $getStr("AC", $row) !== "") ? $getNum("AC", $row) : null;
            $user_te_28_2 = ($use_fallback && $getStr("AD", $row) !== "") ? $getNum("AD", $row) : null;
            $user_te_29   = ($use_fallback && $getStr("AE", $row) !== "") ? $getNum("AE", $row) : null;
            $user_te_30   = ($use_fallback && $getStr("AF", $row) !== "") ? $getNum("AF", $row) : null;
            $user_te_total = ($getStr("AG", $row) !== "") ? $getNum("AG", $row) : null;

            // --- User Meta (AH, AI) ---
            $user_fallback_reason = $use_fallback ? $getStr("AH", $row) : null;
            $user_comment = $getStr("AI", $row);
            if ($user_comment === "") $user_comment = null;

            $data = [
                $batch_id,
                $excel_year,
                $filing_date,
                $ptin,
                $getStr("D", $row),
                $amount_21, $amount_22, $amount_23_1, $amount_23_2,
                $amount_24, $amount_25, $amount_26,
                $amount_27, $amount_28_1, $amount_28_2, $amount_29,
                $is_stock_listed, $is_banking_system, $is_ss_member, $ss_contribution, $use_fallback,
                $user_te_21, $user_te_22, $user_te_23_1, $user_te_23_2,
                $user_te_24, $user_te_25, $user_te_26, $user_te_27,
                $user_te_28_1, $user_te_28_2, $user_te_29, $user_te_30, $user_te_total,
                $user_fallback_reason, $user_comment,
            ];

            $stmt->execute($data);
            $imported++;
        }
        endif; // $ok (skip main import if duplicates found)

        $message = "<strong>Import Success!</strong> Imported $imported records.<br><br>";
        if ($skipped > 0) {
            $message .= "Warning: Skipped $skipped row(s) with data but no PTIN.<br>";
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
$recent = $pdo->query("SELECT batch_id, MIN(tax_year) as tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_pit_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2"></i> Individual Tax (PIT) Data Import</h2>
    <p class="text-muted">Upload the PIT Excel template (v1.0) or manage manual entries.</p>
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
            <label class="form-label fw-bold">Tax Year <small class="text-muted">(fallback if not in file)</small></label>
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
                <a href="generate_pit_template.php" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download PIT Import Template (v1.0)</a>
            </div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Column Mapping -->
    <div class="card mt-3 shadow-sm">
      <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-table me-2"></i> Excel Column Mapping</div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light"><tr><th>Col</th><th>Field</th><th>Type</th></tr></thead>
          <tbody>
            <tr class="table-secondary"><td colspan="3" class="fw-bold text-muted small">PRIMARY OPTIONAL</td></tr>
            <tr><td>A</td><td>Filing Date</td><td>Date</td></tr>
            <tr><td>D</td><td>Individual Name</td><td>Text</td></tr>
            <tr class="table-primary"><td colspan="3" class="fw-bold text-primary small">PRIMARY REQUIRED</td></tr>
            <tr><td>B</td><td>Tax Year</td><td>Number</td></tr>
            <tr><td>C</td><td>PTIN</td><td>Text</td></tr>
            <tr class="table-info"><td colspan="3" class="fw-bold text-info small">PRIMARY CONDITIONAL — Provision Amounts</td></tr>
            <tr><td>E</td><td>Amount #21 Overtime LAK</td><td>Numeric</td></tr>
            <tr><td>F</td><td>Amount #22 Uniform / Safety LAK</td><td>Numeric</td></tr>
            <tr><td>G</td><td>Amount #23.1 Spouse Allowance LAK</td><td>Numeric</td></tr>
            <tr><td>H</td><td>Amount #23.2 Child Allowance LAK</td><td>Numeric</td></tr>
            <tr><td>I</td><td>Amount #24 Government Allowance LAK</td><td>Numeric</td></tr>
            <tr><td>J</td><td>Amount #25 Student Allowance LAK</td><td>Numeric</td></tr>
            <tr><td>K</td><td>Amount #26 Share Sale Profit LAK</td><td>Numeric</td></tr>
            <tr><td>L</td><td>Lao Stock Exchange Listed?</td><td>Yes / No</td></tr>
            <tr><td>M</td><td>Amount #27 Dividend Income LAK</td><td>Numeric</td></tr>
            <tr><td>N</td><td>Amount #28.1 Deposit Interest LAK</td><td>Numeric</td></tr>
            <tr><td>O</td><td>Deposit in Banking System?</td><td>Yes / No</td></tr>
            <tr><td>P</td><td>Amount #28.2 Bond Interest LAK</td><td>Numeric</td></tr>
            <tr><td>Q</td><td>Amount #29 Security Bonus LAK</td><td>Numeric</td></tr>
            <tr><td>R</td><td>Social Security Member?</td><td>Yes / No</td></tr>
            <tr><td>S</td><td>Social Security Contribution LAK #30</td><td>Numeric</td></tr>
            <tr class="table-warning"><td colspan="3" class="fw-bold text-warning small">USER FALLBACK</td></tr>
            <tr><td>T</td><td>Use User Fallback?</td><td>Yes / No</td></tr>
            <tr><td>U</td><td>User TE #21</td><td>Numeric</td></tr>
            <tr><td>V</td><td>User TE #22</td><td>Numeric</td></tr>
            <tr><td>W</td><td>User TE #23.1</td><td>Numeric</td></tr>
            <tr><td>X</td><td>User TE #23.2</td><td>Numeric</td></tr>
            <tr><td>Y</td><td>User TE #24</td><td>Numeric</td></tr>
            <tr><td>Z</td><td>User TE #25</td><td>Numeric</td></tr>
            <tr><td>AA</td><td>User TE #26</td><td>Numeric</td></tr>
            <tr><td>AB</td><td>User TE #27</td><td>Numeric</td></tr>
            <tr><td>AC</td><td>User TE #28.1</td><td>Numeric</td></tr>
            <tr><td>AD</td><td>User TE #28.2</td><td>Numeric</td></tr>
            <tr><td>AE</td><td>User TE #29</td><td>Numeric</td></tr>
            <tr><td>AF</td><td>User TE #30</td><td>Numeric</td></tr>
            <tr><td>AG</td><td>User TE Total</td><td>Numeric</td></tr>
            <tr><td>AH</td><td>User Fallback Reason</td><td>Text</td></tr>
            <tr><td>AI</td><td>User Comment</td><td>Text</td></tr>
          </tbody>
        </table>
        </div>
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
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    window.location.href = `view_individual.php?batch=MANUAL_ENTRY_PIT_${year}_${stamp}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
