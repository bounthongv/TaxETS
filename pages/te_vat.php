<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_vat_engine.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? $_POST["batch_id"] ?? "";
$records = [];
$message = "";
$message_type = "success";
$calc_result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    try {
        $engine = new TEVatEngine($pdo);
        $calc_result = $engine->calculateBatch($_POST["batch_id"]);
        
        if ($calc_result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$calc_result["calculated"]} records</strong> processed. Total TE: <strong>" . number_format($calc_result["total_te"], 2) . " LAK</strong>";
            $message_type = "success";
        } else {
            $message = "No records found for batch: " . htmlspecialchars($_POST["batch_id"]);
            $message_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Calculation error: " . $e->getMessage();
        $message_type = "danger";
    }
}

$vat_rates = [];
try {
    $vat_rates = $pdo->query("SELECT * FROM bm_vat ORDER BY start_date ASC")->fetchAll();
} catch (Exception $e) { }

function getVatRateForDate($date, $rates) {
    if (!$date || $date == '0000-00-00') return 0.10;
    $ts = strtotime($date);
    foreach ($rates as $r) {
        if ($ts >= strtotime($r["start_date"]) && $ts <= strtotime($r["end_date"])) {
            return (float)$r["rate_percentage"] / 100;
        }
    }
    return 0.10;
}

if ($batch_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM import_vat_data WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = "Error fetching data: " . $e->getMessage();
        $message_type = "danger";
    }
}

$batches = [];
try {
    $batches = $pdo->query("SELECT DISTINCT batch_id, COUNT(*) as row_count FROM import_vat_data GROUP BY batch_id ORDER BY batch_id DESC LIMIT 10")->fetchAll();
} catch (Exception $e) { }
?>

<style>
.row-parent { cursor: pointer; transition: background 0.2s; }
.row-parent:hover { background-color: rgba(13, 110, 253, 0.05) !important; }
.row-child { background-color: #fcfcfc; }
.variance-pos { color: #dc3545; font-weight: bold; }
.variance-neg { color: #198754; font-weight: bold; }
</style>

<div class="row mb-3">
    <div class="col-12 text-start">
        <h2><i class="fas fa-calculator me-2 text-primary"></i> VAT TE Calculation Engine</h2>
        <p class="text-muted">Calculate Tax Expenditure based on exempt and zero-rated sales using historical VAT rates.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($calc_result["errors"])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Calculation Errors:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($calc_result["errors"] as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Select Import Batch</label>
                <select name="batch_id" class="form-select border-0 bg-light" required>
                    <option value="">-- Choose a batch --</option>
                    <?php foreach ($batches as $b): ?>
                    <option value="<?= htmlspecialchars($b["batch_id"]) ?>" <?= $batch_id == $b["batch_id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($b["batch_id"]) ?> (<?= $b["row_count"] ?> records)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-play me-2"></i> Run Engine</button>
            </div>
        </form>
    </div>
</div>

<?php if ($batch_id && !empty($records)): ?>
<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="bg-light small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4">Taxpayer / TIN</th>
                    <th>Period</th>
                    <th class="text-end">Exempt Sales</th>
                    <th class="text-end">Bench Rate</th>
                    <th class="text-end text-primary">Engine TE (DB)</th>
                    <th class="text-end text-info">Dummy Math TE</th>
                    <th class="pe-4 text-end">Variance</th>
                </tr>
            </thead>
            <tbody>
<?php
$total_sys = 0;
$total_exp = 0;
foreach ($records as $idx => $r):
    $calc_date = !empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00' ? $r["filing_period"] : $r["input_date"];
    $rate = getVatRateForDate($calc_date, $vat_rates);
    $dummy_te = (float)$r["sales_exempt"] * $rate;
    $engine_te = (float)$r["expert_te"];
    $variance = $engine_te - $dummy_te;
    $total_sys += $engine_te;
    $total_exp += $dummy_te;
    $row_id = "vat_detail_" . $idx;
?>
                <tr class="row-parent" data-bs-toggle="collapse" data-bs-target="#<?= $row_id ?>">
                    <td class="ps-4">
                        <div class="fw-bold"><?= htmlspecialchars($r["name"]) ?></div>
                        <small class="text-muted">TIN: <?= htmlspecialchars($r["tin"]) ?></small>
                    </td>
                    <td><?= !empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00' ? date("M Y", strtotime($r["filing_period"])) : '-' ?></td>
                    <td class="text-end"><?= number_format($r["sales_exempt"]) ?></td>
                    <td class="text-end text-muted small"><?= $rate * 100 ?>%</td>
                    <td class="text-end fw-bold text-primary"><?= number_format($engine_te) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format($dummy_te) ?></td>
                    <td class="pe-4 text-end">
                        <span class="<?= abs($variance) > 1 ? ($variance > 0 ? 'variance-pos' : 'variance-neg') : 'text-muted' ?>">
                            <?= number_format($variance) ?>
                            <i class="fas fa-chevron-down ms-1 opacity-50"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="p-0 border-0">
                        <div class="collapse row-child" id="<?= $row_id ?>">
                            <div class="p-4 px-5">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h6 class="fw-bold small text-uppercase text-primary mb-3">Calculation Parameters</h6>
                                        <table class="table table-sm table-borderless small mb-0">
                                            <tr><td class="text-muted">Filing Period:</td><td class="fw-bold"><?= !empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00' ? date("F Y", strtotime($r["filing_period"])) : 'N/A' ?></td></tr>
                                            <tr><td class="text-muted">Applied Rate:</td><td class="fw-bold text-primary"><?= $rate * 100 ?>% (Domestic Standard)</td></tr>
                                            <tr><td class="text-muted">Exempt Turnover:</td><td class="fw-bold"><?= number_format($r["sales_exempt"]) ?> LAK</td></tr>
                                            <tr><td class="text-muted">Legal Basis:</td><td class="text-info"><?= htmlspecialchars($r["provision_number"] ?: "Unclassified") ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6 ps-4">
                                        <h6 class="fw-bold small text-uppercase text-info mb-3">Input/Output Summary</h6>
                                        <table class="table table-sm table-borderless small mb-0">
                                            <tr><td class="text-muted">Total Output VAT:</td><td class="text-end"><?= number_format($r["total_output_vat"]) ?></td></tr>
                                            <tr><td class="text-muted">Total Input VAT:</td><td class="text-end"><?= number_format($r["total_input_vat"]) ?></td></tr>
                                            <tr><td class="text-muted">Net Payable/Credit:</td><td class="text-end fw-bold"><?= number_format($r["vat_payable"] - $r["vat_credit"]) ?></td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="ps-4">CONSOLIDATED BATCH TOTALS</td>
                    <td class="text-end text-primary fs-5"><?= number_format($total_sys) ?></td>
                    <td class="text-end text-info fs-5"><?= number_format($total_exp) ?></td>
                    <td class="pe-4 text-end fs-5 <?= abs($total_sys - $total_exp) > 1 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($total_sys - $total_exp) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php elseif ($batch_id && empty($records)): ?>
<div class="text-center py-5">
    <i class="fas fa-search fa-3x text-muted mb-3 opacity-25"></i>
    <h5>No data found in this batch.</h5>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-arrow-up fa-3x mb-3 opacity-10"></i>
    <h5>Select a VAT Data Batch to start calculation.</h5>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
