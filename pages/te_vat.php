<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_vat_engine.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? "";
$message = "";
$message_type = "success";
$calc_result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $batch_id = $_POST["batch_id"];
    try {
        $engine = new TEVatEngine($pdo);
        $calc_result = $engine->calculateBatch($batch_id);

        if ($calc_result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$calc_result["calculated"]} records</strong> processed. Total TE: <strong>" . number_format($calc_result["total_te"], 0) . " LAK</strong>";
        } else {
            $message = "No records found for batch: " . htmlspecialchars($batch_id);
            $message_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Calculation error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Load VAT rates
$vat_rates = [];
try {
    $vat_rates = $pdo->query("SELECT * FROM bm_vat ORDER BY start_date ASC")->fetchAll();
} catch (Exception $e) {}

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

// Fetch records
$records = [];
if ($batch_id) {
    $stmt = $pdo->prepare("SELECT * FROM import_vat_data WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    $records = $stmt->fetchAll();
}

// Available batches
$batches = $pdo->query("SELECT batch_id, COUNT(*) as row_count, MAX(id) as lid FROM import_vat_data GROUP BY batch_id ORDER BY lid DESC LIMIT 20")->fetchAll();

// Summary
$summary = null;
if (!empty($records)) {
    $total_sales_exempt = array_sum(array_column($records, "sales_exempt"));
    $total_expert_te = array_sum(array_column($records, "expert_te"));
    $total_output_vat = array_sum(array_column($records, "total_output_vat"));
    $total_input_vat = array_sum(array_column($records, "total_input_vat"));
    $summary = [
        "total" => count($records),
        "total_exempt" => $total_sales_exempt,
        "total_expert_te" => $total_expert_te,
        "total_output" => $total_output_vat,
        "total_input" => $total_input_vat,
    ];
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-calculator me-2 text-primary"></i> Domestic VAT TE Calculation</h2>
        <p class="text-muted">Calculate Tax Expenditure based on exempt and zero-rated sales using historical VAT rates.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show shadow-sm border-start border-4 border-<?= $message_type ?>">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($calc_result["errors"])): ?>
<div class="alert alert-danger alert-dismissible fade show shadow-sm">
    <strong><i class="fas fa-exclamation-triangle me-2"></i> Calculation Errors:</strong>
    <ul class="mb-0 mt-2"><?php foreach ($calc_result["errors"] as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
    <?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<?php if (!empty($records) && $summary): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Records</div>
                <div class="h2 mb-0 fw-bold"><?= $summary["total"] ?></div>
                <div class="small text-white-50">in this batch</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Exempt Sales</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($summary["total_exempt"], 0) ?></div>
                <div class="small text-white-50">LAK</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Expert TE</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($summary["total_expert_te"], 0) ?></div>
                <div class="small text-white-50">LAK</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Output VAT</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($summary["total_output"], 0) ?></div>
                <div class="small text-white-50">Input: <?= number_format($summary["total_input"], 0) ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Run Calculation -->
<div class="card mb-4 shadow-sm border-0" style="border-radius:12px">
    <div class="card-header bg-white fw-bold py-3"><i class="fas fa-play-circle me-2 text-success"></i> Run TE Calculation</div>
    <div class="card-body">
        <form method="POST" id="calcForm">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Select VAT Batch</label>
                    <select name="batch_id" class="form-select form-select-lg" required>
                        <option value="">-- Select a batch --</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?= htmlspecialchars($b["batch_id"]) ?>" <?= ($batch_id === $b["batch_id"]) ? "selected" : "" ?>>
                            <?= htmlspecialchars($b["batch_id"]) ?> (<?= $b["row_count"] ?> records)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg shadow-sm" id="runBtn">
                            <i class="fas fa-cogs me-2"></i> Run Calculation
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info mb-0 py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        TE = Exempt Sales × applicable VAT rate.
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<?php if (!empty($records)): ?>
<div class="card shadow-sm border-0" style="border-radius:12px">
    <div class="card-header bg-white d-flex justify-content-between align-items-center fw-bold py-3">
        <span><i class="fas fa-table me-2 text-primary"></i> TE Results — Batch: <code><?= htmlspecialchars($batch_id) ?></code></span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th class="ps-4">Taxpayer / TIN</th>
                    <th>Period</th>
                    <th class="text-end">Exempt Sales</th>
                    <th class="text-end">Bench Rate</th>
                    <th class="text-end text-primary">Engine TE</th>
                    <th class="text-end text-info">Expert TE</th>
                    <th class="pe-4 text-end">Variance</th>
                </tr>
            </thead>
            <tbody>
<?php
$total_engine_te = 0;
$total_expert_te = 0;
foreach ($records as $idx => $r):
    $calc_date = !empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00' ? $r["filing_period"] : $r["input_date"];
    $rate = getVatRateForDate($calc_date, $vat_rates);
    $engine_te = (float)$r["sales_exempt"] * $rate;
    $expert_te = (float)($r["expert_te"] ?? 0);
    $variance = $engine_te - $expert_te;
    $total_engine_te += $engine_te;
    $total_expert_te += $expert_te;
    $row_id = "vat_detail_" . $idx;
?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold"><?= htmlspecialchars($r["name"]) ?></div>
                        <small class="text-muted">TIN: <?= htmlspecialchars($r["tin"]) ?></small>
                    </td>
                    <td><?= !empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00' ? date("M Y", strtotime($r["filing_period"])) : '-' ?></td>
                    <td class="text-end"><?= number_format($r["sales_exempt"], 0) ?></td>
                    <td class="text-end text-muted small"><?= $rate * 100 ?>%</td>
                    <td class="text-end fw-bold text-primary"><?= number_format($engine_te, 0) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format($expert_te, 0) ?></td>
                    <td class="pe-4 text-end">
                        <span class="badge bg-<?= abs($variance) < 1 ? "success" : ($variance > 0 ? "danger" : "warning") ?>">
                            <?= number_format($variance, 0) ?>
                        </span>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="ps-4">BATCH TOTALS</td>
                    <td></td>
                    <td class="text-end text-primary"><?= number_format($total_engine_te, 0) ?></td>
                    <td class="text-end text-info"><?= number_format($total_expert_te, 0) ?></td>
                    <td class="pe-4 text-end">
                        <span class="badge bg-<?= abs($total_engine_te - $total_expert_te) < 1 ? "success" : "danger" ?> fs-6">
                            <?= number_format($total_engine_te - $total_expert_te, 0) ?>
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php elseif ($batch_id && empty($records)): ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
    <h5>No data found in this batch.</h5>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-arrow-up fa-3x mb-3 opacity-10"></i>
    <h5>Select a VAT Batch to start calculation.</h5>
</div>
<?php endif; ?>

<script>
document.getElementById("calcForm")?.addEventListener("submit", function() {
    document.getElementById("runBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    document.getElementById("runBtn").disabled = true;
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
