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

// 2. Get Year Filters
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

// 3. Fetch Sectors
$sectors = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT sector FROM companies WHERE sector IS NOT NULL AND sector != '' ORDER BY sector ASC");
    $sectors = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// 4. Aggregate Data Matrix
$matrix = []; // [Sector][Year] => Total
$other_total = []; // [Year] => Total (PIT + VAT + ASYCUDA)

try {
    // A. Profit Tax by Sector
    $stmt = $pdo->prepare("SELECT c.sector, c.tax_year, SUM(r.profit_tax_te) as te 
                           FROM companies c 
                           JOIN te_profit_result r ON c.id = r.company_id 
                           WHERE c.tax_year BETWEEN ? AND ? 
                           GROUP BY c.sector, c.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $matrix[$row['sector']][$row['tax_year']] = (float)$row['te'];
    }

    // B. Individual Tax (PIT) -> Attempt mapping via TIN to Companies for Sector
    $stmt = $pdo->prepare("SELECT COALESCE(c.sector, 'Unclassified') as sec, r.tax_year, SUM(r.te_amount) as te 
                           FROM te_individual_result r 
                           LEFT JOIN companies c ON r.tin = c.tin 
                           WHERE r.tax_year BETWEEN ? AND ? 
                           GROUP BY sec, r.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sec = trim($row['sec']);
        $yr = (int)$row['tax_year'];
        if ($sec != 'Unclassified') {
            $matrix[$sec][$yr] = ($matrix[$sec][$yr] ?? 0) + (float)$row['te'];
        } else {
            $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // C. Domestic VAT -> "Other"
    $stmt = $pdo->prepare("SELECT YEAR(filing_period) as yr, SUM(expert_te) as te FROM import_vat_data WHERE YEAR(filing_period) BETWEEN ? AND ? GROUP BY yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

    // D. ASYCUDA (Customs, Excise, Import VAT) -> "Other"
    $stmt = $pdo->prepare("SELECT YEAR(ai.doc_date) as yr, SUM(r.total_te) as te 
                           FROM te_asycuda_result r 
                           JOIN asycuda_imports ai ON r.asycuda_id = ai.id 
                           WHERE YEAR(ai.doc_date) BETWEEN ? AND ? 
                           GROUP BY yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i> Tax Expenditure by Sector</h2>
        <p class="text-muted small mb-0">Analysis of tax incentives across different economic sectors. Sectors are currently mapped from Profit Tax (CIT) data.</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-outline-success shadow-sm" onclick="window.print()"><i class="fas fa-print me-2"></i> Print / PDF</button>
        <button class="btn btn-success shadow-sm ms-2"><i class="fas fa-file-excel me-2"></i> Export to Excel</button>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">From Year</label>
                <select name="from_year" class="form-select border-0 bg-light">
                    <?php foreach ($all_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $from_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">To Year</label>
                <select name="to_year" class="form-select border-0 bg-light">
                    <?php foreach ($all_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $to_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-filter me-2"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <a href="report_sector.php" class="btn btn-light w-100 shadow-sm border-0"> Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 matrix-table">
                <thead class="bg-primary text-white">
                    <tr class="align-middle">
                        <th class="ps-4 py-3" style="min-width: 250px;">Sector Name</th>
                        <?php foreach ($display_years as $year): ?>
                        <th class="text-end py-3"><?= $year ?></th>
                        <?php endforeach; ?>
                        <th class="text-end pe-4 py-3 bg-dark">Row Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $column_totals = [];
                    foreach ($sectors as $sector): 
                        $row_total = 0;
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($sector) ?></td>
                        <?php foreach ($display_years as $year): 
                            $val = $matrix[$sector][$year] ?? 0;
                            $row_total += $val;
                            $column_totals[$year] = ($column_totals[$year] ?? 0) + $val;
                        ?>
                        <td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>">
                            <?= $val > 0 ? number_format($val, 2) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold bg-light"><?= number_format($row_total, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Unclassified/Unknown Sector Row -->
                    <?php if (array_sum($other_total) > 0): ?>
                    <tr class="table-info bg-opacity-10 border-top">
                        <td class="ps-4 fw-bold">Unclassified / Unknown Sector</td>
                        <?php 
                        $other_row_total = 0;
                        foreach ($display_years as $year): 
                            $val = $other_total[$year] ?? 0;
                            $other_row_total += $val;
                            $column_totals[$year] = ($column_totals[$year] ?? 0) + $val;
                        ?>
                        <td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>">
                            <?= $val > 0 ? number_format($val, 2) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold"><?= number_format($other_row_total, 2) ?></td>
                    </tr>
                    <?php endif; ?>

                </tbody>
                <tfoot class="bg-light border-top-2 border-dark">
                    <tr class="fw-bold text-dark align-middle">
                        <td class="ps-4 py-3">GRAND TOTAL</td>
                        <?php 
                        $grand_total = 0;
                        foreach ($display_years as $year): 
                            $val = $column_totals[$year] ?? 0;
                            $grand_total += $val;
                        ?>
                        <td class="text-end py-3"><?= number_format($val, 2) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 py-3 bg-dark text-white"><?= number_format($grand_total, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
.matrix-table { font-size: 0.95rem; }
.matrix-table thead th { border: none; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem; }
.matrix-table tbody td { border-bottom: 1px solid #f0f0f0; }
.matrix-table tfoot td { font-size: 1rem; border-top: 2px solid #333; }
.table-hover tbody tr:hover td { background-color: #f8faff; }
.matrix-table .bg-light { background-color: #fcfcfc !important; }

@media print {
    .btn, form, header, .sidebar { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
