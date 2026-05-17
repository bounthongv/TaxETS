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
    
    try {
        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getActiveSheet();
        $batch_id = "GDP_" . date("YmdHis");
        $imported = 0;

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
            if (empty($year)) continue;

            $stmt = $pdo->prepare("INSERT INTO repo_gdp_revenue (import_batch_id, gdp_year, gdp_value, revenue_value, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $batch_id,
                $year,
                (float)$sheet->getCell("B" . $row)->getCalculatedValue(),
                (float)$sheet->getCell("C" . $row)->getCalculatedValue(),
                (string)$sheet->getCell("D" . $row)->getCalculatedValue()
            ]);
            $imported++;
        }
        $message = "Successfully imported $imported records.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><a href="repo_gdp.php" class="text-dark"><i class="fas fa-arrow-left me-2"></i></a> Import GDP Data</h2>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card shadow-sm p-4">
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="excel_file" class="form-control mb-3" required>
        <div class="alert alert-info small">
            <strong>Template Format:</strong> A: Year, B: GDP (Billions), C: Revenue, D: Note
        </div>
        <button type="submit" class="btn btn-primary">Import Data</button>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
