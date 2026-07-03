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
    <?php $is_dict = (strpos($cur, 'dictionary_') !== false || $cur == 'repo_gdp.php'); ?>
    <li class="<?= $is_dict ? 'active' : '' ?>">
      <a href="#dictionarySub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_dict ? 'true' : 'false' ?>">
        <i class="fas fa-book me-2"></i> Data Dictionary
      </a>
      <ul class="collapse list-unstyled <?= $is_dict ? 'show' : '' ?>" id="dictionarySub">
        <li class="<?= $cur == 'dictionary_province.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_province.php">Province</a></li>
        <li class="<?= $cur == 'dictionary_district.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_district.php">District</a></li>
        <li class="<?= $cur == 'dictionary_zone.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_zone.php">Investment Zone</a></li>
        <li class="<?= $cur == 'dictionary_village.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_village.php">Village</a></li>
        <li class="<?= $cur == 'dictionary_enterprise_type.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_enterprise_type.php">Enterprise Type</a></li>
        <li class="<?= $cur == 'dictionary_sector.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_sector.php">Business Sector</a></li>
        <li class="<?= $cur == 'dictionary_moic_categories.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_moic_categories.php">MOIC Categories</a></li>
        <li class="<?= $cur == 'repo_gdp.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_gdp.php">GDP, Revenue</a></li>
        <li class="<?= $cur == 'dictionary_status.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/dictionary_status.php">Enterprise/Project Status</a></li>
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
        <li class="<?= $cur == 'benchmark_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_excise.php">Excise Tax</a></li>
        <li class="<?= $cur == 'benchmark_msme.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_msme.php">MSME Definition</a></li>
        <li class="<?= $cur == 'benchmark_customs_regime.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_customs_regime.php">Customs Regime Codes</a></li>
        <li class="<?= $cur == 'benchmark_payment_condition.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_payment_condition.php">Payment Condition Code</a></li>
        <li class="<?= $cur == 'benchmark_nontax.php' || $cur == 'benchmark_land_concession.php' ? 'active' : '' ?>">
          <?php $is_nontax_bench = ($cur == 'benchmark_nontax.php' || strpos($cur, 'benchmark_land') !== false); ?>
          <a href="#nonTaxBenchSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_nontax_bench ? 'true' : 'false' ?>">
            <i class="fas fa-money-bill-wave me-2"></i> Non-Tax
          </a>
          <ul class="collapse list-unstyled <?= $is_nontax_bench ? 'show' : '' ?>" id="nonTaxBenchSub">
            <li class="<?= $cur == 'benchmark_land_concession.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_land_concession.php">Land Concession</a></li>
            <li class="<?= $cur == 'benchmark_nontax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_nontax.php">Natural Resource</a></li>
          </ul>
        </li>
        <li class="<?= $cur == 'benchmark_art9.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/benchmark_art9.php">Activities in Art 9 of IPL</a></li>
      </ul>
    </li>

    <!-- Repository -->
    <?php 
      $is_repo = (
        (strpos($cur, 'repo_') !== false || strpos($cur, 'provision_') !== false || strpos($cur, 'config_sez_provisions') !== false || strpos($cur, 'config_rules') !== false) 
        && strpos($cur, 'molsw') === false 
        && strpos($cur, 'lse') === false 
        && strpos($cur, 'moic') === false 
        && strpos($cur, 'mpi') === false 
        && strpos($cur, 'taxris') === false 
        && strpos($cur, 'sezo') === false
        || $cur == 'config_provisions.php'
      ); 
    ?>
    <li class="<?= $is_repo ? 'active' : '' ?>">
      <a href="#repositorySub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_repo ? 'true' : 'false' ?>">
        <i class="fas fa-archive me-2"></i> Repository
      </a>
      <ul class="collapse list-unstyled <?= $is_repo ? 'show' : '' ?>" id="repositorySub">
        <li class="<?= $cur == 'config_provisions.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/config_provisions.php">Corporate Income Tax</a></li>
        <li class="<?= $cur == 'repo_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_individual.php">Individual Income Tax</a></li>
        <li class="<?= $cur == 'repo_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_vat.php">Value-Added Tax</a></li>
        <li class="<?= $cur == 'repo_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_customs.php">Customs Duty</a></li>
        <li class="<?= $cur == 'repo_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_excise.php">Excise Tax</a></li>
        <li class="<?= $cur == 'config_sez_provisions.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/config_sez_provisions.php">SEZ Developer/Investor</a></li>
        <li class="<?= $cur == 'repo_nontax.php' || $cur == 'repo_royalty.php' || $cur == 'provision_land_concession.php' ? 'active' : '' ?>">
          <?php $is_nontax_repo = ($cur == 'repo_nontax.php' || $cur == 'repo_royalty.php' || strpos($cur, 'provision_land') !== false); ?>
          <a href="#nonTaxRepoSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_nontax_repo ? 'true' : 'false' ?>">
            <i class="fas fa-archive me-2"></i> Non-Tax
          </a>
          <ul class="collapse list-unstyled <?= $is_nontax_repo ? 'show' : '' ?>" id="nonTaxRepoSub">
            <li class="<?= $cur == 'provision_land_concession.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/provision_land_concession.php">Land Concession</a></li>
            <li class="<?= $cur == 'repo_nontax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_nontax.php">Natural Resource</a></li>
            <li class="<?= $cur == 'repo_royalty.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_royalty.php">Royalty Fee</a></li>
          </ul>
        </li>
      </ul>
    </li>

    <hr class="mx-3 opacity-25">

    <!-- Get Tax Data by Import from Excel -->
    <?php 
        $is_molsw = (strpos($cur, 'molsw') !== false && strpos($cur, 'api_') === false);
        $is_lse = (strpos($cur, 'lse') !== false && strpos($cur, 'api_') === false);
        $is_sezo = (strpos($cur, 'sezo') !== false && strpos($cur, 'api_') === false);
        $is_moic = (strpos($cur, 'moic') !== false && strpos($cur, 'api_') === false);
        $is_mpi = (strpos($cur, 'mpi') !== false && strpos($cur, 'api_') === false);
        $is_taxris = (strpos($cur, 'taxris') !== false && strpos($cur, 'api_') === false);
        $is_data_req = ($is_molsw || $is_lse || $is_sezo || $is_moic || $is_mpi || $is_taxris);
        $is_import_te = $cur == 'batches.php'
          || (strpos($cur, 'import_') !== false && !$is_data_req)
          || (strpos($cur, 'asycuda_') !== false && strpos($cur, 'te_asycuda_') === false && strpos($cur, 'api_') === false)
          || (strpos($cur, 'view_') !== false);
        $is_import_excel = ($is_data_req || $is_import_te);
    ?>
    <li class="<?= $is_import_excel ? 'active' : '' ?>">
      <a href="#importExcelSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_import_excel ? 'true' : 'false' ?>">
        <i class="fas fa-file-excel me-2"></i> Get Tax Data by Import from Excel
      </a>
      <ul class="collapse list-unstyled <?= $is_import_excel ? 'show' : '' ?>" id="importExcelSub">
        
        <!-- Data Requirement to Identify the type of Repository -->
        <li class="<?= $is_data_req ? 'active' : '' ?>">
          <a href="#importDataReqSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_data_req ? 'true' : 'false' ?>">
            <i class="fas fa-database me-2"></i> Data Requirement to Identify the type of Repository
          </a>
          <ul class="collapse list-unstyled ps-3 <?= $is_data_req ? 'show' : '' ?>" id="importDataReqSub">
            <!-- Import Data from TaxRIS and Others -->
            <li>
              <a href="#importTaxrisSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= ($is_molsw || $is_lse || $is_moic || $is_mpi || $is_taxris) ? 'true' : 'false' ?>">
                <i class="fas fa-file-import me-2"></i> Import Data from TaxRIS and Others
              </a>
              <ul class="collapse list-unstyled ps-3 <?= ($is_molsw || $is_lse || $is_moic || $is_mpi || $is_taxris) ? 'show' : '' ?>" id="importTaxrisSub">
                <li class="<?= $cur == 'import_new_data.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_new_data.php"><i class="fas fa-plus me-2"></i>Consolidated Stakeholder Import</a></li>
                <li class="<?= $is_moic ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_moic.php"><i class="fas fa-building me-2"></i>Get data from MOIC</a></li>
                <li class="<?= $is_taxris ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_taxris.php"><i class="fas fa-file-invoice-dollar me-2"></i>Get data from TaxRIS</a></li>
                <li class="<?= $is_mpi ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_mpi.php"><i class="fas fa-chart-line me-2"></i>Get data from MPI</a></li>
                <li class="<?= $is_molsw ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_molsw.php"><i class="fas fa-users me-2"></i>Get data from MOLSW</a></li>
                <li class="<?= $is_lse ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_lse.php"><i class="fas fa-chart-line me-2"></i>Get data from Lao Stock Exchange</a></li>
              </ul>
            </li>
            <li class="<?= $is_sezo ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_sezo.php"><i class="fas fa-industry me-2"></i>Get data from SEZO</a></li>
          </ul>
        </li>

        <!-- Data Requirement to estimate TE -->
        <li class="<?= $is_import_te ? 'active' : '' ?>">
          <a href="#importTESub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_import_te ? 'true' : 'false' ?>">
            <i class="fas fa-calculator me-2"></i> Data Requirement to estimate TE
          </a>
          <ul class="collapse list-unstyled ps-3 <?= $is_import_te ? 'show' : '' ?>" id="importTESub">
            <?php $is_asycuda = ((strpos($cur, 'asycuda_') !== false && strpos($cur, 'te_asycuda_') === false) || $cur == 'import_asycuda.php'); ?>
            <li class="<?= $is_asycuda ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/asycuda_index.php" class="dropdown-toggle" aria-expanded="<?= $is_asycuda ? 'true' : 'false' ?>">Data from ASYCUDA</a>
                <ul class="collapse list-unstyled ps-3 <?= $is_asycuda ? 'show' : '' ?>" id="asySub">
                    <li class="<?= $cur == 'import_asycuda.php' || $cur == 'view_asycuda.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_asycuda.php">Import New Data</a></li>
                    <li class="<?= $cur == 'asycuda_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/asycuda_customs.php">Custom Duty</a></li>
                    <li class="<?= $cur == 'asycuda_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/asycuda_excise.php">Excise Tax</a></li>
                    <li class="<?= $cur == 'asycuda_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/asycuda_vat.php">Import VAT</a></li>
                </ul>
            </li>
            <li class="<?= $cur == 'import_cit.php' || $cur == 'view_companies.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_cit.php">Profit Tax</a></li>
            <li class="<?= $cur == 'import_individual.php' || $cur == 'view_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_individual.php">Individual Tax</a></li>
            <!-- <li class="<?= $cur == 'import_salary.php' || $cur == 'view_salary.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_salary.php">Salary Tax</a></li> -->
            <li class="<?= $cur == 'import_domestic_vat.php' || $cur == 'view_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_domestic_vat.php">Domestic VAT</a></li>
            <li class="<?= $cur == 'import_sez_dev.php' || $cur == 'view_sez_dev.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_sez_dev.php">For SEZ Developers</a></li>
            <li class="<?= $cur == 'import_sez_inv.php' || $cur == 'view_sez_inv.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_sez_inv.php">For SEZ Investors</a></li>
            <li class="<?= $cur == 'import_land_concession.php' || $cur == 'repo_land_concession.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_land_concession.php">Non-Tax: Land concession</a></li>
            <li class="<?= $cur == 'import_resource.php' || $cur == 'view_resource.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_resource.php">Non-Tax: Resource fee</a></li>
            <li class="<?= $cur == 'import_royalty.php' || $cur == 'view_royalty.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/import_royalty.php">Non-Tax: Royalty fee</a></li>
            <li class="<?= $cur == 'batches.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/batches.php"><i class="fas fa-layer-group me-2"></i>Batch Management</a></li>
          </ul>
        </li>
      </ul>
    </li>

    <!-- Get Tax Data by API -->
    <?php 
        $is_api_moic = ($cur == 'api_moic.php');
        $is_api_mpi = ($cur == 'api_mpi.php');
        $is_api_taxris = ($cur == 'api_taxris.php');
        $is_api_asycuda = ($cur == 'api_asycuda.php');
        $is_api_sezo = ($cur == 'api_sezo.php');
        $is_api_lse = ($cur == 'api_lse.php');
        $is_api = ($is_api_moic || $is_api_mpi || $is_api_taxris || $is_api_asycuda || $is_api_sezo || $is_api_lse);
    ?>
    <li class="<?= $is_api ? 'active' : '' ?>">
      <a href="#apiSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_api ? 'true' : 'false' ?>">
        <i class="fas fa-cloud-download-alt me-2"></i> Get Tax Data by API
      </a>
      <ul class="collapse list-unstyled <?= $is_api ? 'show' : '' ?>" id="apiSub">
        <li class="<?= $is_api_moic ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/api_moic.php"><i class="fas fa-building me-2"></i>MOIC</a></li>
        <li class="<?= $is_api_mpi ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/api_mpi.php"><i class="fas fa-chart-line me-2"></i>MPI</a></li>
        <li class="<?= $is_api_taxris ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/api_taxris.php"><i class="fas fa-file-invoice-dollar me-2"></i>TaxRIS</a></li>
        <li class="<?= $is_api_asycuda ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/api_asycuda.php"><i class="fas fa-ship me-2"></i>ASYCUDA</a></li>
        <li class="<?= $is_api_sezo ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/api_sezo.php"><i class="fas fa-industry me-2"></i>SEZO</a></li>
        <li class="<?= $is_api_lse ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/api_lse.php"><i class="fas fa-chart-line me-2"></i>Lao Stock Exchange</a></li>
      </ul>
    </li>

    <!-- TE Calculation -->
    <?php
        $is_calc = (strpos($cur, 'te_') !== false || $cur == 'calculator.php' || strpos($cur, 'calculate_') !== false);
        $is_asy_calc = (strpos($cur, 'te_asycuda_') !== false);
        $is_nontax_calc = ($cur == 'te_nontax.php' || $cur == 'te_royalty.php' || $cur == 'calculate_land_concession.php');
    ?>
    <li class="<?= $is_calc ? 'active' : '' ?>">
      <a href="#calculationSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_calc ? 'true' : 'false' ?>">
        <i class="fas fa-laptop-code me-2"></i> TE Calculation
      </a>
      <ul class="collapse list-unstyled <?= $is_calc ? 'show' : '' ?>" id="calculationSub">
        <li class="<?= $cur == 'calculator.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/calculator.php">Profit Tax TE</a></li>
        <li class="<?= $cur == 'te_individual.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_individual.php">Individual Tax TE</a></li>
        <!-- <li class="<?= $cur == 'te_salary_tax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_salary_tax.php">Salary Tax TE</a></li> -->
        <li class="<?= $cur == 'te_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_vat.php">Domestic VAT TE</a></li>
        <li class="<?= $is_asy_calc ? 'active' : '' ?>">
          <a href="#asyCalcSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_asy_calc ? 'true' : 'false' ?>">Data from ASYCUDA</a>
          <ul class="collapse list-unstyled ps-3 <?= $is_asy_calc ? 'show' : '' ?>" id="asyCalcSub">
            <li class="<?= $cur == 'te_asycuda_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_asycuda_customs.php">Customs Duty TE</a></li>
            <li class="<?= $cur == 'te_asycuda_excise.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_asycuda_excise.php">Excise Tax TE</a></li>
            <li class="<?= $cur == 'te_asycuda_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_asycuda_vat.php">Import VAT TE</a></li>
          </ul>
        </li>
        <li class="<?= $cur == 'te_sez_dev.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_sez_dev.php">For SEZ Developers TE</a></li>
        <li class="<?= $cur == 'te_sez_inv.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_sez_inv.php">For SEZ Investors TE</a></li>
        <li class="<?= $is_nontax_calc ? 'active' : '' ?>">
          <a href="#nonTaxCalcSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_nontax_calc ? 'true' : 'false' ?>">
            <i class="fas fa-calculator me-2"></i> Non-Tax
          </a>
          <ul class="collapse list-unstyled <?= $is_nontax_calc ? 'show' : '' ?>" id="nonTaxCalcSub">
            <li class="<?= $cur == 'calculate_land_concession.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/calculate_land_concession.php">Land concession TE</a></li>
            <li class="<?= $cur == 'te_nontax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_nontax.php">Resource fee TE</a></li>
            <li class="<?= $cur == 'te_royalty.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/te_royalty.php">Royalty fee TE</a></li>
          </ul>
        </li>
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
        <li class="<?= $cur == 'report_revenue.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_revenue.php">TE by Tax Type (% of Revenue)</a></li>
        <?php
        $is_provision_report = ($cur == 'report_total_provision.php' || $cur == 'report_provisions.php' || $cur == 'report_individual_provision.php' || $cur == 'report_salary_tax_provision.php' || $cur == 'report_vat_provision.php' || $cur == 'report_sez_dev_provision.php' || $cur == 'report_sez_inv_provision.php' || $cur == 'report_nontax_provision.php');
        ?>
        <li class="<?= $is_provision_report ? 'active' : '' ?>">
          <a href="#provisionReportSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_provision_report ? 'true' : 'false' ?>">
            <i class="fas fa-list-alt me-2"></i> TE by Provision
          </a>
          <ul class="collapse list-unstyled ps-3 <?= $is_provision_report ? 'show' : '' ?>" id="provisionReportSub">
            <li class="<?= $cur == 'report_total_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_total_provision.php"><i class="fas fa-layer-group me-1 text-warning"></i> <strong>Total TE by Provision</strong></a></li>
            <li class="<?= $cur == 'report_provisions.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_provisions.php">Profit Tax TE</a></li>
            <li class="<?= $cur == 'report_individual_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_individual_provision.php">Individual Income Tax TE</a></li>
            <!-- <li class="<?= $cur == 'report_salary_tax_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_salary_tax_provision.php">Salary Tax TE</a></li> -->
            <li class="<?= $cur == 'report_vat_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_vat_provision.php">Domestic VAT TE</a></li>
            <li class="<?= $cur == 'report_sez_dev_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_sez_dev_provision.php">SEZ Developer TE</a></li>
            <li class="<?= $cur == 'report_sez_inv_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_sez_inv_provision.php">SEZ Investor TE</a></li>
            <li class="<?= $cur == 'report_nontax_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_nontax_provision.php">Non-Tax: Land Concession TE</a></li>
            <li class="<?= $cur == 'report_nontax_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_nontax_provision.php">Non-Tax: Resource Fee TE</a></li>
            <li class="<?= $cur == 'report_nontax_provision.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_nontax_provision.php">Non-Tax: Royalty Fee TE</a></li>
          </ul>
        </li>
        <?php
        $is_customs_report = ($cur == 'report_total_customs.php' || $cur == 'report_customs_duty.php' || $cur == 'report_excise_tax.php' || $cur == 'report_import_vat.php');
        ?>
        <li class="<?= $is_customs_report ? 'active' : '' ?>">
          <a href="#customsReportSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_customs_report ? 'true' : 'false' ?>">
            <i class="fas fa-ship me-2"></i> TE by Customs Regime Code-Payment Condition Code
          </a>
          <ul class="collapse list-unstyled ps-3 <?= $is_customs_report ? 'show' : '' ?>" id="customsReportSub">
            <li class="<?= $cur == 'report_total_customs.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_total_customs.php"><i class="fas fa-layer-group me-1 text-warning"></i> <strong>Total TE by Customs Regime</strong></a></li>
            <li class="<?= $cur == 'report_customs_duty.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_customs_duty.php">Custom Duty</a></li>
            <li class="<?= $cur == 'report_excise_tax.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_excise_tax.php">Excise Tax</a></li>
            <li class="<?= $cur == 'report_import_vat.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/report_import_vat.php">Import VAT</a></li>
          </ul>
        </li>
      </ul>
    </li>

    <!-- Notification -->
    <?php $is_notif = ($cur == 'notification_mgmt.php' || $cur == 'repo_milestones.php'); ?>
    <li class="<?= $is_notif ? 'active' : '' ?>">
      <a href="#notificationSub" data-bs-toggle="collapse" class="dropdown-toggle" aria-expanded="<?= $is_notif ? 'true' : 'false' ?>">
        <i class="fas fa-bell me-2"></i> Notification
      </a>
      <ul class="collapse list-unstyled <?= $is_notif ? 'show' : '' ?>" id="notificationSub">
        <li class="<?= $cur == 'repo_milestones.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/repo_milestones.php">Concession Compliance</a></li>
        <li class="<?= $cur == 'notification_mgmt.php' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/pages/notification_mgmt.php">Notification Management</a></li>
      </ul>
    </li>
  </ul>
</nav>
