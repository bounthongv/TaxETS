<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();

// 1. Fetch All Available Years for the Dropdowns
$all_years = [];
try {
    $year_queries = [
        "SELECT DISTINCT tax_year FROM companies WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM te_individual_result WHERE tax_year > 0",
        "SELECT DISTINCT YEAR(filing_period) as yr FROM import_vat_data WHERE filing_period IS NOT NULL AND filing_period != '0000-00-00'",
        "SELECT DISTINCT YEAR(doc_date) as yr FROM asycuda_imports WHERE doc_date IS NOT NULL AND doc_date != '0000-00-00'"
    ];
    foreach ($year_queries as $q) {
        $stmt = $pdo->query($q);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            if ($row[0] > 1900 && $row[0] < 2100) $all_years[] = (int)$row[0];
        }
    }
    $all_years = array_unique($all_years);
    sort($all_years);
} catch (Exception $e) {}

// Get Year Filters
$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0;
$to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;

// Default range if not set (Min -> Max)
if (!$from_year || !$to_year) {
    if (!empty($all_years)) {
        $from_year = min($all_years);
        $to_year = max($all_years);
    } else {
        $current_year = (int)date('Y');
        $from_year = $current_year - 4;
        $to_year = $current_year;
    }
}

// Selected range of years for the horizontal axis
$display_years = [];
for ($y = $from_year; $y <= $to_year; $y++) {
    $display_years[] = $y;
}

// 2. Fetch All Provinces across all modules
$provinces = [];
try {
    $p_queries = [
        "SELECT DISTINCT province FROM companies WHERE province IS NOT NULL AND province != ''",
        "SELECT DISTINCT province FROM import_vat_data WHERE province IS NOT NULL AND province != ''",
        "SELECT DISTINCT province FROM asycuda_imports WHERE province IS NOT NULL AND province != ''"
    ];
    foreach ($p_queries as $q) {
        $stmt = $pdo->query($q);
        while ($row = $stmt->fetchColumn()) {
            $provinces[] = trim($row);
        }
    }
    $provinces = array_unique($provinces);
    sort($provinces);
} catch (Exception $e) {}

// 3. Aggregate Data Matrix [Province][Year] => Total TE
$matrix = [];
$unclassified_row = []; // Year => Sum

try {
    // 3.1 Profit Tax (CIT)
    $stmt = $pdo->prepare("SELECT c.province, c.tax_year, SUM(r.profit_tax_te) as te 
                           FROM companies c 
                           JOIN te_profit_result r ON c.id = r.company_id 
                           WHERE c.tax_year BETWEEN ? AND ? 
                           GROUP BY c.province, c.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['province']);
        $yr = (int)$row['tax_year'];
        if ($loc) {
            $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te'];
        } else {
            $unclassified_row[$yr] = ($unclassified_row[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // 3.2 Domestic VAT
    $stmt = $pdo->prepare("SELECT province, YEAR(filing_period) as yr, SUM(expert_te) as te 
                           FROM import_vat_data 
                           WHERE YEAR(filing_period) BETWEEN ? AND ? 
                           GROUP BY province, yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['province']);
        $yr = (int)$row['yr'];
        if ($loc) {
            $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te'];
        } else {
            $unclassified_row[$yr] = ($unclassified_row[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // 3.3 ASYCUDA (Customs, Excise, Import VAT)
    $stmt = $pdo->prepare("SELECT ai.province, YEAR(ai.doc_date) as yr, SUM(r.total_te) as te 
                           FROM te_asycuda_result r 
                           JOIN asycuda_imports ai ON r.asycuda_id = ai.id 
                           WHERE YEAR(ai.doc_date) BETWEEN ? AND ? 
                           GROUP BY ai.province, yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['province']);
        $yr = (int)$row['yr'];
        if ($loc) {
            $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te'];
        } else {
            $unclassified_row[$yr] = ($unclassified_row[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // 3.4 Individual Tax (PIT) - Link via TIN to Companies for Location
    // If no match in companies table, it goes to unclassified
    $stmt = $pdo->prepare("SELECT COALESCE(c.province, 'Unclassified') as loc, r.tax_year, SUM(r.te_amount) as te 
                           FROM te_individual_result r 
                           LEFT JOIN companies c ON r.tin = c.tin 
                           WHERE r.tax_year BETWEEN ? AND ? 
                           GROUP BY loc, r.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['loc']);
        $yr = (int)$row['tax_year'];
        if ($loc != 'Unclassified') {
            $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te'];
        } else {
            $unclassified_row[$yr] = ($unclassified_row[$yr] ?? 0) + (float)$row['te'];
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold text-dark"><i class="fas fa-map-marked-alt me-2 text-primary"></i> Tax Expenditure by Location (Province)</h2>
        <p class="text-muted">Geographic breakdown of Tax Expenditures across the country. Data aggregated from all Tax Modules.</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-outline-success shadow-sm" onclick="window.print()"><i class="fas fa-print me-2"></i> Print Report</button>
        <button class="btn btn-success shadow-sm ms-2"><i class="fas fa-file-excel me-2"></i> Export Data</button>
    </div>
</div>

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body bg-light rounded-3">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">From Year</label>
                <select name="from_year" class="form-select border-0 shadow-sm">
                    <?php foreach ($all_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $from_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">To Year</label>
                <select name="to_year" class="form-select border-0 shadow-sm">
                    <?php foreach ($all_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $to_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-2"></i> Update</button>
            </div>
            <div class="col-md-2">
                <a href="report_location.php" class="btn btn-outline-secondary w-100 border-0">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Data Matrix Table -->
<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 matrix-table">
                <thead class="bg-primary text-white">
                    <tr class="align-middle">
                        <th class="ps-4 py-3" style="min-width: 280px;">Province Name</th>
                        <?php foreach ($display_years as $year): ?>
                        <th class="text-end py-3"><?= $year ?></th>
                        <?php endforeach; ?>
                        <th class="text-end pe-4 py-3 bg-dark">Row Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $col_totals = [];
                    foreach ($provinces as $province): 
                        $row_total = 0;
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($province) ?></td>
                        <?php foreach ($display_years as $year): 
                            $val = $matrix[$province][$year] ?? 0;
                            $row_total += $val;
                            $col_totals[$year] = ($col_totals[$year] ?? 0) + $val;
                        ?>
                        <td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>">
                            <?= $val > 0 ? number_format($val, 0) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold bg-light"><?= number_format($row_total, 0) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Unclassified/Unknown Location -->
                    <?php if (array_sum($unclassified_row) > 0): ?>
                    <tr class="table-warning bg-opacity-10 border-top">
                        <td class="ps-4 ps-4 fw-bold">
                            Unclassified / Regional / Unknown
                            <i class="fas fa-question-circle ms-1 text-muted small" title="Data where no province could be identified or linked."></i>
                        </td>
                        <?php 
                        $un_row_total = 0;
                        foreach ($display_years as $year): 
                            $val = $unclassified_row[$year] ?? 0;
                            $un_row_total += $val;
                            $col_totals[$year] = ($col_totals[$year] ?? 0) + $val;
                        ?>
                        <td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>">
                            <?= $val > 0 ? number_format($val, 0) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold"><?= number_format($un_row_total, 0) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-light border-top-2 border-dark">
                    <tr class="fw-bold text-dark align-middle">
                        <td class="ps-4 py-3">NATIONAL TOTAL</td>
                        <?php 
                        $grand_total = 0;
                        foreach ($display_years as $year): 
                            $val = $col_totals[$year] ?? 0;
                            $grand_total += $val;
                        ?>
                        <td class="text-end py-3"><?= number_format($val, 0) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 py-3 bg-dark text-white"><?= number_format($grand_total, 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
.matrix-table { font-size: 0.95rem; }
.matrix-table thead th { border: none; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem; }
.matrix-table tbody td { border-bottom: 1px solid #f2f2f2; }
.matrix-table tfoot td { font-size: 1.1rem; border-top: 2px solid #222; }
.table-hover tbody tr:hover td { background-color: #f0f7ff; }
.matrix-table .bg-light { background-color: #f9f9f9 !important; }

@media print {
    .btn, form, header, .sidebar { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; }
}
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
