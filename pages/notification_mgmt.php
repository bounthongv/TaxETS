<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/check_milestones.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "scan_milestones") {
            $count = scanConcessionMilestones();
            $message = "Compliance scan completed. $count new alerts generated.";
        } elseif ($_POST["action"] == "save_notification") {
            $id = $_POST["id"] ?? null;
            $data = [
                $_POST["ref_id"],
                $_POST["source"],
                $_POST["contents"],
                $_POST["notification_date"],
                $_POST["emails"],
                $_POST["phones"],
                $_POST["status"]
            ];

            if ($id) {
                $sql = "UPDATE notifications SET ref_id=?, source=?, contents=?, notification_date=?, emails=?, phones=?, status=? WHERE id=?";
                $data[] = $id;
                $pdo->prepare($sql)->execute($data);
                $message = "Notification updated successfully.";
            } else {
                $sql = "INSERT INTO notifications (ref_id, source, contents, notification_date, emails, phones, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute($data);
                $message = "Notification added successfully.";
            }
        } elseif ($_POST["action"] == "delete_notification") {
            $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$_POST["id"]]);
            $message = "Notification deleted.";
        } elseif ($_POST["action"] == "send_email") {
            // Placeholder for real email logic
            $pdo->prepare("UPDATE notifications SET status='Sent' WHERE id = ?")->execute([$_POST["id"]]);
            $message = "Email sent successfully (Simulated). Status updated.";
        } elseif ($_POST["action"] == "send_sms") {
            // Placeholder for real SMS logic
            $pdo->prepare("UPDATE notifications SET status='Sent' WHERE id = ?")->execute([$_POST["id"]]);
            $message = "SMS sent successfully (Simulated). Status updated.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch Notifications
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY notification_date DESC")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-bell me-2 text-primary"></i> Notification Management</h2>
      <p class="text-muted">Manage system alerts, stakeholder communications, and concession milestone compliance.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="scan_milestones">
            <button type="submit" class="btn btn-outline-warning shadow-sm">
                <i class="fas fa-clock me-1"></i> Scan for Compliance
            </button>
        </form>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#notifModal" onclick="clearNotifForm()">
            <i class="fas fa-plus me-1"></i> Add Notification
        </button>
    </div>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 fw-bold">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 datatable" style="font-size: 0.85rem;">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4" style="width: 80px;">ID</th>
            <th style="width: 120px;">Ref ID</th>
            <th style="width: 120px;">Source</th>
            <th>Contents</th>
            <th style="width: 150px;">Date</th>
            <th style="width: 100px;">Status</th>
            <th class="pe-4 text-end" style="width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notifications as $n): ?>
          <tr>
            <td class="ps-4 text-muted">#<?= $n["id"] ?></td>
            <td class="fw-bold text-dark"><?= htmlspecialchars($n["ref_id"]) ?></td>
            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($n["source"]) ?></span></td>
            <td>
              <div class="text-truncate" style="max-width: 400px;" title="<?= htmlspecialchars($n["contents"]) ?>">
                <?= htmlspecialchars($n["contents"]) ?>
              </div>
            </td>
            <td class="small text-muted"><?= $n["notification_date"] ?></td>
            <td>
              <?php
              $status_class = "bg-warning text-dark";
              if ($n["status"] == "Sent") $status_class = "bg-success";
              if ($n["status"] == "Failed") $status_class = "bg-danger";
              ?>
              <span class="badge <?= $status_class ?>"><?= $n["status"] ?></span>
            </td>
            <td class="pe-4">
              <div class="d-flex justify-content-end align-items-center gap-1">
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="send_email">
                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Send Email"><i class="fas fa-envelope"></i></button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="send_sms">
                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                    <button type="submit" class="btn btn-outline-info btn-sm text-dark" title="Send SMS"><i class="fas fa-sms"></i></button>
                </form>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#notifModal" onclick='editNotif(<?= json_encode($n, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?')">
                  <input type="hidden" name="action" value="delete_notification">
                  <input type="hidden" name="id" value="<?= $n["id"] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Add/Edit Notification -->
<div class="modal fade" id="notifModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <form method="POST">
        <input type="hidden" name="action" value="save_notification">
        <input type="hidden" name="id" id="notif_id">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h5 class="modal-title fw-bold" id="notifModalTitle"><i class="fas fa-plus-circle me-2"></i> Add Notification</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Reference ID</label>
              <input type="text" name="ref_id" id="notif_ref_id" class="form-control" required placeholder="e.g. TIN">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Source</label>
              <input type="text" name="source" id="notif_source" class="form-control" required placeholder="e.g. MOF Lao">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-uppercase">Date/Time</label>
              <input type="datetime-local" name="notification_date" id="notif_date" class="form-control" required>
            </div>
          </div>
          
          <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase">Notification Content</label>
            <textarea name="contents" id="notif_contents" class="form-control" rows="4" required></textarea>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Email Addresses</label>
              <input type="text" name="emails" id="notif_emails" class="form-control" placeholder="comma separated...">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-uppercase">Phone Numbers</label>
              <input type="text" name="phones" id="notif_phones" class="form-control" placeholder="comma separated...">
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold text-uppercase">Status</label>
            <select name="status" id="notif_status" class="form-select">
              <option value="Unsent">Unsent</option>
              <option value="Sent">Sent</option>
              <option value="Failed">Failed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer bg-light border-0 py-3">
          <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Notification</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearNotifForm() {
    document.getElementById('notifModalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i> Add Notification';
    document.getElementById('notif_id').value = '';
    document.getElementById('notif_ref_id').value = '';
    document.getElementById('notif_source').value = '';
    document.getElementById('notif_contents').value = '';
    document.getElementById('notif_date').value = '';
    document.getElementById('notif_emails').value = '';
    document.getElementById('notif_phones').value = '';
    document.getElementById('notif_status').value = 'Unsent';
}

function editNotif(n) {
    document.getElementById('notifModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Notification';
    document.getElementById('notif_id').value = n.id;
    document.getElementById('notif_ref_id').value = n.ref_id;
    document.getElementById('notif_source').value = n.source;
    document.getElementById('notif_contents').value = n.contents;
    // Format date for datetime-local (strip space, replace with T)
    let d = n.notification_date.replace(' ', 'T');
    document.getElementById('notif_date').value = d;
    document.getElementById('notif_emails').value = n.emails;
    document.getElementById('notif_phones').value = n.phones;
    document.getElementById('notif_status').value = n.status;
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
