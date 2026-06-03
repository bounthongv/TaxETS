<?php
if (!isset($asycudaTeConfig) || !is_array($asycudaTeConfig)) {
    throw new RuntimeException("ASYCUDA TE page configuration is missing.");
}

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_asycuda_engine.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists("asycudaFormatMoney")) {
    function asycudaFormatMoney($value): string {
        return number_format((float)($value ?? 0), 0);
    }
}

if (!function_exists("asycudaMonthDisplay")) {
    function asycudaMonthDisplay(?string $date): string {
        if (empty($date) || $date === "0000-00-00") {
            return "-";
        }
        $time = strtotime($date);
        return $time ? date("Y-m", $time) : "-";
    }
}

$pdo = getDbConnection();
$engine = new TEAsycudaEngine($pdo);
$page = basename($_SERVER["SCRIPT_NAME"]);
$batch = $_GET["batch"] ?? $_POST["batch_id"] ?? "";
$message = "";
$msg_type = "success";
$is_admin = ($_SESSION["user_email"] ?? "") === "admin@example.com";

$paidColumn = $asycudaTeConfig["paid_column"];
$expertColumn = $asycudaTeConfig["expert_column"];
$teColumn = $asycudaTeConfig["te_column"];
$benchmarkColumn = $asycudaTeConfig["benchmark_column"] ?? null;
$calcTotalKey = $asycudaTeConfig["calc_total_key"];
$theme = $asycudaTeConfig["theme"] ?? "primary";
$tableClass = $asycudaTeConfig["table_class"] ?? "table-primary";
$teTextClass = $asycudaTeConfig["te_text_class"] ?? "text-danger";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "calculate" && !empty($_POST["batch_id"])) {
    try {
        $batch = $_POST["batch_id"];
        $result = $engine->calculateBatch($batch);
        $message = "Calculation complete! <strong>" . number_format((int)($result["calculated"] ?? 0)) . " records</strong> processed. Total TE = <strong>" . asycudaFormatMoney($result[$calcTotalKey] ?? 0) . " LAK</strong>";
    } catch (Exception $e) {
        $message = "Calculation error: " . htmlspecialchars($e->getMessage());
        $msg_type = "danger";
    }
}

if (!$batch) {
    $totals = [
        "row_count" => 0,
        "total_invoice" => 0,
        "total_paid" => 0,
        "total_te" => 0,
        "total_expert_te" => 0,
    ];
    $batches = [];

    try {
        $totals = $pdo->query("
            SELECT COUNT(*) AS row_count,
                   COALESCE(SUM(i.invoice_amount_lak), 0) AS total_invoice,
                   COALESCE(SUM(i.`{$paidColumn}`), 0) AS total_paid,
                   COALESCE(SUM(te.`{$teColumn}`), 0) AS total_te,
                   COALESCE(SUM(i.`{$expertColumn}`), 0) AS total_expert_te
            FROM asycuda_imports i
            LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id
        ")->fetch() ?: $totals;

        $batches = $pdo->query("
            SELECT i.import_batch_id AS batch_id,
                   COUNT(*) AS row_count,
                   COALESCE(SUM(i.invoice_amount_lak), 0) AS total_invoice,
                   COALESCE(SUM(i.`{$paidColumn}`), 0) AS total_paid,
                   COALESCE(SUM(te.`{$teColumn}`), 0) AS total_te,
                   COALESCE(SUM(i.`{$expertColumn}`), 0) AS total_expert_te,
                   MAX(i.id) AS last_id
            FROM asycuda_imports i
            LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id
            GROUP BY i.import_batch_id
            ORDER BY last_id DESC
        ")->fetchAll();
    } catch (Exception $e) {
        $message = "Error fetching ASYCUDA batches: " . htmlspecialchars($e->getMessage());
        $msg_type = "danger";
    }

    require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="<?= htmlspecialchars($asycudaTeConfig["icon"]) ?> me-2 text-<?= htmlspecialchars($theme) ?>"></i> <?= htmlspecialchars($asycudaTeConfig["title"]) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($asycudaTeConfig["description"]) ?></p>
    </div>
    <div class="col-md-4 text-end">
        <a href="import_asycuda.php" class="btn btn-outline-primary"><i class="fas fa-file-import me-1"></i> Import New Data</a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row mb-3 g-2">
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format((int)($totals["row_count"] ?? 0)) ?></div>
            <div class="small opacity-75">Total Records</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($totals["total_invoice"] ?? 0) ?></div>
            <div class="small opacity-75">Total Invoice</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($totals["total_paid"] ?? 0) ?></div>
            <div class="small opacity-75"><?= htmlspecialchars($asycudaTeConfig["paid_label"]) ?></div>
        </div>
    </div>
    <div class="<?= $is_admin ? "col-md-2" : "col-md-5" ?> col-6">
        <div class="card border-0 shadow-sm bg-danger text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($totals["total_te"] ?? 0) ?></div>
            <div class="small opacity-75">Total TE</div>
        </div>
    </div>
    <?php if ($is_admin): ?>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($totals["total_expert_te"] ?? 0) ?></div>
            <div class="small opacity-75">Expert TE</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> ASYCUDA Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No ASYCUDA Data Found</h5>
            <p>Import data from the <a href="import_asycuda.php">Data from ASYCUDA</a> page first.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Batch</th>
                    <th>Records</th>
                    <th class="text-end">Invoice</th>
                    <th class="text-end"><?= htmlspecialchars($asycudaTeConfig["paid_label"]) ?></th>
                    <th class="text-end text-danger">TE</th>
                    <?php if ($is_admin): ?><th class="text-end text-warning">Expert TE</th><?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["batch_id"]) ?></small></td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format((int)$b["row_count"]) ?></span></td>
                    <td class="text-end fw-bold text-info"><?= asycudaFormatMoney($b["total_invoice"]) ?></td>
                    <td class="text-end fw-bold text-success"><?= asycudaFormatMoney($b["total_paid"]) ?></td>
                    <td class="text-end fw-bold text-danger"><?= asycudaFormatMoney($b["total_te"]) ?></td>
                    <?php if ($is_admin): ?>
                    <td class="text-end fw-bold text-warning"><?= asycudaFormatMoney($b["total_expert_te"]) ?></td>
                    <?php endif; ?>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="calculate">
                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($b["batch_id"]) ?>">
                            <button class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></button>
                        </form>
                        <a href="<?= htmlspecialchars($page) ?>?batch=<?= urlencode($b["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php
} else {
    $rows = [];
    $is_calculated = false;

    try {
        $is_calculated = $engine->isBatchCalculated($batch);
        $stmt = $pdo->prepare("
            SELECT i.*, te.customs_te, te.excise_te, te.vat_te, te.total_te
            FROM asycuda_imports i
            LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id
            WHERE i.import_batch_id = ?
            ORDER BY i.doc_date ASC, i.id ASC
        ");
        $stmt->execute([$batch]);
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = "Error fetching batch data: " . htmlspecialchars($e->getMessage());
        $msg_type = "danger";
    }

    $total_records = count($rows);
    $total_invoice = array_sum(array_map(fn($r) => (float)($r["invoice_amount_lak"] ?? 0), $rows));
    $total_paid = array_sum(array_map(fn($r) => (float)($r[$paidColumn] ?? 0), $rows));
    $total_te = array_sum(array_map(fn($r) => (float)($r[$teColumn] ?? 0), $rows));
    $total_expert_te = array_sum(array_map(fn($r) => (float)($r[$expertColumn] ?? 0), $rows));
    $total_benchmark = $benchmarkColumn ? array_sum(array_map(fn($r) => (float)($r[$benchmarkColumn] ?? 0), $rows)) : 0;
    $hs_codes = array_values(array_unique(array_filter(array_map(fn($r) => $r["hs_code"] ?? "", $rows))));
    sort($hs_codes);
    $months = array_values(array_unique(array_filter(array_map(fn($r) => asycudaMonthDisplay($r["doc_date"] ?? null), $rows), fn($v) => $v !== "-")));
    rsort($months);

    require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="<?= htmlspecialchars($page) ?>" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> <?= htmlspecialchars($asycudaTeConfig["detail_title"]) ?></h2>
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
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($total_invoice) ?></div>
            <div class="small opacity-75">Invoice</div>
        </div>
    </div>
    <?php if ($benchmarkColumn): ?>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-secondary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($total_benchmark) ?></div>
            <div class="small opacity-75"><?= htmlspecialchars($asycudaTeConfig["benchmark_label"]) ?></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($total_paid) ?></div>
            <div class="small opacity-75"><?= htmlspecialchars($asycudaTeConfig["paid_label"]) ?></div>
        </div>
    </div>
    <div class="<?= $is_admin ? "col-md-2" : "col-md-4" ?> col-6">
        <div class="card border-0 shadow-sm bg-danger text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($total_te) ?></div>
            <div class="small opacity-75">TE</div>
        </div>
    </div>
    <?php if ($is_admin): ?>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm bg-warning text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= asycudaFormatMoney($total_expert_te) ?></div>
            <div class="small opacity-75">Expert TE</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Search TIN / Importer</label>
                <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">HS Code</label>
                <select id="filterHs" class="form-select">
                    <option value="">All HS Codes</option>
                    <?php foreach ($hs_codes as $hs): ?>
                    <option value="<?= htmlspecialchars($hs) ?>"><?= htmlspecialchars($hs) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Doc Month</label>
                <select id="filterMonth" class="form-select">
                    <option value="">All Months</option>
                    <?php foreach ($months as $month): ?>
                    <option value="<?= htmlspecialchars($month) ?>"><?= htmlspecialchars($month) ?></option>
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
                    <th>Doc Month</th>
                    <th>TIN</th>
                    <th>Importer</th>
                    <th>HS Code</th>
                    <th class="text-end">Invoice</th>
                    <?php if ($benchmarkColumn): ?><th class="text-end <?= htmlspecialchars($tableClass) ?>"><?= htmlspecialchars($asycudaTeConfig["benchmark_label"]) ?></th><?php endif; ?>
                    <th class="text-end"><?= htmlspecialchars($asycudaTeConfig["paid_label"]) ?></th>
                    <th class="text-end <?= htmlspecialchars($tableClass) ?>"><?= htmlspecialchars($asycudaTeConfig["te_label"]) ?></th>
                    <?php if ($is_admin): ?>
                    <th class="text-end table-warning">Expert TE</th>
                    <th class="text-center" style="width:40px">Δ</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r):
                    $system_te = (float)($r[$teColumn] ?? 0);
                    $expert_te = (float)($r[$expertColumn] ?? 0);
                    $diff = $system_te - $expert_te;
                    $has_diff = $is_admin && $expert_te > 0 && abs($diff) > 0.01;
                ?>
                <tr class="<?= $has_diff ? "table-warning" : "" ?>">
                    <td><?= $i + 1 ?></td>
                    <td><?= asycudaMonthDisplay($r["doc_date"] ?? null) ?></td>
                    <td class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"] ?? "-") ?></td>
                    <td><?= htmlspecialchars($r["importer_name"] ?? "-") ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($r["hs_code"] ?? "-") ?></td>
                    <td class="text-end"><?= asycudaFormatMoney($r["invoice_amount_lak"] ?? 0) ?></td>
                    <?php if ($benchmarkColumn): ?><td class="text-end fw-bold"><?= asycudaFormatMoney($r[$benchmarkColumn] ?? 0) ?></td><?php endif; ?>
                    <td class="text-end"><?= asycudaFormatMoney($r[$paidColumn] ?? 0) ?></td>
                    <td class="text-end fw-bold <?= htmlspecialchars($teTextClass) ?>"><?= asycudaFormatMoney($system_te) ?></td>
                    <?php if ($is_admin): ?>
                    <td class="text-end fw-bold text-warning"><?= asycudaFormatMoney($expert_te) ?></td>
                    <td class="text-center">
                        <?php if ($expert_te > 0): ?>
                            <?php if (abs($diff) > 0.01): ?>
                                <span class="badge bg-<?= abs($diff) > 1000000 ? "danger" : "warning" ?> text-dark" title="System TE - Expert TE"><?= $diff > 0 ? "+" : "" ?><?= asycudaFormatMoney($diff) ?></span>
                            <?php else: ?>
                                <span class="text-success" title="Matches Expert TE">OK</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="<?= ($benchmarkColumn ? 9 : 8) + ($is_admin ? 2 : 0) ?>" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-warning fw-bold">
                    <td colspan="5" class="text-end">Total Tax Expenditure (LAK):</td>
                    <td class="text-end text-info"><?= asycudaFormatMoney($total_invoice) ?></td>
                    <?php if ($benchmarkColumn): ?><td class="text-end text-secondary"><?= asycudaFormatMoney($total_benchmark) ?></td><?php endif; ?>
                    <td class="text-end text-success"><?= asycudaFormatMoney($total_paid) ?></td>
                    <td class="text-end text-danger"><?= asycudaFormatMoney($total_te) ?></td>
                    <?php if ($is_admin): ?>
                    <td class="text-end text-warning"><?= asycudaFormatMoney($total_expert_te) ?></td>
                    <td class="text-center">
                        <?php $total_diff = $total_te - $total_expert_te; ?>
                        <?php if (abs($total_diff) > 0.01): ?>
                            <span class="badge bg-<?= abs($total_diff) > 1000000 ? "danger" : "warning" ?> text-dark"><?= $total_diff > 0 ? "+" : "" ?><?= asycudaFormatMoney($total_diff) ?></span>
                        <?php else: ?>
                            <span class="text-success">OK</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
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
    document.getElementById('filterHs').addEventListener('change', function() {
        table.column(4).search(this.value).draw();
    });
    document.getElementById('filterMonth').addEventListener('change', function() {
        table.column(1).search(this.value).draw();
    });
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterHs').value = '';
    document.getElementById('filterMonth').value = '';
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
}

require_once __DIR__ . "/../includes/footer.php";
