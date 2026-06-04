<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/batch_nav.php";
$pdo = getDbConnection();

// Migration: derive tax_year from filing_period for existing records
$pdo->exec("UPDATE import_salary_tax_data SET tax_year = REGEXP_SUBSTR(filing_period, '[0-9]{4}') WHERE (tax_year IS NULL OR tax_year = 0) AND filing_period REGEXP '[0-9]{4}'");

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
            $stmt = $pdo->prepare("UPDATE import_salary_tax_data SET " . implode(", ", $fields) . " WHERE id = ?");
            $stmt->execute($values);
            $message = "Record updated successfully.";
        } elseif ($action === "add_record") {
            $manualYear = $data["tax_year"] ?? date("Y");
            $new_batch = $batch ?: "MANUAL_ENTRY_SALARY_" . $manualYear . "_" . date("YmdHis");
            $data["batch_id"] = $new_batch;
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_salary_tax_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            if (empty($batch)) {
                header("Location: view_salary.php?batch=" . urlencode($new_batch));
                exit;
            }
            $message = "New record added.";
        } elseif ($action === "delete_record") {
            $stmt = $pdo->prepare("DELETE FROM import_salary_tax_data WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Record deleted.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch records
$records = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT * FROM import_salary_tax_data WHERE batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $records = $stmt->fetchAll();
}

// Fetch periods for filter
$periods = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT DISTINCT filing_period FROM import_salary_tax_data WHERE batch_id = ? AND filing_period IS NOT NULL AND filing_period != '' ORDER BY filing_period DESC");
    $stmt->execute([$batch]);
    $periods = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_salary.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Salary Tax Records</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch ?: 'Manual Entry') ?></code> — <strong><?= count($records) ?></strong> records</p>
    </div>
    <div class="btn-group shadow-sm">
      <?= batchHubBackButton() ?>
      <button class="btn btn-success" onclick="addRecord()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
      <a href="te_salary_tax.php?batch=<?= urlencode($batch) ?>" class="btn btn-primary"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
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
        <label class="form-label small fw-bold text-muted">Search TIN / Period</label>
        <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
      </div>
      <div class="col-md-1">
        <label class="form-label small fw-bold text-muted">Year</label>
        <select id="filterYear" class="form-select">
          <option value="">All</option>
          <?php
          $years = $pdo->prepare("SELECT DISTINCT tax_year FROM import_salary_tax_data WHERE batch_id = ? AND tax_year IS NOT NULL ORDER BY tax_year DESC");
          $years->execute([$batch]);
          foreach ($years as $y): ?>
          <option value="<?= $y['tax_year'] ?>"><?= $y['tax_year'] ?></option>
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
          <th>Year</th>
          <th>Period</th>
          <th>Filing Type</th>
          <th class="text-end">Taxable Amount</th>
          <th class="text-end">Exempt Amount</th>
          <th class="text-end">Tax Paid</th>
          <th>Provision</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></small></td>
          <td><?= $r["tax_year"] ?></td>
          <td><?= htmlspecialchars($r["filing_period"] ?? '') ?></td>
          <td><?= htmlspecialchars($r["filing_type"] ?? '') ?></td>
          <td class="text-end"><?= number_format((float)($r["total_taxable_amount"] ?? 0), 0) ?></td>
          <td class="text-end"><?= number_format((float)($r["tax_exempt_amount"] ?? 0), 0) ?></td>
          <td class="text-end fw-bold"><?= number_format((float)($r["tax_amount"] ?? 0), 0) ?></td>
          <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r["provision_number"] ?: '-') ?></span></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick='editRecord(<?= (int)$r["id"] ?>)'>
              <i class="fas fa-edit"></i>
            </button>
            <?php if (empty($r["calculated_at"])): ?>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
              <input type="hidden" name="action" value="delete_record">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
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
        <h5 class="modal-title" id="modalTitle">Salary Tax Record</h5>
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
            <div class="col-md-2 mb-3">
              <label class="form-label fw-bold small">Tax Year</label>
              <input type="number" name="tax_year" id="edit_tax_year" class="form-control" required>
            </div>
            <div class="col-md-2 mb-3">
              <label class="form-label fw-bold small">Filing Type</label>
              <input type="text" name="filing_type" id="edit_filing_type" class="form-control" placeholder="Monthly">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Filing Period</label>
              <input type="text" name="filing_period" id="edit_filing_period" class="form-control" placeholder="e.g. 05/2026" required>
            </div>
            <div class="col-md-2 mb-3">
              <label class="form-label fw-bold small">Input Date</label>
              <input type="date" name="input_date" id="edit_input_date" class="form-control">
            </div>
          </div>

          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2">
              <i class="fas fa-coins me-2 text-primary"></i> Financial Data
            </div>
            <div class="card-body row">
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Salaries & Wages (Cash)</label>
                <input type="number" step="0.01" name="total_salaries_wages_cash" id="edit_total_salaries_wages_cash" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Other Fringe Benefits</label>
                <input type="number" step="0.01" name="other_fringe_benefits" id="edit_other_fringe_benefits" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1 fw-bold">Total Taxable Amount</label>
                <input type="number" step="0.01" name="total_taxable_amount" id="edit_total_taxable_amount" class="form-control form-control-sm border-primary">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1 fw-bold">Tax Exempt Amount</label>
                <input type="number" step="0.01" name="tax_exempt_amount" id="edit_tax_exempt_amount" class="form-control form-control-sm border-warning">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1 fw-bold">Tax Amount Paid</label>
                <input type="number" step="0.01" name="tax_amount" id="edit_tax_amount" class="form-control form-control-sm border-success">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Adjustment Amount</label>
                <input type="number" step="0.01" name="adjustment_amount" id="edit_adjustment_amount" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Carryforward Amount</label>
                <input type="number" step="0.01" name="carryforward_amount" id="edit_carryforward_amount" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Total Amount Due</label>
                <input type="number" step="0.01" name="total_amount_due" id="edit_total_amount_due" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted mb-1">Provision Number</label>
                <input type="text" name="provision_number" id="edit_provision_number" class="form-control form-control-sm" placeholder="e.g. T21">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const recordData = <?= json_encode(array_column($records, null, 'id')) ?>;

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
    document.getElementById('filterPeriod').addEventListener('change', function() {
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
    document.getElementById('filterPeriod').value = '';
    $('#mainTable').DataTable().search('').columns().search('').draw();
}

function editRecord(id) {
    const r = recordData[id];
    if (!r) return;

    document.getElementById('modalAction').value = 'update_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Salary Tax Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tin').value = r.tin || '';
    document.getElementById('edit_tax_year').value = r.tax_year || '';
    document.getElementById('edit_filing_type').value = r.filing_type || '';
    document.getElementById('edit_filing_period').value = r.filing_period || '';
    document.getElementById('edit_input_date').value = r.input_date || '';
    document.getElementById('edit_total_salaries_wages_cash').value = r.total_salaries_wages_cash || 0;
    document.getElementById('edit_other_fringe_benefits').value = r.other_fringe_benefits || 0;
    document.getElementById('edit_total_taxable_amount').value = r.total_taxable_amount || 0;
    document.getElementById('edit_tax_exempt_amount').value = r.tax_exempt_amount || 0;
    document.getElementById('edit_tax_amount').value = r.tax_amount || 0;
    document.getElementById('edit_adjustment_amount').value = r.adjustment_amount || 0;
    document.getElementById('edit_carryforward_amount').value = r.carryforward_amount || 0;
    document.getElementById('edit_total_amount_due').value = r.total_amount_due || 0;
    document.getElementById('edit_provision_number').value = r.provision_number || '';

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function addRecord(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Salary Tax Record';
    document.getElementById('edit_id').value = '';
    const form = document.querySelector('#editModal form');
    form.reset();
    if (prefilledYear) {
        document.getElementById('edit_tax_year').value = prefilledYear;
    }
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
