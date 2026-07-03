<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/batch_nav.php";
$pdo = getDbConnection();

$batch = $_GET["batch"] ?? "";
$message = "";
$msg_type = "success";

// --- Handle Save (Add or Update or Delete) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        $action = $_POST["action"];
        $data = $_POST;
        unset($data["action"]);
        $id = $data["id"] ?? null;
        unset($data["id"]);

        // --- Synchronize Names from IDs (Standard Pattern) ---
        if (!empty($data['pro_id'])) {
            $data['province'] = $pdo->query("SELECT province_name FROM provinces WHERE province_code = " . $pdo->quote($data['pro_id']))->fetchColumn();
        }
        if (!empty($data['dis_id'])) {
            $data['district'] = $pdo->query("SELECT district_name FROM districts WHERE district_code = " . $pdo->quote($data['dis_id']))->fetchColumn();
        }

        if ($action === "update_record") {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = ($v === "" ? null : $v);
            }
            $values[] = $id;
            $stmt = $pdo->prepare("UPDATE import_sez_data SET " . implode(", ", $fields) . " WHERE id = ?");
            $stmt->execute($values);
            $message = "Record updated successfully.";
        } elseif ($action === "add_record") {
            $manualYear = $data["tax_year"] ?? date("Y");
            $new_batch = $batch ?: "MANUAL_ENTRY_SEZDEV_" . $manualYear . "_" . date("YmdHis");
            $data["batch_id"] = $new_batch;
            $data["type"] = 'Developer';
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_sez_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            if (empty($batch)) {
                header("Location: view_sez_dev.php?batch=" . urlencode($new_batch));
                exit;
            }
            $message = "New record added.";
        } elseif ($action === "delete_record") {
            $stmt = $pdo->prepare("DELETE FROM import_sez_data WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Record deleted.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$records = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT * FROM import_sez_data WHERE batch_id = ? AND type = 'Developer' ORDER BY id");
    $stmt->execute([$batch]);
    $records = $stmt->fetchAll();
}

// Fetch years for filter
$years = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT DISTINCT tax_year FROM import_sez_data WHERE batch_id = ? AND type = 'Developer' ORDER BY tax_year DESC");
    $stmt->execute([$batch]);
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch provinces for filter
$provinces = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces ORDER BY province_name")->fetchAll();
$all_districts = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id ORDER BY d.district_name")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_sez_dev.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> SEZ Developer Records</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch ?: 'Manual Entry') ?></code> — <strong><?= count($records) ?></strong> records</p>
    </div>
    <div class="btn-group shadow-sm">
      <?= batchHubBackButton() ?>
      <button class="btn btn-primary" onclick="addRecord()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
      <a href="te_sez_dev.php?batch=<?= urlencode($batch) ?>" class="btn btn-info text-white"><i class="fas fa-calculator me-2"></i> Go to TE Calculation</a>
    </div>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-start border-4 border-<?= $msg_type ?>">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card mb-3 border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Search TIN</label>
        <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Tax Year</label>
        <select id="filterYear" class="form-select">
          <option value="">All Years</option>
          <?php foreach ($years as $y): ?>
          <option value="<?= $y ?>"><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Province</label>
        <select id="filterProvince" class="form-select">
          <option value="">All Provinces</option>
          <?php foreach ($provinces as $p): ?>
          <option value="<?= htmlspecialchars($p["pro_name"]) ?>"><?= htmlspecialchars($p["pro_name"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-outline-secondary w-100" onclick="resetFilters()" title="Reset Filters"><i class="fas fa-undo"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body p-0">
    <table id="mainTable" class="table table-bordered table-hover w-100" style="font-size:0.85em">
      <thead class="table-light text-uppercase small">
        <tr>
          <th>#</th>
          <th>TIN</th>
          <th>Year</th>
          <th>Province</th>
          <th>District</th>
          <th class="text-end">Basic Infra</th>
          <th class="text-end">Other Infra</th>
          <th class="text-end text-primary">TE Amount</th>
          <th>Provision</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $i => $r): ?>
        <?php $is_unmapped = (!empty($r["province"]) && empty($r["pro_id"])); ?>
        <tr class="<?= $is_unmapped ? 'table-warning' : '' ?>">
          <td><?= $i + 1 ?></td>
          <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></small></td>
          <td><?= $r["tax_year"] ?></td>
          <td>
            <?= htmlspecialchars($r["province"] ?? '') ?>
            <?php if ($is_unmapped): ?><i class="fas fa-exclamation-triangle text-danger ms-1" title="Unknown Province"></i><?php endif; ?>
          </td>
          <td>
            <?= htmlspecialchars($r["district"] ?? '') ?>
            <?php if (!empty($r['district']) && empty($r["dis_id"])): ?><i class="fas fa-exclamation-triangle text-danger ms-1" title="Unknown District"></i><?php endif; ?>
          </td>
          <td class="text-end"><?= number_format((float)$r["amount_infra_basic"], 0) ?></td>
          <td class="text-end"><?= number_format((float)$r["amount_infra_other"], 0) ?></td>
          <td class="text-end fw-bold text-primary"><?= number_format((float)$r["te_amount"], 0) ?></td>
          <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r["provision_number"] ?: '-') ?></span></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick='editRecord(<?= (int)$r["id"] ?>)'>
              <i class="fas fa-edit"></i>
            </button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
              <input type="hidden" name="action" value="delete_record">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Manual Entry Modal (Year Picker) -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manual Data Entry for SEZ Developers</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Select the Tax Year for the manual records you want to manage.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Tax Year</label>
          <select id="manualTaxYear" class="form-select">
            <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-info text-white" onclick="goToManualEntry()">Manage Records</button>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="modalTitle">SEZ Developer Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="modalAction" value="update_record">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">TIN</label>
              <input type="text" name="tin" id="edit_tin" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Tax Year</label>
              <input type="number" name="tax_year" id="edit_tax_year" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold small">Province</label>
                <select name="pro_id" id="edit_pro_id" class="form-select" onchange="filterDistricts()">
                    <option value="">-- Select Province --</option>
                    <?php foreach ($provinces as $p): ?>
                    <option value="<?= htmlspecialchars($p["pro_id"]) ?>"><?= htmlspecialchars($p["pro_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold small">District</label>
                <select name="dis_id" id="edit_dis_id" class="form-select">
                    <option value="">-- Select District --</option>
                </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold small">Investment License Date</label>
              <input type="date" name="license_date" id="edit_license_date" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold small">Provision Number</label>
              <input type="text" name="provision_number" id="edit_provision_number" class="form-control">
            </div>
          </div>

          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2">Infrastructure Investment (LAK)</div>
            <div class="card-body row">
              <div class="col-md-6 mb-2">
                <label class="form-label small text-muted">Basic Infrastructure (Road/Elec/Water)</label>
                <input type="number" step="0.01" name="amount_infra_basic" id="edit_amount_infra_basic" class="form-control">
              </div>
              <div class="col-md-6 mb-2">
                <label class="form-label small text-muted">Other Infrastructure</label>
                <input type="number" step="0.01" name="amount_infra_other" id="edit_amount_infra_other" class="form-control">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const sezData = <?= json_encode(array_column($records, null, 'id')) ?>;
const allDistricts = <?= json_encode($all_districts) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const table = $('#mainTable').DataTable({
        "dom": 'rtip',
        "pageLength": 25,
        "ordering": true,
        "order": [[0, "asc"]]
    });

    document.getElementById('customSearch').addEventListener('keyup', function() {
        table.search(this.value).draw();
    });

    document.getElementById('filterYear').addEventListener('change', function() {
        table.column(2).search(this.value).draw();
    });

    document.getElementById('filterProvince').addEventListener('change', function() {
        table.column(3).search(this.value).draw();
    });

    // auto_add support
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_add') === '1') {
        const manualYear = urlParams.get('year');
        addRecord(manualYear);
    }
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterYear').value = '';
    document.getElementById('filterProvince').value = '';
    $('#mainTable').DataTable().search('').columns().search('').draw();
}

function filterDistricts(selectedDistrictId = "") {
    const provinceId = document.getElementById('edit_pro_id').value;
    const districtSelect = document.getElementById('edit_dis_id');
    districtSelect.innerHTML = '<option value="">-- Select District --</option>';

    if (provinceId) {
        const filtered = allDistricts.filter(d => String(d.pro_id) === String(provinceId));
        filtered.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.dis_id;
            opt.textContent = d.dis_name;
            if (String(d.dis_id) === String(selectedDistrictId)) opt.selected = true;
            districtSelect.appendChild(opt);
        });
    }
}

function editRecord(id) {
    const r = sezData[id];
    if (!r) return;

    document.getElementById('modalAction').value = 'update_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tin').value = r.tin || '';
    document.getElementById('edit_tax_year').value = r.tax_year || '';
    document.getElementById('edit_license_date').value = r.license_date || '';
    document.getElementById('edit_pro_id').value = r.pro_id || '';
    filterDistricts(r.dis_id || '');
    document.getElementById('edit_amount_infra_basic').value = r.amount_infra_basic || 0;
    document.getElementById('edit_amount_infra_other').value = r.amount_infra_other || 0;
    document.getElementById('edit_provision_number').value = r.provision_number || '';

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    window.location.href = `view_sez_dev.php?batch=MANUAL_ENTRY_SEZDEV_${year}_${stamp}&auto_add=1&year=${year}`;
}

function addRecord(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Record';
    document.getElementById('edit_id').value = '';
    const form = document.querySelector('#editModal form');
    form.reset();
    document.getElementById('edit_dis_id').innerHTML = '<option value="">-- Select District --</option>';
    if (prefilledYear) {
        document.getElementById('edit_tax_year').value = prefilledYear;
    }
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
