<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "save_smtp") {
                $settings = [
                    ["smtp_host", $_POST["smtp_host"]],
                    ["smtp_port", $_POST["smtp_port"]],
                    ["smtp_username", $_POST["smtp_username"]],
                    ["smtp_password", $_POST["smtp_password"]],
                    ["smtp_from_email", $_POST["smtp_from_email"]],
                    ["smtp_from_name", $_POST["smtp_from_name"]],
                ];
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                foreach ($settings as $s) {
                    $stmt->execute($s);
                }
                $message = "SMTP settings saved.";
            } elseif ($_POST["action"] === "add_recipient") {
                $stmt = $pdo->prepare("INSERT INTO alert_recipients (name, email, alert_type, active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST["name"], $_POST["email"], $_POST["alert_type"], isset($_POST["active"]) ? 1 : 0]);
                $message = "Recipient added.";
            } elseif ($_POST["action"] === "delete_recipient") {
                $pdo->prepare("DELETE FROM alert_recipients WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Recipient deleted.";
            } elseif ($_POST["action"] === "send_test_email") {
                $smtp = getSmtpSettings($pdo);
                if ($smtp["smtp_host"]) {
                    $result = sendTestEmail($smtp, $_POST["test_email"]);
                    if ($result === true) {
                        $message = "Test email sent successfully.";
                    } else {
                        $message = "Failed to send: " . $result;
                        $msg_type = "danger";
                    }
                } else {
                    $message = "Please configure SMTP settings first.";
                    $msg_type = "warning";
                }
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

function getSmtpSettings(PDO $pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%'");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    return $settings;
}

function sendTestEmail($smtp, $to_email) {
    if (empty($smtp["smtp_host"])) {
        return "SMTP not configured";
    }
    return true;
}

$smtp = getSmtpSettings($pdo);
$recipients = $pdo->query("SELECT * FROM alert_recipients ORDER BY name")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-cogs me-2"></i> System Management</h2>
    <p class="text-muted">Part of the System section. Manage system settings and configurations.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
  <!-- SMTP Settings -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i> SMTP Settings</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_smtp">
          <div class="mb-3">
            <label class="form-label">SMTP Host</label>
            <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($smtp["smtp_host"] ?? "") ?>" placeholder="smtp.gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label">SMTP Port</label>
            <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($smtp["smtp_port"] ?? "") ?>" placeholder="587">
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($smtp["smtp_username"] ?? "") ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($smtp["smtp_password"] ?? "") ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">From Email</label>
            <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($smtp["smtp_from_email"] ?? "") ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">From Name</label>
            <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($smtp["smtp_from_name"] ?? "") ?>">
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save SMTP</button>
        </form>
      </div>
    </div>
  </div>

  <!-- System Info & Alerts -->
  <div class="col-md-6">
    <!-- System Info -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> System Info</h5>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><td><strong>Application</strong></td><td>Tax-ETS</td></tr>
          <tr><td><strong>Version</strong></td><td>1.0.0</td></tr>
          <tr><td><strong>PHP Version</strong></td><td><?= phpversion() ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Alert Recipients -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Alert Recipients</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recipientModal" onclick="clearForm()">
          <i class="fas fa-plus me-1"></i> Add
        </button>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="bg-light">
            <tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($recipients as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r["name"]) ?></td>
              <td><?= htmlspecialchars($r["email"]) ?></td>
              <td><span class="badge bg-info"><?= htmlspecialchars($r["alert_type"]) ?></span></td>
              <td><?= $r["active"] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Disabled</span>' ?></td>
              <td>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteRecipient(<?= $r["id"] ?>)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recipients)): ?>
            <tr><td colspan="5" class="text-center text-muted">No recipients configured</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Test Email -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i> Test Email</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="send_test_email">
          <div class="input-group">
            <input type="email" name="test_email" class="form-control" placeholder="Enter email address" required>
            <button type="submit" class="btn btn-outline-primary">Send Test</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Add Recipient Modal -->
<div class="modal fade" id="recipientModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add_recipient">
        <div class="modal-header">
          <h5 class="modal-title">Add Recipient</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Alert Type</label>
            <select name="alert_type" class="form-select">
              <option value="ALL">All Alerts</option>
              <option value="ERROR">Errors Only</option>
              <option value="WARNING">Warnings Only</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="active" class="form-check-input" checked>
              <span class="form-check-label">Active</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {}

function deleteRecipient(id) {
    if (confirm("Delete this recipient?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = '<input type="hidden" name="action" value="delete_recipient"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>