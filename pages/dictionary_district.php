<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_district") {
                $stmt = $pdo->prepare("INSERT INTO districts (district_code, district_name, province_id, active) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["district_code"],
                    $_POST["district_name"],
                    $_POST["province_id"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "District added.";
            } elseif ($_POST["action"] === "edit_district") {
                $stmt = $pdo->prepare("UPDATE districts SET district_code = ?, district_name = ?, province_id = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["district_code"],
                    $_POST["district_name"],
                    $_POST["province_id"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "District updated.";
            } elseif ($_POST["action"] === "delete_district") {
                $pdo->prepare("DELETE FROM districts WHERE id = ?")->execute([$_POST["id"]]);
                $message = "District deleted.";
            } elseif ($_POST["action"] === "import_district") {
                if (!empty($_FILES["excel_file"]["name"])) {
                    $ext = strtolower(pathinfo($_FILES["excel_file"]["name"], PATHINFO_EXTENSION));
                    if (!in_array($ext, ["xlsx", "xls"])) {
                        $message = "Please select Excel file (.xlsx or .xls)";
                        $msg_type = "danger";
                    } else {
                        require_once __DIR__ . "/../vendor/autoload.php";
                        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES["excel_file"]["tmp_name"]);
                        $sheet = $spreadsheet->getActiveSheet();
                        $row_count = 0;
                        
                        foreach ($sheet->toArray() as $row) {
                            if ($row_count === 0) { $row_count++; continue; }
                            if (empty($row[0])) continue;
                            
                            $code = trim($row[0]);
                            $name = trim($row[1] ?? "");
                            $province_name = trim($row[2] ?? "");
                            
                            $province_id = null;
                            if ($province_name) {
                                $p = $pdo->prepare("SELECT id FROM provinces WHERE province_name = ?");
                                $p->execute([$province_name]);
                                $prov = $p->fetch();
                                $province_id = $prov["id"] ?? null;
                            }
                            
                            $stmt = $pdo->prepare("INSERT INTO districts (district_code, district_name, province_id, active) VALUES (?, ?, ?, 1) 
                                ON DUPLICATE KEY UPDATE district_name = VALUES(district_name), province_id = VALUES(province_id)");
                            $stmt->execute([$code, $name, $province_id]);
                            $row_count++;
                        }
                        $message = "Imported $row_count districts.";
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$province_filter = $_GET["province_filter"] ?? "";

$where = "1=1";
if ($province_filter) {
    $where .= " AND d.province_id = " . (int)$province_filter;
}

$districts = $pdo->query("SELECT d.*, p.province_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id WHERE $where ORDER BY d.district_code")->fetchAll();
$provinces = $pdo->query("SELECT id, province_name FROM provinces WHERE active = 1 ORDER BY province_name")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-map me-2"></i> Districts</h2>
      <p class="text-muted">Part of the Data Dictionary. Manage administrative districts.</p>
    </div>
    <div>
      <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="fas fa-file-import me-1"></i> Import Excel
      </button>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#districtModal" onclick="clearForm()">
        <i class="fas fa-plus me-1"></i> Add District
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

<!-- Filter by Province -->
<div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-auto">
        <label class="small text-muted">Filter by Province:</label>
      </div>
      <div class="col-auto">
        <select name="province_filter" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Provinces</option>
          <?php foreach ($provinces as $p): ?>
          <option value="<?= $p["id"] ?>" <?= $province_filter == $p["id"] ? "selected" : "" ?>><?= htmlspecialchars($p["province_name"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small"><?= count($districts) ?> districts</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Code</th>
            <th>District Name</th>
            <th>Province</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($districts as $d): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= htmlspecialchars($d["district_code"]) ?></td>
            <td><?= htmlspecialchars($d["district_name"]) ?></td>
            <td><?= htmlspecialchars($d["province_name"] ?? "-") ?></td>
            <td><?= $d["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editDistrict(<?= json_encode($d) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteDistrict(<?= $d["id"] ?>, '<?= htmlspecialchars($d['district_name']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($districts)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No districts. Click "Add District" to add.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="districtModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add District</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_district">
          <input type="hidden" name="id" id="districtId">
          <div class="mb-3">
            <label class="form-label">District Code *</label>
            <input type="text" name="district_code" id="districtCode" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">District Name *</label>
            <input type="text" name="district_name" id="districtName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Province</label>
            <select name="province_id" id="districtProvince" class="form-select">
              <option value="">-- Select Province --</option>
              <?php foreach ($provinces as $p): ?>
              <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["province_name"]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="districtActive" class="form-check-input" checked>
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
        <input type="hidden" name="action" value="delete_district">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Delete District</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Delete district <strong id="deleteName"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Import Districts from Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="import_district">
          <div class="mb-3">
            <label class="form-label">Select Excel File</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <small class="text-muted">Columns: Code, District Name, Province Name (optional)</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById("formAction").value = "add_district";
    document.getElementById("modalTitle").innerText = "Add District";
    document.getElementById("districtId").value = "";
    document.getElementById("districtCode").value = "";
    document.getElementById("districtName").value = "";
    document.getElementById("districtProvince").value = "";
    document.getElementById("districtActive").checked = true;
}

function editDistrict(d) {
    document.getElementById("formAction").value = "edit_district";
    document.getElementById("modalTitle").innerText = "Edit District";
    document.getElementById("districtId").value = d.id;
    document.getElementById("districtCode").value = d.district_code;
    document.getElementById("districtName").value = d.district_name;
    document.getElementById("districtProvince").value = d.province_id || "";
    document.getElementById("districtActive").checked = d.active == 1;
    new bootstrap.Modal(document.getElementById("districtModal")).show();
}

function deleteDistrict(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>