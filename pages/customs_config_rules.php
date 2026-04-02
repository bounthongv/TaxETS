<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$provision_id = isset($_GET["provision_id"]) ? (int)$_GET["provision_id"] : 0;

if ($provision_id <= 0) {
    die("Invalid provision ID.");
}

$provision = $pdo->prepare("SELECT * FROM customs_provisions WHERE id = ?");
$provision->execute([$provision_id]);
$provision = $provision->fetch(PDO::FETCH_ASSOC);

if (!$provision) {
    die("Provision not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if ($_POST["action"] === "add_condition") {
            $stmt = $pdo->prepare("INSERT INTO customs_provision_conditions (provision_id, field_name, operator, value_1, value_2) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $provision_id,
                $_POST["field_name"],
                $_POST["operator"],
                $_POST["value_1"] ?? null,
                $_POST["value_2"] ?? null
            ]);
            $message = "Condition added successfully.";
        } elseif ($_POST["action"] === "delete_condition") {
            $stmt = $pdo->prepare("DELETE FROM customs_provision_conditions WHERE id = ? AND provision_id = ?");
            $stmt->execute([$_POST["condition_id"], $provision_id]);
            $message = "Condition deleted.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$conditions = $pdo->prepare("SELECT * FROM customs_provision_conditions WHERE provision_id = ? ORDER BY id ASC");
$conditions->execute([$provision_id]);
$conditions = $conditions->fetchAll(PDO::FETCH_ASSOC);

$fieldOptions = [
    "provision_number" => "Provision Number",
    "ahtn_chapter" => "AHTN Chapter",
    "origin_country" => "Country of Origin",
    "importer_type" => "Importer Type",
    "cif_value" => "CIF Value",
    "duty_paid" => "Duty Paid",
    "hs_code" => "HS Code",
];

$operatorOptions = ["=", ">", "<", ">=", "<=", "LIKE", "BETWEEN"];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="repo_customs.php">Customs Repository</a></li>
                <li class="breadcrumb-item active">Rule Configuration</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-primary text-white" style="border-radius: 12px 12px 0 0;">
                <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i> Category <?= htmlspecialchars($provision["provision_number"]) ?> - <?= htmlspecialchars($provision["purpose"]) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 120px;">Legal Basis:</td>
                                <td class="fw-bold"><?= htmlspecialchars($provision["legal_basis"]) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Type of TE:</td>
                                <td><span class="badge bg-<?= $provision["type_of_te"] == "Exemption" ? "success" : "warning text-dark" ?>"><?= htmlspecialchars($provision["type_of_te"]) ?></span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Effective:</td>
                                <td><?= $provision["start_year"] ?> - <?= $provision["end_year"] ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small text-uppercase">Description</p>
                        <p class="mb-0 small"><?= htmlspecialchars($provision["description"]) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-plus-circle me-2 text-success"></i> Add New Condition</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_condition">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Field Name</label>
                        <select name="field_name" class="form-select" required>
                            <option value="">-- Select Field --</option>
                            <?php foreach ($fieldOptions as $val => $label): ?>
                            <option value="<?= $val ?>"><?= $label ?> (<?= $val ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Operator</label>
                        <select name="operator" class="form-select" required id="operatorSelect">
                            <option value="">-- Select Operator --</option>
                            <?php foreach ($operatorOptions as $op): ?>
                            <option value="<?= $op ?>"><?= $op ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Value 1</label>
                        <input type="text" name="value_1" class="form-control" placeholder="Enter value" required>
                    </div>

                    <div class="mb-3" id="value2Group" style="display: none;">
                        <label class="form-label fw-bold">Value 2 (for BETWEEN)</label>
                        <input type="text" name="value_2" class="form-control" placeholder="Enter second value">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i> Add Condition</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list-ul me-2 text-primary"></i> Active Conditions (<?= count($conditions) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($conditions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">No conditions configured yet.<br>Add conditions above to enable auto-classification.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-4">Field</th>
                                <th>Operator</th>
                                <th>Value(s)</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conditions as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <code><?= htmlspecialchars($c["field_name"]) ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($c["operator"]) ?></span>
                                </td>
                                <td>
                                    <?php if ($c["operator"] === "BETWEEN" && $c["value_2"]): ?>
                                        <span class="text-primary"><?= htmlspecialchars($c["value_1"]) ?></span>
                                        <span class="text-muted">AND</span>
                                        <span class="text-primary"><?= htmlspecialchars($c["value_2"]) ?></span>
                                    <?php else: ?>
                                        <span class="text-primary"><?= htmlspecialchars($c["value_1"]) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this condition?')">
                                        <input type="hidden" name="action" value="delete_condition">
                                        <input type="hidden" name="condition_id" value="<?= $c["id"] ?>">
                                        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 bg-light" style="border-radius: 12px;">
            <div class="card-body">
                <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i> How Conditions Work</h6>
                <p class="small text-muted mb-2">
                    When the customs TE calculation engine runs, it evaluates each imported record against these conditions.
                    If <strong>ALL conditions match</strong>, the provision is automatically assigned to that record.
                </p>
                <p class="small text-muted mb-0">
                    <strong>Example:</strong> To classify imports from ASEAN countries under ATIGA, add:
                    <code>origin_country = ASEAN</code>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("operatorSelect").addEventListener("change", function() {
    var value2Group = document.getElementById("value2Group");
    value2Group.style.display = this.value === "BETWEEN" ? "block" : "none";
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
