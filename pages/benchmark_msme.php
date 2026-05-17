<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "save_msme") {
                $id = $_POST["id"] ?? "";
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE bm_msme_definition SET effective_date_from = ?, effective_date_to = ?, sector_id = ?, legacy_item_id = ?, item_name = ?, micro_value = ?, small_value = ?, medium_value = ?, active = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST["effective_date_from"],
                        $_POST["effective_date_to"] ?: null,
                        $_POST["sector_id"] ?: null,
                        $_POST["legacy_item_id"] ?: null,
                        $_POST["item_name"],
                        $_POST["micro_value"],
                        $_POST["small_value"],
                        $_POST["medium_value"],
                        isset($_POST["active"]) ? 1 : 0,
                        $id
                    ]);
                    $message = "MSME definition updated.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO bm_msme_definition (effective_date_from, effective_date_to, sector_id, legacy_item_id, item_name, micro_value, small_value, medium_value, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST["effective_date_from"],
                        $_POST["effective_date_to"] ?: null,
                        $_POST["sector_id"] ?: null,
                        $_POST["legacy_item_id"] ?: null,
                        $_POST["item_name"],
                        $_POST["micro_value"],
                        $_POST["small_value"],
                        $_POST["medium_value"],
                        isset($_POST["active"]) ? 1 : 0
                    ]);
                    $message = "MSME definition added.";
                }
            } elseif ($_POST["action"] === "delete_msme") {
                $pdo->prepare("DELETE FROM bm_msme_definition WHERE id = ?")->execute([$_POST["id"]]);
                $message = "MSME definition deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch records with sector name
$records = $pdo->query("
    SELECT m.*, s.sector_name 
    FROM bm_msme_definition m 
    LEFT JOIN business_sectors s ON m.sector_id = s.id 
    ORDER BY m.effective_date_from DESC, s.sector_name, m.item_name
")->fetchAll();

// Fetch sectors for dropdown
$sectors = $pdo->query("SELECT id, sector_name FROM business_sectors ORDER BY sector_name")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-building me-2"></i> MSME Definition</h2>
      <p class="text-muted">Relocated to Benchmark category. Defined criteria for Micro, Small, and Medium Enterprises.</p>
    </div>
    <div>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#msmeModal" onclick="clearForm()">
        <i class="fas fa-plus me-1"></i> Add MSME Definition
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
            <th class="ps-4">Effective From</th>
            <th>Sector</th>
            <th>Item Name</th>
            <th>Micro</th>
            <th>Small</th>
            <th>Medium</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($records as $r): ?>
          <tr>
            <td class="ps-4">
              <span class="fw-bold"><?= htmlspecialchars($r["effective_date_from"]) ?></span>
              <?php if ($r["effective_date_to"]): ?>
                <br><small class="text-muted">to <?= htmlspecialchars($r["effective_date_to"]) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-<?= $r['sector_id'] ? 'info' : 'secondary' ?>">
                <?= htmlspecialchars($r["sector_name"] ?? "All Sectors") ?>
              </span>
            </td>
            <td><?= htmlspecialchars($r["item_name"]) ?></td>
            <td><?= htmlspecialchars($r["micro_value"]) ?></td>
            <td><?= htmlspecialchars($r["small_value"]) ?></td>
            <td><?= htmlspecialchars($r["medium_value"]) ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editMsme(<?= json_encode($r) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteMsme(<?= $r["id"] ?>, '<?= htmlspecialchars($r['item_name']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($records)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No MSME definitions. Click "Add MSME Definition" to start.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="msmeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add MSME Definition</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="save_msme">
          <input type="hidden" name="id" id="msmeId">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Effective Date From *</label>
              <input type="date" name="effective_date_from" id="effectiveFrom" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Effective Date To</label>
              <input type="date" name="effective_date_to" id="effectiveTo" class="form-control">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Business Sector</label>
              <select name="sector_id" id="sectorId" class="form-select">
                <option value="">All Sectors</option>
                <?php foreach ($sectors as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['sector_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Item Name *</label>
              <select name="item_name" id="itemName" class="form-select" required>
                <option value="">Select Item...</option>
                <option value="Annual average number of employees">Annual average number of employees</option>
                <option value="Annual turnover (KN)">Annual turnover (KN)</option>
                <option value="Total Assets (KN)">Total Assets (KN)</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label text-muted small uppercase">Legacy ID (Optional)</label>
              <input type="text" name="legacy_item_id" id="legacyItemId" class="form-control form-control-sm">
            </div>
          </div>

          <hr>
          <h6 class="mb-3">Criteria Values</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label text-success">Micro *</label>
              <input type="text" name="micro_value" id="microValue" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label text-primary">Small *</label>
              <input type="text" name="small_value" id="smallValue" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label text-warning">Medium *</label>
              <input type="text" name="medium_value" id="mediumValue" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" name="active" id="msmeActive" class="form-check-input" checked>
              <label class="form-check-label">Active</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Definition</button>
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
        <input type="hidden" name="action" value="delete_msme">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Delete Confirmation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the MSME definition for <strong id="deleteName"></strong>?</p>
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
    document.getElementById("msmeId").value = "";
    document.getElementById("modalTitle").innerText = "Add MSME Definition";
    document.getElementById("effectiveFrom").value = "";
    document.getElementById("effectiveTo").value = "";
    document.getElementById("sectorId").value = "";
    document.getElementById("itemName").value = "";
    document.getElementById("legacyItemId").value = "";
    document.getElementById("microValue").value = "";
    document.getElementById("smallValue").value = "";
    document.getElementById("mediumValue").value = "";
    document.getElementById("msmeActive").checked = true;
}

function editMsme(r) {
    document.getElementById("msmeId").value = r.id;
    document.getElementById("modalTitle").innerText = "Edit MSME Definition";
    document.getElementById("effectiveFrom").value = r.effective_date_from;
    document.getElementById("effectiveTo").value = r.effective_date_to || "";
    document.getElementById("sectorId").value = r.sector_id || "";
    document.getElementById("itemName").value = r.item_name;
    document.getElementById("legacyItemId").value = r.legacy_item_id || "";
    document.getElementById("microValue").value = r.micro_value;
    document.getElementById("smallValue").value = r.small_value;
    document.getElementById("mediumValue").value = r.medium_value;
    document.getElementById("msmeActive").checked = r.active == 1;
    new bootstrap.Modal(document.getElementById("msmeModal")).show();
}

function deleteMsme(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
