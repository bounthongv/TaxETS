<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM repo_moic WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Record deleted successfully.";
}

// Fetch Records
$records = $pdo->query("
    SELECT r.*, v.village_name 
    FROM repo_moic r
    LEFT JOIN villages v ON r.village_id = v.id
    ORDER BY r.id DESC
")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-building me-2"></i> MOIC Enterprise Repository</h2>
            <p class="text-muted">Manage enterprise records from the Ministry of Industry and Commerce.</p>
        </div>
        <div>
            <a href="add_moic.php" class="btn btn-success"><i class="fas fa-plus me-2"></i> Add New Enterprise</a>
            <a href="import_moic.php" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Import from Excel</a>
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
                    <th>Company Name</th>
                    <th>License Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><small class="font-monospace fw-bold"><?= htmlspecialchars($r['tin']) ?></small></td>
                    <td><?= htmlspecialchars($r['company_name']) ?></td>
                    <td><?= $r['license_date'] ?></td>
                    <td><span class="badge bg-<?= $r['is_active'] ? 'success' : 'secondary' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td class="text-center">
                        <a href="edit_moic.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
