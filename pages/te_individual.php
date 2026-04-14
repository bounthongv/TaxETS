<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_pit_engine.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$batch_id = $_GET["batch"] ?? $_POST["batch_id"] ?? "";
$records = [];
$message = "";
$message_type = "success";
$calc_result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    try {
        $engine = new TEPitEngine($pdo);
        $calc_result = $engine->calculateBatch($_POST["batch_id"]);
        
        if ($calc_result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$calc_result["calculated"]} individuals</strong> processed. Total TE: <strong>" . number_format($calc_result["total_te"], 2) . " LAK</strong>";
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

if ($batch_id) {
    try {
        $stmt = $pdo->prepare("SELECT i.*, r.benchmark_calculated_tax, r.te_amount as engine_te, r.matched_provisions 
                               FROM import_pit_data i 
                               LEFT JOIN te_individual_result r ON r.tin = i.ptin AND r.tax_year = i.tax_year 
                               WHERE i.batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($records)) {
            $message = "No records found for batch: " . htmlspecialchars($batch_id);
            $message_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Error fetching data: " . $e->getMessage();
        $message_type = "danger";
    }
}

$batches = [];
try {
    $batches = $pdo->query("SELECT batch_id, MIN(tax_year) as min_year, MAX(tax_year) as max_year, COUNT(*) as row_count FROM import_pit_data GROUP BY batch_id ORDER BY batch_id DESC LIMIT 20")->fetchAll();
} catch (Exception $e) { }

$db_rates = [
    "21" => 0.15, "22" => 0.05, "23_1" => 0.10, "23_2" => 0.10,
    "24" => 0.05, "25" => 0.05, "26" => 0.02, "27" => 0.10,
    "28_1" => 0.10, "28_2" => 0.10, "29" => 0.15,
];

try {
    $flat_rates_raw = $pdo->query("SELECT income_type, rate_percentage FROM bm_pit_flat_rates")->fetchAll();
    foreach ($flat_rates_raw as $r) {
        if ($r["income_type"] == "Dividends") $db_rates["27"] = $r["rate_percentage"] / 100;
        if ($r["income_type"] == "Shares Transfer") $db_rates["26"] = $r["rate_percentage"] / 100;
        if ($r["income_type"] == "Rental Income") {
            $db_rates["23_1"] = $r["rate_percentage"] / 100;
            $db_rates["23_2"] = $r["rate_percentage"] / 100;
        }
    }
} catch (Exception $e) { }

$prov_cols = [
    "21" => "amount_21", "22" => "amount_22", "23.1" => "amount_23_1", "23.2" => "amount_23_2",
    "24" => "amount_24", "25" => "amount_25", "26" => "amount_26", "27" => "amount_27",
    "28.1" => "amount_28_1", "28.2" => "amount_28_2", "29" => "amount_29"
];

function getProvName($id) {
    $names = [
        "21" => "Overtime/Night Shift", "22" => "Severance/Redundancy",
        "23.1" => "Rental (Building)", "23.2" => "Rental (Land/Other)",
        "24" => "Consulting/Service", "25" => "Contractor Income",
        "26" => "Shares Transfer", "27" => "Dividends",
        "28.1" => "Interest (Loan)", "28.2" => "Interest (Bonds)",
        "29" => "Gifts/Bonus", "30" => "Social Security Benefits"
    ];
    return $names[$id] ?? "Provision $id";
}
?>

<style>
.card-calc { border-radius: 15px; border: none; overflow: hidden; }
.table-parent th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; background: #f8f9fa; border-top: none; }
.row-parent { cursor: pointer; transition: background 0.2s; }
.row-parent:hover { background-color: rgba(13, 110, 253, 0.05) !important; }
.row-child { background-color: #fcfcfc; }
.detail-card { border-left: 4px solid #0d6efd; background: white; margin: 10px 0; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.prov-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; }
.variance-pos { color: #dc3545; font-weight: bold; }
.variance-neg { color: #198754; font-weight: bold; }
.variance-zero { color: #6c757d; font-weight: normal; }
</style>

<div class="row mb-3">
    <div class="col-12 text-start">
        <h2><i class="fas fa-layer-group me-2 text-primary"></i> Individual TE Calculation Engine</h2>
        <p class="text-muted">Calculate Tax Expenditure using PIT benchmark rates and progressive brackets.</p>
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

<div class="card mb-4 shadow-sm card-calc">
    <div class="card-body bg-white border-bottom py-4">
        <form method="POST" class="row align-items-center g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Calculation Batch</label>
                <select name="batch_id" class="form-select border-0 bg-light shadow-none" required>
                    <option value="">-- Choose a batch --</option>
                    <?php foreach ($batches as $b): ?>
                    <option value="<?= htmlspecialchars($b["batch_id"]) ?>" <?= ($batch_id === $b["batch_id"]) ? "selected" : "" ?>>
                        <?= htmlspecialchars($b["batch_id"]) ?> (Years: <?= $b["min_year"] == $b["max_year"] ? $b["min_year"] : $b["min_year"] . "-" . $b["max_year"] ?> | <?= $b["row_count"] ?> records)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-play me-2"></i> Run Engine</button>
            </div>
            <div class="col-md-5 text-end small text-muted align-self-end pb-1">
                <i class="fas fa-info-circle me-1 text-primary"></i> Click "Run Engine" to calculate TE for all individuals in the batch.
            </div>
        </form>
    </div>
</div>

<?php if (!empty($records)): ?>
<div class="card shadow-sm border-0 card-calc">
    <div class="card-body p-0">
        <table class="table mb-0 table-parent align-middle">
            <thead>
                <tr>
                    <th class="ps-4">Employee Name / PTIN</th>
                    <th class="text-center">Filing Date</th>
                    <th class="text-end">Total Income</th>
                    <th class="text-end text-primary">Engine TE (DB)</th>
                    <th class="text-end text-info">Dummy Math TE</th>
                    <th class="text-end pe-4">Variance</th>
                </tr>
            </thead>
            <tbody>
<?php
$total_sys_batch = 0;
$total_exp_batch = 0;
foreach ($records as $idx => $r):
    $sys_te_list = [];
    $sum_income = 0;

    foreach ($prov_cols as $id => $col) {
        if (isset($r[$col])) {
            $amt = (float)$r[$col];
            if ($amt > 0) {
                $rate_key = str_replace('.', '_', $id);
                $rate = $db_rates[$rate_key] ?? 0.10;
                $te = $amt * $rate;
                $sys_te_list[] = ["num" => $id, "name" => getProvName($id), "amt" => $amt, "rate" => $rate, "te" => $te];
                $sum_income += $amt;
            }
        }
    }

    if (isset($r["is_ss_member"]) && $r["is_ss_member"]) {
        $sys_te_list[] = ["num" => "30", "name" => getProvName("30"), "amt" => 0, "rate" => 0, "te" => 0];
    }

    $dummy_math_total = array_sum(array_column($sys_te_list, "te"));
    $engine_total = isset($r["engine_te"]) ? (float)$r["engine_te"] : 0.0;
    $variance = $engine_total - $dummy_math_total;
    $total_sys_batch += $engine_total;
    $total_exp_batch += $dummy_math_total;
    $row_id = "detail_" . $idx;
?>
                <tr class="row-parent" data-bs-toggle="collapse" data-bs-target="#<?= $row_id ?>">
                    <td class="ps-4 py-3">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($r["employee_name"]) ?></div>
                        <div class="text-muted small">PTIN: <?= htmlspecialchars($r["ptin"]) ?></div>
                        <?php if(!empty($r["matched_provisions"])): ?>
                            <div class="text-success small fw-bold mt-1">Matched: <?= htmlspecialchars($r["matched_provisions"]) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-muted small"><?= $r["filing_date"] ?: "N/A" ?></td>
                    <td class="text-end"><?= number_format($sum_income) ?></td>
                    <td class="text-end fw-bold text-primary"><?= number_format($engine_total) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format($dummy_math_total) ?></td>
                    <td class="text-end pe-4">
                        <span class="<?= $variance > 1 ? 'variance-pos' : ($variance < -1 ? 'variance-neg' : 'variance-zero') ?>">
                            <?= number_format($variance) ?>
                            <i class="fas fa-chevron-down ms-2 small opacity-50"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="p-0 border-0">
                        <div class="collapse row-child" id="<?= $row_id ?>">
                            <div class="p-4 px-5">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-list-ul me-2 text-primary"></i> Provision Breakdown for <?= htmlspecialchars($r["employee_name"]) ?></h6>
                                        <?php if (empty($sys_te_list)): ?>
                                        <div class="text-muted italic small">No non-zero provision amounts recorded for this employee.</div>
                                        <?php else: ?>
                                        <table class="table table-sm table-borderless small">
                                            <thead>
                                                <tr class="text-muted border-bottom">
                                                    <th>Provision Category</th>
                                                    <th class="text-end">Amount (LAK)</th>
                                                    <th class="text-center">Rate</th>
                                                    <th class="text-end text-primary">System TE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sys_te_list as $item): ?>
                                                <tr class="border-bottom-dashed">
                                                    <td class="py-2">
                                                        <span class="badge bg-secondary me-2 prov-badge">T#<?= $item['num'] ?></span>
                                                        <?= $item['name'] ?>
                                                    </td>
                                                    <td class="text-end py-2"><?= $item['amt'] > 0 ? number_format($item['amt']) : "<em>Flag Only</em>" ?></td>
                                                    <td class="text-center py-2 text-muted"><?= $item['rate'] > 0 ? ($item['rate'] * 100 . "%") : "-" ?></td>
                                                    <td class="text-end py-2 fw-bold text-primary"><?= number_format($item['te']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-card">
                                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Technical Note</h6>
                                            <p class="small mb-0">This view dynamically maps database columns to tax categories. Variance indicates a mismatch in benchmark rate assumptions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold py-3 fs-6">
                    <td class="ps-4">BATCH CONSOLIDATED TOTALS</td>
                    <td class="text-end"></td>
                    <td class="text-end text-primary"><?= number_format($total_sys_batch) ?></td>
                    <td class="text-end text-info"><?= number_format($total_exp_batch) ?></td>
                    <td class="text-end pe-4 <?= abs($total_sys_batch - $total_exp_batch) < 1 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($total_sys_batch - $total_exp_batch) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php elseif ($batch_id && empty($records)): ?>
<div class="text-center py-5">
    <i class="fas fa-search fa-3x text-muted mb-3 opacity-25"></i>
    <h5>No data found for this batch.</h5>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-arrow-up fa-3x mb-3 opacity-10"></i>
    <h5>Select a PIT Data Batch to start calculation.</h5>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
