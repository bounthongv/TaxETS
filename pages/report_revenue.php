<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$report_filters = reportFilterInput();

// ===================================================================
// 1. Fetch Revenue data (kip, no billion conversion)
// ===================================================================
$revenue_data = [];
$rev_stmt = $pdo->query("SELECT gdp_year, revenue_value FROM repo_gdp_revenue ORDER BY gdp_year");
while ($row = $rev_stmt->fetch(PDO::FETCH_ASSOC)) {
    // revenue_value stored in kip (billions); multiply by 1,000 for trillions
    $revenue_data[(int)$row['gdp_year']] = (float)$row['revenue_value'] * 1_000;
}
$revenue_years = array_keys($revenue_data);

// ===================================================================
// 2. Fetch TE data by tax type
// ===================================================================
$profit_data = [];
$pit_data = [];
$vat_domestic_data = [];
$customs_data = [];
$excise_data = [];
$vat_import_data = [];
$sez_dev_data = [];
$sez_inv_data = [];
$resource_data = [];
$royalty_data = [];
$land_concession_data = [];

try {
    $reportData = reportTaxTypeData($pdo, $report_filters);
    $profit_data = $reportData["profit"];
    $pit_data = $reportData["pit"];
    $vat_domestic_data = $reportData["vat_domestic"];
    $customs_data = $reportData["customs"];
    $excise_data = $reportData["excise"];
    $vat_import_data = $reportData["vat_import"];
    $sez_dev_data = $reportData["sez_dev"];
    $sez_inv_data = $reportData["sez_inv"];
    $resource_data = $reportData["resource"];
    $royalty_data = $reportData["royalty"];
    $land_concession_data = $reportData["land"];
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Determine available year range
$data_years = array_unique(array_merge(
    $revenue_years,
    array_keys($profit_data), array_keys($pit_data),
    array_keys($vat_domestic_data), array_keys($customs_data), array_keys($excise_data),
    array_keys($vat_import_data), array_keys($sez_dev_data), array_keys($sez_inv_data),
    array_keys($resource_data), array_keys($royalty_data), array_keys($land_concession_data)
));
sort($data_years);

$display_years = [];
foreach ($data_years as $y) {
    if ($y > 1900 && $y < 2100) $display_years[] = $y;
}
$display_years = array_values(array_unique($display_years));
$all_years = $display_years;
$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0;
$to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;
if (!$from_year || !$to_year) {
    if (!empty($all_years)) {
        $from_year = (int)min($all_years);
        $to_year = (int)max($all_years);
    } else {
        $from_year = (int)date("Y");
        $to_year = (int)date("Y");
    }
}
if ($from_year > $to_year) {
    [$from_year, $to_year] = [$to_year, $from_year];
}
$display_years = array_values(array_filter($display_years, function($y) use ($from_year, $to_year) {
    return (int)$y >= $from_year && (int)$y <= $to_year;
}));
if (empty($display_years)) { $display_years = range($from_year, $to_year); }
if (empty($all_years)) { $all_years = $display_years; }

$tax_types = [
    'Corporate Income Tax (Profit Tax)' => $profit_data,
    'Individual Income Tax (PIT)'       => $pit_data,
    'Domestic Value Added Tax (VAT)'    => $vat_domestic_data,
    'Customs Duty (Import)'             => $customs_data,
    'Excise Tax (Import)'               => $excise_data,
    'Import Value Added Tax (VAT)'      => $vat_import_data,
    'SEZ Developer'                     => $sez_dev_data,
    'SEZ Investor'                      => $sez_inv_data,
    'Resource Fee (Non-Tax)'            => $resource_data,
    'Royalty Fee (Non-Tax)'             => $royalty_data,
    'Land Concession (Non-Tax)'         => $land_concession_data,
];

// ===================================================================
// 3. Build chart data
// ===================================================================
$chartData = [];
foreach ($tax_types as $label => $data) {
    $entry = ['tax_type' => $label];
    foreach ($display_years as $y) {
        $te = (float)($data[$y] ?? 0);
        $rev = (float)($revenue_data[$y] ?? 0);
        $entry[(string)$y] = $rev > 0 ? round(($te / $rev) * 100, 4) : 0;
    }
    $chartData[] = $entry;
}

// ===================================================================
// 4. Excel export
// ===================================================================
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('TE % of Revenue');

    $sheet->setCellValue('A1', 'Tax Type Category');
    $col = 'B';
    foreach ($display_years as $year) {
        $sheet->setCellValue($col . '1', $year . ' (%)');
        $col++;
    }

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ];
    $sheet->getStyle('A1:' . $col . '1')->applyFromArray($headerStyle);

    $rowIdx = 2;
    foreach ($tax_types as $type => $data) {
        $sheet->setCellValue('A' . $rowIdx, $type);
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
        $c = 'B';
        foreach ($display_years as $year) {
            $te = (float)($data[$year] ?? 0);
            $rev = (float)($revenue_data[$year] ?? 0);
            $pct = $rev > 0 ? round(($te / $rev) * 100, 4) : 0;
            $sheet->setCellValue($c . $rowIdx, $pct > 0 ? $pct : '');
            $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('0.0000');
            $c++;
        }
        $rowIdx++;
    }

    $rowIdx++;
    $sheet->setCellValue('A' . $rowIdx, 'Total Revenue (Kip)');
    $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
    $c = 'B';
    foreach ($display_years as $year) {
        $sheet->setCellValue($c . $rowIdx, $revenue_data[$year] ?? '');
        $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $c++;
    }

    $rowIdx++;
    $sheet->setCellValue('A' . $rowIdx, 'Total TE (% of Revenue)');
    $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
    $c = 'B';
    $lastCol = chr(65 + count($display_years));
    foreach ($display_years as $year) {
        $total_te = 0;
        foreach ($tax_types as $d) { $total_te += (float)($d[$year] ?? 0); }
        $rev = (float)($revenue_data[$year] ?? 0);
        $pct = $rev > 0 ? round(($total_te / $rev) * 100, 4) : 0;
        $sheet->setCellValue($c . $rowIdx, $pct > 0 ? $pct : '');
        $sheet->getStyle($c . $rowIdx)->getNumberFormat()->setFormatCode('0.0000');
        $c++;
    }
    $sheet->getStyle('A' . $rowIdx . ':' . $lastCol . $rowIdx)
        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('D9E2F3'));

    foreach (range('A', $lastCol) as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="TE_percent_of_Revenue.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-coins me-2 text-primary"></i> Tax Expenditure as % of Revenue</h2>
      <p class="text-muted">Revenue foregone from each tax type expressed as a percentage of total tax revenue.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="?<?= reportAppendFilters(["export" => 1, "from_year" => $from_year, "to_year" => $to_year]) ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel</a>
      <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="fas fa-file-pdf me-1"></i> Export PDF</button>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <form method="GET" class="row align-items-end g-3">
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted text-uppercase">From Year</label>
        <select name="from_year" class="form-select border-0 bg-light">
          <?php foreach ($all_years as $yearOption): ?>
            <option value="<?= htmlspecialchars($yearOption) ?>" <?= (int)$yearOption === (int)$from_year ? 'selected' : '' ?>><?= htmlspecialchars($yearOption) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted text-uppercase">To Year</label>
        <select name="to_year" class="form-select border-0 bg-light">
          <?php foreach ($all_years as $yearOption): ?>
            <option value="<?= htmlspecialchars($yearOption) ?>" <?= (int)$yearOption === (int)$to_year ? 'selected' : '' ?>><?= htmlspecialchars($yearOption) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?= reportImportDateFilterControl("report_revenue.php") ?>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-filter me-2"></i> Filter</button>
      </div>
      <div class="col-md-2">
        <a href="report_revenue.php" class="btn btn-light w-100 shadow-sm border-0">Reset</a>
      </div>
    </form>
  </div>
</div>

<div id="reportContent">
<div class="card shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="table-light small text-uppercase fw-bold">
          <tr>
            <th class="ps-4" style="min-width: 480px; white-space: nowrap;">Tax Type Category</th>
            <?php foreach ($display_years as $year): ?>
              <th class="text-end pe-4"><?= htmlspecialchars($year) ?> (%)</th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tax_types as $type => $data): ?>
            <?php
            $has_data = false;
            foreach ($display_years as $year) {
                $te = (float)($data[$year] ?? 0);
                $rev = (float)($revenue_data[$year] ?? 0);
                $pct = $rev > 0 ? ($te / $rev) * 100 : 0;
                if ($pct != 0) { $has_data = true; break; }
            }
            if (!$has_data) continue;
            ?>
          <tr>
            <td class="ps-4 fw-bold text-dark" style="white-space: nowrap;"><?= htmlspecialchars($type) ?></td>
            <?php foreach ($display_years as $year):
                $te = (float)($data[$year] ?? 0);
                $rev = (float)($revenue_data[$year] ?? 0);
                $pct = $rev > 0 ? ($te / $rev) * 100 : 0;
            ?>
              <td class="text-end pe-4">
                <?= $pct != 0 ? '<strong>' . number_format($pct, 4) . '%</strong>' : '<span class="text-muted opacity-50">-</span>' ?>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="table-info fw-bold">
            <td class="ps-4" style="white-space: nowrap;">Total Revenue (Kip)</td>
            <?php foreach ($display_years as $year): ?>
              <td class="text-end pe-4"><?= ($revenue_data[$year] ?? 0) > 0 ? number_format($revenue_data[$year], 0) : '-' ?></td>
            <?php endforeach; ?>
          </tr>
          <tr class="table-primary fw-bold">
            <td class="ps-4">Total TE (% of Revenue)</td>
            <?php foreach ($display_years as $year):
                $total_te = 0;
                foreach ($tax_types as $d) { $total_te += (float)($d[$year] ?? 0); }
                $rev = (float)($revenue_data[$year] ?? 0);
                $pct = $rev > 0 ? ($total_te / $rev) * 100 : 0;
            ?>
              <td class="text-end pe-4"><?= $pct > 0 ? number_format($pct, 4) . '%' : '-' ?></td>
            <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
    </div>
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
      <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i> TE as % of Revenue Trend</h5>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-primary chart-type-btn active" data-type="line" title="Line Chart"><i class="fas fa-chart-line"></i></button>
        <button type="button" class="btn btn-outline-primary chart-type-btn" data-type="bar" title="Bar Chart"><i class="fas fa-chart-bar"></i></button>
        <button type="button" class="btn btn-outline-primary chart-type-btn" data-type="pie" title="Pie Chart"><i class="fas fa-chart-pie"></i></button>
      </div>
    </div>
    <div id="chartContainer" style="display: block;">
      <canvas id="teRevenueChart" height="120"></canvas>
    </div>
    <div id="pieChartsContainer" class="row g-2" style="display: none;"></div>
  </div>
</div>

<script>
var chartData = <?= json_encode($chartData) ?>;
var chartYears = <?= json_encode($display_years) ?>;

(function() {
    var chart = null;
    var pieCharts = [];
    var currentChartType = 'line';
    var colors = ['#3b82f6','#ef4444','#10b981','#f59e42','#a78bfa','#f472b6','#facc15','#38bdf8','#6366f1','#eab308','#14b8a6','#f43f5e','#8b5cf6','#22d3ee','#84cc16'];

    function getColor(idx) { return colors[idx % colors.length]; }

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
                label: d.tax_type,
                data: chartYears.map(function(y) { return d[y] || 0; }),
                borderColor: getColor(i),
                backgroundColor: getColor(i),
                fill: false,
                tension: 0.2,
                spanGaps: true
            }; });

            chart = new Chart(document.getElementById('teRevenueChart'), {
                type: 'line',
                data: { labels: chartYears, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + Number(ctx.raw).toFixed(4) + '%'; } } }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Year', font: { size: 11 } }, ticks: { font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { callback: function(v) { return v.toFixed(2) + '%'; }, font: { size: 11 } } }
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

            chart = new Chart(document.getElementById('teRevenueChart'), {
                type: 'bar',
                data: { labels: filteredData.map(function(d) { return d.tax_type; }), datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + Number(ctx.raw).toFixed(4) + '%'; } } }
                    },
                    scales: {
                        x: { ticks: { font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { callback: function(v) { return v.toFixed(2) + '%'; }, font: { size: 11 } } }
                    }
                }
            });
        } else if (currentChartType === 'pie') {
            document.getElementById('chartContainer').style.display = 'none';
            document.getElementById('pieChartsContainer').style.display = 'flex';
            document.getElementById('pieChartsContainer').innerHTML = '';

            chartYears.forEach(function(year) {
                var yearData = filteredData.map(function(d) { return { label: d.tax_type, value: d[year] || 0 }; })
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
                                var pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + Number(ctx.raw).toFixed(4) + '% (' + pct + '%)';
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';

    var { jsPDF } = window.jspdf;
    var pdf = new jsPDF('l', 'mm', 'a4');
    var pageWidth = pdf.internal.pageSize.getWidth();

    html2canvas(document.getElementById('reportContent'), {
        scale: 2, backgroundColor: '#ffffff'
    }).then(function(tableCanvas) {
        var tableImgData = tableCanvas.toDataURL('image/png');
        var tableImgProps = pdf.getImageProperties(tableImgData);
        var tableWidth = pageWidth - 20;
        var tableHeight = (tableImgProps.height * tableWidth) / tableImgProps.width;
        pdf.addImage(tableImgData, 'PNG', 10, 10, tableWidth, tableHeight);

        var chartCanvas = document.getElementById('teRevenueChart');
        if (!chartCanvas) { pdf.save('TE_Percent_of_Revenue.pdf'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i> Export PDF'; return; }

        var types = ['line', 'bar', 'pie'];
        var promiseChain = Promise.resolve();

        types.forEach(function(type) {
            promiseChain = promiseChain.then(function() {
                return window.switchChartType(type);
            }).then(function() {
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        pdf.addPage();
                        if (type === 'pie') {
                            html2canvas(document.getElementById('pieChartsContainer'), {
                                scale: 2, backgroundColor: '#ffffff', useCORS: true, allowTaint: true
                            }).then(function(c) {
                                var img = c.toDataURL('image/png');
                                var ip = pdf.getImageProperties(img);
                                var w = pageWidth - 20;
                                var h = (ip.height * w) / ip.width;
                                pdf.addImage(img, 'PNG', 10, 10, w, h);
                                resolve();
                            });
                        } else {
                            var ctx = chartCanvas.getContext('2d');
                            ctx.globalCompositeOperation = 'destination-over';
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, chartCanvas.width, chartCanvas.height);
                            var img = chartCanvas.toDataURL('image/png');
                            var ip = pdf.getImageProperties(img);
                            var w = pageWidth - 20;
                            var h = (ip.height * w) / ip.width;
                            pdf.addImage(img, 'PNG', 10, 10, w, h);
                            resolve();
                        }
                    }, 300);
                });
            });
        });

        promiseChain.then(function() {
            pdf.save('TE_Percent_of_Revenue.pdf');
            window.switchChartType('line').then(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i> Export PDF';
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
