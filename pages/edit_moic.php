<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM repo_moic WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) { die("Record not found"); }

$provinces = $pdo->query("SELECT * FROM provinces WHERE active = 1 ORDER BY province_name")->fetchAll();
$moic_categories = $pdo->query("SELECT * FROM moic_categories WHERE active = 1 ORDER BY category_name")->fetchAll();
$enterprise_types = $pdo->query("SELECT * FROM enterprise_types WHERE active = 1 ORDER BY type_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM enterprise_project_status ORDER BY id ASC")->fetchAll();

$existing_cats = $pdo->prepare("
    SELECT m.*, c.category_name, sc.sub_category_name 
    FROM moic_enterprise_category_map m
    JOIN moic_categories c ON m.main_category_id = c.id
    LEFT JOIN moic_sub_categories sc ON m.sub_category_id = sc.id
    WHERE m.enterprise_id = ?
");
$existing_cats->execute([$id]);
$cat_rows = $existing_cats->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><a href="repo_moic.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Edit MOIC Enterprise</h2>
        <p class="text-muted">Modify enterprise record details for <strong><?= htmlspecialchars($record['company_name']) ?></strong>.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="save_moic.php" method="POST">
            <input type="hidden" name="id" value="<?= $record['id'] ?>">

            <!-- 1. Basic Information -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-info-circle me-2"></i> 1. Basic Information</h5></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">TIN (Tax ID)</label>
                    <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($record['tin']) ?>" required readonly bg-light>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($record['company_name']) ?>" required>
                </div>
            </div>

            <!-- 2. Location Details -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-map-marker-alt me-2"></i> 2. Location Details</h5></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Province</label>
                    <select name="province_id" class="form-select province-select" id="province_select">
                        <option value="">Select Province</option>
                        <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $record['province_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['province_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">District</label>
                    <select name="district_id" class="form-select district-select" id="district_select">
                        <option value="">Select District</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Village</label>
                    <select name="village_id" class="form-select village-select" id="village_select" disabled>
                        <option value="">Select Village</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">Address / Street</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($record['address']) ?></textarea>
                </div>
            </div>

            <!-- 3. Enterprise Status & Dates -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-file-contract me-2"></i> 3. Enterprise Status & Dates</h5></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Enterprise Type</label>
                    <select name="enterprise_type_id" class="form-select">
                        <option value="">Select Type</option>
                        <?php foreach ($enterprise_types as $et): ?>
                        <option value="<?= $et['id'] ?>" <?= $record['enterprise_type_id'] == $et['id'] ? 'selected' : '' ?>><?= htmlspecialchars($et['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Business License Date</label>
                    <input type="date" name="license_date" class="form-control" value="<?= $record['license_date'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">First Revenue Date</label>
                    <input type="date" name="first_revenue_date" class="form-control" value="<?= $record['first_revenue_date'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Staff Count</label>
                    <input type="number" name="business_size_staff" class="form-control" value="<?= $record['business_size_staff'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Registered Capital (LAK)</label>
                    <input type="number" step="0.01" name="registered_capital" class="form-control" value="<?= $record['registered_capital'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Enterprise Holding VAT System</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="vat_system_status" id="vatToggle" value="1" <?= $record['vat_system_status'] == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="vatToggle">Yes (Holding VAT)</label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Enterprise/Project Status</label>
                    <select name="status_id" class="form-select">
                        <?php foreach ($statuses as $st): ?>
                        <option value="<?= $st['id'] ?>" <?= $record['status_id'] == $st['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['status_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 4. Incentive Scopes -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-tags me-2"></i> 4. Incentive Eligibility Scopes</h5></div>
                <p class="small text-muted mb-3">Select 'Yes' for sectors/activities that qualify for tax incentives.</p>
                
                <?php 
                $scopes = [
                    'hr_dev_scope' => 'HR Development',
                    'innovative_tech_scope' => 'Innovative Technology',
                    'art9_p2_scope' => 'Art 9 Point 2',
                    'art9_p3_scope' => 'Art 9 Point 3',
                    'art9_p4_scope' => 'Art 9 Point 4',
                    'art9_p5_scope' => 'Art 9 Point 5',
                    'art9_p6_scope' => 'Art 9 Point 6',
                    'industry_manuf_scope' => 'Industry and Manufacturing',
                    'commerce_service_scope' => 'Commerce and Service',
                    'electric_mining_scope' => 'Electric Power and Mining',
                    'agri_industrial_scope' => 'Production agricultural industrial',
                    'commerce_scope' => 'Commerce',
                    'transport_scope' => 'Goods and passenger transportation',
                    'construction_scope' => 'Construction and repairs',
                    'wood_exploitation_scope' => 'Exploitation and trading of trees',
                    'extraction_filling_scope' => 'Soil/Rock extraction & filling',
                    'entertainment_scope' => 'Entertainment service',
                    'consultancy_scope' => 'Legal/Consultancy services',
                    'brokers_agents_scope' => 'Brokers or agents',
                    'real_estate_dev_sale_scope' => 'Development of land/building',
                    'other_service_scope' => 'Other services',
                    'tobacco_scope' => 'Tobacco companies',
                    'mining_activity_scope' => 'Mining activities',
                    'sez_developer_scope' => 'SEZ developer',
                    'sez_investor_scope' => 'SEZ investor',
                    'prod_industry_scope' => 'Productive Industry',
                    'tourism_scope' => 'Tourism Services',
                    'public_health_scope' => 'Public Health',
                    'edu_scope' => 'Education Services',
                    'sport_scope' => 'Sport Activities',
                    'real_estate_scope' => 'Real Estate'
                ];
                foreach ($scopes as $key => $label): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="p-2 border rounded bg-light">
                        <label class="form-label small fw-bold d-block mb-1 text-truncate" title="<?= $label ?>"><?= $label ?></label>
                        <select name="<?= $key ?>" class="form-select form-select-sm">
                            <option value="2" <?= $record[$key] == 2 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $record[$key] == 1 ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 5. Incentive Details & Categories -->
            <div class="row">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-list me-2"></i> 5. Incentive & Category Details</h5></div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Date Grant Incentives</label>
                    <input type="date" name="incentive_grant_date" class="form-control" value="<?= $record['incentive_grant_date'] ?>">
                </div>
                <div class="col-md-9 mb-3">
                    <label class="form-label fw-bold">Incentive Tax Policy</label>
                    <textarea name="incentive_tax_policy" class="form-control" rows="1"><?= htmlspecialchars($record['incentive_tax_policy']) ?></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Investor Fund Rate</label>
                    <input type="text" name="investor_fund_rate" class="form-control" value="<?= htmlspecialchars($record['investor_fund_rate']) ?>">
                </div>
            </div>

            <div class="card bg-light border-0 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle me-1"></i> Manage Industry Categories (Multiple)</h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="small fw-bold">Main Category</label>
                            <select class="form-select form-select-sm" id="main_category_select">
                                <option value="">Select Category</option>
                                <?php foreach ($moic_categories as $mc): ?>
                                <option value="<?= $mc['id'] ?>"><?= htmlspecialchars($mc['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold">Industry Sub Category</label>
                            <select class="form-select form-select-sm" id="sub_category_select" disabled>
                                <option value="">Select Sub Category</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary btn-sm w-100" id="add_category_btn">
                                <i class="fas fa-plus me-1"></i> Add to List
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered bg-white mb-0" id="selected_categories_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Main Category</th>
                                    <th>Sub Category</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cat_rows)): ?>
                                <tr id="no_cat_msg"><td colspan="3" class="text-center text-muted small">No categories added yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($cat_rows as $idx => $crow): ?>
                                    <tr data-main-id="<?= $crow['main_category_id'] ?>" data-sub-id="<?= $crow['sub_category_id'] ?>">
                                        <td><?= htmlspecialchars($crow['category_name']) ?></td>
                                        <td><?= htmlspecialchars($crow['sub_category_name'] ?: 'N/A') ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove(); syncCategoriesData();">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <input type="hidden" name="categories_data" id="categories_data">

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="repo_moic.php" class="btn btn-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5"><i class="fas fa-save me-2"></i> Update Enterprise Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function loadDistricts(provinceId, selectedDistrictId = null, selectedVillageId = null) {
    const districtSelect = document.getElementById('district_select');
    const villageSelect = document.getElementById('village_select');
    
    districtSelect.innerHTML = '<option value="">Loading...</option>';
    districtSelect.disabled = true;
    villageSelect.innerHTML = '<option value="">Select Village</option>';
    villageSelect.disabled = true;

    if (!provinceId) {
        districtSelect.innerHTML = '<option value="">Select District</option>';
        return;
    }

    fetch(`get_districts.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            districtSelect.innerHTML = '<option value="">Select District</option>';
            data.forEach(d => {
                const selected = (selectedDistrictId == d.id) ? 'selected' : '';
                districtSelect.innerHTML += `<option value="${d.id}" data-code="${d.district_code}" ${selected}>${d.district_name}</option>`;
            });
            districtSelect.disabled = false;
            
            if (selectedDistrictId) {
                const selectedOption = Array.from(districtSelect.options).find(opt => opt.value == selectedDistrictId);
                const districtCode = selectedOption ? selectedOption.getAttribute('data-code') : null;
                if (districtCode) {
                    loadVillages(districtCode, selectedVillageId);
                }
            }
        });
}

function loadVillages(districtCode, selectedVillageId = null) {
    const villageSelect = document.getElementById('village_select');

    villageSelect.innerHTML = '<option value="">Loading...</option>';
    villageSelect.disabled = true;

    if (!districtCode) {
        villageSelect.innerHTML = '<option value="">Select Village</option>';
        return;
    }

    fetch(`get_villages.php?district_code=${districtCode}`)
        .then(response => response.json())
        .then(data => {
            villageSelect.innerHTML = '<option value="">Select Village</option>';
            data.forEach(v => {
                const selected = (selectedVillageId == v.id) ? 'selected' : '';
                villageSelect.innerHTML += `<option value="${v.id}" ${selected}>${v.village_name}</option>`;
            });
            villageSelect.disabled = false;
        });
}

function loadSubCategories(categoryId, selectedSubId = null) {
    const subSelect = document.getElementById('sub_category_select');
    
    subSelect.innerHTML = '<option value="">Loading...</option>';
    subSelect.disabled = true;

    if (!categoryId) {
        subSelect.innerHTML = '<option value="">Select Sub Category</option>';
        return;
    }

    fetch(`get_moic_sub_categories.php?main_category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            subSelect.innerHTML = '<option value="">Select Sub Category</option>';
            data.forEach(s => {
                const selected = (selectedSubId == s.id) ? 'selected' : '';
                subSelect.innerHTML += `<option value="${s.id}" ${selected}>${s.sub_category_name}</option>`;
            });
            subSelect.disabled = false;
        });
}

function syncCategoriesData() {
    const selected = [];
    document.querySelectorAll('#selected_categories_table tbody tr[data-main-id]').forEach(tr => {
        selected.push({
            main_id: tr.dataset.mainId,
            sub_id: tr.dataset.subId !== "null" ? tr.dataset.subId : null
        });
    });
    document.getElementById('categories_data').value = JSON.stringify(selected);
    
    if (selected.length === 0) {
        document.getElementById('selected_categories_table').querySelector('tbody').innerHTML = '<tr id="no_cat_msg"><td colspan="3" class="text-center text-muted small">No categories added yet.</td></tr>';
    }
}

document.getElementById('province_select').addEventListener('change', function() {
    loadDistricts(this.value);
});

document.getElementById('district_select').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const districtCode = selectedOption.getAttribute('data-code');
    loadVillages(districtCode);
});

document.getElementById('main_category_select').addEventListener('change', function() {
    loadSubCategories(this.value);
});

document.getElementById('add_category_btn').addEventListener('click', function() {
    const mainSelect = document.getElementById('main_category_select');
    const subSelect = document.getElementById('sub_category_select');
    
    if (!mainSelect.value) { alert("Please select a Main Category"); return; }
    
    const mainId = mainSelect.value;
    const mainName = mainSelect.options[mainSelect.selectedIndex].text;
    const subId = subSelect.value || null;
    const subName = subId ? subSelect.options[subSelect.selectedIndex].text : "N/A";
    
    // Check duplicates
    let exists = false;
    document.querySelectorAll('#selected_categories_table tbody tr[data-main-id]').forEach(tr => {
        if (tr.dataset.mainId == mainId && (tr.dataset.subId == subId || (tr.dataset.subId == "null" && subId === null))) {
            exists = true;
        }
    });
    
    if (exists) {
        alert("This category pair is already added.");
        return;
    }
    
    const noMsg = document.getElementById('no_cat_msg');
    if (noMsg) noMsg.remove();
    
    const row = `<tr data-main-id="${mainId}" data-sub-id="${subId || 'null'}">
        <td>${mainName}</td>
        <td>${subName}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove(); syncCategoriesData();">
                <i class="fas fa-times-circle"></i>
            </button>
        </td>
    </tr>`;
    document.querySelector('#selected_categories_table tbody').insertAdjacentHTML('beforeend', row);
    syncCategoriesData();
});

// Initial load
<?php if ($record['province_id']): ?>
loadDistricts(<?= $record['province_id'] ?>, <?= $record['district_id'] ?: 'null' ?>, <?= $record['village_id'] ?: 'null' ?>);
<?php endif; ?>

syncCategoriesData();
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
