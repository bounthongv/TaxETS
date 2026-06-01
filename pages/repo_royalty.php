<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_prov") {
                $stmt = $pdo->prepare("INSERT INTO natural_resource_provisions (provision_code, provision_name, category, exemption_years, reduction_percentage, period_time, description, active) VALUES (?, ?, 'Royalty Fee', ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["provision_code"],
                    $_POST["provision_name"],
                    $_POST["exemption_years"],
                    $_POST["reduction_percentage"] ?: 0,
                    $_POST["period_time"] ?: 0,
                    $_POST["description"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Royalty Fee provision added.";
            } elseif ($_POST["action"] === "edit_prov") {
                $stmt = $pdo->prepare("UPDATE natural_resource_provisions SET provision_code = ?, provision_name = ?, exemption_years = ?, reduction_percentage = ?, period_time = ?, description = ?, active = ? WHERE id = ? AND category = 'Royalty Fee'");
                $stmt->execute([
                    $_POST["provision_code"],
                    $_POST["provision_name"],
                    $_POST["exemption_years"],
                    $_POST["reduction_percentage"] ?: 0,
                    $_POST["period_time"] ?: 0,
                    $_POST["description"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Provision updated.";
            } elseif ($_POST["action"] === "delete_prov") {
                $pdo->prepare("DELETE FROM natural_resource_provisions WHERE id = ? AND category = 'Royalty Fee'")->execute([$_POST["id"]]);
                $message = "Provision deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$provisions = $pdo->query("SELECT * FROM natural_resource_provisions WHERE category = 'Royalty Fee' ORDER BY provision_code")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-gem me-2 text-warning"></i> Royalty Fee Repository</h2>
            <p class="text-muted">Manage provisions for royalty fee exemptions and reductions.</p>
        </div>
        <button class="btn btn-primary shadow-sm" onclick="clearForm()" data-bs-toggle="modal" data-bs-target="#provModal">
            <i class="fas fa-plus me-2"></i> Add Provision
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-start border-4 border-<?= $msg_type ?>"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning text-white text-center py-2">
            <div class="fs-5 fw-bold"><?= count($provisions) ?></div>
            <div class="small opacity-75">Royalty Fee Provisions</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th>Code</th>
                    <th>Provision Name</th>
                    <th class="text-center">Exemption Years</th>
                    <th class="text-center">Reduction</th>
                    <th class="text-center">Period (months)</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($provisions as $p): ?>
                <tr>
                    <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($p["provision_code"]) ?></span></td>
                    <td class="fw-bold"><?= htmlspecialchars($p["provision_name"]) ?></td>
                    <td class="text-center"><?= $p["exemption_years"] ?: "-" ?></td>
                    <td class="text-center"><?= $p["reduction_percentage"] ? number_format($p["reduction_percentage"], 2) . "%" : "-" ?></td>
                    <td class="text-center"><?= $p["period_time"] ?: "-" ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $p["active"] ? "success" : "secondary" ?>">
                            <?= $p["active"] ? "Active" : "Inactive" ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick='editProv(<?= json_encode($p) ?>)'><i class="fas fa-edit"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this provision?')">
                            <input type="hidden" name="action" value="delete_prov">
                            <input type="hidden" name="id" value="<?= $p["id"] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($provisions)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No royalty fee provisions found. Click "Add Provision" to create one.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="provModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-gem me-2 text-warning"></i> <span id="modalTitle">Add Provision</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_prov">
                    <input type="hidden" name="id" id="provId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Provision Code</label>
                            <input type="text" name="provision_code" id="provCode" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="active" id="provActive" class="form-check-input" checked>
                                <label class="form-check-label" for="provActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Provision Name</label>
                        <input type="text" name="provision_name" id="provName" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Exemption Years</label>
                            <input type="number" name="exemption_years" id="provYears" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Reduction (%)</label>
                            <input type="number" step="0.01" name="reduction_percentage" id="provReduction" class="form-control" value="0" min="0" max="100">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Period (months)</label>
                            <input type="number" name="period_time" id="provPeriod" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" id="provDesc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById("formAction").value = "add_prov";
    document.getElementById("modalTitle").textContent = "Add Provision";
    document.getElementById("provId").value = "";
    document.getElementById("provCode").value = "";
    document.getElementById("provName").value = "";
    document.getElementById("provYears").value = 0;
    document.getElementById("provReduction").value = 0;
    document.getElementById("provPeriod").value = 0;
    document.getElementById("provDesc").value = "";
    document.getElementById("provActive").checked = true;
}

function editProv(data) {
    document.getElementById("formAction").value = "edit_prov";
    document.getElementById("modalTitle").textContent = "Edit Provision";
    document.getElementById("provId").value = data.id;
    document.getElementById("provCode").value = data.provision_code;
    document.getElementById("provName").value = data.provision_name;
    document.getElementById("provYears").value = data.exemption_years;
    document.getElementById("provReduction").value = data.reduction_percentage;
    document.getElementById("provPeriod").value = data.period_time;
    document.getElementById("provDesc").value = data.description || "";
    document.getElementById("provActive").checked = data.active == 1;
    new bootstrap.Modal(document.getElementById("provModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
