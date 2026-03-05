<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();

// Fetch summary data for Profit Tax by Year
$profit_data = [];
try {
    $stmt = $pdo->query("
        SELECT c.tax_year, SUM(r.profit_tax_te) as total_te 
        FROM companies c 
        JOIN te_profit_result r ON r.company_id = c.id 
        GROUP BY c.tax_year 
        ORDER BY c.tax_year ASC
    ");
    $profit_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) { }

$years = array_keys($profit_data);
if (empty($years)) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT tax_year FROM companies ORDER BY tax_year ASC");
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { }
}
if (empty($years)) { $years = [date("Y")]; }

$tax_types = [
    'Profit Tax' => $profit_data,
    'Individual Tax' => [],
    'Value Added Tax (VAT)' => [],
    'Excise tax' => [],
    'Customs Duty' => [],
    'Non-Tax Fee' => []
];
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-file-invoice-dollar me-2"></i> Revenue Foregone from Tax Expenditure (Kip)</h2>
      <p class="text-muted">Summary of tax expenditures by tax type and year.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel</button>
      <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync-alt me-1"></i> Update Data</button>
    </div>
  </div>
</div>
<div class="card shadow-sm mb-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Tax Type Name</th>
            <?php foreach ($years as $year): ?>
              <th class="text-end"><?= htmlspecialchars($year) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tax_types as $type => $data): ?>
          <tr>
            <td class="fw-bold"><?= htmlspecialchars($type) ?></td>
            <?php foreach ($years as $year): ?>
              <td class="text-end">
                <?php 
                $val = $data[$year] ?? 0;
                echo $val > 0 ? number_format($val, 0) : '-';
                ?>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-warning fw-bold">
          <tr>
            <td>Grand Total</td>
            <?php foreach ($years as $year): ?>
              <td class="text-end">
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
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
