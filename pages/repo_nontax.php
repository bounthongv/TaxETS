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
                $stmt = $pdo->prepare("INSERT INTO natural_resource_provisions (provision_code, provision_name, category, exemption_years, reduction_percentage, period_time, description, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["provision_code"],
                    $_POST["provision_name"],
                    $_POST["category"],
                    $_POST["exemption_years"],
                    $_POST["reduction_percentage"] ?: 0,
                    $_POST["period_time"] ?: 0,
                    $_POST["description"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "Natural Resource provision added.";
            } elseif ($_POST["action"] === "edit_prov") {
                $stmt = $pdo->prepare("UPDATE natural_resource_provisions SET provision_code = ?, provision_name = ?, category = ?, exemption_years = ?, reduction_percentage = ?, period_time = ?, description = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST["provision_code"],
                    $_POST["provision_name"],
                    $_POST["category"],
                    $_POST["exemption_years"],
                    $_POST["reduction_percentage"] ?: 0,
                    $_POST["period_time"] ?: 0,
                    $_POST["description"] ?: null,
                    isset($_POST["active"]) ? 1 : 0,
                    $_POST["id"]
                ]);
                $message = "Provision updated.";
            } elseif ($_POST["action"] === "delete_prov") {
                $pdo->prepare("DELETE FROM natural_resource_provisions WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Provision deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$provisions = $pdo->query("SELECT * FROM natural_resource_provisions ORDER BY category, provision_code")->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM natural_resource_provisions ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-tree me-2 text-success"></i> Natural Resource Repository</h2>
            <p class="text-muted">Manage provisions for natural resource extraction fees and related exemptions.</p>
        </div>
        <button class="btn btn-primary shadow-sm" onclick="clearForm()" data-bs-toggle="modal" data-bs-target="#provModal">
            <i class="fas fa-plus me-2"></i> Add Provision
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-start border-4 border-<?= $msg_type ?>"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-2 mb-3">
    <?php foreach ($categories as $cat): ?>
    <?php $cnt = count(array_filter($provisions, fn($p) => $p['category'] === $cat)); ?>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-5 fw-bold"><?= $cnt ?></div>
            <div class="small text-muted"><?= htmlspecialchars($cat) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-2">
            <div class="fs-5 fw-bold"><?= count($provisions) ?></div>
            <div class="small opacity-75">Total</div>
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
                    <th>Category</th>
                    <th class="text-center">Exemption Years</th>
                    <th class="text-center">Reduction</th>
                    <th class="text-center">Period (months)</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($provisions)): ?>
                <?php foreach ($provisions as $p): ?>
                <tr>
                    <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($p["provision_code"]) ?></span></td>
                    <td class="fw-bold"><?= htmlspecialchars($p["provision_name"]) ?></td>
                    <td>
                        <?php
                            $colors = ["Resource Fee" => "success", "Royalty Fee" => "warning", "Other" => "info"];
                            $c = $colors[$p["category"]] ?? "secondary";
                        ?>
                        <span class="badge bg-<?= $c ?>"><?= htmlspecialchars($p["category"]) ?></span>
                    </td>
                    <td class="text-center"><?= $p["exemption_years"] ?: "-" ?></td>
                    <td class="text-center"><?= $p["reduction_percentage"] ? number_format($p["reduction_percentage"], 2) . "%" : "-" ?></td>
                    <td class="text-center"><?= $p["period_time"] ?: "-" ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $p["active"] ? "success" : "secondary" ?>">
                            <?= $p["active"] ? "Active" : "Inactive" ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick='editProv(<?= json_encode($p) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this provision?')">
                            <input type="hidden" name="action" value="delete_prov">
                            <input type="hidden" name="id" value="<?= $p["id"] ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No provisions found. Click "Add Provision" to create one.</td></tr>
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
                    <h5 class="modal-title"><i class="fas fa-tree me-2 text-success"></i> <span id="modalTitle">Add Provision</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_prov">
                    <input type="hidden" name="id" id="provId">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Provision Code</label>
                            <input type="text" name="provision_code" id="provCode" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" id="provCategory" class="form-select" required>
                                <option value="Resource Fee">Resource Fee</option>
                                <option value="Royalty Fee">Royalty Fee</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
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
    document.getElementById("provCategory").value = "Resource Fee";
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
    document.getElementById("provCategory").value = data.category;
    document.getElementById("provYears").value = data.exemption_years;
    document.getElementById("provReduction").value = data.reduction_percentage;
    document.getElementById("provPeriod").value = data.period_time;
    document.getElementById("provDesc").value = data.description || "";
    document.getElementById("provActive").checked = data.active == 1;
    new bootstrap.Modal(document.getElementById("provModal")).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
