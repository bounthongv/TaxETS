<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$modules = [
    // === System Administration ===
    "system_users"       => "System: User Management",
    "system_roles"       => "System: Role Management",
    "system_history"     => "System: Operation Logs",
    "system_logs"        => "System: Error Logs",
    "system_ip"          => "System: IP Access Control",
    "system_online"      => "System: Online Users",
    "system_backup"      => "System: Backup & Restore",
    // === Configuration / Benchmark Rates ===
    "config_rates"       => "Config: CIT Benchmark Rates",
    "config_provisions"  => "Config: CIT Provisions",
    "config_rules"       => "Config: CIT Manual Rules",
    "config_sez_provisions" => "Config: SEZ Provisions",
    "benchmark_msme"     => "Config: MSME Benchmark",
    "benchmark_individual" => "Config: PIT / Individual Benchmark",
    "benchmark_vat"      => "Config: VAT Benchmark",
    "benchmark_customs"  => "Config: Customs Benchmark",
    "benchmark_customs_regime" => "Config: Customs Regime",
    "benchmark_excise"   => "Config: Excise Benchmark",
    "benchmark_art9"     => "Config: Article 9 Activities",
    "benchmark_land_concession" => "Config: Land Concession",
    "benchmark_nontax"   => "Config: Non-Tax Revenue",
    "benchmark_payment_condition" => "Config: Payment Condition",
    "vat_config_rules"   => "Config: VAT Rules",
    "customs_config_rules" => "Config: Customs Rules",
    // === Dictionaries ===
    "dictionary_province"  => "Dictionary: Province",
    "dictionary_district"  => "Dictionary: District",
    "dictionary_village"   => "Dictionary: Village",
    "dictionary_zone"      => "Dictionary: Investment Zone",
    "dictionary_sector"    => "Dictionary: Sector",
    "dictionary_enterprise_type" => "Dictionary: Enterprise Type",
    "dictionary_moic_categories" => "Dictionary: MOIC Categories",
    "dictionary_status"    => "Dictionary: Status",
    // === Import ===
    "import_cit"         => "Import: Corporate Income Tax",
    "import_individual"  => "Import: Individual / PIT",
    "import_vat"         => "Import: VAT",
    "import_asycuda"     => "Import: ASYCUDA (Customs/Excise)",
    "import_sez_dev"     => "Import: SEZ Developer",
    "import_sez_inv"     => "Import: SEZ Investor",
    "import_salary"      => "Import: Salary Tax",
    "import_lse"         => "Import: LSE Data",
    "import_land"        => "Import: Land Tax",
    "import_land_concession" => "Import: Land Concession",
    "import_resource"    => "Import: Natural Resource",
    "import_royalty"     => "Import: Royalty",
    "import_moic"        => "Import: MOIC (Enterprise)",
    "import_mpi"         => "Import: MPI (Investment)",
    "import_molsw"       => "Import: MOLSW",
    "import_taxris"      => "Import: TaxRIS",
    "import_gdp"         => "Import: GDP Data",
    "import_districts"   => "Import: Districts (CSV)",
    "import_tariff"      => "Import: Tariff",
    "import_new_data"    => "Import: Legacy Migration",
    // === Data Entry ===
    "view_companies"     => "Data: View Companies (CIT)",
    "view_individual"    => "Data: View Individual Tax",
    "view_vat"           => "Data: View VAT",
    "view_salary"        => "Data: View Salary Tax",
    "view_resource"      => "Data: View Resource",
    "view_royalty"       => "Data: View Royalty",
    "view_sez_dev"       => "Data: View SEZ Developer",
    "view_sez_inv"       => "Data: View SEZ Investor",
    "asycuda_customs"    => "Data: ASYCUDA Customs",
    "asycuda_excise"     => "Data: ASYCUDA Excise",
    "asycuda_vat"        => "Data: ASYCUDA VAT",
    // === Calculation ===
    "calculator"         => "Calc: TE Calculator",
    "recalculate_all"    => "Calc: Recalculate All",
    "calculate_land_concession" => "Calc: Land Concession Calc",
    // === TE Engine Views ===
    "te_asycuda_customs" => "Calc: Customs TE",
    "te_asycuda_excise"  => "Calc: Excise TE",
    "te_asycuda_vat"     => "Calc: ASYCUDA VAT TE",
    "te_customs"         => "Calc: Customs TE (detailed)",
    "te_excise"          => "Calc: Excise TE (detailed)",
    "te_individual"      => "Calc: Individual TE",
    "te_nontax"          => "Calc: Non-Tax TE",
    "te_resource"        => "Calc: Resource TE",
    "te_royalty"         => "Calc: Royalty TE",
    "te_salary_tax"      => "Calc: Salary Tax TE",
    "te_sez_dev"         => "Calc: SEZ Developer TE",
    "te_sez_inv"         => "Calc: SEZ Investor TE",
    "te_vat"             => "Calc: VAT TE",
    // === Reports ===
    "report_tax_type"    => "Report: Tax Type Summary",
    "report_provisions"  => "Report: Provisions Summary",
    "report_summary"     => "Report: Overall Summary",
    "report_sector"      => "Report: By Sector",
    "report_location"    => "Report: By Location",
    "report_revenue"     => "Report: Revenue Impact",
    "report_customs_duty" => "Report: Customs Duty",
    "report_customs_provision" => "Report: Customs Provisions",
    "report_excise_tax"  => "Report: Excise Tax",
    "report_excise_provision"  => "Report: Excise Provisions",
    "report_import_vat"  => "Report: Import VAT",
    "report_vat_provision" => "Report: VAT Provisions",
    "report_individual_provision" => "Report: Individual/PIT Provisions",
    "report_salary_tax_provision" => "Report: Salary Tax Provisions",
    "report_nontax_provision"     => "Report: Non-Tax Provisions",
    "report_sez_dev_provision"    => "Report: SEZ Developer Provisions",
    "report_sez_inv_provision"    => "Report: SEZ Investor Provisions",
    "report_total_customs" => "Report: Total Customs",
    "report_total_provision" => "Report: Total Provisions",
    "report_gdp"         => "Report: GDP Impact",
    "repo_individual"    => "Repo: Individual Provisions",
    "repo_vat"           => "Repo: VAT Provisions",
    "repo_customs"       => "Repo: Customs Data",
    "repo_excise"        => "Repo: Excise Data",
    "repo_land_concession" => "Repo: Land Concession",
    "repo_lse"           => "Repo: LSE Data",
    "repo_moic"          => "Repo: MOIC Data",
    "repo_molsw"         => "Repo: MOLSW Data",
    "repo_mpi"           => "Repo: MPI Data",
    "repo_natural_resource" => "Repo: Natural Resource",
    "repo_nontax"        => "Repo: Non-Tax Revenue",
    "repo_royalty"       => "Repo: Royalty Data",
    "repo_sezo"          => "Repo: SEZ Data",
    "repo_taxris"        => "Repo: TaxRIS Data",
    "repo_gdp"           => "Repo: GDP Data",
    "repo_milestones"    => "Repo: Milestones",
    "repo_vat"           => "Repo: VAT Data",
    // === Utilities ===
    "batches"            => "Utility: Batch Manager",
    "delete_batch"       => "Utility: Delete Batch",
    "download_log"       => "Utility: Download Log",
    "notification_mgmt"  => "Utility: Notification",
    "change_password"    => "Utility: Change Password",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "add_role") {
                $stmt = $pdo->prepare("INSERT INTO roles (role_name, role_description) VALUES (?, ?)");
                $stmt->execute([$_POST["role_name"], $_POST["role_description"]]);
                $role_id = $pdo->lastInsertId();
                foreach ($modules as $mod => $name) {
                    $pdo->prepare("INSERT INTO role_permissions (role_id, module, can_read) VALUES (?, ?, TRUE)")->execute([$role_id, $mod]);
                }
                $message = "Role added successfully.";
            } elseif ($_POST["action"] === "edit_role") {
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, role_description = ? WHERE id = ?");
                $stmt->execute([$_POST["role_name"], $_POST["role_description"], $_POST["id"]]);
                $message = "Role updated successfully.";
            } elseif ($_POST["action"] === "delete_role") {
                $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$_POST["id"]]);
                $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$_POST["id"]]);
                $message = "Role deleted.";
            } elseif ($_POST["action"] === "save_permissions") {
                $role_id = $_POST["role_id"];
                $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);
                foreach ($_POST["permissions"] ?? [] as $mod => $perms) {
                    $pdo->prepare("INSERT INTO role_permissions (role_id, module, can_create, can_read, can_update, can_delete) VALUES (?, ?, ?, ?, ?, ?)")->execute([
                        $role_id, $mod,
                        isset($perms["c"]) ? 1 : 0,
                        isset($perms["r"]) ? 1 : 0,
                        isset($perms["u"]) ? 1 : 0,
                        isset($perms["d"]) ? 1 : 0
                    ]);
                }
                $message = "Permissions saved successfully.";
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$roles = $pdo->query("
    SELECT r.*, 
    (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count 
    FROM roles r 
    ORDER BY r.id ASC
")->fetchAll();

$selected_role = $_GET["role_id"] ?? ($roles[0]["id"] ?? null);
$permissions = [];
if ($selected_role) {
    $perms = $pdo->prepare("SELECT * FROM role_permissions WHERE role_id = ?");
    $perms->execute([$selected_role]);
    foreach ($perms->fetchAll() as $p) {
        $permissions[$p["module"]] = $p;
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-user-shield me-2"></i> Role Management</h2>
      <p class="text-muted">Part of the System section. Define and manage user permissions and roles.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="clearForm()">
      <i class="fas fa-plus me-1"></i> Add Role
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

<div class="row">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <span class="text-muted small">Total <?= count($roles) ?> roles</span>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php foreach ($roles as $r): ?>
          <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $selected_role == $r["id"] ? "active" : "" ?>" 
               onclick="window.location='?role_id=<?= $r["id"] ?>'">
            <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              <strong><?= htmlspecialchars($r["role_name"]) ?></strong>
              <br><small class="<?= $selected_role == $r["id"] ? "text-white-50" : "text-muted" ?>"><?= $r["user_count"] ?> users</small>
            </div>
            <div class="d-flex gap-1">
              <button class="btn btn-sm <?= $selected_role == $r["id"] ? "btn-light" : "btn-outline-primary" ?>" onclick="event.stopPropagation(); editRole(<?= htmlspecialchars(json_encode($r)) ?>)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm <?= $selected_role == $r["id"] ? "btn-light text-danger" : "btn-outline-danger" ?>" onclick="event.stopPropagation(); deleteRole(<?= $r["id"] ?>, <?= json_encode($r["role_name"]) ?>)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-9">
    <?php if ($selected_role): ?>
    <form method="POST">
      <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-primary">
            <i class="fas fa-key me-2"></i>Permissions - <?= htmlspecialchars($roles[array_search($selected_role, array_column($roles, "id"))]["role_name"] ?? "") ?>
          </h5>
          <button type="submit" class="btn btn-primary shadow-sm">
            <i class="fas fa-save me-1"></i> Save Permissions
          </button>
        </div>
        <div class="card-body p-0">
          <input type="hidden" name="action" value="save_permissions">
          <input type="hidden" name="role_id" value="<?= $selected_role ?>">
          
          <?php 
          // Build category groups from module names
          $categories = [];
          foreach ($modules as $mod => $name) {
              $cat = explode(': ', $name)[0];
              $categories[$cat][] = $mod;
          }
          // Calculate stats
          $total = count($modules);
          $enabled = 0;
          foreach ($modules as $mod => $name) {
              $p = $permissions[$mod] ?? ["can_create"=>0, "can_read"=>1, "can_update"=>0, "can_delete"=>0];
              if ($p["can_read"]) $enabled++;
          }
          ?>
          
          <!-- Permission Summary -->
          <div class="px-4 py-3 border-bottom bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <span>
                <i class="fas fa-check-circle text-success me-1"></i>
                <strong><?= $enabled ?></strong> of <strong><?= $total ?></strong> modules enabled
                <span class="text-muted small ms-2">(<?= round($enabled/$total*100) ?>%)</span>
              </span>
              <span class="small text-muted">
                <i class="fas fa-check-square me-1"></i> Check <strong>Read</strong> to grant page access
              </span>
            </div>
          </div>
          
          <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light text-muted small sticky-top">
                <tr>
                  <th class="ps-4" style="width: 40%;">Module</th>
                  <th class="text-center">Create</th>
                  <th class="text-center">Read</th>
                  <th class="text-center">Update</th>
                  <th class="text-center">Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $prev_cat = null;
                foreach ($modules as $mod => $name): 
                  $p = $permissions[$mod] ?? ["can_create"=>0, "can_read"=>1, "can_update"=>0, "can_delete"=>0];
                  $cat = explode(': ', $name)[0];
                  // Category header row
                  if ($cat !== $prev_cat):
                    $prev_cat = $cat;
                ?>
                <tr class="bg-secondary bg-opacity-10">
                  <td colspan="5" class="ps-4 py-2">
                    <strong class="text-secondary" style="font-size: 0.85rem;">
                      <i class="fas fa-folder-open me-2"></i><?= htmlspecialchars($cat) ?>
                      <span class="text-muted small fw-normal ms-2">
                        (<?= count($categories[$cat]) ?> modules)
                      </span>
                    </strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 ms-3" 
                      onclick="toggleCategory(this, '<?= $cat ?>')" style="font-size: 0.75rem;">
                      <i class="fas fa-check-double me-1"></i>All
                    </button>
                  </td>
                </tr>
                <?php endif; ?>
                <tr class="<?= $p['can_read'] ? '' : 'table-danger' ?>">
                  <td class="ps-4">
                    <div class="fw-bold"><?= htmlspecialchars($name) ?></div>
                    <small class="text-muted d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($mod) ?></small>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input category-<?= str_replace(' ', '_', $cat) ?>" 
                             name="permissions[<?= $mod ?>][c]" <?= $p["can_create"] ? "checked" : "" ?>>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input category-<?= str_replace(' ', '_', $cat) ?>"
                             name="permissions[<?= $mod ?>][r]" <?= $p["can_read"] ? "checked" : "" ?>
                             onchange="this.closest('tr').className=this.checked?'':'table-danger'">
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input category-<?= str_replace(' ', '_', $cat) ?>"
                             name="permissions[<?= $mod ?>][u]" <?= $p["can_update"] ? "checked" : "" ?>>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                      <input type="checkbox" class="form-check-input category-<?= str_replace(' ', '_', $cat) ?>"
                             name="permissions[<?= $mod ?>][d]" <?= $p["can_delete"] ? "checked" : "" ?>>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <script>
          function toggleCategory(btn, catName) {
            var cls = 'category-' + catName.replace(/ /g, '_');
            var checked = btn.classList.contains('all-on');
            document.querySelectorAll('.' + cls).forEach(function(cb) {
              cb.checked = !checked;
              // Also toggle row highlighting for Read
              if (cb.name && cb.name.endsWith('[r]')) {
                cb.closest('tr').className = !checked ? '' : 'table-danger';
              }
            });
            btn.classList.toggle('all-on');
            btn.innerHTML = checked ? '<i class="fas fa-check-double me-1"></i>All' : '<i class="fas fa-times me-1"></i>None';
          }
          </script>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Add/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add_role">
          <input type="hidden" name="id" id="roleId">
          <div class="mb-3">
            <label class="form-label">Role Name *</label>
            <input type="text" name="role_name" id="roleName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="role_description" id="roleDescription" class="form-control" rows="3"></textarea>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="delete_role">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete role <strong id="deleteName"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearForm() {
  document.getElementById("formAction").value = "add_role";
  document.getElementById("modalTitle").innerText = "Add Role";
  document.getElementById("roleId").value = "";
  document.getElementById("roleName").value = "";
  document.getElementById("roleDescription").value = "";
}

function editRole(role) {
  document.getElementById("formAction").value = "edit_role";
  document.getElementById("modalTitle").innerText = "Edit Role";
  document.getElementById("roleId").value = role.id;
  document.getElementById("roleName").value = role.role_name;
  document.getElementById("roleDescription").value = role.role_description || "";
  var myModal = new bootstrap.Modal(document.getElementById("roleModal"));
  myModal.show();
}

function deleteRole(id, name) {
  document.getElementById("deleteId").value = id;
  document.getElementById("deleteName").innerText = name;
  var myModal = new bootstrap.Modal(document.getElementById("deleteModal"));
  myModal.show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>