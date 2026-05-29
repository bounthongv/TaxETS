<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_pit_engine.php";

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';
$message = '';
$msg_type = 'success';

// Handle Calculate action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate" && !empty($_POST["batch_id"])) {
    try {
        $engine = new TEPitEngine($pdo);
        $result = $engine->calculateBatch($_POST["batch_id"]);
        if ($result["calculated"] > 0) {
            $message = "Calculation complete! <strong>{$result["calculated"]} individuals</strong> processed. Total TE = <strong>" . number_format($result["total_te"], 0) . " LAK</strong>";
        } else {
            $message = "No records found for batch: " . htmlspecialchars($_POST["batch_id"]);
            $msg_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Engine error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// --- Batch List Mode ---
if (!$batch):
    $all_rows = $pdo->query("SELECT i.*, r.te_amount as engine_te, r.benchmark_calculated_tax, r.matched_provisions 
                            FROM import_pit_data i 
                            LEFT JOIN te_individual_result r ON i.ptin = r.tin AND i.tax_year = r.tax_year 
                            ORDER BY i.id DESC")->fetchAll();

    $total_records = count($all_rows);
    $total_income = 0;
    $total_engine_te = 0;
    $total_expert_te = 0;
    foreach ($all_rows as $r) {
        $ti = 0;
        foreach (['amount_21','amount_22','amount_23_1','amount_23_2','amount_24','amount_25','amount_26','amount_27','amount_28_1','amount_28_2','amount_29'] as $col) {
            $ti += (float)($r[$col] ?? 0);
        }
        $total_income += $ti;
        $total_engine_te += (float)($r['engine_te'] ?? 0);
        $total_expert_te += (float)($r['expert_te_total'] ?? 0);
    }

    $batches = $pdo->query("SELECT i.batch_id, COUNT(*) as `rows`,
                            COALESCE(SUM(r.te_amount), 0) as total_te,
                            COALESCE(SUM(i.expert_te_total), 0) as total_expert
                            FROM import_pit_data i
                            LEFT JOIN te_individual_result r ON i.ptin = r.tin AND i.tax_year = r.tax_year
                            GROUP BY i.batch_id
                            ORDER BY MAX(i.id) DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-layer-group me-2 text-primary"></i> Individual Tax TE Calculation</h2>
        <p class="text-muted">Calculate and review Tax Expenditure for Individual Tax batches.</p>
    </div>
    <div class="col-md-4 text-end"></div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row mb-3 g-2">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Total Records</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_income) ?></div>
            <div class="small opacity-75">Total Income</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_engine_te) ?></div>
            <div class="small opacity-75">Engine TE</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_expert_te) ?></div>
            <div class="small opacity-75">Expert TE</div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> Import Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Individual Tax Data Found</h5>
            <p>Import data from the <a href="import_individual.php">Data Requirement &gt; Individual Tax</a> page first.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch</th><th>Records</th><th class="text-end">Engine TE</th><th class="text-end">Expert TE</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["batch_id"]) ?></small></td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format($b["rows"]) ?></span></td>
                    <td class="text-end fw-bold text-success"><?= number_format($b["total_te"]) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format($b["total_expert"]) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="calculate">
                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($b["batch_id"]) ?>">
                            <button class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></button>
                        </form>
                        <a href="?batch=<?= urlencode($b["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php
// --- Batch Detail Mode ---
else:
    $stmt = $pdo->prepare("SELECT i.*, r.benchmark_calculated_tax, r.te_amount as engine_te, r.matched_provisions 
                           FROM import_pit_data i 
                           LEFT JOIN te_individual_result r ON i.ptin = r.tin AND i.tax_year = r.tax_year 
                           WHERE i.batch_id = ?
                           ORDER BY i.id ASC");
    $stmt->execute([$batch]);
    $rows = $stmt->fetchAll();

    $total_records = count($rows);
    $total_income = 0;
    $total_engine_te = 0;
    $total_expert_te = 0;
    $calc_count = 0;
    foreach ($rows as $r) {
        $ti = 0;
        foreach (['amount_21','amount_22','amount_23_1','amount_23_2','amount_24','amount_25','amount_26','amount_27','amount_28_1','amount_28_2','amount_29'] as $col) {
            $ti += (float)($r[$col] ?? 0);
        }
        $total_income += $ti;
        $total_engine_te += (float)($r['engine_te'] ?? 0);
        $total_expert_te += (float)($r['expert_te_total'] ?? 0);
        if (!empty($r['engine_te'])) $calc_count++;
    }

    $years = $pdo->prepare("SELECT DISTINCT tax_year FROM import_pit_data WHERE batch_id = ? ORDER BY tax_year DESC");
    $years->execute([$batch]);
    $year_list = $years->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="te_individual.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Individual Tax TE: Batch Results</h2>
        <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> — <strong><?= $total_records ?></strong> records (<?= $calc_count ?> calculated)</p>
    </div>
    <div class="col-md-4 text-end">
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="calculate">
            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($batch) ?>">
            <button class="btn btn-success" id="runBtn"><i class="fas fa-calculator me-2"></i> Run TE Calculation</button>
        </form>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row mb-3 g-2">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Records</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_income) ?></div>
            <div class="small opacity-75">Total Income</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_engine_te) ?></div>
            <div class="small opacity-75">Engine TE</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_expert_te) ?></div>
            <div class="small opacity-75">Expert TE</div>
        </div>
    </div>
</div>

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Search PTIN / Name</label>
                <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Tax Year</label>
                <select id="filterYear" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($year_list as $y): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()" title="Reset Filters"><i class="fas fa-undo"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table id="teTable" class="table table-bordered table-hover w-100" style="font-size:0.85em">
            <thead class="text-uppercase small">
                <tr class="text-nowrap">
                    <th>#</th>
                    <th>Year</th>
                    <th>PTIN</th>
                    <th>Employee Name</th>
                    <th>Filing Date</th>
                    <th class="text-end">Total Income</th>
                    <th class="text-center">SS Member</th>
                    <th class="text-end table-info">BM TE</th>
                    <th class="text-end table-success">Engine TE</th>
                    <th class="text-end">Expert TE</th>
                    <th>Provisions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                <?php
                    $sum_income = 0;
                    foreach (['amount_21','amount_22','amount_23_1','amount_23_2','amount_24','amount_25','amount_26','amount_27','amount_28_1','amount_28_2','amount_29'] as $col) {
                        $sum_income += (float)($r[$col] ?? 0);
                    }
                    $engine_te = (float)($r['engine_te'] ?? 0);
                    $expert_te = (float)($r['expert_te_total'] ?? 0);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $r["tax_year"] ?></td>
                    <td class="font-monospace fw-bold"><?= htmlspecialchars($r["ptin"]) ?></td>
                    <td><?= htmlspecialchars($r["employee_name"]) ?></td>
                    <td><?= htmlspecialchars($r["filing_date"] ?? '') ?></td>
                    <td class="text-end"><?= number_format($sum_income, 0) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $r["is_ss_member"] ? "success" : "secondary" ?>">
                            <?= $r["is_ss_member"] ? "YES" : "NO" ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-info"><?= number_format($r["benchmark_calculated_tax"] ?? 0, 0) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($engine_te, 0) ?></td>
                    <td class="text-end fw-bold"><?= number_format($expert_te, 0) ?></td>
                    <td>
                        <?php if ($r["matched_provisions"]): ?>
                            <?php foreach (explode(",", $r["matched_provisions"]) as $pn): ?>
                            <span class="badge bg-primary me-1"><?= trim($pn) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="11" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-warning fw-bold">
                    <td colspan="5" class="text-end">Totals:</td>
                    <td class="text-end"><?= number_format($total_income, 0) ?></td>
                    <td></td>
                    <td class="text-end text-info"><?= number_format(array_sum(array_column($rows, 'benchmark_calculated_tax')), 0) ?></td>
                    <td class="text-end text-success"><?= number_format($total_engine_te, 0) ?></td>
                    <td class="text-end"><?= number_format($total_expert_te, 0) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = $('#teTable').DataTable({
        dom: 'rtip',
        pageLength: 50,
        order: [],
        columnDefs: [{ targets: '_all', className: 'dt-body-left' }]
    });

    document.getElementById('customSearch').addEventListener('keyup', function() {
        table.search(this.value).draw();
    });
    document.getElementById('filterYear').addEventListener('change', function() {
        table.column(1).search(this.value).draw();
    });
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterYear').value = '';
    $('#teTable').DataTable().search('').columns().search('').draw();
}

document.getElementById('runBtn')?.addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
    this.disabled = true;
});
</script>
<style>
table.dataTable td { padding: 6px 8px !important; vertical-align: middle; }
table.dataTable thead th { padding: 8px 6px !important; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
.dataTables_info { font-size: 0.8rem; padding: 8px 12px !important; }
.dataTables_paginate { padding: 8px 12px !important; }
</style>

<?php
endif;
require_once __DIR__ . "/../includes/footer.php";
