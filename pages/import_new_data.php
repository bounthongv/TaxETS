<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$message = "";
$msg_type = "success";
$summary_counts = [
    'repo_moic' => 0,
    'repo_taxris' => 0,
    'repo_mpi' => 0,
    'repo_sezo' => 0
];

// --- Handle Delete Batch ---
if (isset($_POST["action"]) && $_POST["action"] === "delete_batch" && !empty($_POST["batch_id"])) {
    $batch_id = $_POST["batch_id"];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM repo_moic WHERE import_batch_id = ?")->execute([$batch_id]);
        $pdo->prepare("DELETE FROM repo_taxris WHERE import_batch_id = ?")->execute([$batch_id]);
        $pdo->prepare("DELETE FROM repo_mpi WHERE import_batch_id = ?")->execute([$batch_id]);
        $pdo->prepare("DELETE FROM repo_sezo WHERE import_batch_id = ?")->execute([$batch_id]);
        $pdo->commit();
        $message = "Batch <code>$batch_id</code> deleted from all repositories.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error deleting batch: " . $e->getMessage(); $msg_type = "danger";
    }
}

// --- Lookup Cache ---
$province_map = $pdo->query("SELECT LOWER(province_name) as name, id FROM provinces")->fetchAll(PDO::FETCH_KEY_PAIR);
$sector_map = $pdo->query("SELECT LOWER(sector_name) as name, id FROM business_sectors")->fetchAll(PDO::FETCH_KEY_PAIR);
$category_map = $pdo->query("SELECT LOWER(category_name) as name, id FROM business_categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper to get District ID
function getDistrictId($pdo, $name, $province_id) {
    if (!$name) return null;
    $stmt = $pdo->prepare("SELECT id FROM districts WHERE LOWER(district_name) = LOWER(?) AND (province_id = ? OR province_id IS NULL) LIMIT 1");
    $stmt->execute([trim($name), $province_id]);
    return $stmt->fetchColumn() ?: null;
}

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"]) && !isset($_POST["action"])) {
    $file = $_FILES["excel_file"];
    
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error."; $msg_type = "danger";
    } else {
        try {
            $spreadsheet = IOFactory::load($file["tmp_name"]);
            $sheet = $spreadsheet->getActiveSheet();
            $batch_id = "CONS_" . date("YmdHis");
            $imported = 0;

            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $tin = trim($sheet->getCell("D" . $row)->getCalculatedValue());
                // Skip headers or empty rows
                if (empty($tin) || $tin === "TaxRIS" || $tin === "TIN" || $tin === "Year") continue;

                // Helper: get boolean flag (1 or 0) from cell
                $flag = function($col) use ($sheet, $row) {
                    $v = $sheet->getCell($col . $row)->getCalculatedValue();
                    return ($v == 1 || strtolower(trim($v)) === "yes" || strtolower(trim($v)) === "true") ? 1 : 0;
                };
                // Helper: get date string
                $dateVal = function($col) use ($sheet, $row) {
                    $v = $sheet->getCell($col . $row)->getCalculatedValue();
                    if (!$v) return null;
                    if (is_numeric($v)) {
                        $d = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v);
                        return $d->format("Y-m-d");
                    }
                    return $v;
                };

                $year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
                $company_name = $sheet->getCell("C" . $row)->getCalculatedValue();
                $province_name = trim($sheet->getCell("E" . $row)->getCalculatedValue());
                $district_name = trim($sheet->getCell("F" . $row)->getCalculatedValue());
                $sector_name = trim($sheet->getCell("J" . $row)->getCalculatedValue());

                // Lookup IDs
                $province_id = isset($province_map[strtolower($province_name)]) ? $province_map[strtolower($province_name)] : null;
                $district_id = getDistrictId($pdo, $district_name, $province_id);
                $sector_id = isset($sector_map[strtolower($sector_name)]) ? $sector_map[strtolower($sector_name)] : null;
                $category_id = isset($category_map[strtolower($sector_name)]) ? $category_map[strtolower($sector_name)] : null;

                // 1. Distribute to repo_taxris
                $stmt_taxris = $pdo->prepare("INSERT INTO repo_taxris (import_batch_id, tin, company_name, year, revenue, expense, net_profit, tax_paid, reinvest_net_profit, total_assets, vat_system_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_taxris->execute([
                    $batch_id, $tin, $company_name, $year,
                    (float)$sheet->getCell("K" . $row)->getCalculatedValue(),
                    (float)$sheet->getCell("L" . $row)->getCalculatedValue(),
                    (float)$sheet->getCell("M" . $row)->getCalculatedValue(),
                    (float)$sheet->getCell("O" . $row)->getCalculatedValue(),
                    (float)$sheet->getCell("N" . $row)->getCalculatedValue(),
                    (float)$sheet->getCell("AO" . $row)->getCalculatedValue(),
                    $flag("AL")
                ]);
                $summary_counts['repo_taxris']++;

                // 2. Distribute to repo_moic
                // Map Art 9 Activities (AC to AK) to available art9 columns if needed, 
                // but let's prioritize the specific scope flags first.
                $stmt_moic = $pdo->prepare("INSERT INTO repo_moic (
                    import_batch_id, tin, company_name, province_id, district_id, license_date, 
                    hr_dev_scope, innovative_tech_scope, prod_industry_scope, 
                    public_health_scope, real_estate_scope, vat_system_status,
                    art9_p2_scope, art9_p3_scope, art9_p4_scope, art9_p5_scope, art9_p6_scope
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt_moic->execute([
                    $batch_id, $tin, $company_name, $province_id, $district_id,
                    $dateVal("T"), // Registration date used as profile license date
                    $flag("U"), // HR Dev
                    $flag("V"), // Innovative Tech
                    $flag("Y"), // Production Industry (combined column in Excel)
                    $flag("Z"), // Public Health / Social
                    $flag("AB"), // Real Estate Transfer
                    $flag("AL"), // VAT System
                    // Mapping Art 9 Activity columns to placeholders for now
                    $flag("AC"), // Activity 1
                    $flag("AD"), // Activity 2
                    $flag("AE"), // Activity 3
                    $flag("AF"), // Activity 4
                    $flag("AG")  // Activity 5
                ]);
                $summary_counts['repo_moic']++;

                // 3. Distribute to repo_mpi
                $stmt_mpi = $pdo->prepare("INSERT INTO repo_mpi (import_batch_id, tin, project_name, investment_license_date, sector_id, tax_holiday_period) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_mpi->execute([
                    $batch_id, $tin, $company_name, $dateVal("B"), $sector_id,
                    (string)$sheet->getCell("S" . $row)->getCalculatedValue()
                ]);
                $summary_counts['repo_mpi']++;

                // 4. Distribute to repo_sezo (if SEZ flag is set)
                $is_dev = $flag("W");
                $is_inv = $flag("X");
                if ($is_dev || $is_inv) {
                    $stmt_sezo = $pdo->prepare("INSERT INTO repo_sezo (import_batch_id, tin, company_name, province_id, district_id, category_id, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_sezo->execute([
                        $batch_id, $tin, $company_name, $province_id, $district_id, $category_id,
                        $is_dev ? 'Developer' : 'Investor'
                    ]);
                    $summary_counts['repo_sezo']++;
                }

                // 5. Reference Check: Mark 'companies' record for repository-based classification
                // If a company with this TIN exists in the main TE table, we could potentially
                // tag it or just rely on the TIN for join-based reporting in the future.
                // For now, we ensure that if we want to report TE by "Source Repository", 
                // we have the link established via the TIN.
                
                $imported++;
            }
            $message = "Consolidated Data Distributed Successfully! Batch ID: <code>$batch_id</code>";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage(); $msg_type = "danger";
        }
    }
}

// Fetch recent consolidated batches
$recent_batches = $pdo->query("
    SELECT import_batch_id, created_at, COUNT(*) as `total_rows`
    FROM (
        SELECT import_batch_id, created_at FROM repo_taxris WHERE import_batch_id LIKE 'CONS_%'
        UNION ALL
        SELECT import_batch_id, created_at FROM repo_moic WHERE import_batch_id LIKE 'CONS_%'
        UNION ALL
        SELECT import_batch_id, created_at FROM repo_mpi WHERE import_batch_id LIKE 'CONS_%'
        UNION ALL
        SELECT import_batch_id, created_at FROM repo_sezo WHERE import_batch_id LIKE 'CONS_%'
    ) AS combined
    GROUP BY import_batch_id
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2 text-primary"></i> Consolidated Stakeholder Data Import</h2>
    <p class="text-muted">Import data from various stakeholders (MOIC, MPI, TaxRIS, etc.) to identify repositories and create cross-check references.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm">
    <strong><?= $message ?></strong>
    <?php if ($msg_type === "success" && !empty($summary_counts['repo_taxris'])): ?>
    <hr>
    <div class="row small">
        <div class="col-md-3 border-end"><strong>MOIC:</strong> <?= $summary_counts['repo_moic'] ?></div>
        <div class="col-md-3 border-end"><strong>TaxRIS:</strong> <?= $summary_counts['repo_taxris'] ?></div>
        <div class="col-md-3 border-end"><strong>MPI:</strong> <?= $summary_counts['repo_mpi'] ?></div>
        <div class="col-md-3"><strong>SEZO:</strong> <?= $summary_counts['repo_sezo'] ?></div>
    </div>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Import Form -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white fw-bold py-3">
                <i class="fas fa-upload me-2"></i> Upload Stakeholder Template
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <div class="mb-4 text-center">
                        <i class="fas fa-file-excel fa-4x text-light-50 mb-3 d-block"></i>
                        <label class="form-label fw-bold d-block">Select Excel File (.xlsx)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text mt-3">
                            Format: <code>Import_Data_from_TaxRIS.xlsx</code>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="importBtn">
                            <i class="fas fa-database me-2"></i> Distribute to Repositories
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light border-0 py-3">
                <div class="alert alert-info mb-0 small border-0 shadow-none">
                    <i class="fas fa-info-circle me-2"></i> This tool <strong>does not</strong> affect TE calculation results. It populates stakeholder repositories for identification and future verification.
                </div>
            </div>
        </div>
    </div>

    <!-- Info/Recent Batches -->
    <div class="col-lg-7">
        <!-- Batches Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold py-3">
                <i class="fas fa-history me-2"></i> Recent Consolidated Imports
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-3">Batch ID / Date</th>
                                <th class="text-center">Records</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_batches)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No consolidated imports yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_batches as $b): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold font-monospace small"><?= $b['import_batch_id'] ?></div>
                                        <div class="text-muted extra-small"><i class="far fa-calendar-alt me-1"></i> <?= $b['created_at'] ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary rounded-pill px-3"><?= number_format($b['total_rows']) ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <form method="POST" onsubmit="return confirm('Delete this batch from ALL repositories? This cannot be undone.')">
                                            <input type="hidden" name="action" value="delete_batch">
                                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($b['import_batch_id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt me-1"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Repository Links -->
        <div class="card shadow-sm border-0 border-start border-info border-4">
            <div class="card-body py-3">
                <h6 class="fw-bold text-info"><i class="fas fa-external-link-alt me-2"></i> Explore Repositories</h6>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="repo_moic.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-building me-1"></i> MOIC</a>
                    <a href="repo_taxris.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-invoice-dollar me-1"></i> TaxRIS</a>
                    <a href="repo_mpi.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chart-line me-1"></i> MPI</a>
                    <a href="repo_sezo.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-industry me-1"></i> SEZO</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("importForm").addEventListener("submit", function() {
    const btn = document.getElementById("importBtn");
    btn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Processing...";
    btn.disabled = true;
});
</script>

<style>
.extra-small { font-size: 0.75rem; }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
