<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDbConnection();
$message = ""; $msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] === "save") {
            $id = $_POST["id"] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE bm_natural_resource SET item_no=?, item_name=?, rate_percentage=?, start_year=?, end_year=?, active=? WHERE id=?");
                $stmt->execute([$_POST["item_no"], $_POST["item_name"], $_POST["rate"], $_POST["start"], $_POST["end"], isset($_POST["active"])?1:0, $id]);
                $message = "Updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO bm_natural_resource (item_no, item_name, rate_percentage, start_year, end_year, active) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$_POST["item_no"], $_POST["item_name"], $_POST["rate"], $_POST["start"], $_POST["end"], isset($_POST["active"])?1:0]);
                $message = "Added successfully.";
            }
        } elseif ($_POST["action"] === "delete") {
            $pdo->prepare("DELETE FROM bm_natural_resource WHERE id=?")->execute([$_POST["id"]]);
            $message = "Deleted.";
        }
    } catch (Exception $e) { $message = $e->getMessage(); $msg_type = "danger"; }
}

$data = $pdo->query("SELECT * FROM bm_natural_resource ORDER BY item_no ASC")->fetchAll();
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-tree me-2"></i> Natural Resource Repository</h2>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#editModal" onclick="clearForm()">
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

<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
              <thead class="bg-light text-muted small text-uppercase">
                  <tr>
                      <th class="ps-3" style="width: 100px;">Item No</th>
                      <th>Name</th>
                      <th class="text-center" style="width: 100px;">Rate</th>
                      <th class="text-center" style="width: 100px;">Status</th>
                      <th class="text-center" style="width: 100px;">Action</th>
                  </tr>
              </thead>
              <tbody class="small">
                <?php foreach ($data as $r): ?>
                <tr>
                    <td class="ps-3 fw-bold"><?= htmlspecialchars($r['item_no']) ?></td>
                    <td><?= htmlspecialchars($r['item_name']) ?></td>
                    <td class="text-center fw-bold text-primary"><?= number_format($r['rate_percentage'], 2) ?>%</td>
                    <td class="text-center">
                        <?= $r['active'] ? '<span class="badge bg-success-subtle text-success">Active</span>' : '<span class="badge bg-danger-subtle text-danger">Inactive</span>' ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick='edit(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($data)): ?>
                <tr><td colspan="5" class="text-center p-5 text-muted">No natural resource records found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item No</label>
                        <input type="text" name="item_no" id="item_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="item_name" id="item_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rate (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="rate" id="rate" class="form-control" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Start Year</label>
                            <input type="number" name="start" id="start" class="form-control" value="2026">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">End Year</label>
                            <input type="number" name="end" id="end" class="form-control" value="3000">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="active" checked>
                        <label class="form-check-label fw-bold">Active</label>
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
<script>
function clearForm() {
    document.getElementById('modalTitle').innerText = 'Add Record';
    document.getElementById('id').value=''; document.getElementById('item_no').value='';
    document.getElementById('item_name').value=''; document.getElementById('rate').value='';
}
function edit(d) {
    document.getElementById('modalTitle').innerText = 'Edit Record';
    document.getElementById('id').value=d.id; document.getElementById('item_no').value=d.item_no;
    document.getElementById('item_name').value=d.item_name; document.getElementById('rate').value=d.rate_percentage;
    document.getElementById('start').value=d.start_year; document.getElementById('end').value=d.end_year;
    document.getElementById('active').checked = d.active==1;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
