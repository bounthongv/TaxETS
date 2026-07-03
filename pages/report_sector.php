<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";

$pdo = getDbConnection();
$report_filters = reportFilterInput();

// 1. Fetch All Available Years
$all_years = [];
try {
    $year_queries = [
        "SELECT DISTINCT tax_year FROM companies WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM te_individual_result WHERE tax_year > 0",
        "SELECT DISTINCT YEAR(filing_period) as yr FROM import_vat_data WHERE filing_period IS NOT NULL AND filing_period != '0000-00-00'",
        "SELECT DISTINCT YEAR(doc_date) as yr FROM asycuda_imports WHERE doc_date IS NOT NULL AND doc_date != '0000-00-00'",
        "SELECT DISTINCT tax_year FROM import_sez_data WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM import_resource_data WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM import_royalty_data WHERE tax_year > 0",
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

$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0;
$to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;

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

$display_years = [];
for ($y = $from_year; $y <= $to_year; $y++) {
    $display_years[] = $y;
}

// 2. Fetch Sectors
$sectors = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT sector FROM companies WHERE sector IS NOT NULL AND sector != '' ORDER BY sector ASC");
    $sectors = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// 3. Aggregate Data
$matrix = [];
$other_total = [];
$error = null;

try {
    // A. Profit Tax by Sector (CIT)
    $citParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT c.sector, c.tax_year, SUM(r.profit_tax_te) as te
                           FROM companies c JOIN te_profit_result r ON c.id = r.company_id
                           WHERE c.tax_year BETWEEN ? AND ?" . reportImportDateCondition(reportBatchDateExpression("c", "import_batch_id"), $report_filters, $citParams) . " GROUP BY c.sector, c.tax_year");
    $stmt->execute($citParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $matrix[$row['sector']][$row['tax_year']] = (float)$row['te'];
    }

    // B. PIT by Sector (via TIN)
    $pitParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT COALESCE(c.sector, 'Unclassified') as sec, r.tax_year, SUM(r.te_amount) as te
                           FROM te_individual_result r LEFT JOIN companies c ON r.tin COLLATE utf8mb4_unicode_ci = c.tin COLLATE utf8mb4_unicode_ci
                           WHERE r.tax_year BETWEEN ? AND ?" . reportImportDateCondition("(SELECT MAX(ipd.import_date) FROM import_pit_data ipd WHERE ipd.ptin COLLATE utf8mb4_unicode_ci = r.tin COLLATE utf8mb4_unicode_ci AND ipd.tax_year = r.tax_year)", $report_filters, $pitParams) . " GROUP BY sec, r.tax_year");
    $stmt->execute($pitParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sec = trim($row['sec']);
        $yr = (int)$row['tax_year'];
        if ($sec != 'Unclassified') {
            $matrix[$sec][$yr] = ($matrix[$sec][$yr] ?? 0) + (float)$row['te'];
        } else {
            $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // C. SEZ Developer by Sector
    $sezDevParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT COALESCE(s.sector, 'Unclassified') as sec, s.tax_year, SUM(s.te_amount) as te
                           FROM import_sez_data s WHERE s.type = 'Developer' AND s.tax_year BETWEEN ? AND ? AND s.te_amount > 0
                           " . reportImportDateCondition(reportBatchDateExpression("s", "batch_id", "import_date"), $report_filters, $sezDevParams) . " GROUP BY sec, s.tax_year");
    $stmt->execute($sezDevParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sec = trim($row['sec']);
        $yr = (int)$row['tax_year'];
        if ($sec != 'Unclassified') {
            $matrix[$sec][$yr] = ($matrix[$sec][$yr] ?? 0) + (float)$row['te'];
        } else {
            $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // E. SEZ Investor by Sector
    $sezInvParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT COALESCE(s.sector, 'Unclassified') as sec, s.tax_year, SUM(s.te_amount) as te
                           FROM import_sez_data s WHERE s.type = 'Investor' AND s.tax_year BETWEEN ? AND ? AND s.te_amount > 0
                           " . reportImportDateCondition(reportBatchDateExpression("s", "batch_id", "import_date"), $report_filters, $sezInvParams) . " GROUP BY sec, s.tax_year");
    $stmt->execute($sezInvParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sec = trim($row['sec']);
        $yr = (int)$row['tax_year'];
        if ($sec != 'Unclassified') {
            $matrix[$sec][$yr] = ($matrix[$sec][$yr] ?? 0) + (float)$row['te'];
        } else {
            $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // F. Land Concession by Sector
    $landParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT COALESCE(c.sector, 'Unclassified') as sec, c.tax_year, SUM(r.te_land_concession) as te
                           FROM te_land_concession_result r JOIN companies c ON r.company_id = c.id
                           WHERE c.tax_year BETWEEN ? AND ?" . reportImportDateCondition(reportBatchDateExpression("c", "import_batch_id"), $report_filters, $landParams) . " GROUP BY sec, c.tax_year");
    $stmt->execute($landParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sec = trim($row['sec']);
        $yr = (int)$row['tax_year'];
        if ($sec != 'Unclassified') {
            $matrix[$sec][$yr] = ($matrix[$sec][$yr] ?? 0) + (float)$row['te'];
        } else {
            $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te'];
        }
    }

    // G. Domestic VAT -> Other
    $vatParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT YEAR(filing_period) as yr, SUM(COALESCE(system_te, expert_te, 0)) as te FROM import_vat_data
                           WHERE YEAR(filing_period) BETWEEN ? AND ?" . reportImportDateCondition(reportBatchDateExpression("import_vat_data", "batch_id", "import_date"), $report_filters, $vatParams) . " GROUP BY yr");
    $stmt->execute($vatParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

    // H. ASYCUDA (Customs, Excise, Import VAT) -> Other
    $asyParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT YEAR(ai.doc_date) as yr, SUM(r.total_te) as te
                           FROM te_asycuda_result r JOIN asycuda_imports ai ON r.asycuda_id = ai.id
                           WHERE YEAR(ai.doc_date) BETWEEN ? AND ?" . reportImportDateCondition(reportBatchDateExpression("ai", "import_batch_id", "import_date"), $report_filters, $asyParams) . " GROUP BY yr");
    $stmt->execute($asyParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

    // I. Resource Fee -> Other
    $resourceParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT tax_year as yr, SUM(te_amount) as te FROM import_resource_data
                           WHERE tax_year BETWEEN ? AND ? AND te_amount > 0" . reportImportDateCondition(reportBatchDateExpression("import_resource_data", "batch_id", "import_date"), $report_filters, $resourceParams) . " GROUP BY yr");
    $stmt->execute($resourceParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

    // J. Royalty Fee -> Other
    $royaltyParams = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT tax_year as yr, SUM(te_amount) as te FROM import_royalty_data
                           WHERE tax_year BETWEEN ? AND ? AND te_amount > 0" . reportImportDateCondition(reportBatchDateExpression("import_royalty_data", "batch_id", "import_date"), $report_filters, $royaltyParams) . " GROUP BY yr");
    $stmt->execute($royaltyParams);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

// Export to Excel
if (isset($_GET['export'])) {
    require __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('TE by Sector');

    $sheet->setCellValue('A1', 'Sector Name');
    $col = 'B';
    foreach ($display_years as $year) {
        $sheet->setCellValue($col . '1', $year);
        $col++;
    }
    $sheet->setCellValue($col . '1', 'Row Total');

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ];
    $endCol = $col;
    $sheet->getStyle('A1:' . $endCol . '1')->applyFromArray($headerStyle);

    $rowIdx = 2;
    $colTotals = [];
    foreach ($sectors as $sector) {
        $sheet->setCellValue('A' . $rowIdx, $sector);
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
        $c = 'B';
        $rowTotal = 0;
        foreach ($display_years as $year) {
            $val = $matrix[$sector][$year] ?? 0;
            $rowTotal += $val;
            $colTotals[$year] = ($colTotals[$year] ?? 0) + $val;
            $sheet->setCellValue($c . $rowIdx, $val > 0 ? $val : '');
            $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $c++;
        }
        $sheet->setCellValue($c . $rowIdx, $rowTotal > 0 ? $rowTotal : '');
        $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $rowIdx++;
    }

    if (array_sum($other_total) > 0) {
        $sheet->setCellValue('A' . $rowIdx, 'Unclassified / Unknown Sector');
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowIdx)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E8F4FD'));
        $c = 'B';
        $rowTotal = 0;
        foreach ($display_years as $year) {
            $val = $other_total[$year] ?? 0;
            $rowTotal += $val;
            $colTotals[$year] = ($colTotals[$year] ?? 0) + $val;
            $sheet->setCellValue($c . $rowIdx, $val > 0 ? $val : '');
            $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $c++;
        }
        $sheet->setCellValue($c . $rowIdx, $rowTotal > 0 ? $rowTotal : '');
        $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $rowIdx++;
    }

    // Grand Total
    $sheet->setCellValue('A' . $rowIdx, 'GRAND TOTAL');
    $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
    $c = 'B';
    $grandTotal = 0;
    foreach ($display_years as $year) {
        $val = $colTotals[$year] ?? 0;
        $grandTotal += $val;
        $sheet->setCellValue($c . $rowIdx, $val > 0 ? $val : '');
        $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $c++;
    }
    $sheet->setCellValue($c . $rowIdx, $grandTotal > 0 ? $grandTotal : '');
    $sheet->getStyle('A' . $rowIdx . ':' . $c . $rowIdx)
        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('D9E2F3'));

    foreach (range('A', $c) as $colLetter) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="TE_by_Sector.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once __DIR__ . "/../includes/header.php";

// Build chart data for JS (sectors as series, years as labels)
$chartSectors = $sectors;
$chartOther = []; // We'll add "Other" as a pseudo-sector for the chart
foreach ($display_years as $year) {
    $chartOther[$year] = $other_total[$year] ?? 0;
}
$chartSectors[] = 'Unclassified / Other';
$sectorDataForChart = [];
foreach ($sectors as $sec) {
    $row = ['sector_name' => $sec];
    foreach ($display_years as $year) {
        $row[(string)$year] = (float)($matrix[$sec][$year] ?? 0);
    }
    $sectorDataForChart[] = $row;
}
// Add "Other" row
$otherRow = ['sector_name' => 'Unclassified / Other'];
foreach ($display_years as $year) {
    $otherRow[(string)$year] = (float)($other_total[$year] ?? 0);
}
$sectorDataForChart[] = $otherRow;
?>
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i> Tax Expenditure by Sector</h2>
        <p class="text-muted small mb-0">Analysis of tax incentives across different economic sectors aggregated from all tax regimes.</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="d-flex gap-2 justify-content-end flex-wrap">
            <a href="?<?= reportAppendFilters(["export" => 1, "from_year" => $from_year, "to_year" => $to_year]) ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel</a>
            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="fas fa-file-pdf me-1"></i> Export PDF</button>
        </div>
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
            <?= reportImportDateFilterControl("report_sector.php", $from_year, $to_year) ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-filter me-2"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <a href="report_sector.php" class="btn btn-light w-100 shadow-sm border-0"> Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div id="reportContent">
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
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
                            <?= $val > 0 ? number_format($val, 0) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold bg-light"><?= number_format($row_total, 0) ?></td>
                    </tr>
                    <?php endforeach; ?>

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
                            <?= $val > 0 ? number_format($val, 0) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold"><?= number_format($other_row_total, 0) ?></td>
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
                        <td class="text-end py-3"><?= number_format($val, 0) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 py-3 bg-dark text-white"><?= number_format($grand_total, 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Chart Section -->
<style>
.chart-type-btn.active { background-color: var(--bs-primary) !important; color: #fff !important; border-color: var(--bs-primary) !important; }
.pie-chart-container { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 0.25rem; }
.pie-chart-container canvas { max-height: 180px; }
</style>

<div class="card shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i> Sector TE Trend</h5>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-primary chart-type-btn active" data-type="line" title="Line Chart">
          <i class="fas fa-chart-line"></i>
        </button>
        <button type="button" class="btn btn-outline-primary chart-type-btn" data-type="bar" title="Bar Chart">
          <i class="fas fa-chart-bar"></i>
        </button>
        <button type="button" class="btn btn-outline-primary chart-type-btn" data-type="pie" title="Pie Chart">
          <i class="fas fa-chart-pie"></i>
        </button>
      </div>
    </div>

    <div id="chartContainer" style="display: block;">
      <canvas id="teSectorChart" height="120"></canvas>
    </div>

    <div id="pieChartsContainer" class="row g-2" style="display: none;"></div>
  </div>
</div>
</div> <!-- /reportContent -->

<script>
var chartData = <?= json_encode($sectorDataForChart) ?>;
var chartYears = <?= json_encode($display_years) ?>;

(function() {
    var chart = null;
    var pieCharts = [];
    var currentChartType = 'line';
    var colors = ['#3b82f6','#ef4444','#10b981','#f59e42','#a78bfa','#f472b6','#facc15','#38bdf8','#6366f1','#eab308','#14b8a6','#f43f5e','#8b5cf6','#22d3ee','#84cc16'];

    function getColor(idx) { return colors[idx % colors.length]; }

    function formatValue(v) {
        if (Math.abs(v) >= 1e12) return (v / 1e12).toFixed(1) + 'T';
        if (Math.abs(v) >= 1e9) return (v / 1e9).toFixed(1) + 'B';
        if (Math.abs(v) >= 1e6) return (v / 1e6).toFixed(1) + 'M';
        if (Math.abs(v) >= 1e3) return (v / 1e3).toFixed(1) + 'K';
        return v.toFixed(0);
    }

    function setChartType(type) {
        currentChartType = type;
        document.querySelectorAll('.chart-type-btn').forEach(function(b) {
            b.classList.toggle('active', b.getAttribute('data-type') === type);
        });
        renderChart();
    }

    window.switchChartType = function(type) {
        return new Promise(function(resolve) {
            setChartType(type);
            setTimeout(resolve, 1200);
        });
    };

    function renderChart() {
        if (chart) { chart.destroy(); chart = null; }
        pieCharts.forEach(function(c) { c.destroy(); });
        pieCharts = [];

        var filteredData = chartData.filter(function(d) {
            return chartYears.some(function(y) { return Math.abs(d[y]) > 0; });
        });
        if (filteredData.length === 0) filteredData = chartData;

        if (currentChartType === 'line') {
            document.getElementById('chartContainer').style.display = 'block';
            document.getElementById('pieChartsContainer').style.display = 'none';
            document.getElementById('pieChartsContainer').innerHTML = '';

            var datasets = filteredData.map(function(d, i) { return {
                label: d.sector_name,
                data: chartYears.map(function(y) { return d[y] || 0; }),
                borderColor: getColor(i),
                backgroundColor: getColor(i),
                fill: false,
                tension: 0.2,
                spanGaps: true
            }; });

            chart = new Chart(document.getElementById('teSectorChart'), {
                type: 'line',
                data: { labels: chartYears, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString(); } } }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Year', font: { size: 11 } }, ticks: { font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { callback: function(v) { return formatValue(v); }, font: { size: 11 } } }
                    }
                }
            });
        } else if (currentChartType === 'bar') {
            document.getElementById('chartContainer').style.display = 'block';
            document.getElementById('pieChartsContainer').style.display = 'none';
            document.getElementById('pieChartsContainer').innerHTML = '';

            var datasets = chartYears.map(function(y, i) { return {
                label: String(y),
                data: filteredData.map(function(d) { return d[y] || 0; }),
                backgroundColor: getColor(i)
            }; });

            chart = new Chart(document.getElementById('teSectorChart'), {
                type: 'bar',
                data: { labels: filteredData.map(function(d) { return d.sector_name; }), datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString(); } } }
                    },
                    scales: {
                        x: { ticks: { font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { callback: function(v) { return formatValue(v); }, font: { size: 11 } } }
                    }
                }
            });
        } else if (currentChartType === 'pie') {
            document.getElementById('chartContainer').style.display = 'none';
            document.getElementById('pieChartsContainer').style.display = 'flex';
            document.getElementById('pieChartsContainer').innerHTML = '';

            chartYears.forEach(function(year) {
                var yearData = filteredData.map(function(d) { return { label: d.sector_name, value: d[year] || 0 }; })
                    .filter(function(item) { return item.value > 0; });
                if (yearData.length === 0) return;

                var col = document.createElement('div');
                col.className = 'col-lg-4 col-md-6 col-sm-12';
                var container = document.createElement('div');
                container.className = 'pie-chart-container';
                var canvas = document.createElement('canvas');
                container.appendChild(canvas);
                col.appendChild(container);
                document.getElementById('pieChartsContainer').appendChild(col);

                var pChart = new Chart(canvas, {
                    type: 'pie',
                    data: {
                        labels: yearData.map(function(item) { return item.label; }),
                        datasets: [{ data: yearData.map(function(item) { return item.value; }), backgroundColor: yearData.map(function(_, idx) { return getColor(idx); }) }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'right', labels: { boxWidth: 8, padding: 4, font: { size: 9 } } },
                            title: { display: true, text: 'Year ' + year, font: { size: 11 }, padding: { top: 4, bottom: 2 } },
                            tooltip: { callbacks: { label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var pct = ((ctx.raw / total) * 100).toFixed(1);
                                return ctx.label + ': ' + Number(ctx.raw).toLocaleString() + ' (' + pct + '%)';
                            } } }
                        }
                    }
                });
                pieCharts.push(pChart);
            });
        }
    }

    document.querySelectorAll('.chart-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setChartType(this.getAttribute('data-type'));
        });
    });

    document.addEventListener('DOMContentLoaded', renderChart);
})();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.getElementById('exportPdfBtn')?.addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating PDF...';
    var margin = 10;

    function captureCanvas(cvs, maxW, maxH) {
        var ctx = cvs.getContext('2d');
        if (ctx) {
            ctx.save();
            ctx.globalCompositeOperation = 'destination-over';
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, cvs.width, cvs.height);
            ctx.restore();
        }
        var ar = cvs.width / cvs.height;
        var w = maxW;
        var h = maxW / ar;
        if (h > maxH) { h = maxH; w = h * ar; }
        return { data: cvs.toDataURL('image/png'), w: w, h: h };
    }

    var tableCard = document.querySelector('#reportContent > .card');
    if (!tableCard) { alert('Report content not found.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-file-pdf me-1"></i> Export PDF'; return; }

    html2canvas(tableCard, { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }).then(function(tableCanvas) {
        var pdf = new jspdf.jsPDF('l', 'mm', 'a3');
        var pw = pdf.internal.pageSize.getWidth();
        var ph = pdf.internal.pageSize.getHeight();

        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Tax Expenditure by Sector', pw / 2, margin + 6, { align: 'center' });

        var tw = pw - margin * 2;
        var th = (tableCanvas.height / tableCanvas.width) * tw;
        if (th > ph - 30) th = ph - 30;
        pdf.addImage(tableCanvas.toDataURL('image/png'), 'PNG', margin, 22, tw, th);

        var chartConfigs = [
            { type: 'line', title: 'Sector TE Trend (Line Chart)', pageTitle: true },
            { type: 'bar', title: 'Sector TE by Year (Bar Chart)', pageTitle: true },
            { type: 'pie', title: 'Sector TE Distribution by Year', pageTitle: true }
        ];

        function captureChart(idx) {
            if (idx >= chartConfigs.length) {
                pdf.save('TE_by_Sector.pdf');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf me-1"></i> Export PDF';
                return;
            }

            var cfg = chartConfigs[idx];
            window.switchChartType(cfg.type).then(function() {
                setTimeout(function() {
                    pdf.addPage();
                    var y = margin;
                    pdf.setFontSize(14);
                    pdf.setFont('helvetica', 'bold');
                    pdf.text(cfg.title, pw / 2, y + 6, { align: 'center' });
                    y += 14;

                    if (cfg.type === 'pie') {
                        var pies = document.querySelectorAll('#pieChartsContainer canvas');
                        if (pies.length === 0) { captureChart(idx + 1); return; }
                        var itemsPerRow = 3;
                        var cw = (pw - margin * (itemsPerRow + 1)) / itemsPerRow;
                        var col = 0;
                        var maxRowH = 0;
                        pies.forEach(function(cvs) {
                            if (col === 0 && maxRowH > 0) { y += maxRowH + 6; maxRowH = 0; }
                            if (y + 70 > ph) { pdf.addPage(); y = margin + 10; }
                            var cp = captureCanvas(cvs, cw, 90);
                            pdf.addImage(cp.data, 'PNG', margin + col * (cw + margin), y, cp.w, cp.h);
                            maxRowH = Math.max(maxRowH, cp.h);
                            col++;
                            if (col >= itemsPerRow) { col = 0; y += maxRowH + 6; maxRowH = 0; }
                        });
                    } else {
                        var mainCvs = document.getElementById('teSectorChart');
                        if (mainCvs) {
                            var cp = captureCanvas(mainCvs, pw - margin * 2, 140);
                            var cx = (pw - cp.w) / 2;
                            pdf.addImage(cp.data, 'PNG', cx, y, cp.w, cp.h);
                        }
                    }
                    captureChart(idx + 1);
                }, 200);
            });
        }
        captureChart(0);
    }).catch(function(err) {
        console.error('PDF failed:', err);
        alert('Failed to generate PDF. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-file-pdf me-1"></i> Export PDF';
    });
});
</script>

<style>
.matrix-table { font-size: 0.95rem; }
.matrix-table thead th { border: none; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem; }
.matrix-table tbody td { border-bottom: 1px solid #f0f0f0; }
.matrix-table tfoot td { font-size: 1rem; border-top: 2px solid #333; }
.table-hover tbody tr:hover td { background-color: #f8faff; }
.matrix-table .bg-light { background-color: #fcfcfc !important; }
@media print { .btn, form, header, .sidebar { display: none !important; } .card { box-shadow: none !important; border: 1px solid #ddd !important; } }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
