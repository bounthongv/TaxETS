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

        // Handle checkbox fields
        $checkboxes = ["is_ss_member"];
        foreach ($checkboxes as $cb) {
            $data[$cb] = isset($data[$cb]) ? 1 : 0;
        }

        if ($action === "update_record") {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = ($v === "" ? null : $v);
            }
            $values[] = $id;
            $stmt = $pdo->prepare("UPDATE import_pit_data SET " . implode(", ", $fields) . " WHERE id = ?");
            $stmt->execute($values);
            $message = "Record updated successfully.";
        } elseif ($action === "add_record") {
            $new_batch = $batch ?: "MANUAL_ENTRY_PIT_" . date("Y");
            $data["batch_id"] = $new_batch;
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_pit_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            if (empty($batch)) {
                header("Location: view_individual.php?batch=" . urlencode($new_batch));
                exit;
            }
            $message = "New record added.";
        } elseif ($action === "delete_record") {
            $stmt = $pdo->prepare("DELETE FROM import_pit_data WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Record deleted.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// List of all amount fields for display
$amount_fields = [
    '21' => 'amount_21', '22' => 'amount_22',
    '23_1' => 'amount_23_1', '23_2' => 'amount_23_2',
    '24' => 'amount_24', '25' => 'amount_25',
    '26' => 'amount_26', '27' => 'amount_27',
    '28_1' => 'amount_28_1', '28_2' => 'amount_28_2',
    '29' => 'amount_29'
];

$prov_names = [
    "21" => "Overtime/Night Shift",
    "22" => "Severance/Redundancy",
    "23_1" => "Rental (Building)",
    "23_2" => "Rental (Land/Other)",
    "24" => "Consulting/Service",
    "25" => "Contractor Income",
    "26" => "Shares Transfer",
    "27" => "Dividends",
    "28_1" => "Interest (Loan)",
    "28_2" => "Interest (Bonds)",
    "29" => "Gifts/Bonus"
];

$records = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT * FROM import_pit_data WHERE batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $records = $stmt->fetchAll();
}

// Fetch years for filter
$years = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT DISTINCT tax_year FROM import_pit_data WHERE batch_id = ? ORDER BY tax_year DESC");
    $stmt->execute([$batch]);
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_individual.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Individual Tax Records</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch ?: 'Manual Entry') ?></code> — <strong><?= count($records) ?></strong> records</p>
    </div>
    <div class="btn-group shadow-sm">
      <button class="btn btn-primary" onclick="addRecord()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
      <a href="te_individual.php?batch=<?= urlencode($batch) ?>" class="btn btn-success"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
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
        <label class="form-label small fw-bold text-muted">Search PTIN / Name</label>
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
          <th>Year</th>
          <th>PTIN</th>
          <th>Employee Name</th>
          <th>Filing Date</th>
          <th class="text-end">Total Income</th>
          <th class="text-center">SS Member</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $i => $r): ?>
        <?php
            $total_income = 0;
            foreach ($amount_fields as $col) {
                $total_income += (float)($r[$col] ?? 0);
            }
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= $r["tax_year"] ?></td>
          <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["ptin"]) ?></small></td>
          <td><?= htmlspecialchars($r["employee_name"]) ?></td>
          <td><?= htmlspecialchars($r["filing_date"] ?? '') ?></td>
          <td class="text-end"><?= number_format($total_income, 0) ?></td>
          <td class="text-center">
            <span class="badge bg-<?= $r["is_ss_member"] ? "success" : "secondary" ?>">
              <?= $r["is_ss_member"] ? "YES" : "NO" ?>
            </span>
          </td>
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
        <h5 class="modal-title" id="modalTitle">Individual Tax Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="modalAction" value="update_record">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Tax Year</label>
              <input type="number" name="tax_year" id="edit_tax_year" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">PTIN</label>
              <input type="text" name="ptin" id="edit_ptin" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Employee Name</label>
              <input type="text" name="employee_name" id="edit_employee_name" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Filing Date</label>
              <input type="date" name="filing_date" id="edit_filing_date" class="form-control">
            </div>
          </div>

          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2">
              <i class="fas fa-coins me-2 text-primary"></i> Income Provisions (Amounts)
            </div>
            <div class="card-body row">
              <?php foreach ($amount_fields as $key => $col): ?>
              <div class="col-md-3 mb-2">
                <label class="form-label small text-muted mb-1"><?= htmlspecialchars($prov_names[$key] ?? "Prov $key") ?></label>
                <input type="number" step="0.01" name="<?= $col ?>" id="edit_<?= $col ?>" class="form-control form-control-sm">
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-check">
                <input type="checkbox" name="is_ss_member" id="edit_is_ss_member" class="form-check-input">
                <label class="form-check-label small">Social Security Member (Provision 30)</label>
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
        table.column(1).search(this.value).draw();
    });

    // Check for auto-add trigger from import page
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_add') === '1') {
        const manualYear = urlParams.get('year');
        addRecord(manualYear);
    }
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterYear').value = '';
    $('#mainTable').DataTable().search('').columns().search('').draw();
}

function editRecord(id) {
    const r = recordData[id];
    if (!r) return;

    document.getElementById('modalAction').value = 'update_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Individual Tax Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tax_year').value = r.tax_year || '';
    document.getElementById('edit_ptin').value = r.ptin || '';
    document.getElementById('edit_employee_name').value = r.employee_name || '';
    document.getElementById('edit_filing_date').value = r.filing_date || '';
    
    // Set amount fields
    <?php foreach ($amount_fields as $col): ?>
    document.getElementById('edit_<?= $col ?>').value = r.<?= $col ?> || 0;
    <?php endforeach; ?>
    document.getElementById('edit_is_ss_member').checked = (r.is_ss_member == 1);
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function addRecord(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Individual Tax Record';
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
