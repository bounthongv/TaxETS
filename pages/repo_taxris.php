<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM repo_taxris WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

// Fetch Records
$records = $pdo->query("SELECT * FROM repo_taxris ORDER BY year DESC, tin ASC")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-file-invoice-dollar me-2"></i> TaxRIS Data Repository</h2>
            <p class="text-muted">Manage yearly enterprise profit and tax value data from TaxRIS.</p>
        </div>
        <div>
            <a href="add_taxris.php" class="btn btn-success"><i class="fas fa-plus me-2"></i> Add Yearly Data</a>
            <a href="import_taxris.php" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Import from Excel</a>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-bordered table-hover datatable">
            <thead class="table-light">
                <tr>
                    <th>TIN</th>
                    <th>Year</th>
                    <th>Revenue</th>
                    <th>Expense</th>
                    <th>Profit</th>
                    <th>Tax Paid</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r['tin']) ?></small></td>
                    <td class="text-center"><?= $r['year'] ?></td>
                    <td class="text-end"><?= number_format($r['revenue'], 0) ?></td>
                    <td class="text-end"><?= number_format($r['expense'], 0) ?></td>
                    <td class="text-end fw-bold"><?= number_format($r['net_profit'], 0) ?></td>
                    <td class="text-end text-success fw-bold"><?= number_format($r['tax_paid'], 0) ?></td>
                    <td class="text-center">
                        <a href="edit_taxris.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
