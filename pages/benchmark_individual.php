<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$errors = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_bracket') {
            $stmt = $pdo->prepare("INSERT INTO bm_pit_employment (start_year, end_year, min_income, max_income, rate_percentage) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$_POST['start_year'],
                (int)$_POST['end_year'],
                (float)$_POST['min_income'],
                $_POST['max_income'] !== '' ? (float)$_POST['max_income'] : null,
                (float)$_POST['rate_percentage']
            ]);
            $success = 'Bracket added successfully.';
        } elseif ($action === 'edit_bracket') {
            $stmt = $pdo->prepare("UPDATE bm_pit_employment SET start_year=?, end_year=?, min_income=?, max_income=?, rate_percentage=? WHERE id=?");
            $stmt->execute([
                (int)$_POST['start_year'],
                (int)$_POST['end_year'],
                (float)$_POST['min_income'],
                $_POST['max_income'] !== '' ? (float)$_POST['max_income'] : null,
                (float)$_POST['rate_percentage'],
                (int)$_POST['id']
            ]);
            $success = 'Bracket updated successfully.';
        } elseif ($action === 'delete_bracket') {
            $pdo->prepare("DELETE FROM bm_pit_employment WHERE id=?")->execute([(int)$_POST['id']]);
            $success = 'Bracket deleted.';
        } elseif ($action === 'add_flat') {
            $stmt = $pdo->prepare("INSERT INTO bm_pit_flat_rates (start_year, end_year, income_type, rate_percentage) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                (int)$_POST['start_year'],
                (int)$_POST['end_year'],
                $_POST['income_type'],
                (float)$_POST['rate_percentage']
            ]);
            $success = 'Flat rate added successfully.';
        } elseif ($action === 'edit_flat') {
            $stmt = $pdo->prepare("UPDATE bm_pit_flat_rates SET start_year=?, end_year=?, income_type=?, rate_percentage=? WHERE id=?");
            $stmt->execute([
                (int)$_POST['start_year'],
                (int)$_POST['end_year'],
                $_POST['income_type'],
                (float)$_POST['rate_percentage'],
                (int)$_POST['id']
            ]);
            $success = 'Flat rate updated successfully.';
        } elseif ($action === 'delete_flat') {
            $pdo->prepare("DELETE FROM bm_pit_flat_rates WHERE id=?")->execute([(int)$_POST['id']]);
            $success = 'Flat rate deleted.';
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Fetch data
$brackets = [];
$flat_rates = [];
try {
    $brackets = $pdo->query("SELECT * FROM bm_pit_employment ORDER BY start_year DESC, min_income ASC")->fetchAll();
    $flat_rates = $pdo->query("SELECT * FROM bm_pit_flat_rates ORDER BY income_type ASC")->fetchAll();
} catch (Exception $e) { $errors[] = $e->getMessage(); }

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
    <div class="col-12 text-start">
        <h2><i class="fas fa-user-tie me-2"></i> Individual Income Tax Benchmark</h2>
        <p class="text-muted">Manage progressive tax brackets for employment and flat rates for other income types.</p>
    </div>
</div>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= htmlspecialchars(implode('; ', $errors)) ?></div><?php endif; ?>

<ul class="nav nav-tabs mb-4" id="pitTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment" type="button"><i class="fas fa-briefcase me-2"></i> Employment Brackets</button></li>
    <li class="nav-item"><button class="nav-link" id="flat-tab" data-bs-toggle="tab" data-bs-target="#flat" type="button"><i class="fas fa-percent me-2"></i> Flat Rates (Other Income)</button></li>
</ul>

<div class="tab-content" id="pitTabsContent">
    <!-- Employment Brackets Tab -->
    <div class="tab-pane fade show active" id="employment" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-layer-group me-2"></i> Progressive Tax Brackets</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBracketModal"><i class="fas fa-plus me-1"></i> Add Bracket</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>Years</th><th class="text-end">Min Income (LAK/mo)</th><th class="text-end">Max Income (LAK/mo)</th><th class="text-center">Rate</th><th class="text-center">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brackets as $b): ?>
                        <tr>
                            <td><?= $b["start_year"] ?> - <?= $b["end_year"] ?></td>
                            <td class="text-end"><?= number_format($b["min_income"]) ?></td>
                            <td class="text-end"><?= $b["max_income"] ? number_format($b["max_income"]) : '<span class="text-muted fw-bold">&infin;</span>' ?></td>
                            <td class="text-center fw-bold"><?= $b["rate_percentage"] ?>%</td>
                            <td class="text-center">
                                 <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editBracketModal<?= $b['id'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this bracket?')">
                                    <input type="hidden" name="action" value="delete_bracket">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($brackets)): ?><tr><td colspan="5" class="text-center text-muted py-4">No brackets defined yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Flat Rates Tab -->
    <div class="tab-pane fade" id="flat" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-tags me-2"></i> Flat Rates for Other Income</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFlatModal"><i class="fas fa-plus me-1"></i> Add Factor</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>Income Type</th><th class="text-center">Rate</th><th>Years</th><th class="text-center">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flat_rates as $f): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($f["income_type"]) ?></td>
                            <td class="text-center fw-bold text-primary"><?= $f["rate_percentage"] ?>%</td>
                            <td><?= $f["start_year"] ?> - <?= $f["end_year"] ?></td>
                            <td class="text-center">
                                 <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editFlatModal<?= $f['id'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this flat rate?')">
                                    <input type="hidden" name="action" value="delete_flat">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($flat_rates)): ?><tr><td colspan="4" class="text-center text-muted py-4">No flat rates defined yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Bracket Modal -->
<div class="modal fade" id="addBracketModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i> Add Bracket</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="add_bracket">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-bold small">Start Year</label><input type="number" name="start_year" class="form-control" value="<?= date('Y') ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">End Year</label><input type="number" name="end_year" class="form-control" value="2099" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Min Income (LAK/mo)</label><input type="number" name="min_income" class="form-control" step="0.01" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Max Income (LAK/mo) <small class="text-muted">(leave empty for &infin;)</small></label><input type="number" name="max_income" class="form-control" step="0.01"></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Rate (%)</label><input type="number" name="rate_percentage" class="form-control" step="0.01" required></div>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
    </form>
</div></div></div>

<!-- Edit Bracket Modals -->
<?php foreach ($brackets as $b): ?>
<div class="modal fade" id="editBracketModal<?= $b['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Bracket</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="edit_bracket">
        <input type="hidden" name="id" value="<?= $b['id'] ?>">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-bold small">Start Year</label><input type="number" name="start_year" class="form-control" value="<?= $b['start_year'] ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">End Year</label><input type="number" name="end_year" class="form-control" value="<?= $b['end_year'] ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Min Income (LAK/mo)</label><input type="number" name="min_income" class="form-control" step="0.01" value="<?= $b['min_income'] ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Max Income (LAK/mo) <small class="text-muted">(leave empty for &infin;)</small></label><input type="number" name="max_income" class="form-control" step="0.01" value="<?= $b['max_income'] ?>"></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Rate (%)</label><input type="number" name="rate_percentage" class="form-control" step="0.01" value="<?= $b['rate_percentage'] ?>" required></div>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
    </form>
</div></div></div>
<?php endforeach; ?>

<!-- Add Flat Rate Modal -->
<div class="modal fade" id="addFlatModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i> Add Flat Rate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="add_flat">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-12"><label class="form-label fw-bold small">Income Type</label><input type="text" name="income_type" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Start Year</label><input type="number" name="start_year" class="form-control" value="<?= date('Y') ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">End Year</label><input type="number" name="end_year" class="form-control" value="2099" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Rate (%)</label><input type="number" name="rate_percentage" class="form-control" step="0.01" required></div>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
    </form>
</div></div></div>

<!-- Edit Flat Rate Modals -->
<?php foreach ($flat_rates as $f): ?>
<div class="modal fade" id="editFlatModal<?= $f['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Flat Rate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="edit_flat">
        <input type="hidden" name="id" value="<?= $f['id'] ?>">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-12"><label class="form-label fw-bold small">Income Type</label><input type="text" name="income_type" class="form-control" value="<?= htmlspecialchars($f['income_type']) ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Start Year</label><input type="number" name="start_year" class="form-control" value="<?= $f['start_year'] ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">End Year</label><input type="number" name="end_year" class="form-control" value="<?= $f['end_year'] ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold small">Rate (%)</label><input type="number" name="rate_percentage" class="form-control" step="0.01" value="<?= $f['rate_percentage'] ?>" required></div>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
    </form>
</div></div></div>
<?php endforeach; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
