<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection();

$batch = $_GET["batch"] ?? "";
$companies = [];
if ($batch) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE import_batch_id = ? ORDER BY id");
    $stmt->execute([$batch]);
    $companies = $stmt->fetchAll();
}
require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="import_cit.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Company Data</h2>
      <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> — <strong><?= count($companies) ?></strong> companies</p>
    </div>
    <a href="calculator.php?batch=<?= urlencode($batch) ?>" class="btn btn-success"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <table class="table table-bordered table-hover datatable w-100" style="font-size:0.85em">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Year</th><th>TIN</th><th>Company Name</th><th>Province</th><th>Sector</th>
          <th>VAT?</th><th>Staff</th><th>Revenue</th><th>Net Profit</th><th>PT Paid</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $i => $c): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= $c["tax_year"] ?></td>
          <td><small class="font-monospace"><?= htmlspecialchars($c["tin"]) ?></small></td>
          <td><?= htmlspecialchars($c["company_name"]) ?></td>
          <td><?= htmlspecialchars($c["province"]) ?></td>
          <td><?= htmlspecialchars($c["sector"]) ?></td>
          <td class="text-center"><span class="badge bg-<?= $c["is_vat_holder"] ? "success" : "secondary" ?>"><?= $c["is_vat_holder"] ? "YES" : "NO" ?></span></td>
          <td class="text-end"><?= number_format($c["staff_count"]) ?></td>
          <td class="text-end"><?= number_format($c["revenue"], 0) ?></td>
          <td class="text-end"><?= number_format($c["net_profit"], 0) ?></td>
          <td class="text-end text-danger fw-bold"><?= number_format($c["pt_paid"], 0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
