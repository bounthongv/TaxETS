<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/te_profit_tax_engine.php";
require_once __DIR__ . "/../includes/te_vat_engine.php";
require_once __DIR__ . "/../includes/te_pit_engine.php";
require_once __DIR__ . "/../includes/te_salary_tax_engine.php";
require_once __DIR__ . "/../includes/te_sez_engine.php";
require_once __DIR__ . "/../includes/te_resource_engine.php";
require_once __DIR__ . "/../includes/te_royalty_engine.php";
require_once __DIR__ . "/../includes/te_land_concession_engine.php";
require_once __DIR__ . "/../includes/te_asycuda_engine.php";

$pdo = getDbConnection();

// If confirmed, run recalculation
$results = [];
$has_errors = false;
$confirmed = ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm"]));

if ($confirmed) {
    set_time_limit(0);
    header("Content-Type: text/html; charset=utf-8");

    $engines = [
        ["name" => "Corporate Income Tax (Profit Tax)",  "batch_col" => "import_batch_id", "table" => "companies",               "engine" => "TEEngine",             "method" => "calculateBatch"],
        ["name" => "Individual Income Tax (PIT)",         "batch_col" => "batch_id",        "table" => "import_pit_data",         "engine" => "TEPitEngine",          "method" => "calculateBatch"],
        ["name" => "Domestic VAT",                         "batch_col" => "batch_id",        "table" => "import_vat_data",         "engine" => "TEVatEngine",          "method" => "calculateBatch"],
        ["name" => "SEZ Developer",                       "batch_col" => "batch_id",        "table" => "import_sez_data",         "engine" => "TESEZEngine",          "method" => "calculateBatch", "extra" => "Developer"],
        ["name" => "SEZ Investor",                        "batch_col" => "batch_id",        "table" => "import_sez_data",         "engine" => "TESEZEngine",          "method" => "calculateBatch", "extra" => "Investor"],
        ["name" => "Resource Fee",                         "batch_col" => "batch_id",        "table" => "import_resource_data",    "engine" => "TEResourceEngine",     "method" => "calculateBatch"],
        ["name" => "Royalty Fee",                          "batch_col" => "batch_id",        "table" => "import_royalty_data",     "engine" => "TERoyaltyEngine",      "method" => "calculateBatch"],
        ["name" => "Land Concession",                      "batch_col" => "import_batch_id", "table" => "repo_land_concession_data","engine" => "TELandConcessionEngine","method" => "calculateBatch"],
        ["name" => "ASYCUDA (Customs, Excise, VAT)",       "batch_col" => "import_batch_id", "table" => "asycuda_imports",          "engine" => "TEAsycudaEngine",      "method" => "calculateBatch"],
    ];

    require_once __DIR__ . "/../includes/header.php";
    echo '<div class="container py-4"><div class="card shadow-sm" style="border-radius: 12px;"><div class="card-body">';
    echo '<h4 class="mb-4"><i class="fas fa-sync-alt me-2"></i> Recalculation Progress</h4>';

    foreach ($engines as $cfg) {
        $where = ($cfg["batch_col"] === "import_batch_id") ? "WHERE import_batch_id IS NOT NULL AND import_batch_id != ''" : "WHERE batch_id IS NOT NULL AND batch_id != ''";
        $type_filter = "";
        if (isset($cfg["extra"])) {
            $type_filter = " AND type = " . $pdo->quote($cfg["extra"]);
        }
        $col = ($cfg["batch_col"] === "import_batch_id") ? "DISTINCT import_batch_id" : "DISTINCT batch_id";
        $stmt = $pdo->query("SELECT $col as bid FROM {$cfg["table"]} $where $type_filter ORDER BY bid");
        $batch_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($batch_ids)) {
            echo "<p class='text-muted'><i class='fas fa-circle me-2' style='color:#6c757d;font-size:0.6rem;'></i> {$cfg['name']}: <em>no batches found</em></p>";
            continue;
        }

        $engine_instance = new $cfg["engine"]($pdo);
        $total = 0;
        $errors = [];

        foreach ($batch_ids as $bid) {
            try {
                if (isset($cfg["extra"])) {
                    $res = $engine_instance->{$cfg["method"]}($bid, $cfg["extra"]);
                } else {
                    $res = $engine_instance->{$cfg["method"]}($bid);
                }
                $total += (int)($res["calculated"] ?? 0);
                if (!empty($res["errors"])) {
                    $errors = array_merge($errors, $res["errors"]);
                }
            } catch (Exception $e) {
                $errors[] = "Batch $bid: " . $e->getMessage();
            }
        }

        $icon = empty($errors) ? '#28a745' : '#dc3545';
        $label = empty($errors) ? "{$total} records processed" : "{$total} records (" . count($errors) . " errors)";
        if ($total > 0 || !empty($errors)) {
            echo "<p><i class='fas fa-circle me-2' style='color:{$icon};font-size:0.6rem;'></i> {$cfg['name']}: <strong>{$label}</strong></p>";
        }
        if (!empty($errors)) {
            $has_errors = true;
        }
        if (ob_get_level()) ob_flush();
        flush();
    }

    echo '<hr><div class="d-flex justify-content-between align-items-center">';
    if ($has_errors) {
        echo '<p class="text-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Completed with some errors. Check individual TE pages for details.</p>';
    } else {
        echo '<p class="text-success mb-0 fw-bold"><i class="fas fa-check-circle me-2"></i> All tax types recalculated successfully!</p>';
    }
    echo '<a href="report_tax_type.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i> Back to Report</a>';
    echo '</div></div></div></div>';
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recalculate All Tax Expenditures</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .confirm-card { border-radius: 16px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,0.1); max-width: 560px; }
        .warning-icon { font-size: 3rem; color: #f59e42; }
    </style>
</head>
<body>
    <div class="card confirm-card">
        <div class="card-body p-5 text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle warning-icon"></i>
            </div>
            <h4 class="fw-bold mb-3">Recalculate All Tax Expenditures</h4>
            <p class="text-muted mb-4">
                This will run the TE calculation engine across <strong>all</strong> tax types and batches.
                The process may take several minutes. All existing TE results will be recalculated.
            </p>
            <ul class="list-unstyled text-start text-muted small mb-4 bg-light p-3 rounded">
                <li><i class="fas fa-check-circle text-success me-2"></i> Corporate Income Tax (Profit Tax)</li>
                <li><i class="fas fa-check-circle text-success me-2"></i> Individual Income Tax (PIT)</li>
                <li><i class="fas fa-check-circle text-success me-2"></i> Salary Tax</li>
                <li><i class="fas fa-check-circle text-success me-2"></i> Domestic VAT</li>
                <li><i class="fas fa-check-circle text-success me-2"></i> Customs Duty, Excise Tax, Import VAT (ASYCUDA)</li>
                <li><i class="fas fa-check-circle text-success me-2"></i> SEZ Developer &amp; Investor</li>
                <li><i class="fas fa-check-circle text-success me-2"></i> Resource Fee, Royalty Fee, Land Concession</li>
            </ul>
            <form method="POST" id="recalcForm">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-warning text-dark fw-bold px-5 py-2" id="confirmBtn">
                    <i class="fas fa-sync-alt me-2"></i> Start Recalculation
                </button>
                <a href="report_tax_type.php" class="btn btn-outline-secondary ms-2 px-4 py-2">Cancel</a>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('confirmBtn')?.addEventListener('click', function(e) {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        document.getElementById('recalcForm').submit();
    });
    </script>
</body>
</html>
