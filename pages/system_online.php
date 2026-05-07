<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

$search = $_GET["search"] ?? "";

$where = "s.is_online = 1";
$params = [];
if ($search) {
    $where .= " AND u.name LIKE ?";
    $params[] = "%$search%";
}

$sql = "SELECT s.*, u.name, u.email, u.photo 
        FROM user_sessions s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.is_online = 1 
        GROUP BY s.user_id
        ORDER BY s.last_activity DESC";
$sessions = $pdo->query($sql)->fetchAll();

$total_online = count($sessions);
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-users me-2"></i> User Online Management</h2>
    <p class="text-muted">Part of the System section. Monitor currently logged in users.</p>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="display-4 text-success"><?= $total_online ?></div>
        <div class="text-muted">Users Online</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <div class="display-4 text-info"><?= date("H:i") ?></div>
        <div class="text-muted">Current Time</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-body text-center">
        <i class="fas fa-clock text-muted me-2"></i>
        <span class="text-muted">Auto-refresh every 30s</span>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <div class="d-flex justify-content-between align-items-center">
      <span class="text-muted small">Show 1 ~ <?= count($sessions) ?> from <?= $total_online ?> online users</span>
      <form method="GET" class="d-flex">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
      </form>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">User</th>
            <th>Email</th>
            <th>Login Time</th>
            <th>Last Activity</th>
            <th>IP Address</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($sessions as $s): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center">
                <?php if ($s["photo"]): ?>
                <img src="<?= BASE_URL ?>/uploads/users/<?= $s["photo"] ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                <?php else: ?>
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white me-2" style="width: 32px; height: 32px;">
                  <i class="fas fa-user"></i>
                </div>
                <?php endif; ?>
                <strong><?= htmlspecialchars($s["name"]) ?></strong>
              </div>
            </td>
            <td><?= htmlspecialchars($s["email"]) ?></td>
            <td><?= date("d/m/Y H:i:s", strtotime($s["login_at"])) ?></td>
            <td><?= date("d/m/Y H:i:s", strtotime($s["last_activity"])) ?></td>
            <td class="font-monospace text-muted"><?= htmlspecialchars($s["ip_address"] ?? "-") ?></td>
            <td class="text-center">
              <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Online</span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($sessions)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No users currently online</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>