<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

$backup_dir = __DIR__ . "/../backups/";
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["action"])) {
            if ($_POST["action"] === "backup") {
                $filename = "tax_ets_backup_" . date("Y-m-d_His") . ".sql";
                $filepath = $backup_dir . $filename;
                
                $tables = [
                    "roles" => "CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);",
                    "users" => "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100),
    phone VARCHAR(20),
    role_id INT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);",
                    "user_history" => "CREATE TABLE IF NOT EXISTS user_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(100),
    action VARCHAR(50) NOT NULL,
    details VARCHAR(255),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);",
                    "user_sessions" => "CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255),
    ip_address VARCHAR(45),
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_online BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);",
                    "ip_access" => "CREATE TABLE IF NOT EXISTS ip_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(10) NOT NULL,
    description VARCHAR(255),
    start_date DATE,
    end_date DATE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);",
                    "system_settings" => "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);",
                    "alert_recipients" => "CREATE TABLE IF NOT EXISTS alert_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);",
                    "role_permissions" => "CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    module VARCHAR(100) NOT NULL,
    can_create BOOLEAN DEFAULT FALSE,
    can_read BOOLEAN DEFAULT TRUE,
    can_update BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_module (role_id, module)
);",
                    "bm_profit_standard" => "CREATE TABLE IF NOT EXISTS bm_profit_standard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    rate_percentage DECIMAL(5, 2) NOT NULL
);",
                    "bm_profit_mandatory" => "CREATE TABLE IF NOT EXISTS bm_profit_mandatory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    sector VARCHAR(100) NOT NULL,
    sub_sector VARCHAR(255),
    profit_base_rate DECIMAL(5, 2) NOT NULL
);",
                    "bm_profit_sme" => "CREATE TABLE IF NOT EXISTS bm_profit_sme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    sector VARCHAR(100) NOT NULL,
    turnover_min DECIMAL(15, 2) DEFAULT 0,
    turnover_max DECIMAL(15, 2),
    rate_percentage DECIMAL(5, 2) NOT NULL
);",
                    "profit_provisions" => "CREATE TABLE IF NOT EXISTS profit_provisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_number VARCHAR(10) NOT NULL,
    legal_reference VARCHAR(255),
    description TEXT,
    target_rate DECIMAL(5, 2) DEFAULT NULL,
    is_exemption BOOLEAN DEFAULT FALSE
);",
                    "profit_provision_conditions" => "CREATE TABLE IF NOT EXISTS profit_provision_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    operator VARCHAR(20) NOT NULL,
    value_1 VARCHAR(255),
    value_2 VARCHAR(255),
    FOREIGN KEY (provision_id) REFERENCES profit_provisions(id) ON DELETE CASCADE
);",
                    "companies" => "CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tax_year INT NOT NULL,
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    province VARCHAR(100),
    district VARCHAR(100),
    sector VARCHAR(100),
    is_vat_holder BOOLEAN DEFAULT FALSE,
    zone_1 BOOLEAN DEFAULT FALSE,
    zone_2 BOOLEAN DEFAULT FALSE,
    zone_3 BOOLEAN DEFAULT FALSE,
    revenue DECIMAL(20, 2) DEFAULT 0,
    expense DECIMAL(20, 2) DEFAULT 0,
    net_profit DECIMAL(20, 2) DEFAULT 0,
    re_invested_profit DECIMAL(20, 2) DEFAULT 0,
    pt_paid DECIMAL(20, 2) DEFAULT 0,
    activity_type VARCHAR(100),
    staff_count INT DEFAULT 0,
    total_assets DECIMAL(20, 2) DEFAULT 0,
    registration_date DATE,
    investment_license_date DATE
);",
                    "te_profit_result" => "CREATE TABLE IF NOT EXISTS te_profit_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    benchmark_rate_applied DECIMAL(5, 2),
    benchmark_pt DECIMAL(20, 2) DEFAULT 0,
    pt_te DECIMAL(20, 2) DEFAULT 0,
    matched_provisions VARCHAR(255),
    profit_tax_te DECIMAL(20, 2) DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);"
                ];
                
                $sql_content = "-- Tax-ETS Full Backup " . date("Y-m-d H:i:s") . "\n";
                $sql_content .= "-- Database: tax_ets\n\n";
                $sql_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
                
                foreach ($tables as $table => $create_sql) {
                    $sql_content .= "-- \n-- Table: $table\n-- \n";
                    $sql_content .= "DROP TABLE IF EXISTS $table;\n";
                    $sql_content .= $create_sql . ";\n\n";
                    
                    $stmt = $pdo->query("SELECT * FROM $table");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($rows)) {
                        $columns = array_keys($rows[0]);
                        $sql_content .= "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES\n";
                        foreach ($rows as $i => $row) {
                            $values = array_map(function($v) use ($pdo) {
                                return $v === null ? "NULL" : $pdo->quote($v);
                            }, $row);
                            $comma = ($i < count($rows) - 1) ? ",\n" : ";\n";
                            $sql_content .= "(" . implode(", ", $values) . ")" . $comma;
                        }
                        $sql_content .= "\n";
                    }
                }
                
                $sql_content .= "SET FOREIGN_KEY_CHECKS = 1;\n";
                
                file_put_contents($filepath, $sql_content);
                $message = "Full backup created: $filename";
            } elseif ($_POST["action"] === "restore") {
if (!empty($_FILES["backup_file"]["name"])) {
                        $ext = strtolower(pathinfo($_FILES["backup_file"]["name"], PATHINFO_EXTENSION));
                        if ($ext !== "sql") {
                            $message = "Please select a .sql file";
                            $msg_type = "danger";
                        } else {
                            $sql_content = file_get_contents($_FILES["backup_file"]["tmp_name"]);
                            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                            $pdo->exec($sql_content);
                            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                            $message = "Database restored successfully. Please refresh the page.";
                        }
                    }
            } elseif ($_POST["action"] === "delete_backup") {
                $file = $backup_dir . $_POST["filename"];
                if (file_exists($file)) {
                    unlink($file);
                    $message = "Backup deleted";
                }
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

$backups = array_diff(scandir($backup_dir), [".", ".."]);
rsort($backups);

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-database me-2"></i> Backup/Restore Data</h2>
    <p class="text-muted">Part of the System section. Database maintenance and recovery tools.</p>
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
  <!-- Backup Section -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="fas fa-download me-2"></i> Create Backup</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Create a full backup of the database including all tables and data.</p>
        <form method="POST">
          <input type="hidden" name="action" value="backup">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-database me-2"></i> Create Backup Now
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Restore Section -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="fas fa-upload me-2"></i> Restore Backup</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Restore database from a .sql backup file. <strong class="text-danger">Warning: This will overwrite existing data!</strong></p>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="restore">
          <div class="mb-3">
            <input type="file" name="backup_file" class="form-control" accept=".sql" required>
          </div>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-upload me-2"></i> Restore Backup
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Backup Files List -->
<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-header bg-white border-0 py-3">
    <h5 class="mb-0"><i class="fas fa-folder me-2"></i> Backup Files</h5>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead class="bg-light">
        <tr>
          <th>Filename</th>
          <th>Size</th>
          <th>Date Created</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($backups as $file): 
          $filepath = $backup_dir . $file;
          if (is_file($filepath)):
        ?>
        <tr>
          <td><i class="fas fa-file-sql text-info me-2"></i><?= htmlspecialchars($file) ?></td>
          <td><?= round(filesize($filepath) / 1024, 1) ?> KB</td>
          <td><?= date("d/m/Y H:i", filemtime($filepath)) ?></td>
          <td class="text-center">
            <a href="<?= BASE_URL ?>/backups/<?= $file ?>" class="btn btn-sm btn-outline-primary" download>
              <i class="fas fa-download"></i>
            </a>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('<?= $file ?>')">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
        <?php endif; endforeach; ?>
        <?php if (empty($backups)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No backup files found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function deleteBackup(filename) {
    if (confirm("Delete backup file " + filename + "?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = '<input type="hidden" name="action" value="delete_backup"><input type="hidden" name="filename" value="' + filename + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>