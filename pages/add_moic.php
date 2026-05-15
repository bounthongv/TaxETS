<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$provinces = $pdo->query("SELECT * FROM provinces WHERE active = 1 ORDER BY province_name")->fetchAll();
$moic_categories = $pdo->query("SELECT * FROM moic_categories WHERE active = 1 ORDER BY category_name")->fetchAll();
$enterprise_types = $pdo->query("SELECT * FROM enterprise_types WHERE active = 1 ORDER BY type_name")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><a href="repo_moic.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Add New MOIC Enterprise</h2>
        <p class="text-muted">Register a new enterprise record with full details from the Ministry of Industry and Commerce.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="save_moic.php" method="POST">
            <!-- 1. Basic Information -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-info-circle me-2"></i> 1. Basic Information</h5></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">TIN (Tax ID) <span class="text-danger">*</span></label>
                    <input type="text" name="tin" class="form-control" required placeholder="Enter 10-12 digit TIN">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" required placeholder="Enter full registered name">
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
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['province_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">District</label>
                    <select name="district_id" class="form-select district-select" id="district_select" disabled>
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
                    <textarea name="address" class="form-control" rows="2" placeholder="Unit, House No, Street..."></textarea>
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
                        <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Business License Date</label>
                    <input type="date" name="license_date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">First Revenue Date</label>
                    <input type="date" name="first_revenue_date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Staff Count</label>
                    <input type="number" name="business_size_staff" class="form-control" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Registered Capital (LAK)</label>
                    <input type="number" step="0.01" name="registered_capital" class="form-control" value="0.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Enterprise Holding VAT System</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="vat_system_status" id="vatToggle" value="1">
                        <label class="form-check-label" for="vatToggle">Yes (Holding VAT)</label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked value="1">
                        <label class="form-check-label fw-bold" for="isActive">Enterprise Active</label>
                    </div>
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
                            <option value="2">No</option>
                            <option value="1">Yes</option>
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
                    <input type="date" name="incentive_grant_date" class="form-control">
                </div>
                <div class="col-md-9 mb-3">
                    <label class="form-label fw-bold">Incentive Tax Policy</label>
                    <textarea name="incentive_tax_policy" class="form-control" rows="1" placeholder="Description of granted incentives..."></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Investor Fund Rate</label>
                    <input type="text" name="investor_fund_rate" class="form-control" placeholder="e.g. 100% Domestic">
                </div>
            </div>

            <div class="card bg-light border-0 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle me-1"></i> Add Industry Categories (Multiple)</h6>
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
                                <tr id="no_cat_msg"><td colspan="3" class="text-center text-muted small">No categories added yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="categories_data" id="categories_data">

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="repo_moic.php" class="btn btn-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-success px-5"><i class="fas fa-save me-2"></i> Save Enterprise Record</button>
            </div>
        </form>
    </div>
</div>

<script>
// Cascading Dropdowns for Location
document.getElementById('province_select').addEventListener('change', function() {
    const provinceId = this.value;
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
                districtSelect.innerHTML += `<option value="${d.id}" data-code="${d.district_code}">${d.district_name}</option>`;
            });
            districtSelect.disabled = false;
        });
});

document.getElementById('district_select').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const districtCode = selectedOption.getAttribute('data-code');
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
                villageSelect.innerHTML += `<option value="${v.id}">${v.village_name}</option>`;
            });
            villageSelect.disabled = false;
        });
});

// Cascading Dropdown for Categories
const selectedCategories = [];

document.getElementById('main_category_select').addEventListener('change', function() {
    const categoryId = this.value;
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
                subSelect.innerHTML += `<option value="${s.id}">${s.sub_category_name}</option>`;
            });
            subSelect.disabled = false;
        });
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
    if (selectedCategories.some(c => c.main_id == mainId && c.sub_id == subId)) {
        alert("This category pair is already added.");
        return;
    }
    
    selectedCategories.push({ main_id: mainId, sub_id: subId });
    updateCategoryTable();
});

function updateCategoryTable() {
    const tbody = document.querySelector('#selected_categories_table tbody');
    const hiddenInput = document.getElementById('categories_data');
    
    if (selectedCategories.length === 0) {
        tbody.innerHTML = '<tr id="no_cat_msg"><td colspan="3" class="text-center text-muted small">No categories added yet.</td></tr>';
        hiddenInput.value = "";
        return;
    }
    
    tbody.innerHTML = "";
    selectedCategories.forEach((cat, index) => {
        const mainName = document.querySelector(`#main_category_select option[value="${cat.main_id}"]`).text;
        const subName = cat.sub_id ? "..." : "N/A"; // Subname is harder to get without a full map, so we'll just use IDs for submission
        
        const row = `<tr>
            <td>${mainName}</td>
            <td>${cat.sub_id ? "Sub-Category ID: " + cat.sub_id : "N/A"}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeCategory(${index})">
                    <i class="fas fa-times-circle"></i>
                </button>
            </td>
        </tr>`;
        tbody.innerHTML += row;
    });
    
    hiddenInput.value = JSON.stringify(selectedCategories);
}

function removeCategory(index) {
    selectedCategories.splice(index, 1);
    updateCategoryTable();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
