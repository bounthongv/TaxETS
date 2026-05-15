<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM repo_sezo WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

if (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error']);
    $msg_type = "danger";
}

// Fetch Records
$records = $pdo->query("
    SELECT r.*, p.province_name, d.district_name, s.sector_name, bc.category_name 
    FROM repo_sezo r
    LEFT JOIN provinces p ON r.province_id = p.id
    LEFT JOIN districts d ON r.district_id = d.id
    LEFT JOIN business_sectors s ON r.sector_id = s.id
    LEFT JOIN business_categories bc ON r.category_id = bc.id
    ORDER BY r.id DESC
")->fetchAll();

$provinces = $pdo->query("SELECT id, province_name FROM provinces WHERE active = 1 ORDER BY province_name")->fetchAll();
$sectors = $pdo->query("SELECT id, sector_name FROM business_sectors WHERE active = 1 ORDER BY sector_name")->fetchAll();
$categories = $pdo->query("SELECT id, category_name FROM business_categories WHERE active = 1 ORDER BY category_name")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-industry me-2"></i> SEZO Data Repository</h2>
            <p class="text-muted">Manage SEZ Developers and Investors data.</p>
        </div>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i> Add New Record</button>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-bordered table-hover datatable">
            <thead class="table-light">
                <tr>
                    <th>TIN</th>
                    <th>Company Name</th>
                    <th>Location</th>
                    <th>Sector / Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r['tin']) ?></small></td>
                    <td><?= htmlspecialchars($r['company_name']) ?></td>
                    <td>
                        <small>
                            <?= htmlspecialchars($r['province_name'] ?? 'N/A') ?><br>
                            <span class="text-muted"><?= htmlspecialchars($r['district_name'] ?? '') ?></span>
                        </small>
                    </td>
                    <td>
                        <small>
                            S: <?= htmlspecialchars($r['sector_name'] ?? 'N/A') ?><br>
                            C: <?= htmlspecialchars($r['category_name'] ?? 'N/A') ?>
                        </small>
                    </td>
                    <td><span class="badge bg-<?= $r['type'] == 'Developer' ? 'info' : 'primary' ?>"><?= $r['type'] ?></span></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $r['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-id="<?= $r['id'] ?>" 
                                data-tin="<?= htmlspecialchars($r['tin']) ?>"
                                data-name="<?= htmlspecialchars($r['company_name']) ?>"
                                data-province="<?= $r['province_id'] ?>"
                                data-district="<?= $r['district_id'] ?>"
                                data-sector="<?= $r['sector_id'] ?>"
                                data-category="<?= $r['category_id'] ?>"
                                data-type="<?= $r['type'] ?>"
                                data-active="<?= $r['is_active'] ?>"
                                data-remark="<?= htmlspecialchars($r['remark']) ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="save_sezo.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add SEZO Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">TIN (Tax ID)</label>
                        <input type="text" name="tin" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Province</label>
                            <select name="province_id" class="form-select province-select" required>
                                <option value="">Select Province</option>
                                <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['province_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">District</label>
                            <select name="district_id" class="form-select district-select" required disabled>
                                <option value="">Select District</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Sector</label>
                            <select name="sector_id" class="form-select" required>
                                <option value="">Select Sector</option>
                                <?php foreach ($sectors as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['sector_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeInvestor" value="Investor" checked>
                                <label class="form-check-label" for="typeInvestor">Investor</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeDeveloper" value="Developer">
                                <label class="form-check-label" for="typeDeveloper">Developer</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label" for="isActive">Active Status</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="save_sezo.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit SEZO Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">TIN (Tax ID)</label>
                        <input type="text" name="tin" id="edit_tin" class="form-control" required readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Province</label>
                            <select name="province_id" id="edit_province" class="form-select province-select" required>
                                <option value="">Select Province</option>
                                <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['province_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">District</label>
                            <select name="district_id" id="edit_district" class="form-select district-select" required>
                                <option value="">Select District</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Sector</label>
                            <select name="sector_id" id="edit_sector" class="form-select" required>
                                <option value="">Select Sector</option>
                                <?php foreach ($sectors as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['sector_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Category</label>
                            <select name="category_id" id="edit_category" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="edit_typeInvestor" value="Investor">
                                <label class="form-check-label" for="edit_typeInvestor">Investor</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="edit_typeDeveloper" value="Developer">
                                <label class="form-check-label" for="edit_typeDeveloper">Developer</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_active">
                            <label class="form-check-label" for="edit_active">Active Status</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="edit_remark" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.province-select').forEach(select => {
    select.addEventListener('change', function() {
        const provinceId = this.value;
        const districtSelect = this.closest('form').querySelector('.district-select');
        
        districtSelect.innerHTML = '<option value="">Loading...</option>';
        districtSelect.disabled = true;

        if (!provinceId) {
            districtSelect.innerHTML = '<option value="">Select District</option>';
            return;
        }

        fetch(`get_districts.php?province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                data.forEach(d => {
                    districtSelect.innerHTML += `<option value="${d.id}">${d.district_name}</option>`;
                });
                districtSelect.disabled = false;
                
                // If editing, select the correct district
                if (this.id === 'edit_province' && this.dataset.pendingDistrict) {
                    districtSelect.value = this.dataset.pendingDistrict;
                    delete this.dataset.pendingDistrict;
                }
            });
    });
});

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_tin').value = this.dataset.tin;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_sector').value = this.dataset.sector;
        document.getElementById('edit_category').value = this.dataset.category;
        document.getElementById('edit_active').checked = this.dataset.active == 1;
        document.getElementById('edit_remark').value = this.dataset.remark;
        
        if (this.dataset.type === 'Investor') {
            document.getElementById('edit_typeInvestor').checked = true;
        } else {
            document.getElementById('edit_typeDeveloper').checked = true;
        }

        const provinceSelect = document.getElementById('edit_province');
        provinceSelect.value = this.dataset.province;
        provinceSelect.dataset.pendingDistrict = this.dataset.district;
        provinceSelect.dispatchEvent(new Event('change'));

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
