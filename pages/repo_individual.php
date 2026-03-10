<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();

// Fetch Individual Provisions (PIT)
$provisions = [];
try {
    $provisions = $pdo->query("SELECT * FROM individual_provisions ORDER BY CAST(provision_number AS UNSIGNED) ASC")->fetchAll();
} catch (Exception $e) { }
?>

<div class="row mb-3">
  <div class="col-12 text-start">
    <h2><i class="fas fa-balance-scale me-2"></i> Individual Income Tax Repository</h2>
    <p class="text-muted">Part of the Repository section. Archive of personal income tax regulations and tax expenditure provisions.</p>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-bold"><i class="fas fa-list-check me-2"></i> Individual Tax Provisions (ITL Art. 35/36)</span>
    <button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Provision</button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0" style="font-size: 0.9em;">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;">Prov #</th>
            <th style="width: 100px;">Period</th>
            <th style="width: 120px;">Legal Basis</th>
            <th style="width: 100px;">Type</th>
            <th>Description of Provision</th>
            <th style="width: 120px;">Limit Amount</th>
            <th class="text-center" style="width: 80px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($provisions as $p): ?>
          <tr>
            <td class="text-center fw-bold text-primary"><?= htmlspecialchars($p["provision_number"]) ?></td>
            <td><?= $p["start_year"] ?? 2000 ?>-<?= $p["end_year"] ?? 2099 ?></td>
            <td><?= htmlspecialchars($p["legal_basis"]) ?></td>
            <td>
              <span class="badge bg-<?= $p["type_of_te"] == "Exemption" ? "info" : "warning" ?> text-dark">
                <?= htmlspecialchars($p["type_of_te"]) ?>
              </span>
            </td>
            <td>
              <strong><?= htmlspecialchars($p["purpose"]) ?></strong><br>
              <small class="text-muted"><?= htmlspecialchars($p["description"]) ?></small>
            </td>
            <td class="text-end">
              <?= $p["limit_amount"] ? "max " . number_format($p["limit_amount"]) : "-" ?>
            </td>
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
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
