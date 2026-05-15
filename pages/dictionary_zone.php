<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_zone") {
                $stmt = $pdo->prepare("INSERT INTO districts (district_code, district_name, province_id, zone, active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["district_code"],
                    $_POST["district_name"],
                    $_POST["province_id"] ?: null,
                    $_POST["zone"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Zone area added.";
            } elseif ($_POST["action"] === "edit_zone") {
                $stmt = $pdo->prepare("UPDATE districts SET district_code = ?, district_name = ?, province_id = ?, zone = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["district_code"],
                    $_POST["district_name"],
                    $_POST["province_id"] ?: null,
                    $_POST["zone"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Zone area updated.";
            } elseif ($_POST["action"] === "delete_zone") {
                $pdo->prepare("DELETE FROM districts WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Zone area deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$zone_filter = $_GET["zone_filter"] ?? "";
$province_filter = $_GET["province_filter"] ?? "";

$where = "1=1";
if ($zone_filter == "1") {
    $where .= " AND d.zone = 1";
} elseif ($zone_filter == "2") {
    $where .= " AND d.zone = 2";
} elseif ($zone_filter == "0") {
    $where .= " AND (d.zone IS NULL OR d.zone = 0)";
}
if ($province_filter) {
    $where .= " AND d.province_id = " . (int)$province_filter;
}

$districts = $pdo->query("SELECT d.*, p.province_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id WHERE $where ORDER BY d.zone, p.province_name, d.district_name")->fetchAll();
$provinces = $pdo->query("SELECT id, province_name FROM provinces WHERE active = 1 ORDER BY province_name")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-map-marked-alt me-2"></i> Investment Zones</h2>
      <p class="text-muted">Part of the Data Dictionary. Manage Special Economic Zones and Investment Promotion Zones.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#zoneModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add Zone Area
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

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-auto">
        <label class="small text-muted">Zone Type:</label>
      </div>
      <div class="col-auto">
        <select name="zone_filter" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="" <?= $zone_filter == "" ? "selected" : "" ?>>All Districts</option>
          <option value="1" <?= $zone_filter == "1" ? "selected" : "" ?>>Zone 1 (SEZ)</option>
          <option value="2" <?= $zone_filter == "2" ? "selected" : "" ?>>Zone 2 (Promotion)</option>
          <option value="0" <?= $zone_filter == "0" ? "selected" : "" ?>>No Zone</option>
        </select>
      </div>
      <div class="col-auto">
        <label class="small text-muted">Province:</label>
      </div>
      <div class="col-auto">
        <select name="province_filter" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Provinces</option>
          <?php foreach ($provinces as $p): ?>
          <option value="<?= $p["id"] ?>" <?= $province_filter == $p["id"] ? "selected" : "" ?>><?= htmlspecialchars($p["province_name"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<?php if (!$zone_filter): ?>
<?php
    $zone1_count = $pdo->query("SELECT COUNT(*) FROM districts WHERE zone = 1")->fetchColumn();
    $zone2_count = $pdo->query("SELECT COUNT(*) FROM districts WHERE zone = 2")->fetchColumn();
?>
<div class="row mb-3">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-primary"><?= $zone1_count ?></div>
        <small class="text-muted">Zone 1 (SEZ) Districts</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-info"><?= $zone2_count ?></div>
        <small class="text-muted">Zone 2 (Promotion) Districts</small>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small"><?= count($districts) ?> zone areas</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">District</th>
            <th>Province</th>
            <th>Zone</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($districts as $d): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= htmlspecialchars($d["district_name"]) ?></td>
            <td><?= htmlspecialchars($d["province_name"] ?? "-") ?></td>
            <td>
              <?php if ($d["zone"] == 1): ?><span class="badge bg-primary">Zone 1 (SEZ)</span>
              <?php elseif ($d["zone"] == 2): ?><span class="badge bg-info">Zone 2 (Promotion)</span>
              <?php else: ?><span class="text-muted">-</span><?php endif; ?>
            </td>
            <td><?= $d["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editZone(<?= json_encode($d) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteZone(<?= $d["id"] ?>, '<?= htmlspecialchars($d['district_name']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($districts)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No zone areas. Click "Add Zone Area" to mark districts as zone.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="zoneModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Zone Area</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_zone">
          <input type="hidden" name="id" id="zoneId">
          <div class="mb-3">
            <label class="form-label">Select District *</label>
            <select name="district_code" id="zoneDistrict" class="form-select" required>
              <option value="">-- Select District --</option>
              <?php 
              $all_districts = $pdo->query("SELECT d.id, d.district_name, p.province_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id ORDER BY p.province_name, d.district_name")->fetchAll();
              foreach ($all_districts as $d): 
              ?>
              <option value="<?= $d["id"] ?>"><?= htmlspecialchars($d["district_name"] . " - " . ($d["province_name"] ?? "")) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Zone Type *</label>
            <select name="zone" id="zoneType" class="form-select" required>
              <option value="">-- Select Zone --</option>
              <option value="1">Zone 1 (Special Economic Zone)</option>
              <option value="2">Zone 2 (Investment Promotion Zone)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="zoneActive" class="form-check-input" checked>
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
        <input type="hidden" name="action" value="delete_zone">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Delete Zone Area</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Remove zone designation from <strong id="deleteName"></strong>?</p>
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
    document.getElementById("formAction").value = "add_zone";
    document.getElementById("modalTitle").innerText = "Add Zone Area";
    document.getElementById("zoneId").value = "";
    document.getElementById("zoneDistrict").value = "";
    document.getElementById("zoneType").value = "";
    document.getElementById("zoneActive").checked = true;
}

function editZone(d) {
    document.getElementById("formAction").value = "edit_zone";
    document.getElementById("modalTitle").innerText = "Edit Zone Area";
    document.getElementById("zoneId").value = d.id;
    // Find district option by district_name
    var select = document.getElementById("zoneDistrict");
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].text.includes(d.district_name)) {
            select.selectedIndex = i;
            break;
        }
    }
    document.getElementById("zoneType").value = d.zone || "";
    document.getElementById("zoneActive").checked = d.active == 1;
    new bootstrap.Modal(document.getElementById("zoneModal")).show();
}

function deleteZone(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>