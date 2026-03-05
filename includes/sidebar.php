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
        <li class="<?= $cur == 'system_users.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_users.php"><i class="fas fa-user-friends me-2"></i> User Management</a>
        </li>
        <li class="<?= $cur == 'system_roles.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_roles.php"><i class="fas fa-key me-2"></i> Role Management</a>
        </li>
        <li class="<?= $cur == 'system_history.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_history.php"><i class="fas fa-history me-2"></i> User history</a>
        </li>
        <li class="<?= $cur == 'system_mgmt.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_mgmt.php"><i class="fas fa-cog me-2"></i> System Management</a>
        </li>
        <li class="<?= $cur == 'system_ip.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_ip.php"><i class="fas fa-network-wired me-2"></i> IP access Management</a>
        </li>
        <li class="<?= $cur == 'system_online.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_online.php"><i class="fas fa-user-clock me-2"></i> User Online Management</a>
        </li>
        <li class="<?= $cur == 'system_logs.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_logs.php"><i class="fas fa-file-alt me-2"></i> Operation Logs</a>
        </li>
        <li class="<?= $cur == 'system_backup.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/system_backup.php"><i class="fas fa-database me-2"></i> Backup/Restore Data</a>
        </li>
      </ul>
    </li>

    <!-- Data Dictionary -->
    <li>
      <a href="#dictionarySub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= in_array($cur, ['dictionary_province.php', 'dictionary_district.php', 'dictionary_zone.php']) ? 'true' : 'false' ?>">
        <i class="fas fa-book me-2"></i> Data Dictionary
      </a>
      <ul class="collapse list-unstyled <?= in_array($cur, ['dictionary_province.php', 'dictionary_district.php', 'dictionary_zone.php']) ? 'show' : '' ?>" id="dictionarySub">
        <li class="<?= $cur == 'dictionary_province.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/dictionary_province.php">Province</a>
        </li>
        <li class="<?= $cur == 'dictionary_district.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/dictionary_district.php">District</a>
        </li>
        <li class="<?= $cur == 'dictionary_zone.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/dictionary_zone.php">Investment Zone</a>
        </li>
      </ul>
    </li>

    <!-- Benchmark -->
    <li>
      <a href="#benchmarkSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'benchmark_') !== false || $cur == 'config_rates.php' ? 'true' : 'false' ?>">
        <i class="fas fa-search-dollar me-2"></i> Benchmark
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'benchmark_') !== false || $cur == 'config_rates.php' ? 'show' : '' ?>" id="benchmarkSub">
        <li class="<?= $cur == 'config_rates.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/config_rates.php">Corporate Income Tax</a>
        </li>
        <li class="<?= $cur == 'benchmark_individual.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/benchmark_individual.php">Individual Income Tax</a>
        </li>
        <li class="<?= $cur == 'benchmark_vat.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/benchmark_vat.php">Value-Added Tax</a>
        </li>
        <li class="<?= $cur == 'benchmark_customs.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/benchmark_customs.php">Customs Duty</a>
        </li>
        <li class="<?= $cur == 'benchmark_excise.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/benchmark_excise.php">Excise Tax Services</a>
        </li>
        <li class="<?= $cur == 'benchmark_nontax.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/benchmark_nontax.php">Non-Tax</a>
        </li>
        <li class="<?= $cur == 'benchmark_art9.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/benchmark_art9.php">Activities in Art 9 of IPL</a>
        </li>
      </ul>
    </li>

    <!-- Repository -->
    <li>
      <a href="#repositorySub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'repo_') !== false || $cur == 'config_provisions.php' ? 'true' : 'false' ?>">
        <i class="fas fa-archive me-2"></i> Repository
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'repo_') !== false || $cur == 'config_provisions.php' ? 'show' : '' ?>" id="repositorySub">
        <li class="<?= $cur == 'config_provisions.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/config_provisions.php">Corporate Income Tax</a>
        </li>
        <li class="<?= $cur == 'repo_individual.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/repo_individual.php">Individual Income Tax</a>
        </li>
        <li class="<?= $cur == 'repo_vat.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/repo_vat.php">Value-Added Tax</a>
        </li>
        <li class="<?= $cur == 'repo_customs.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/repo_customs.php">Customs Duty</a>
        </li>
        <li class="<?= $cur == 'repo_excise.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/repo_excise.php">Excise Tax Services</a>
        </li>
        <li class="<?= $cur == 'repo_nontax.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/repo_nontax.php">Non-Tax</a>
        </li>
      </ul>
    </li>

    <hr class="mx-3 opacity-25">

    <!-- Get Tax Data from Excel -->
    <li>
      <a href="#importSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'import_') !== false ? 'true' : 'false' ?>">
        <i class="fas fa-file-excel me-2"></i> Get Tax Data from Excel
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'import_') !== false ? 'show' : '' ?>" id="importSub">
        <li class="<?= $cur == 'import_asycuda.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_asycuda.php">Data from ASYCUDA</a>
        </li>
        <li class="<?= $cur == 'import_cit.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_cit.php">Profit Tax</a>
        </li>
        <li class="<?= $cur == 'import_individual.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_individual.php">Individual Tax</a>
        </li>
        <li class="<?= $cur == 'import_salary.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_salary.php">Salary Tax</a>
        </li>
        <li class="<?= $cur == 'import_vat.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_vat.php">Domestic VAT</a>
        </li>
        <li class="<?= $cur == 'import_sez_dev.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_sez_dev.php">For SEZ Developers</a>
        </li>
        <li class="<?= $cur == 'import_sez_inv.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_sez_inv.php">For SEZ Investors</a>
        </li>
        <li class="<?= $cur == 'import_land.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_land.php">Non-Tax: Land concession</a>
        </li>
        <li class="<?= $cur == 'import_resource.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_resource.php">Non-Tax: Resource fee</a>
        </li>
        <li class="<?= $cur == 'import_royalty.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/import_royalty.php">Non-Tax: Royalty fee</a>
        </li>
      </ul>
    </li>

    <!-- TE Calculation -->
    <li>
      <a href="#calculationSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'te_') !== false || $cur == 'calculator.php' ? 'true' : 'false' ?>">
        <i class="fas fa-laptop-code me-2"></i> TE Calculation
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'te_') !== false || $cur == 'calculator.php' ? 'show' : '' ?>" id="calculationSub">
        <li class="<?= $cur == 'calculator.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/calculator.php">Profit Tax TE Calculation Engine</a>
        </li>
        <li class="<?= $cur == 'te_individual.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/te_individual.php">Individual Tax Expenditure</a>
        </li>
        <li class="<?= $cur == 'te_excise.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/te_excise.php">Excise Tax Expenditure</a>
        </li>
        <li class="<?= $cur == 'te_vat.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/te_vat.php">VAT Expenditure</a>
        </li>
        <li class="<?= $cur == 'te_customs.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/te_customs.php">Custom Tax Expenditure</a>
        </li>
      </ul>
    </li>

    <!-- TE Reports -->
    <li>
      <a href="#reportSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= strpos($cur, 'report_') !== false ? 'true' : 'false' ?>">
        <i class="fas fa-chart-bar me-2"></i> TE Reports
      </a>
      <ul class="collapse list-unstyled <?= strpos($cur, 'report_') !== false ? 'show' : '' ?>" id="reportSub">
        <li class="<?= $cur == 'report_tax_type.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_tax_type.php">TE by Tax Type</a>
        </li>
        <li class="<?= $cur == 'report_sector.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_sector.php">TE by Sector</a>
        </li>
        <li class="<?= $cur == 'report_location.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_location.php">TE by Location (Province)</a>
        </li>
        <li class="<?= $cur == 'report_gdp.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_gdp.php">TE by Tax Type (% of GDP)</a>
        </li>
        <li class="<?= $cur == 'report_revenue.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_revenue.php">TE Tax Type (% of Revenue)</a>
        </li>
        <li class="<?= $cur == 'report_provisions.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_provisions.php">Profit Tax TE by provision</a>
        </li>
        <li class="<?= $cur == 'report_individual_provision.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_individual_provision.php">Individual Income Tax TE by provision</a>
        </li>
        <li class="<?= $cur == 'report_vat_provision.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_vat_provision.php">VAT TE by provision</a>
        </li>
        <li class="<?= $cur == 'report_excise_provision.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_excise_provision.php">Excise Tax TE by provision</a>
        </li>
        <li class="<?= $cur == 'report_customs_provision.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_customs_provision.php">Customs Duty TE by provision</a>
        </li>
        <li class="<?= $cur == 'report_nontax_provision.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/report_nontax_provision.php">Non-Tax TE by provision</a>
        </li>
      </ul>
    </li>
  </ul>
</nav>
