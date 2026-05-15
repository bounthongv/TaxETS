<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_village") {
                $stmt = $pdo->prepare("INSERT INTO villages (village_code, village_name, village_name_lao, district_code, active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["village_code"],
                    $_POST["village_name"],
                    $_POST["village_name_lao"] ?? null,
                    $_POST["district_code"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Village added.";
            } elseif ($_POST["action"] === "edit_village") {
                $stmt = $pdo->prepare("UPDATE villages SET village_code = ?, village_name = ?, village_name_lao = ?, district_code = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["village_code"],
                    $_POST["village_name"],
                    $_POST["village_name_lao"] ?? null,
                    $_POST["district_code"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Village updated.";
            } elseif ($_POST["action"] === "delete_village") {
                $pdo->prepare("DELETE FROM villages WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Village deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$province_filter = $_GET["province_filter"] ?? "";
$district_filter = $_GET["district_filter"] ?? "";

$where = "1=1";
$params = [];
if ($province_filter) {
    $where .= " AND d.province_id = ?";
    $params[] = (int)$province_filter;
}
if ($district_filter) {
    $where .= " AND v.district_code = ?";
    $params[] = $district_filter;
}

$sql = "SELECT v.*, d.district_name, p.province_name, p.province_name_lao 
    FROM villages v 
    LEFT JOIN districts d ON v.district_code = d.district_code 
    LEFT JOIN provinces p ON d.province_id = p.id 
    WHERE $where 
    ORDER BY v.village_code";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$villages = $stmt->fetchAll();

$provinces = $pdo->query("SELECT id, province_name, province_name_lao FROM provinces WHERE active = 1 ORDER BY province_name")->fetchAll();
$districts = $pdo->query("SELECT district_code, district_name, province_id FROM districts WHERE active = 1 ORDER BY district_code")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-home me-2"></i> Villages</h2>
      <p class="text-muted">Part of the Data Dictionary. Manage administrative villages.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#villageModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add Village
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
        <label class="small text-muted">Province:</label>
      </div>
      <div class="col-auto">
        <select name="province_filter" id="provinceFilter" class="form-select form-select-sm" onchange="loadDistricts()">
          <option value="">All Provinces</option>
          <?php foreach ($provinces as $p): ?>
          <option value="<?= $p["id"] ?>" <?= $province_filter == $p["id"] ? "selected" : "" ?>><?= htmlspecialchars($p["province_name"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="small text-muted">District:</label>
      </div>
      <div class="col-auto">
        <select name="district_filter" id="districtFilter" class="form-select form-select-sm">
          <option value="">All Districts</option>
          <?php foreach ($districts as $d): ?>
          <option value="<?= $d["district_code"] ?>" <?= $district_filter == $d["district_code"] ? "selected" : "" ?> data-province="<?= $d["province_id"] ?>"><?= htmlspecialchars($d["district_name"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
        <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<?php if (!$province_filter && !$district_filter): ?>
<?php
    $total_villages = $pdo->query("SELECT COUNT(*) FROM villages")->fetchColumn();
    $total_districts = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
    $total_provinces = $pdo->query("SELECT COUNT(*) FROM provinces")->fetchColumn();
?>
<div class="row mb-3">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-primary"><?= $total_provinces ?></div>
        <small class="text-muted">Provinces</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-info"><?= $total_districts ?></div>
        <small class="text-muted">Districts</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="h3 text-success"><?= $total_villages ?></div>
        <small class="text-muted">Villages</small>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small"><?= count($villages) ?> villages</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Code</th>
            <th>Village Name</th>
            <th> Lao Name</th>
            <th>District</th>
            <th>Province</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($villages as $v): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= htmlspecialchars($v["village_code"]) ?></td>
            <td><?= htmlspecialchars($v["village_name"]) ?></td>
            <td class="text-muted"><?= htmlspecialchars($v["village_name_lao"] ?? "-") ?></td>
            <td><?= htmlspecialchars($v["district_name"] ?? "-") ?></td>
            <td><?= htmlspecialchars($v["province_name"] ?? "-") ?></td>
            <td><?= $v["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editVillage(<?= json_encode($v) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteVillage(<?= $v["id"] ?>, '<?= htmlspecialchars($v['village_name']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($villages)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No villages found. Click "Add Village" to add.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="villageModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Village</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_village">
          <input type="hidden" name="id" id="villageId">
          <div class="mb-3">
            <label class="form-label">Village Code *</label>
            <input type="text" name="village_code" id="villageCode" class="form-control" required>
            <small class="text-muted">7-digit code (e.g., 0101001)</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Village Name *</label>
            <input type="text" name="village_name" id="villageName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Village Name (Lao)</label>
            <input type="text" name="village_name_lao" id="villageNameLao" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">District</label>
            <select name="district_code" id="villageDistrict" class="form-select">
              <option value="">-- Select District --</option>
              <?php foreach ($districts as $d): ?>
              <option value="<?= $d["district_code"] ?>" data-province="<?= $d["province_id"] ?>"><?= htmlspecialchars($d["district_name"]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="villageActive" class="form-check-input" checked>
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
        <input type="hidden" name="action" value="delete_village">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Delete Village</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Delete village <strong id="deleteName"></strong>?</p>
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
    document.getElementById("formAction").value = "add_village";
    document.getElementById("modalTitle").innerText = "Add Village";
    document.getElementById("villageId").value = "";
    document.getElementById("villageCode").value = "";
    document.getElementById("villageName").value = "";
    document.getElementById("villageNameLao").value = "";
    document.getElementById("villageDistrict").value = "";
    document.getElementById("villageActive").checked = true;
}

function editVillage(v) {
    document.getElementById("formAction").value = "edit_village";
    document.getElementById("modalTitle").innerText = "Edit Village";
    document.getElementById("villageId").value = v.id;
    document.getElementById("villageCode").value = v.village_code;
    document.getElementById("villageName").value = v.village_name;
    document.getElementById("villageNameLao").value = v.village_name_lao || "";
    document.getElementById("villageDistrict").value = v.district_code || "";
    document.getElementById("villageActive").checked = v.active == 1;
    new bootstrap.Modal(document.getElementById("villageModal")).show();
}

function deleteVillage(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}

function loadDistricts() {
    var provinceId = document.getElementById("provinceFilter").value;
    var districtSelect = document.getElementById("districtFilter");
    var options = districtSelect.querySelectorAll("option");
    
    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
        if (opt.value === "") {
            opt.style.display = "";
        } else {
            opt.style.display = (provinceId === "" || opt.getAttribute("data-province") === provinceId) ? "" : "none";
        }
    }
    if (provinceId) {
        districtSelect.value = "";
    }
}

// Run on page load
document.addEventListener("DOMContentLoaded", function() {
    loadDistricts();
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>