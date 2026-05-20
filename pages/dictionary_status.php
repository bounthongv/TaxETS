<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"])) {
        try {
            if ($_POST["action"] === "save") {
                $id = $_POST["id"] ?? "";
                $name = $_POST["status_name"] ?? "";
                $desc = $_POST["description"] ?? "";

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE enterprise_project_status SET status_name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $desc, $id]);
                    $message = "Status updated successfully.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO enterprise_project_status (status_name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $desc]);
                    $message = "Status added successfully.";
                }
            } elseif ($_POST["action"] === "delete") {
                $id = $_POST["id"];
                // Check if in use
                $check = $pdo->prepare("SELECT COUNT(*) FROM repo_moic WHERE status_id = ?");
                $check->execute([$id]);
                if ($check->fetchColumn() > 0) {
                    $message = "Cannot delete status as it is currently assigned to enterprises.";
                    $msg_type = "danger";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM enterprise_project_status WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Status deleted successfully.";
                }
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

$statuses = $pdo->query("SELECT * FROM enterprise_project_status ORDER BY status_name ASC")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-tasks me-2"></i> Enterprise/Project Status</h2>
            <p class="text-muted">Manage status types for enterprises and projects.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="clearForm()">
            <i class="fas fa-plus me-1"></i> Add Status
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
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
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th>Status Name</th>
                        <th>Description</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statuses as $s): ?>
                    <tr>
                        <td class="ps-4"><?= $s["id"] ?></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($s["status_name"]) ?></td>
                        <td><?= htmlspecialchars($s["description"]) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick='editStatus(<?= json_encode($s) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteStatus(<?= $s["id"] ?>, '<?= htmlspecialchars($s["status_name"]) ?>')">
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
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="statusId">
                    <div class="mb-3">
                        <label class="form-label">Status Name *</label>
                        <input type="text" name="status_name" id="statusName" class="form-control" required placeholder="e.g. Active, Pending...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="statusDesc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Delete Status</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete status <strong id="deleteName"></strong>?</p>
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
    document.getElementById("statusId").value = "";
    document.getElementById("statusName").value = "";
    document.getElementById("statusDesc").value = "";
    document.getElementById("modalTitle").innerText = "Add Status";
}

function editStatus(s) {
    document.getElementById("statusId").value = s.id;
    document.getElementById("statusName").value = s.status_name;
    document.getElementById("statusDesc").value = s.description;
    document.getElementById("modalTitle").innerText = "Edit Status";
    new bootstrap.Modal(document.getElementById("statusModal")).show();
}

function deleteStatus(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
