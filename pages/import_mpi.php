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
        $batch_id = "MPI_" . date("YmdHis");
        $imported = 0;

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue());
            if (empty($tin)) continue;

            $stmt = $pdo->prepare("INSERT INTO repo_mpi (import_batch_id, tin, project_name, investment_license_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$batch_id, $tin, $sheet->getCell("B" . $row)->getCalculatedValue(), date("Y-m-d")]);
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
        <h2><a href="repo_mpi.php" class="text-dark"><i class="fas fa-arrow-left me-2"></i></a> Import MPI Data</h2>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card shadow-sm p-4">
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="excel_file" class="form-control mb-3" required>
        <button type="submit" class="btn btn-primary">Import</button>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
