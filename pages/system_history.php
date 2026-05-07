<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

$search = $_GET["search"] ?? "";
$filter_user = $_GET["user_id"] ?? "";
$filter_action = $_GET["action"] ?? "";
$filter_status = $_GET["status"] ?? "";
$date_from = $_GET["date_from"] ?? "";
$date_to = $_GET["date_to"] ?? "";
$sort = $_GET["sort"] ?? "newest";

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (user_name LIKE ? OR details LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_user) {
    $where .= " AND user_id = ?";
    $params[] = $filter_user;
}
if ($filter_action) {
    $where .= " AND action = ?";
    $params[] = $filter_action;
}
if ($filter_status) {
    $where .= " AND details LIKE ?";
    $params[] = $filter_status === "success" ? "successful%" : "failed%";
}
if ($date_from) {
    $where .= " AND created_at >= ?";
    $params[] = $date_from . " 00:00:00";
}
if ($date_to) {
    $where .= " AND created_at <= ?";
    $params[] = $date_to . " 23:59:59";
}

$order = $sort === "oldest" ? "created_at ASC" : "created_at DESC";

$sql = "SELECT * FROM user_history WHERE $where ORDER BY $order LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();

$users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "delete_log") {
        $pdo->prepare("DELETE FROM user_history WHERE id = ?")->execute([$_POST["id"]]);
        header("Location: " . $_SERVER["REQUEST_URI"]);
        exit;
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-history me-2"></i> Operation Logs</h2>
    <p class="text-muted">Track all user activities and system operations.</p>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-2">
        <label class="form-label small">Date From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $date_from ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">Date To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $date_to ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">User</label>
        <select name="user_id" class="form-select form-select-sm">
          <option value="">All Users</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= $u["id"] ?>" <?= $filter_user == $u["id"] ? "selected" : "" ?>><?= htmlspecialchars($u["name"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Action</label>
        <select name="action" class="form-select form-select-sm">
          <option value="">All Actions</option>
          <option value="LOGIN" <?= $filter_action == "LOGIN" ? "selected" : "" ?>>Login</option>
          <option value="LOGOUT" <?= $filter_action == "LOGOUT" ? "selected" : "" ?>>Logout</option>
          <option value="CREATE" <?= $filter_action == "CREATE" ? "selected" : "" ?>>Create</option>
          <option value="UPDATE" <?= $filter_action == "UPDATE" ? "selected" : "" ?>>Update</option>
          <option value="DELETE" <?= $filter_action == "DELETE" ? "selected" : "" ?>>Delete</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All</option>
          <option value="success" <?= $filter_status == "success" ? "selected" : "" ?>>Success</option>
          <option value="failed" <?= $filter_status == "failed" ? "selected" : "" ?>>Failed</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Sort</label>
        <select name="sort" class="form-select form-select-sm">
          <option value="newest" <?= $sort == "newest" ? "selected" : "" ?>>Newest First</option>
          <option value="oldest" <?= $sort == "oldest" ? "selected" : "" ?>>Oldest First</option>
        </select>
      </div>
      <div class="col-md-12">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search details..." value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-outline-primary btn-sm" type="submit">Filter</button>
          <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <span class="text-muted small">Show <?= count($history) ?> records</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Date/Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Details</th>
            <th>IP Address</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($history as $h): ?>
          <tr>
            <td class="ps-4"><?= date("d/m/Y H:i:s", strtotime($h["created_at"])) ?></td>
            <td class="fw-bold"><?= htmlspecialchars($h["user_name"] ?? "-") ?></td>
            <td>
              <?php 
                $badges = [
                  "LOGIN" => "success", "LOGOUT" => "secondary",
                  "CREATE" => "primary", "UPDATE" => "info", "DELETE" => "danger"
                ];
                $badge = $badges[$h["action"]] ?? "light";
              ?>
              <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($h["action"]) ?></span>
            </td>
            <td><?= htmlspecialchars($h["details"] ?? "-") ?></td>
            <td class="text-muted"><?= htmlspecialchars($h["ip_address"] ?? "-") ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailModal"
                onclick='showDetail(<?= htmlspecialchars(json_encode($h)) ?>)'>
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteLog(<?= $h["id"] ?>)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($history)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No logs available</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Log Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-sm">
          <tr><td class="fw-bold">Date/Time</td><td id="detailDate"></td></tr>
          <tr><td class="fw-bold">User</td><td id="detailUser"></td></tr>
          <tr><td class="fw-bold">Action</td><td id="detailAction"></td></tr>
          <tr><td class="fw-bold">Details</td><td id="detailDetails"></td></tr>
          <tr><td class="fw-bold">IP Address</td><td id="detailIP"></td></tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function showDetail(log) {
    document.getElementById("detailDate").innerText = log.created_at;
    document.getElementById("detailUser").innerText = log.user_name || "-";
    document.getElementById("detailAction").innerText = log.action;
    document.getElementById("detailDetails").innerText = log.details || "-";
    document.getElementById("detailIP").innerText = log.ip_address || "-";
}

function deleteLog(id) {
    if (confirm("Delete this log entry?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = '<input type="hidden" name="action" value="delete_log"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>