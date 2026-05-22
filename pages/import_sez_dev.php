<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$current_batch = $_GET['batch'] ?? null;

// --- Handle Batch Deletion ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_batch'])) {
    $bid = $_POST['batch_id'];
    $pdo->prepare("DELETE FROM import_sez_data WHERE batch_id = ? AND type = 'Developer'")->execute([$bid]);
    $message = "Batch <strong>$bid</strong> deleted successfully.";
    if ($current_batch === $bid) $current_batch = null;
}

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"];
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error."; $msg_type = "danger";
    } else {
        try {
            $spreadsheet = IOFactory::load($file["tmp_name"]);
            $sheet = $spreadsheet->getActiveSheet();
            $batch_id = "SEZ_DEV_BATCH_" . date("YmdHis");
            $imported = 0; $skipped = 0;

            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue() ?? "");
                if (empty($tin)) { $skipped++; continue; } 

                $num = function($col) use ($sheet, $row) {
                    $v = $sheet->getCell($col . $row)->getCalculatedValue();
                    return is_numeric($v) ? (float)$v : 0.00;
                };

                $license_date_val = $sheet->getCell("C" . $row)->getCalculatedValue();
                $license_date = null;
                if (is_numeric($license_date_val)) {
                    $d = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($license_date_val);
                    $license_date = $d->format("Y-m-d");
                }

                $data = [
                    "batch_id"           => $batch_id,
                    "tax_year"           => (int)$sheet->getCell("B" . $row)->getCalculatedValue(),
                    "tin"                => $tin,
                    "license_date"       => $license_date,
                    "province_id"        => is_numeric($sheet->getCell("D" . $row)->getCalculatedValue()) ? (int)$sheet->getCell("D" . $row)->getCalculatedValue() : null,
                    "district_id"        => is_numeric($sheet->getCell("E" . $row)->getCalculatedValue()) ? (int)$sheet->getCell("E" . $row)->getCalculatedValue() : null,
                    "type"               => 'Developer',
                    "amount_infra_basic" => $num("F"),
                    "amount_infra_other" => $num("G")
                ];

                $cols = implode(", ", array_keys($data));
                $ph = implode(", ", array_fill(0, count($data), "?"));
                $pdo->prepare("INSERT INTO import_sez_data ($cols) VALUES ($ph)")->execute(array_values($data));
                $imported++;
            }
            $message = "Import complete! <strong>$imported records</strong> imported.";
            $current_batch = $batch_id;
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage(); $msg_type = "danger";
        }
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT batch_id, COUNT(*) as `rows`, MAX(import_date) as latest FROM import_sez_data WHERE type='Developer' GROUP BY batch_id ORDER BY latest DESC LIMIT 5")->fetchAll();

// Fetch records for current batch
$records = [];
if ($current_batch) {
    $stmt = $pdo->prepare("SELECT s.*, p.province_name, d.district_name FROM import_sez_data s 
        LEFT JOIN provinces p ON s.province_id = p.id 
        LEFT JOIN districts d ON s.district_id = d.id 
        WHERE s.batch_id = ? AND s.type = 'Developer' ORDER BY s.id ASC");
    $stmt->execute([$current_batch]);
    $records = $stmt->fetchAll();
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-md-8">
    <h2><i class="fas fa-file-excel me-2 text-info"></i> Import Data for SEZ Developers</h2>
    <p class="text-muted">Import infrastructure development data using the standardized developer template.</p>
  </div>
  <div class="col-md-4 text-end">
    <a href="add_edit_sez_dev.php" class="btn btn-outline-primary"><i class="fas fa-plus me-1"></i> Add Manual Record</a>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card shadow-sm border-0 border-top border-4 border-info h-100">
      <div class="card-header bg-white text-dark fw-bold">Upload Developer Excel</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold mb-0 small">Excel File (.xlsx)</label>
                <a href="<?= BASE_URL ?>/docs/For%20SEZ%20Developers_Template.xlsx" class="btn btn-sm btn-link text-info p-0 text-decoration-none fw-bold small"><i class="fas fa-download me-1"></i> Download Template</a>
            </div>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2 small"><i class="fas fa-info-circle me-1"></i> Use the provided template for best results.</div>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-info text-white shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
          </div>
        </form>

        <hr class="my-4 opacity-10">
        
        <h6 class="fw-bold mb-3 small text-uppercase text-muted">Recent Batches</h6>
        <?php foreach ($recent as $r): ?>
          <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded <?= $current_batch == $r['batch_id'] ? 'bg-light border' : '' ?>">
            <div class="small">
                <a href="?batch=<?= urlencode($r['batch_id']) ?>" class="text-decoration-none fw-bold"><?= substr($r['batch_id'], -14) ?></a>
                <div class="text-muted" style="font-size: 0.75rem;"><?= $r['rows'] ?> records • <?= date("H:i", strtotime($r['latest'])) ?></div>
            </div>
            <form method="POST" onsubmit="return confirm('Delete this entire batch?')">
                <input type="hidden" name="batch_id" value="<?= $r['batch_id'] ?>">
                <button type="submit" name="delete_batch" class="btn btn-link text-danger p-0"><i class="fas fa-trash-alt"></i></button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><?= $current_batch ? "Batch: " . htmlspecialchars($current_batch) : "Import Preview" ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>TIN</th>
                        <th>Location</th>
                        <th class="text-end">Road/Elec/Water</th>
                        <th class="text-end">Other Infra</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($records)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No records to display. Upload a file or select a recent batch.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($r['tin']) ?></td>
                            <td><small><?= htmlspecialchars($r['province_name'] ?? '-') ?></small></td>
                            <td class="text-end"><?= number_format($r['amount_infra_basic'], 2) ?></td>
                            <td class="text-end"><?= number_format($r['amount_infra_other'], 2) ?></td>
                            <td class="text-center">
                                <a href="add_edit_sez_dev.php?id=<?= $r['id'] ?>" class="text-primary"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
