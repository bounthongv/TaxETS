<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM repo_gdp_revenue WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

$records = $pdo->query("SELECT * FROM repo_gdp_revenue ORDER BY gdp_year DESC")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-chart-bar me-2"></i> GDP & Revenue Repository</h2>
            <p class="text-muted">Manage yearly GDP and Revenue values.</p>
        </div>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i> Add Record</button>
            <a href="import_gdp.php" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Import from Excel</a>
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
                    <th>Year</th>
                    <th>GDP Value (Billions)</th>
                    <th>Revenue Value</th>
                    <th>Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td class="fw-bold"><?= $r['gdp_year'] ?></td>
                    <td class="text-end"><?= number_format($r['gdp_value'], 2) ?></td>
                    <td class="text-end"><?= number_format($r['revenue_value'], 0) ?></td>
                    <td><?= htmlspecialchars($r['note']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-id="<?= $r['id'] ?>" 
                                data-year="<?= $r['gdp_year'] ?>"
                                data-gdp="<?= $r['gdp_value'] ?>"
                                data-rev="<?= $r['revenue_value'] ?>"
                                data-note="<?= htmlspecialchars($r['note']) ?>">
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="save_gdp.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add GDP Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">GDP Year</label>
                        <input type="number" name="gdp_year" id="edit_year" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GDP Value (Billions)</label>
                        <input type="number" step="0.01" name="gdp_value" id="edit_gdp" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Revenue Value</label>
                        <input type="number" name="revenue_value" id="edit_rev" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" id="edit_note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_year').value = this.dataset.year;
        document.getElementById('edit_gdp').value = this.dataset.gdp;
        document.getElementById('edit_rev').value = this.dataset.rev;
        document.getElementById('edit_note').value = this.dataset.note;
        document.getElementById('modalTitle').innerText = "Edit GDP Record";
        new bootstrap.Modal(document.getElementById('addModal')).show();
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
