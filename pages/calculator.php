<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_profit_tax_engine.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$results = [];
$selected_batch = $_GET["batch"] ?? "";
$summary = null;

// Run calculation on POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $selected_batch = $_POST["batch_id"];
    try {
        $engine = new TEEngine($pdo);
        $summary = $engine->calculateBatch($selected_batch);
        if (empty($summary["errors"])) {
            $message = "Calculation complete! <strong>{$summary['calculated']} companies</strong> processed. Total TE = <strong>" . number_format($summary["total_te"], 0) . " LAK</strong>";
        } else {
            $message = "Calculated with some errors: " . implode("; ", array_slice($summary["errors"], 0, 3));
            $msg_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Engine error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch results for display
if ($selected_batch) {
    $stmt = $pdo->prepare("SELECT c.*, r.benchmark_rate_applied, r.benchmark_pt, r.pt_te, r.matched_provisions, r.profit_tax_te FROM companies c JOIN te_profit_result r ON r.company_id = c.id WHERE c.import_batch_id = ? ORDER BY c.id");
    $stmt->execute([$selected_batch]);
    $results = $stmt->fetchAll();
}

// Available batches
$batches = $pdo->query("SELECT DISTINCT import_batch_id, tax_year FROM companies ORDER BY tax_year DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-calculator me-2"></i> TE Calculation Engine</h2>
    <p class="text-muted">Select an import batch and run the benchmark identification and TE classification engine.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
  <?= $message ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Run Calculation -->
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-success text-white fw-bold"><i class="fas fa-play-circle me-2"></i> Run TE Calculation</div>
  <div class="card-body">
    <form method="POST" id="calcForm">
      <div class="row align-items-end">
        <div class="col-md-6">
          <label class="form-label fw-bold">Select Import Batch</label>
          <select name="batch_id" class="form-select form-select-lg" required>
            <option value="">-- Select a batch --</option>
            <?php foreach ($batches as $b): ?>
            <option value="<?= htmlspecialchars($b["import_batch_id"]) ?>" <?= ($selected_batch === $b["import_batch_id"]) ? "selected" : "" ?>>
              <?= htmlspecialchars($b["import_batch_id"]) ?> (Tax Year: <?= $b["tax_year"] ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg" id="runBtn">
              <i class="fas fa-cogs me-2"></i> Run Calculation
            </button>
          </div>
        </div>
        <div class="col-md-3">
          <div class="alert alert-info mb-0 py-2 small">
            <i class="fas fa-info-circle me-1"></i>
            Make sure Benchmark Rates and Provision Rules are configured first.
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Results Table -->
<?php if (!empty($results)): ?>
<div class="card shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center fw-bold">
    <span><i class="fas fa-table me-2"></i> TE Results — Batch: <code><?= htmlspecialchars($selected_batch) ?></code></span>
    <a href="report_provisions.php?batch=<?= urlencode($selected_batch) ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-chart-bar me-1"></i> View TE Report</a>
  </div>
  <div class="card-body p-0">
    <table class="table table-bordered table-hover datatable w-100 mb-0" style="font-size:0.82em">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Year</th>
          <th>TIN</th>
          <th>Company</th>
          <th>VAT?</th>
          <th>Sector</th>
          <th>Bm Rate</th>
          <th>Benchmark PT</th>
          <th>PT Paid</th>
          <th>PT TE</th>
          <th>Provisions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= $r["tax_year"] ?></td>
          <td><small class="font-monospace"><?= htmlspecialchars($r["tin"]) ?></small></td>
          <td><?= htmlspecialchars($r["company_name"]) ?></td>
          <td><span class="badge bg-<?= $r["is_vat_holder"] ? "success" : "secondary" ?>"><?= $r["is_vat_holder"] ? "VAT" : "MTP" ?></span></td>
          <td><?= htmlspecialchars($r["sector"]) ?></td>
          <td class="text-end"><?= $r["benchmark_rate_applied"] ?>%</td>
          <td class="text-end fw-bold"><?= number_format($r["benchmark_pt"], 0) ?></td>
          <td class="text-end"><?= number_format($r["pt_paid"], 0) ?></td>
          <td class="text-end text-danger fw-bold"><?= number_format($r["profit_tax_te"], 0) ?></td>
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
      </tbody>
      <tfoot>
        <tr class="table-warning fw-bold">
          <td colspan="8" class="text-end">Total Tax Expenditure (LAK):</td>
          <td class="text-end text-danger"><?= number_format(array_sum(array_column($results, "profit_tax_te")), 0) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
document.getElementById("calcForm").addEventListener("submit", function() {
    document.getElementById("runBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    document.getElementById("runBtn").disabled = true;
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
