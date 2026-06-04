<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/batch_nav.php";
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

        // List of all checkbox fields
        $checkboxes = [
            "zone_1", "zone_2", "zone_3", "is_vat_holder", 
            "flag_hr_dev", "flag_eco_friendly", "flag_sez_developer", "flag_sez_investor", 
            "flag_act_production_services", "flag_public_benefit", "flag_compliant_rental", 
            "flag_real_estate_transfer", "flag_act_1_4_7_8_9", "flag_act_2_3_5_6"
        ];
        foreach ($checkboxes as $cb) {
            $data[$cb] = isset($data[$cb]) ? 1 : 0;
        }

        // --- Important: Synchronize Names for legacy support ---
        if (!empty($data['pro_id'])) {
            $data['province'] = $pdo->query("SELECT province_name FROM provinces WHERE province_code = " . $pdo->quote($data['pro_id']))->fetchColumn();
        }
        if (!empty($data['dis_id'])) {
            $data['district'] = $pdo->query("SELECT district_name FROM districts WHERE district_code = " . $pdo->quote($data['dis_id']))->fetchColumn();
        }
        if (!empty($data['sector_id'])) {
            $data['sector'] = $pdo->query("SELECT sector_name FROM business_sectors WHERE id = " . (int)$data['sector_id'])->fetchColumn();
        }

        if ($action === "update_company") {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = ($v === "" ? null : $v);
            }
            $values[] = $id;
            $stmt = $pdo->prepare("UPDATE companies SET " . implode(", ", $fields) . " WHERE id = ?");
            $stmt->execute($values);
            $message = "Company record updated successfully.";
        } elseif ($action === "add_company") {
            $manualYear = $data["tax_year"] ?? date("Y");
            $data["import_batch_id"] = $batch ?: "MANUAL_ENTRY_CIT_" . $manualYear . "_" . date("YmdHis");
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO companies ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            $message = "New company record added.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$companies = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE import_batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $companies = $stmt->fetchAll();
}

// --- Fetch Dictionary Data ---
$provinces = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces ORDER BY province_name")->fetchAll();
$all_districts = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id ORDER BY d.district_name")->fetchAll();
$sectors = $pdo->query("SELECT id, sector_name FROM business_sectors WHERE active = 1 ORDER BY sector_name")->fetchAll();
$years = $pdo->query("SELECT DISTINCT tax_year FROM companies WHERE import_batch_id = " . $pdo->quote($batch) . " ORDER BY tax_year DESC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_cit.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Company Data</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch ?: 'Manual Entry') ?></code> — <strong><?= count($companies) ?></strong> companies</p>
    </div>
    <div class="btn-group shadow-sm">
      <?= batchHubBackButton() ?>
      <button class="btn btn-primary" onclick="addCompany()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
      <a href="calculator.php?batch=<?= urlencode($batch) ?>" class="btn btn-success"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
    </div>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card mb-3 border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
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
        <label class="form-label small fw-bold text-muted">Sector</label>
        <select id="filterSector" class="form-select">
          <option value="">All Sectors</option>
          <?php foreach ($sectors as $s): ?>
          <option value="<?= htmlspecialchars($s["sector_name"]) ?>"><?= htmlspecialchars($s["sector_name"]) ?></option>
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
  <div class="card-body">
    <table id="mainTable" class="table table-bordered table-hover w-100" style="font-size:0.85em">
      <thead class="table-light text-uppercase small">
        <tr>
          <th>#</th><th>Year</th><th>TIN</th><th>Company Name</th><th>Province</th><th>District</th><th>Sector</th>
          <th>VAT?</th><th>Staff</th><th>Revenue</th><th>Net Profit</th><th>PT Paid</th><th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody id="companyTableBody">
        <?php foreach ($companies as $i => $c): ?>
        <?php 
          $is_unmapped = (empty($c["pro_id"]) || empty($c["dis_id"]) || empty($c["sector_id"]));
        ?>
        <tr class="<?= $is_unmapped ? 'table-warning' : '' ?>">
          <td><?= $i + 1 ?></td>
          <td><?= $c["tax_year"] ?></td>
          <td><small class="font-monospace fw-bold"><?= htmlspecialchars($c["tin"]) ?></small></td>
          <td><?= htmlspecialchars($c["company_name"]) ?></td>
          <td>
            <?= htmlspecialchars($c["province"]) ?>
            <?php if (empty($c["pro_id"])): ?><i class="fas fa-exclamation-triangle text-danger ms-1" title="Unknown Province"></i><?php endif; ?>
          </td>
          <td>
            <?= htmlspecialchars($c["district"] ?? '') ?>
            <?php if (empty($c["dis_id"])): ?><i class="fas fa-exclamation-triangle text-danger ms-1" title="Unknown District"></i><?php endif; ?>
          </td>
          <td>
            <?= htmlspecialchars($c["sector"]) ?>
            <?php if (empty($c["sector_id"])): ?><i class="fas fa-exclamation-triangle text-danger ms-1" title="Unknown Sector"></i><?php endif; ?>
          </td>
          <td class="text-center"><span class="badge bg-<?= $c["is_vat_holder"] ? "success" : "secondary" ?>"><?= $c["is_vat_holder"] ? "YES" : "NO" ?></span></td>
          <td class="text-end"><?= number_format($c["staff_count"]) ?></td>
          <td class="text-end"><?= number_format($c["revenue"], 0) ?></td>
          <td class="text-end"><?= number_format($c["net_profit"], 0) ?></td>
          <td class="text-end text-danger fw-bold"><?= number_format($c["pt_paid"], 0) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick='editCompany(<?= (int)$c["id"] ?>)'>
              <i class="fas fa-edit"></i>
            </button>
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
        <h5 class="modal-title" id="modalTitle">Company Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="modalAction" value="update_company">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row">
            <!-- Basic Info -->
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Tax Year</label>
              <input type="number" name="tax_year" id="edit_tax_year" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">TIN</label>
              <input type="text" name="tin" id="edit_tin" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold small">Company Name</label>
              <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
            </div>

            <!-- Location & Sector -->
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold small">Province</label>
              <select name="pro_id" id="edit_pro_id" class="form-select" onchange="filterDistricts()" required>
                <option value="">-- Select Province --</option>
                <?php foreach ($provinces as $p): ?>
                <option value="<?= htmlspecialchars($p["pro_id"]) ?>"><?= htmlspecialchars($p["pro_name"]) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold small">District</label>
              <select name="dis_id" id="edit_dis_id" class="form-select" required>
                <option value="">-- Select District --</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold small">Sector</label>
              <select name="sector_id" id="edit_sector_id" class="form-select" required>
                <option value="">-- Select Sector --</option>
                <?php foreach ($sectors as $s): ?>
                <option value="<?= (int)$s["id"] ?>"><?= htmlspecialchars($s["sector_name"]) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Financials -->
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Revenue</label>
              <input type="number" step="0.01" name="revenue" id="edit_revenue" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Expense</label>
              <input type="number" step="0.01" name="expense" id="edit_expense" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Net Profit</label>
              <input type="number" step="0.01" name="net_profit" id="edit_net_profit" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">PT Paid (TaxRIS)</label>
              <input type="number" step="0.01" name="pt_paid" id="edit_pt_paid" class="form-control text-danger fw-bold">
            </div>

            <!-- More Details -->
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Staff Count</label>
              <input type="number" name="staff_count" id="edit_staff_count" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Total Assets</label>
              <input type="number" step="0.01" name="total_assets" id="edit_total_assets" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Registration Date</label>
              <input type="date" name="registration_date" id="edit_registration_date" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label fw-bold small">Investment License Date</label>
              <input type="date" name="investment_license_date" id="edit_investment_license_date" class="form-control">
            </div>

            <!-- Checkboxes / Flags -->
            <div class="col-12 mt-2">
              <div class="card bg-light border-0">
                <div class="card-body row py-2">
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="is_vat_holder" id="edit_is_vat_holder" class="form-check-input"><label class="form-check-label small">VAT Holder</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="zone_1" id="edit_zone_1" class="form-check-input"><label class="form-check-label small">Zone 1</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="zone_2" id="edit_zone_2" class="form-check-input"><label class="form-check-label small">Zone 2</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="zone_3" id="edit_zone_3" class="form-check-input"><label class="form-check-label small">Zone 3</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_sez_developer" id="edit_flag_sez_developer" class="form-check-input"><label class="form-check-label small">SEZ Developer</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_sez_investor" id="edit_flag_sez_investor" class="form-check-input"><label class="form-check-label small">SEZ Investor</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_hr_dev" id="edit_flag_hr_dev" class="form-check-input"><label class="form-check-label small">HR Dev</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_eco_friendly" id="edit_flag_eco_friendly" class="form-check-input"><label class="form-check-label small">Eco Friendly</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_act_production_services" id="edit_flag_act_production_services" class="form-check-input"><label class="form-check-label small">Production/Services</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_public_benefit" id="edit_flag_public_benefit" class="form-check-input"><label class="form-check-label small">Public Benefit</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_compliant_rental" id="edit_flag_compliant_rental" class="form-check-input"><label class="form-check-label small">Compliant Rental</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_real_estate_transfer" id="edit_flag_real_estate_transfer" class="form-check-input"><label class="form-check-label small">Real Estate Transfer</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_act_1_4_7_8_9" id="edit_flag_act_1_4_7_8_9" class="form-check-input"><label class="form-check-label small">Act 1,4,7,8,9 (IPL)</label></div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-check"><input type="checkbox" name="flag_act_2_3_5_6" id="edit_flag_act_2_3_5_6" class="form-check-input"><label class="form-check-label small">Act 2,3,5,6 (IPL)</label></div>
                  </div>
                </div>
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
const companyData = <?= json_encode(array_column($companies, null, 'id')) ?>;
const allDistricts = <?= json_encode($all_districts) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    const table = $('#mainTable').DataTable({
        "dom": 'rtip', // Hide default search box
        "pageLength": 25,
        "ordering": true,
        "order": [[0, "asc"]]
    });

    // Custom Search (TIN / Name)
    document.getElementById('customSearch').addEventListener('keyup', function() {
        table.search(this.value).draw();
    });

    // Dropdown Filters
    document.getElementById('filterProvince').addEventListener('change', function() {
        table.column(4).search(this.value).draw(); // Province column
    });
    document.getElementById('filterSector').addEventListener('change', function() {
        table.column(6).search(this.value).draw(); // Sector column
    });
    document.getElementById('filterYear').addEventListener('change', function() {
        table.column(1).search(this.value).draw(); // Year column
    });

    // Check for auto-add trigger from import page
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_add') === '1') {
        const manualYear = urlParams.get('year');
        addCompany(manualYear);
    }
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterSector').value = '';
    document.getElementById('filterYear').value = '';
    const table = $('#mainTable').DataTable();
    table.search('').columns().search('').draw();
}

function addCompany(prefilledYear = "") {
    document.getElementById('modalAction').value = 'add_company';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New Company Record';
    document.getElementById('edit_id').value = '';
    const form = document.querySelector('#editModal form');
    form.reset();

    if (prefilledYear) {
        document.getElementById('edit_tax_year').value = prefilledYear;
    }

    document.getElementById('edit_dis_id').innerHTML = '<option value="">-- Select District --</option>';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function filterDistricts(selectedDistrictId = "") {
    const provinceId = document.getElementById('edit_pro_id').value;
    const districtSelect = document.getElementById('edit_dis_id');
    districtSelect.innerHTML = '<option value="">-- Select District --</option>';
    
    if (provinceId) {
        const filtered = allDistricts.filter(d => d.pro_id == provinceId);
        filtered.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.dis_id;
            opt.textContent = d.dis_name;
            if (d.dis_id == selectedDistrictId) opt.selected = true;
            districtSelect.appendChild(opt);
        });
    }
}

function editCompany(id) {
    const c = companyData[id];
    if (!c) return;
    
    document.getElementById('modalAction').value = 'update_company';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Company Record';
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_tax_year').value = c.tax_year || '';
    document.getElementById('edit_tin').value = c.tin || '';
    document.getElementById('edit_company_name').value = c.company_name || '';
    
    // Set IDs directly
    document.getElementById('edit_pro_id').value = c.pro_id || '';
    filterDistricts(c.dis_id || '');
    document.getElementById('edit_sector_id').value = c.sector_id || '';

    // Financials
    document.getElementById('edit_revenue').value = c.revenue || 0;
    document.getElementById('edit_expense').value = c.expense || 0;
    document.getElementById('edit_net_profit').value = c.net_profit || 0;
    document.getElementById('edit_pt_paid').value = c.pt_paid || 0;
    document.getElementById('edit_staff_count').value = c.staff_count || 0;
    document.getElementById('edit_total_assets').value = c.total_assets || 0;
    document.getElementById('edit_registration_date').value = c.registration_date || '';
    document.getElementById('edit_investment_license_date').value = c.investment_license_date || '';

    // Checkboxes
    const cbs = [
        "zone_1", "zone_2", "zone_3", "is_vat_holder", 
        "flag_hr_dev", "flag_eco_friendly", "flag_sez_developer", "flag_sez_investor", 
        "flag_act_production_services", "flag_public_benefit", "flag_compliant_rental", 
        "flag_real_estate_transfer", "flag_act_1_4_7_8_9", "flag_act_2_3_5_6"
    ];
    cbs.forEach(cb => {
        const el = document.getElementById('edit_' + cb);
        if (el) el.checked = (c[cb] == 1);
    });

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
