<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-list-ul me-2"></i> Customs Duty TE by provision</h2>
    <p class="text-muted">Part of the TE Reports section. Breakdown of customs duty expenditures by legal provision.</p>
  </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body py-5 text-center text-muted">
        <i class="fas fa-chart-bar fa-3x mb-3 text-primary opacity-50"></i>
        <h4 class="text-dark">Report Available After TE Calculation</h4>
        <p>This report will show Customs Duty TE breakdown by provision category once data is imported and calculations are run.</p>
        <div class="mt-4">
            <span class="badge bg-success me-1">Ready</span> Benchmark Rates &mdash;
            <span class="badge bg-success me-1">Ready</span> Provisions Repository &mdash;
            <span class="badge bg-warning text-dark me-1">Pending</span> Data Import &mdash;
            <span class="badge bg-warning text-dark">Pending</span> TE Engine
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
