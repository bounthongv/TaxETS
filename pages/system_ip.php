<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_ip") {
                $stmt = $pdo->prepare("INSERT INTO ip_access (ip_address, action, description, start_date, end_date, active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST["ip_address"],
                    $_POST["action"],
                    $_POST["description"],
                    $_POST["start_date"] ?: null,
                    $_POST["end_date"] ?: null,
                    isset($_POST["active"]) ? 1 : 0
                ]);
                $message = "IP rule added.";
            } elseif ($_POST["action"] === "delete_ip") {
                $pdo->prepare("DELETE FROM ip_access WHERE id = ?")->execute([$_POST["id"]]);
                $message = "IP rule deleted.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$ips = $pdo->query("SELECT * FROM ip_access ORDER BY id DESC")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-shield-alt me-2"></i> IP Access Management</h2>
      <p class="text-muted">Part of the System section. Control access by IP address whitelisting or blacklisting.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#ipModal">
      <i class="fas fa-plus me-1"></i> Add IP Rule
    </button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <div class="d-flex justify-content-between align-items-center">
      <span class="text-muted small">Total <?= count($ips) ?> rules</span>
      <div class="alert alert-warning mb-0 py-1 px-2 small">
        <i class="fas fa-info-circle me-1"></i> ALLOW rules are checked first. If no ALLOW rule matches, access is denied.
      </div>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">IP Address</th>
            <th>Action</th>
            <th>Description</th>
            <th>Valid Period</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($ips as $ip): ?>
          <tr>
            <td class="ps-4 fw-bold font-monospace"><?= htmlspecialchars($ip["ip_address"]) ?></td>
            <td>
              <?php if ($ip["action"] === "ALLOW"): ?>
              <span class="badge bg-success">ALLOW</span>
              <?php else: ?>
              <span class="badge bg-danger">DENY</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($ip["description"] ?? "-") ?></td>
            <td>
              <?php if ($ip["start_date"] || $ip["end_date"]): ?>
              <small class="text-muted">
                <?= $ip["start_date"] ? date("d/m/Y", strtotime($ip["start_date"])) : "No start" ?>
                -
                <?= $ip["end_date"] ? date("d/m/Y", strtotime($ip["end_date"])) : "No end" ?>
              </small>
              <?php else: ?>
              <span class="text-muted">Always</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($ip["active"]): ?>
              <span class="badge bg-success">Active</span>
              <?php else: ?>
              <span class="badge bg-secondary">Disabled</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-danger" onclick="deleteIp(<?= $ip["id"] ?>)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($ips)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No IP rules configured. All access is allowed by default.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add IP Modal -->
<div class="modal fade" id="ipModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Add IP Rule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_ip">
          <div class="mb-3">
            <label class="form-label">IP Address *</label>
            <input type="text" name="ip_address" class="form-control" placeholder="e.g., 192.168.1.100 or 10.0.0.0/24" required>
            <small class="text-muted">Use CIDR notation for ranges (e.g., 10.0.0.0/24)</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Action *</label>
            <select name="action" class="form-select">
              <option value="ALLOW">ALLOW - Whitelist this IP</option>
              <option value="DENY">DENY - Blacklist this IP</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Optional description">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control">
            </div>
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
function deleteIp(id) {
    if (confirm("Delete this IP rule?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = '<input type="hidden" name="action" value="delete_ip"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>