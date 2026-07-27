<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$modules = [
    "system_users"       => "User Management",
    "system_roles"       => "Role Management",
    "system_history"     => "Operation Logs",
    "system_logs"        => "Error Logs",
    "system_ip"          => "IP Access Control",
    "system_online"      => "Online Users",
    "system_backup"      => "Backup & Restore",
    "config_rates"       => "CIT Benchmark Rates",
    "config_provisions"  => "CIT Provisions",
    "config_rules"       => "CIT Manual Rules",
    "config_sez_provisions" => "SEZ Provisions",
    "benchmark_msme"     => "MSME Benchmark",
    "benchmark_individual" => "PIT / Individual Benchmark",
    "benchmark_vat"      => "VAT Benchmark",
    "benchmark_customs"  => "Customs Benchmark",
    "benchmark_customs_regime" => "Customs Regime",
    "benchmark_excise"   => "Excise Benchmark",
    "benchmark_art9"     => "Article 9 Activities",
    "benchmark_land_concession" => "Land Concession",
    "benchmark_nontax"   => "Non-Tax Revenue",
    "benchmark_payment_condition" => "Payment Condition",
    "vat_config_rules"   => "VAT Rules",
    "customs_config_rules" => "Customs Rules",
    "dictionary_province"  => "Province",
    "dictionary_district"  => "District",
    "dictionary_village"   => "Village",
    "dictionary_zone"      => "Investment Zone",
    "dictionary_sector"    => "Sector",
    "dictionary_enterprise_type" => "Enterprise Type",
    "dictionary_moic_categories" => "MOIC Categories",
    "dictionary_status"    => "Status",
    "import_cit"         => "Corporate Income Tax",
    "import_individual"  => "Individual / PIT",
    "import_vat"         => "VAT",
    "import_asycuda"     => "ASYCUDA (Customs/Excise)",
    "import_sez_dev"     => "SEZ Developer",
    "import_sez_inv"     => "SEZ Investor",
    "import_salary"      => "Salary Tax",
    "import_lse"         => "LSE Data",
    "import_land"        => "Land Tax",
    "import_land_concession" => "Land Concession",
    "import_resource"    => "Natural Resource",
    "import_royalty"     => "Royalty",
    "import_moic"        => "MOIC (Enterprise)",
    "import_mpi"         => "MPI (Investment)",
    "import_molsw"       => "MOLSW",
    "import_taxris"      => "TaxRIS",
    "import_gdp"         => "GDP Data",
    "import_districts"   => "Districts (CSV)",
    "import_tariff"      => "Tariff",
    "import_new_data"    => "Legacy Migration",
    "view_companies"     => "View Companies (CIT)",
    "view_individual"    => "View Individual Tax",
    "view_vat"           => "View VAT",
    "view_salary"        => "View Salary Tax",
    "view_resource"      => "View Resource",
    "view_royalty"       => "View Royalty",
    "view_sez_dev"       => "View SEZ Developer",
    "view_sez_inv"       => "View SEZ Investor",
    "asycuda_customs"    => "ASYCUDA Customs",
    "asycuda_excise"     => "ASYCUDA Excise",
    "asycuda_vat"        => "ASYCUDA VAT",
    "calculator"         => "TE Calculator",
    "recalculate_all"    => "Recalculate All",
    "calculate_land_concession" => "Land Concession Calc",
    "te_asycuda_customs" => "Customs TE",
    "te_asycuda_excise"  => "Excise TE",
    "te_asycuda_vat"     => "ASYCUDA VAT TE",
    "te_customs"         => "Customs TE (detailed)",
    "te_excise"          => "Excise TE (detailed)",
    "te_individual"      => "Individual TE",
    "te_nontax"          => "Non-Tax TE",
    "te_resource"        => "Resource TE",
    "te_royalty"         => "Royalty TE",
    "te_salary_tax"      => "Salary Tax TE",
    "te_sez_dev"         => "SEZ Developer TE",
    "te_sez_inv"         => "SEZ Investor TE",
    "te_vat"             => "VAT TE",
    "report_tax_type"    => "Tax Type Summary",
    "report_provisions"  => "Provisions Summary",
    "report_summary"     => "Overall Summary",
    "report_sector"      => "By Sector",
    "report_location"    => "By Location",
    "report_revenue"     => "Revenue Impact",
    "report_customs_duty" => "Customs Duty",
    "report_customs_provision" => "Customs Provisions",
    "report_excise_tax"  => "Excise Tax",
    "report_excise_provision"  => "Excise Provisions",
    "report_import_vat"  => "Import VAT",
    "report_vat_provision" => "VAT Provisions",
    "report_individual_provision" => "Individual/PIT Provisions",
    "report_salary_tax_provision" => "Salary Tax Provisions",
    "report_nontax_provision"     => "Non-Tax Provisions",
    "report_sez_dev_provision"    => "SEZ Developer Provisions",
    "report_sez_inv_provision"    => "SEZ Investor Provisions",
    "report_total_customs" => "Total Customs",
    "report_total_provision" => "Total Provisions",
    "report_gdp"         => "GDP Impact",
    "repo_individual"    => "Individual Provisions",
    "repo_vat"           => "VAT Provisions",
    "repo_customs"       => "Customs Data",
    "repo_excise"        => "Excise Data",
    "repo_land_concession" => "Land Concession",
    "repo_lse"           => "LSE Data",
    "repo_moic"          => "MOIC Data",
    "repo_molsw"         => "MOLSW Data",
    "repo_mpi"           => "MPI Data",
    "repo_natural_resource" => "Natural Resource",
    "repo_nontax"        => "Non-Tax Revenue",
    "repo_royalty"       => "Royalty Data",
    "repo_sezo"          => "SEZ Data",
    "repo_taxris"        => "TaxRIS Data",
    "repo_gdp"           => "GDP Data",
    "repo_milestones"    => "Milestones",
    "batches"            => "Batch Manager",
    "delete_batch"       => "Delete Batch",
    "download_log"       => "Download Log",
    "notification_mgmt"  => "Notification",
    "change_password"    => "Change Password",
];

// Category definitions: module_key prefix matches are grouped together
$category_groups = [
    "System"        => ["system_"],
    "Configuration" => ["config_", "benchmark_", "vat_", "customs_"],
    "Dictionary"    => ["dictionary_"],
    "Import"        => ["import_"],
    "Data Entry"    => ["view_", "asycuda_"],
    "Calculation"   => ["calculator", "recalculate_", "calculate_", "te_"],
    "Reports"       => ["report_", "repo_"],
    "Utilities"     => ["batches", "delete_", "download_", "notification_", "change_"],
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
          // Build category grouping from prefix matching
          $categories = [];
          $module_cat = [];
          foreach ($modules as $mod => $name) {
              $cat = "Other";
              foreach ($category_groups as $cname => $prefixes) {
                  foreach ($prefixes as $pref) {
                      if (strpos($mod, $pref) === 0) { $cat = $cname; break 2; }
                  }
              }
              $categories[$cat][] = $mod;
              $module_cat[$mod] = $cat;
          }
          // Calculate stats
          $total = count($modules);
          $enabled = 0;
          foreach ($modules as $mod => $name) {
              $p = $permissions[$mod] ?? ["can_create"=>0, "can_read"=>0, "can_update"=>0, "can_delete"=>0];
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
                  $p = $permissions[$mod] ?? ["can_create"=>0, "can_read"=>0, "can_update"=>0, "can_delete"=>0];
                  $cat = $module_cat[$mod];
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