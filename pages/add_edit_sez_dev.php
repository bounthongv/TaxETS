<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$id = $_GET['id'] ?? null;
$record = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM import_sez_data WHERE id = ? AND type = 'Developer'");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "batch_id"           => $record['batch_id'] ?? ("MANUAL_" . date("YmdHis")),
        "tax_year"           => $_POST["tax_year"],
        "tin"                => $_POST["tin"],
        "license_date"       => $_POST["license_date"],
        "province_id"        => $_POST["province_id"] ?: null,
        "district_id"        => $_POST["district_id"] ?: null,
        "amount_infra_basic" => $_POST["amount_infra_basic"] ?: 0,
        "amount_infra_other" => $_POST["amount_infra_other"] ?: 0,
        "type"               => 'Developer'
    ];

    try {
        if ($id) {
            $sql = "UPDATE import_sez_data SET " . implode("=?, ", array_keys($data)) . "=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($data), [$id]));
            $message = "Record updated successfully.";
        } else {
            $data["batch_id"] = "MANUAL_" . date("YmdHis");
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_sez_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            $message = "Record added successfully.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$provinces = $pdo->query("SELECT id, province_name FROM provinces ORDER BY province_name")->fetchAll();
$districts = [];
if ($record && $record['province_id']) {
    $stmt = $pdo->prepare("SELECT id, district_name FROM districts WHERE province_id = ? ORDER BY district_name");
    $stmt->execute([$record['province_id']]);
    $districts = $stmt->fetchAll();
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-edit me-2 text-info"></i> <?= $id ? 'Edit' : 'Add' ?> SEZ Developer Record</h2>
    <p class="text-muted">Enter infrastructure data for an SEZ developer.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0 border-top border-4 border-info">
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
        <div class="col-md-4">
          <label class="form-label fw-bold">Province</label>
          <select name="province_id" id="province_id" class="form-select" onchange="loadDistricts(this.value)">
            <option value="">Select Province</option>
            <?php foreach ($provinces as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($record['province_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['province_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">District</label>
          <select name="district_id" id="district_id" class="form-select">
            <option value="">Select District</option>
            <?php foreach ($districts as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($record['district_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['district_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label fw-bold text-primary">Construction (Road, Elec, Water, Wastewater)</label>
          <input type="number" step="0.01" name="amount_infra_basic" class="form-control border-primary" value="<?= $record['amount_infra_basic'] ?? 0 ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold text-info">Other Infrastructure Construction</label>
          <input type="number" step="0.01" name="amount_infra_other" class="form-control border-info" value="<?= $record['amount_infra_other'] ?? 0 ?>">
        </div>
      </div>

      <div class="mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-info text-white btn-lg px-5 shadow-sm">
          <i class="fas fa-save me-2"></i> <?= $id ? 'Update Record' : 'Save Record' ?>
        </button>
        <a href="import_sez_dev.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
function loadDistricts(provinceId) {
    if (!provinceId) {
        document.getElementById('district_id').innerHTML = '<option value="">Select District</option>';
        return;
    }
    fetch('get_districts.php?province_id=' + provinceId)
        .then(response => response.json())
        .then(data => {
            let html = '<option value="">Select District</option>';
            data.forEach(d => {
                html += `<option value="${d.id}">${d.district_name}</option>`;
            });
            document.getElementById('district_id').innerHTML = html;
        });
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
