<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_bm" || $_POST["action"] === "edit_bm") {
                $sql = "";
                $params = [
                    $_POST["item_no"],
                    $_POST["item_name"],
                    $_POST["rate_percentage"] ?: 0,
                    $_POST["start_year"],
                    $_POST["end_year"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ];

                if ($_POST["action"] === "add_bm") {
                    $sql = "INSERT INTO bm_natural_resource (item_no, item_name, rate_percentage, start_year, end_year, active) VALUES (?, ?, ?, ?, ?, ?)";
                } else {
                    $sql = "UPDATE bm_natural_resource SET item_no = ?, item_name = ?, rate_percentage = ?, start_year = ?, end_year = ?, active = ? WHERE id = ?";
                    $params[] = $_POST["id"];
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "Natural resource rate " . ($_POST["action"] === "add_bm" ? "added" : "updated") . ".";
            } elseif ($_POST["action"] === "delete_bm") {
                $pdo->prepare("DELETE FROM bm_natural_resource WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Natural resource rate deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$year_filter = $_GET["year_filter"] ?? date("Y");
$rates = $pdo->query("SELECT * FROM bm_natural_resource WHERE start_year <= $year_filter AND (end_year IS NULL OR end_year >= $year_filter) ORDER BY CAST(item_no AS UNSIGNED), item_no")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-gem me-2"></i> Natural Resource Compensation</h2>
      <p class="text-muted">Benchmark rates for natural resource extraction as a percentage of sales value.</p>
    </div>
    <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#bmModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add Rate
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

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-auto">
        <label class="small text-muted">Filter by Year:</label>
      </div>
      <div class="col-auto">
        <select name="year_filter" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php for ($y = 2020; $y <= 2030; $y++): ?>
          <option value="<?= $y ?>" <?= $year_filter == $y ? "selected" : "" ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-auto">
        <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4" style="width: 80px;">L/D</th>
            <th>Mineral Type / Resource</th>
            <th class="text-center">Rate (%)</th>
            <th class="text-center">Period</th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($rates as $r): ?>
          <tr>
            <td class="ps-4 fw-bold"><?= htmlspecialchars($r["item_no"]) ?></td>
            <td><?= htmlspecialchars($r["item_name"]) ?></td>
            <td class="text-center fw-bold text-success h5"><?= number_format($r["rate_percentage"], 1) ?>%</td>
            <td class="text-center text-muted"><?= $r["start_year"] ?> - <?= $r["end_year"] ?: "Ongoing" ?></td>
            <td class="text-center">
                <?= $r["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick='editRate(<?= json_encode($r) ?>)'>
                <i class="fas fa-edit"></i>
              </button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete this rate?')">
                <input type="hidden" name="action" value="delete_bm">
                <input type="hidden" name="id" value="<?= $r["id"] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($rates)): ?>
          <tr><td colspan="6" class="text-center text-muted py-5">No rates found for <?= $year_filter ?>.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="bmModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title"><i class="fas fa-gem me-2"></i> Resource Rate</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="action" id="modalAction" value="add_bm">
          <input type="hidden" name="id" id="bmId">
          
          <div class="mb-3">
            <label class="form-label fw-bold">L/D No.</label>
            <input type="text" name="item_no" id="itemNo" class="form-control" placeholder="e.g. 1" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Mineral Type / Resource Description</label>
            <textarea name="item_name" id="itemName" class="form-control" rows="3" required></textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Percentage of Sales Value (%)</label>
            <div class="input-group">
                <input type="number" step="0.1" name="rate_percentage" id="ratePercentage" class="form-control" required>
                <span class="input-group-text">%</span>
            </div>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Start Year</label>
                <input type="number" name="start_year" id="startYear" class="form-control" required value="2025">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">End Year</label>
                <input type="number" name="end_year" id="endYear" class="form-control" placeholder="Blank = Ongoing">
            </div>
          </div>
          
          <div class="mt-3">
              <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="active" id="active" value="1" checked>
                <label class="form-check-label" for="active">Active Status</label>
              </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4">Save Rate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalAction').value = 'add_bm';
    document.getElementById('bmId').value = '';
    document.getElementById('itemNo').value = '';
    document.getElementById('itemName').value = '';
    document.getElementById('ratePercentage').value = '';
    document.getElementById('startYear').value = '2025';
    document.getElementById('endYear').value = '';
    document.getElementById('active').checked = true;
}

function editRate(data) {
    document.getElementById('modalAction').value = 'edit_bm';
    document.getElementById('bmId').value = data.id;
    document.getElementById('itemNo').value = data.item_no;
    document.getElementById('itemName').value = data.item_name;
    document.getElementById('ratePercentage').value = data.rate_percentage;
    document.getElementById('startYear').value = data.start_year;
    document.getElementById('endYear').value = data.end_year || '';
    document.getElementById('active').checked = data.active == 1;
    new bootstrap.Modal(document.getElementById('bmModal')).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
