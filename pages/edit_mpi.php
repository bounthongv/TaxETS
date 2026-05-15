<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM repo_mpi WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) { die("Record not found"); }

$sectors = $pdo->query("SELECT * FROM business_sectors WHERE active = 1 ORDER BY sector_name")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><a href="repo_mpi.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Edit MPI Project</h2>
        <p class="text-muted">Modify project details for <strong><?= htmlspecialchars($record['project_name']) ?></strong>.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="save_mpi.php" method="POST">
            <input type="hidden" name="id" value="<?= $record['id'] ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">TIN (Tax ID)</label>
                    <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($record['tin']) ?>" required readonly bg-light>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Project Name <span class="text-danger">*</span></label>
                    <input type="text" name="project_name" class="form-control" value="<?= htmlspecialchars($record['project_name']) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Date of Investment License</label>
                    <input type="date" name="investment_license_date" class="form-control" value="<?= $record['investment_license_date'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Sector</label>
                    <select name="sector_id" class="form-select">
                        <option value="">Select Sector</option>
                        <?php foreach ($sectors as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $record['sector_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['sector_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Tax Holiday Period</label>
                    <input type="text" name="tax_holiday_period" class="form-control" value="<?= htmlspecialchars($record['tax_holiday_period']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Activities</label>
                <textarea name="activities" class="form-control" rows="3"><?= htmlspecialchars($record['activities']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Incentives as outlined in the agreement signed with MPI</label>
                <textarea name="incentives" class="form-control" rows="3"><?= htmlspecialchars($record['incentives']) ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="repo_mpi.php" class="btn btn-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5"><i class="fas fa-save me-2"></i> Update Project Record</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
