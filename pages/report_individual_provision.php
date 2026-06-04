<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";

$pdo = getDbConnection();
$errors = [];
$report_filters = reportFilterInput();

// 1. Fetch All Available Years
$all_years = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT tax_year FROM te_individual_result WHERE tax_year > 0");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if ($row[0] > 1900 && $row[0] < 2100) $all_years[] = (int)$row[0];
    }
    $all_years = array_unique($all_years);
    sort($all_years);
} catch (Exception $e) { $errors[] = $e->getMessage(); }

$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0;
$to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;
if (!$from_year || !$to_year) {
    if (!empty($all_years)) {
        $from_year = min($all_years);
        $to_year = max($all_years);
    } else {
        $from_year = (int)date('Y') - 4;
        $to_year = (int)date('Y');
    }
}

$display_years = [];
for ($y = $from_year; $y <= $to_year; $y++) { $display_years[] = $y; }

// 2. Fetch all provisions
$provisions = [];
try {
    $stmt = $pdo->query("SELECT id, provision_number, legal_basis, description, type_of_te, purpose
                         FROM individual_provisions ORDER BY CAST(provision_number AS UNSIGNED) ASC, provision_number ASC");
    $provisions = $stmt->fetchAll();
} catch (Exception $e) { $errors[] = $e->getMessage(); }

$provLabel = [];
$provDesc = [];
foreach ($provisions as $p) {
    $num = $p['provision_number'];
    $short = "Prov #{$num}";
    if (!empty($p['legal_basis'])) $short .= " \u{2013} " . $p['legal_basis'];
    $provLabel[$num] = $short;
    $provDesc[$num] = $p['description'];
}

// 3. Aggregate data
$matrix = [];
$other_te = [];
$grand_total_te = [];

try {
    // A. Provision-matched TE
    $pitDate = "(SELECT MAX(ipd.import_date) FROM import_pit_data ipd WHERE ipd.ptin COLLATE utf8mb4_unicode_ci = r.tin COLLATE utf8mb4_unicode_ci AND ipd.tax_year = r.tax_year)";
    $params = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT p.provision_number, r.tax_year, SUM(r.te_amount) as te
                           FROM te_individual_result r
                           JOIN individual_provisions p ON FIND_IN_SET(p.provision_number COLLATE utf8mb4_general_ci, REPLACE(r.matched_provisions, ', ', ','))
                           WHERE r.tax_year BETWEEN ? AND ? AND r.te_amount > 0
                             AND r.matched_provisions IS NOT NULL AND r.matched_provisions != ''
                             " . reportImportDateCondition($pitDate, $report_filters, $params) . "
                           GROUP BY p.provision_number, r.tax_year");
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $num = $row['provision_number'];
        $yr = (int)$row['tax_year'];
        $matrix[$num][$yr] = (float)$row['te'];
    }

    // B. Unclassified TE
    $params = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT r.tax_year, SUM(r.te_amount) as te
                           FROM te_individual_result r
                           WHERE r.tax_year BETWEEN ? AND ? AND r.te_amount > 0
                             AND (r.matched_provisions IS NULL OR r.matched_provisions = '')
                             " . reportImportDateCondition($pitDate, $report_filters, $params) . "
                           GROUP BY r.tax_year");
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_te[(int)$row['tax_year']] = (float)$row['te'];
    }

    // C. Grand total (unique)
    $params = [$from_year, $to_year];
    $stmt = $pdo->prepare("SELECT r.tax_year, SUM(r.te_amount) as te
                           FROM te_individual_result r
                           WHERE r.tax_year BETWEEN ? AND ? AND r.te_amount > 0
                           " . reportImportDateCondition($pitDate, $report_filters, $params) . "
                           GROUP BY r.tax_year");
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grand_total_te[(int)$row['tax_year']] = (float)$row['te'];
    }
} catch (Exception $e) { $errors[] = $e->getMessage(); }

// 4. Chart data
$chartData = [];
$allProvNums = array_keys($matrix);
usort($allProvNums, function($a, $b) {
    $aNum = (int)$a; $bNum = (int)$b;
    if ($aNum !== $bNum) return $aNum - $bNum;
    return strcasecmp($a, $b);
});
foreach ($allProvNums as $num) {
    $item = ['provision_number' => $num, 'label' => $provLabel[$num] ?? "Prov #{$num}"];
    foreach ($display_years as $y) {
        $item[(string)$y] = (float)($matrix[$num][$y] ?? 0);
    }
    $chartData[] = $item;
}
$otherItem = ['provision_number' => 'Unclassified', 'label' => 'Unclassified / Other'];
$otherHasData = false;
foreach ($display_years as $y) {
    $v = (float)($other_te[$y] ?? 0);
    $otherItem[(string)$y] = $v;
    if ($v > 0) $otherHasData = true;
}
if ($otherHasData) { $chartData[] = $otherItem; }

// 5. Excel export
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("TE by Provision");

    $sheet->mergeCells('A1:' . chr(65 + count($display_years)) . '1');
    $sheet->setCellValue('A1', "Individual Income Tax TE by Provision ({$from_year} - {$to_year})");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $headerRow = 3;
    $sheet->setCellValue("A{$headerRow}", 'Provision');
    $colIdx = 2;
    foreach ($display_years as $y) {
        $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, (string)$y);
        $colIdx++;
    }
    $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, 'Row Total');
    $sheet->getStyle("A{$headerRow}:" . $sheet->getCellByColumnAndRow($colIdx, $headerRow)->getColumn() . "{$headerRow}")->getFont()->setBold(true);

    $rowIdx = 4;
    $colTotals = [];
    foreach ($matrix as $num => $yearData) {
        $label = $provLabel[$num] ?? "Prov #{$num}";
        $sheet->setCellValue("A{$rowIdx}", $label);
        $cIdx = 2;
        $rowTotal = 0;
        foreach ($display_years as $y) {
            $val = $yearData[$y] ?? 0;
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
            $rowTotal += $val;
            $colTotals[$y] = ($colTotals[$y] ?? 0) + $val;
            $cIdx++;
        }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $rowTotal);
        $rowIdx++;
    }

    if (array_sum($other_te) > 0) {
        $sheet->setCellValue("A{$rowIdx}", 'Unclassified / Other');
        $cIdx = 2;
        $unRowTotal = 0;
        foreach ($display_years as $y) {
            $val = $other_te[$y] ?? 0;
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
            $unRowTotal += $val;
            $colTotals[$y] = ($colTotals[$y] ?? 0) + $val;
            $cIdx++;
        }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $unRowTotal);
        $rowIdx++;
    }

    $sheet->setCellValue("A{$rowIdx}", 'TOTAL (all individuals)');
    $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
    $cIdx = 2;
    $grandTotal = 0;
    foreach ($display_years as $y) {
        $val = $grand_total_te[$y] ?? 0;
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
        $grandTotal += $val;
        $cIdx++;
    }
    $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $grandTotal);
    $sheet->getStyle($sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn() . $rowIdx)->getFont()->setBold(true);

    foreach (range('A', $sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="TE_Individual_by_Provision_' . $from_year . '-' . $to_year . '.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold text-dark"><i class="fas fa-users me-2 text-primary"></i> Individual Income Tax TE by Provision</h2>
        <p class="text-muted">Tax Expenditure from Personal Income Tax, classified by the legal provision that generated the benefit.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="?<?= reportAppendFilters(["export" => 1, "from_year" => $from_year, "to_year" => $to_year]) ?>" class="btn btn-success shadow-sm"><i class="fas fa-file-excel me-2"></i> Export Excel</a>
        <button id="exportPdfBtn" class="btn btn-danger shadow-sm"><i class="fas fa-file-pdf me-2"></i> Export PDF</button>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger shadow-sm"><?= htmlspecialchars(implode('; ', $errors)) ?></div>
<?php endif; ?>

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
            <?= reportImportDateFilterControl("report_individual_provision.php", $from_year, $to_year) ?>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-2"></i> Update</button>
            </div>
            <div class="col-md-3">
                <a href="report_individual_provision.php" class="btn btn-outline-secondary w-100 border-0">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-light small text-white-50 text-uppercase">Total TE</span>
                    <i class="fas fa-money-bill-wave fa-2x text-white-50"></i>
                </div>
                <span class="fw-bold fs-4"><?= number_format(array_sum($grand_total_te), 0) ?></span>
                <small class="text-white-50 mt-1"><?= $from_year ?>&ndash;<?= $to_year ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-light small text-white-50 text-uppercase">Provisions Active</span>
                    <i class="fas fa-gavel fa-2x text-white-50"></i>
                </div>
                <span class="fw-bold fs-4"><?= count($provisions) ?></span>
                <small class="text-white-50 mt-1">In <?= $from_year ?>&ndash;<?= $to_year ?> range</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="card-body text-white d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-light small text-white-50 text-uppercase">Provisions with TE</span>
                    <i class="fas fa-check-circle fa-2x text-white-50"></i>
                </div>
                <span class="fw-bold fs-4"><?= count($matrix) ?></span>
                <small class="text-white-50 mt-1">With matched TE data</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="card-body text-white d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-light small text-white-50 text-uppercase">Unclassified</span>
                    <i class="fas fa-folder-open fa-2x text-white-50"></i>
                </div>
                <span class="fw-bold fs-4"><?= number_format(array_sum($other_te), 0) ?></span>
                <small class="text-white-50 mt-1">No provision matched</small>
            </div>
        </div>
    </div>
</div>

<div id="reportContent" class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 matrix-table">
                <thead class="bg-primary text-white">
                    <tr class="align-middle">
                        <th class="ps-4 py-3" style="min-width: 240px;">Provision</th>
                        <?php foreach ($display_years as $year): ?>
                        <th class="text-end py-3"><?= $year ?></th>
                        <?php endforeach; ?>
                        <th class="text-end pe-4 py-3 bg-dark">Row Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $col_totals = [];
                    foreach ($allProvNums as $num):
                        $yearData = $matrix[$num] ?? [];
                        $rowTotal = 0;
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary" title="<?= htmlspecialchars($provDesc[$num] ?? '') ?>">
                            <?= htmlspecialchars($provLabel[$num] ?? "Prov #{$num}") ?>
                        </td>
                        <?php foreach ($display_years as $year):
                            $val = $yearData[$year] ?? 0;
                            $rowTotal += $val;
                            $col_totals[$year] = ($col_totals[$year] ?? 0) + $val;
                        ?>
                        <td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>">
                            <?= $val > 0 ? number_format($val, 0) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold bg-light"><?= $rowTotal > 0 ? number_format($rowTotal, 0) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (array_sum($other_te) > 0): ?>
                    <tr class="table-warning bg-opacity-10 border-top">
                        <td class="ps-4 fw-bold text-muted">
                            <i class="fas fa-question-circle me-1 text-warning"></i> Unclassified / Other
                            <span class="text-muted small fw-normal ms-2">(no provision matched)</span>
                        </td>
                        <?php $unRowTotal = 0; ?>
                        <?php foreach ($display_years as $year):
                            $val = $other_te[$year] ?? 0;
                            $unRowTotal += $val;
                            $col_totals[$year] = ($col_totals[$year] ?? 0) + $val;
                        ?>
                        <td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>">
                            <?= $val > 0 ? number_format($val, 0) : '-' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 fw-bold"><?= number_format($unRowTotal, 0) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-light border-top-2 border-dark">
                    <tr class="fw-bold text-dark align-middle">
                        <td class="ps-4 py-3">GRAND TOTAL <span class="text-muted small fw-normal">(unique, all individuals)</span></td>
                        <?php
                        $grandTotal = 0;
                        foreach ($display_years as $year):
                            $val = $grand_total_te[$year] ?? 0;
                            $grandTotal += $val;
                        ?>
                        <td class="text-end py-3"><?= number_format($val, 0) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end pe-4 py-3 bg-dark text-white"><?= number_format($grandTotal, 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="px-4 py-2 bg-light border-top small text-muted">
            <i class="fas fa-info-circle me-1"></i> An individual may match multiple provisions. The provision rows show the full TE for every individual matching that provision, so the sum of provision rows may exceed the Grand Total.
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 mt-4" style="border-radius: 12px;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> TE by Provision (<?= $from_year ?>&ndash;<?= $to_year ?>)</h5>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary chart-type-btn active" data-type="bar" onclick="switchChartType('bar')"><i class="fas fa-chart-bar"></i> Bar</button>
                <button class="btn btn-outline-primary chart-type-btn" data-type="line" onclick="switchChartType('line')"><i class="fas fa-chart-line"></i> Line</button>
                <button class="btn btn-outline-primary chart-type-btn" data-type="pie" onclick="switchChartType('pie')"><i class="fas fa-chart-pie"></i> Pie</button>
            </div>
        </div>
        <div id="chartContainer" style="width:100%; height:360px;">
            <canvas id="teProvisionChart"></canvas>
        </div>
        <div id="pieChartsContainer" class="row g-3" style="display:none;"></div>
    </div>
</div>

<style>
.matrix-table { font-size: 0.95rem; }
.matrix-table thead th { border: none; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem; }
.matrix-table tbody td { border-bottom: 1px solid #f2f2f2; }
.matrix-table tfoot td { font-size: 1.1rem; border-top: 2px solid #222; }
.table-hover tbody tr:hover td { background-color: #f0f7ff; }
.matrix-table .bg-light { background-color: #f9f9f9 !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var chartData = <?= json_encode($chartData) ?>;
var chartYears = <?= json_encode($display_years) ?>;
var chart = null;
var pieCharts = [];
var currentChartType = 'bar';

var COLORS = [
    '#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc948','#b07aa1','#ff9da7',
    '#9c755f','#bab0ac','#6b6ecf','#d4a6c8','#a1c9f4','#ffb482','#8cd17d','#f1ce63',
    '#499894','#e377c2','#b5b5b5','#7f7f7f','#c5b0d5','#ffbb78','#98df8a','#ff9898'
];
function getColor(i) { return COLORS[i % COLORS.length]; }
function formatValue(v) {
    if (v >= 1e12) return (v / 1e12).toFixed(1) + 'T';
    if (v >= 1e9) return (v / 1e9).toFixed(1) + 'B';
    if (v >= 1e6) return (v / 1e6).toFixed(1) + 'M';
    if (v >= 1e3) return (v / 1e3).toFixed(1) + 'K';
    return v.toFixed(0);
}
function setChartType(type) {
    currentChartType = type;
    document.querySelectorAll('.chart-type-btn').forEach(function(b) {
        b.classList.toggle('active', b.getAttribute('data-type') === type);
    });
    renderChart();
}
function switchChartType(type) {
    return new Promise(function(resolve) {
        setChartType(type);
        setTimeout(resolve, 1200);
    });
}
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
            label: d.label,
            data: chartYears.map(function(y) { return d[y] || 0; }),
            borderColor: getColor(i), backgroundColor: getColor(i), fill: false, tension: 0.2, spanGaps: true
        }; });
        chart = new Chart(document.getElementById('teProvisionChart'), {
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
        chart = new Chart(document.getElementById('teProvisionChart'), {
            type: 'bar',
            data: { labels: filteredData.map(function(d) { return d.label; }), datasets: datasets },
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
            var yearData = filteredData.map(function(d) { return { label: d.label, value: d[year] || 0 }; })
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
                data: { labels: yearData.map(function(item) { return item.label; }), datasets: [{ data: yearData.map(function(item) { return item.value; }), backgroundColor: yearData.map(function(_, idx) { return getColor(idx); }) }] },
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
renderChart();

document.getElementById('exportPdfBtn')?.addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';
    var script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
    script.onload = function() {
        var script2 = document.createElement('script');
        script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        script2.onload = function() {
            var { jsPDF } = window.jspdf;
            var pdf = new jsPDF('l', 'mm', 'a4');
            var pageWidth = pdf.internal.pageSize.getWidth();
            switchChartType('bar').then(function() {
                setTimeout(function() {
                    html2canvas(document.getElementById('reportContent'), { scale: 2, backgroundColor: '#ffffff' })
                    .then(function(tableCanvas) {
                        var tableImgData = tableCanvas.toDataURL('image/png');
                        var tableImgProps = pdf.getImageProperties(tableImgData);
                        var tableWidth = pageWidth - 20;
                        var tableHeight = (tableImgProps.height * tableWidth) / tableImgProps.width;
                        pdf.addImage(tableImgData, 'PNG', 10, 10, tableWidth, tableHeight);
                        var chartCanvas = document.querySelector('#chartContainer canvas');
                        if (chartCanvas) {
                            setTimeout(function() {
                                var mapImgData = chartCanvas.toDataURL('image/png');
                                pdf.addPage();
                                var mapImgProps = pdf.getImageProperties(mapImgData);
                                var mapWidth = pageWidth - 20;
                                var mapHeight = (mapImgProps.height * mapWidth) / mapImgProps.width;
                                pdf.addImage(mapImgData, 'PNG', 10, 10, mapWidth, mapHeight);
                                pdf.save('TE_Individual_by_Provision_<?= $from_year ?>-<?= $to_year ?>.pdf');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i> Export PDF';
                            }, 800);
                        } else {
                            pdf.save('TE_Individual_by_Provision_<?= $from_year ?>-<?= $to_year ?>.pdf');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i> Export PDF';
                        }
                    });
                }, 500);
            });
        };
        document.head.appendChild(script2);
    };
    document.head.appendChild(script);
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
