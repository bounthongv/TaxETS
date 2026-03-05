<?php $cur = basename($_SERVER["PHP_SELF"]); ?>
<nav id="sidebar">
  <div class="sidebar-header">
    <h4><i class="fas fa-calculator me-2"></i> Tax-ETS</h4>
    <small class="text-white-50">Lao PDR Tax Expenditure</small>
  </div>
  <ul class="list-unstyled components">
    <li class="<?= $cur == 'index.php' ? 'active' : '' ?>">
      <a href="<?= BASE_URL ?>/index.php"><i class="fas fa-home me-2"></i> Dashboard</a>
    </li>
    <li>
      <a href="#configSub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-cogs me-2"></i> Config &amp; Rules
      </a>
      <ul class="collapse list-unstyled" id="configSub">
        <li><a href="<?= BASE_URL ?>/pages/config_rates.php">Benchmark Rates</a></li>
        <li><a href="<?= BASE_URL ?>/pages/config_provisions.php">Tax Provisions</a></li>
      </ul>
    </li>
    <li>
      <a href="#importSub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-file-import me-2"></i> Tax Data
      </a>
      <ul class="collapse list-unstyled" id="importSub">
        <li><a href="<?= BASE_URL ?>/pages/import_cit.php">Import CIT (Excel)</a></li>
      </ul>
    </li>
    <li class="<?= $cur == 'calculator.php' ? 'active' : '' ?>">
      <a href="<?= BASE_URL ?>/pages/calculator.php"><i class="fas fa-laptop-code me-2"></i> TE Calculation</a>
    </li>
    <li>
      <a href="#reportSub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-chart-bar me-2"></i> Reports
      </a>
      <ul class="collapse list-unstyled" id="reportSub">
        <li><a href="<?= BASE_URL ?>/pages/report_summary.php">Overall TE Summary</a></li>
        <li><a href="<?= BASE_URL ?>/pages/report_provisions.php">CIT TE by Provision</a></li>
      </ul>
    </li>
  </ul>
</nav>
