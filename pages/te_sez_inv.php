<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_sez_engine.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? "";
$message = "";
$message_type = "success";
$calc_result = null;

// Run calculation on POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $batch_id = $_POST["batch_id"];
    try {
        $engine = new TESEZEngine($pdo);
        $calc_result = $engine->calculateBatch($batch_id, 'Investor');
        if ($calc_result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$calc_result["calculated"]} records</strong> processed. Total TE = <strong>" . number_format($calc_result["total_te"], 0) . " LAK</strong>";
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
    $stmt = $pdo->prepare("SELECT s.*, p.province_name, d.district_name FROM import_sez_data s 
        LEFT JOIN provinces p ON s.province_id = p.id 
        LEFT JOIN districts d ON s.district_id = d.id 
        WHERE s.batch_id = ? AND s.type = 'Investor' ORDER BY s.id ASC");
    $stmt->execute([$batch_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Available batches
$batches = $pdo->query("SELECT batch_id, MIN(tax_year) as min_year, MAX(tax_year) as max_year, COUNT(*) as row_count, MAX(calculated_at) as last_calc FROM import_sez_data WHERE type='Investor' GROUP BY batch_id ORDER BY MAX(id) DESC LIMIT 20")->fetchAll();

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
    $engine = new TESEZEngine($pdo);
    $summary = $engine->getBatchSummary($batch_id);
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-calculator me-2 text-success"></i> SEZ Investor TE Calculation</h2>
        <p class="text-muted">Estimate VAT expenditures for SEZ investor utility and infrastructure usage.</p>
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
    $total_investment = (float)($summary["total_investment"] ?? 0);
    $total_utility = array_sum(array_column($records, "amount_utility_usage"));
    $total_infra = array_sum(array_column($records, "amount_infra_dev"));
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Records</div>
                <div class="h2 mb-0 fw-bold"><?= count($records) ?></div>
                <div class="small text-white-50">in this batch</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total TE</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_te, 0) ?></div>
                <div class="small text-white-50">LAK <?= $is_calculated ? "" : "(not yet calculated)" ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Investment</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_investment, 0) ?></div>
                <div class="small text-white-50">LAK</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Utility / Infra</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_utility, 0) ?></div>
                <div class="small text-white-50">Infra: <?= number_format($total_infra, 0) ?></div>
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
                    <label class="form-label fw-bold">Select SEZ Investor Batch</label>
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
                        <button type="submit" class="btn btn-success btn-lg shadow-sm" id="runBtn">
                            <i class="fas fa-cogs me-2"></i> <?= $is_calculated ? "Re-calculate" : "Run Calculation" ?>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info mb-0 py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        TE = (Utility + Infra) × applicable rate.
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
                    <th>Location</th>
                    <th class="text-end">Utility Usage</th>
                    <th class="text-end">Internal Infra</th>
                    <th class="text-end text-warning">Benchmark</th>
                    <th class="text-end text-info">TE Amount</th>
                    <th>Provision</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php foreach ($records as $r): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($r['tin']) ?></td>
                    <td><small><?= htmlspecialchars($r['province_name'] ?? 'N/A') ?></small></td>
                    <td class="text-end"><?= number_format($r['amount_utility_usage'], 2) ?></td>
                    <td class="text-end"><?= number_format($r['amount_infra_dev'], 2) ?></td>
                    <td class="text-end text-primary fw-bold"><?= number_format($r['benchmark_tax'], 2) ?></td>
                    <td class="text-end text-info fw-bold"><?= number_format($r['te_amount'], 2) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['provision_number'] ?: '-') ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="2" class="text-end">BATCH TOTALS</td>
                    <td class="text-end"><?= number_format(array_sum(array_column($records, "amount_utility_usage")), 2) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($records, "amount_infra_dev")), 2) ?></td>
                    <td class="text-end text-primary"><?= $is_calculated ? number_format(array_sum(array_column($records, "benchmark_tax")), 2) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end text-info"><?= $is_calculated ? number_format(array_sum(array_column($records, "te_amount")), 2) : '<span class="text-muted">—</span>' ?></td>
                    <td></td>
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
    <h5>Select an SEZ Investor Batch to start calculation.</h5>
</div>
<?php endif; ?>

<script>
document.getElementById("calcForm")?.addEventListener("submit", function() {
    document.getElementById("runBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    document.getElementById("runBtn").disabled = true;
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
