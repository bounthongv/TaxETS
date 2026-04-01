<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();

// Fetch Employment Brackets
$brackets = [];
try {
    $brackets = $pdo->query("SELECT * FROM bm_pit_employment ORDER BY start_year DESC, min_income ASC")->fetchAll();
} catch (Exception $e) { }

// Fetch Flat Rates
$flat_rates = [];
try {
    $flat_rates = $pdo->query("SELECT * FROM bm_pit_flat_rates ORDER BY income_type ASC")->fetchAll();
} catch (Exception $e) { }
?>

<div class="row mb-3">
  <div class="col-12 text-start">
    <h2><i class="fas fa-user-tie me-2"></i> Individual Income Tax Benchmark</h2>
    <p class="text-muted">Manage progressive tax brackets for employment and flat rates for other income types.</p>
  </div>
</div>

<ul class="nav nav-tabs mb-4" id="pitTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment" type="button"><i class="fas fa-briefcase me-2"></i> Employment Brackets</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="flat-tab" data-bs-toggle="tab" data-bs-target="#flat" type="button"><i class="fas fa-percent me-2"></i> Flat Rates (Other Income)</button>
  </li>
</ul>

<div class="tab-content" id="pitTabsContent">
  <div class="tab-pane fade show active" id="employment" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-layer-group me-2"></i> Progressive Tax Brackets</span>
        <button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Bracket</button>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>Years</th>
              <th class="text-end">Min Income (LAK/mo)</th>
              <th class="text-end">Max Income (LAK/mo)</th>
              <th class="text-center">Rate</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($brackets as $b): ?>
            <tr>
              <td><?= $b["start_year"] ?> - <?= $b["end_year"] ?></td>
              <td class="text-end"><?= number_format($b["min_income"]) ?></td>
              <td class="text-end"><?= $b["max_income"] ? number_format($b["max_income"]) : "+" ?></td>
              <td class="text-center fw-bold"><?= $b["rate_percentage"] ?>%</td>
              <td class="text-center">
                <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-edit"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="flat" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-tags me-2"></i> Flat Rates for Other Income</span>
        <button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Factor</button>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>Income Type</th>
              <th class="text-center">Rate</th>
              <th>Years</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($flat_rates as $f): ?>
            <tr>
              <td class="fw-bold"><?= htmlspecialchars($f["income_type"]) ?></td>
              <td class="text-center fw-bold text-primary"><?= $f["rate_percentage"] ?>%</td>
              <td><?= $f["start_year"] ?> - <?= $f["end_year"] ?></td>
              <td class="text-center">
                <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-edit"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
