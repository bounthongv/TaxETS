<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"];
    $tax_year = (int)$_POST["tax_year"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error."; $msg_type = "danger";
    } elseif (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
        $message = "Invalid file type."; $msg_type = "danger";
    } else {
        $vendor = __DIR__ . "/../vendor/autoload.php";
        if (!file_exists($vendor)) {
            $message = "PhpSpreadsheet not installed. Run <code>composer install</code> on the server."; $msg_type = "warning";
        } else {
            try {
                $spreadsheet = IOFactory::load($file["tmp_name"]);
                $sheet = $spreadsheet->getActiveSheet();
                $batch_id = "PIT_BATCH_" . date("YmdHis");
                $imported = 0; $skipped = 0;

                for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                    $ptin = trim($sheet->getCell("C" . $row)->getCalculatedValue() ?? "");
                    if (empty($ptin)) { $skipped++; continue; } 

                    // Helper: format numeric amounts
                    $num = function($col) use ($sheet, $row) {
                        $v = $sheet->getCell($col . $row)->getCalculatedValue();
                        return is_numeric($v) ? (float)$v : 0.00;
                    };

                    $excel_date = $sheet->getCell("A" . $row)->getCalculatedValue();
                    $excel_year = $tax_year; // Default to form
                    if (is_numeric($excel_date)) {
                        $d = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excel_date);
                        $excel_year = (int)$d->format("Y");
                    }

                    // For Social Security, if cell AB has any value, consider it True
                    $ss_val = trim($sheet->getCell("AB" . $row)->getCalculatedValue() ?? "");
                    $is_ss = !empty($ss_val) ? 1 : 0;

                    $data = [
                        "batch_id"       => $batch_id,
                        "tax_year"       => $excel_year > 0 ? $excel_year : $tax_year,
                        "employee_name"  => $sheet->getCell("B" . $row)->getCalculatedValue(),
                        "ptin"           => $ptin,

                        // Raw Amounts
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

                        // Expert Calculated TE
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
                $message = "Import processing complete! <strong>$imported records</strong> imported, $skipped skipped.";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage(); $msg_type = "danger";
            }
        }
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT batch_id, tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM import_pit_data GROUP BY batch_id, tax_year ORDER BY lid DESC LIMIT 10")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 text-start">
    <h2><i class="fas fa-file-excel me-2"></i> Individual Tax Data Import</h2>
    <p class="text-muted">Import Personal Income Tax (PIT) testing data, including both component amounts and expert TE calculations.</p>
  </div>
</div>
<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
  <div class="col-md-5">
    <div class="card shadow-sm border-0 border-top border-4 border-info">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-info"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Tax Year</label>
            <select name="tax_year" class="form-select" required>
              <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
              <option value="<?= $y ?>" <?= ($y == date("Y")-1) ? "selected" : "" ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i> Use the standardized <strong>PIT TE Test</strong> template. Row 1 = headers.</div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-info btn-lg text-white shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Process File</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Import Batches</div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No PIT Data Found</h5><p class="small">Upload an Excel file to get started.</p>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch ID</th><th>Year</th><th>Records</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td class="align-middle"><small class="font-monospace text-primary"><?= htmlspecialchars($r["batch_id"]) ?></small></td>
                <td class="align-middle fw-bold"><?= $r["tax_year"] ?></td>
                <td class="align-middle"><span class="badge bg-success rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td class="align-middle">
                  <a href="<?= BASE_URL ?>/pages/te_individual.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-calculator me-1"></i> Calculate TE</a>
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

<script>
document.getElementById("uploadForm").addEventListener("submit", function() {
    let btn = document.getElementById("importBtn");
    btn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Importing...";
    btn.classList.add("disabled");
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
