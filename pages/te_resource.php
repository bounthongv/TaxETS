<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_resource_engine.php";

$pdo = getDbConnection();
$engine = new TEResourceEngine($pdo);

$current_batch = $_GET['batch'] ?? null;
$message = "";
$msg_type = "success";

// --- Handle Clear Calculation Results ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clear_results'])) {
    $bid = $_POST['batch_id'];
    $pdo->prepare("UPDATE import_resource_data SET benchmark_rate = 0, benchmark_fee = 0, te_amount = 0, calculated_at = NULL WHERE batch_id = ?")->execute([$bid]);
    $message = "Calculation results for batch <strong>$bid</strong> have been cleared. Imported data remains.";
}

// --- Handle Calculation ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['calculate_batch'])) {
    $bid = $_POST['batch_id'];
    $res = $engine->calculateBatch($bid);
    if (empty($res['errors'])) {
        $message = "Calculation complete! <strong>{$res['calculated']}</strong> records processed. Total TE: <strong>" . number_format($res['total_te'], 2) . "</strong>";
    } else {
        $message = "Calculated with " . count($res['errors']) . " errors: " . htmlspecialchars(implode("; ", array_slice($res['errors'], 0, 3)));
        $msg_type = "warning";
    }
    $current_batch = $bid;
}

// Fetch recent batches for the sidebar
$recent = $pdo->query("SELECT batch_id, tax_year, COUNT(*) as `rows`, SUM(te_amount) as total_te, MAX(import_date) as latest, MAX(calculated_at) as last_calc FROM import_resource_data GROUP BY batch_id, tax_year ORDER BY latest DESC LIMIT 10")->fetchAll();

// Fetch records for the selected batch
$records = [];
if ($current_batch) {
    $stmt = $pdo->prepare("SELECT * FROM import_resource_data WHERE batch_id = ? ORDER BY id ASC");
    $stmt->execute([$current_batch]);
    $records = $stmt->fetchAll();
}

$summary = $current_batch ? $engine->getBatchSummary($current_batch) : null;
$is_calculated = false;
if (!empty($records)) {
    foreach ($records as $r) {
        if ($r['calculated_at'] !== null) {
            $is_calculated = true;
            break;
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3 align-items-center">
    <div class="col-md-8">
        <h2><i class="fas fa-calculator me-2 text-warning"></i> Non-Tax: Resource Fee TE</h2>
        <p class="text-muted">Estimate revenue loss from natural resource fee concessions and incentives.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-bold small text-uppercase text-muted">Recent Batches</div>
      <div class="card-body p-2">
        <?php if (empty($recent)): ?>
            <div class="text-center py-4 text-muted small">No batches found</div>
        <?php else: ?>
            <?php foreach ($recent as $r): ?>
            <div class="mb-2 p-2 rounded <?= $current_batch == $r['batch_id'] ? 'bg-light border border-warning' : 'border' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="?batch=<?= urlencode($r['batch_id']) ?>" class="small fw-bold text-decoration-none text-dark"><?= substr($r['batch_id'], -14) ?> (<?= $r['tax_year'] ?>)</a>
                    <?php if ($r['last_calc']): ?>
                        <span class="badge bg-success rounded-pill" style="font-size: 0.6rem;">Calculated</span>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-pill" style="font-size: 0.6rem;">Pending</span>
                    <?php endif; ?>
                </div>
                <div class="text-muted mt-1" style="font-size: 0.7rem;">
                    <?= $r['rows'] ?> records • TE: <?= number_format($r['total_te'], 0) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <?php if ($current_batch): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-1 fw-bold">Batch: <?= htmlspecialchars($current_batch) ?></h5>
                        <p class="text-muted mb-0 small"><?= $summary['total'] ?> records found in this batch.</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($current_batch) ?>">
                            <button type="submit" name="calculate_batch" class="btn <?= $is_calculated ? 'btn-outline-warning' : 'btn-warning' ?> shadow-sm text-dark fw-bold">
                                <i class="fas <?= $is_calculated ? 'fa-sync-alt' : 'fa-play' ?> me-2"></i> 
                                <?= $is_calculated ? 'Re-calculate' : 'Calculate TE' ?>
                            </button>
                        </form>
                        <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Clear calculation results for this batch? Imported data will be kept.')">
                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($current_batch) ?>">
                            <button type="submit" name="clear_results" class="btn btn-outline-danger shadow-sm" title="Clear Results">
                                <i class="fas fa-eraser"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($is_calculated): ?>
                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border-start border-4 border-success">
                                <div class="small text-muted text-uppercase fw-bold">Total TE Amount</div>
                                <div class="h4 mb-0 fw-bold"><?= number_format($summary['total_te'], 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border-start border-4 border-info">
                                <div class="small text-muted text-uppercase fw-bold">Total Collected</div>
                                <div class="h4 mb-0 fw-bold"><?= number_format($summary['total_collected'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-dark small">
                            <tr>
                                <th>TIN</th>
                                <th>Resource Type</th>
                                <th class="text-end">Actual Rate</th>
                                <th class="text-end">BM Rate</th>
                                <th class="text-end">Actual Collected</th>
                                <th class="text-end text-warning">Benchmark Fee</th>
                                <th class="text-end text-info">TE Amount</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $r): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($r['tin']) ?></td>
                                <td><?= htmlspecialchars($r['resource_type']) ?></td>
                                <td class="text-end"><?= number_format($r['actual_rate'], 2) ?>%</td>
                                <td class="text-end"><?= number_format($r['benchmark_rate'], 2) ?>%</td>
                                <td class="text-end"><?= number_format($r['fee_collected'], 2) ?></td>
                                <td class="text-end text-primary fw-bold"><?= number_format($r['benchmark_fee'], 2) ?></td>
                                <td class="text-end text-info fw-bold"><?= number_format($r['te_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-body p-5 text-center">
                <i class="fas fa-folder-open fa-4x text-light mb-4"></i>
                <h4>No Batch Selected</h4>
                <p class="text-muted">Please select a resource fee batch from the sidebar on the left.</p>
                <a href="import_resource.php" class="btn btn-warning mt-3"><i class="fas fa-upload me-2"></i> Go to Import Page</a>
            </div>
        </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
