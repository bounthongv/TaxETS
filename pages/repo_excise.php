<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if (isset($_GET["delete"])) {
    try {
        $pdo->prepare("DELETE FROM excise_provisions WHERE id = ?")->execute([$_GET["delete"]]);
        $message = "Provision deleted.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "add_provision") {
            $stmt = $pdo->prepare("INSERT INTO excise_provisions (provision_number, start_year, end_year, legal_basis, description, purpose, type_of_te) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST["provision_number"], $_POST["start_year"], $_POST["end_year"], $_POST["legal_basis"], $_POST["description"], $_POST["purpose"], $_POST["type_of_te"]]);
            $message = "Excise provision added.";
        } elseif ($_POST["action"] == "edit_provision") {
            $stmt = $pdo->prepare("UPDATE excise_provisions SET provision_number=?, start_year=?, end_year=?, legal_basis=?, description=?, purpose=?, type_of_te=? WHERE id=?");
            $stmt->execute([$_POST["provision_number"], $_POST["start_year"], $_POST["end_year"], $_POST["legal_basis"], $_POST["description"], $_POST["purpose"], $_POST["type_of_te"], $_POST["id"]]);
            $message = "Excise provision updated.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$provisions = $pdo->query("SELECT * FROM excise_provisions ORDER BY provision_number ASC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-glass-cheers me-2 text-primary"></i> Excise Tax Repository</h2>
            <p class="text-muted">Archive of excise tax exemptions and reductions by legal provision.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProvModal"><i class="fas fa-plus me-1"></i> Add Provision</button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: .9em;">
                <thead class="bg-light text-uppercase small fw-bold">
                    <tr><th class="ps-4">Prov #</th><th>Effective</th><th>Legal Basis</th><th>Description & Purpose</th><th class="text-center">Type</th><th class="pe-4 text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($provisions as $p): ?>
                    <tr class="align-middle">
                        <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($p["provision_number"]) ?></td>
                        <td><span class="badge bg-light text-dark border"><i class="far fa-calendar-alt me-1 text-primary"></i> <?= $p["start_year"] ?>-<?= $p["end_year"] ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($p["legal_basis"]) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($p["purpose"]) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars(mb_substr($p["description"], 0, 120)) ?>...</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $p["type_of_te"] == "Exemption" ? "success" : "warning text-dark" ?>"><?= htmlspecialchars($p["type_of_te"]) ?></span>
                        </td>
                        <td class="pe-4">
                            <div class="d-flex justify-content-end align-items-center">
                                <button class="btn btn-outline-secondary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editProvModal<?= $p["id"] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                <a href="?delete=<?= $p["id"] ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this provision?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($provisions)): ?><tr><td colspan="6" class="text-center text-muted py-4">No provisions defined yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Provision Modal -->
<div class="modal fade" id="addProvModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content border-0 shadow-lg">
<form method="POST"><input type="hidden" name="action" value="add_provision">
<div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Excise Provision</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<div class="row g-3 mb-3">
<div class="col-md-4"><label class="form-label small fw-bold text-uppercase">Provision #</label><input type="text" name="provision_number" class="form-control" required placeholder="e.g. 6"></div>
<div class="col-md-4"><label class="form-label small fw-bold text-uppercase">Start Year</label><input type="number" name="start_year" class="form-control" value="2020" required></div>
<div class="col-md-4"><label class="form-label small fw-bold text-uppercase">End Year</label><input type="number" name="end_year" class="form-control" value="2099" required></div>
</div>
<div class="row g-3 mb-3">
<div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Legal Basis</label><input type="text" name="legal_basis" class="form-control" required placeholder="e.g. Excise Law Art."></div>
<div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Type of TE</label><select name="type_of_te" class="form-select"><option value="Exemption">Exemption</option><option value="Reduction">Reduction</option></select></div>
</div>
<div class="mb-3"><label class="form-label small fw-bold text-uppercase">Purpose</label><input type="text" name="purpose" class="form-control" required></div>
<div class="mb-0"><label class="form-label small fw-bold text-uppercase">Description</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
</div>
<div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Provision</button></div>
</form></div></div></div>

<!-- Edit Provision Modals -->
<?php foreach ($provisions as $p): ?>
<div class="modal fade" id="editProvModal<?= $p["id"] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content border-0 shadow-lg">
<form method="POST"><input type="hidden" name="action" value="edit_provision"><input type="hidden" name="id" value="<?= $p["id"] ?>">
<div class="modal-header bg-warning"><h5 class="modal-title">Edit Excise Provision</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<div class="row g-3 mb-3">
<div class="col-md-4"><label class="form-label small fw-bold text-uppercase">Provision #</label><input type="text" name="provision_number" class="form-control" value="<?= htmlspecialchars($p["provision_number"]) ?>" required></div>
<div class="col-md-4"><label class="form-label small fw-bold text-uppercase">Start Year</label><input type="number" name="start_year" class="form-control" value="<?= $p["start_year"] ?>" required></div>
<div class="col-md-4"><label class="form-label small fw-bold text-uppercase">End Year</label><input type="number" name="end_year" class="form-control" value="<?= $p["end_year"] ?>" required></div>
</div>
<div class="row g-3 mb-3">
<div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Legal Basis</label><input type="text" name="legal_basis" class="form-control" value="<?= htmlspecialchars($p["legal_basis"]) ?>" required></div>
<div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Type of TE</label><select name="type_of_te" class="form-select"><option value="Exemption" <?= $p["type_of_te"]=="Exemption"?"selected":"" ?>>Exemption</option><option value="Reduction" <?= $p["type_of_te"]=="Reduction"?"selected":"" ?>>Reduction</option></select></div>
</div>
<div class="mb-3"><label class="form-label small fw-bold text-uppercase">Purpose</label><input type="text" name="purpose" class="form-control" value="<?= htmlspecialchars($p["purpose"]) ?>" required></div>
<div class="mb-0"><label class="form-label small fw-bold text-uppercase">Description</label><textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($p["description"]) ?></textarea></div>
</div>
<div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-warning">Update</button></div>
</form></div></div></div>
<?php endforeach; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
