<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_province") {
                $stmt = $pdo->prepare("INSERT INTO provinces (province_code, province_name, region, active) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["province_code"],
                    $_POST["province_name"],
                    $_POST["region"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Province added.";
            } elseif ($_POST["action"] === "edit_province") {
                $stmt = $pdo->prepare("UPDATE provinces SET province_code = ?, province_name = ?, region = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["province_code"],
                    $_POST["province_name"],
                    $_POST["region"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Province updated.";
            } elseif ($_POST["action"] === "delete_province") {
                $pdo->prepare("DELETE FROM provinces WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Province deleted.";
            } elseif ($_POST["action"] === "import_excel") {
                if (!empty($_FILES["excel_file"]["name"])) {
                    $ext = strtolower(pathinfo($_FILES["excel_file"]["name"], PATHINFO_EXTENSION));
                    if (!in_array($ext, ["xlsx", "xls"])) {
                        $message = "Please select Excel file (.xlsx or .xls)";
                        $msg_type = "danger";
                    } else {
                        $inputFile = $_FILES["excel_file"]["tmp_name"];
                        $spreadsheet = IOFactory::load($inputFile);
                        $sheet = $spreadsheet->getActiveSheet();
                        $row_count = 0;
                        
                        foreach ($sheet->toArray() as $row) {
                            if ($row_count === 0) { $row_count++; continue; }
                            if (empty($row[0])) continue;
                            
                            $code = trim($row[0]);
                            $name = trim($row[1] ?? "");
                            $region = trim($row[2] ?? "");
                            
                            $stmt = $pdo->prepare("INSERT INTO provinces (province_code, province_name, region, active) VALUES (?, ?, ?, 1) 
                                ON DUPLICATE KEY UPDATE province_name = VALUES(province_name), region = VALUES(region)");
                            $stmt->execute([$code, $name, $region]);
                            $row_count++;
                        }
                        $message = "Imported $row_count provinces.";
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$provinces = $pdo->query("SELECT * FROM provinces ORDER BY province_code")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-map-marked-alt me-2"></i> Provinces</h2>
      <p class="text-muted">Part of the Data Dictionary. Manage administrative regions.</p>
    </div>
    <div>
      <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="fas fa-file-import me-1"></i> Import Excel
      </button>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#provinceModal" onclick="clearForm()">
        <i class="fas fa-plus me-1"></i> Add Province
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
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small"><?= count($provinces) ?> provinces</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Code</th>
            <th>Province Name</th>
            <th>Region</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($provinces as $p): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= htmlspecialchars($p["province_code"]) ?></td>
            <td><?= htmlspecialchars($p["province_name"]) ?></td>
            <td><?= htmlspecialchars($p["region"] ?? "-") ?></td>
            <td><?= $p["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editProvince(<?= json_encode($p) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteProvince(<?= $p["id"] ?>, '<?= htmlspecialchars($p['province_name']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($provinces)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No provinces. Click "Import Excel" to add.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="provinceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Province</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_province">
          <input type="hidden" name="id" id="provinceId">
          <div class="mb-3">
            <label class="form-label">Province Code *</label>
            <input type="text" name="province_code" id="provinceCode" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Province Name *</label>
            <input type="text" name="province_name" id="provinceName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Region</label>
            <input type="text" name="region" id="provinceRegion" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" id="provinceActive" class="form-check-input" checked>
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Import Provinces from Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="import_excel">
          <div class="mb-3">
            <label class="form-label">Select Excel File</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <small class="text-muted">Columns: Code, Province Name, Region (optional)</small>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="delete_province">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Delete Province</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Delete province <strong id="deleteName"></strong>?</p>
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
    document.getElementById("formAction").value = "add_province";
    document.getElementById("modalTitle").innerText = "Add Province";
    document.getElementById("provinceId").value = "";
    document.getElementById("provinceCode").value = "";
    document.getElementById("provinceName").value = "";
    document.getElementById("provinceRegion").value = "";
    document.getElementById("provinceActive").checked = true;
}

function editProvince(p) {
    document.getElementById("formAction").value = "edit_province";
    document.getElementById("modalTitle").innerText = "Edit Province";
    document.getElementById("provinceId").value = p.id;
    document.getElementById("provinceCode").value = p.province_code;
    document.getElementById("provinceName").value = p.province_name;
    document.getElementById("provinceRegion").value = p.region || "";
    document.getElementById("provinceActive").checked = p.active == 1;
    new bootstrap.Modal(document.getElementById("provinceModal")).show();
}

function deleteProvince(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").innerText = name;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>