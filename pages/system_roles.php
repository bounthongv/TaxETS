<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$modules = [
    "system_users" => "User Management",
    "system_roles" => "Role Management",
    "dictionary_province" => "Province",
    "dictionary_district" => "District",
    "dictionary_zone" => "Investment Zone",
    "config_rates" => "CIT Rates",
    "benchmark_individual" => "PIT Benchmark",
    "benchmark_vat" => "VAT Benchmark",
    "benchmark_customs" => "Customs Benchmark",
    "benchmark_excise" => "Excise Benchmark",
    "config_provisions" => "CIT Provisions",
    "repo_individual" => "PIT Provisions",
    "repo_vat" => "VAT Provisions",
    "import_cit" => "Import Profit Tax",
    "import_individual" => "Import Individual Tax",
    "import_vat" => "Import VAT",
    "calculator" => "TE Calculation",
    "report_tax_type" => "Reports",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_role") {
                $stmt = $pdo->prepare("INSERT INTO roles (role_name, role_description) VALUES (?, ?)");
                $stmt->execute([$_POST["role_name"], $_POST["role_description"]]);
                $role_id = $pdo->lastInsertId();
                foreach ($modules as $mod => $name) {
                    $pdo->prepare("INSERT INTO role_permissions (role_id, module, can_read) VALUES (?, ?, TRUE)")->execute([$role_id, $mod]);
                }
                $message = "Role added successfully.";
            } elseif ($_POST["action"] === "edit_role") {
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, role_description = ? WHERE id = ?");
                $stmt->execute([$_POST["role_name"], $_POST["role_description"], $_POST["id"]]);
                $message = "Role updated successfully.";
            } elseif ($_POST["action"] === "delete_role") {
                $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$_POST["id"]]);
                $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Role deleted.";
            } elseif ($_POST["action"] === "save_permissions") {
                $role_id = $_POST["role_id"];
                $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);
                foreach ($_POST["permissions"] ?? [] as $mod => $perms) {
                    $pdo->prepare("INSERT INTO role_permissions (role_id, module, can_create, can_read, can_update, can_delete) VALUES (?, ?, ?, ?, ?, ?)")->execute([
                        $role_id, $mod,
                        isset($perms["c"]) ? 1 : 0,
                        isset($perms["r"]) ? 1 : 0,
                        isset($perms["u"]) ? 1 : 0,
                        isset($perms["d"]) ? 1 : 0
                    ]);
                }
                $message = "Permissions saved successfully.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$roles = $pdo->query("
    SELECT r.*, 
    (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count 
    FROM roles r 
    ORDER BY r.id ASC
")->fetchAll();

$selected_role = $_GET["role_id"] ?? ($roles[0]["id"] ?? null);
$permissions = [];
if ($selected_role) {
    $perms = $pdo->prepare("SELECT * FROM role_permissions WHERE role_id = ?");
    $perms->execute([$selected_role]);
    foreach ($perms->fetchAll() as $p) {
        $permissions[$p["module"]] = $p;
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-user-shield me-2"></i> Role Management</h2>
      <p class="text-muted">Part of the System section. Define and manage user permissions and roles.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add Role
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

<div class="row">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <span class="text-muted small">Total <?= count($roles) ?> roles</span>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php foreach ($roles as $r): ?>
          <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $selected_role == $r["id"] ? "active" : "" ?>" 
               onclick="window.location='?role_id=<?= $r["id"] ?>'">
            <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              <strong><?= htmlspecialchars($r["role_name"]) ?></strong>
              <br><small class="<?= $selected_role == $r["id"] ? "text-white-50" : "text-muted" ?>"><?= $r["user_count"] ?> users</small>
            </div>
            <div class="d-flex gap-1">
              <button class="btn btn-sm <?= $selected_role == $r["id"] ? "btn-light" : "btn-outline-primary" ?>" onclick="event.stopPropagation(); editRole(<?= htmlspecialchars(json_encode($r)) ?>)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm <?= $selected_role == $r["id"] ? "btn-light text-danger" : "btn-outline-danger" ?>" onclick="event.stopPropagation(); deleteRole(<?= $r["id"] ?>, <?= json_encode($r["role_name"]) ?>)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-9">
    <?php if ($selected_role): ?>
    <form method="POST">
      <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-primary">
            <i class="fas fa-key me-2"></i>Permissions - <?= htmlspecialchars($roles[array_search($selected_role, array_column($roles, "id"))]["role_name"] ?? "") ?>
          </h5>
          <button type="submit" class="btn btn-primary shadow-sm">
            <i class="fas fa-save me-1"></i> Save Permissions
          </button>
        </div>
        <div class="card-body p-0">
          <input type="hidden" name="action" value="save_permissions">
          <input type="hidden" name="role_id" value="<?= $selected_role ?>">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light text-muted small">
                <tr>
                  <th class="ps-4" style="width: 40%;">Module</th>
                  <th class="text-center">Create</th>
                  <th class="text-center">Read</th>
                  <th class="text-center">Update</th>
                  <th class="text-center">Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($modules as $mod => $name): 
                  $p = $permissions[$mod] ?? ["can_create"=>0, "can_read"=>1, "can_update"=>0, "can_delete"=>0];
                ?>
                <tr>
                  <td class="ps-4">
                    <div class="fw-bold"><?= htmlspecialchars($name) ?></div>
                    <small class="text-muted d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($mod) ?></small>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input" name="permissions[<?= $mod ?>][c]" <?= $p["can_create"] ? "checked" : "" ?>>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input" name="permissions[<?= $mod ?>][r]" <?= $p["can_read"] ? "checked" : "" ?>>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input" name="permissions[<?= $mod ?>][u]" <?= $p["can_update"] ? "checked" : "" ?>>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input" name="permissions[<?= $mod ?>][d]" <?= $p["can_delete"] ? "checked" : "" ?>>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Add/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_role">
          <input type="hidden" name="id" id="roleId">
          <div class="mb-3">
            <label class="form-label">Role Name *</label>
            <input type="text" name="role_name" id="roleName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="role_description" id="roleDescription" class="form-control" rows="3"></textarea>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="delete_role">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete role <strong id="deleteName"></strong>?</p>
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
  document.getElementById("formAction").value = "add_role";
  document.getElementById("modalTitle").innerText = "Add Role";
  document.getElementById("roleId").value = "";
  document.getElementById("roleName").value = "";
  document.getElementById("roleDescription").value = "";
}

function editRole(role) {
  document.getElementById("formAction").value = "edit_role";
  document.getElementById("modalTitle").innerText = "Edit Role";
  document.getElementById("roleId").value = role.id;
  document.getElementById("roleName").value = role.role_name;
  document.getElementById("roleDescription").value = role.role_description || "";
  var myModal = new bootstrap.Modal(document.getElementById("roleModal"));
  myModal.show();
}

function deleteRole(id, name) {
  document.getElementById("deleteId").value = id;
  document.getElementById("deleteName").innerText = name;
  var myModal = new bootstrap.Modal(document.getElementById("deleteModal"));
  myModal.show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>