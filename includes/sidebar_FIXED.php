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

    <!-- System -->
    <li>
      <a href="#systemSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'system_') !== false ? 'true' : 'false' ?>">
        <i class="fas fa-desktop me-2"></i> System
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'system_') !== false ? 'show' : '' ?>" id="systemSub">
        <li><a href="<?= BASE_URL ?>/pages/system_users.php">User Management</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_roles.php">Role Management</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_history.php">User history</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_mgmt.php">System Management</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_ip.php">IP access Management</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_online.php">User Online Management</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_logs.php">Operation Logs</a></li>
        <li><a href="<?= BASE_URL ?>/pages/system_backup.php">Backup/Restore Data</a></li>
      </ul>
    </li>

    <!-- Data Dictionary -->
    <li>
      <a href="#dictionarySub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-book me-2"></i> Data Dictionary
      </a>
      <ul class="collapse list-unstyled" id="dictionarySub">
        <li><a href="<?= BASE_URL ?>/pages/dictionary_province.php">Province</a></li>
        <li><a href="<?= BASE_URL ?>/pages/dictionary_district.php">District</a></li>
        <li><a href="<?= BASE_URL ?>/pages/dictionary_zone.php">Investment Zone</a></li>
      </ul>
    </li>

    <!-- Benchmark -->
    <li>
      <a href="#benchmarkSub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-search-dollar me-2"></i> Benchmark
      </a>
      <ul class="collapse list-unstyled" id="benchmarkSub">
        <li><a href="<?= BASE_URL ?>/pages/config_rates.php">Corporate Income Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/benchmark_individual.php">Individual Income Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/benchmark_vat.php">Value-Added Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/benchmark_customs.php">Customs Duty</a></li>
        <li><a href="<?= BASE_URL ?>/pages/benchmark_excise.php">Excise Tax Services</a></li>
        <li><a href="<?= BASE_URL ?>/pages/benchmark_nontax.php">Non-Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/benchmark_art9.php">Activities in Art 9 of IPL</a></li>
      </ul>
    </li>

    <!-- Repository -->
    <li>
      <a href="#repositorySub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-archive me-2"></i> Repository
      </a>
      <ul class="collapse list-unstyled" id="repositorySub">
        <li><a href="<?= BASE_URL ?>/pages/config_provisions.php">Corporate Income Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/repo_individual.php">Individual Income Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/repo_vat.php">Value-Added Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/repo_customs.php">Customs Duty</a></li>
        <li><a href="<?= BASE_URL ?>/pages/repo_excise.php">Excise Tax Services</a></li>
        <li><a href="<?= BASE_URL ?>/pages/repo_nontax.php">Non-Tax</a></li>
      </ul>
    </li>

    <hr class="mx-3 opacity-25">

    <!-- Get Tax Data from Excel -->
    <li>
      <a href="#importSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'import_') !== false || strpos($cur, 'asycuda_') !== false ? 'true' : 'false' ?>">
        <i class="fas fa-file-excel me-2"></i> Get Tax Data from Excel
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'import_') !== false || strpos($cur, 'asycuda_') !== false ? 'show' : '' ?>" id="importSub">
        <li>
            <a href="#asySub" data-bs-toggle="collapse" class="dropdown-toggle">Data from ASYCUDA</a>
            <ul class="collapse list-unstyled ps-3" id="asySub">
                <li><a href="<?= BASE_URL ?>/pages/import_asycuda.php">Import New Data</a></li>
                <li><a href="<?= BASE_URL ?>/pages/asycuda_customs.php">Custom Duty</a></li>
                <li><a href="<?= BASE_URL ?>/pages/asycuda_excise.php">Excise Tax</a></li>
                <li><a href="<?= BASE_URL ?>/pages/asycuda_vat.php">Import VAT</a></li>
            </ul>
        </li>
        <li><a href="<?= BASE_URL ?>/pages/import_cit.php">Profit Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_individual.php">Individual Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_salary.php">Salary Tax</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_vat.php">Domestic VAT</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_sez_dev.php">For SEZ Developers</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_sez_inv.php">For SEZ Investors</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_land.php">Non-Tax: Land concession</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_resource.php">Non-Tax: Resource fee</a></li>
        <li><a href="<?= BASE_URL ?>/pages/import_royalty.php">Non-Tax: Royalty fee</a></li>
      </ul>
    </li>

    <!-- TE Calculation -->
    <li>
      <a href="#calculationSub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-laptop-code me-2"></i> TE Calculation
      </a>
      <ul class="collapse list-unstyled" id="calculationSub">
        <li><a href="<?= BASE_URL ?>/pages/calculator.php">Profit Tax TE Calculation Engine</a></li>
        <li><a href="<?= BASE_URL ?>/pages/te_individual.php">Individual Tax Expenditure</a></li>
        <li><a href="<?= BASE_URL ?>/pages/te_excise.php">Excise Tax Expenditure</a></li>
        <li><a href="<?= BASE_URL ?>/pages/te_vat.php">VAT Expenditure</a></li>
        <li><a href="<?= BASE_URL ?>/pages/te_customs.php">Custom Tax Expenditure</a></li>
      </ul>
    </li>

    <!-- TE Reports -->
    <li>
      <a href="#reportSub" data-bs-toggle="collapse" class="dropdown-toggle">
        <i class="fas fa-chart-bar me-2"></i> TE Reports
      </a>
      <ul class="collapse list-unstyled" id="reportSub">
        <li><a href="<?= BASE_URL ?>/pages/report_tax_type.php">TE by Tax Type</a></li>
        <li><a href="<?= BASE_URL ?>/pages/report_sector.php">TE by Sector</a></li>
        <li><a href="<?= BASE_URL ?>/pages/report_location.php">TE by Location (Province)</a></li>
        <li><a href="<?= BASE_URL ?>/pages/report_gdp.php">TE by Tax Type (% of GDP)</a></li>
        <li><a href="<?= BASE_URL ?>/pages/report_revenue.php">TE Tax Type (% of Revenue)</a></li>
        <li><a href="<?= BASE_URL ?>/pages/report_provisions.php">Profit Tax TE by provision</a></li>
      </ul>
    </li>
  </ul>
</nav>
