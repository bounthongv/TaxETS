<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "calculate") {
    // Placeholder for natural resource calculation logic
    $message = "Calculation logic for Natural Resource is being implemented.";
    $msg_type = "info";
}

$batches = $pdo->query("SELECT DISTINCT import_batch_id FROM companies WHERE resource_extraction_item IS NOT NULL ORDER BY import_date DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-calculator me-2"></i> Natural Resource TE Calculation</h2>
    <p class="text-muted">Calculate tax expenditure for natural resource extraction based on benchmark percentages.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

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
        <button type="submit" class="btn btn-success w-100">
          <i class="fas fa-calculator me-2"></i> Calculate
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-body py-5 text-center text-muted">
        <i class="fas fa-info-circle fa-3x mb-3 opacity-25"></i>
        <h4>Ready for Calculation</h4>
        <p>Once you have imported data for Natural Resources, select a batch above to run the TE calculation.</p>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
