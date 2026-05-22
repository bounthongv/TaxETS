<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$id = $_GET['id'] ?? null;
$record = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM import_salary_tax_data WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "batch_id"                  => $record['batch_id'] ?? ("MANUAL_" . date("YmdHis")),
        "tax_year"                  => $_POST["tax_year"],
        "tin"                       => $_POST["tin"],
        "filing_type"               => $_POST["filing_type"],
        "filing_period"             => $_POST["filing_period"],
        "input_date"                => $_POST["input_date"],
        "total_salaries_wages_cash" => $_POST["total_salaries_wages_cash"] ?: 0,
        "other_fringe_benefits"     => $_POST["other_fringe_benefits"] ?: 0,
        "total_taxable_amount"      => $_POST["total_taxable_amount"] ?: 0,
        "tax_exempt_amount"         => $_POST["tax_exempt_amount"] ?: 0,
        "tax_amount"                => $_POST["tax_amount"] ?: 0,
        "adjustment_amount"         => $_POST["adjustment_amount"] ?: 0,
        "carryforward_amount"       => $_POST["carryforward_amount"] ?: 0,
        "total_amount_due"          => $_POST["total_amount_due"] ?: 0,
        "provision_number"          => $_POST["provision_number"]
    ];

    try {
        if ($id) {
            $sql = "UPDATE import_salary_tax_data SET " . implode("=?, ", array_keys($data)) . "=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($data), [$id]));
            $message = "Record updated successfully.";
        } else {
            $data["batch_id"] = "MANUAL_" . date("YmdHis");
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO import_salary_tax_data ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            $message = "Record added successfully.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-edit me-2 text-primary"></i> <?= $id ? 'Edit' : 'Add' ?> Salary Tax Record</h2>
    <p class="text-muted">Manually enter or update monthly salary tax filing data.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="POST">
      <div class="row g-3 mb-4">
        <div class="col-md-2">
          <label class="form-label fw-bold">TIN</label>
          <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($record['tin'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-bold">Fiscal Year</label>
          <input type="number" name="tax_year" class="form-control" value="<?= htmlspecialchars($record['tax_year'] ?? date('Y')) ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-bold">Filing Type</label>
          <input type="text" name="filing_type" class="form-control" value="<?= htmlspecialchars($record['filing_type'] ?? 'Monthly') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Filing Period</label>
          <input type="text" name="filing_period" class="form-control" value="<?= htmlspecialchars($record['filing_period'] ?? '') ?>" placeholder="e.g. 05/2026" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Input Date</label>
          <input type="date" name="input_date" class="form-control" value="<?= htmlspecialchars($record['input_date'] ?? date('Y-m-d')) ?>">
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label fw-bold">Salaries & Wages (Cash)</label>
          <input type="number" step="0.01" name="total_salaries_wages_cash" class="form-control" value="<?= $record['total_salaries_wages_cash'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Other Fringe Benefits</label>
          <input type="number" step="0.01" name="other_fringe_benefits" class="form-control" value="<?= $record['other_fringe_benefits'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold text-primary">Total Taxable Amount</label>
          <input type="number" step="0.01" name="total_taxable_amount" class="form-control border-primary" value="<?= $record['total_taxable_amount'] ?? 0 ?>">
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label fw-bold text-warning">Tax Exempt Amount</label>
          <input type="number" step="0.01" name="tax_exempt_amount" class="form-control border-warning" value="<?= $record['tax_exempt_amount'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold text-success">Tax Amount Paid</label>
          <input type="number" step="0.01" name="tax_amount" class="form-control border-success" value="<?= $record['tax_amount'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Adjustment Amount</label>
          <input type="number" step="0.01" name="adjustment_amount" class="form-control" value="<?= $record['adjustment_amount'] ?? 0 ?>">
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label fw-bold">Carryforward Amount</label>
          <input type="number" step="0.01" name="carryforward_amount" class="form-control" value="<?= $record['carryforward_amount'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Total Amount Due</label>
          <input type="number" step="0.01" name="total_amount_due" class="form-control" value="<?= $record['total_amount_due'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Provision Number</label>
          <input type="text" name="provision_number" class="form-control" value="<?= htmlspecialchars($record['provision_number'] ?? '') ?>" placeholder="e.g. T21">
        </div>
      </div>

      <div class="mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
          <i class="fas fa-save me-2"></i> <?= $id ? 'Update Record' : 'Save Record' ?>
        </button>
        <a href="te_salary_tax.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
