<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/te_land_concession_engine.php';

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate") {
    try {
        $batch_id = $_POST["batch_id"];
        
        $engine = new TELandConcessionEngine($pdo);
        $result = $engine->calculateBatch($batch_id);
        
        $message = "Calculated {$result['calculated']} companies. Total TE: " . number_format($result["total_te"], 0) . " Kip";
        
        if (!empty($result["errors"])) {
            $message .= " (" . count($result["errors"]) . " errors)";
            $msg_type = "warning";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$batches = $pdo->query("SELECT DISTINCT import_batch_id FROM companies WHERE land_area_sqm > 0")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-calculator me-2"></i> Land Concession Tax Calculation</h2>
    <p class="text-muted">Calculate tax expenditure for land concession based on benchmark rates and provisions.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Calculate Form -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <form method="POST" class="row g-3">
      <input type="hidden" name="action" value="calculate">
      
      <div class="col-md-6">
        <label class="form-label">Select Import Batch</label>
        <select name="batch_id" class="form-select" required>
          <option value="">-- Select Batch --</option>
          <?php foreach ($batches as $b): ?>
          <option value="<?= htmlspecialchars($b["import_batch_id"]) ?>"><?= htmlspecialchars($b["import_batch_id"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-calculator me-2"></i> Calculate
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Results -->
<?php
$results = $pdo->query("
    SELECT r.*, c.company_name, c.tin as tax_id, c.land_area_sqm, c.zone_type as company_zone
    FROM te_land_concession_result r
    JOIN companies c ON r.company_id = c.id
    ORDER BY r.te_land_concession DESC
")->fetchAll();
?>

<?php if (!empty($results)): ?>
<?php
$total_te = 0;
foreach ($results as $r) $total_te += $r["te_land_concession"];
?>
<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h2 text-primary"><?= number_format($total_te, 0) ?></div>
        <small class="text-muted">Total Tax Expenditure (Kip)</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h2"><?= count($results) ?></div>
        <small class="text-muted">Companies Calculated</small>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small"><?= count($results) ?> results</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 datatable">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Company</th>
            <th>Tax ID</th>
            <th>Land (m²)</th>
            <th>Zone</th>
            <th>Benchmark Rate</th>
            <th>Land Value</th>
            <th>Exemption</th>
            <th>Tax Expenditure</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($results as $r): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= htmlspecialchars($r["company_name"]) ?></td>
            <td><code><?= htmlspecialchars($r["tax_id"]) ?></code></td>
            <td><?= number_format($r["land_area_sqm"]) ?></td>
            <td><span class="badge bg-<?= $r["zone_type"] == "A" ? "success" : ($r["zone_type"] == "B" ? "warning" : "info") ?>"><?= $r["zone_type"] ?></span></td>
            <td><?= number_format($r["benchmark_rate"]) ?>/m²</td>
            <td><?= number_format($r["land_value_kip"]) ?></td>
            <td class="text-success">-<?= number_format($r["exemption_value"]) ?> (<?= $r["exemption_years"] ?>yr)</td>
            <td class="fw-bold text-primary"><?= number_format($r["te_land_concession"]) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>