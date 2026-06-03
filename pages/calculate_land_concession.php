<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_land_concession_engine.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();
$engine = new TELandConcessionEngine($pdo);
$batch = $_GET["batch"] ?? "";
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "calculate" && !empty($_POST["batch_id"])) {
    try {
        $batch = $_POST["batch_id"];
        $result = $engine->calculateBatch($batch);
        if (empty($result["errors"])) {
            $message = "Calculation complete! <strong>" . number_format((int)$result["calculated"]) . " records</strong> processed. Total TE = <strong>" . number_format((float)$result["total_te"], 2) . " USD</strong>";
        } else {
            $message = "Calculated with " . count($result["errors"]) . " errors: " . htmlspecialchars(implode("; ", array_slice($result["errors"], 0, 3)));
            $msg_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Error: " . htmlspecialchars($e->getMessage());
        $msg_type = "danger";
    }
}

if (!$batch):
    $allRows = $pdo->query("SELECT * FROM repo_land_concession_data ORDER BY id DESC")->fetchAll();
    $total_records = count($allRows);
    $total_area = array_sum(array_map(fn($r) => (float)($r["concession_area_ha"] ?? 0), $allRows));
    $total_paid = array_sum(array_map(fn($r) => (float)($r["concession_fee_paid_usd"] ?? 0), $allRows));
    $total_benchmark = array_sum(array_map(fn($r) => (float)($r["benchmark_value_usd"] ?? 0), $allRows));
    $total_te = array_sum(array_map(fn($r) => (float)($r["non_tax_te_usd"] ?? 0), $allRows));

    $batches = $pdo->query("
        SELECT import_batch_id,
               COUNT(*) AS row_count,
               COALESCE(SUM(concession_area_ha), 0) AS total_area_ha,
               COALESCE(SUM(concession_fee_paid_usd), 0) AS total_paid_usd,
               COALESCE(SUM(benchmark_value_usd), 0) AS total_benchmark_usd,
               COALESCE(SUM(non_tax_te_usd), 0) AS total_te_usd,
               MIN(tax_year) AS min_year,
               MAX(tax_year) AS max_year,
               MAX(id) AS last_id
        FROM repo_land_concession_data
        GROUP BY import_batch_id
        ORDER BY last_id DESC
    ")->fetchAll();

    require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-calculator me-2 text-warning"></i> Non-Tax: Land Concession TE</h2>
        <p class="text-muted">Calculate non-tax expenditure from land concession fee reductions.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="import_land_concession.php" class="btn btn-outline-primary"><i class="fas fa-file-import me-1"></i> Import New Data</a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row mb-3 g-2">
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Total Records</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_area, 2) ?></div>
            <div class="small opacity-75">Total Area (ha)</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid, 2) ?></div>
            <div class="small opacity-75">Paid Fee</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-secondary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_benchmark, 2) ?></div>
            <div class="small opacity-75">Benchmark Value</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-danger text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_te, 2) ?></div>
            <div class="small opacity-75">Total TE</div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> Land Concession Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Land Concession Data Found</h5>
            <p>Import data from the <a href="import_land_concession.php">Data Requirement &gt; Non-Tax: Land concession</a> page first.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Batch</th>
                    <th>Years</th>
                    <th>Records</th>
                    <th class="text-end">Area (ha)</th>
                    <th class="text-end">Paid Fee</th>
                    <th class="text-end">Benchmark Value</th>
                    <th class="text-end text-danger">TE</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["import_batch_id"]) ?></small></td>
                    <td><?= htmlspecialchars($b["min_year"]) ?><?= $b["min_year"] !== $b["max_year"] ? " - " . htmlspecialchars($b["max_year"]) : "" ?></td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format((int)$b["row_count"]) ?></span></td>
                    <td class="text-end fw-bold text-info"><?= number_format((float)$b["total_area_ha"], 2) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format((float)$b["total_paid_usd"], 2) ?></td>
                    <td class="text-end fw-bold text-secondary"><?= number_format((float)$b["total_benchmark_usd"], 2) ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format((float)$b["total_te_usd"], 2) ?></td>
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
else:
    $stmt = $pdo->prepare("SELECT * FROM repo_land_concession_data WHERE import_batch_id = ? ORDER BY id ASC");
    $stmt->execute([$batch]);
    $rows = $stmt->fetchAll();
    $summary = $engine->getBatchSummary($batch);

    $total_records = (int)($summary["total"] ?? count($rows));
    $total_area = (float)($summary["total_area_ha"] ?? 0);
    $total_paid = (float)($summary["total_paid_usd"] ?? 0);
    $total_benchmark = (float)($summary["total_benchmark_usd"] ?? 0);
    $total_te = (float)($summary["total_te_usd"] ?? 0);
    $years = array_values(array_unique(array_filter(array_map(fn($r) => (string)($r["tax_year"] ?? ""), $rows))));
    rsort($years);
    $provinces = array_values(array_unique(array_filter(array_map(fn($r) => $r["province"] ?? "", $rows))));
    sort($provinces);

    require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="calculate_land_concession.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Non-Tax: Land Concession TE</h2>
        <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> - <strong><?= number_format($total_records) ?></strong> records</p>
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
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Records</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_area, 2) ?></div>
            <div class="small opacity-75">Area (ha)</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid, 2) ?></div>
            <div class="small opacity-75">Paid Fee</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-secondary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_benchmark, 2) ?></div>
            <div class="small opacity-75">Benchmark Value</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-danger text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_te, 2) ?></div>
            <div class="small opacity-75">TE</div>
        </div>
    </div>
</div>

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Search TIN / Company</label>
                <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Province</label>
                <select id="filterProvince" class="form-select">
                    <option value="">All Provinces</option>
                    <?php foreach ($provinces as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Tax Year</label>
                <select id="filterYear" class="form-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                    <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
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
                    <th>Company</th>
                    <th>Year</th>
                    <th>Province</th>
                    <th class="text-end">Area (ha)</th>
                    <th class="text-end">BM Rate</th>
                    <th class="text-end">Paid Fee</th>
                    <th class="text-end table-info">BM Value</th>
                    <th class="text-end table-danger">TE</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"] ?? "") ?></td>
                    <td><?= htmlspecialchars($r["company_name"] ?? "") ?></td>
                    <td><?= htmlspecialchars($r["tax_year"] ?? "") ?></td>
                    <td><?= htmlspecialchars($r["province"] ?? "") ?></td>
                    <td class="text-end"><?= number_format((float)($r["concession_area_ha"] ?? 0), 4) ?></td>
                    <td class="text-end"><?= number_format((float)($r["benchmark_rate_usd"] ?? 0), 4) ?></td>
                    <td class="text-end"><?= number_format((float)($r["concession_fee_paid_usd"] ?? 0), 4) ?></td>
                    <td class="text-end fw-bold text-info"><?= number_format((float)($r["benchmark_value_usd"] ?? 0), 4) ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format((float)($r["non_tax_te_usd"] ?? 0), 4) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-warning fw-bold">
                    <td colspan="5" class="text-end">Total Tax Expenditure (USD):</td>
                    <td class="text-end text-info"><?= number_format($total_area, 4) ?></td>
                    <td></td>
                    <td class="text-end text-success"><?= number_format($total_paid, 4) ?></td>
                    <td class="text-end text-info"><?= number_format($total_benchmark, 4) ?></td>
                    <td class="text-end text-danger"><?= number_format($total_te, 4) ?></td>
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
    document.getElementById('filterYear').addEventListener('change', function() {
        table.column(3).search(this.value).draw();
    });
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterProvince').value = '';
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
