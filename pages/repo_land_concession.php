<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection();

$batch = $_GET["batch"] ?? "";
$message = "";
$msg_type = "success";

// --- Handle Save (Add or Update) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        $action = $_POST["action"];
        $data = $_POST;
        unset($data["action"]);
        $id = $data["id"] ?? null;
        unset($data["id"]);

        // Sync Names from Dictionary IDs
        if (!empty($data['pro_id'])) {
            $data['province'] = $pdo->query("SELECT pro_name FROM province WHERE pro_id = " . $pdo->quote($data['pro_id']))->fetchColumn();
        }
        if (!empty($data['dis_id'])) {
            $data['district'] = $pdo->query("SELECT dis_name FROM district WHERE dis_id = " . $pdo->quote($data['dis_id']))->fetchColumn();
        }

        if ($action === "update_land") {
            $fields = []; $values = [];
            foreach ($data as $k => $v) { $fields[] = "$k = ?"; $values[] = ($v === "" ? null : $v); }
            $values[] = $id;
            $pdo->prepare("UPDATE repo_land_concession_data SET " . implode(", ", $fields) . " WHERE id = ?")->execute($values);
            $message = "Record updated.";
        } elseif ($action === "add_land") {
            $data["import_batch_id"] = $batch ?: "MANUAL_ENTRY_LAND_" . date("Ymd");
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO repo_land_concession_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $message = "Record added.";
        }
    } catch (Exception $e) { $message = "Error: " . $e->getMessage(); $msg_type = "danger"; }
}

$records = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT * FROM repo_land_concession_data WHERE import_batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $records = $stmt->fetchAll();
}

$provinces = $pdo->query("SELECT pro_id, pro_name FROM province ORDER BY pro_name")->fetchAll();
$all_districts = $pdo->query("SELECT dis_id, pro_id, dis_name FROM district ORDER BY dis_name")->fetchAll();
$years = $pdo->query("SELECT DISTINCT tax_year FROM repo_land_concession_data WHERE import_batch_id = " . $pdo->quote($batch) . " ORDER BY tax_year DESC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <?php $is_manual = (strpos($batch, 'MANUAL') !== false); ?>
      <h2><a href="import_land_concession.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Land Concession Data</h2>
      <p class="text-muted">
          Source: <strong><?= $is_manual ? 'Manual Entry' : 'Excel Batch' ?></strong> — 
          Batch: <code><?= htmlspecialchars($batch ?: 'Manual') ?></code> — 
          <strong><?= count($records) ?></strong> records
      </p>
    </div>
    <div class="btn-group shadow-sm">
      <button class="btn btn-primary" onclick="addLand()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
      <a href="calculate_land_concession.php?batch=<?= urlencode($batch) ?>" class="btn btn-success"><i class="fas fa-calculator me-2"></i> Run Calculation</a>
    </div>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card mb-3 border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Search TIN / Company</label>
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
    <div class="table-responsive">
      <table id="mainTable" class="table table-hover mb-0" style="font-size:0.85em">
        <thead class="table-light text-uppercase small">
          <tr>
            <th>TIN</th><th>Tax Year</th><th>Company</th><th>Province</th><th>District</th><th>Area (ha)</th><th>Bench Rate</th><th>Paid</th><th>TE (USD)</th><th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <tr class="<?= (empty($r['pro_id']) || empty($r['dis_id'])) ? 'table-warning' : '' ?>">
            <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r["tin"]) ?></small></td>
            <td><?= $r["tax_year"] ?></td>
            <td><?= htmlspecialchars($r["company_name"]) ?></td>
            <td><?= htmlspecialchars($r["province"]) ?> <?php if(empty($r['pro_id'])): ?>⚠️<?php endif; ?></td>
            <td><?= htmlspecialchars($r["district"]) ?> <?php if(empty($r['dis_id'])): ?>⚠️<?php endif; ?></td>
            <td class="text-end"><?= number_format($r["concession_area_ha"], 2) ?></td>
            <td class="text-end"><?= number_format($r["benchmark_rate_usd"], 2) ?></td>
            <td class="text-end"><?= number_format($r["concession_fee_paid_usd"], 2) ?></td>
            <td class="text-end text-danger fw-bold"><?= number_format($r["non_tax_te_usd"], 2) ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editLand(<?= (int)$r["id"] ?>)'><i class="fas fa-edit"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="landModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title" id="modalTitle">Land Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="modalAction">
          <input type="hidden" name="id" id="edit_id">
          <div class="row">
            <div class="col-md-3 mb-3"><label class="form-label small fw-bold">Tax Year</label><input type="number" name="tax_year" id="edit_tax_year" class="form-control" required></div>
            <div class="col-md-3 mb-3"><label class="form-label small fw-bold">TIN</label><input type="text" name="tin" id="edit_tin" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label class="form-label small fw-bold">Company Name</label><input type="text" name="company_name" id="edit_company_name" class="form-control"></div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">Province</label>
                <select name="pro_id" id="edit_pro_id" class="form-select" onchange="filterDistricts()" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($provinces as $p): ?><option value="<?= $p['pro_id'] ?>"><?= $p['pro_name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold">District</label>
                <select name="dis_id" id="edit_dis_id" class="form-select" required><option value="">-- Select --</option></select>
            </div>

            <div class="col-md-3 mb-3"><label class="form-label small fw-bold">Confirm Date</label><input type="date" name="confirm_date" id="edit_confirm_date" class="form-control"></div>
            <div class="col-md-3 mb-3"><label class="form-label small fw-bold">Area (ha)</label><input type="number" step="0.0001" name="concession_area_ha" id="edit_area" class="form-control"></div>
            <div class="col-md-3 mb-3"><label class="form-label small fw-bold">Bench Rate (USD)</label><input type="number" step="0.01" name="benchmark_rate_usd" id="edit_bench_rate" class="form-control"></div>
            <div class="col-md-3 mb-3"><label class="form-label small fw-bold">Paid (USD)</label><input type="number" step="0.01" name="concession_fee_paid_usd" id="edit_paid" class="form-control"></div>
            
            <div class="col-md-6 mb-3"><label class="form-label small fw-bold">Provision Name</label><input type="text" name="provision_name" id="edit_provision" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const landData = <?= json_encode(array_column($records, null, 'id')) ?>;
const allDistricts = <?= json_encode($all_districts) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const table = $('#mainTable').DataTable({
        "dom": 'rtip',
        "pageLength": 25,
        "ordering": true,
        "order": [[2, "asc"]]
    });

    document.getElementById('customSearch').addEventListener('keyup', function() { table.search(this.value).draw(); });
    document.getElementById('filterProvince').addEventListener('change', function() { table.column(3).search(this.value).draw(); });
    document.getElementById('filterYear').addEventListener('change', function() { table.column(1).search(this.value).draw(); });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_add') === '1') { addLand(urlParams.get('year')); }
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterYear').value = '';
    const table = $('#mainTable').DataTable();
    table.search('').columns().search('').draw();
}

function filterDistricts(selectedId = "") {
    const proId = document.getElementById('edit_pro_id').value;
    const disSelect = document.getElementById('edit_dis_id');
    disSelect.innerHTML = '<option value="">-- Select --</option>';
    if (proId) {
        allDistricts.filter(d => d.pro_id == proId).forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.dis_id; opt.textContent = d.dis_name;
            if (d.dis_id == selectedId) opt.selected = true;
            disSelect.appendChild(opt);
        });
    }
}

function addLand(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_land';
    document.getElementById('modalTitle').textContent = 'Add Land Concession Record';
    const form = document.querySelector('#landModal form');
    form.reset();
    document.getElementById('edit_id').value = '';
    if (prefilledYear) { document.getElementById('edit_tax_year').value = prefilledYear; }
    filterDistricts();
    new bootstrap.Modal(document.getElementById('landModal')).show();
}

function editLand(id) {
    const r = landData[id];
    if (!r) return;
    document.getElementById('modalAction').value = 'update_land';
    document.getElementById('modalTitle').textContent = 'Edit Land Concession Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tax_year').value = r.tax_year;
    document.getElementById('edit_tin').value = r.tin;
    document.getElementById('edit_company_name').value = r.company_name;
    document.getElementById('edit_pro_id').value = r.pro_id || '';
    filterDistricts(r.dis_id);
    document.getElementById('edit_confirm_date').value = r.confirm_date;
    document.getElementById('edit_area').value = r.concession_area_ha;
    document.getElementById('edit_bench_rate').value = r.benchmark_rate_usd;
    document.getElementById('edit_paid').value = r.concession_fee_paid_usd;
    document.getElementById('edit_provision').value = r.provision_name;
    new bootstrap.Modal(document.getElementById('landModal')).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
