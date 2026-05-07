<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/user_history.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$current_user_id = 1; // TODO: Get from session
$current_user_name = "Admin";

$upload_dir = __DIR__ . "/../uploads/users/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_user") {
                $photo_name = null;
                if (!empty($_FILES["photo"]["name"])) {
                    $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                    if (in_array($ext, ["jpg", "jpeg", "png", "gif"])) {
                        $photo_name = uniqid("user_") . "." . $ext;
                        move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $photo_name);
                    }
                }
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, position, phone, role_id, photo, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
                $stmt->execute([
                    $_POST["name"],
                    $_POST["email"],
                    $password,
                    $_POST["position"],
                    $_POST["phone"],
                    $_POST["role_id"] ?: null,
                    $photo_name,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                logUserAction($pdo, $current_user_id, $current_user_name, "CREATE", "Created user: " . $_POST["name"]);
                $message = "User added successfully.";
            } elseif ($_POST["action"] === "edit_user") {
                $sql = "UPDATE users SET name = ?, email = ?, position = ?, phone = ?, role_id = ?, active = ?";
                $params = [$_POST["name"], $_POST["email"], $_POST["position"], $_POST["phone"], $_POST["role_id"] ?: null, isset($_POST["active"]) ? 1 : 0];
                
                if (!empty($_POST["password"])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST["password"], PASSWORD_DEFAULT);
                }
                
                if (!empty($_FILES["photo"]["name"])) {
                    $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                    if (in_array($ext, ["jpg", "jpeg", "png", "gif"])) {
                        $photo_name = uniqid("user_") . "." . $ext;
                        move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $photo_name);
                        $sql .= ", photo = ?";
                        $params[] = $photo_name;
                    }
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $_POST["id"];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                logUserAction($pdo, $current_user_id, $current_user_name, "UPDATE", "Updated user: " . $_POST["name"]);
                $message = "User updated successfully.";
            } elseif ($_POST["action"] === "delete_user") {
                $user = $pdo->prepare("SELECT photo FROM users WHERE id = ?")->execute([$_POST["id"]]);
                $user = $pdo->prepare("SELECT photo FROM users WHERE id = ?")->fetch();
                if ($user && $user["photo"] && file_exists($upload_dir . $user["photo"])) {
                    unlink($upload_dir . $user["photo"]);
                }
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST["id"]]);
                logUserAction($pdo, $current_user_id, $current_user_name, "DELETE", "Deleted user ID: " . $_POST["id"]);
                $message = "User deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$search = $_GET["search"] ?? "";
$where = $search ? "WHERE u.name LIKE ? OR u.email LIKE ? OR u.position LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];
$sql = "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id $where ORDER BY u.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-users-cog me-2"></i> User Management</h2>
      <p class="text-muted">Part of the System section. Manage application users and credentials.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add New
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

<div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <div class="d-flex justify-content-between align-items-center">
      <span class="text-muted small">Show 1 ~ <?= count($users) ?> data from total <?= count($users) ?> records</span>
      <form method="GET" class="d-flex">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-outline-secondary" type="submit">Search</button>
          <?php if ($search): ?>
          <a href="?" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Photo</th>
            <th>Name</th>
            <th>Email</th>
            <th>Position</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="ps-4">
              <?php if ($u["photo"] && file_exists($upload_dir . $u["photo"])): ?>
              <img src="<?= BASE_URL ?>/uploads/users/<?= $u["photo"] ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
              <?php else: ?>
              <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
              </div>
              <?php endif; ?>
            </td>
            <td class="fw-bold"><?= htmlspecialchars($u["name"]) ?></td>
            <td><?= htmlspecialchars($u["email"]) ?></td>
            <td><?= htmlspecialchars($u["position"] ?? "-") ?></td>
            <td><?= htmlspecialchars($u["phone"] ?? "-") ?></td>
            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($u["role_name"] ?? "-") ?></span></td>
            <td>
              <?php if ($u["active"]): ?><span class="badge bg-success">Enabled</span>
              <?php else: ?><span class="badge bg-secondary">Disabled</span><?php endif; ?>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal"
                onclick='editUser(<?= json_encode($u) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                onclick='deleteUser(<?= $u["id"] ?>, <?= json_encode($u["name"]) ?>)'>
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No data available</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_user">
          <input type="hidden" name="id" id="userId">
          
          <div class="text-center mb-3">
            <div id="photoPreviewContainer">
              <?php if ($u["photo"] ?? false): ?>
              <img id="photoPreview" src="" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
              <?php else: ?>
              <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto" id="photoPlaceholder" style="width: 100px; height: 100px;">
                <i class="fas fa-user fa-3x"></i>
              </div>
              <?php endif; ?>
            </div>
            <label for="photoInput" class="btn btn-outline-secondary btn-sm mt-2">
              <i class="fas fa-camera me-1"></i> Upload Photo
            </label>
            <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*" onchange="previewPhoto(this)">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" id="userName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="userEmail" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password <span id="passwordReq">*</span></label>
            <input type="password" name="password" id="userPassword" class="form-control" required>
            <small class="text-muted">Leave blank to keep current password when editing</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Position</label>
            <input type="text" name="position" id="userPosition" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="userPhone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" id="userRole" class="form-select">
              <option value="">-- Select Role --</option>
              <?php foreach ($roles as $r): ?>
              <option value="<?= $r["id"] ?>"><?= htmlspecialchars($r["role_name"]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="userActive" class="form-check-input" checked>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
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
  document.getElementById("formAction").value = "add_user";
  document.getElementById("modalTitle").innerText = "Add New User";
  document.getElementById("userId").value = "";
  document.getElementById("userName").value = "";
  document.getElementById("userEmail").value = "";
  document.getElementById("userPassword").value = "";
  document.getElementById("userPassword").required = true;
  document.getElementById("passwordReq").innerText = "*";
  document.getElementById("userPosition").value = "";
  document.getElementById("userPhone").value = "";
  document.getElementById("userRole").value = "";
  document.getElementById("userActive").checked = true;
  
  var placeholder = document.getElementById("photoPlaceholder");
  if (placeholder) placeholder.style.display = "flex";
  var preview = document.getElementById("photoPreview");
  if (preview) preview.style.display = "none";
}

function editUser(user) {
  document.getElementById("formAction").value = "edit_user";
  document.getElementById("modalTitle").innerText = "Edit User";
  document.getElementById("userId").value = user.id;
  document.getElementById("userName").value = user.name;
  document.getElementById("userEmail").value = user.email;
  document.getElementById("userPassword").value = "";
  document.getElementById("userPassword").required = false;
  document.getElementById("passwordReq").innerText = "";
  document.getElementById("userPosition").value = user.position || "";
  document.getElementById("userPhone").value = user.phone || "";
  document.getElementById("userRole").value = user.role_id || "";
  document.getElementById("userActive").checked = user.active == 1;
  
  // Photo preview
  if (user.photo) {
    var container = document.getElementById("photoPreviewContainer");
    container.innerHTML = '<img id="photoPreview" src="<?= BASE_URL ?>/uploads/users/' + user.photo + '" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">';
  } else {
    var placeholder = document.getElementById("photoPlaceholder");
    if (placeholder) placeholder.style.display = "flex";
  }
}

function previewPhoto(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var container = document.getElementById("photoPreviewContainer");
      container.innerHTML = '<img id="photoPreview" src="' + e.target.result + '" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function deleteUser(id, name) {
  document.getElementById("deleteId").value = id;
  document.getElementById("deleteName").innerText = name;
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>