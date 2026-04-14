<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();

// Initialize data containers
$profit_data = [];
$pit_data = [];
$vat_domestic_data = [];
$customs_data = [];
$excise_data = [];
$vat_import_data = [];
$annual_years = [];

try {
    // 1. Profit Tax (CIT) - Ensure year > 0
    $stmt = $pdo->query("SELECT c.tax_year, SUM(r.profit_tax_te) as total_te FROM companies c JOIN te_profit_result r ON r.company_id = c.id WHERE c.tax_year > 0 GROUP BY c.tax_year");
    $profit_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Individual Tax (PIT) - Ensure year > 0
    $stmt = $pdo->query("SELECT tax_year, SUM(te_amount) as total_te FROM te_individual_result WHERE tax_year > 0 GROUP BY tax_year");
    $pit_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. Value Added Tax (Domestic) - Ensure valid year
    $stmt = $pdo->query("SELECT YEAR(filing_period) as yr, SUM(expert_te) as total_te FROM import_vat_data WHERE filing_period IS NOT NULL AND filing_period != '0000-00-00' GROUP BY yr HAVING yr > 0");
    $vat_domestic_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. Customs Duty (ASYCUDA)
    $stmt = $pdo->query("SELECT YEAR(COALESCE(NULLIF(ai.receipt_date, '0000-00-00'), NULLIF(ai.assess_date, '0000-00-00'), ai.doc_date)) as yr, SUM(r.customs_te) as total_te 
                         FROM te_asycuda_result r 
                         JOIN asycuda_imports ai ON r.asycuda_id = ai.id 
                         GROUP BY yr HAVING yr > 0");
    $customs_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 5. Excise Tax (ASYCUDA)
    $stmt = $pdo->query("SELECT YEAR(COALESCE(NULLIF(ai.receipt_date, '0000-00-00'), NULLIF(ai.assess_date, '0000-00-00'), ai.doc_date)) as yr, SUM(r.excise_te) as total_te 
                         FROM te_asycuda_result r 
                         JOIN asycuda_imports ai ON r.asycuda_id = ai.id 
                         GROUP BY yr HAVING yr > 0");
    $excise_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 6. Import VAT (ASYCUDA)
    $stmt = $pdo->query("SELECT YEAR(COALESCE(NULLIF(ai.receipt_date, '0000-00-00'), NULLIF(ai.assess_date, '0000-00-00'), ai.doc_date)) as yr, SUM(r.vat_te) as total_te 
                         FROM te_asycuda_result r 
                         JOIN asycuda_imports ai ON r.asycuda_id = ai.id 
                         GROUP BY yr HAVING yr > 0");
    $vat_import_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Collect all unique years and filter out null/0/invalid
    $raw_years = array_unique(array_merge(
        array_keys($profit_data),
        array_keys($pit_data),
        array_keys($vat_domestic_data),
        array_keys($customs_data),
        array_keys($excise_data),
        array_keys($vat_import_data)
    ));
    
    $annual_years = array_filter($raw_years, function($y) {
        return is_numeric($y) && $y > 1900 && $y < 2100; // Reasonable range
    });
    sort($annual_years);

} catch (Exception $e) {
    // Basic error handling for the UI
    echo '<div class="alert alert-danger">Error aggregating data: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

if (empty($annual_years)) { $annual_years = [date("Y")]; }

$tax_types = [
    'Corporate Income Tax (Profit Tax)' => $profit_data,
    'Individual Income Tax (PIT)'       => $pit_data,
    'Domestic Value Added Tax (VAT)'    => $vat_domestic_data,
    'Customs Duty (Import)'             => $customs_data,
    'Excise Tax (Import)'               => $excise_data,
    'Import Value Added Tax (VAT)'      => $vat_import_data
];
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-file-invoice-dollar me-2"></i> Revenue Foregone from Tax Expenditure (Kip)</h2>
      <p class="text-muted">Consolidated summary of tax expenditures across all tax regimes and years.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel</button>
      <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync-alt me-1"></i> Update Data</button>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="table-light small text-uppercase fw-bold">
          <tr>
            <th class="ps-4" style="width: 300px;">Tax Type Category</th>
            <?php foreach ($annual_years as $year): ?>
              <th class="text-end pe-4"><?= htmlspecialchars($year) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tax_types as $type => $data): ?>
          <tr>
            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($type) ?></td>
            <?php foreach ($annual_years as $year): ?>
              <td class="text-end pe-4">
                <?php 
                $val = $data[$year] ?? 0;
                echo $val > 0 ? '<strong>' . number_format($val, 0) . '</strong>' : '<span class="text-muted opacity-50">-</span>';
                ?>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-primary fw-bold">
          <tr>
            <td class="ps-4">Total Revenue Foregone</td>
            <?php foreach ($annual_years as $year): ?>
              <td class="text-end pe-4">
                <?php 
                $total = 0;
                foreach ($tax_types as $data) { $total += ($data[$year] ?? 0); }
                echo $total > 0 ? number_format($total, 0) : '-';
                ?>
              </td>
            <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<div class="alert bg-light border text-muted small">
    <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> This report aggregates real-time data from result tables for CIT and PIT, while Domestic VAT and ASYCUDA data are pulled from their respective import/assessment modules. Click <strong>"Update Data"</strong> to refresh calculations if new imports have been processed.
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
