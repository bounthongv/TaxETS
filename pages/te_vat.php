<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_vat_engine.php";

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';
$message = '';
$msg_type = 'success';

// Handle Calculate action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate" && !empty($_POST["batch_id"])) {
    try {
        $engine = new TEVatEngine($pdo);
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

// --- Batch List Mode ---
if (!$batch):
    $all_rows = $pdo->query("SELECT * FROM import_vat_data ORDER BY id DESC")->fetchAll();

    $total_records = count($all_rows);
    $total_exempt = array_sum(array_column($all_rows, 'sales_exempt'));
    $total_payable = array_sum(array_column($all_rows, 'vat_payable'));
    $total_te = array_sum(array_column($all_rows, 'expert_te'));

    $batches = $pdo->query("SELECT v.batch_id, COUNT(*) as `rows`,
                            COALESCE(SUM(v.sales_exempt), 0) as total_exempt,
                            COALESCE(SUM(v.vat_payable), 0) as total_payable,
                            COALESCE(SUM(v.expert_te), 0) as total_te
                            FROM import_vat_data v
                            GROUP BY v.batch_id
                            ORDER BY MAX(v.id) DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-calculator me-2 text-primary"></i> Domestic VAT TE Calculation</h2>
        <p class="text-muted">Calculate Tax Expenditure for Domestic VAT batches.</p>
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
            <div class="fs-5 fw-bold"><?= number_format($total_exempt) ?></div>
            <div class="small opacity-75">Total Exempt Sales</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_payable) ?></div>
            <div class="small opacity-75">Total VAT Payable</div>
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
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> VAT Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No VAT Data Found</h5>
            <p>Import data from the <a href="import_vat.php">Data Requirement &gt; VAT</a> page first.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch</th><th>Records</th><th class="text-end">Exempt Sales</th><th class="text-end">VAT Payable</th><th class="text-end">TE Total</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["batch_id"]) ?></small></td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format($b["rows"]) ?></span></td>
                    <td class="text-end fw-bold text-info"><?= number_format($b["total_exempt"]) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($b["total_payable"]) ?></td>
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
// --- Batch Detail Mode ---
else:
    $stmt = $pdo->prepare("SELECT * FROM import_vat_data WHERE batch_id = ? ORDER BY id ASC");
    $stmt->execute([$batch]);
    $rows = $stmt->fetchAll();

    $total_records = count($rows);
    $total_exempt = array_sum(array_column($rows, 'sales_exempt'));
    $total_payable = array_sum(array_column($rows, 'vat_payable'));
    $total_te = array_sum(array_column($rows, 'expert_te'));

    // Fetch VAT rates for inline benchmark display
    $vat_rates = [];
    try {
        $vat_rates = $pdo->query("SELECT * FROM bm_vat ORDER BY start_date ASC")->fetchAll();
    } catch (Exception $e) {}

    $getVatRate = function($date) use ($vat_rates) {
        if (!$date || $date == '0000-00-00') return 10.0;
        $ts = strtotime($date);
        foreach ($vat_rates as $r) {
            if ($ts >= strtotime($r["start_date"]) && $ts <= strtotime($r["end_date"])) {
                return (float)$r["rate_percentage"];
            }
        }
        return 10.0;
    };

    $provinces = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces ORDER BY province_name")->fetchAll();

    $periods = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(filing_period, '%Y-%m') as period FROM import_vat_data WHERE batch_id = ? AND filing_period IS NOT NULL AND filing_period != '0000-00-00' ORDER BY period DESC");
    $periods->execute([$batch]);
    $period_list = $periods->fetchAll(PDO::FETCH_COLUMN);

    // Precompute benchmark totals for tfoot
    $total_bm_output = 0;
    foreach ($rows as $r) {
        $calc_date = (!empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00') ? $r["filing_period"] : $r["input_date"];
        $rate = $getVatRate($calc_date);
        $total_sales = (float)($r["sales_standard"] ?? 0) + (float)($r["sales_zero_rate"] ?? 0) + (float)($r["sales_exempt"] ?? 0);
        $total_bm_output += $total_sales * ($rate / 100);
    }

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="te_vat.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Domestic VAT TE: Batch Results</h2>
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
            <div class="fs-5 fw-bold"><?= number_format($total_exempt) ?></div>
            <div class="small opacity-75">Exempt Sales</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_payable) ?></div>
            <div class="small opacity-75">VAT Payable</div>
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
                <label class="form-label small fw-bold text-muted">Period</label>
                <select id="filterPeriod" class="form-select">
                    <option value="">All Periods</option>
                    <?php foreach ($period_list as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
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
                    <th>Period</th>
                    <th>TIN</th>
                    <th>Name</th>
                    <th>Province</th>
                    <th class="text-end">Exempt Sales</th>
                    <th class="text-end table-info">BM Output VAT</th>
                    <th class="text-end">VAT Payable</th>
                    <th class="text-end table-danger">Expert TE</th>
                    <th>Provision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r):
                    $calc_date = (!empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00') ? $r["filing_period"] : $r["input_date"];
                    $rate = $getVatRate($calc_date);
                    $total_sales = (float)($r["sales_standard"] ?? 0) + (float)($r["sales_zero_rate"] ?? 0) + (float)($r["sales_exempt"] ?? 0);
                    $bm_output_vat = $total_sales * ($rate / 100);
                    $period_display = (!empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00') ? date("Y-m", strtotime($r["filing_period"])) : '-';
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $period_display ?></td>
                    <td class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></td>
                    <td><?= htmlspecialchars($r["name"]) ?></td>
                    <td><?= htmlspecialchars($r["province"] ?? '') ?></td>
                    <td class="text-end"><?= number_format((float)($r["sales_exempt"] ?? 0), 0) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format($bm_output_vat, 0) ?></td>
                    <td class="text-end"><?= number_format((float)($r["vat_payable"] ?? 0), 0) ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format((float)($r["expert_te"] ?? 0), 0) ?></td>
                    <td>
                        <?php if (!empty($r["provision_number"])): ?>
                            <span class="badge bg-primary"><?= htmlspecialchars($r["provision_number"]) ?></span>
                        <?php else: ?>
                            <span class="text-muted small">None</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-warning fw-bold">
                    <td colspan="6" class="text-end">Total Tax Expenditure (LAK):</td>
                    <td class="text-end text-info"><?= number_format($total_bm_output, 0) ?></td>
                    <td class="text-end text-success"><?= number_format($total_payable, 0) ?></td>
                    <td class="text-end text-danger"><?= number_format($total_te, 0) ?></td>
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
    document.getElementById('filterPeriod').addEventListener('change', function() {
        table.column(1).search(this.value).draw();
    });
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterPeriod').value = '';
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
