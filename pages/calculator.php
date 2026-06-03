<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_profit_tax_engine.php";

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';
$message = '';
$msg_type = 'success';

// Handle Calculate action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate" && !empty($_POST["batch_id"])) {
    try {
        $engine = new TEEngine($pdo);
        $summary = $engine->calculateBatch($_POST["batch_id"]);
        $return_to = $_POST["return_to"] ?? "";
        $qs = http_build_query([
            "batch"     => $_POST["batch_id"],
            "calc"      => "1",
            "count"     => $summary["calculated"] ?? 0,
            "total_te"  => $summary["total_te"] ?? 0,
            "errors"    => !empty($summary["errors"]) ? count($summary["errors"]) : 0,
        ]);
        // If called from import_cit.php / view_companies.php — redirect back with success
        if ($return_to === "import_cit") {
            header("Location: import_cit.php?calc_done=1&" . $qs);
            exit;
        }
        // Otherwise — redirect to batch detail view (recalculation flow)
        header("Location: calculator.php?" . $qs);
        exit;
    } catch (Exception $e) {
        $message = "Engine error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Show success banner on redirect back to detail view
if (isset($_GET["calc"]) && $_GET["calc"] === "1" && $batch) {
    $count = (int)($_GET["count"] ?? 0);
    $total = (float)($_GET["total_te"] ?? 0);
    $err = (int)($_GET["errors"] ?? 0);
    if ($err > 0) {
        $message = "Recalculation: <strong>{$count}</strong> companies, <strong>" . number_format($total, 0) . " LAK</strong> total TE. <span class='text-warning'>{$err} issue(s)</span> — check data.";
        $msg_type = "warning";
    } elseif ($count > 0) {
        $message = "Recalculation: <strong>{$count}</strong> companies processed. Total TE = <strong>" . number_format($total, 0) . " LAK</strong>";
    }
}

// --- Batch List Mode ---
if (!$batch):
    $all_rows = $pdo->query("SELECT c.*, r.benchmark_pt, r.profit_tax_te 
                            FROM companies c 
                            LEFT JOIN te_profit_result r ON c.id = r.company_id 
                            ORDER BY c.id DESC")->fetchAll();

    $total_records = count($all_rows);
    $total_bm = array_sum(array_column($all_rows, 'benchmark_pt'));
    $total_paid = array_sum(array_column($all_rows, 'pt_paid'));
    $total_te = array_sum(array_column($all_rows, 'profit_tax_te'));

    $batches = $pdo->query("SELECT c.import_batch_id, 
                            COUNT(*) as total_rows,
                            MIN(c.tax_year) as min_year,
                            MAX(c.tax_year) as max_year,
                            GROUP_CONCAT(DISTINCT c.tax_year ORDER BY c.tax_year SEPARATOR ',') as year_list,
                            COALESCE(SUM(r.benchmark_pt), 0) as total_bm,
                            SUM(c.pt_paid) as total_paid,
                            COALESCE(SUM(r.profit_tax_te), 0) as total_te
                            FROM companies c
                            LEFT JOIN te_profit_result r ON c.id = r.company_id
                            GROUP BY c.import_batch_id
                            ORDER BY MAX(c.id) DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-calculator me-2 text-success"></i> Profit Tax TE Calculation</h2>
        <p class="text-muted">Calculate and review Tax Expenditure for Corporate Income Tax batches.</p>
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
            <div class="fs-5 fw-bold"><?= number_format($total_bm) ?></div>
            <div class="small opacity-75">Total BM PT</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">Total PT Paid</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-danger text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_te) ?></div>
            <div class="small opacity-75">Total TE</div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> Import Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No CIT Data Found</h5>
            <p>Import data from the <a href="import_cit.php">Data Requirement &gt; Profit Tax</a> page first.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch</th><th>Year</th><th>Records</th><th class="text-end">BM PT</th><th class="text-end">PT Paid</th><th class="text-end">TE Total</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($batches as $b): 
                    $b_years = explode(',', $b["year_list"]);
                ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["import_batch_id"]) ?></small></td>
                    <td>
                        <?php if ($b["min_year"] == $b["max_year"]): ?>
                            <?= $b["min_year"] ?>
                        <?php else: ?>
                            <?php foreach ($b_years as $y): ?>
                            <span class="badge bg-secondary me-1" style="font-size:0.7rem"><?= $y ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format($b["total_rows"]) ?></span></td>
                    <td class="text-end fw-bold text-info"><?= number_format($b["total_bm"]) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($b["total_paid"]) ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format($b["total_te"]) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="calculate">
                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($b["import_batch_id"]) ?>">
                            <button class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></button>
                        </form>
                        <a href="?batch=<?= urlencode($b["import_batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
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
    $stmt = $pdo->prepare("SELECT c.*, r.benchmark_rate_applied, r.benchmark_pt, r.pt_te, r.matched_provisions, r.profit_tax_te, r.expert_te 
                           FROM companies c 
                           LEFT JOIN te_profit_result r ON c.id = r.company_id 
                           WHERE c.import_batch_id = ?
                           ORDER BY c.id ASC");
    $stmt->execute([$batch]);
    $rows = $stmt->fetchAll();

    $total_records = count($rows);
    $total_bm = array_sum(array_column($rows, 'benchmark_pt'));
    $total_paid = array_sum(array_column($rows, 'pt_paid'));
    $total_te = array_sum(array_column($rows, 'profit_tax_te'));

    $provinces = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces ORDER BY province_name")->fetchAll();
    $sectors = $pdo->query("SELECT id, sector_name FROM business_sectors WHERE active = 1 ORDER BY sector_name")->fetchAll();
    $years = $pdo->prepare("SELECT DISTINCT tax_year FROM companies WHERE import_batch_id = ? ORDER BY tax_year DESC");
    $years->execute([$batch]);
    $year_list = $years->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . "/../includes/header.php";
$is_admin = ($_SESSION["user_email"] ?? '') === "admin@example.com";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="calculator.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Profit Tax TE: Batch Results</h2>
        <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> — <strong><?= $total_records ?></strong> records</p>
    </div>
    <div class="col-md-4 text-end">
        <form method="POST" class="d-inline" id="calcForm">
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
            <div class="fs-5 fw-bold"><?= number_format($total_bm) ?></div>
            <div class="small opacity-75">BM PT</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">PT Paid</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-danger text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_te) ?></div>
            <div class="small opacity-75">TE Total</div>
        </div>
    </div>
</div>

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Search TIN / Name</label>
                <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Province</label>
                <select id="filterProvince" class="form-select">
                    <option value="">All Provinces</option>
                    <?php foreach ($provinces as $p): ?>
                    <option value="<?= htmlspecialchars($p["pro_name"]) ?>"><?= htmlspecialchars($p["pro_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Sector</label>
                <select id="filterSector" class="form-select">
                    <option value="">All Sectors</option>
                    <?php foreach ($sectors as $s): ?>
                    <option value="<?= htmlspecialchars($s["sector_name"]) ?>"><?= htmlspecialchars($s["sector_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-bold text-muted">Year</label>
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
                    <th>TIN</th>
                    <th>Company Name</th>
                    <th>Province</th>
                    <th>District</th>
                    <th>Sector</th>
                    <th>VAT?</th>
                    <th class="text-end">Staff</th>
                    <th class="text-end">Revenue</th>
                    <th class="text-end">Net Profit</th>
                    <th class="text-end table-info">BM PT</th>
                    <th class="text-end">PT Paid</th>
                    <th class="text-end table-danger">PT TE</th>
                    <?php if ($is_admin): ?>
                    <th class="text-end table-warning">Expert TE</th>
                    <th class="text-center" style="width:40px">Δ</th>
                    <?php endif; ?>
                    <th>Provisions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): 
                    $has_diff = $is_admin && $r["expert_te"] !== null && abs((float)$r["profit_tax_te"] - (float)$r["expert_te"]) > 0.01;
                ?>
                <tr class="<?= $has_diff ? 'table-warning' : '' ?>">
                    <td><?= $i + 1 ?></td>
                    <td><?= $r["tax_year"] ?></td>
                    <td class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></td>
                    <td><?= htmlspecialchars($r["company_name"]) ?></td>
                    <td><?= htmlspecialchars($r["province"]) ?></td>
                    <td><?= htmlspecialchars($r["district"] ?? '') ?></td>
                    <td><?= htmlspecialchars($r["sector"]) ?></td>
                    <td><span class="badge bg-<?= $r["is_vat_holder"] ? "success" : "secondary" ?>"><?= $r["is_vat_holder"] ? "YES" : "NO" ?></span></td>
                    <td class="text-end"><?= number_format($r["staff_count"]) ?></td>
                    <td class="text-end"><?= number_format($r["revenue"], 0) ?></td>
                    <td class="text-end"><?= number_format($r["net_profit"], 0) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format($r["benchmark_pt"], 0) ?></td>
                    <td class="text-end"><?= number_format($r["pt_paid"], 0) ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format($r["profit_tax_te"], 0) ?></td>
                    <?php if ($is_admin): ?>
                    <td class="text-end fw-bold text-warning"><?= $r["expert_te"] !== null ? number_format($r["expert_te"], 0) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-center">
                        <?php if ($r["expert_te"] !== null): ?>
                            <?php $diff = (float)$r["profit_tax_te"] - (float)$r["expert_te"]; ?>
                            <?php if (abs($diff) > 0.01): ?>
                                <span class="badge bg-<?= abs($diff) > 1000000 ? 'danger' : 'warning' ?> text-dark" title="System TE - Expert TE">
                                    <?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 0) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-success" title="Matches Expert TE">✓</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <?php if ($r["matched_provisions"]): ?>
                            <?php foreach (explode(",", $r["matched_provisions"]) as $pn): ?>
                            <span class="badge bg-primary me-1"><?= trim($pn) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small">None</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="<?= $is_admin ? 17 : 15 ?>" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-warning fw-bold">
                    <td colspan="11" class="text-end">Total Tax Expenditure (LAK):</td>
                    <td class="text-end text-info"><?= number_format($total_bm, 0) ?></td>
                    <td class="text-end text-success"><?= number_format($total_paid, 0) ?></td>
                    <td class="text-end text-danger"><?= number_format($total_te, 0) ?></td>
                    <?php if ($is_admin): ?>
                    <?php $total_expert = array_sum(array_filter(array_column($rows, 'expert_te'), fn($v) => $v !== null)); ?>
                    <td class="text-end fw-bold text-warning"><?= number_format($total_expert, 0) ?></td>
                    <td class="text-center">
                        <?php $total_diff = $total_te - $total_expert; ?>
                        <?php if (abs($total_diff) > 0.01): ?>
                            <span class="badge bg-<?= abs($total_diff) > 1000000 ? 'danger' : 'warning' ?> text-dark">
                                <?= $total_diff > 0 ? '+' : '' ?><?= number_format($total_diff, 0) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-success">✓</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
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
    document.getElementById('filterProvince').addEventListener('change', function() {
        table.column(4).search(this.value).draw();
    });
    document.getElementById('filterSector').addEventListener('change', function() {
        table.column(6).search(this.value).draw();
    });
    document.getElementById('filterYear').addEventListener('change', function() {
        table.column(1).search(this.value).draw();
    });
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterSector').value = '';
    document.getElementById('filterYear').value = '';
    $('#teTable').DataTable().search('').columns().search('').draw();
}

document.getElementById('calcForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('runBtn');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        btn.disabled = true;
    }
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
