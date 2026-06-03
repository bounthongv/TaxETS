<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

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

        // Synchronize names from IDs
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
            $stmt = $pdo->prepare("UPDATE import_vat_data SET " . implode(", ", $fields) . " WHERE id = ?");
            $stmt->execute($values);
            $message = "Record updated successfully.";
        } elseif ($action === "add_record") {
            $new_batch = $batch ?: "MANUAL_ENTRY_VAT_" . date("Y");
            $data["batch_id"] = $new_batch;
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_vat_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            if (empty($batch)) {
                header("Location: view_vat.php?batch=" . urlencode($new_batch));
                exit;
            }
            $message = "New record added.";
        } elseif ($action === "delete_record") {
            $stmt = $pdo->prepare("DELETE FROM import_vat_data WHERE id = ?");
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
    $stmt = $pdo->prepare("SELECT * FROM import_vat_data WHERE batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $records = $stmt->fetchAll();
}

// Fetch periods for filter
$periods = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(filing_period, '%Y-%m') as period FROM import_vat_data WHERE batch_id = ? AND filing_period IS NOT NULL AND filing_period != '0000-00-00' ORDER BY period DESC");
    $stmt->execute([$batch]);
    $periods = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch provinces for filter
$provinces = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces ORDER BY province_name")->fetchAll();
$all_districts = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id ORDER BY d.district_name")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_vat.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Domestic VAT Records</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch ?: 'Manual Entry') ?></code> — <strong><?= count($records) ?></strong> records</p>
    </div>
    <div class="btn-group shadow-sm">
      <button class="btn btn-primary" onclick="addRecord()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
      <a href="te_vat.php?batch=<?= urlencode($batch) ?>" class="btn btn-success"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
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
      <div class="col-md-4">
        <label class="form-label small fw-bold text-muted">Search TIN / Name</label>
        <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
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
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Period (Month/Year)</label>
        <select id="filterPeriod" class="form-select">
          <option value="">All Periods</option>
          <?php foreach ($periods as $p): ?>
          <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
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
          <th>Name</th>
          <th>Province</th>
          <th>Period</th>
          <th class="text-end">Exempt Sales</th>
          <th>Provision</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $i => $r): ?>
        <?php
          $is_unmapped = (!empty($r["province"]) && empty($r["pro_id"]));
          $period_display = (!empty($r["filing_period"]) && $r["filing_period"] != '0000-00-00') ? date("M Y", strtotime($r["filing_period"])) : '-';
        ?>
        <tr class="<?= $is_unmapped ? 'table-warning' : '' ?>">
          <td><?= $i + 1 ?></td>
          <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></small></td>
          <td><?= htmlspecialchars($r["name"]) ?></td>
          <td>
            <?= htmlspecialchars($r["province"] ?? '') ?>
            <?php if (!empty($r["province"]) && empty($r["pro_id"])): ?>
                <i class="fas fa-exclamation-triangle text-danger ms-1" title="Unknown Province"></i>
            <?php endif; ?>
          </td>
          <td><?= $period_display ?></td>
          <td class="text-end"><?= number_format((float)($r["sales_exempt"] ?? 0), 0) ?></td>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="modalTitle">VAT Record</h5>
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
              <label class="form-label fw-bold small">Name</label>
              <input type="text" name="name" id="edit_name" class="form-control" required>
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
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Filing Type</label>
              <input type="text" name="filing_type" id="edit_filing_type" class="form-control" placeholder="Monthly">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Filing Period</label>
              <input type="date" name="filing_period" id="edit_filing_period" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Input Date</label>
              <input type="date" name="input_date" id="edit_input_date" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Provision Number</label>
              <input type="text" name="provision_number" id="edit_provision_number" class="form-control" placeholder="e.g. T10">
            </div>
          </div>

          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2">
              <i class="fas fa-chart-line me-2 text-primary"></i> Sales Data
            </div>
            <div class="card-body row">
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Sales (Standard Rate)</label>
                <input type="number" step="0.01" name="sales_standard" id="edit_sales_standard" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Sales (Zero Rate)</label>
                <input type="number" step="0.01" name="sales_zero_rate" id="edit_sales_zero_rate" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1 fw-bold">Sales (Exempt)</label>
                <input type="number" step="0.01" name="sales_exempt" id="edit_sales_exempt" class="form-control form-control-sm border-warning">
              </div>
            </div>
          </div>

          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2">
              <i class="fas fa-shopping-cart me-2 text-primary"></i> Purchase Data
            </div>
            <div class="card-body row">
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">Domestic (Non-Exempt)</label>
                <input type="number" step="0.01" name="purchase_domestic_nonexempt" id="edit_purchase_domestic_nonexempt" class="form-control form-control-sm">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">Domestic (Exempt)</label>
                <input type="number" step="0.01" name="purchase_domestic_exempt" id="edit_purchase_domestic_exempt" class="form-control form-control-sm">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">Import (Non-Exempt)</label>
                <input type="number" step="0.01" name="purchase_import_nonexempt" id="edit_purchase_import_nonexempt" class="form-control form-control-sm">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">Import (Exempt)</label>
                <input type="number" step="0.01" name="purchase_import_exempt" id="edit_purchase_import_exempt" class="form-control form-control-sm">
              </div>
            </div>
          </div>

          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2">
              <i class="fas fa-calculator me-2 text-primary"></i> VAT Summary
            </div>
            <div class="card-body row">
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">Total Input VAT</label>
                <input type="number" step="0.01" name="total_input_vat" id="edit_total_input_vat" class="form-control form-control-sm">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">Total Output VAT</label>
                <input type="number" step="0.01" name="total_output_vat" id="edit_total_output_vat" class="form-control form-control-sm">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1 fw-bold">VAT Payable</label>
                <input type="number" step="0.01" name="vat_payable" id="edit_vat_payable" class="form-control form-control-sm border-success">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1">VAT Credit</label>
                <input type="number" step="0.01" name="vat_credit" id="edit_vat_credit" class="form-control form-control-sm">
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
const vatData = <?= json_encode(array_column($records, null, 'id')) ?>;
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

    document.getElementById('filterProvince').addEventListener('change', function() {
        table.column(3).search(this.value).draw();
    });

    document.getElementById('filterPeriod').addEventListener('change', function() {
        table.column(4).search(this.value).draw();
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
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterPeriod').value = '';
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
    const r = vatData[id];
    if (!r) return;

    document.getElementById('modalAction').value = 'update_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit VAT Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tin').value = r.tin || '';
    document.getElementById('edit_name').value = r.name || '';
    document.getElementById('edit_pro_id').value = r.pro_id || '';
    filterDistricts(r.dis_id || '');
    document.getElementById('edit_provision_number').value = r.provision_number || '';
    document.getElementById('edit_filing_type').value = r.filing_type || '';
    document.getElementById('edit_filing_period').value = r.filing_period || '';
    document.getElementById('edit_input_date').value = r.input_date || '';
    document.getElementById('edit_sales_standard').value = r.sales_standard || 0;
    document.getElementById('edit_sales_zero_rate').value = r.sales_zero_rate || 0;
    document.getElementById('edit_sales_exempt').value = r.sales_exempt || 0;
    document.getElementById('edit_purchase_domestic_nonexempt').value = r.purchase_domestic_nonexempt || 0;
    document.getElementById('edit_purchase_domestic_exempt').value = r.purchase_domestic_exempt || 0;
    document.getElementById('edit_purchase_import_nonexempt').value = r.purchase_import_nonexempt || 0;
    document.getElementById('edit_purchase_import_exempt').value = r.purchase_import_exempt || 0;
    document.getElementById('edit_total_input_vat').value = r.total_input_vat || 0;
    document.getElementById('edit_total_output_vat').value = r.total_output_vat || 0;
    document.getElementById('edit_vat_payable').value = r.vat_payable || 0;
    document.getElementById('edit_vat_credit').value = r.vat_credit || 0;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function addRecord(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New VAT Record';
    document.getElementById('edit_id').value = '';
    const form = document.querySelector('#editModal form');
    form.reset();
    document.getElementById('edit_dis_id').innerHTML = '<option value="">-- Select District --</option>';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
