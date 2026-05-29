<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_salary_tax_engine.php";

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';
$message = '';
$msg_type = 'success';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate" && !empty($_POST["batch_id"])) {
    try {
        $engine = new TESalaryTaxEngine($pdo);
        $summary = $engine->calculateBatch($_POST["batch_id"]);
        if (empty($summary["errors"])) {
            $message = "Calculation complete! <strong>{$summary['calculated']} records</strong> processed. Total TE = <strong>" . number_format($summary["total_te"], 0) . " LAK</strong>";
        } else {
            $message = "Calculated with " . count($summary["errors"]) . " errors: " . implode("; ", array_slice($summary["errors"], 0, 3));
            $msg_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Engine error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

if (!$batch):
    $all_rows = $pdo->query("SELECT * FROM import_salary_tax_data ORDER BY id DESC")->fetchAll();

    $total_records = count($all_rows);
    $total_taxable = array_sum(array_column($all_rows, 'total_taxable_amount'));
    $total_paid = array_sum(array_column($all_rows, 'tax_amount'));
    $total_te = array_sum(array_column($all_rows, 'te_amount'));

    $batches = $pdo->query("SELECT s.batch_id,
                            MIN(s.tax_year) as min_year,
                            MAX(s.tax_year) as max_year,
                            COUNT(*) as `rows`,
                            COALESCE(SUM(s.total_taxable_amount), 0) as total_taxable,
                            COALESCE(SUM(s.tax_amount), 0) as total_paid,
                            COALESCE(SUM(s.te_amount), 0) as total_te,
                            MAX(s.calculated_at) as last_calc
                            FROM import_salary_tax_data s
                            GROUP BY s.batch_id
                            ORDER BY MAX(s.id) DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-calculator me-2 text-primary"></i> Salary Tax TE Calculation</h2>
        <p class="text-muted">Calculate and review Tax Expenditure for Salary Tax batches.</p>
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
            <div class="fs-5 fw-bold"><?= number_format($total_taxable) ?></div>
            <div class="small opacity-75">Total Taxable Amount</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">Total Tax Paid</div>
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
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> Salary Tax Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Salary Tax Data Found</h5>
            <p>Import data from the <a href="import_salary.php">Data Requirement &gt; Salary Tax</a> page first.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch</th><th>Years</th><th>Records</th><th class="text-end">Taxable Amount</th><th class="text-end">Tax Paid</th><th class="text-end">TE Total</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["batch_id"]) ?></small></td>
                    <td><?= $b["min_year"] == $b["max_year"] ? $b["min_year"] : $b["min_year"] . "-" . $b["max_year"] ?></td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format($b["rows"]) ?></span></td>
                    <td class="text-end fw-bold text-info"><?= number_format($b["total_taxable"]) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($b["total_paid"]) ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format($b["total_te"]) ?></td>
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
else:
    $stmt = $pdo->prepare("SELECT * FROM import_salary_tax_data WHERE batch_id = ? ORDER BY id ASC");
    $stmt->execute([$batch]);
    $rows = $stmt->fetchAll();

    $total_records = count($rows);
    $total_taxable = array_sum(array_column($rows, 'total_taxable_amount'));
    $total_paid = array_sum(array_column($rows, 'tax_amount'));
    $total_te = array_sum(array_column($rows, 'te_amount'));

    $years = $pdo->prepare("SELECT DISTINCT tax_year FROM import_salary_tax_data WHERE batch_id = ? AND tax_year IS NOT NULL ORDER BY tax_year DESC");
    $years->execute([$batch]);
    $year_list = $years->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="te_salary_tax.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Salary Tax TE: Batch Results</h2>
        <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> — <strong><?= $total_records ?></strong> records</p>
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
            <div class="fs-5 fw-bold"><?= number_format($total_taxable) ?></div>
            <div class="small opacity-75">Total Taxable</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">Tax Paid</div>
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
                <label class="form-label small fw-bold text-muted">Search TIN / Period</label>
                <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
            </div>
            <div class="col-md-2">
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
                    <th>TIN</th>
                    <th>Year</th>
                    <th>Period</th>
                    <th>Filing Type</th>
                    <th class="text-end">Taxable Amount</th>
                    <th class="text-end">Exempt Amount</th>
                    <th class="text-end">Tax Paid</th>
                    <th class="text-end table-info">Benchmark Tax</th>
                    <th class="text-end table-danger">TE Amount</th>
                    <th>Provision</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                <?php $calced = !empty($r["calculated_at"]); ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></td>
                    <td><?= $r["tax_year"] ?></td>
                    <td><?= htmlspecialchars($r["filing_period"] ?? '') ?></td>
                    <td><?= htmlspecialchars($r["filing_type"] ?? '') ?></td>
                    <td class="text-end"><?= number_format((float)($r["total_taxable_amount"] ?? 0), 0) ?></td>
                    <td class="text-end"><?= number_format((float)($r["tax_exempt_amount"] ?? 0), 0) ?></td>
                    <td class="text-end fw-bold"><?= number_format((float)($r["tax_amount"] ?? 0), 0) ?></td>
                    <td class="text-end fw-bold text-info"><?= $calced ? number_format((float)($r["benchmark_tax"] ?? 0), 0) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end fw-bold text-danger"><?= $calced ? number_format((float)($r["te_amount"] ?? 0), 0) : '<span class="text-muted">—</span>' ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r["provision_number"] ?: '-') ?></span></td>
                    <td>
                        <?php if ($calced): ?>
                        <span class="badge bg-success">Calculated</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="12" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-warning fw-bold">
                    <td colspan="5" class="text-end">Total Tax Expenditure (LAK):</td>
                    <td class="text-end text-info"><?= number_format($total_taxable, 0) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($rows, 'tax_exempt_amount')), 0) ?></td>
                    <td class="text-end text-success"><?= number_format($total_paid, 0) ?></td>
                    <td class="text-end text-info"><?= number_format(array_sum(array_column($rows, 'benchmark_tax')), 0) ?></td>
                    <td class="text-end text-danger"><?= number_format($total_te, 0) ?></td>
                    <td></td>
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
        table.column(2).search(this.value).draw();
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
