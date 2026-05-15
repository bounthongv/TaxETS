<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error."; $msg_type = "danger";
    } elseif (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
        $message = "Invalid file type."; $msg_type = "danger";
    } else {
        try {
            $spreadsheet = IOFactory::load($file["tmp_name"]);
            $sheet = $spreadsheet->getActiveSheet();
            $batch_id = "LSE_" . date("YmdHis");
            $imported = 0; $skipped = 0; $updated = 0;

            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue());
                $name = trim($sheet->getCell("B" . $row)->getCalculatedValue());
                $date_raw = $sheet->getCell("C" . $row)->getCalculatedValue();
                
                $listing_date = null;
                if ($date_raw) {
                    if (is_numeric($date_raw)) {
                        $listing_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date_raw)->format("Y-m-d");
                    } else {
                        $listing_date = date("Y-m-d", strtotime($date_raw));
                    }
                }

                if (empty($tin)) { $skipped++; continue; }

                $check = $pdo->prepare("SELECT id FROM repo_lse WHERE tin = ?");
                $check->execute([$tin]);
                $existing = $check->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE repo_lse SET company_name = ?, listing_date = ?, import_batch_id = ? WHERE id = ?");
                    $stmt->execute([$name, $listing_date, $batch_id, $existing['id']]);
                    $updated++;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO repo_lse (tin, company_name, listing_date, import_batch_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$tin, $name, $listing_date, $batch_id]);
                    $imported++;
                }
            }
            $message = "Import complete! <strong>$imported</strong> new records, <strong>$updated</strong> updated, $skipped skipped.";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage(); $msg_type = "danger";
        }
    }
}

$recent_batches = $pdo->query("SELECT import_batch_id, COUNT(*) as count, MAX(created_at) as last_date FROM repo_lse WHERE import_batch_id IS NOT NULL GROUP BY import_batch_id ORDER BY last_date DESC LIMIT 10")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><a href="repo_lse.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Import LSE Data</h2>
        <p class="text-muted">Upload an Excel file with 3 columns: TIN, Company Name, and Listing Date.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-upload me-2"></i> Upload LSE Excel</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Excel File (.xlsx)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i> <strong>Template Format:</strong><br>
                        Col A: TIN (Tax ID)<br>
                        Col B: Company Name<br>
                        Col C: Listing Date (YYYY-MM-DD)
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-file-import me-2"></i> Import Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="fas fa-history me-2"></i> Recent LSE Imports</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Batch ID</th>
                            <th>Records</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_batches as $b): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($b['import_batch_id']) ?></code></td>
                            <td><?= $b['count'] ?></td>
                            <td><?= $b['last_date'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_batches)): ?>
                        <tr><td colspan="3" class="text-center text-muted">No imports yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
