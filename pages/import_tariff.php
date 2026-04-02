<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '300');

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
    } else {
        try {
            $reader = IOFactory::createReaderForFile($file["tmp_name"]);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file["tmp_name"]);
            $sheet = $spreadsheet->getActiveSheet();
            
            $pdo->exec("TRUNCATE TABLE bm_customs_tariff");
            
            $stmt = $pdo->prepare("INSERT INTO bm_customs_tariff (hs_code, sub_code, description_lo, description_en, unit, rate_normal, rate_mfn, rate_atiga, rate_acfta, rate_akfta, rate_ajcep, rate_aanzfta, rate_aifta, rate_apta, rate_laoviet) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $imported = 0;
            $highestRow = $sheet->getHighestRow();
            
            $pdo->beginTransaction(); // Use transaction for speed
            for ($row = 17; $row <= $highestRow; $row++) {
                $hs_code = trim($sheet->getCell("A" . $row)->getValue());
                $sub_code = trim($sheet->getCell("B" . $row)->getValue());
                $desc_lo = trim($sheet->getCell("C" . $row)->getValue());
                $desc_en = trim($sheet->getCell("D" . $row)->getValue());
                
                if (empty($hs_code) && empty($desc_en)) continue;

                $data = [
                    $hs_code,
                    $sub_code,
                    $desc_lo,
                    $desc_en,
                    $sheet->getCell("E" . $row)->getValue(), // Unit
                    $sheet->getCell("F" . $row)->getValue(), // Normal
                    $sheet->getCell("G" . $row)->getValue(), // MFN
                    $sheet->getCell("H" . $row)->getValue(), // ATIGA
                    $sheet->getCell("I" . $row)->getValue(), // ACFTA (2018)
                    $sheet->getCell("L" . $row)->getValue(), // AKFTA
                    $sheet->getCell("S" . $row)->getValue(), // AJCEP
                    $sheet->getCell("W" . $row)->getValue(), // AANZFTA
                    $sheet->getCell("AE" . $row)->getValue(), // AIFTA
                    $sheet->getCell("AI" . $row)->getValue(), // APTA
                    $sheet->getCell("AJ" . $row)->getValue(), // Lao-Viet
                ];
                
                $stmt->execute($data);
                $imported++;
                
                // Commit in batches of 1000 to manage memory/locking
                if ($imported % 1000 == 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }
            $pdo->commit();
            
            $message = "Import Successful! <strong>$imported tariff lines</strong> imported from $highestRow total rows.";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-upload me-2"></i> Import Customs Benchmark Tariff</h2>
        <p class="text-muted">Import the AHTN 2017 Customs Tariff Excel file (starting from Row 17).</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">Select Tariff File</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Excel File (.xlsx)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                        <div class="form-text">Ensure the file structure matches the AHTN 2017 format.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-file-import me-2"></i> Import Benchmark Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">Import Configuration</div>
            <div class="card-body">
                <ul>
                    <li><strong>Start Row:</strong> 17 (Headers are on rows 15-16)</li>
                    <li><strong>HS Code Column:</strong> A</li>
                    <li><strong>Description Column:</strong> C (LO) / D (EN)</li>
                    <li><strong>Rate Columns:</strong> F through AJ</li>
                </ul>
                <p class="small text-muted italic">Note: This is a heavy operation. It might take up to a minute depending on the file size.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
