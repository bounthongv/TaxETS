<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_type") {
                $stmt = $pdo->prepare("INSERT INTO enterprise_types (type_name, active) VALUES (?, ?)");
                $stmt->execute([
                    $_POST["type_name"],
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Enterprise type added.";
            } elseif ($_POST["action"] === "edit_type") {
                $stmt = $pdo->prepare("UPDATE enterprise_types SET type_name = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["type_name"],
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Enterprise type updated.";
            } elseif ($_POST["action"] === "delete_type") {
                $pdo->prepare("DELETE FROM enterprise_types WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Enterprise type deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$types = $pdo->query("SELECT * FROM enterprise_types ORDER BY type_name")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-building me-2"></i> Enterprise Types</h2>
      <p class="text-muted">Manage the list of enterprise types used in MOIC and other modules.</p>
    </div>
    <div>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#typeModal" onclick="clearForm()">
        <i class="fas fa-plus me-1"></i> Add Enterprise Type
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
            <th class="ps-4">ID</th>
            <th>Type Name</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($types as $t): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= $t["id"] ?></td>
            <td><?= htmlspecialchars($t["type_name"]) ?></td>
            <td><?= $t["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editType(<?= json_encode($t) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteType(<?= $t["id"] ?>, '<?= htmlspecialchars($t['type_name']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="typeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Enterprise Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_type">
          <input type="hidden" name="id" id="typeId">
          <div class="mb-3">
            <label class="form-label">Type Name *</label>
            <input type="text" name="type_name" id="typeName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="typeActive" class="form-check-input" checked>
              <span class="form-check-label">Active</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
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
        <input type="hidden" name="action" value="delete_type">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Delete Enterprise Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Delete enterprise type <strong id="deleteName"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById("formAction").value = "add_type";
    document.getElementById("modalTitle").innerText = "Add Enterprise Type";
    document.getElementById("typeId").value = "";
    document.getElementById("typeName").value = "";
    document.getElementById("typeActive").checked = true;
}

function editType(t) {
    document.getElementById("formAction").value = "edit_type";
    document.getElementById("modalTitle").innerText = "Edit Enterprise Type";
    document.getElementById("typeId").value = t.id;
    document.getElementById("typeName").value = t.type_name;
    document.getElementById("typeActive").checked = t.active == 1;
    new bootstrap.Modal(document.getElementById("typeModal")).show();
}

function deleteType(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>