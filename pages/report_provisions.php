<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$selected_batch = $_GET["batch"] ?? "";

// Get all active batches for selection
$batches = $pdo->query("SELECT DISTINCT import_batch_id, tax_year FROM companies ORDER BY tax_year DESC")->fetchAll();

$report_data = [];
if ($selected_batch) {
    // 1. Get all provisions
    $provisions = $pdo->query("SELECT id, provision_number, description FROM profit_provisions ORDER BY provision_number")->fetchAll();
    
    // 2. Efficiently count matches per provision from the te_profit_result table
    // matched_provisions is a comma-separated string like "1, 7, 9"
    foreach ($provisions as $p) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(profit_tax_te / (SELECT COUNT(*) + 1 FROM (SELECT 1) as t WHERE FIND_IN_SET(?, REPLACE(matched_provisions, \", \", \",\")) )) as total_te 
                               FROM te_profit_result tr
                               JOIN companies c ON tr.company_id = c.id
                               WHERE c.import_batch_id = ? AND FIND_IN_SET(?, REPLACE(matched_provisions, \", \", \",\"))");
                               
        // Note: The logic for "splitting" TE between multiple provisions can be complex. 
        // For now, we show the total TE for any company that matched this provision.
        $stmt = $pdo->prepare("SELECT COUNT(*) as company_count, SUM(tr.profit_tax_te) as sum_te 
                               FROM te_profit_result tr
                               JOIN companies c ON tr.company_id = c.id
                               WHERE c.import_batch_id = ? AND (tr.matched_provisions LIKE ? OR tr.matched_provisions = ?)");
        $stmt->execute([$selected_batch, $p['provision_number'].', %', $p['provision_number']]);
        $res = $stmt->fetch();
        
        $report_data[] = [
            'number' => $p['provision_number'],
            'description' => $p['description'],
            'count' => $res['company_count'],
            'te' => $res['sum_te'] ?? 0
        ];
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-chart-pie me-2"></i> TE by Provision</h2>
      <p class="text-muted">Summary of Tax Expenditure aggregated by individual legal provisions.</p>
    </div>
    <div class="d-flex gap-2">
      <select class="form-select" onchange="location.href=\"?batch=\" + this.value">
        <option value="">-- Change Batch --</option>
        <?php foreach ($batches as $b): ?>
        <option value="<?= htmlspecialchars($b["import_batch_id"]) ?>" <?= ($selected_batch === $b["import_batch_id"]) ? "selected" : "" ?>>
          <?= htmlspecialchars($b["import_batch_id"]) ?> (<?= $b["tax_year"] ?>)
        </option>
        <?php endforeach; ?>
      </select>
      <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i></button>
    </div>
  </div>
</div>

<?php if (!$selected_batch): ?>
<div class="alert alert-info">Please select a calculation batch from the dropdown above to view the report.</div>
<?php else: ?>
<div class="card shadow-sm">
  <div class="card-body">
    <table class="table table-hover table-bordered datatable">
      <thead class="table-light">
        <tr>
          <th style="width: 80px;">Prov #</th>
          <th>Description of Provision / Legal Basis</th>
          <th class="text-center">Matched Companies</th>
          <th class="text-end">Total TE (LAK)</th>
          <th class="text-end">% of Total</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $grand_total = array_sum(array_column($report_data, 'te'));
        foreach ($report_data as $row): 
        ?>
        <tr>
          <td class="text-center fw-bold text-primary"><?= $row['number'] ?></td>
          <td><?= htmlspecialchars($row['description']) ?></td>
          <td class="text-center"><?= number_format($row['count']) ?></td>
          <td class="text-end fw-bold"><?= number_format($row['te'], 0) ?></td>
          <td class="text-end small">
            <?= ($grand_total > 0) ? round(($row['te'] / $grand_total) * 100, 1) : 0 ?>%
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-dark">
          <td colspan="3" class="text-end">Grand Total TE for this Batch:</td>
          <td class="text-end"><?= number_format($grand_total, 0) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
