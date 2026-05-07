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
    <?php $is_system = (strpos($cur, 'system_') !== false); ?>
    <li class="<?= $is_system ? 'active' : '' ?>">
      <a href="#systemSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_system ? 'true' : 'false' ?>">
        <i class="fas fa-desktop me-2"></i> System
      </a>
      <ul class="collapse list-unstyled <?= $is_system ? 'show' : '' ?>" id="systemSub">
        <li class="<?= $cur == 'system_users.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_users.php">User Management</a></li>
        <li class="<?= $cur == 'system_roles.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_roles.php">Role Management</a></li>
        <li class="<?= $cur == 'system_history.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_history.php">Operation Logs</a></li>
        <li class="<?= $cur == 'system_mgmt.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_mgmt.php">System Management</a></li>
        <li class="<?= $cur == 'system_ip.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_ip.php">IP access Management</a></li>
        <li class="<?= $cur == 'system_online.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_online.php">User Online Management</a></li>
        <li class="<?= $cur == 'system_backup.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/system_backup.php">Backup/Restore Data</a></li>
      </ul>
    </li>

    <!-- Data Dictionary -->
    <?php $is_dict = (strpos($cur, 'dictionary_') !== false); ?>
    <li class="<?= $is_dict ? 'active' : '' ?>">
      <a href="#dictionarySub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_dict ? 'true' : 'false' ?>">
        <i class="fas fa-book me-2"></i> Data Dictionary
      </a>
      <ul class="collapse list-unstyled <?= $is_dict ? 'show' : '' ?>" id="dictionarySub">
        <li class="<?= $cur == 'dictionary_province.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_province.php">Province</a></li>
        <li class="<?= $cur == 'dictionary_district.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_district.php">District</a></li>
        <li class="<?= $cur == 'dictionary_zone.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_zone.php">Investment Zone</a></li>
      </ul>
    </li>

    <!-- Benchmark -->
    <?php $is_bench = (strpos($cur, 'benchmark_') !== false || $cur == 'config_rates.php'); ?>
    <li class="<?= $is_bench ? 'active' : '' ?>">
      <a href="#benchmarkSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_bench ? 'true' : 'false' ?>">
        <i class="fas fa-search-dollar me-2"></i> Benchmark
      </a>
      <ul class="collapse list-unstyled <?= $is_bench ? 'show' : '' ?>" id="benchmarkSub">
        <li class="<?= $cur == 'config_rates.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/config_rates.php">Corporate Income Tax</a></li>
        <li class="<?= $cur == 'benchmark_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_individual.php">Individual Income Tax</a></li>
        <li class="<?= $cur == 'benchmark_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_vat.php">Value-Added Tax</a></li>
        <li class="<?= $cur == 'benchmark_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_customs.php">Customs Duty</a></li>
        <li class="<?= $cur == 'benchmark_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_excise.php">Excise Tax Services</a></li>
        <li class="<?= $cur == 'benchmark_nontax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_nontax.php">Non-Tax</a></li>
        <li class="<?= $cur == 'benchmark_art9.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_art9.php">Activities in Art 9 of IPL</a></li>
      </ul>
    </li>

    <!-- Repository -->
    <?php $is_repo = (strpos($cur, 'repo_') !== false || $cur == 'config_provisions.php'); ?>
    <li class="<?= $is_repo ? 'active' : '' ?>">
      <a href="#repositorySub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_repo ? 'true' : 'false' ?>">
        <i class="fas fa-archive me-2"></i> Repository
      </a>
      <ul class="collapse list-unstyled <?= $is_repo ? 'show' : '' ?>" id="repositorySub">
        <li class="<?= $cur == 'config_provisions.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/config_provisions.php">Corporate Income Tax</a></li>
        <li class="<?= $cur == 'repo_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_individual.php">Individual Income Tax</a></li>
        <li class="<?= $cur == 'repo_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_vat.php">Value-Added Tax</a></li>
        <li class="<?= $cur == 'repo_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_customs.php">Customs Duty</a></li>
        <li class="<?= $cur == 'repo_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_excise.php">Excise Tax Services</a></li>
        <li class="<?= $cur == 'repo_nontax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_nontax.php">Non-Tax</a></li>
      </ul>
    </li>

    <hr class="mx-3 opacity-25">

    <!-- Get Tax Data from Excel -->
    <li>
      <a href="#importSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'import_') !== false || strpos($cur, 'asycuda_') !== false ? 'true' : 'false' ?>">
        <i class="fas fa-file-excel me-2"></i> Get Tax Data from Excel
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'import_') !== false || strpos($cur, 'asycuda_') !== false ? 'show' : '' ?>" id="importSub">
        <?php $is_asycuda = (strpos($cur, 'asycuda_') !== false || $cur == 'import_asycuda.php'); ?>
        <li class="<?= $is_asycuda ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/asycuda_index.php" class="dropdown-toggle" aria-expanded="<?= $is_asycuda ? 'true' : 'false' ?>">Data from ASYCUDA</a>
            <ul class="collapse list-unstyled ps-3 <?= $is_asycuda ? 'show' : '' ?>" id="asySub">
                <li class="<?= $cur == 'import_asycuda.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_asycuda.php">Import New Data</a></li>
                <li class="<?= $cur == 'asycuda_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/asycuda_customs.php">Custom Duty</a></li>
                <li class="<?= $cur == 'asycuda_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/asycuda_excise.php">Excise Tax</a></li>
                <li class="<?= $cur == 'asycuda_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/asycuda_vat.php">Import VAT</a></li>
            </ul>
        </li>
        <li class="<?= $cur == 'import_cit.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_cit.php">Profit Tax</a></li>
        <li class="<?= $cur == 'import_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_individual.php">Individual Tax</a></li>
        <li class="<?= $cur == 'import_salary.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_salary.php">Salary Tax</a></li>
        <li class="<?= $cur == 'import_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_vat.php">Domestic VAT</a></li>
        <li class="<?= $cur == 'import_sez_dev.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_sez_dev.php">For SEZ Developers</a></li>
        <li class="<?= $cur == 'import_sez_inv.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_sez_inv.php">For SEZ Investors</a></li>
        <li class="<?= $cur == 'import_land.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_land.php">Non-Tax: Land concession</a></li>
        <li class="<?= $cur == 'import_resource.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_resource.php">Non-Tax: Resource fee</a></li>
        <li class="<?= $cur == 'import_royalty.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_royalty.php">Non-Tax: Royalty fee</a></li>
      </ul>
    </li>

    <!-- TE Calculation -->
    <?php $is_calc = (strpos($cur, 'te_') !== false || $cur == 'calculator.php'); ?>
    <li class="<?= $is_calc ? 'active' : '' ?>">
      <a href="#calculationSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_calc ? 'true' : 'false' ?>">
        <i class="fas fa-laptop-code me-2"></i> TE Calculation
      </a>
      <ul class="collapse list-unstyled <?= $is_calc ? 'show' : '' ?>" id="calculationSub">
        <li class="<?= $cur == 'calculator.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/calculator.php">Profit Tax TE Calculation Engine</a></li>
        <li class="<?= $cur == 'te_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_individual.php">Individual Tax Expenditure</a></li>
        <li class="<?= $cur == 'te_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_vat.php">Domestic VAT Expenditure</a></li>
        <li class="<?= $cur == 'te_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_customs.php">Custom Tax Expenditure</a></li>
        <li class="<?= $cur == 'te_asycuda_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_asycuda_excise.php">Excise Tax TE</a></li>
        <li class="<?= $cur == 'te_asycuda_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_asycuda_vat.php">Import VAT TE</a></li>
      </ul>
    </li>

    <!-- TE Reports -->
    <?php $is_report = (strpos($cur, 'report_') !== false); ?>
    <li class="<?= $is_report ? 'active' : '' ?>">
      <a href="#reportSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_report ? 'true' : 'false' ?>">
        <i class="fas fa-chart-bar me-2"></i> TE Reports
      </a>
      <ul class="collapse list-unstyled <?= $is_report ? 'show' : '' ?>" id="reportSub">
        <li class="<?= $cur == 'report_tax_type.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_tax_type.php">TE by Tax Type</a></li>
        <li class="<?= $cur == 'report_sector.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_sector.php">TE by Sector</a></li>
        <li class="<?= $cur == 'report_location.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_location.php">TE by Location (Province)</a></li>
        <li class="<?= $cur == 'report_gdp.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_gdp.php">TE by Tax Type (% of GDP)</a></li>
        <li class="<?= $cur == 'report_revenue.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_revenue.php">TE Tax Type (% of Revenue)</a></li>
        <li class="<?= $cur == 'report_provisions.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_provisions.php">Profit Tax TE by provision</a></li>
      </ul>
    </li>
  </ul>
</nav>
