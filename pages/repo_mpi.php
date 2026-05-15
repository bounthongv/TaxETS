<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM repo_mpi WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

// Fetch Records
$records = $pdo->query("
    SELECT r.*, s.sector_name 
    FROM repo_mpi r
    LEFT JOIN business_sectors s ON r.sector_id = s.id
    ORDER BY r.id DESC
")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-chart-line me-2"></i> MPI Project Repository</h2>
            <p class="text-muted">Manage investment projects and incentives granted by the Ministry of Planning and Investment.</p>
        </div>
        <div>
            <a href="add_mpi.php" class="btn btn-success"><i class="fas fa-plus me-2"></i> Add New Project</a>
            <a href="import_mpi.php" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Import from Excel</a>
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
                    <th>Project Name</th>
                    <th>License Date</th>
                    <th>Sector</th>
                    <th>Tax Holiday</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r['tin']) ?></small></td>
                    <td><?= htmlspecialchars($r['project_name']) ?></td>
                    <td><?= $r['investment_license_date'] ?></td>
                    <td><?= htmlspecialchars($r['sector_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($r['tax_holiday_period']) ?></td>
                    <td class="text-center">
                        <a href="edit_mpi.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
