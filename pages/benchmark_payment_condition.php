<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "save_condition") {
                $id = $_POST["id"] ?? "";
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE bm_payment_condition_codes SET condition_code = ?, description = ?, effective_date_from = ?, effective_date_to = ?, active = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST["condition_code"],
                        $_POST["description"],
                        $_POST["effective_date_from"] ?: null,
                        $_POST["effective_date_to"] ?: null,
                        isset($_POST["active"]) ? 1 : 0,
                        $id
                    ]);
                    $message = "Payment condition code updated.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO bm_payment_condition_codes (condition_code, description, effective_date_from, effective_date_to, active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST["condition_code"],
                        $_POST["description"],
                        $_POST["effective_date_from"] ?: null,
                        $_POST["effective_date_to"] ?: null,
                        isset($_POST["active"]) ? 1 : 0
                    ]);
                    $message = "Payment condition code added.";
                }
            } elseif ($_POST["action"] === "delete_condition") {
                $pdo->prepare("DELETE FROM bm_payment_condition_codes WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Payment condition code deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch records
$records = $pdo->query("SELECT * FROM bm_payment_condition_codes ORDER BY condition_code ASC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-file-invoice-dollar me-2"></i> Payment Condition Codes</h2>
      <p class="text-muted">Manage standard payment condition codes for tax classification.</p>
    </div>
    <div>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#conditionModal" onclick="clearForm()">
        <i class="fas fa-plus me-1"></i> Add Condition Code
      </button>
    </div>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Code</th>
            <th>Description</th>
            <th>Effective From</th>
            <th>Effective To</th>
            <th class="text-center">Active</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($records as $r): ?>
          <tr>
            <td class="ps-4 fw-bold text-success"><?= htmlspecialchars($r["condition_code"]) ?></td>
            <td><?= htmlspecialchars($r["description"]) ?></td>
            <td><?= htmlspecialchars($r["effective_date_from"] ?? "-") ?></td>
            <td><?= htmlspecialchars($r["effective_date_to"] ?? "-") ?></td>
            <td class="text-center">
              <span class="badge bg-<?= $r['active'] ? 'success' : 'secondary' ?>">
                <?= $r['active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editCondition(<?= json_encode($r) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteCondition(<?= $r["id"] ?>, '<?= htmlspecialchars($r['condition_code']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($records)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No payment condition codes. Click "Add Condition Code" to start.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="conditionModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Condition Code</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="save_condition">
          <input type="hidden" name="id" id="conditionId">
          
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Code *</label>
              <input type="text" name="condition_code" id="conditionCode" class="form-control" maxlength="10" required placeholder="e.g. 000">
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label fw-bold">Description *</label>
              <input type="text" name="description" id="description" class="form-control" required placeholder="Condition Description">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Effective Date From</label>
              <input type="date" name="effective_date_from" id="effectiveFrom" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Effective Date To</label>
              <input type="date" name="effective_date_to" id="effectiveTo" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" name="active" id="conditionActive" class="form-check-input" checked>
              <label class="form-check-label">Active</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Condition Code</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="delete_condition">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Delete Confirmation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the condition code <strong id="deleteName"></strong>?</p>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger px-4">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById("conditionId").value = "";
    document.getElementById("modalTitle").innerText = "Add Condition Code";
    document.getElementById("conditionCode").value = "";
    document.getElementById("description").value = "";
    document.getElementById("effectiveFrom").value = "";
    document.getElementById("effectiveTo").value = "";
    document.getElementById("conditionActive").checked = true;
}

function editCondition(r) {
    document.getElementById("conditionId").value = r.id;
    document.getElementById("modalTitle").innerText = "Edit Condition Code";
    document.getElementById("conditionCode").value = r.condition_code;
    document.getElementById("description").value = r.description;
    document.getElementById("effectiveFrom").value = r.effective_date_from || "";
    document.getElementById("effectiveTo").value = r.effective_date_to || "";
    document.getElementById("conditionActive").checked = r.active == 1;
    new bootstrap.Modal(document.getElementById("conditionModal")).show();
}

function deleteCondition(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
