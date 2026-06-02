<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = ""; $msg_type = "success";

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] == "save_milestone") {
            $id = $_POST["id"] ?? null;
            $data = [
                $_POST["tin"], $_POST["company_name"], $_POST["project_name"], $_POST["milestone_type"],
                $_POST["start_date"], $_POST["end_date"], $_POST["remind_days"], $_POST["responsible_person"],
                $_POST["contact_email"], $_POST["status"]
            ];
            if ($id) {
                $sql = "UPDATE concession_milestones SET tin=?, company_name=?, project_name=?, milestone_type=?, start_date=?, end_date=?, remind_days=?, responsible_person=?, contact_email=?, status=? WHERE id=?";
                $data[] = $id;
                $pdo->prepare($sql)->execute($data);
                $message = "Milestone updated.";
            } else {
                $sql = "INSERT INTO concession_milestones (tin, company_name, project_name, milestone_type, start_date, end_date, remind_days, responsible_person, contact_email, status) VALUES (?,?,?,?,?,?,?,?,?,?)";
                $pdo->prepare($sql)->execute($data);
                $message = "Milestone added.";
            }
        } elseif ($_POST["action"] == "delete") {
            $pdo->prepare("DELETE FROM concession_milestones WHERE id=?")->execute([$_POST["id"]]);
            $message = "Milestone deleted.";
        }
    } catch (Exception $e) { $message = $e->getMessage(); $msg_type = "danger"; }
}

$milestones = $pdo->query("SELECT * FROM concession_milestones ORDER BY end_date ASC")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-flag me-2 text-primary"></i> Concession Milestones</h2>
      <p class="text-muted">Monitor implementation periods (MOU, Survey, etc.) and compliance deadlines.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#mModal" onclick="clearMForm()">
        <i class="fas fa-plus me-1"></i> Add Milestone
    </button>
  </div>
</div>

<?php if ($message): ?><div class="alert alert-<?= $msg_type ?>"><?= $message ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 datatable">
        <thead class="bg-light text-uppercase small fw-bold">
          <tr>
            <th class="ps-4">Project / Company</th>
            <th>Type</th>
            <th>Deadline</th>
            <th>Responsible</th>
            <th>Status</th>
            <th class="pe-4 text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach ($milestones as $m): 
            $days_left = ceil((strtotime($m['end_date']) - time()) / 86400);
            $warning = ($days_left <= $m['remind_days'] && $m['status'] == 'Active');
          ?>
          <tr class="<?= ($days_left < 0 && $m['status'] == 'Active') ? 'table-danger' : ($warning ? 'table-warning' : '') ?>">
            <td class="ps-4">
                <div class="fw-bold text-dark"><?= htmlspecialchars($m['project_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($m['company_name']) ?> (<?= $m['tin'] ?>)</small>
            </td>
            <td><span class="badge bg-info-subtle text-info border border-info"><?= $m['milestone_type'] ?></span></td>
            <td>
                <div class="fw-bold"><?= $m['end_date'] ?></div>
                <small class="<?= $days_left < 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                    <?= $days_left < 0 ? abs($days_left) . " days overdue" : "$days_left days left" ?>
                </small>
            </td>
            <td>
                <div><?= htmlspecialchars($m['responsible_person']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($m['contact_email']) ?></small>
            </td>
            <td>
                <?php
                $s_map = ['Active'=>'bg-primary', 'Completed'=>'bg-success', 'Extended'=>'bg-info', 'Terminated'=>'bg-secondary'];
                ?>
                <span class="badge <?= $s_map[$m['status']] ?>"><?= $m['status'] ?></span>
            </td>
            <td class="pe-4">
              <div class="d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#mModal" onclick='editM(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fas fa-edit"></i></button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this milestone?')">
                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
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

<!-- Modal -->
<div class="modal fade" id="mModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="save_milestone"><input type="hidden" name="id" id="m_id">
        <div class="modal-header"><h5 class="modal-title" id="mTitle">Add Milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold">TIN</label>
                <div class="input-group">
                    <input type="text" name="tin" id="m_tin" class="form-control" required placeholder="Type TIN...">
                    <button type="button" class="btn btn-info text-white" onclick="fetchCompanyInfo()" title="Auto-fill from MOIC/MPI"><i class="fas fa-sync-alt"></i> Fetch</button>
                </div>
                <div id="fetch_status" class="small mt-1"></div>
            </div>
            <div class="col-md-7"><label class="form-label fw-bold">Company Name</label><input type="text" name="company_name" id="m_company" class="form-control"></div>
            <div class="col-12"><label class="form-label fw-bold">Project Name</label><input type="text" name="project_name" id="m_project" class="form-control" required></div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Milestone Type</label>
                <select name="milestone_type" id="m_type" class="form-select">
                    <option value="MOU">MOU</option><option value="Prospecting">Prospecting</option><option value="Survey">Survey</option>
                    <option value="Feasibility">Feasibility</option><option value="Construction">Construction</option><option value="Operation">Operation</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <select name="status" id="m_status" class="form-select">
                    <option value="Active">Active</option><option value="Completed">Completed</option><option value="Extended">Extended</option><option value="Terminated">Terminated</option>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label fw-bold">Start Date</label><input type="date" name="start_date" id="m_start" class="form-control"></div>
            <div class="col-md-4"><label class="form-label fw-bold text-danger">End Date (Deadline)</label><input type="date" name="end_date" id="m_end" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Notify Before (Days)</label><input type="number" name="remind_days" id="m_remind" class="form-control" value="30"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Responsible Person</label><input type="text" name="responsible_person" id="m_person" class="form-control"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Contact Email</label><input type="email" name="contact_email" id="m_email" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Milestone</button></div>
      </form>
    </div>
  </div>
</div>

<script>
async function fetchCompanyInfo() {
    const tin = document.getElementById('m_tin').value.trim();
    const status = document.getElementById('fetch_status');
    if (!tin) { alert('Please enter a TIN first'); return; }
    
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';
    try {
        const response = await fetch(`api_get_company_info.php?tin=${encodeURIComponent(tin)}`);
        const data = await response.json();
        if (data.success) {
            document.getElementById('m_company').value = data.company_name;
            document.getElementById('m_project').value = data.project_name || '';
            status.innerHTML = `<span class="text-success"><i class="fas fa-check"></i> Data found from ${data.source}!</span>`;
        } else {
            status.innerHTML = `<span class="text-danger"><i class="fas fa-times"></i> ${data.message}</span>`;
        }
    } catch (e) {
        status.innerHTML = '<span class="text-danger">Error connecting to server.</span>';
    }
}

function clearMForm() {
    document.getElementById('mTitle').innerText = 'Add Milestone';
    document.getElementById('m_id').value = '';
    document.getElementById('m_remind').value = '30';
    document.getElementById('m_status').value = 'Active';
}
function editM(d) {
    document.getElementById('mTitle').innerText = 'Edit Milestone';
    document.getElementById('m_id').value = d.id;
    document.getElementById('m_tin').value = d.tin;
    document.getElementById('m_company').value = d.company_name;
    document.getElementById('m_project').value = d.project_name;
    document.getElementById('m_type').value = d.milestone_type;
    document.getElementById('m_status').value = d.status;
    document.getElementById('m_start').value = d.start_date;
    document.getElementById('m_end').value = d.end_date;
    document.getElementById('m_remind').value = d.remind_days;
    document.getElementById('m_person').value = d.responsible_person;
    document.getElementById('m_email').value = d.contact_email;
    new bootstrap.Modal(document.getElementById('mModal')).show();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
