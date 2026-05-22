<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$id = $_GET['id'] ?? null;
$record = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM import_resource_data WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "batch_id"      => $record['batch_id'] ?? ("MANUAL_" . date("YmdHis")),
        "tax_year"      => $_POST["tax_year"],
        "tin"           => $_POST["tin"],
        "license_date"  => $_POST["license_date"],
        "resource_type" => $_POST["resource_type"],
        "actual_rate"   => $_POST["actual_rate"] ?: 0,
        "fee_collected" => $_POST["fee_collected"] ?: 0
    ];

    try {
        if ($id) {
            $sql = "UPDATE import_resource_data SET " . implode("=?, ", array_keys($data)) . "=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($data), [$id]));
            $message = "Record updated successfully.";
        } else {
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_resource_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            $message = "Record added successfully.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$resource_types = $pdo->query("SELECT item_no, item_name FROM bm_natural_resource WHERE active = 1 ORDER BY item_no")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-edit me-2 text-warning"></i> <?= $id ? 'Edit' : 'Add' ?> Resource Fee Record</h2>
    <p class="text-muted">Enter natural resource fee collection data manually.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0 border-top border-4 border-warning">
  <div class="card-body">
    <form method="POST">
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <label class="form-label fw-bold">TIN</label>
          <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($record['tin'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Fiscal Year</label>
          <input type="number" name="tax_year" class="form-control" value="<?= htmlspecialchars($record['tax_year'] ?? date('Y')) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">License Date</label>
          <input type="date" name="license_date" class="form-control" value="<?= htmlspecialchars($record['license_date'] ?? '') ?>">
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label fw-bold">Resource Type</label>
          <select name="resource_type" class="form-select" required>
            <option value="">Select Resource Type</option>
            <?php foreach ($resource_types as $rt): ?>
              <option value="<?= htmlspecialchars($rt['item_no']) ?>" <?= ($record['resource_type'] ?? '') == $rt['item_no'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($rt['item_no']) ?> - <?= htmlspecialchars($rt['item_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Actual Rate (%)</label>
          <input type="number" step="0.01" name="actual_rate" class="form-control" value="<?= $record['actual_rate'] ?? 0 ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold text-success">Fee Collected</label>
          <input type="number" step="0.01" name="fee_collected" class="form-control border-success" value="<?= $record['fee_collected'] ?? 0 ?>" required>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-warning text-dark btn-lg px-5 shadow-sm fw-bold">
          <i class="fas fa-save me-2"></i> <?= $id ? 'Update Record' : 'Save Record' ?>
        </button>
        <a href="import_resource.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
