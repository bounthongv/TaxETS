<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

// 1. TE by Sector
$sector_data = $pdo->query("SELECT c.sector, SUM(tr.profit_tax_te) as total_te 
                             FROM te_profit_result tr 
                             JOIN companies c ON tr.company_id = c.id 
                             GROUP BY c.sector 
                             ORDER BY total_te DESC LIMIT 10")->fetchAll();

// 2. TE by Year
$year_data = $pdo->query("SELECT c.tax_year, SUM(tr.profit_tax_te) as total_te 
                           FROM te_profit_result tr 
                           JOIN companies c ON tr.company_id = c.id 
                           GROUP BY c.tax_year 
                           ORDER BY c.tax_year ASC")->fetchAll();

// 3. Stats
$total_te = $pdo->query("SELECT SUM(profit_tax_te) FROM te_profit_result")->fetchColumn();
$total_companies = $pdo->query("SELECT COUNT(DISTINCT company_id) FROM te_profit_result")->fetchColumn();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-4">
  <div class="col-12">
    <h2><i class="fas fa-tachometer-alt me-2"></i> TE Summary Dashboard</h2>
    <p class="text-muted">High-level overview of total Tax Expenditure across all sectors and years.</p>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm bg-success text-white">
      <div class="card-body">
        <h6 class="text-uppercase small fw-bold">Grand Total Tax Expenditure (LAK)</h6>
        <h2 class="display-5 fw-bold mb-0"><?= number_format($total_te, 0) ?></h2>
        <div class="mt-2 small"><i class="fas fa-building me-1"></i> Based on <?= number_format($total_companies) ?> companies with identified TE.</div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm bg-primary text-white">
      <div class="card-body">
        <h6 class="text-uppercase small fw-bold">Top Sector by TE</h6>
        <h2 class="display-5 fw-bold mb-0"><?= $sector_data[0]['sector'] ?? 'N/A' ?></h2>
        <div class="mt-2 small"><i class="fas fa-chart-line me-1"></i> Representing <?= number_format($sector_data[0]['total_te'] ?? 0, 0) ?> LAK</div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white fw-bold">TE by Sector (Top 10)</div>
      <div class="card-body">
        <canvas id="sectorChart" height="300"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white fw-bold">TE Trend by Year</div>
      <div class="card-body">
        <canvas id="yearChart" height="300"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sector Chart
const ctxSector = document.getElementById('sectorChart');
new Chart(ctxSector, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($sector_data, 'sector')) ?>,
        datasets: [{
            label: 'Total TE (LAK)',
            data: <?= json_encode(array_column($sector_data, 'total_te')) ?>,
            backgroundColor: 'rgba(25, 135, 84, 0.7)',
            borderColor: 'rgb(25, 135, 84)',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } }
    }
});

// Year Chart
const ctxYear = document.getElementById('yearChart');
new Chart(ctxYear, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($year_data, 'tax_year')) ?>,
        datasets: [{
            label: 'TE (LAK)',
            data: <?= json_encode(array_column($year_data, 'total_te')) ?>,
            fill: true,
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            borderColor: 'rgb(13, 110, 253)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
