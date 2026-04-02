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
    } else {
        try {
            $spreadsheet = IOFactory::load($file["tmp_name"]);
            $sheet = $spreadsheet->getActiveSheet();
            $batch_id = "ASY_" . date("YmdHis");
            $imported = 0;
            
            $pdo->beginTransaction();
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $tin = trim($sheet->getCell("B" . $row)->getValue());
                if (empty($tin)) continue;

                $regime = trim($sheet->getCell("G" . $row)->getValue()) . "-" . trim($sheet->getCell("H" . $row)->getValue());
                
                $parseDate = function($col) use ($sheet, $row) {
                    $v = $sheet->getCell($col . $row)->getValue();
                    if (!$v) return null;
                    if (is_numeric($v)) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format("Y-m-d");
                    return $v;
                };

                $data = [
                    "import_batch_id"   => $batch_id,
                    "province"          => $sheet->getCell("A" . $row)->getValue(),
                    "tin"               => $tin,
                    "no_seq"            => $sheet->getCell("C" . $row)->getValue(),
                    "border_code"       => $sheet->getCell("D" . $row)->getValue(),
                    "border_name"       => $sheet->getCell("E" . $row)->getValue(),
                    "type_customs"      => $sheet->getCell("F" . $row)->getValue(),
                    "process_type"      => $sheet->getCell("G" . $row)->getValue(),
                    "regime_f"          => $sheet->getCell("H" . $row)->getValue(),
                    "regime_code"       => $regime,
                    "special_role"      => $sheet->getCell("I" . $row)->getValue(),
                    "doc_number"        => $sheet->getCell("J" . $row)->getValue(),
                    "doc_date"          => $parseDate("K"),
                    "assess_number"     => $sheet->getCell("L" . $row)->getValue(),
                    "assess_date"       => $parseDate("M"),
                    "receipt_no"        => $sheet->getCell("N" . $row)->getValue(),
                    "receipt_date"      => $parseDate("O"),
                    "importer_name"     => $sheet->getCell("P" . $row)->getValue(),
                    "declarant_tin"     => $sheet->getCell("Q" . $row)->getValue(),
                    "declarant_name"    => $sheet->getCell("R" . $row)->getValue(),
                    "export_country"    => $sheet->getCell("S" . $row)->getValue(),
                    "dest_country"      => $sheet->getCell("T" . $row)->getValue(),
                    "origin_country"    => $sheet->getCell("U" . $row)->getValue(),
                    "list_no"           => $sheet->getCell("V" . $row)->getValue(),
                    "hs_code"           => $sheet->getCell("W" . $row)->getValue(),
                    "goods_description" => $sheet->getCell("X" . $row)->getValue(),
                    "quantity"          => (float)$sheet->getCell("Y" . $row)->getValue(),
                    "unit"              => $sheet->getCell("Z" . $row)->getValue(),
                    "declare_weight"    => (float)$sheet->getCell("AA" . $row)->getValue(),
                    "actual_weight"     => (float)$sheet->getCell("AB" . $row)->getValue(),
                    "invoice_usd"       => (float)$sheet->getCell("AC" . $row)->getValue(),
                    "invoice_amount_lak" => (float)$sheet->getCell("AD" . $row)->getValue(),
                    "paid_customs"      => (float)$sheet->getCell("AE" . $row)->getValue(),
                    "paid_excise"       => (float)$sheet->getCell("AF" . $row)->getValue(),
                    "paid_vat"          => (float)$sheet->getCell("AG" . $row)->getValue(),
                    "paid_profit"       => (float)$sheet->getCell("AH" . $row)->getValue(),
                    "paid_road_fund"    => (float)$sheet->getCell("AI" . $row)->getValue(),
                    "paid_total"        => (float)$sheet->getCell("AJ" . $row)->getValue(),
                    "status_aj"         => $sheet->getCell("AK" . $row)->getValue(),
                    "exemp_customs"     => (float)$sheet->getCell("AL" . $row)->getValue(),
                    "exempt_excise"     => (float)$sheet->getCell("AM" . $row)->getValue(),
                    "exempt_vat"        => (float)$sheet->getCell("AN" . $row)->getValue(),
                    "te_customs_excel"  => (float)$sheet->getCell("AO" . $row)->getValue(),
                    "te_excise_excel"   => (float)$sheet->getCell("AP" . $row)->getValue(),
                    "te_vat_excel"      => (float)$sheet->getCell("AQ" . $row)->getValue(),
                    "provision_customs" => $sheet->getCell("AR" . $row)->getValue(),
                ];

                $cols = implode(", ", array_keys($data));
                $ph = implode(", ", array_fill(0, count($data), "?"));
                $stmt = $pdo->prepare("INSERT INTO asycuda_imports ($cols) VALUES ($ph)");
                $stmt->execute(array_values($data));
                $asy_id = $pdo->lastInsertId();

                // Calculate TE: Benchmark - Paid
                $customs_te = max(0, $data['exemp_customs'] - $data['paid_customs']);
                $excise_te = max(0, $data['exempt_excise'] - $data['paid_excise']);
                $vat_te = max(0, $data['exempt_vat'] - $data['paid_vat']);
                $total_te = $customs_te + $excise_te + $vat_te;

                $stmt_te = $pdo->prepare("INSERT INTO te_asycuda_result (asycuda_id, customs_te, excise_te, vat_te, total_te) VALUES (?, ?, ?, ?, ?)");
                $stmt_te->execute([$asy_id, $customs_te, $excise_te, $vat_te, $total_te]);

                $imported++;
                if ($imported % 500 == 0) {
                    $pdo->commit(); $pdo->beginTransaction();
                }
            }
            $pdo->commit();
            $message = "Import Successful! <strong>$imported records</strong> processed and TE calculated.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error: " . $e->getMessage(); $msg_type = "danger";
        }
    }
}

$recent = $pdo->query("SELECT import_batch_id, DATE(import_date) as idate, COUNT(*) as `rows`, SUM(te.total_te) as total_te 
                       FROM asycuda_imports i 
                       JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                       GROUP BY import_batch_id ORDER BY i.id DESC LIMIT 10")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-file-invoice me-2"></i> Import Data from ASYCUDA</h2>
        <p class="text-muted">Upload the standard ASYCUDA Excel export to calculate Customs, Excise, and VAT Tax Expenditures.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-upload me-2"></i> Upload ASYCUDA File</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Excel File (.xlsx)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                        <div class="form-text">File must match the standard ASYCUDA export format.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-file-import me-2"></i> Import and Calculate</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-info-circle me-2"></i> Column Mapping Info</div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0 small">
                    <thead class="table-light"><tr><th>Excel Column</th><th>System Meaning</th></tr></thead>
                    <tbody>
                        <tr><td>AE: Customs</td><td>Paid Customs Duty</td></tr>
                        <tr><td>AF: Excise</td><td>Paid Excise Tax</td></tr>
                        <tr><td>AG: VAT</td><td>Paid Import VAT</td></tr>
                        <tr class="table-info"><td>AL: Exemp_Customs</td><td><strong>Benchmark Customs Duty</strong></td></tr>
                        <tr class="table-info"><td>AM: Exempt_Excise</td><td><strong>Benchmark Excise Tax</strong></td></tr>
                        <tr class="table-info"><td>AN: Exempt_VAT</td><td><strong>Benchmark Import VAT</strong></td></tr>
                        <tr><td>G & H</td><td>Regime Code (e.g., 4000-480)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="fas fa-history me-2"></i> Recent ASYCUDA Imports</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 small">
                    <thead class="table-light"><tr><th>Batch ID</th><th>Date</th><th>Records</th><th>Total TE (LAK)</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><small class="font-monospace"><?= htmlspecialchars($r['import_batch_id']) ?></small></td>
                            <td><?= $r['idate'] ?></td>
                            <td><?= number_format($r['rows']) ?></td>
                            <td class="fw-bold text-danger"><?= number_format($r['total_te'], 2) ?></td>
                            <td>
                                <a href="view_asycuda.php?batch=<?= urlencode($r['import_batch_id']) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent)): ?>
                        <tr><td colspan="4" class="text-center p-4 text-muted">No ASYCUDA data imported yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
