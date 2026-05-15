<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM repo_molsw WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

// Fetch Records
$records = $pdo->query("SELECT * FROM repo_molsw ORDER BY id DESC")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-users me-2"></i> MOLSW Data Repository</h2>
            <p class="text-muted">Manage labor data (Staff count) imported from the Ministry of Labour and Social Welfare.</p>
        </div>
        <div>
            <a href="import_molsw.php" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Import from Excel</a>
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
                    <th>Staff Count</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r['tin']) ?></small></td>
                    <td><?= htmlspecialchars($r['company_name']) ?></td>
                    <td class="text-end"><?= number_format($r['staff_count']) ?></td>
                    <td><?= $r['updated_at'] ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-id="<?= $r['id'] ?>" 
                                data-tin="<?= htmlspecialchars($r['tin']) ?>"
                                data-name="<?= htmlspecialchars($r['company_name']) ?>"
                                data-staff="<?= $r['staff_count'] ?>"
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
        <form action="save_molsw.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add MOLSW Record</h5>
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
                    <div class="mb-3">
                        <label class="form-label">Staff Count</label>
                        <input type="number" name="staff_count" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" class="form-control" rows="3"></textarea>
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
        <form action="save_molsw.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit MOLSW Record</h5>
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
                    <div class="mb-3">
                        <label class="form-label">Staff Count</label>
                        <input type="number" name="staff_count" id="edit_staff" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="edit_remark" class="form-control" rows="3"></textarea>
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
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_tin').value = this.dataset.tin;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_staff').value = this.dataset.staff;
        document.getElementById('edit_remark').value = this.dataset.remark;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
