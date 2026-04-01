<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "add_provision") {
            $stmt = $pdo->prepare("INSERT INTO vat_provisions (provision_number, start_year, end_year, legal_basis, description, purpose, type_of_te) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST["provision_number"], $_POST["start_year"], $_POST["end_year"],
                $_POST["legal_basis"], $_POST["description"], $_POST["purpose"], $_POST["type_of_te"]
            ]);
            $message = "VAT Provision added.";
        } elseif ($_POST["action"] == "delete_provision") {
            $pdo->prepare("DELETE FROM vat_provisions WHERE id = ?")->execute([$_POST["id"]]);
            $message = "VAT Provision deleted.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$provisions = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM vat_provision_conditions WHERE provision_id = p.id) as rule_count FROM vat_provisions p ORDER BY provision_number ASC, start_year DESC")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-boxes me-2 text-primary"></i> Domestic VAT Repository</h2>
      <p class="text-muted">Archive of legal provisions and categories for VAT Tax Expenditures.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVatProvModal"><i class="fas fa-plus me-1"></i> Add Provision</button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 0.9em;">
<thead class="bg-light text-uppercase small fw-bold">
<tr>
<th class="ps-4">Prov #</th>
<th>Effective</th>
<th>Legal Basis</th>
<th>Description & Purpose</th>
<th class="text-center">Active Rules</th>
<th class="pe-4 text-end">Actions</th>
</tr>
</thead>
        <tbody>
          <?php foreach ($provisions as $p): ?>
          <tr>
            <td class="ps-4 fw-bold text-primary">T#<?= htmlspecialchars($p["provision_number"]) ?></td>
            <td><span class="badge bg-light text-dark border"><i class="far fa-calendar-alt me-1 text-primary"></i> <?= $p["start_year"] ?>-<?= $p["end_year"] ?></span></td>
            <td class="text-muted small"><?= htmlspecialchars($p["legal_basis"]) ?></td>
<td>
<div class="fw-bold"><?= htmlspecialchars($p["purpose"]) ?></div>
<div class="text-muted small"><?= htmlspecialchars($p["description"]) ?></div>
</td>
<td class="text-center">
<span class="badge bg-primary rounded-pill"><?= $p['rule_count'] ?> rules</span>
</td>
<td class="pe-4 text-end">
<a href="vat_config_rules.php?provision_id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm me-1"><i class="fas fa-cogs"></i></a>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete this VAT provision?')">
<input type="hidden" name="action" value="delete_provision">
<input type="hidden" name="id" value="<?= $p["id"] ?>">
<button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
</form>
</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addVatProvModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content border-0 shadow-lg">
    <form method="POST">
      <input type="hidden" name="action" value="add_provision">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add VAT Provision</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">Provision #</label><input type="text" name="provision_number" class="form-control" required placeholder="e.g. 31"></div>
            <div class="col-md-4"><label class="form-label">Start Year</label><input type="number" name="start_year" class="form-control" value="2020" required></div>
            <div class="col-md-4"><label class="form-label">End Year</label><input type="number" name="end_year" class="form-control" value="2099" required></div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Legal Basis</label><input type="text" name="legal_basis" class="form-control" required placeholder="e.g. VAT Law Art 12.1.1"></div>
            <div class="col-md-6"><label class="form-label">Type of TE</label><select name="type_of_te" class="form-select"><option value="Exemption">Exemption</option><option value="Rate Relief">Rate Relief</option></select></div>
        </div>
        <div class="mb-3"><label class="form-label">Purpose/Objective</label><input type="text" name="purpose" class="form-control" required placeholder="e.g. Food security"></div>
        <div class="mb-0"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
      </div>
      <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Provision</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
