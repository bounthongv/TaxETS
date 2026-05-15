<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_prov") {
                $stmt = $pdo->prepare("INSERT INTO land_concession_provisions (provision_code, provision_name, category, exemption_years, conditions, active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["provision_code"],
                    $_POST["provision_name"],
                    $_POST["category"],
                    $_POST["exemption_years"],
                    $_POST["conditions"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Provision added.";
            } elseif ($_POST["action"] === "edit_prov") {
                $stmt = $pdo->prepare("UPDATE land_concession_provisions SET provision_code = ?, provision_name = ?, category = ?, exemption_years = ?, conditions = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["provision_code"],
                    $_POST["provision_name"],
                    $_POST["category"],
                    $_POST["exemption_years"],
                    $_POST["conditions"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Provision updated.";
            } elseif ($_POST["action"] === "delete_prov") {
                $pdo->prepare("DELETE FROM land_concession_provisions WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Provision deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$category_filter = $_GET["category_filter"] ?? "";

$where = "1=1";
if ($category_filter) {
    $where .= " AND category = '" . $pdo->quote($category_filter) . "'";
}

$provisions = $pdo->query("SELECT * FROM land_concession_provisions WHERE $where ORDER BY exemption_years DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-file-contract me-2"></i> Land Concession Provisions</h2>
      <p class="text-muted">Exemption rules for land concession tax.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#provModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add Provision
    </button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<?php if (!$category_filter): ?>
<?php
$industrial = $pdo->query("SELECT COUNT(*) FROM land_concession_provisions WHERE category = 'Industrial' AND active = 1")->fetchColumn();
$sez = $pdo->query("SELECT COUNT(*) FROM land_concession_provisions WHERE category = 'SEZ' AND active = 1")->fetchColumn();
$priority = $pdo->query("SELECT COUNT(*) FROM land_concession_provisions WHERE category = 'Priority' AND active = 1")->fetchColumn();
?>
<div class="row mb-3">
  <div class="col-md-2">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-primary"><?= $industrial ?></div>
        <small class="text-muted">Industrial</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-success"><?= $sez ?></div>
        <small class="text-muted">SEZ</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-warning"><?= $priority ?></div>
        <small class="text-muted">Priority</small>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small"><?= count($provisions) ?> provisions</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Code</th>
            <th>Provision Name</th>
            <th>Category</th>
            <th>Exemption Years</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($provisions as $p): ?>
          <tr>
            <td class="ps-4"><code><?= htmlspecialchars($p["provision_code"]) ?></code></td>
            <td><?= htmlspecialchars($p["provision_name"]) ?></td>
            <td>
              <?php if ($p["category"] == "Industrial"): ?><span class="badge bg-primary">Industrial</span>
              <?php elseif ($p["category"] == "SEZ"): ?><span class="badge bg-success">SEZ</span>
              <?php elseif ($p["category"] == "Priority"): ?><span class="badge bg-warning text-dark">Priority</span>
              <?php else: ?><span class="badge bg-secondary"><?= htmlspecialchars($p["category"]) ?></span><?php endif; ?>
            </td>
            <td class="fw-bold"><?= $p["exemption_years"] ?> years</td>
            <td><?= $p["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editProv(<?= json_encode($p) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete_prov">
                <input type="hidden" name="id" value="<?= $p["id"] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($provisions)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No provisions. Click "Add Provision" to create.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="provModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Land Concession Provision</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" id="modalAction" value="add_prov">
          <input type="hidden" name="id" id="provId">
          
          <div class="mb-3">
            <label class="form-label">Provision Code</label>
            <input type="text" name="provision_code" id="provisionCode" class="form-control" required placeholder="e.g., LC_IND">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Provision Name</label>
            <input type="text" name="provision_name" id="provisionName" class="form-control" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" id="category" class="form-select" required>
              <option value="Industrial">Industrial / Factory</option>
              <option value="SEZ">SEZ (Special Economic Zone)</option>
              <option value="Priority">Priority Project</option>
              <option value="Other">Other</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Exemption Years</label>
            <input type="number" name="exemption_years" id="exemptionYears" class="form-control" required min="0" value="5">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Conditions (JSON)</label>
            <textarea name="conditions" id="conditions" class="form-control" rows="2" placeholder='{"sector": "manufacturing"}'></textarea>
          </div>
          
          <div class="form-check">
            <input type="checkbox" class="form-check-input" name="active" id="active" value="1" checked>
            <label class="form-check-label" for="active">Active</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalAction').value = 'add_prov';
    document.getElementById('provId').value = '';
    document.getElementById('provisionCode').value = '';
    document.getElementById('provisionName').value = '';
    document.getElementById('category').value = 'Industrial';
    document.getElementById('exemptionYears').value = '5';
    document.getElementById('conditions').value = '';
    document.getElementById('active').checked = true;
}

function editProv(data) {
    document.getElementById('modalAction').value = 'edit_prov';
    document.getElementById('provId').value = data.id;
    document.getElementById('provisionCode').value = data.provision_code;
    document.getElementById('provisionName').value = data.provision_name;
    document.getElementById('category').value = data.category || 'Other';
    document.getElementById('exemptionYears').value = data.exemption_years;
    document.getElementById('conditions').value = data.conditions || '';
    document.getElementById('active').checked = data.active == 1;
    new bootstrap.Modal(document.getElementById('provModal')).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>