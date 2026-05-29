<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_salary_tax_engine.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? "";
$message = "";
$message_type = "success";
$calc_result = null;

// Run calculation on POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $batch_id = $_POST["batch_id"];
    try {
        $engine = new TESalaryTaxEngine($pdo);
        $calc_result = $engine->calculateBatch($batch_id);
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

// Fetch results for display
$records = [];
if ($batch_id) {
    $stmt = $pdo->prepare("SELECT * FROM import_salary_tax_data WHERE batch_id = ? ORDER BY id");
    $stmt->execute([$batch_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Available batches
$batches = $pdo->query("SELECT batch_id, MIN(tax_year) as min_year, MAX(tax_year) as max_year, COUNT(*) as row_count, MAX(calculated_at) as last_calc FROM import_salary_tax_data GROUP BY batch_id ORDER BY MAX(id) DESC LIMIT 20")->fetchAll();

// Summary
$summary = null;
if ($batch_id) {
    $engine = new TESalaryTaxEngine($pdo);
    $summary = $engine->getBatchSummary($batch_id);
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-calculator me-2 text-primary"></i> Salary Tax TE Calculation</h2>
        <p class="text-muted">Review imported monthly filings and estimate Salary Tax Expenditures.</p>
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
    $total_taxable = (float)($summary["total_taxable"] ?? 0);
    $total_exempt = (float)($summary["total_exempt"] ?? 0);
    $total_te = (float)($summary["total_te"] ?? 0);
    $is_calculated = false;
    foreach ($records as $r) {
        if (!empty($r["calculated_at"])) { $is_calculated = true; break; }
    }
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Records</div>
                <div class="h2 mb-0 fw-bold"><?= count($records) ?></div>
                <div class="small text-white-50">in this batch</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total TE</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_te, 0) ?></div>
                <div class="small text-white-50">LAK <?= $is_calculated ? "(calculated)" : "(not yet calculated)" ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Taxable</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_taxable, 0) ?></div>
                <div class="small text-white-50">LAK</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Exempt</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_exempt, 0) ?></div>
                <div class="small text-white-50">LAK</div>
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
                    <label class="form-label fw-bold">Select Salary Tax Batch</label>
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
                        TE = Exempt Amount × 10% benchmark rate.
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
        <table id="resultTable" class="table table-bordered table-hover w-100 mb-0 align-middle" style="font-size:0.82em">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>#</th>
                    <th>TIN</th>
                    <th>Period</th>
                    <th class="text-end">Taxable Amount</th>
                    <th class="text-end">Exempt Amount</th>
                    <th class="text-end">Actual Tax Paid</th>
                    <th class="text-end text-primary">Benchmark Tax</th>
                    <th class="text-end text-success">TE Amount</th>
                    <th>Provision</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($records as $idx => $r): ?>
<?php
    $calced = !empty($r["calculated_at"]);
    $benchmark = (float)($r["benchmark_tax"] ?? 0);
    $te = (float)($r["te_amount"] ?? 0);
    $taxable = (float)($r["total_taxable_amount"] ?? 0);
    $exempt = (float)($r["tax_exempt_amount"] ?? 0);
    $tax_paid = (float)($r["tax_amount"] ?? 0);
?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></small></td>
                    <td><?= htmlspecialchars($r["filing_period"] ?? '') ?></td>
                    <td class="text-end"><?= number_format($taxable, 0) ?></td>
                    <td class="text-end"><?= number_format($exempt, 0) ?></td>
                    <td class="text-end fw-bold"><?= number_format($tax_paid, 0) ?></td>
                    <td class="text-end text-primary fw-bold"><?= $calced ? number_format($benchmark, 0) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end text-success fw-bold"><?= $calced ? number_format($te, 0) : '<span class="text-muted">—</span>' ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r["provision_number"] ?: '-') ?></span></td>
                    <td class="text-center">
                        <a href="view_salary.php?batch=<?= urlencode($batch_id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">BATCH TOTALS</td>
                    <td class="text-end"><?= number_format(array_sum(array_column($records, "total_taxable_amount")), 0) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($records, "tax_exempt_amount")), 0) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($records, "tax_amount")), 0) ?></td>
                    <td class="text-end text-primary"><?= $is_calculated ? number_format(array_sum(array_column($records, "benchmark_tax")), 0) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end text-success"><?= $is_calculated ? number_format(array_sum(array_column($records, "te_amount")), 0) : '<span class="text-muted">—</span>' ?></td>
                    <td></td><td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php elseif ($batch_id && empty($records)): ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
    <h5>No data found for this batch.</h5>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-arrow-up fa-3x mb-3 opacity-10"></i>
    <h5>Select a Salary Tax Batch to start calculation.</h5>
</div>
<?php endif; ?>

<script>
document.getElementById("calcForm")?.addEventListener("submit", function() {
    document.getElementById("runBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    document.getElementById("runBtn").disabled = true;
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
