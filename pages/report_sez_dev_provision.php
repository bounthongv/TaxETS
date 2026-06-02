<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection(); $errors = [];

$all_years = [];
try { $stmt = $pdo->query("SELECT DISTINCT tax_year FROM import_sez_data WHERE tax_year > 0"); while ($row = $stmt->fetch(PDO::FETCH_NUM)) { if ($row[0] > 1900 && $row[0] < 2100) $all_years[] = (int)$row[0]; } $all_years = array_unique($all_years); sort($all_years); } catch (Exception $e) { $errors[] = $e->getMessage(); }
$from_year = isset($_GET['from_year']) ? (int)$_GET['from_year'] : 0; $to_year = isset($_GET['to_year']) ? (int)$_GET['to_year'] : 0;
if (!$from_year || !$to_year) { if (!empty($all_years)) { $from_year = min($all_years); $to_year = max($all_years); } else { $from_year = (int)date('Y') - 4; $to_year = (int)date('Y'); } }
$display_years = []; for ($y = $from_year; $y <= $to_year; $y++) { $display_years[] = $y; }

$matrix = []; $other_te = []; $grand_total_te = [];
try {
    $stmt = $pdo->prepare("SELECT provision_number, tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? AND te_amount > 0 AND type = 'Developer' AND provision_number IS NOT NULL AND provision_number != '' GROUP BY provision_number, tax_year");
    $stmt->execute([$from_year, $to_year]); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $matrix[$row['provision_number']][(int)$row['tax_year']] = (float)$row['te']; }
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? AND te_amount > 0 AND type = 'Developer' AND (provision_number IS NULL OR provision_number = '') GROUP BY tax_year");
    $stmt->execute([$from_year, $to_year]); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $other_te[(int)$row['tax_year']] = (float)$row['te']; }
    $stmt = $pdo->prepare("SELECT tax_year, SUM(te_amount) as te FROM import_sez_data WHERE tax_year BETWEEN ? AND ? AND te_amount > 0 AND type = 'Developer' GROUP BY tax_year");
    $stmt->execute([$from_year, $to_year]); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $grand_total_te[(int)$row['tax_year']] = (float)$row['te']; }
} catch (Exception $e) { $errors[] = $e->getMessage(); }

$chartData = []; $allProvNums = array_keys($matrix);
foreach ($allProvNums as $num) { $item = ['provision_number' => $num, 'label' => "Prov {$num}"]; foreach ($display_years as $y) { $item[(string)$y] = (float)($matrix[$num][$y] ?? 0); } $chartData[] = $item; }
$otherItem = ['provision_number' => 'Unclassified', 'label' => 'Unclassified / Other']; $otherHasData = false;
foreach ($display_years as $y) { $v = (float)($other_te[$y] ?? 0); $otherItem[(string)$y] = $v; if ($v > 0) $otherHasData = true; }
if ($otherHasData) { $chartData[] = $otherItem; }

if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("TE by Provision"); $sheet->mergeCells('A1:' . chr(65 + count($display_years)) . '1');
    $sheet->setCellValue('A1', "SEZ Developer TE by Provision ({$from_year} - {$to_year})");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $headerRow = 3; $sheet->setCellValue("A{$headerRow}", 'Provision'); $colIdx = 2;
    foreach ($display_years as $y) { $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, (string)$y); $colIdx++; }
    $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, 'Row Total');
    $sheet->getStyle("A{$headerRow}:" . $sheet->getCellByColumnAndRow($colIdx, $headerRow)->getColumn() . "{$headerRow}")->getFont()->setBold(true);
    $rowIdx = 4; $colTotals = [];
    foreach ($matrix as $num => $yearData) {
        $sheet->setCellValue("A{$rowIdx}", "Prov {$num}"); $cIdx = 2; $rowTotal = 0;
        foreach ($display_years as $y) { $val = $yearData[$y] ?? 0; $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val); $rowTotal += $val; $colTotals[$y] = ($colTotals[$y] ?? 0) + $val; $cIdx++; }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $rowTotal); $rowIdx++;
    }
    if (array_sum($other_te) > 0) {
        $sheet->setCellValue("A{$rowIdx}", 'Unclassified / Other'); $cIdx = 2; $unRowTotal = 0;
        foreach ($display_years as $y) { $val = $other_te[$y] ?? 0; $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val); $unRowTotal += $val; $colTotals[$y] = ($colTotals[$y] ?? 0) + $val; $cIdx++; }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $unRowTotal); $rowIdx++;
    }
    $sheet->setCellValue("A{$rowIdx}", 'TOTAL'); $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
    $cIdx = 2; $grandTotal = 0;
    foreach ($display_years as $y) { $val = $grand_total_te[$y] ?? 0; $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val); $grandTotal += $val; $cIdx++; }
    $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $grandTotal);
    $sheet->getStyle($sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn() . $rowIdx)->getFont()->setBold(true);
    foreach (range('A', $sheet->getCellByColumnAndRow($cIdx, $rowIdx)->getColumn()) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="TE_SEZ_Dev_by_Provision_' . $from_year . '-' . $to_year . '.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet); $writer->save('php://output');
    exit;
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="row mb-4"><div class="col-md-8"><h2 class="fw-bold text-dark"><i class="fas fa-building me-2 text-primary"></i> SEZ Developer TE by Provision</h2><p class="text-muted">Tax Expenditure from SEZ Developers, classified by provision.</p></div><div class="col-md-4 text-end"><a href="?export=1&from_year=<?= $from_year ?>&to_year=<?= $to_year ?>" class="btn btn-success shadow-sm"><i class="fas fa-file-excel me-2"></i> Export Excel</a><button id="exportPdfBtn" class="btn btn-danger shadow-sm ms-2"><i class="fas fa-file-pdf me-2"></i> Export PDF</button></div></div>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= htmlspecialchars(implode('; ', $errors)) ?></div><?php endif; ?>
<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;"><div class="card-body bg-light rounded-3">
<form method="GET" class="row align-items-end g-3">
    <div class="col-md-3"><label class="form-label small fw-bold text-muted text-uppercase">From Year</label><select name="from_year" class="form-select border-0 shadow-sm"><?php foreach ($all_years as $y): ?><option value="<?= $y ?>" <?= $y == $from_year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label small fw-bold text-muted text-uppercase">To Year</label><select name="to_year" class="form-select border-0 shadow-sm"><?php foreach ($all_years as $y): ?><option value="<?= $y ?>" <?= $y == $to_year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-2"></i> Update</button></div>
    <div class="col-md-3"><a href="report_sez_dev_provision.php" class="btn btn-outline-secondary w-100 border-0">Reset</a></div>
</form></div></div>
<div class="row g-3 mb-4"><?php
$cards = [
    ['Total TE', array_sum($grand_total_te), '#667eea,#764ba2', 'fa-money-bill-wave'],
    ['Provisions', count($matrix), '#f093fb,#f5576c', 'fa-tag'],
    ['Provisions w/ TE', count($matrix), '#4facfe,#00f2fe', 'fa-check-circle'],
    ['Unclassified', array_sum($other_te), '#43e97b,#38f9d7', 'fa-folder-open'],
];
foreach ($cards as $c) { echo "<div class=\"col-md-3\"><div class=\"card shadow-sm border-0 h-100\" style=\"border-radius:12px;background:linear-gradient(135deg,{$c[2]});\"><div class=\"card-body text-white d-flex flex-column\"><div class=\"d-flex justify-content-between align-items-center mb-2\"><span class=\"fw-light small text-white-50 text-uppercase\">{$c[0]}</span><i class=\"fas {$c[3]} fa-2x text-white-50\"></i></div><span class=\"fw-bold fs-4\">" . number_format($c[1], 0) . "</span></div></div></div>"; }
?></div>
<div id="reportContent" class="card shadow-sm border-0 mb-4" style="border-radius:12px;"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0 matrix-table"><thead class="bg-primary text-white"><tr class="align-middle"><th class="ps-4 py-3">Provision</th><?php foreach ($display_years as $year): ?><th class="text-end py-3"><?= $year ?></th><?php endforeach; ?><th class="text-end pe-4 py-3 bg-dark">Row Total</th></tr></thead><tbody>
<?php $col_totals = []; foreach ($allProvNums as $num): $yearData = $matrix[$num] ?? []; $rowTotal = 0; ?>
<tr><td class="ps-4 fw-bold text-primary">Prov <?= htmlspecialchars($num) ?></td><?php foreach ($display_years as $year): $val = $yearData[$year] ?? 0; $rowTotal += $val; $col_totals[$year] = ($col_totals[$year] ?? 0) + $val; ?><td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td><?php endforeach; ?><td class="text-end pe-4 fw-bold bg-light"><?= $rowTotal > 0 ? number_format($rowTotal, 0) : '-' ?></td></tr>
<?php endforeach; if (array_sum($other_te) > 0): ?><tr class="table-warning bg-opacity-10 border-top"><td class="ps-4 fw-bold text-muted"><i class="fas fa-question-circle me-1 text-warning"></i> Unclassified / Other</td><?php $unRowTotal = 0; foreach ($display_years as $year): $val = $other_te[$year] ?? 0; $unRowTotal += $val; $col_totals[$year] = ($col_totals[$year] ?? 0) + $val; ?><td class="text-end <?= $val > 0 ? '' : 'text-muted opacity-25' ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td><?php endforeach; ?><td class="text-end pe-4 fw-bold"><?= number_format($unRowTotal, 0) ?></td></tr><?php endif; ?>
</tbody><tfoot class="bg-light border-top-2 border-dark"><tr class="fw-bold text-dark align-middle"><td class="ps-4 py-3">TOTAL</td><?php $grandTotal = 0; foreach ($display_years as $year): $val = $grand_total_te[$year] ?? 0; $grandTotal += $val; ?><td class="text-end py-3"><?= number_format($val, 0) ?></td><?php endforeach; ?><td class="text-end pe-4 py-3 bg-dark text-white"><?= number_format($grandTotal, 0) ?></td></tr></tfoot></table></div></div></div>
<div class="card shadow-sm mb-4" style="border-radius:12px;"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> TE by Provision (<?= $from_year ?>&ndash;<?= $to_year ?>)</h5><div class="btn-group btn-group-sm"><button class="btn btn-outline-primary chart-type-btn active" data-type="bar"><i class="fas fa-chart-bar"></i> Bar</button><button class="btn btn-outline-primary chart-type-btn" data-type="line"><i class="fas fa-chart-line"></i> Line</button><button class="btn btn-outline-primary chart-type-btn" data-type="pie"><i class="fas fa-chart-pie"></i> Pie</button></div></div><div id="chartContainer" style="width:100%;height:360px;"><canvas id="teProvisionChart"></canvas></div><div id="pieChartsContainer" class="row g-3" style="display:none;"></div></div></div>
<style>.matrix-table{font-size:.95rem}.matrix-table thead th{border:none;font-weight:700;text-transform:uppercase;letter-spacing:.5px;font-size:.8rem}.matrix-table tbody td{border-bottom:1px solid #f2f2f2}.matrix-table tfoot td{font-size:1.1rem;border-top:2px solid #222}.table-hover tbody tr:hover td{background-color:#f0f7ff}</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var chartData=<?= json_encode($chartData) ?>,chartYears=<?= json_encode($display_years) ?>,chart=null,pieCharts=[],currentChartType='bar';
var COLORS=['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ac','#6b6ecf','#d4a6c8','#a1c9f4','#ffb482','#8cd17d','#f1ce63','#499894','#e377c2','#b5b5b5','#7f7f7f','#c5b0d5','#ffbb78','#98df8a','#ff9898'];
function getColor(i){return COLORS[i%COLORS.length]}
function formatValue(v){if(v>=1e12)return(v/1e12).toFixed(1)+'T';if(v>=1e9)return(v/1e9).toFixed(1)+'B';if(v>=1e6)return(v/1e6).toFixed(1)+'M';if(v>=1e3)return(v/1e3).toFixed(1)+'K';return v.toFixed(0)}
function setChartType(t){currentChartType=t;document.querySelectorAll('.chart-type-btn').forEach(function(b){b.classList.toggle('active',b.getAttribute('data-type')===t)});renderChart()}
function switchChartType(t){return new Promise(function(r){setChartType(t);setTimeout(r,1200)})}
function renderChart(){if(chart){chart.destroy();chart=null}pieCharts.forEach(function(c){c.destroy()});pieCharts=[];var fd=chartData.filter(function(d){return chartYears.some(function(y){return Math.abs(d[y])>0})});if(fd.length===0)fd=chartData;if(currentChartType==='line'){document.getElementById('chartContainer').style.display='block';document.getElementById('pieChartsContainer').style.display='none';document.getElementById('pieChartsContainer').innerHTML='';var ds=fd.map(function(d,i){return{label:d.label,data:chartYears.map(function(y){return d[y]||0}),borderColor:getColor(i),backgroundColor:getColor(i),fill:false,tension:.2,spanGaps:true}});chart=new Chart(document.getElementById('teProvisionChart'),{type:'line',data:{labels:chartYears,datasets:ds},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top',labels:{boxWidth:12,padding:10,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+Number(ctx.raw).toLocaleString()}}}},scales:{x:{title:{display:true,text:'Year',font:{size:11}},ticks:{font:{size:11}}},y:{beginAtZero:true,ticks:{callback:function(v){return formatValue(v)},font:{size:11}}}}}})}else if(currentChartType==='bar'){document.getElementById('chartContainer').style.display='block';document.getElementById('pieChartsContainer').style.display='none';document.getElementById('pieChartsContainer').innerHTML='';var ds=chartYears.map(function(y,i){return{label:String(y),data:fd.map(function(d){return d[y]||0}),backgroundColor:getColor(i)}});chart=new Chart(document.getElementById('teProvisionChart'),{type:'bar',data:{labels:fd.map(function(d){return d.label}),datasets:ds},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top',labels:{boxWidth:12,padding:10,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+Number(ctx.raw).toLocaleString()}}}},scales:{x:{ticks:{font:{size:10}}},y:{beginAtZero:true,ticks:{callback:function(v){return formatValue(v)},font:{size:11}}}}}})}else if(currentChartType==='pie'){document.getElementById('chartContainer').style.display='none';document.getElementById('pieChartsContainer').style.display='flex';document.getElementById('pieChartsContainer').innerHTML='';chartYears.forEach(function(year){var yd=fd.map(function(d){return{label:d.label,value:d[year]||0}}).filter(function(i){return i.value>0});if(yd.length===0)return;var col=document.createElement('div');col.className='col-lg-4 col-md-6 col-sm-12';var ctn=document.createElement('div');ctn.className='pie-chart-container';var cv=document.createElement('canvas');ctn.appendChild(cv);col.appendChild(ctn);document.getElementById('pieChartsContainer').appendChild(col);var p=new Chart(cv,{type:'pie',data:{labels:yd.map(function(i){return i.label}),datasets:[{data:yd.map(function(i){return i.value}),backgroundColor:yd.map(function(_,i){return getColor(i)})}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'right',labels:{boxWidth:8,padding:4,font:{size:9}}},title:{display:true,text:'Year '+year,font:{size:11},padding:{top:4,bottom:2}},tooltip:{callbacks:{label:function(ctx){var total=ctx.dataset.data.reduce(function(a,b){return a+b},0);return ctx.label+': '+Number(ctx.raw).toLocaleString()+' ('+((ctx.raw/total)*100).toFixed(1)+'%)'}}}}}});pieCharts.push(p)})}}
document.querySelectorAll('.chart-type-btn').forEach(function(b){b.addEventListener('click',function(){setChartType(this.getAttribute('data-type'))})});
renderChart();
document.getElementById('exportPdfBtn')?.addEventListener('click',function(){var btn=this;btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';s.onload=function(){var s2=document.createElement('script');s2.src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';s2.onload=function(){var {jsPDF}=window.jspdf;var pdf=new jsPDF('l','mm','a4');var pw=pdf.internal.pageSize.getWidth();switchChartType('bar').then(function(){setTimeout(function(){html2canvas(document.getElementById('reportContent'),{scale:2,backgroundColor:'#ffffff'}).then(function(tc){var td=tc.toDataURL('image/png');var tp=pdf.getImageProperties(td);var tw=pw-20;var th=(tp.height*tw)/tp.width;pdf.addImage(td,'PNG',10,10,tw,th);var cc=document.querySelector('#chartContainer canvas');if(cc){setTimeout(function(){var md=cc.toDataURL('image/png');pdf.addPage();var mp=pdf.getImageProperties(md);var mw=pw-20;var mh=(mp.height*mw)/mp.width;pdf.addImage(md,'PNG',10,10,mw,mh);pdf.save('TE_SEZ_Dev_by_Provision_<?= $from_year ?>-<?= $to_year ?>.pdf');btn.disabled=false;btn.innerHTML='<i class="fas fa-file-pdf me-2"></i> Export PDF'},800)}else{pdf.save('TE_SEZ_Dev_by_Provision_<?= $from_year ?>-<?= $to_year ?>.pdf');btn.disabled=false;btn.innerHTML='<i class="fas fa-file-pdf me-2"></i> Export PDF'}})},500)})};document.head.appendChild(s2)};document.head.appendChild(s)});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
