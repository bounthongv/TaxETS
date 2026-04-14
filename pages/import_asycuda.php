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
                    $v = $sheet->getCell($col . $row)->getCalculatedValue();
                    if (!$v) return null;
                    if (is_numeric($v)) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format("Y-m-d");
                    // Try to parse string date if it's not numeric (e.g. DD/MM/YYYY)
                    $ts = strtotime(str_replace('/', '-', $v));
                    if ($ts) return date("Y-m-d", $ts);
                    return $v;
                };

                $cleanNum = function($col) use ($sheet, $row) {
                    $val = $sheet->getCell($col . $row)->getCalculatedValue();
                    if (is_numeric($val)) return (float)$val;
                    return (float)str_replace(',', '', (string)$val);
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
                    "quantity"          => $cleanNum("Y"),
                    "unit"              => $sheet->getCell("Z" . $row)->getValue(),
                    "declare_weight"    => $cleanNum("AA"),
                    "actual_weight"     => $cleanNum("AB"),
                    "invoice_usd"       => $cleanNum("AC"),
                    "invoice_amount_lak" => $cleanNum("AD"),
                    "paid_customs"      => $cleanNum("AE"),
                    "paid_excise"       => $cleanNum("AF"),
                    "paid_vat"          => $cleanNum("AG"),
                    "paid_profit"       => $cleanNum("AH"),
                    "paid_road_fund"    => $cleanNum("AI"),
                    "paid_total"        => $cleanNum("AJ"),
                    "status_aj"         => $sheet->getCell("AK" . $row)->getValue(),
                    "exemp_customs"     => $cleanNum("AL"),
                    "exempt_excise"     => $cleanNum("AM"),
                    "exempt_vat"        => $cleanNum("AN"),
                    "te_customs_excel"  => $cleanNum("AO"),
                    "te_excise_excel"   => $cleanNum("AP"),
                    "te_vat_excel"      => $cleanNum("AQ"),
                    "provision_customs" => $sheet->getCell("AR" . $row)->getValue(),
                ];

                $cols = implode(", ", array_keys($data));
                $ph = implode(", ", array_fill(0, count($data), "?"));
                $stmt = $pdo->prepare("INSERT INTO asycuda_imports ($cols) VALUES ($ph)");
                $stmt->execute(array_values($data));
                $asy_id = $pdo->lastInsertId();

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
            $message = "Import Successful! <strong>$imported records</strong> imported. Please go to calculation pages to process tax expenditures.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error: " . $e->getMessage(); $msg_type = "danger";
        }
    }
}

$recent = $pdo->query("SELECT i.import_batch_id, DATE(i.import_date) as idate, COUNT(*) as `rows`, COALESCE(SUM(te.total_te), 0) as total_te 
                       FROM asycuda_imports i 
                       LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                       GROUP BY i.import_batch_id ORDER BY i.id DESC LIMIT 10")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-file-invoice me-2"></i> Import Data from ASYCUDA</h2>
        <p class="text-muted">Upload the standard ASYCUDA Excel export to calculate Customs, Excise, and VAT Tax Expenditures.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-primary text-white fw-bold py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <i class="fas fa-upload me-2"></i> Upload ASYCUDA File
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase">Excel File (.xlsx)</label>
                        <input type="file" name="excel_file" class="form-control form-control-lg border-0 bg-light" accept=".xlsx" required style="border-radius: 8px;">
                        <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i> File must match the standard ASYCUDA export format.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm" style="border-radius: 8px;"><i class="fas fa-file-import me-2"></i> Import ASYCUDA Data</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4 shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-secondary text-white fw-bold py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <i class="fas fa-info-circle me-2"></i> Column Mapping Info
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0 small">
                    <thead class="table-light"><tr><th class="ps-3">Excel Column</th><th>System Meaning</th></tr></thead>
                    <tbody>
                        <tr><td class="ps-3 font-monospace">M & O</td><td><strong>Transaction Dates</strong> (Used for Report Year)</td></tr>
                        <tr><td class="ps-3 font-monospace">AE, AF, AG</td><td>Paid Customs, Excise, VAT</td></tr>
                        <tr class="table-info"><td class="ps-3 font-monospace">AL, AM, AN</td><td><strong>Benchmark Taxes</strong></td></tr>
                        <tr><td class="ps-3 font-monospace">G & H</td><td>Regime Code (e.g., 4000-480)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white fw-bold py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <i class="fas fa-history me-2 text-primary"></i> Recent ASYCUDA Imports
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 small">
                    <thead class="bg-light"><tr><th class="ps-4">Batch ID</th><th>Date</th><th>Records</th><th class="text-end">Total TE (LAK)</th><th class="text-center">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td class="ps-4"><small class="font-monospace text-muted"><?= htmlspecialchars($r['import_batch_id']) ?></small></td>
                            <td><?= $r['idate'] ?></td>
                            <td><?= number_format($r['rows']) ?></td>
                            <td class="fw-bold text-end">
                                <?php if ($r['total_te'] > 0): ?>
                                    <span class="text-danger"><?= number_format($r['total_te'], 2) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark opacity-75 small">Pending Calc</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="view_asycuda.php?batch=<?= urlencode($r['import_batch_id']) ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                                    <form method="POST" action="delete_batch.php" onsubmit="return confirm('Truly delete this batch? All calculated TE results will also be removed.')" style="margin:0;">
                                        <input type="hidden" name="type" value="asy">
                                        <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r['import_batch_id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Batch"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent)): ?>
                        <tr><td colspan="5" class="text-center p-5 text-muted"><i class="fas fa-ghost fa-2x mb-3 opacity-25"></i><br>No ASYCUDA data imported yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
