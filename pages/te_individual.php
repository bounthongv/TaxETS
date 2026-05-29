<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_pit_engine.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? "";
$message = "";
$message_type = "success";
$calc_result = null;

// Run calculation on POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $batch_id = $_POST["batch_id"];
    try {
        $engine = new TEPitEngine($pdo);
        $calc_result = $engine->calculateBatch($batch_id);
        if ($calc_result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$calc_result["calculated"]} individuals</strong> processed. Total TE = <strong>" . number_format($calc_result["total_te"], 0) . " LAK</strong>";
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
    $stmt = $pdo->prepare("SELECT i.*, r.benchmark_calculated_tax, r.te_amount as engine_te, r.matched_provisions
                           FROM import_pit_data i
                           LEFT JOIN te_individual_result r ON r.tin = i.ptin AND r.tax_year = i.tax_year
                           WHERE i.batch_id = ?");
    $stmt->execute([$batch_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Available batches
$batches = $pdo->query("SELECT batch_id, MIN(tax_year) as min_year, MAX(tax_year) as max_year, COUNT(*) as row_count FROM import_pit_data GROUP BY batch_id ORDER BY batch_id DESC LIMIT 20")->fetchAll();

// Load benchmark rates from DB (with fallback)
$db_rates = [
    "21" => 0.15, "22" => 0.05, "23_1" => 0.10, "23_2" => 0.10,
    "24" => 0.05, "25" => 0.05, "26" => 0.02, "27" => 0.10,
    "28_1" => 0.10, "28_2" => 0.10, "29" => 0.15,
];
try {
    $flat_rates = $pdo->query("SELECT income_type, rate_percentage FROM bm_pit_flat_rates")->fetchAll();
    foreach ($flat_rates as $r) {
        if ($r["income_type"] == "Dividends") $db_rates["27"] = $r["rate_percentage"] / 100;
        if ($r["income_type"] == "Shares Transfer") $db_rates["26"] = $r["rate_percentage"] / 100;
        if ($r["income_type"] == "Rental Income") {
            $db_rates["23_1"] = $r["rate_percentage"] / 100;
            $db_rates["23_2"] = $r["rate_percentage"] / 100;
        }
    }
} catch (Exception $e) {}

$prov_cols = [
    "21" => "amount_21", "22" => "amount_22", "23_1" => "amount_23_1", "23_2" => "amount_23_2",
    "24" => "amount_24", "25" => "amount_25", "26" => "amount_26", "27" => "amount_27",
    "28_1" => "amount_28_1", "28_2" => "amount_28_2", "29" => "amount_29"
];

$prov_names = [
    "21" => "Overtime/Night Shift", "22" => "Severance/Redundancy",
    "23_1" => "Rental (Building)", "23_2" => "Rental (Land/Other)",
    "24" => "Consulting/Service", "25" => "Contractor Income",
    "26" => "Shares Transfer", "27" => "Dividends",
    "28_1" => "Interest (Loan)", "28_2" => "Interest (Bonds)",
    "29" => "Gifts/Bonus", "30" => "Social Security Benefits"
];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="fas fa-layer-group me-2 text-primary"></i> Individual Tax TE Calculation</h2>
        <p class="text-muted">Calculate Tax Expenditure using PIT benchmark rates and progressive brackets.</p>
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
<?php if (!empty($records)): ?>
<?php
    $total_income = 0;
    $total_engine_te = 0;
    $total_expert_te = 0;
    $calc_count = 0;
    $matched_count = 0;
    foreach ($records as $r) {
        $ti = 0;
        foreach ($prov_cols as $col) { $ti += (float)($r[$col] ?? 0); }
        $total_income += $ti;
        $total_engine_te += (float)($r["engine_te"] ?? 0);
        $total_expert_te += (float)($r["expert_te_total"] ?? 0);
        if (!empty($r["engine_te"])) $calc_count++;
        if (!empty($r["matched_provisions"])) $matched_count++;
    }
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Individuals</div>
                <div class="h2 mb-0 fw-bold"><?= count($records) ?></div>
                <div class="small text-white-50">in this batch</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total TE (Engine)</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_engine_te, 0) ?></div>
                <div class="small text-white-50">LAK — <?= $calc_count ?> individuals calculated</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Expert TE</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_expert_te, 0) ?></div>
                <div class="small text-white-50">LAK — expert-provided values</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-white h-100" style="border-radius:12px">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Variance</div>
                <div class="h4 mb-0 fw-bold"><?= number_format($total_engine_te - $total_expert_te, 0) ?></div>
                <div class="small text-white-50"><?= $total_expert_te > 0 ? number_format(abs($total_engine_te - $total_expert_te) / $total_expert_te * 100, 1) : 0 ?>% from expert</div>
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
                    <label class="form-label fw-bold">Select PIT Batch</label>
                    <select name="batch_id" class="form-select form-select-lg" required>
                        <option value="">-- Select a batch --</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?= htmlspecialchars($b["batch_id"]) ?>" <?= ($batch_id === $b["batch_id"]) ? "selected" : "" ?>>
                            <?= htmlspecialchars($b["batch_id"]) ?> (<?= $b["min_year"] == $b["max_year"] ? $b["min_year"] : $b["min_year"] . "-" . $b["max_year"] ?> | <?= $b["row_count"] ?> records)
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
                        Make sure PIT benchmark rates and provisions are configured first.
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
            <span class="badge bg-primary rounded-pill me-2"><?= $calc_count ?> Calculated</span>
            <span class="badge bg-warning text-dark rounded-pill"><?= $matched_count ?> Matched</span>
        </div>
    </div>
    <div class="card-body p-0">
        <table id="resultTable" class="table table-bordered table-hover w-100 mb-0 align-middle" style="font-size:0.82em">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>#</th>
                    <th>Name / PTIN</th>
                    <th class="text-center">Year</th>
                    <th class="text-end">Total Income</th>
                    <th class="text-end text-primary">Engine TE</th>
                    <th class="text-end text-info">Expert TE</th>
                    <th class="text-end">Variance</th>
                    <th class="text-center">Provisions</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
<?php
$total_income = 0;
$total_engine_te = 0;
$total_expert_te = 0;
foreach ($records as $idx => $r):
    $sum_income = 0;
    $sys_te_list = [];
    foreach ($prov_cols as $id => $col) {
        $amt = (float)($r[$col] ?? 0);
        if ($amt > 0) {
            $rate = $db_rates[str_replace('.', '_', $id)] ?? 0.10;
            $te = $amt * $rate;
            $sys_te_list[] = ["num" => $id, "name" => ($prov_names[$id] ?? $id), "amt" => $amt, "rate" => $rate, "te" => $te];
            $sum_income += $amt;
        }
    }
    if (!empty($r["is_ss_member"])) {
        $sys_te_list[] = ["num" => "30", "name" => "Social Security Benefits", "amt" => 0, "rate" => 0, "te" => 0];
    }

    $dummy_math = array_sum(array_column($sys_te_list, "te"));
    $engine_te = (float)($r["engine_te"] ?? 0);
    $expert_te = (float)($r["expert_te_total"] ?? 0);
    $variance = $engine_te - $expert_te;
    $total_income += $sum_income;
    $total_engine_te += $engine_te;
    $total_expert_te += $expert_te;
    $row_id = "detail_" . $idx;
?>
                <tr class="row-parent" data-bs-toggle="collapse" data-bs-target="#<?= $row_id ?>">
                    <td><?= $idx + 1 ?></td>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($r["employee_name"]) ?></div>
                        <div class="text-muted font-monospace small"><?= htmlspecialchars($r["ptin"]) ?></div>
                    </td>
                    <td class="text-center"><?= $r["tax_year"] ?></td>
                    <td class="text-end"><?= number_format($sum_income, 0) ?></td>
                    <td class="text-end fw-bold text-primary"><?= number_format($engine_te, 0) ?></td>
                    <td class="text-end text-info"><?= number_format($expert_te, 0) ?></td>
                    <td class="text-end">
                        <span class="badge bg-<?= abs($variance) < 1 ? "success" : ($variance > 0 ? "danger" : "warning") ?>">
                            <?= number_format($variance, 0) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($r["matched_provisions"]): ?>
                            <?php foreach (explode(",", $r["matched_provisions"]) as $pn): ?>
                                <span class="badge bg-secondary me-1"><?= trim($pn) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="view_individual.php?batch=<?= urlencode($batch_id) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                <tr>
                    <td colspan="9" class="p-0 border-0">
                        <div class="collapse" id="<?= $row_id ?>">
                            <div class="p-4 px-5 bg-light">
                                <h6 class="fw-bold mb-3"><i class="fas fa-list-ul me-2 text-primary"></i> Provision Breakdown</h6>
                                <?php if (empty($sys_te_list)): ?>
                                <div class="text-muted small">No non-zero provision amounts recorded.</div>
                                <?php else: ?>
                                <table class="table table-sm table-borderless small mb-0">
                                    <thead><tr class="text-muted border-bottom">
                                        <th>Category</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Rate</th>
                                        <th class="text-end text-primary">TE</th>
                                    </tr></thead>
                                    <tbody>
                                        <?php foreach ($sys_te_list as $item): ?>
                                        <tr class="border-bottom-dashed">
                                            <td class="py-1"><span class="badge bg-secondary me-1">#<?= $item['num'] ?></span> <?= $item['name'] ?></td>
                                            <td class="text-end py-1"><?= $item['amt'] > 0 ? number_format($item['amt'], 0) : '<em class="small">Flag</em>' ?></td>
                                            <td class="text-center py-1 text-muted"><?= $item['rate'] > 0 ? ($item['rate'] * 100 . "%") : "-" ?></td>
                                            <td class="text-end py-1 fw-bold text-primary"><?= number_format($item['te'], 0) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">BATCH TOTALS</td>
                    <td class="text-end"><?= number_format($total_income, 0) ?></td>
                    <td class="text-end text-primary"><?= number_format($total_engine_te, 0) ?></td>
                    <td class="text-end text-info"><?= number_format($total_expert_te, 0) ?></td>
                    <td class="text-end">
                        <span class="badge bg-<?= abs($total_engine_te - $total_expert_te) < 1 ? "success" : "danger" ?> fs-6">
                            <?= number_format($total_engine_te - $total_expert_te, 0) ?>
                        </span>
                    </td>
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
    <h5>Select a PIT Data Batch to start calculation.</h5>
</div>
<?php endif; ?>

<script>
document.getElementById("calcForm")?.addEventListener("submit", function() {
    document.getElementById("runBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    document.getElementById("runBtn").disabled = true;
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
