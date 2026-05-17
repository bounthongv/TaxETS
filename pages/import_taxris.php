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
        $batch_id = "TaxRIS_" . date("YmdHis");
        $imported = 0;

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $tin = trim($sheet->getCell("A" . $row)->getCalculatedValue());
            $year = (int)$sheet->getCell("C" . $row)->getCalculatedValue();
            if (empty($tin) || empty($year)) continue;

            $stmt = $pdo->prepare("INSERT INTO repo_taxris (import_batch_id, tin, company_name, year, revenue, expense, net_profit, tax_paid, total_assets, vat_system_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $batch_id, 
                $tin, 
                $sheet->getCell("B" . $row)->getCalculatedValue(), 
                $year,
                (float)$sheet->getCell("D" . $row)->getCalculatedValue(),
                (float)$sheet->getCell("E" . $row)->getCalculatedValue(),
                (float)$sheet->getCell("F" . $row)->getCalculatedValue(),
                (float)$sheet->getCell("G" . $row)->getCalculatedValue(),
                (float)$sheet->getCell("H" . $row)->getCalculatedValue(),
                (int)$sheet->getCell("I" . $row)->getCalculatedValue()
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
        <h2><a href="repo_taxris.php" class="text-dark"><i class="fas fa-arrow-left me-2"></i></a> Import TaxRIS Data</h2>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card shadow-sm p-4">
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="excel_file" class="form-control mb-3" required>
        <div class="alert alert-info small">
            <strong>Template Format:</strong><br>
            A: TIN, B: Name, C: Year, D: Revenue, E: Expense, F: Net Profit, G: Tax Paid, H: Total Assets, I: VAT Status (1/0)
        </div>
        <button type="submit" class="btn btn-primary">Import Data</button>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
