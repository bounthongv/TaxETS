<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET["delete"])) {
    try {
        $pdo->prepare("DELETE FROM individual_provisions WHERE id = ?")->execute([$_GET["delete"]]);
        $message = "PIT Provision deleted.";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Handle Actions (Add/Edit)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "add_provision") {
            $stmt = $pdo->prepare("INSERT INTO individual_provisions (provision_number, start_year, end_year, legal_basis, type_of_te, purpose, description, limit_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST["provision_number"],
                $_POST["start_year"] ?: 2020,
                $_POST["end_year"] ?: 2099,
                $_POST["legal_basis"],
                $_POST["type_of_te"],
                $_POST["purpose"],
                $_POST["description"],
                $_POST["limit_amount"] !== "" ? $_POST["limit_amount"] : null
            ]);
            $message = "PIT Provision added successfully.";
        } elseif ($_POST["action"] == "edit_provision") {
            $stmt = $pdo->prepare("UPDATE individual_provisions SET provision_number=?, start_year=?, end_year=?, legal_basis=?, type_of_te=?, purpose=?, description=?, limit_amount=? WHERE id=?");
            $stmt->execute([
                $_POST["provision_number"],
                $_POST["start_year"] ?: 2020,
                $_POST["end_year"] ?: 2099,
                $_POST["legal_basis"],
                $_POST["type_of_te"],
                $_POST["purpose"],
                $_POST["description"],
                $_POST["limit_amount"] !== "" ? $_POST["limit_amount"] : null,
                $_POST["id"]
            ]);
            $message = "PIT Provision updated successfully.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch Individual Provisions (PIT)
$provisions = [];
try {
    $provisions = $pdo->query("SELECT * FROM individual_provisions ORDER BY provision_number ASC, start_year DESC")->fetchAll();
} catch (Exception $e) { }

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-balance-scale me-2"></i> Individual Income Tax Repository</h2>
      <p class="text-muted">Archive of personal income tax regulations and Tax Expenditure (TE) provisions.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addPitProvModal"><i class="fas fa-plus me-1"></i> Add Provision</button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 fw-bold">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 0.9em;">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4" style="width: 100px;">Prov #</th>
            <th style="width: 150px;">Effective</th>
            <th style="width: 180px;">Legal Basis</th>
            <th style="width: 120px;">Type</th>
            <th>Description & Purpose</th>
            <th class="text-end" style="width: 160px;">Limit (LAK)</th>
            <th class="pe-4 text-end" style="width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($provisions as $p): ?>
          <tr>
            <td class="ps-4 fw-bold text-primary">T#<?= htmlspecialchars($p["provision_number"]) ?></td>
            <td>
              <span class="badge bg-light text-dark border small"><i class="far fa-calendar-alt me-1 text-primary"></i> <?= $p["start_year"] ?> - <?= $p["end_year"] ?></span>
            </td>
            <td class="text-muted"><?= htmlspecialchars($p["legal_basis"]) ?></td>
            <td>
              <span class="badge bg-<?= $p["type_of_te"] == "Exemption" ? "info text-dark" : "warning text-dark" ?> font-weight-normal">
                <?= htmlspecialchars($p["type_of_te"]) ?>
              </span>
            </td>
            <td>
              <div class="fw-bold text-dark"><?= htmlspecialchars($p["purpose"]) ?></div>
              <div class="text-muted small lh-sm"><?= htmlspecialchars($p["description"]) ?></div>
            </td>
            <td class="text-end fw-medium">
              <?= $p["limit_amount"] ? number_format($p["limit_amount"]) : "<span class='text-muted'>-</span>" ?>
            </td>
            <td class="pe-4 text-end">
              <button type="button" class="btn btn-outline-secondary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editPitProvModal<?= $p["id"] ?>" title="Edit"><i class="fas fa-edit"></i></button>
              <a href="?delete=<?= $p["id"] ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this PIT provision?')"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Add PIT Provision -->
<div class="modal fade" id="addPitProvModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <form method="POST">
        <input type="hidden" name="action" value="add_provision">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Add PIT Provision</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Provision #</label>
              <input type="text" name="provision_number" class="form-control" required placeholder="e.g. 21">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Start Year</label>
              <input type="number" name="start_year" class="form-control" value="2020" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">End Year</label>
              <input type="number" name="end_year" class="form-control" value="2099" required>
            </div>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Legal Basis</label>
              <input type="text" name="legal_basis" class="form-control" required placeholder="e.g. ITL Art 35 (2)">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Type of TE</label>
              <select name="type_of_te" class="form-select">
                <option value="Exemption">Exemption</option>
                <option value="Deduction">Deduction</option>
              </select>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Purpose / Category</label>
            <input type="text" name="purpose" class="form-control" required placeholder="Short name for the benefit...">
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Detailed Description</label>
            <textarea name="description" class="form-control" rows="3" required placeholder="Legal description of the provision..."></textarea>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-bold text-uppercase">Limit Amount (LAK) <small class="text-muted">(Optional)</small></label>
            <input type="number" name="limit_amount" class="form-control" placeholder="e.g. 1000000">
          </div>
        </div>
        <div class="modal-footer bg-light border-0 py-3">
          <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Provision</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Provision Modals -->
<?php foreach ($provisions as $p): ?>
<div class="modal fade" id="editPitProvModal<?= $p["id"] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <form method="POST">
        <input type="hidden" name="action" value="edit_provision">
        <input type="hidden" name="id" value="<?= $p["id"] ?>">
        <div class="modal-header bg-warning border-0 py-3">
          <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Edit PIT Provision</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Provision #</label>
              <input type="text" name="provision_number" class="form-control" value="<?= htmlspecialchars($p["provision_number"]) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Start Year</label>
              <input type="number" name="start_year" class="form-control" value="<?= $p["start_year"] ?: 2020 ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">End Year</label>
              <input type="number" name="end_year" class="form-control" value="<?= $p["end_year"] ?: 2099 ?>" required>
            </div>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Legal Basis</label>
              <input type="text" name="legal_basis" class="form-control" value="<?= htmlspecialchars($p["legal_basis"]) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Type of TE</label>
              <select name="type_of_te" class="form-select">
                <option value="Exemption" <?= $p["type_of_te"]=="Exemption"?"selected":"" ?>>Exemption</option>
                <option value="Deduction" <?= $p["type_of_te"]=="Deduction"?"selected":"" ?>>Deduction</option>
              </select>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Purpose / Category</label>
            <input type="text" name="purpose" class="form-control" value="<?= htmlspecialchars($p["purpose"]) ?>" required>
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Detailed Description</label>
            <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($p["description"]) ?></textarea>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-bold text-uppercase">Limit Amount (LAK) <small class="text-muted">(Optional)</small></label>
            <input type="number" name="limit_amount" class="form-control" value="<?= $p["limit_amount"] ?>" placeholder="e.g. 1000000">
          </div>
        </div>
        <div class="modal-footer bg-light border-0 py-3">
          <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning px-4 shadow-sm">Update Provision</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script>
function openEditModal(id) {
    var el = document.getElementById(id);
    if (el) new bootstrap.Modal(el).show();
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
