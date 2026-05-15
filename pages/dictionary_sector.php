<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_sector") {
                $stmt = $pdo->prepare("INSERT INTO business_sectors (sector_name, active) VALUES (?, ?)");
                $stmt->execute([
                    $_POST["sector_name"],
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Business sector added.";
            } elseif ($_POST["action"] === "edit_sector") {
                $stmt = $pdo->prepare("UPDATE business_sectors SET sector_name = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["sector_name"],
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Business sector updated.";
            } elseif ($_POST["action"] === "delete_sector") {
                $pdo->prepare("DELETE FROM business_sectors WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Business sector deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$sectors = $pdo->query("SELECT * FROM business_sectors ORDER BY sector_name")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-industry me-2"></i> Business Sectors</h2>
      <p class="text-muted">Manage the broad industry sectors used across MPI, SEZO, and other modules.</p>
    </div>
    <div>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#sectorModal" onclick="clearForm()">
        <i class="fas fa-plus me-1"></i> Add Business Sector
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
            <th>Sector Name</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($sectors as $s): ?>
          <tr>
            <td class="ps-4 fw-bold text-muted"><?= $s["id"] ?></td>
            <td><?= htmlspecialchars($s["sector_name"]) ?></td>
            <td><?= $s["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editSector(<?= json_encode($s) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteSector(<?= $s["id"] ?>, '<?= htmlspecialchars($s['sector_name']) ?>')">
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
<div class="modal fade" id="sectorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Business Sector</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_sector">
          <input type="hidden" name="id" id="sectorId">
          <div class="mb-3">
            <label class="form-label">Sector Name *</label>
            <input type="text" name="sector_name" id="sectorName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="sectorActive" class="form-check-input" checked>
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
        <input type="hidden" name="action" value="delete_sector">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Delete Confirmation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Delete business sector <strong id="deleteName"></strong>?</p>
          <p class="small text-muted">Records linked to this sector will have their sector reference cleared.</p>
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
    document.getElementById("formAction").value = "add_sector";
    document.getElementById("modalTitle").innerText = "Add Business Sector";
    document.getElementById("sectorId").value = "";
    document.getElementById("sectorName").value = "";
    document.getElementById("sectorActive").checked = true;
}

function editSector(s) {
    document.getElementById("formAction").value = "edit_sector";
    document.getElementById("modalTitle").innerText = "Edit Business Sector";
    document.getElementById("sectorId").value = s.id;
    document.getElementById("sectorName").value = s.sector_name;
    document.getElementById("sectorActive").checked = s.active == 1;
    new bootstrap.Modal(document.getElementById("sectorModal")).show();
}

function deleteSector(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
