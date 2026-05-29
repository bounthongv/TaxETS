<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_resource_engine.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? "";
$message = "";
$message_type = "success";
$calc_result = null;

// Run calculation on POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $batch_id = $_POST["batch_id"];
    try {
        $engine = new TEResourceEngine($pdo);
        $calc_result = $engine->calculateBatch($batch_id);
        if ($calc_result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$calc_result["calculated"]} records</strong> processed. Total TE = <strong>" . number_format($calc_result["total_te"], 2) . " LAK</strong>";
        } else {
            $message = "No records found for batch: " . htmlspecialchars($batch_id);
            $message_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Calculation error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Fetch records
$records = [];
if ($batch_id) {
    $stmt = $pdo->prepare("SELECT * FROM import_resource_data WHERE batch_id = ? ORDER BY id ASC");
    $stmt->execute([$batch_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Available batches
$batches = $pdo->query("SELECT batch_id, MIN(tax_year) as min_year, MAX(tax_year) as max_year, COUNT(*) as row_count, MAX(calculated_at) as last_calc FROM import_resource_data GROUP BY batch_id ORDER BY MAX(id) DESC LIMIT 20")->fetchAll();

// Check if calculated
$is_calculated = false;
if (!empty($records)) {
    foreach ($records as $r) {
        if (!empty($r["calculated_at"])) { $is_calculated = true; break; }
    }
}

// Summary
$summary = null;
if ($batch_id) {
    $engine = new TEResourceEngine($pdo);
    $summary = $engine->getBatchSummary($batch_id);
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-calculator me-2 text-warning"></i> Natural Resource TE Calculation</h2>
        <p class="text-muted">Calculate tax expenditure for natural resource extraction based on benchmark percentages.</p>
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
<?php
    $total_te = (float)($summary["total_te"] ?? 0);
    $total_collected = (float)($summary["total_collected"] ?? 0);
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-dark h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-dark-50 text-uppercase fw-bold">Records</div>
                <div class="h2 mb-0 fw-bold"><?= count($records) ?></div>
                <div class="small text-dark-50">in this batch</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total TE</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_te, 2) ?></div>
                <div class="small text-white-50">LAK <?= $is_calculated ? "" : "(not yet calculated)" ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Collected</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_collected, 2) ?></div>
                <div class="small text-white-50">LAK</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-secondary text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Avg TE/Record</div>
                <div class="h4 mb-0 fw-bold"><?= count($records) > 0 ? number_format($total_te / count($records), 2) : 0 ?></div>
                <div class="small text-white-50">LAK</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Run Calculation -->
<div class="card mb-4 shadow-sm border-0" style="border-radius:12px">
    <div class="card-header bg-white fw-bold py-3"><i class="fas fa-play-circle me-2 text-warning"></i> Run TE Calculation</div>
    <div class="card-body">
        <form method="POST" id="calcForm">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Select Resource Fee Batch</label>
                    <select name="batch_id" class="form-select form-select-lg" required>
                        <option value="">-- Select a batch --</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?= htmlspecialchars($b["batch_id"]) ?>" <?= ($batch_id === $b["batch_id"]) ? "selected" : "" ?>>
                            <?= htmlspecialchars($b["batch_id"]) ?> (<?= $b["min_year"] == $b["max_year"] ? $b["min_year"] : $b["min_year"] . "-" . $b["max_year"] ?> | <?= $b["row_count"] ?> records<?= $b["last_calc"] ? ", calculated" : "" ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning text-dark btn-lg shadow-sm fw-bold" id="runBtn">
                            <i class="fas fa-cogs me-2"></i> <?= $is_calculated ? "Re-calculate" : "Run Calculation" ?>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info mb-0 py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        TE = (Benchmark fee - Actual fee) where actual rate &lt; benchmark rate.
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
        <div>
            <?php if ($is_calculated): ?>
                <span class="badge bg-success rounded-pill">Calculated</span>
            <?php else: ?>
                <span class="badge bg-secondary rounded-pill">Not Calculated</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-dark small">
                <tr>
                    <th>TIN</th>
                    <th>Resource Type</th>
                    <th class="text-end">Actual Rate</th>
                    <th class="text-end">Fee Collected</th>
                    <th class="text-end text-warning">Benchmark Rate</th>
                    <th class="text-end text-warning">Benchmark Fee</th>
                    <th class="text-end text-info">TE Amount</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php foreach ($records as $r): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($r['tin']) ?></td>
                    <td><?= htmlspecialchars($r['resource_type'] ?? '') ?></td>
                    <td class="text-end"><?= number_format($r['actual_rate'], 2) ?>%</td>
                    <td class="text-end"><?= number_format($r['fee_collected'], 2) ?></td>
                    <td class="text-end text-primary fw-bold"><?= number_format($r['benchmark_rate'], 2) ?>%</td>
                    <td class="text-end text-primary fw-bold"><?= number_format($r['benchmark_fee'], 2) ?></td>
                    <td class="text-end text-info fw-bold"><?= number_format($r['te_amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">BATCH TOTALS</td>
                    <td class="text-end"><?= number_format(array_sum(array_column($records, "fee_collected")), 2) ?></td>
                    <td></td>
                    <td class="text-end text-primary"><?= $is_calculated ? number_format(array_sum(array_column($records, "benchmark_fee")), 2) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end text-info"><?= $is_calculated ? number_format(array_sum(array_column($records, "te_amount")), 2) : '<span class="text-muted">—</span>' ?></td>
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
    <h5>Select a Resource Fee Batch to start calculation.</h5>
</div>
<?php endif; ?>

<script>
document.getElementById("calcForm")?.addEventListener("submit", function() {
    document.getElementById("runBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    document.getElementById("runBtn").disabled = true;
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
