<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/te_asycuda_engine.php";

$pdo = getDbConnection();
$engine = new TEAsycudaEngine($pdo);
$batch_id = $_GET["batch"] ?? $_POST["batch_id"] ?? "";
$records = [];
$summary = null;
$message = "";
$message_type = "info";
$is_calculated = false;

// Handle Calculation Request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate" && !empty($batch_id)) {
    try {
        $result = $engine->calculateBatch($batch_id);
        $message = "Calculation complete! <strong>{$result['calculated']} records</strong> processed for Customs Duty. TE total: " . number_format($result['total_customs'], 2) . " LAK.";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Calculation error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Fetch available batches
$batches = [];
try {
    $batches = $pdo->query("SELECT import_batch_id as batch_id, MIN(doc_date) as start_date, MAX(doc_date) as end_date, COUNT(*) as row_count 
                           FROM asycuda_imports 
                           GROUP BY import_batch_id 
                           ORDER BY import_batch_id DESC LIMIT 20")->fetchAll();
} catch (Exception $e) {
    $message = "Error fetching batches: " . $e->getMessage();
    $message_type = "danger";
}

// Check status and fetch records if batch selected
if ($batch_id) {
    $is_calculated = $engine->isBatchCalculated($batch_id);
    try {
        $stmt = $pdo->prepare("SELECT i.*, te.customs_te, te.excise_te, te.vat_te, te.total_te 
                               FROM asycuda_imports i 
                               LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                               WHERE i.import_batch_id = ? ORDER BY i.doc_date ASC");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll();

        if (!empty($records)) {
            $total_invoice = 0;
            $total_bm = 0;
            $total_paid = 0;
            $total_te = 0;
            foreach ($records as $r) {
                $total_invoice += $r['invoice_amount_lak'];
                $total_bm += $r['exemp_customs'];
                $total_paid += $r['paid_customs'];
                $total_te += ($r['customs_te'] ?? 0);
            }
            $summary = [
                'total_invoice' => $total_invoice,
                'total_bm' => $total_bm,
                'total_paid' => $total_paid,
                'total_te' => $total_te,
                'count' => count($records)
            ];
        } else {
            $message = "No records found for batch: " . htmlspecialchars($batch_id);
            $message_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Error fetching data: " . $e->getMessage();
        $message_type = "danger";
    }
}
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-gavel me-2 text-primary"></i> Customs Duty TE (ASYCUDA)</h2>
        <p class="text-muted">Calculate and analyze Tax Expenditure for Customs Duty from ASYCUDA imports.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="import_asycuda.php" class="btn btn-outline-primary"><i class="fas fa-file-import me-1"></i> Import New Data</a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show shadow-sm">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Batch Selection & Control -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 small text-uppercase fw-bold text-muted"><i class="fas fa-list me-2"></i> Batch Selection</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="te_asycuda_customs.php">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($batch_id) ?>">
                    <input type="hidden" name="action" value="calculate">
                    <div class="mb-3">
                        <select name="batch_select" class="form-select form-select-lg border-0 bg-light" onchange="location.href='te_asycuda_customs.php?batch=' + this.value">
                            <option value="">-- Choose Import Batch --</option>
                            <?php foreach ($batches as $b): ?>
                            <option value="<?= htmlspecialchars($b['batch_id'] ?? '') ?>" <?= $batch_id == ($b['batch_id'] ?? '') ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['batch_id'] ?? 'Unknown') ?> (<?= $b['row_count'] ?> items)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($batch_id): ?>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3 shadow border-0" <?= $is_calculated ? 'disabled' : '' ?>>
                        <i class="fas fa-sync-alt me-2 <?= $is_calculated ? '' : 'fa-spin' ?>"></i> 
                        <?= $is_calculated ? 'Calculation Complete' : 'Run TE Calculation' ?>
                    </button>
                    <?php endif; ?>
                </form>

                <?php if ($batch_id): ?>
                <hr>
                <div class="p-3 bg-light rounded border <?= $is_calculated ? 'border-success' : 'border-primary' ?> border-opacity-25 shadow-sm">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Current Batch:</span>
                        <span class="fw-bold small"><?= htmlspecialchars($batch_id) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Status:</span>
                        <span class="badge <?= $is_calculated ? 'bg-success' : 'bg-warning text-dark' ?> px-3">
                            <i class="fas <?= $is_calculated ? 'fa-check-circle' : 'fa-clock' ?> me-1"></i>
                            <?= $is_calculated ? 'Calculated & Synced' : 'Awaiting Calculation' ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="col-md-8">
        <?php if ($batch_id && $summary): ?>
        <div class="row g-3 mb-4 text-center">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #0d6efd !important;">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-1">Records</div>
                        <div class="h4 mb-0 fw-bold text-dark"><?= number_format($summary['count'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #0d6efd !important;">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-1">Invoice (LAK)</div>
                        <div class="h4 mb-0 fw-bold text-dark"><?= number_format($summary['total_invoice'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-1">Paid Customs</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($summary['total_paid'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white" style="border-radius: 12px;">
                    <div class="card-body">
                        <div class="text-white-50 small text-uppercase mb-1">Customs TE</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($summary['total_te'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_calculated && !empty($records)): ?>
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2 text-primary"></i> Calculation Result: <?= htmlspecialchars($batch_id) ?></h5>
                <a href="asycuda_customs.php?batch=<?= urlencode($batch_id) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye me-1"></i> View / Edit Records</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4">No.</th>
                                <th>Doc Date</th>
                                <th>Importer TIN</th>
                                <th>Importer Name</th>
                                <th>HS Code</th>
                                <th class="text-end">Invoice Amount</th>
                                <th class="text-end">BM Customs</th>
                                <th class="text-end">Paid Customs</th>
                                <th class="text-end table-primary border-start">Customs TE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($records as $r): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($r['doc_date'] ?? '-') ?></td>
                                <td class="font-monospace text-muted small"><?= htmlspecialchars($r['tin'] ?? '-') ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($r['importer_name'] ?? '-') ?></td>
                                <td class="font-monospace"><?= htmlspecialchars($r['hs_code'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format($r['invoice_amount_lak'], 2) ?></td>
                                <td class="text-end text-primary fw-bold"><?= number_format($r['exemp_customs'], 2) ?></td>
                                <td class="text-end text-success fw-bold"><?= number_format($r['paid_customs'], 2) ?></td>
                                <td class="text-end table-primary border-start fw-bold text-dark"><?= number_format($r['customs_te'] ?? 0, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm h-100 d-flex align-items-center justify-content-center py-5 text-muted" style="border-radius: 12px; background: #f8f9fa;">
            <div class="text-center">
                <i class="fas fa-calculator fa-4x mb-3 text-primary opacity-25"></i>
                <h5 class="text-dark">TE Calculation Pending</h5>
                <p>Select a batch and click <strong>"Run TE Calculation"</strong> to view results<br>and update the consolidated reports.</p>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="card border-0 shadow-sm h-100 d-flex align-items-center justify-content-center py-5 text-muted" style="border-radius: 12px;">
            <i class="fas fa-chart-line fa-4x mb-3 opacity-10"></i>
            <p>Please select a batch from the list to view or perform TE calculations.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4 p-3 bg-white shadow-sm rounded text-muted small border-start border-primary border-5">
    <i class="fas fa-info-circle me-2 text-primary"></i> <strong>Note on TE Calculation:</strong> For Customs Duty, Tax Expenditure = max(0, Benchmark Customs &minus; Paid Customs). The engine computes and persists these values for reporting. Data originates from ASYCUDA imports.
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
