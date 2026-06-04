<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";

$pdo = getDbConnection();
$errors = [];
$report_filters = reportFilterInput();

// Fetch all available years
$all_years = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT YEAR(i.doc_date) FROM asycuda_imports i JOIN te_asycuda_result te ON i.id = te.asycuda_id WHERE te.customs_te <> 0 AND i.doc_date IS NOT NULL");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $y = (int)$row[0];
        if ($y > 1900 && $y < 2100) $all_years[] = $y;
    }
    $all_years = array_unique($all_years);
    sort($all_years);
} catch (Exception $e) { $errors[] = $e->getMessage(); }

$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0;
$to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;
if (!$from_year || !$to_year) {
    if (!empty($all_years)) { $from_year = min($all_years); $to_year = max($all_years); }
    else { $from_year = (int)date('Y') - 2; $to_year = (int)date('Y'); }
}
$display_years = range($from_year, $to_year);

// ==================== FETCH DATA ====================
$regimeDescriptions = []; // 4-digit code => description
$conditionDescriptions = []; // 3-digit code => description

try {
    $stmt = $pdo->query("SELECT regime_code, description FROM bm_customs_regime_codes WHERE active = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $regimeDescriptions[$row['regime_code']] = $row['description'];
    }
    $stmt = $pdo->query("SELECT condition_code, description FROM bm_payment_condition_codes WHERE active = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conditionDescriptions[$row['condition_code']] = $row['description'];
    }
} catch (Exception $e) { $errors[] = $e->getMessage(); }

// Build matrix: [regime_4digit][regime_3digit][year] => te
$matrix = [];
$regimeYearTotals = []; // [regime_4digit][year] => te
$grandYearTotals = [];

try {
    $params = [$from_year, $to_year];
    $sql = "SELECT
                SUBSTRING_INDEX(i.regime_code, '-', 1) AS regime_4digit,
                SUBSTRING_INDEX(i.regime_code, '-', -1) AS regime_3digit,
                YEAR(i.doc_date) AS tax_year,
                SUM(te.customs_te) AS te
            FROM asycuda_imports i
            JOIN te_asycuda_result te ON i.id = te.asycuda_id
            WHERE te.customs_te <> 0 AND i.doc_date IS NOT NULL
              AND YEAR(i.doc_date) BETWEEN ? AND ?
              " . reportImportDateCondition(reportBatchDateExpression("i", "import_batch_id", "import_date"), $report_filters, $params) . "
            GROUP BY regime_4digit, regime_3digit, tax_year
            ORDER BY regime_4digit, regime_3digit, tax_year";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $r4 = $row['regime_4digit'];
        $r3 = $row['regime_3digit'];
        $year = (int)$row['tax_year'];
        $te = (float)$row['te'];

        if (!isset($matrix[$r4])) $matrix[$r4] = [];
        if (!isset($matrix[$r4][$r3])) $matrix[$r4][$r3] = [];
        $matrix[$r4][$r3][$year] = ($matrix[$r4][$r3][$year] ?? 0) + $te;

        if (!isset($regimeYearTotals[$r4])) $regimeYearTotals[$r4] = [];
        $regimeYearTotals[$r4][$year] = ($regimeYearTotals[$r4][$year] ?? 0) + $te;

        $grandYearTotals[$year] = ($grandYearTotals[$year] ?? 0) + $te;
    }
} catch (Exception $e) { $errors[] = $e->getMessage(); }

// Sort regime codes naturally (as strings, but numeric-like)
ksort($matrix);

// ==================== CHART DATA ====================
$chartData = [];
foreach ($regimeYearTotals as $r4 => $yearData) {
    $row = ['regime' => $r4, 'label' => $r4 . ' - ' . ($regimeDescriptions[$r4] ?? 'Unknown')];
    foreach ($display_years as $y) { $row[(string)$y] = (float)($yearData[$y] ?? 0); }
    $chartData[] = $row;
}

// ==================== EXPORT ====================
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Customs Duty by Regime");
    $sheet->mergeCells('A1:' . chr(65 + count($display_years)) . '1');
    $sheet->setCellValue('A1', "Customs Duty TE by Regime Code ({$from_year} - {$to_year})");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $headerRow = 3;
    $sheet->setCellValue("A{$headerRow}", 'Regime Code / Sub-Regime');
    $colIdx = 2;
    foreach ($display_years as $y) {
        $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, (string)$y);
        $colIdx++;
    }
    $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, 'Row Total');
    $sheet->getStyle("A{$headerRow}:" . $sheet->getCellByColumnAndRow($colIdx, $headerRow)->getColumn() . "{$headerRow}")->getFont()->setBold(true);

    $rowIdx = 4;
    $grandTotal = 0;

    foreach ($matrix as $r4 => $subRegimes) {
        // Regime header
        $r4Label = $r4 . ' - ' . ($regimeDescriptions[$r4] ?? 'Unknown');
        $sheet->setCellValue("A{$rowIdx}", $r4Label);
        $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
        $rowIdx++;

        foreach ($subRegimes as $r3 => $yearData) {
            $r3Label = $r3 . ' - ' . ($conditionDescriptions[$r3] ?? 'Unknown');
            $sheet->setCellValue("A{$rowIdx}", '  ' . $r3Label);
            $cIdx = 2;
            $subTotal = 0;
            foreach ($display_years as $y) {
                $val = $yearData[$y] ?? 0;
                $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
                $cIdx++;
                $subTotal += $val;
            }
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $subTotal);
            $rowIdx++;
        }

        // Regime subtotal
        $sheet->setCellValue("A{$rowIdx}", '  SUBTOTAL');
        $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
        $cIdx = 2;
        $r4Total = 0;
        foreach ($display_years as $y) {
            $val = $regimeYearTotals[$r4][$y] ?? 0;
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
            $cIdx++;
            $r4Total += $val;
        }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $r4Total);
        $sheet->getStyle($sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn() . $rowIdx)->getFont()->setBold(true);
        $rowIdx++;
        $grandTotal += $r4Total;
    }

    // Grand total
    $sheet->setCellValue("A{$rowIdx}", 'GRAND TOTAL');
    $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true)->setSize(12);
    $cIdx = 2;
    $gt = 0;
    foreach ($display_years as $y) {
        $v = $grandYearTotals[$y] ?? 0;
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $v);
        $cIdx++;
        $gt += $v;
    }
    $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $gt);
    $sheet->getStyle($sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn() . $rowIdx)->getFont()->setBold(true)->setSize(12);

    foreach (range('A', $sheet->getCellByColumnAndRow(min($cIdx, 26), $rowIdx)->getColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Customs_Duty_TE_by_Regime_' . $from_year . '-' . $to_year . '.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once __DIR__ . "/../includes/header.php";

$totalRegimes = count($matrix);
$totalSubRegimes = 0;
foreach ($matrix as $sub) { $totalSubRegimes += count($sub); }
?>
<div class="row mb-4">
    <div class="col-md-8"><h2 class="fw-bold text-dark"><i class="fas fa-ship me-2 text-primary"></i> Customs Duty TE by Regime Code</h2><p class="text-muted">Tax Expenditure from Customs Duty, grouped by Regime Code (4-digit) and Sub-Regime / Payment Condition (3-digit).</p></div>
    <div class="col-md-4 text-end">
        <button id="expandAllBtn" class="btn btn-outline-primary shadow-sm me-2"><i class="fas fa-expand me-2"></i> Expand All</button>
        <a href="?<?= reportAppendFilters(["export" => 1, "from_year" => $from_year, "to_year" => $to_year]) ?>" class="btn btn-success shadow-sm"><i class="fas fa-file-excel me-2"></i> Export Excel</a>
        <button id="exportPdfBtn" class="btn btn-danger shadow-sm ms-2"><i class="fas fa-file-pdf me-2"></i> Export PDF</button>
    </div>
</div>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= htmlspecialchars(implode('; ', $errors)) ?></div><?php endif; ?>

<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;"><div class="card-body bg-light rounded-3">
<form method="GET" class="row align-items-end g-3">
    <div class="col-md-2"><label class="form-label small fw-bold text-muted text-uppercase">From Year</label><select name="from_year" class="form-select border-0 shadow-sm"><?php foreach ($all_years as $y): ?><option value="<?= $y ?>" <?= $y == $from_year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label small fw-bold text-muted text-uppercase">To Year</label><select name="to_year" class="form-select border-0 shadow-sm"><?php foreach ($all_years as $y): ?><option value="<?= $y ?>" <?= $y == $to_year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?></select></div>
    <?= reportImportDateFilterControl("report_customs_duty.php", $from_year, $to_year) ?>
    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-2"></i> Update</button></div>
    <div class="col-md-2"><a href="report_customs_duty.php" class="btn btn-outline-secondary w-100 border-0">Reset</a></div>
</form></div></div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Total TE</span><i class="fas fa-money-bill-wave fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= number_format(array_sum($grandYearTotals), 0) ?></span><small class="text-white-50 mt-1"><?= $from_year ?>&ndash;<?= $to_year ?></small></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Regime Codes</span><i class="fas fa-tag fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= $totalRegimes ?></span><small class="text-white-50 mt-1">4-digit groups</small></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Sub-Regimes</span><i class="fas fa-code-branch fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= $totalSubRegimes ?></span><small class="text-white-50 mt-1">3-digit codes</small></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100" style="border-radius:12px;background:linear-gradient(135deg,#fa709a 0%,#fee140 100%);"><div class="card-body text-white d-flex flex-column"><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-light small text-white-50 text-uppercase">Years</span><i class="fas fa-calendar fa-2x text-white-50"></i></div><span class="fw-bold fs-4"><?= count($display_years) ?></span><small class="text-white-50 mt-1"><?= $from_year ?>&ndash;<?= $to_year ?></small></div></div></div>
</div>

<div id="reportContent" class="card shadow-sm border-0 mb-4" style="border-radius:12px;"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0 matrix-table" id="regimeTable"><thead class="bg-primary text-white"><tr class="align-middle"><th class="ps-4 py-3" style="min-width:320px;">Regime Code / Sub-Regime (Payment Condition)</th><?php foreach ($display_years as $year): ?><th class="text-end py-3"><?= $year ?></th><?php endforeach; ?><th class="text-end pe-4 py-3 bg-dark">Row Total</th></tr></thead><tbody>
<?php
$grandAllTotal = array_sum($grandYearTotals);
foreach ($matrix as $r4 => $subRegimes):
    $r4Total = array_sum($regimeYearTotals[$r4] ?? []);
    $r4Desc = $regimeDescriptions[$r4] ?? 'Unknown';
    $r4Id = 'regime-' . preg_replace('/[^a-zA-Z0-9]/', '', $r4);
?>
<tr class="bg-light type-header" data-target="#<?= $r4Id ?>" role="button"><td class="ps-4 py-3 fw-bold fs-6"><i class="fas fa-folder-open me-2 text-primary"></i><?= htmlspecialchars($r4) ?> &ndash; <?= htmlspecialchars($r4Desc) ?> <span class="badge bg-primary ms-2"><?= number_format($r4Total, 0) ?></span></td>
<?php foreach ($display_years as $year): ?><td class="text-end fw-bold py-3"><?= number_format($regimeYearTotals[$r4][$year] ?? 0, 0) ?></td><?php endforeach; ?>
<td class="text-end pe-4 py-3 fw-bold bg-white"><?= number_format($r4Total, 0) ?></td></tr>
<tr id="<?= $r4Id ?>" class="collapse provision-group"><td colspan="<?= 2 + count($display_years) ?>" class="p-0">
<table class="table table-sm align-middle mb-0 inner-table"><tbody>
<?php foreach ($subRegimes as $r3 => $yearData):
    $subTotal = array_sum($yearData);
    $r3Desc = $conditionDescriptions[$r3] ?? 'Unknown';
?>
<tr><td class="ps-5"><span class="badge bg-secondary me-2"><?= htmlspecialchars($r3) ?></span><?= htmlspecialchars($r3Desc) ?></td>
<?php foreach ($display_years as $year): $val = $yearData[$year] ?? 0; ?>
<td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
<?php endforeach; ?>
<td class="text-end pe-4 fw-bold"><?= $subTotal > 0 ? number_format($subTotal, 0) : '-' ?></td></tr>
<?php endforeach; ?>
</tbody></table></td></tr>
<?php endforeach; ?>
</tbody><tfoot class="bg-dark border-top-2 border-dark"><tr class="fw-bold text-white align-middle"><td class="ps-4 py-3 fs-5">GRAND TOTAL</td>
<?php $gt = 0; foreach ($display_years as $year): $v = $grandYearTotals[$year] ?? 0; $gt += $v; ?>
<td class="text-end py-3 fs-5"><?= number_format($v, 0) ?></td><?php endforeach; ?>
<td class="text-end pe-4 py-3 fs-5 bg-black"><?= number_format($gt, 0) ?></td></tr></tfoot></table></div></div></div>

<div class="card shadow-sm mb-4" style="border-radius:12px;"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> Customs Duty TE by Regime Code (<?= $from_year ?>&ndash;<?= $to_year ?>)</h5>
<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary chart-type-btn active" data-type="bar"><i class="fas fa-chart-bar"></i> Bar</button><button class="btn btn-outline-primary chart-type-btn" data-type="line"><i class="fas fa-chart-line"></i> Line</button><button class="btn btn-outline-primary chart-type-btn" data-type="pie"><i class="fas fa-chart-pie"></i> Pie</button></div></div>
<div id="chartContainer" style="width:100%;height:360px;"><canvas id="teRegimeChart"></canvas></div>
<div id="pieChartsContainer" class="row g-3" style="display:none;"></div>
</div></div>

<style>
.matrix-table{font-size:.9rem}.matrix-table thead th{border:none;font-weight:700;text-transform:uppercase;letter-spacing:.5px;font-size:.78rem}.matrix-table tbody td{border-bottom:1px solid #f2f2f2}.matrix-table tfoot td{font-size:1.1rem;border-top:2px solid #222}.type-header td{background-color:#f8f9fa!important;border-bottom:2px solid #dee2e6!important}.type-header:hover{background-color:#e9ecef!important}.inner-table td{border:none!important;padding:.4rem .5rem!important}.inner-table tr:not(:last-child) td{border-bottom:1px solid #f0f0f0!important}.provision-group{background:transparent!important}.table-hover tbody tr:hover td{background-color:#f0f7ff}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var chartData=<?= json_encode($chartData) ?>,chartYears=<?= json_encode($display_years) ?>,chart=null,pieCharts=[],currentChartType='bar';
var COLORS=['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ac','#6b6ecf','#d4a6c8','#a1c9f4','#ffb482','#8cd17d','#f1ce63','#499894','#e377c2','#b5b5b5','#7f7f7f','#c5b0d5','#ffbb78','#98df8a','#ff9898'];
function getColor(i){return COLORS[i%COLORS.length]}
function formatValue(v){if(v>=1e12)return(v/1e12).toFixed(1)+'T';if(v>=1e9)return(v/1e9).toFixed(1)+'B';if(v>=1e6)return(v/1e6).toFixed(1)+'M';if(v>=1e3)return(v/1e3).toFixed(1)+'K';return v.toFixed(0)}
function setChartType(t){currentChartType=t;document.querySelectorAll('.chart-type-btn').forEach(function(b){b.classList.toggle('active',b.getAttribute('data-type')===t)});renderChart()}
function switchChartType(t){return new Promise(function(r){setChartType(t);setTimeout(r,1200)})}
function renderChart(){if(chart){chart.destroy();chart=null}pieCharts.forEach(function(c){c.destroy()});pieCharts=[];var fd=chartData.filter(function(d){return chartYears.some(function(y){return Math.abs(d[y])>0})});if(fd.length===0)fd=chartData;if(currentChartType==='line'){document.getElementById('chartContainer').style.display='block';document.getElementById('pieChartsContainer').style.display='none';document.getElementById('pieChartsContainer').innerHTML='';var ds=fd.map(function(d,i){return{label:d.label,data:chartYears.map(function(y){return d[y]||0}),borderColor:getColor(i),backgroundColor:getColor(i),fill:false,tension:.2,spanGaps:true}});chart=new Chart(document.getElementById('teRegimeChart'),{type:'line',data:{labels:chartYears,datasets:ds},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top',labels:{boxWidth:12,padding:10,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+Number(ctx.raw).toLocaleString()}}}},scales:{x:{title:{display:true,text:'Year',font:{size:11}},ticks:{font:{size:11}}},y:{beginAtZero:true,ticks:{callback:function(v){return formatValue(v)},font:{size:11}}}}}})}else if(currentChartType==='bar'){document.getElementById('chartContainer').style.display='block';document.getElementById('pieChartsContainer').style.display='none';document.getElementById('pieChartsContainer').innerHTML='';var ds=chartYears.map(function(y,i){return{label:String(y),data:fd.map(function(d){return d[y]||0}),backgroundColor:getColor(i)}});chart=new Chart(document.getElementById('teRegimeChart'),{type:'bar',data:{labels:fd.map(function(d){return d.label}),datasets:ds},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top',labels:{boxWidth:12,padding:10,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+Number(ctx.raw).toLocaleString()}}}},scales:{x:{ticks:{font:{size:10}}},y:{beginAtZero:true,ticks:{callback:function(v){return formatValue(v)},font:{size:11}}}}}})}else if(currentChartType==='pie'){document.getElementById('chartContainer').style.display='none';document.getElementById('pieChartsContainer').style.display='flex';document.getElementById('pieChartsContainer').innerHTML='';chartYears.forEach(function(year){var yd=fd.map(function(d){return{label:d.label,value:d[year]||0}}).filter(function(i){return i.value>0});if(yd.length===0)return;var col=document.createElement('div');col.className='col-lg-4 col-md-6 col-sm-12';var ctn=document.createElement('div');ctn.className='pie-chart-container';var cv=document.createElement('canvas');ctn.appendChild(cv);col.appendChild(ctn);document.getElementById('pieChartsContainer').appendChild(col);var p=new Chart(cv,{type:'pie',data:{labels:yd.map(function(i){return i.label}),datasets:[{data:yd.map(function(i){return i.value}),backgroundColor:yd.map(function(_,i){return getColor(i)})}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'right',labels:{boxWidth:8,padding:4,font:{size:9}}},title:{display:true,text:'Year '+year,font:{size:11},padding:{top:4,bottom:2}},tooltip:{callbacks:{label:function(ctx){var total=ctx.dataset.data.reduce(function(a,b){return a+b},0);return ctx.label+': '+Number(ctx.raw).toLocaleString()+' ('+((ctx.raw/total)*100).toFixed(1)+'%)'}}}}}});pieCharts.push(p)})}}
document.querySelectorAll('.chart-type-btn').forEach(function(b){b.addEventListener('click',function(){setChartType(this.getAttribute('data-type'))})});
renderChart();
document.getElementById('expandAllBtn').addEventListener('click',function(){var btn=this;var collapsed=document.querySelectorAll('.provision-group.collapse:not(.show)');var expanded=document.querySelectorAll('.provision-group.collapse.show');if(collapsed.length>0){document.querySelectorAll('.provision-group.collapse').forEach(function(e){e.classList.add('show')});btn.innerHTML='<i class="fas fa-compress me-2"></i> Collapse All'}else{document.querySelectorAll('.provision-group.collapse').forEach(function(e){e.classList.remove('show')});btn.innerHTML='<i class="fas fa-expand me-2"></i> Expand All'}});
document.querySelectorAll('.type-header').forEach(function(el){el.addEventListener('click',function(){var target=document.querySelector(this.getAttribute('data-target'));if(target){target.classList.toggle('show')}})});
document.getElementById('exportPdfBtn')?.addEventListener('click',function(){var btn=this;btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';document.querySelectorAll('.provision-group').forEach(function(e){e.classList.add('show')});var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';s.onload=function(){var s2=document.createElement('script');s2.src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';s2.onload=function(){var {jsPDF}=window.jspdf;var pdf=new jsPDF('l','mm','a4');var pw=pdf.internal.pageSize.getWidth();switchChartType('bar').then(function(){setTimeout(function(){html2canvas(document.getElementById('reportContent'),{scale:2,backgroundColor:'#ffffff'}).then(function(tc){var td=tc.toDataURL('image/png');var tp=pdf.getImageProperties(td);var tw=pw-20;var th=(tp.height*tw)/tp.width;pdf.addImage(td,'PNG',10,10,tw,th);var cc=document.querySelector('#chartContainer canvas');if(cc){setTimeout(function(){var md=cc.toDataURL('image/png');pdf.addPage();var mp=pdf.getImageProperties(md);var mw=pw-20;var mh=(mp.height*mw)/mp.width;pdf.addImage(md,'PNG',10,10,mw,mh);pdf.save('Customs_Duty_TE_by_Regime_<?= $from_year ?>-<?= $to_year ?>.pdf');btn.disabled=false;btn.innerHTML='<i class="fas fa-file-pdf me-2"></i> Export PDF'},800)}else{pdf.save('Customs_Duty_TE_by_Regime_<?= $from_year ?>-<?= $to_year ?>.pdf');btn.disabled=false;btn.innerHTML='<i class="fas fa-file-pdf me-2"></i> Export PDF'}})},500)})};document.head.appendChild(s2)};document.head.appendChild(s)});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
