<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle Form Actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_main") {
                $stmt = $pdo->prepare("INSERT INTO moic_categories (category_name) VALUES (?)");
                $stmt->execute([$_POST["category_name"]]);
                $message = "Main category added.";
            } elseif ($_POST["action"] === "edit_main") {
                $stmt = $pdo->prepare("UPDATE moic_categories SET category_name = ? WHERE id = ?");
                $stmt->execute([$_POST["category_name"], $_POST["id"]]);
                $message = "Main category updated.";
            } elseif ($_POST["action"] === "add_sub") {
                $stmt = $pdo->prepare("INSERT INTO moic_sub_categories (main_category_id, sub_category_name) VALUES (?, ?)");
                $stmt->execute([$_POST["main_id"], $_POST["sub_name"]]);
                $message = "Sub category added.";
            } elseif ($_POST["action"] === "delete_main") {
                $pdo->prepare("DELETE FROM moic_categories WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Main category deleted.";
            } elseif ($_POST["action"] === "delete_sub") {
                $pdo->prepare("DELETE FROM moic_sub_categories WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Sub category deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$main_categories = $pdo->query("SELECT * FROM moic_categories ORDER BY category_name")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-tags me-2"></i> MOIC Industry Categories</h2>
      <p class="text-muted">Manage main industry sectors and their corresponding sub-categories.</p>
    </div>
    <div>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#mainModal" onclick="clearMainForm()">
        <i class="fas fa-plus me-1"></i> Add Main Category
      </button>
    </div>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold"><i class="fas fa-list me-1"></i> Main Categories</div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Name</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($main_categories as $m): ?>
                            <tr class="main-row" data-id="<?= $m['id'] ?>" style="cursor: pointer;">
                                <td class="ps-3 fw-bold text-muted"><?= $m['id'] ?></td>
                                <td><?= htmlspecialchars($m['category_name']) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-link text-primary p-0 me-2" onclick="event.stopPropagation(); editMain(<?= json_encode($m) ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-link text-danger p-0" onclick="event.stopPropagation(); deleteItem('main', <?= $m['id'] ?>, '<?= addslashes($m['category_name']) ?>')"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-project-diagram me-1"></i> Sub Categories for: <span id="selected_main_name" class="text-primary">Select a Category</span></span>
                <button class="btn btn-sm btn-success shadow-sm" id="add_sub_btn" data-bs-toggle="modal" data-bs-target="#subModal" disabled>
                    <i class="fas fa-plus me-1"></i> Add Sub
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="min-height: 200px;">
                    <table class="table table-hover mb-0" id="sub_table">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Sub Category Name</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr><td colspan="3" class="text-center text-muted py-5">Please select a main category from the left to view sub-categories.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Category Modal -->
<div class="modal fade" id="mainModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainTitle">Add Main Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="mainAction" value="add_main">
                <input type="hidden" name="id" id="mainId">
                <div class="mb-3">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="category_name" id="mainNameInput" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Sub Category Modal -->
<div class="modal fade" id="subModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_sub">
                <input type="hidden" name="main_id" id="targetMainId">
                <div class="mb-3">
                    <label class="form-label">Sub Category Name *</label>
                    <input type="text" name="sub_name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Add Sub Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" id="delAction">
            <input type="hidden" name="id" id="delId">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delName"></strong>?</p>
                <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
let activeMainId = null;

document.querySelectorAll('.main-row').forEach(row => {
    row.addEventListener('click', function() {
        document.querySelectorAll('.main-row').forEach(r => r.classList.remove('table-primary'));
        this.classList.add('table-primary');
        
        activeMainId = this.dataset.id;
        const name = this.cells[1].innerText;
        
        document.getElementById('selected_main_name').innerText = name;
        document.getElementById('targetMainId').value = activeMainId;
        document.getElementById('add_sub_btn').disabled = false;
        
        loadSubTable(activeMainId);
    });
});

function loadSubTable(mainId) {
    const tbody = document.querySelector('#sub_table tbody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr>';
    
    fetch(`get_moic_sub_categories.php?main_category_id=${mainId}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No sub-categories found.</td></tr>';
                return;
            }
            tbody.innerHTML = "";
            data.forEach(s => {
                const row = `<tr>
                    <td class="ps-3 fw-bold text-muted">${s.id}</td>
                    <td>${s.sub_category_name}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-link text-danger p-0" onclick="deleteItem('sub', ${s.id}, '${s.sub_category_name.replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
                tbody.innerHTML += row;
            });
        });
}

function clearMainForm() {
    document.getElementById('mainAction').value = 'add_main';
    document.getElementById('mainTitle').innerText = 'Add Main Category';
    document.getElementById('mainId').value = '';
    document.getElementById('mainNameInput').value = '';
}

function editMain(m) {
    document.getElementById('mainAction').value = 'edit_main';
    document.getElementById('mainTitle').innerText = 'Edit Main Category';
    document.getElementById('mainId').value = m.id;
    document.getElementById('mainNameInput').value = m.category_name;
    new bootstrap.Modal(document.getElementById('mainModal')).show();
}

function deleteItem(type, id, name) {
    document.getElementById('delAction').value = (type === 'main') ? 'delete_main' : 'delete_sub';
    document.getElementById('delId').value = id;
    document.getElementById('delName').innerText = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
