<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "add_provision") {
            $stmt = $pdo->prepare("INSERT INTO profit_provisions (provision_number, start_year, end_year, legal_reference, description, target_rate, is_exemption) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST["provision_number"], 
                $_POST["start_year"] ?: 2020, 
                $_POST["end_year"] ?: 2099, 
                $_POST["legal_reference"], 
                $_POST["description"],
                $_POST["target_rate"] !== "" ? $_POST["target_rate"] : null,
                isset($_POST["is_exemption"]) ? 1 : 0
            ]);
            $message = "Provision added successfully.";
        } elseif ($_POST["action"] == "edit_provision") {
            $stmt = $pdo->prepare("UPDATE profit_provisions SET provision_number=?, start_year=?, end_year=?, legal_reference=?, description=?, target_rate=?, is_exemption=? WHERE id=?");
            $stmt->execute([
                $_POST["provision_number"], 
                $_POST["start_year"] ?: 2020, 
                $_POST["end_year"] ?: 2099, 
                $_POST["legal_reference"], 
                $_POST["description"],
                $_POST["target_rate"] !== "" ? $_POST["target_rate"] : null,
                isset($_POST["is_exemption"]) ? 1 : 0,
                $_POST["id"]
            ]);
            $message = "Provision updated successfully.";
        } elseif ($_POST["action"] == "delete_provision") {
            $pdo->prepare("DELETE FROM profit_provisions WHERE id = ?")->execute([$_POST["id"]]);
            $message = "Provision deleted.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

$provisions = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM profit_provision_conditions WHERE provision_id = p.id) as rule_count FROM profit_provisions p ORDER BY provision_number ASC, start_year DESC")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-gavel me-2"></i> Tax Provisions (CIT)</h2>
      <p class="text-muted">Manage legal provisions and their effectiveness periods for Corporate Income Tax.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addProvModal"><i class="fas fa-plus me-1"></i> Add Provision</button>
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
    <table class="table table-hover align-middle mb-0">
      <thead class="bg-light text-uppercase small fw-bold">
        <tr>
          <th class="ps-4">Prov #</th>
          <th>Effective Period</th>
          <th>Legal Reference</th>
          <th>Description</th>
          <th>Target Rate</th>
          <th class="text-center">Active Rules</th>
          <th class="pe-4 text-end">Actions</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($provisions as $r): ?>
        <tr>
          <td class="ps-4 fw-bold text-primary">T#<?= htmlspecialchars($r["provision_number"]) ?></td>
          <td>
            <span class="badge bg-light text-dark border"><i class="far fa-calendar-alt me-1 text-primary"></i> <?= $r["start_year"] ?> - <?= $r["end_year"] ?></span>
          </td>
          <td class="text-muted"><?= htmlspecialchars($r["legal_reference"]) ?></td>
          <td class="fw-medium text-dark"><?= htmlspecialchars($r["description"]) ?></td>
          <td>
            <?php if ($r["is_exemption"]): ?><span class="badge bg-success">Exemption (0%)</span>
            <?php elseif ($r["target_rate"] !== null): ?><span class="badge bg-info text-dark"><?= $r["target_rate"] ?>%</span>
            <?php else: ?><span class="text-muted small">Dynamic</span><?php endif; ?>
          </td>
          <td class="text-center">
             <span class="badge bg-<?= $r["rule_count"] > 0 ? "primary" : "secondary" ?> rounded-pill"><?= $r["rule_count"] ?> rules</span>
          </td>
          <td class="pe-4 text-end">
             <div class="d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#editProvModal<?= $r["id"] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                <a href="config_rules.php?provision_id=<?= $r["id"] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit Logic Rules"><i class="fas fa-cogs"></i></a>
                <form method="POST" class="d-inline" onsubmit="return confirm(\"Delete provision and all associated rules?\")">
                  <input type="hidden" name="action" value="delete_provision">
                  <input type="hidden" name="id" value="<?= $r["id"] ?>">
                  <button class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
             </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Provision -->
<div class="modal fade" id="addProvModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <form method="POST">
        <input type="hidden" name="action" value="add_provision">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Add New Provision</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Provision #</label>
              <input type="text" name="provision_number" class="form-control" required placeholder="e.g. 1A">
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
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Legal Reference</label>
            <input type="text" name="legal_reference" class="form-control" required placeholder="e.g. IPL Art. 9 Sect 1">
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Description</label>
            <textarea name="description" class="form-control" rows="2" required placeholder="Briefly describe this tax expenditure provision..."></textarea>
          </div>
          <div class="row align-items-end g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Target Rate (%) <small class="text-muted">(Optional)</small></label>
              <div class="input-group">
                <input type="number" step="0.01" name="target_rate" class="form-control" placeholder="e.g. 5">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch p-2 ps-5 rounded border border-success-subtle bg-success-light">
                <input class="form-check-input" type="checkbox" name="is_exemption" id="isExemption">
                <label class="form-check-label text-success fw-bold" for="isExemption">Full Tax Exemption (0%)</label>
              </div>
            </div>
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

<!-- Modal: Edit Provisions -->
<?php foreach ($provisions as $r): ?>
<div class="modal fade" id="editProvModal<?= $r["id"] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <form method="POST">
        <input type="hidden" name="action" value="edit_provision">
        <input type="hidden" name="id" value="<?= $r["id"] ?>">
        <div class="modal-header bg-warning border-0 py-3">
          <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Edit Provision</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Provision #</label>
              <input type="text" name="provision_number" class="form-control" value="<?= htmlspecialchars($r["provision_number"]) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Start Year</label>
              <input type="number" name="start_year" class="form-control" value="<?= $r["start_year"] ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">End Year</label>
              <input type="number" name="end_year" class="form-control" value="<?= $r["end_year"] ?>" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Legal Reference</label>
            <input type="text" name="legal_reference" class="form-control" value="<?= htmlspecialchars($r["legal_reference"]) ?>" required>
          </div>
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Description</label>
            <textarea name="description" class="form-control" rows="2" required><?= htmlspecialchars($r["description"]) ?></textarea>
          </div>
          <div class="row align-items-end g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Target Rate (%)</label>
              <div class="input-group">
                <input type="number" step="0.01" name="target_rate" class="form-control" value="<?= $r["target_rate"] ?>">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch p-2 ps-5 rounded border border-success-subtle bg-success-light">
                <input class="form-check-input" type="checkbox" name="is_exemption" id="isExemptionEdit<?= $r["id"] ?>" <?= $r["is_exemption"] ? "checked" : "" ?>>
                <label class="form-check-label text-success fw-bold" for="isExemptionEdit<?= $r["id"] ?>">Full Tax Exemption (0%)</label>
              </div>
            </div>
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

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

