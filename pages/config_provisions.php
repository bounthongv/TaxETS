<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$message = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'add_provision') {
            $stmt = $pdo->prepare("INSERT INTO profit_provisions (provision_number, legal_reference, description, target_rate, is_exemption, start_year, end_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['provision_number'], $_POST['legal_reference'], $_POST['description'],
                $_POST['target_rate'] !== '' ? $_POST['target_rate'] : null,
                isset($_POST['is_exemption']) ? 1 : 0,
                (int)$_POST['start_year'], (int)$_POST['end_year']
            ]);
            $message = "Provision added.";
        } elseif ($_POST['action'] == 'delete_provision') {
            $pdo->prepare("DELETE FROM profit_provisions WHERE id = ?")->execute([$_POST['id']]);
            $message = "Provision deleted.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = 'danger';
    }
}

$provisions = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM profit_provision_conditions WHERE provision_id = p.id) as rule_count FROM profit_provisions p ORDER BY id ASC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-gavel me-2"></i> Tax Provisions (TE Classification)</h2>
      <p class="text-muted">Manage the legal provisions used to classify Tax Expenditures.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProvModal"><i class="fas fa-plus me-1"></i> Add Provision</button>
  </div>
</div>
<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<div class="card">
  <div class="card-body">
    <table class="table table-bordered table-hover datatable w-100">
      <thead class="table-light"><tr><th>Prov #</th><th>Legal Reference</th><th>Period</th><th>Description</th><th>Target Rate</th><th>Rules</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($provisions as $r): ?>
        <tr>
          <td class="fw-bold text-center"><?= htmlspecialchars($r['provision_number']) ?></td>
          <td><?= htmlspecialchars($r['legal_reference']) ?></td>
          <td><?= $r['start_year'] ?? 2000 ?>-<?= $r['end_year'] ?? 2099 ?></td>
          <td><?= htmlspecialchars($r['description']) ?></td>
          <td>
            <?php if ($r['is_exemption']): ?><span class="badge bg-success">Exemption (0%)</span>
            <?php elseif ($r['target_rate'] !== null): ?><span class="badge bg-info text-dark"><?= $r['target_rate'] ?>%</span>
            <?php else: ?><span class="text-muted">Dynamic</span><?php endif; ?>
          </td>
          <td class="text-center"><span class="badge bg-<?= $r['rule_count'] > 0 ? 'primary' : 'secondary' ?> rounded-pill"><?= $r['rule_count'] ?> rules</span></td>
          <td>
            <a href="config_rules.php?provision_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-cogs"></i> Rules</a>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete provision and all rules?')">
              <input type="hidden" name="action" value="delete_provision">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addProvModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add_provision">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add New Provision</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-3"><label>Provision #</label><input type="text" name="provision_number" class="form-control" required placeholder="1A"></div>
          <div class="col-5"><label>Legal Reference</label><input type="text" name="legal_reference" class="form-control" required placeholder="IPL Art. 9 Sect 1"></div>
          <div class="col-4"><label>Period</label>
            <div class="input-group"><input type="number" name="start_year" class="form-control" value="2000" required><span class="input-group-text">-</span><input type="number" name="end_year" class="form-control" value="2099" required></div>
          </div>
        </div>
        <div class="mb-3"><label>Description</label><input type="text" name="description" class="form-control" required placeholder="Description of this tax relief"></div>
        <div class="row mb-3 align-items-end">
          <div class="col-6"><label>Target Rate (%) <small class="text-muted">(Optional)</small></label><input type="number" step="0.01" name="target_rate" class="form-control" placeholder="e.g. 5"></div>
          <div class="col-6"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="is_exemption" id="isExemption"><label class="form-check-label text-success fw-bold" for="isExemption">Full Tax Exemption (0%)</label></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Provision</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
