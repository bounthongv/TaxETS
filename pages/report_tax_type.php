<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";

$is_export = isset($_GET['export']);

if (!$is_export) {
    require_once __DIR__ . "/../includes/header.php";
}

$pdo = getDbConnection();
$report_filters = reportFilterInput();

// Initialize data containers
$profit_data = [];
$pit_data = [];
$salary_data = [];
$vat_domestic_data = [];
$customs_data = [];
$excise_data = [];
$vat_import_data = [];
$sez_dev_data = [];
$sez_inv_data = [];
$resource_data = [];
$royalty_data = [];
$land_concession_data = [];
$annual_years = [];

try {
    $reportData = reportTaxTypeData($pdo, $report_filters);
    $profit_data = $reportData["profit"];
    $pit_data = $reportData["pit"];
    $salary_data = $reportData["salary"];
    $vat_domestic_data = $reportData["vat_domestic"];
    $customs_data = $reportData["customs"];
    $excise_data = $reportData["excise"];
    $vat_import_data = $reportData["vat_import"];
    $sez_dev_data = $reportData["sez_dev"];
    $sez_inv_data = $reportData["sez_inv"];
    $resource_data = $reportData["resource"];
    $royalty_data = $reportData["royalty"];
    $land_concession_data = $reportData["land"];

    // Collect all unique years and filter out null/0/invalid
    $raw_years = array_unique(array_merge(
        array_keys($profit_data),
        array_keys($pit_data),
        array_keys($salary_data),
        array_keys($vat_domestic_data),
        array_keys($customs_data),
        array_keys($excise_data),
        array_keys($vat_import_data),
        array_keys($sez_dev_data),
        array_keys($sez_inv_data),
        array_keys($resource_data),
        array_keys($royalty_data),
        array_keys($land_concession_data)
    ));
    
    $annual_years = array_filter($raw_years, function($y) {
        return is_numeric($y) && $y > 1900 && $y < 2100;
    });
    sort($annual_years);

    $all_years = $annual_years;
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
    $annual_years = array_values(array_filter($annual_years, function($y) use ($from_year, $to_year) {
        return (int)$y >= $from_year && (int)$y <= $to_year;
    }));

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error aggregating data: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

if (!isset($all_years)) { $all_years = []; }
if (!isset($from_year) || !isset($to_year)) {
    $from_year = (int)date("Y");
    $to_year = (int)date("Y");
}
if (empty($annual_years)) { $annual_years = range($from_year, $to_year); }
if (empty($all_years)) { $all_years = $annual_years; }

$tax_types = [
    'Corporate Income Tax (Profit Tax)' => $profit_data,
    'Individual Income Tax (PIT)'       => $pit_data,
    'Salary Tax'                        => $salary_data,
    'Domestic Value Added Tax (VAT)'    => $vat_domestic_data,
    'Customs Duty (Import)'             => $customs_data,
    'Excise Tax (Import)'               => $excise_data,
    'Import Value Added Tax (VAT)'      => $vat_import_data,
    'SEZ Developer'                     => $sez_dev_data,
    'SEZ Investor'                      => $sez_inv_data,
    'Resource Fee (Non-Tax)'            => $resource_data,
    'Royalty Fee (Non-Tax)'             => $royalty_data,
    'Land Concession (Non-Tax)'         => $land_concession_data
];

// Export to Excel
if ($is_export) {
    require __DIR__ . '/../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('TE by Tax Type');

    $sheet->setCellValue('A1', 'Tax Type Category');
    $col = 'B';
    foreach ($annual_years as $year) {
        $sheet->setCellValue($col . '1', $year);
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
        $col = 'B';
        foreach ($annual_years as $year) {
            $val = $data[$year] ?? 0;
            $sheet->setCellValue($col . $rowIdx, $val > 0 ? $val : '');
            $sheet->getStyle($col . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $col++;
        }
        $rowIdx++;
    }

    $sheet->setCellValue('A' . $rowIdx, 'Total Revenue Foregone');
    $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
    $col = 'B';
    foreach ($annual_years as $year) {
        $total = 0;
        foreach ($tax_types as $data) { $total += ($data[$year] ?? 0); }
        $sheet->setCellValue($col . $rowIdx, $total > 0 ? $total : '');
        $sheet->getStyle($col . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $col++;
    }
    $lastCol = chr(65 + count($annual_years));
    $sheet->getStyle('A' . $rowIdx . ':' . $lastCol . $rowIdx)
        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('D9E2F3'));

    foreach (range('A', $lastCol) as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="TE_by_Tax_Type.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><i class="fas fa-file-invoice-dollar me-2"></i> Revenue Foregone from Tax Expenditure (Kip)</h2>
      <p class="text-muted">Consolidated summary of tax expenditures across all tax regimes and years.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="?<?= reportAppendFilters(["export" => 1, "from_year" => $from_year, "to_year" => $to_year]) ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel</a>
      <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="fas fa-file-pdf me-1"></i> Export PDF</button>
      <a href="recalculate_all.php" class="btn btn-primary"><i class="fas fa-sync-alt me-1"></i> Update Data</a>
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
      <?= reportImportDateFilterControl("report_tax_type.php") ?>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-filter me-2"></i> Filter</button>
      </div>
      <div class="col-md-2">
        <a href="report_tax_type.php" class="btn btn-light w-100 shadow-sm border-0">Reset</a>
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
            <th class="ps-4" style="width: 300px;">Tax Type Category</th>
            <?php foreach ($annual_years as $year): ?>
              <th class="text-end pe-4"><?= htmlspecialchars($year) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tax_types as $type => $data): ?>
          <tr>
            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($type) ?></td>
            <?php foreach ($annual_years as $year): ?>
              <td class="text-end pe-4">
                <?php 
                $val = $data[$year] ?? 0;
                echo $val > 0 ? '<strong>' . number_format($val, 0) . '</strong>' : '<span class="text-muted opacity-50">-</span>';
                ?>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-primary fw-bold">
          <tr>
            <td class="ps-4">Total Revenue Foregone</td>
            <?php foreach ($annual_years as $year): ?>
              <td class="text-end pe-4">
                <?php 
                $total = 0;
                foreach ($tax_types as $data) { $total += ($data[$year] ?? 0); }
                echo $total > 0 ? number_format($total, 0) : '-';
                ?>
              </td>
            <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

</div> <!-- /reportContent -->

<div class="alert bg-light border text-muted small">
    <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> This report aggregates real-time data from result tables for CIT and PIT, while Domestic VAT and ASYCUDA data are pulled from their respective import/assessment modules. Click <strong>"Update Data"</strong> to refresh calculations if new imports have been processed.
</div>

<style>
.chart-type-btn.active { background-color: var(--bs-primary) !important; color: #fff !important; border-color: var(--bs-primary) !important; }
.pie-chart-container { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 0.25rem; }
.pie-chart-container canvas { max-height: 180px; }
</style>

<!-- Chart Section -->
<div class="card shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i> TE Trend Visualization</h5>
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
      <canvas id="teResultByTypeChart" height="120"></canvas>
    </div>

    <div id="pieChartsContainer" class="row g-2" style="display: none;"></div>
  </div>
</div>

<script>
var chartData = <?= json_encode(array_map(function($type, $data) use ($annual_years) {
    $row = ['tax_type_name' => $type];
    foreach ($annual_years as $year) {
        $row[(string)$year] = (float)($data[$year] ?? 0);
    }
    return $row;
}, array_keys($tax_types), array_values($tax_types))) ?>;
var chartYears = <?= json_encode(array_map('intval', $annual_years)) ?>;

(function() {
    var chart = null;
    var pieCharts = [];
    var currentChartType = 'line';

    var colors = ['#3b82f6','#ef4444','#10b981','#f59e42','#a78bfa','#f472b6','#facc15','#38bdf8','#6366f1','#eab308','#14b8a6','#f43f5e'];

    function getColor(idx) { return colors[idx % colors.length]; }

    function formatValue(v) {
        if (Math.abs(v) >= 1e12) return (v / 1e12).toFixed(1) + 'T';
        if (Math.abs(v) >= 1e9) return (v / 1e9).toFixed(1) + 'B';
        if (Math.abs(v) >= 1e6) return (v / 1e6).toFixed(1) + 'M';
        if (Math.abs(v) >= 1e3) return (v / 1e3).toFixed(1) + 'K';
        return v.toFixed(0);
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
                label: d.tax_type_name,
                data: chartYears.map(function(y) { return d[y] || 0; }),
                borderColor: getColor(i),
                backgroundColor: getColor(i),
                fill: false,
                tension: 0.2,
                spanGaps: true
            }; });

            chart = new Chart(document.getElementById('teResultByTypeChart'), {
                type: 'line',
                data: { labels: chartYears, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
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

            chart = new Chart(document.getElementById('teResultByTypeChart'), {
                type: 'bar',
                data: { labels: filteredData.map(function(d) { return d.tax_type_name; }), datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
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
                var yearData = filteredData.map(function(d) { return { label: d.tax_type_name, value: d[year] || 0 }; })
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
                        datasets: [{
                            data: yearData.map(function(item) { return item.value; }),
                            backgroundColor: yearData.map(function(_, idx) { return getColor(idx); })
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
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
            // Chart.js default animation is 1000ms, wait for it with a buffer
            setTimeout(resolve, 1200);
        });
    };

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

    // Step 1: Capture the table
    html2canvas(tableCard, { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }).then(function(tableCanvas) {
        var pdf = new jspdf.jsPDF('l', 'mm', 'a3');
        var pw = pdf.internal.pageSize.getWidth();
        var ph = pdf.internal.pageSize.getHeight();

        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Revenue Foregone from Tax Expenditure (Kip)', pw / 2, margin + 6, { align: 'center' });

        var tw = pw - margin * 2;
        var th = (tableCanvas.height / tableCanvas.width) * tw;
        if (th > ph - 30) th = ph - 30;
        pdf.addImage(tableCanvas.toDataURL('image/png'), 'PNG', margin, 22, tw, th);

        // Step 2: Capture all 3 chart types sequentially
        var chartConfigs = [
            { type: 'line', title: 'TE Trend by Tax Type (Line Chart)', pageTitle: true },
            { type: 'bar', title: 'TE by Tax Type (Bar Chart)', pageTitle: true },
            { type: 'pie', title: 'TE Distribution by Year', pageTitle: true }
        ];

        function captureChart(idx) {
            if (idx >= chartConfigs.length) {
                pdf.save('TE_by_Tax_Type.pdf');
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
                            pdf.addImage(cp.data, 'JPEG', margin + col * (cw + margin), y, cp.w, cp.h);
                            maxRowH = Math.max(maxRowH, cp.h);
                            col++;
                            if (col >= itemsPerRow) { col = 0; y += maxRowH + 6; maxRowH = 0; }
                        });
                    } else {
                        var mainCvs = document.getElementById('teResultByTypeChart');
                        if (mainCvs) {
                            var cp = captureCanvas(mainCvs, pw - margin * 2, 140);
                            var cx = (pw - cp.w) / 2;
                            pdf.addImage(cp.data, 'JPEG', cx, y, cp.w, cp.h);
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

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
