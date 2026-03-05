<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$message = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'add_standard') {
            $stmt = $pdo->prepare("INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['start_year'], $_POST['end_year'], $_POST['category'], $_POST['rate_percentage']]);
            $message = "Standard rate added successfully.";
        } elseif ($_POST['action'] == 'delete_standard') {
            $pdo->prepare("DELETE FROM bm_profit_standard WHERE id = ?")->execute([$_POST['id']]);
            $message = "Rule deleted.";
        } elseif ($_POST['action'] == 'add_mandatory') {
            $stmt = $pdo->prepare("INSERT INTO bm_profit_mandatory (start_year, end_year, sector, sub_sector, profit_base_rate) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['start_year'], $_POST['end_year'], $_POST['sector'], $_POST['sub_sector'], $_POST['profit_base_rate']]);
            $message = "Mandatory rate added.";
        } elseif ($_POST['action'] == 'delete_mandatory') {
            $pdo->prepare("DELETE FROM bm_profit_mandatory WHERE id = ?")->execute([$_POST['id']]);
            $message = "Rule deleted.";
        } elseif ($_POST['action'] == 'add_sme') {
            $stmt = $pdo->prepare("INSERT INTO bm_profit_sme (start_year, end_year, sector, turnover_min, turnover_max, rate_percentage) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['start_year'], $_POST['end_year'], $_POST['sector'], $_POST['turnover_min'], $_POST['turnover_max'] ?: null, $_POST['rate_percentage']]);
            $message = "SME rate added.";
        } elseif ($_POST['action'] == 'delete_sme') {
            $pdo->prepare("DELETE FROM bm_profit_sme WHERE id = ?")->execute([$_POST['id']]);
            $message = "Rule deleted.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = 'danger';
    }
}

$std = $pdo->query("SELECT * FROM bm_profit_standard ORDER BY start_year DESC")->fetchAll();
$mand = $pdo->query("SELECT * FROM bm_profit_mandatory ORDER BY start_year DESC")->fetchAll();
$sme = $pdo->query("SELECT * FROM bm_profit_sme ORDER BY start_year DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-percent me-2"></i> Benchmark Rates Configuration</h2>
    <p class="text-muted">Manage standard, mandatory, and SME benchmark rates for Profit Tax.</p>
  </div>
</div>
<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
  <?= htmlspecialchars($message) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header bg-white">
    <ul class="nav nav-tabs card-header-tabs" id="rateTabs">
      <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#stdPane">Standard PT</button></li>
      <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#mandPane">Mandatory / Base</button></li>
      <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#smePane">SME / Micro</button></li>
    </ul>
  </div>
  <div class="card-body">
    <div class="tab-content">
      <!-- Standard PT Tab -->
      <div class="tab-pane fade show active" id="stdPane">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Standard Profit Tax Rates</h5>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStdModal"><i class="fas fa-plus me-1"></i> Add</button>
        </div>
        <table class="table table-bordered table-hover datatable w-100">
          <thead class="table-light"><tr><th>Period</th><th>Category</th><th>Rate (%)</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($std as $r): ?>
            <tr>
              <td><?= $r['start_year'] ?> - <?= $r['end_year'] ?></td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($r['category']) ?></span></td>
              <td class="fw-bold"><?= $r['rate_percentage'] ?>%</td>
              <td>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                  <input type="hidden" name="action" value="delete_standard">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Mandatory Tab -->
      <div class="tab-pane fade" id="mandPane">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Profit Base Rates (Non-VAT Holders)</h5>
          <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addMandModal"><i class="fas fa-plus me-1"></i> Add</button>
        </div>
        <table class="table table-bordered table-hover datatable w-100">
          <thead class="table-light"><tr><th>Period</th><th>Sector</th><th>Sub-Sector</th><th>Profit Base Rate (%)</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($mand as $r): ?>
            <tr>
              <td><?= $r['start_year'] ?> - <?= $r['end_year'] ?></td>
              <td><?= htmlspecialchars($r['sector']) ?></td>
              <td><?= htmlspecialchars($r['sub_sector']) ?></td>
              <td class="fw-bold"><?= $r['profit_base_rate'] ?>%</td>
              <td>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                  <input type="hidden" name="action" value="delete_mandatory">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- SME Tab -->
      <div class="tab-pane fade" id="smePane">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">SME / Micro Enterprise Rates</h5>
          <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#addSmeModal"><i class="fas fa-plus me-1"></i> Add</button>
        </div>
        <table class="table table-bordered table-hover datatable w-100">
          <thead class="table-light"><tr><th>Period</th><th>Sector</th><th>Turnover Range (LAK)</th><th>Rate (%)</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($sme as $r): ?>
            <tr>
              <td><?= $r['start_year'] ?> - <?= $r['end_year'] ?></td>
              <td><?= htmlspecialchars($r['sector']) ?></td>
              <td><?= number_format($r['turnover_min']) ?> - <?= $r['turnover_max'] ? number_format($r['turnover_max']) : 'Any' ?></td>
              <td class="fw-bold"><?= $r['rate_percentage'] ?>%</td>
              <td>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                  <input type="hidden" name="action" value="delete_sme">
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
  </div>
</div>

<!-- Add Standard Modal -->
<div class="modal fade" id="addStdModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add_standard">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Standard PT Rate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-6"><label>Start Year</label><input type="number" name="start_year" class="form-control" required value="2020"></div>
          <div class="col-6"><label>End Year</label><input type="number" name="end_year" class="form-control" required value="2099"></div>
        </div>
        <div class="mb-3"><label>Category</label>
          <select name="category" class="form-select" required>
            <option value="Standard">Standard</option>
            <option value="Tobacco">Tobacco</option>
            <option value="Mining/Electricity">Mining / Electricity</option>
          </select>
        </div>
        <div class="mb-3"><label>Rate (%)</label><input type="number" step="0.01" name="rate_percentage" class="form-control" required placeholder="20"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div></div>
</div>

<!-- Add Mandatory Modal -->
<div class="modal fade" id="addMandModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add_mandatory">
      <div class="modal-header bg-warning"><h5 class="modal-title">Add Profit Base Rate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-6"><label>Start Year</label><input type="number" name="start_year" class="form-control" required value="2020"></div>
          <div class="col-6"><label>End Year</label><input type="number" name="end_year" class="form-control" required value="2099"></div>
        </div>
        <div class="mb-3"><label>Sector</label><input type="text" name="sector" class="form-control" required placeholder="e.g. Commerce"></div>
        <div class="mb-3"><label>Sub-Sector (Optional)</label><input type="text" name="sub_sector" class="form-control" placeholder="Specific type"></div>
        <div class="mb-3"><label>Profit Base Rate (%)</label><input type="number" step="0.01" name="profit_base_rate" class="form-control" required placeholder="5"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-warning">Save</button></div>
    </form>
  </div></div>
</div>

<!-- Add SME Modal -->
<div class="modal fade" id="addSmeModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add_sme">
      <div class="modal-header bg-info text-white"><h5 class="modal-title">Add SME Rate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-6"><label>Start Year</label><input type="number" name="start_year" class="form-control" required value="2020"></div>
          <div class="col-6"><label>End Year</label><input type="number" name="end_year" class="form-control" required value="2099"></div>
        </div>
        <div class="mb-3"><label>Sector</label><input type="text" name="sector" class="form-control" required placeholder="e.g. Agriculture"></div>
        <div class="row mb-3">
          <div class="col-6"><label>Min Turnover (LAK)</label><input type="number" name="turnover_min" class="form-control" required value="0"></div>
          <div class="col-6"><label>Max Turnover (blank=Any)</label><input type="number" name="turnover_max" class="form-control"></div>
        </div>
        <div class="mb-3"><label>Rate (%)</label><input type="number" step="0.01" name="rate_percentage" class="form-control" required placeholder="1"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-info text-white">Save</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
