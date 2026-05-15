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
                    $_POST["article_no"],
                    $_POST["article_name"],
                    $_POST["item_no"],
                    $_POST["item_name"],
                    $_POST["rate_zone1"] ?: 0,
                    $_POST["rate_zone2"] ?: 0,
                    $_POST["rate_zone3"] ?: 0,
                    $_POST["rate_search"] ?: 0,
                    $_POST["rate_survey"] ?: 0,
                    $_POST["rate_analysis"] ?: 0,
                    $_POST["unit"],
                    $_POST["start_year"],
                    $_POST["end_year"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ];

                if ($_POST["action"] === "add_bm") {
                    $sql = "INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, rate_search, rate_survey, rate_analysis, unit, start_year, end_year, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                } else {
                    $sql = "UPDATE bm_land_concession SET article_no = ?, article_name = ?, item_no = ?, item_name = ?, rate_zone1 = ?, rate_zone2 = ?, rate_zone3 = ?, rate_search = ?, rate_survey = ?, rate_analysis = ?, unit = ?, start_year = ?, end_year = ?, active = ? WHERE id = ?";
                    $params[] = $_POST["id"];
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "Benchmark rate " . ($_POST["action"] === "add_bm" ? "added" : "updated") . ".";
            } elseif ($_POST["action"] === "delete_bm") {
                $pdo->prepare("DELETE FROM bm_land_concession WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Benchmark rate deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$year_filter = $_GET["year_filter"] ?? date("Y");

$rates = $pdo->query("SELECT * FROM bm_land_concession WHERE start_year <= $year_filter AND (end_year IS NULL OR end_year >= $year_filter) ORDER BY LENGTH(article_no), article_no, CAST(item_no AS UNSIGNED), item_no")->fetchAll();

// Group by article
$grouped_rates = [];
foreach ($rates as $r) {
    $article_key = $r['article_no'] . ': ' . $r['article_name'];
    $grouped_rates[$article_key][] = $r;
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-map me-2"></i> Land Concession Benchmark</h2>
      <p class="text-muted">Benchmark rates for land concession tax grouped by Decree Articles.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#bmModal" onclick="clearForm()">
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

<?php if (empty($grouped_rates)): ?>
<div class="alert alert-info text-center py-5">
    <i class="fas fa-info-circle fa-3x mb-3 opacity-25"></i>
    <p>No benchmark rates found for year <?= $year_filter ?>.</p>
</div>
<?php else: ?>
    <?php foreach ($grouped_rates as $article_title => $items): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-header bg-light border-0 py-3">
        <h5 class="mb-0 fw-bold"><?= htmlspecialchars($article_title) ?></h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="bg-light text-uppercase small fw-bold">
              <tr>
                <th class="ps-4" style="width: 50px;">L/D</th>
                <th>Description</th>
                <?php if (strpos($article_title, 'Article 12') !== false): ?>
                    <th class="text-center">Search</th>
                    <th class="text-center">Survey</th>
                    <th class="text-center">Analysis</th>
                    <th class="text-center">Rate</th>
                <?php else: ?>
                    <th class="text-center">Zone 1</th>
                    <th class="text-center">Zone 2</th>
                    <th class="text-center">Zone 3</th>
                <?php endif; ?>
                <th class="text-center">Unit</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody class="small">
              <?php foreach ($items as $r): ?>
              <tr>
                <td class="ps-4"><?= htmlspecialchars($r["item_no"]) ?></td>
                <td><?= htmlspecialchars($r["item_name"]) ?></td>
                
                <?php if (strpos($article_title, 'Article 12') !== false): ?>
                    <td class="text-center fw-bold"><?= number_format($r["rate_search"], 2) ?></td>
                    <td class="text-center fw-bold"><?= number_format($r["rate_survey"], 2) ?></td>
                    <td class="text-center fw-bold"><?= number_format($r["rate_analysis"], 2) ?></td>
                    <td class="text-center fw-bold text-primary"><?= number_format($r["rate_zone1"], 2) ?></td>
                <?php else: ?>
                    <td class="text-center fw-bold"><?= number_format($r["rate_zone1"], 2) ?></td>
                    <td class="text-center fw-bold"><?= number_format($r["rate_zone2"], 2) ?></td>
                    <td class="text-center fw-bold"><?= number_format($r["rate_zone3"], 2) ?></td>
                <?php endif; ?>
                
                <td class="text-center text-muted"><?= htmlspecialchars($r["unit"]) ?></td>
                <td class="text-center">
                  <div class="btn-group">
                      <button class="btn btn-sm btn-outline-primary" onclick='editRate(<?= json_encode($r) ?>)'>
                        <i class="fas fa-edit"></i>
                      </button>
                      <form method="POST" class="d-inline" onsubmit="return confirm('Delete this rate?')">
                        <input type="hidden" name="action" value="delete_bm">
                        <input type="hidden" name="id" value="<?= $r["id"] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                      </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Add/Edit Modal -->
<div class="modal fade" id="bmModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i> Land Concession Rate</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="action" id="modalAction" value="add_bm">
          <input type="hidden" name="id" id="bmId">
          
          <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-bold">Article No.</label>
                <input type="text" name="article_no" id="articleNo" class="form-control" placeholder="e.g. Article 10" required>
              </div>
              <div class="col-md-8">
                <label class="form-label fw-bold">Article Name</label>
                <input type="text" name="article_name" id="articleName" class="form-control" placeholder="e.g. Agricultural Activities" required>
              </div>
              
              <div class="col-md-4">
                <label class="form-label fw-bold">Item/LD No.</label>
                <input type="text" name="item_no" id="itemNo" class="form-control" placeholder="e.g. 1" required>
              </div>
              <div class="col-md-8">
                <label class="form-label fw-bold">Item/Goal Description</label>
                <textarea name="item_name" id="itemName" class="form-control" rows="2" required></textarea>
              </div>

              <div class="col-12"><hr></div>

              <div class="col-md-4">
                <label class="form-label fw-bold">Zone 1 Rate / Search</label>
                <input type="number" step="0.01" name="rate_zone1" id="rateZone1" class="form-control" value="0">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Zone 2 Rate / Survey</label>
                <input type="number" step="0.01" name="rate_zone2" id="rateZone2" class="form-control" value="0">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Zone 3 Rate / Analysis</label>
                <input type="number" step="0.01" name="rate_zone3" id="rateZone3" class="form-control" value="0">
              </div>
              
              <div id="extraFields" class="col-12 row g-3 d-none">
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Search Rate</label>
                    <input type="number" step="0.01" name="rate_search" id="rateSearch" class="form-control" value="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Survey Rate</label>
                    <input type="number" step="0.01" name="rate_survey" id="rateSurvey" class="form-control" value="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Analysis Rate</label>
                    <input type="number" step="0.01" name="rate_analysis" id="rateAnalysis" class="form-control" value="0">
                  </div>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-bold">Unit</label>
                <select name="unit" id="unit" class="form-select">
                    <option value="USD/ha/year">USD/ha/year</option>
                    <option value="Kip/ha/year">Kip/ha/year</option>
                    <option value="USD/m2/year">USD/m2/year</option>
                    <option value="Kip/m2/year">Kip/m2/year</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-bold">Start Year</label>
                <input type="number" name="start_year" id="startYear" class="form-control" required value="2025">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">End Year</label>
                <input type="number" name="end_year" id="endYear" class="form-control" placeholder="Blank = Ongoing">
              </div>
              
              <div class="col-12">
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="active" id="active" value="1" checked>
                    <label class="form-check-label" for="active">Benchmark Status (Active/Inactive)</label>
                  </div>
              </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalAction').value = 'add_bm';
    document.getElementById('bmId').value = '';
    document.getElementById('articleNo').value = '';
    document.getElementById('articleName').value = '';
    document.getElementById('itemNo').value = '';
    document.getElementById('itemName').value = '';
    document.getElementById('rateZone1').value = '0';
    document.getElementById('rateZone2').value = '0';
    document.getElementById('rateZone3').value = '0';
    document.getElementById('rateSearch').value = '0';
    document.getElementById('rateSurvey').value = '0';
    document.getElementById('rateAnalysis').value = '0';
    document.getElementById('unit').value = 'USD/ha/year';
    document.getElementById('startYear').value = '2025';
    document.getElementById('endYear').value = '';
    document.getElementById('active').checked = true;
    document.getElementById('extraFields').classList.add('d-none');
}

function editRate(data) {
    document.getElementById('modalAction').value = 'edit_bm';
    document.getElementById('bmId').value = data.id;
    document.getElementById('articleNo').value = data.article_no;
    document.getElementById('articleName').value = data.article_name;
    document.getElementById('itemNo').value = data.item_no;
    document.getElementById('itemName').value = data.item_name;
    document.getElementById('rateZone1').value = data.rate_zone1;
    document.getElementById('rateZone2').value = data.rate_zone2;
    document.getElementById('rateZone3').value = data.rate_zone3;
    document.getElementById('rateSearch').value = data.rate_search || '0';
    document.getElementById('rateSurvey').value = data.rate_survey || '0';
    document.getElementById('rateAnalysis').value = data.rate_analysis || '0';
    document.getElementById('unit').value = data.unit;
    document.getElementById('startYear').value = data.start_year;
    document.getElementById('endYear').value = data.end_year || '';
    document.getElementById('active').checked = data.active == 1;
    
    if (data.article_no.includes('12')) {
        document.getElementById('extraFields').classList.remove('d-none');
    } else {
        document.getElementById('extraFields').classList.add('d-none');
    }
    
    new bootstrap.Modal(document.getElementById('bmModal')).show();
}

document.getElementById('articleNo').addEventListener('input', function() {
    if (this.value.includes('12')) {
        document.getElementById('extraFields').classList.remove('d-none');
    } else {
        document.getElementById('extraFields').classList.add('d-none');
    }
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>