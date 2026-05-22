<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $data = [
            $_POST['type'],
            $_POST['provision_number'],
            $_POST['legal_basis'],
            $_POST['description'],
            $_POST['purpose'],
            $_POST['type_of_te'],
            $_POST['start_year'],
            $_POST['end_year']
        ];
        if (!empty($_POST['id'])) {
            $sql = "UPDATE bm_sez_provisions SET type=?, provision_number=?, legal_basis=?, description=?, purpose=?, type_of_te=?, start_year=?, end_year=? WHERE id=?";
            $data[] = $_POST['id'];
            $pdo->prepare($sql)->execute($data);
            $message = "Provision updated successfully.";
        } else {
            $sql = "INSERT INTO bm_sez_provisions (type, provision_number, legal_basis, description, purpose, type_of_te, start_year, end_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute($data);
            $message = "Provision added successfully.";
        }
    } elseif ($_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM bm_sez_provisions WHERE id = ?")->execute([$_POST['id']]);
        $message = "Provision deleted.";
    }
}

$provisions = $pdo->query("SELECT * FROM bm_sez_provisions ORDER BY type, provision_number ASC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3 align-items-center">
    <div class="col-md-8">
        <h2><i class="fas fa-building me-2 text-primary"></i> SEZ Developer/Investor Provisions</h2>
        <p class="text-muted">Manage tax incentives and provisions for SEZ Developers and Investors.</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#provModal" onclick="clearForm()">
            <i class="fas fa-plus me-2"></i> Add New Provision
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-dark small">
                <tr>
                    <th>Type</th>
                    <th>No.</th>
                    <th>Legal Basis</th>
                    <th>Description</th>
                    <th>Purpose</th>
                    <th>Start/End</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php foreach ($provisions as $p): ?>
                <tr>
                    <td><span class="badge <?= $p['type'] == 'Developer' ? 'bg-info' : 'bg-success' ?>"><?= $p['type'] ?></span></td>
                    <td class="fw-bold"><?= htmlspecialchars($p['provision_number']) ?></td>
                    <td><?= htmlspecialchars($p['legal_basis']) ?></td>
                    <td><div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($p['description']) ?>"><?= htmlspecialchars($p['description']) ?></div></td>
                    <td><?= htmlspecialchars($p['purpose']) ?></td>
                    <td><?= $p['start_year'] ?> - <?= $p['end_year'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editProv(<?= json_encode($p) ?>)'><i class="fas fa-edit"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this provision?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Provision Modal -->
<div class="modal fade" id="provModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Provision Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="prov_id">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Type</label>
            <select name="type" id="prov_type" class="form-select" required>
                <option value="Developer">Developer</option>
                <option value="Investor">Investor</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Provision Number</label>
            <input type="text" name="provision_number" id="prov_number" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">TE Type</label>
            <input type="text" name="type_of_te" id="prov_te_type" class="form-control" value="Exemption">
          </div>
          <div class="col-12">
            <label class="form-label fw-bold">Legal Basis</label>
            <input type="text" name="legal_basis" id="prov_basis" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" id="prov_desc" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Purpose</label>
            <input type="text" name="purpose" id="prov_purpose" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">Start Year</label>
            <input type="number" name="start_year" id="prov_start" class="form-control" value="2018">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">End Year</label>
            <input type="number" name="end_year" id="prov_end" class="form-control" value="2099">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Provision</button>
      </div>
    </form>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('prov_id').value = '';
    document.getElementById('prov_number').value = '';
    document.getElementById('prov_basis').value = '';
    document.getElementById('prov_desc').value = '';
    document.getElementById('prov_purpose').value = '';
    document.getElementById('prov_start').value = '2018';
    document.getElementById('prov_end').value = '2099';
}

function editProv(p) {
    document.getElementById('prov_id').value = p.id;
    document.getElementById('prov_type').value = p.type;
    document.getElementById('prov_number').value = p.provision_number;
    document.getElementById('prov_te_type').value = p.type_of_te;
    document.getElementById('prov_basis').value = p.legal_basis;
    document.getElementById('prov_desc').value = p.description;
    document.getElementById('prov_purpose').value = p.purpose;
    document.getElementById('prov_start').value = p.start_year;
    document.getElementById('prov_end').value = p.end_year;
    new bootstrap.Modal(document.getElementById('provModal')).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
