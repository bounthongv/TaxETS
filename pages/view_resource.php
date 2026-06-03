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

        if ($action === "update_record") {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = ($v === "" ? null : $v);
            }
            $values[] = $id;
            $stmt = $pdo->prepare("UPDATE import_resource_data SET " . implode(", ", $fields) . " WHERE id = ?");
            $stmt->execute($values);
            $message = "Record updated successfully.";
        } elseif ($action === "add_record") {
            $new_batch = $batch ?: "MANUAL_ENTRY_RESOURCE_" . date("Y");
            $data["batch_id"] = $new_batch;
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_resource_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            if (empty($batch)) {
                header("Location: view_resource.php?batch=" . urlencode($new_batch));
                exit;
            }
            $message = "New record added.";
        } elseif ($action === "delete_record") {
            $stmt = $pdo->prepare("DELETE FROM import_resource_data WHERE id = ?");
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
    $stmt = $pdo->prepare("SELECT * FROM import_resource_data WHERE batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $records = $stmt->fetchAll();
}

// Fetch years for filter
$years = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT DISTINCT tax_year FROM import_resource_data WHERE batch_id = ? ORDER BY tax_year DESC");
    $stmt->execute([$batch]);
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch resource types for filter
$resource_types = $pdo->query("SELECT DISTINCT resource_type FROM import_resource_data ORDER BY resource_type")->fetchAll(PDO::FETCH_COLUMN);
$resource_types_lookup = $pdo->query("SELECT item_no, item_name FROM bm_natural_resource WHERE active = 1 ORDER BY item_no")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_resource.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Resource Fee Records</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch ?: 'Manual Entry') ?></code> — <strong><?= count($records) ?></strong> records</p>
    </div>
    <div class="btn-group shadow-sm">
      <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#manualEntryModal"><i class="fas fa-plus me-2"></i> Add Manual Entry</button>
      <button class="btn btn-primary" onclick="addRecord()"><i class="fas fa-list me-2"></i> Add Record to Batch</button>
      <a href="te_resource.php?batch=<?= urlencode($batch) ?>" class="btn btn-warning text-dark fw-bold"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
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
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Resource Type</label>
        <select id="filterResourceType" class="form-select">
          <option value="">All Types</option>
          <?php foreach ($resource_types as $rt): ?>
          <option value="<?= htmlspecialchars($rt) ?>"><?= htmlspecialchars($rt) ?></option>
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
          <th>Resource Type</th>
          <th class="text-end">Actual Rate</th>
          <th class="text-end">Fee Collected</th>
          <th class="text-end text-primary">TE Amount</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></small></td>
          <td><?= $r["tax_year"] ?></td>
          <td><?= htmlspecialchars($r["resource_type"] ?? '') ?></td>
          <td class="text-end"><?= number_format((float)$r["actual_rate"], 2) ?>%</td>
          <td class="text-end"><?= number_format((float)$r["fee_collected"], 2) ?></td>
          <td class="text-end fw-bold text-primary"><?= number_format((float)$r["te_amount"], 2) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick='editRecord(<?= $r["id"] ?>)'>
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
        <h5 class="modal-title">Manual Data Entry for Resource Fee</h5>
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
        <button type="button" class="btn btn-warning text-dark" onclick="goToManualEntry()">Manage Records</button>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="modalTitle">Resource Fee Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="modalAction" value="update_record">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold small">TIN</label>
              <input type="text" name="tin" id="edit_tin" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Tax Year</label>
              <input type="number" name="tax_year" id="edit_tax_year" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">License Date</label>
              <input type="date" name="license_date" id="edit_license_date" class="form-control">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold small">Resource Type</label>
              <select name="resource_type" id="edit_resource_type" class="form-select" required>
                <option value="">-- Select Resource Type --</option>
                <?php foreach ($resource_types_lookup as $rt): ?>
                <option value="<?= htmlspecialchars($rt["item_no"]) ?>"><?= htmlspecialchars($rt["item_no"]) ?> - <?= htmlspecialchars($rt["item_name"]) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Actual Rate (%)</label>
              <input type="number" step="0.01" name="actual_rate" id="edit_actual_rate" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Fee Collected (LAK)</label>
              <input type="number" step="0.01" name="fee_collected" id="edit_fee_collected" class="form-control" required>
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
const resData = <?= json_encode(array_column($records, null, 'id')) ?>;

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

    document.getElementById('filterResourceType').addEventListener('change', function() {
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
    document.getElementById('filterResourceType').value = '';
    $('#mainTable').DataTable().search('').columns().search('').draw();
}

function editRecord(id) {
    const r = resData[id];
    if (!r) return;

    document.getElementById('modalAction').value = 'update_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tin').value = r.tin || '';
    document.getElementById('edit_tax_year').value = r.tax_year || '';
    document.getElementById('edit_license_date').value = r.license_date || '';
    document.getElementById('edit_resource_type').value = r.resource_type || '';
    document.getElementById('edit_actual_rate').value = r.actual_rate || 0;
    document.getElementById('edit_fee_collected').value = r.fee_collected || 0;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function addRecord(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Record';
    document.getElementById('edit_id').value = '';
    const form = document.querySelector('#editModal form');
    form.reset();
    if (prefilledYear) {
        document.getElementById('edit_tax_year').value = prefilledYear;
    }
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    window.location.href = `view_resource.php?batch=MANUAL_ENTRY_RESOURCE_${year}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
