<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM bm_art9_activities WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

// Fetch Records with joining profit_provisions (using as tax rules)
$records = $pdo->query("
    SELECT r.*, p.provision_number, p.description as rule_desc
    FROM bm_art9_activities r
    LEFT JOIN profit_provisions p ON r.tax_rule_id = p.id
    ORDER BY r.project_id ASC
")->fetchAll();

$tax_rules = $pdo->query("SELECT id, provision_number, description FROM profit_provisions ORDER BY provision_number")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-list-ol me-2"></i> Activities outlined in Art 9 of IPL</h2>
            <p class="text-muted">Manage the specific project categories and linked tax rules as defined in Article 9 of the Investment Promotion Law.</p>
        </div>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i> Add New Activity</button>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0 datatable">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th width="100">Project ID</th>
                    <th>Short Name</th>
                    <th>Short Name EN</th>
                    <th>Linked Tax Rule</th>
                    <th width="100" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php foreach ($records as $r): ?>
                <tr>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($r['project_id']) ?></td>
                    <td><?= htmlspecialchars($r['short_name']) ?></td>
                    <td><?= htmlspecialchars($r['short_name_en']) ?></td>
                    <td>
                        <?php if ($r['tax_rule_id']): ?>
                            <span class="badge bg-info text-dark">Rule <?= $r['provision_number'] ?></span>
                            <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($r['rule_desc']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-id="<?= $r['id'] ?>" 
                                data-pid="<?= htmlspecialchars($r['project_id']) ?>"
                                data-name="<?= htmlspecialchars($r['short_name']) ?>"
                                data-name-en="<?= htmlspecialchars($r['short_name_en']) ?>"
                                data-content="<?= htmlspecialchars($r['content']) ?>"
                                data-info="<?= htmlspecialchars($r['more_info']) ?>"
                                data-rule="<?= $r['tax_rule_id'] ?>">
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
    <div class="modal-dialog modal-lg">
        <form action="save_art9_activity.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Activity Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Project ID <span class="text-danger">*</span></label>
                            <input type="text" name="project_id" class="form-control" required placeholder="e.g. 1, 2, 3...">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Short Name <span class="text-danger">*</span></label>
                            <input type="text" name="short_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Short Name EN</label>
                            <input type="text" name="short_name_en" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Content</label>
                        <textarea name="content" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">More Info</label>
                        <textarea name="more_info" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tax Rule (Reference Profit Provision)</label>
                        <select name="tax_rule_id" class="form-select">
                            <option value="">-- Select Rule --</option>
                            <?php foreach ($tax_rules as $tr): ?>
                            <option value="<?= $tr['id'] ?>">Rule <?= $tr['provision_number'] ?>: <?= htmlspecialchars(substr($tr['description'], 0, 80)) ?>...</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="save_art9_activity.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Activity Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Project ID <span class="text-danger">*</span></label>
                            <input type="text" name="project_id" id="edit_pid" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Short Name <span class="text-danger">*</span></label>
                            <input type="text" name="short_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Short Name EN</label>
                            <input type="text" name="short_name_en" id="edit_name_en" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Content</label>
                        <textarea name="content" id="edit_content" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">More Info</label>
                        <textarea name="more_info" id="edit_info" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tax Rule (Reference Profit Provision)</label>
                        <select name="tax_rule_id" id="edit_rule" class="form-select">
                            <option value="">-- Select Rule --</option>
                            <?php foreach ($tax_rules as $tr): ?>
                            <option value="<?= $tr['id'] ?>">Rule <?= $tr['provision_number'] ?>: <?= htmlspecialchars(substr($tr['description'], 0, 80)) ?>...</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_pid').value = this.dataset.pid;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_name_en').value = this.dataset.nameEn;
        document.getElementById('edit_content').value = this.dataset.content;
        document.getElementById('edit_info').value = this.dataset.info;
        document.getElementById('edit_rule').value = this.dataset.rule;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
