<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "add_rate") {
            $stmt = $pdo->prepare("INSERT INTO bm_vat (start_date, end_date, rate_percentage) VALUES (?, ?, ?)");
            $stmt->execute([$_POST["start_date"], $_POST["end_date"], $_POST["rate_percentage"]]);
            $message = "VAT Benchmark Rate added.";
        } elseif ($_POST["action"] == "delete_rate") {
            $pdo->prepare("DELETE FROM bm_vat WHERE id = ?")->execute([$_POST["id"]]);
            $message = "VAT Rate deleted.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$rates = $pdo->query("SELECT * FROM bm_vat ORDER BY start_date DESC")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-percent me-2 text-primary"></i> VAT Benchmark Rates</h2>
      <p class="text-muted">Manage historical and current Domestic VAT rates for legal benchmarking.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRateModal"><i class="fas fa-plus me-1"></i> Add Rate Period</button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="bg-light text-uppercase small fw-bold">
        <tr>
          <th class="ps-4">Effective From</th>
          <th>Effective To</th>
          <th>VAT Rate (%)</th>
          <th class="text-end pe-4">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rates as $r): ?>
        <tr>
          <td class="ps-4 fw-bold"><?= date("d M Y", strtotime($r["start_date"])) ?></td>
          <td><?= date("d M Y", strtotime($r["end_date"])) ?></td>
          <td><span class="badge bg-primary fs-6"><?= number_format($r["rate_percentage"], 0) ?>%</span></td>
          <td class="text-end pe-4">
            <form method="POST" class="d-inline" onsubmit="return confirm(\"Delete this rate period?\")">
              <input type="hidden" name="action" value="delete_rate">
              <input type="hidden" name="id" value="<?= $r["id"] ?>">
              <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addRateModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content border-0 shadow-lg">
    <form method="POST">
      <input type="hidden" name="action" value="add_rate">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add VAT Rate Period</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body p-4">
        <div class="mb-3"><label class="form-label small fw-bold text-uppercase">Start Date</label><input type="date" name="start_date" class="form-control" required></div>
        <div class="mb-3"><label class="form-label small fw-bold text-uppercase">End Date</label><input type="date" name="end_date" class="form-control" value="2099-12-31" required></div>
        <div class="mb-0"><label class="form-label small fw-bold text-uppercase">Rate Percentage (%)</label><input type="number" step="0.01" name="rate_percentage" class="form-control" placeholder="e.g. 10" required></div>
      </div>
      <div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Rate</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
