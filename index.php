<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
$pdo = getDbConnection();

// Live Stats
$total_companies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$total_provisions = $pdo->query("SELECT COUNT(*) FROM profit_provisions")->fetchColumn();
$total_te = $pdo->query("SELECT SUM(profit_tax_te) FROM te_profit_result")->fetchColumn();
$latest_batch = $pdo->query("SELECT import_batch_id FROM companies ORDER BY id DESC LIMIT 1")->fetchColumn();
?>

<div class="row mb-4">
  <div class="col-12">
    <h2 class="mb-1">Overview Dashboard</h2>
    <p class="text-muted">Welcome to the Tax Expenditure Estimation System (Phase 1: Corporate Income Tax)</p>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-success text-white">
      <div class="card-body">
        <h6 class="text-uppercase small fw-bold">Companies Imported</h6>
        <h2 class="display-6 fw-bold"><?= number_format($total_companies) ?></h2>
        <a href="<?= BASE_URL ?>/pages/import_cit.php" class="text-white-50 text-decoration-none small">Manage Data <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-primary text-white">
      <div class="card-body">
        <h6 class="text-uppercase small fw-bold">Active Provisions</h6>
        <h2 class="display-6 fw-bold"><?= $total_provisions ?></h2>
        <a href="<?= BASE_URL ?>/pages/config_provisions.php" class="text-white-50 text-decoration-none small">Configure Rules <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-danger text-white">
      <div class="card-body">
        <h6 class="text-uppercase small fw-bold">Total TE Identified</h6>
        <h2 class="display-6 fw-bold"><?= number_format($total_te / 1000000000, 2) ?>B</h2>
        <a href="<?= BASE_URL ?>/pages/report_summary.php" class="text-white-50 text-decoration-none small">View Analysis <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-dark text-white">
      <div class="card-body">
        <h6 class="text-uppercase small fw-bold">Quick Actions</h6>
        <div class="d-grid gap-1 mt-2">
          <a href="<?= BASE_URL ?>/pages/import_cit.php" class="btn btn-outline-light btn-sm text-start"><i class="fas fa-upload me-1"></i> New Import</a>
          <a href="<?= BASE_URL ?>/pages/calculator.php?batch=<?= urlencode($latest_batch) ?>" class="btn btn-outline-light btn-sm text-start"><i class="fas fa-calculator me-1"></i> Run Calculation</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Getting Started Guide</div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex align-items-center">
            <span class="badge bg-secondary rounded-pill me-3">1</span>
            <div><strong>Configure Benchmarks:</strong> Set the Standard, SME, and Mandatory rates for the current tax year.</div>
          </li>
          <li class="list-group-item d-flex align-items-center">
            <span class="badge bg-secondary rounded-pill me-3">2</span>
            <div><strong>Verify Provision Rules:</strong> Review the 20 seeded provisions to ensure they match current legal interpretation.</div>
          </li>
          <li class="list-group-item d-flex align-items-center">
            <span class="badge bg-secondary rounded-pill me-3">3</span>
            <div><strong>Import Data:</strong> Upload the CIT Excel template to the system.</div>
          </li>
          <li class="list-group-item d-flex align-items-center">
            <span class="badge bg-secondary rounded-pill me-3">4</span>
            <div><strong>Calculate & Analyze:</strong> Run the calculation engine and view the TE reports.</div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">System Status</div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr><td>Database:</td><td><span class="badge bg-success">Connected</span></td></tr>
          <tr><td>PHP Module:</td><td><span class="badge bg-success">Ready</span></td></tr>
          <tr><td>Composer Vendor:</td><td><span class="badge bg-<?= file_exists(__DIR__.'/vendor/autoload.php') ? 'success':'warning' ?>"><?= file_exists(__DIR__.'/vendor/autoload.php') ? 'Installed':'Action Required' ?></span></td></tr>
        </table>
        <?php if (!file_exists(__DIR__.'/vendor/autoload.php')): ?>
        <div class="alert alert-warning mt-3 py-2 small mb-0">
          <i class="fas fa-exclamation-triangle me-1"></i> 
          Run <code>composer install</code> on your server to enable Excel import.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
