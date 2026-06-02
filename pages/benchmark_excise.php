<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "save_excise") {
                $id = $_POST["id"] ?? null;
                $data = [
                    $_POST["category"],
                    $_POST["indicator"],
                    $_POST["product_name"],
                    $_POST["variable_name"],
                    $_POST["tax_rate"],
                    $_POST["effective_from"],
                    $_POST["effective_to"],
                    isset($_POST["is_active"]) ? 1 : 0
                ];

                if ($id) {
                    $sql = "UPDATE bm_excise SET category=?, indicator=?, product_name=?, variable_name=?, tax_rate=?, effective_from=?, effective_to=?, is_active=? WHERE id=?";
                    $data[] = $id;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $message = "Record updated successfully.";
                } else {
                    $sql = "INSERT INTO bm_excise (category, indicator, product_name, variable_name, tax_rate, effective_from, effective_to, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $message = "Record added successfully.";
                }
            } elseif ($_POST["action"] === "delete_excise") {
                $stmt = $pdo->prepare("DELETE FROM bm_excise WHERE id = ?");
                $stmt->execute([$_POST["id"]]);
                $message = "Record deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch all records
$all_records = $pdo->query("SELECT * FROM bm_excise ORDER BY indicator ASC, product_name ASC")->fetchAll();

// Group by category
$categories = [
    'Fuel' => [],
    'Vehicle' => [],
    'Services' => [],
    'Other Product' => []
];

foreach ($all_records as $r) {
    if (isset($categories[$r['category']])) {
        $categories[$r['category']][] = $r;
    } else {
        $categories['Other Product'][] = $r;
    }
}

$active_tab = $_GET['tab'] ?? 'Fuel';

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-gas-pump me-2"></i> Excise Tax Benchmark</h2>
            <p class="text-muted">Configure standard excise tax rates for Fuel, Vehicles, Services, and Other Products.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#exciseModal" onclick="clearForm()">
            <i class="fas fa-plus me-1"></i> Add Record
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 pt-3">
        <ul class="nav nav-tabs card-header-tabs" id="exciseTabs" role="tablist">
            <?php 
            $icons = [
                'Fuel' => 'fas fa-gas-pump',
                'Vehicle' => 'fas fa-car',
                'Services' => 'fas fa-concierge-bell',
                'Other Product' => 'fas fa-box'
            ];
            foreach (array_keys($categories) as $cat): 
            ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab == $cat ? 'active' : '' ?> fw-bold" 
                        id="<?= str_replace(' ', '', $cat) ?>-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#<?= str_replace(' ', '', $cat) ?>-content" 
                        type="button" role="tab">
                    <i class="<?= $icons[$cat] ?? 'fas fa-list' ?> me-2"></i><?= $cat ?> 
                    <span class="badge rounded-pill bg-light text-dark border ms-1" style="font-size: 0.7rem;"><?= count($categories[$cat]) ?></span>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card-body pt-4">
        <div class="tab-content" id="exciseTabsContent">
            <?php foreach ($categories as $cat => $records): ?>
            <div class="tab-pane fade <?= $active_tab == $cat ? 'show active' : '' ?>" 
                 id="<?= str_replace(' ', '', $cat) ?>-content" 
                 role="tabpanel">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle datatable w-100">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th style="width: 80px;">Indicator</th>
                                <th>Product / Service Description</th>
                                <th class="text-center" style="width: 100px;">Tax Rate</th>
                                <th class="text-center">Effective Period</th>
                                <th class="text-center" style="width: 80px;">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $r): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($r['indicator'] ?? '-') ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['product_name']) ?></div>
                                    <?php if ($r['variable_name']): ?>
                                    <small class="text-muted"><?= htmlspecialchars($r['variable_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6"><?= number_format($r['tax_rate'], 2) ?>%</span>
                                </td>
                                <td class="text-center">
                                    <small class="d-block text-muted">From: <?= $r['effective_from'] ?></small>
                                    <small class="d-block text-muted">To: <?= $r['effective_to'] == '3000-01-01' ? 'Indefinite' : $r['effective_to'] ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick='editRecord(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $r['id'] ?>, '<?= htmlspecialchars($r['product_name'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="exciseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Excise Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_excise">
                    <input type="hidden" name="id" id="excise_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category" id="excise_category" class="form-select" required>
                                <option value="Fuel">Fuel</option>
                                <option value="Vehicle">Vehicle</option>
                                <option value="Services">Services</option>
                                <option value="Other Product">Other Product</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Indicator (Optional)</label>
                            <input type="text" name="indicator" id="excise_indicator" class="form-control" placeholder="e.g. 1.1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Product / Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" id="excise_product_name" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Variable Name (Internal)</label>
                            <input type="text" name="variable_name" id="excise_variable_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tax Rate (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="tax_rate" id="excise_tax_rate" class="form-control" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Effective From</label>
                            <input type="date" name="effective_from" id="excise_effective_from" class="form-control" value="1970-01-01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Effective To</label>
                            <input type="date" name="effective_to" id="excise_effective_to" class="form-control" value="3000-01-01">
                        </div>
                    </div>

                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="excise_is_active" checked>
                        <label class="form-check-label fw-bold">Is Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_excise">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_name"></strong>?</p>
                    <p class="small text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').innerText = 'Add Excise Record';
    document.getElementById('excise_id').value = '';
    document.getElementById('excise_category').value = 'Fuel';
    document.getElementById('excise_indicator').value = '';
    document.getElementById('excise_product_name').value = '';
    document.getElementById('excise_variable_name').value = '';
    document.getElementById('excise_tax_rate').value = '';
    document.getElementById('excise_effective_from').value = '1970-01-01';
    document.getElementById('excise_effective_to').value = '3000-01-01';
    document.getElementById('excise_is_active').checked = true;
}

function editRecord(data) {
    document.getElementById('modalTitle').innerText = 'Edit Excise Record';
    document.getElementById('excise_id').value = data.id;
    document.getElementById('excise_category').value = data.category;
    document.getElementById('excise_indicator').value = data.indicator || '';
    document.getElementById('excise_product_name').value = data.product_name;
    document.getElementById('excise_variable_name').value = data.variable_name || '';
    document.getElementById('excise_tax_rate').value = data.tax_rate;
    document.getElementById('excise_effective_from').value = data.effective_from;
    document.getElementById('excise_effective_to').value = data.effective_to;
    document.getElementById('excise_is_active').checked = parseInt(data.is_active) === 1;
    new bootstrap.Modal(document.getElementById('exciseModal')).show();
}

function deleteRecord(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').innerText = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Adjust DataTable search placeholder for consistency
$(document).ready(function() {
    // Re-initialize for each tab if needed, although class .datatable handles it
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
