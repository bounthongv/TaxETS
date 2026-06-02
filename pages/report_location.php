<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$errors = [];

// --- Province -> hc-key mapping for Laos Highmaps ---
$provinceHcKey = [
    'vientiane capital' => 'la-vt', 'ນະຄອນຫຼວງວຽງຈັນ' => 'la-vt',
    'phongsaly' => 'la-ph', 'ຜົ້ງສາລີ' => 'la-ph',
    'luangnamtha' => 'la-lm', 'ຫຼວງນໍ້າທາ' => 'la-lm',
    'oudomxai' => 'la-ou', 'ອຸດົມໄຊ' => 'la-ou', 'oudomxay' => 'la-ou',
    'bokeo' => 'la-bk', 'ບໍ່ແກ້ວ' => 'la-bk',
    'luangprabang' => 'la-lp', 'ຫຼວງພະບາງ' => 'la-lp',
    'houaphanh' => 'la-ho', 'ຫົວພັນ' => 'la-ho', 'huaphanh' => 'la-ho',
    'xaiyabouly' => 'la-xa', 'ໄຊຍະບູລີ' => 'la-xa', 'sayaboury' => 'la-xa',
    'xiengkhouang' => 'la-xi', 'xiangkhouang' => 'la-xi', 'ຊຽງຂວາງ' => 'la-xi',
    'vientiane' => 'la-vi', 'vientiane province' => 'la-vi', 'ວຽງຈັນ' => 'la-vi',
    'bolikhamxai' => 'la-bl', 'bolikhamsai' => 'la-bl', 'borikhamxay' => 'la-bl', 'ບໍລິຄໍາໄຊ' => 'la-bl',
    'khammouane' => 'la-kh', 'khamouane' => 'la-kh', 'ຄໍາມ່ວນ' => 'la-kh',
    'savannakhet' => 'la-sv', 'ສະຫວັນນະເຂດ' => 'la-sv',
    'saravanh' => 'la-sl', 'ສາລະວັນ' => 'la-sl',
    'sekong' => 'la-xe', 'xekong' => 'la-xe', 'ເຊກອງ' => 'la-xe',
    'champasak' => 'la-ch', 'champassak' => 'la-ch', 'ຈໍາປາສັກ' => 'la-ch',
    'attapeu' => 'la-at', 'ອັດຕະປື' => 'la-at',
    'xaisomboune' => 'la-xs', 'xaisomboun' => 'la-xs', 'ໄຊສົມບູນ' => 'la-xs',
];
function getHcKey($provinceName) {
    global $provinceHcKey;
    $key = trim(mb_strtolower($provinceName));
    return $provinceHcKey[$key] ?? null;
}

// ===================================================================
// 1. Fetch all available years
// ===================================================================
$all_years = [];
try {
    $year_queries = [
        "SELECT DISTINCT tax_year FROM companies WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM te_individual_result WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM import_salary_tax_data WHERE tax_year > 0",
        "SELECT DISTINCT YEAR(filing_period) as yr FROM import_vat_data WHERE filing_period IS NOT NULL AND filing_period != '0000-00-00'",
        "SELECT DISTINCT YEAR(doc_date) as yr FROM asycuda_imports WHERE doc_date IS NOT NULL AND doc_date != '0000-00-00'",
        "SELECT DISTINCT tax_year FROM import_sez_data WHERE tax_year > 0",
        "SELECT DISTINCT tax_year FROM te_land_concession_result r JOIN companies c ON r.company_id = c.id WHERE c.tax_year > 0",
    ];
    foreach ($year_queries as $q) {
        $stmt = $pdo->query($q);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            if ($row[0] > 1900 && $row[0] < 2100) $all_years[] = (int)$row[0];
        }
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

// ===================================================================
// 2. Fetch all provinces
// ===================================================================
$provinces = [];
try {
    $p_queries = [
        "SELECT DISTINCT province FROM companies WHERE province IS NOT NULL AND province != ''",
        "SELECT DISTINCT province FROM import_vat_data WHERE province IS NOT NULL AND province != ''",
        "SELECT DISTINCT province FROM asycuda_imports WHERE province IS NOT NULL AND province != ''",
    ];
    foreach ($p_queries as $q) {
        $stmt = $pdo->query($q);
        while ($row = $stmt->fetchColumn()) {
            $provinces[] = trim($row);
        }
    }
    $provinces = array_unique($provinces);
    sort($provinces);
} catch (Exception $e) { $errors[] = $e->getMessage(); }

// ===================================================================
// 3. Aggregate data matrix [province][year] and unclassified row
// ===================================================================
$matrix = [];
$other_total = [];

try {
    // A. CIT (Profit Tax) by Province
    $stmt = $pdo->prepare("SELECT c.province, c.tax_year, SUM(r.profit_tax_te) as te
                           FROM companies c JOIN te_profit_result r ON c.id = r.company_id
                           WHERE c.tax_year BETWEEN ? AND ? GROUP BY c.province, c.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['province']);
        $yr = (int)$row['tax_year'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // B. PIT by Province (via TIN)
    $stmt = $pdo->prepare("SELECT COALESCE(c.province, '') as loc, r.tax_year, SUM(r.te_amount) as te
                           FROM te_individual_result r LEFT JOIN companies c ON r.tin = c.tin COLLATE utf8mb4_general_ci
                           WHERE r.tax_year BETWEEN ? AND ? GROUP BY loc, r.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['loc']);
        $yr = (int)$row['tax_year'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // C. Salary Tax by Province (via TIN)
    $stmt = $pdo->prepare("SELECT COALESCE(c.province, '') as loc, s.tax_year, SUM(s.te_amount) as te
                           FROM import_salary_tax_data s LEFT JOIN companies c ON s.tin = c.tin COLLATE utf8mb4_unicode_ci
                           WHERE s.tax_year BETWEEN ? AND ? AND s.te_amount > 0 GROUP BY loc, s.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['loc']);
        $yr = (int)$row['tax_year'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // D. SEZ Developer by Province
    $stmt = $pdo->prepare("SELECT COALESCE(s.province, '') as loc, s.tax_year, SUM(s.te_amount) as te
                           FROM import_sez_data s WHERE s.type = 'Developer' AND s.tax_year BETWEEN ? AND ? AND s.te_amount > 0
                           GROUP BY loc, s.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['loc']);
        $yr = (int)$row['tax_year'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // E. SEZ Investor by Province
    $stmt = $pdo->prepare("SELECT COALESCE(s.province, '') as loc, s.tax_year, SUM(s.te_amount) as te
                           FROM import_sez_data s WHERE s.type = 'Investor' AND s.tax_year BETWEEN ? AND ? AND s.te_amount > 0
                           GROUP BY loc, s.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['loc']);
        $yr = (int)$row['tax_year'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // F. Land Concession by Province
    $stmt = $pdo->prepare("SELECT COALESCE(c.province, '') as loc, c.tax_year, SUM(r.te_land_concession) as te
                           FROM te_land_concession_result r JOIN companies c ON r.company_id = c.id
                           WHERE c.tax_year BETWEEN ? AND ? GROUP BY loc, c.tax_year");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['loc']);
        $yr = (int)$row['tax_year'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // G. Domestic VAT -> Other / Unclassified (no province link)
    $stmt = $pdo->prepare("SELECT province, YEAR(filing_period) as yr, SUM(expert_te) as te
                           FROM import_vat_data WHERE YEAR(filing_period) BETWEEN ? AND ? GROUP BY province, yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['province']);
        $yr = (int)$row['yr'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // H. ASYCUDA by Province
    $stmt = $pdo->prepare("SELECT ai.province, YEAR(ai.doc_date) as yr, SUM(r.total_te) as te
                           FROM te_asycuda_result r JOIN asycuda_imports ai ON r.asycuda_id = ai.id
                           WHERE YEAR(ai.doc_date) BETWEEN ? AND ? GROUP BY ai.province, yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = trim($row['province']);
        $yr = (int)$row['yr'];
        if ($loc) { $matrix[$loc][$yr] = ($matrix[$loc][$yr] ?? 0) + (float)$row['te']; }
        else { $other_total[$yr] = ($other_total[$yr] ?? 0) + (float)$row['te']; }
    }

    // I. Resource Fee -> Other
    $stmt = $pdo->prepare("SELECT tax_year as yr, SUM(te_amount) as te FROM import_resource_data
                           WHERE tax_year BETWEEN ? AND ? AND te_amount > 0 GROUP BY yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

    // J. Royalty Fee -> Other
    $stmt = $pdo->prepare("SELECT tax_year as yr, SUM(te_amount) as te FROM import_royalty_data
                           WHERE tax_year BETWEEN ? AND ? AND te_amount > 0 GROUP BY yr");
    $stmt->execute([$from_year, $to_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_total[$row['yr']] = ($other_total[$row['yr']] ?? 0) + (float)$row['te'];
    }

} catch (Exception $e) { $errors[] = $e->getMessage(); }

// ===================================================================
// 4. Map data — row total across all selected years
// ===================================================================
$mapDataForJson = [];
$provinceDataForChart = [];
foreach ($provinces as $province) {
    $hcKey = getHcKey($province);
    $item = [
        'province_name' => $province,
        'hc_key' => $hcKey,
    ];
    $rowTotal = 0;
    foreach ($display_years as $y) {
        $v = (float)($matrix[$province][$y] ?? 0);
        $item[(string)$y] = $v;
        $rowTotal += $v;
    }
    $provinceDataForChart[] = $item;
    if ($hcKey) {
        $mapDataForJson[] = [
            'hc_key' => $hcKey,
            'name' => $province,
            'value' => $rowTotal > 0 ? $rowTotal : null,
        ];
    }
}
$mapDataJson = json_encode($mapDataForJson);

// ===================================================================
// 5. Excel export handler
// ===================================================================
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("TE by Province");

    $sheet->mergeCells('A1:' . chr(65 + count($display_years)) . '1');
    $sheet->setCellValue('A1', "Tax Expenditure by Province ({$from_year} - {$to_year})");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $headerRow = 3;
    $sheet->setCellValue("A{$headerRow}", 'Province');
    $colIdx = 2;
    foreach ($display_years as $y) {
        $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, (string)$y);
        $colIdx++;
    }
    $sheet->setCellValueByColumnAndRow($colIdx, $headerRow, 'Row Total');
    $sheet->getStyle("A{$headerRow}:" . $sheet->getCellByColumnAndRow($colIdx, $headerRow)->getColumn() . "{$headerRow}")->getFont()->setBold(true);

    $rowIdx = 4;
    $colTotals = [];
    foreach ($provinces as $province) {
        $sheet->setCellValue("A{$rowIdx}", $province);
        $cIdx = 2;
        $rowTotal = 0;
        foreach ($display_years as $y) {
            $val = $matrix[$province][$y] ?? 0;
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
            $rowTotal += $val;
            $colTotals[$y] = ($colTotals[$y] ?? 0) + $val;
            $cIdx++;
        }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $rowTotal);
        $rowIdx++;
    }

    if (array_sum($other_total) > 0) {
        $sheet->setCellValue("A{$rowIdx}", 'Unclassified / Other');
        $cIdx = 2;
        $unRowTotal = 0;
        foreach ($display_years as $y) {
            $val = $other_total[$y] ?? 0;
            $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $val);
            $unRowTotal += $val;
            $colTotals[$y] = ($colTotals[$y] ?? 0) + $val;
            $cIdx++;
        }
        $sheet->setCellValueByColumnAndRow($cIdx, $rowIdx, $unRowTotal);
        $rowIdx++;
    }

    $sheet->setCellValue("A{$rowIdx}", 'NATIONAL TOTAL');
    $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
    $cIdx = 2;
    $grandTotal = 0;
    foreach ($display_years as $y) {
        $val = $colTotals[$y] ?? 0;
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
    header('Content-Disposition: attachment; filename="TE_by_Province_' . $from_year . '-' . $to_year . '.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ===================================================================
// 6. Render
// ===================================================================
?>
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold text-dark"><i class="fas fa-map-marked-alt me-2 text-primary"></i> Tax Expenditure by Location (Province)</h2>
        <p class="text-muted">Geographic breakdown of Tax Expenditures across the country. Data aggregated from all Tax Modules.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="?export=1&from_year=<?= $from_year ?>&to_year=<?= $to_year ?>" class="btn btn-success shadow-sm"><i class="fas fa-file-excel me-2"></i> Export Excel</a>
        <button id="exportPdfBtn" class="btn btn-danger shadow-sm"><i class="fas fa-file-pdf me-2"></i> Export PDF</button>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger shadow-sm"><?= htmlspecialchars(implode('; ', $errors)) ?></div>
<?php endif; ?>

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body bg-light rounded-3">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">From Year</label>
                <select name="from_year" class="form-select border-0 shadow-sm">
                    <?php foreach ($all_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $from_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">To Year</label>
                <select name="to_year" class="form-select border-0 shadow-sm">
                    <?php foreach ($all_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $to_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-2"></i> Update</button>
            </div>
            <div class="col-md-3">
                <a href="report_location.php" class="btn btn-outline-secondary w-100 border-0">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================================== -->
<!-- Data Table -->
<!-- ===================================================================== -->
<div id="reportContent" class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 matrix-table">
                <thead class="bg-primary text-white">
                    <tr class="align-middle">
                        <th class="ps-4 py-3" style="min-width: 280px;">Province</th>
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

                    <?php if (array_sum($other_total) > 0): ?>
                    <tr class="table-warning bg-opacity-10 border-top">
                        <td class="ps-4 fw-bold">Unclassified / Other <i class="fas fa-question-circle ms-1 text-muted small" title="Data without province attribution."></i></td>
                        <?php
                        $un_row_total = 0;
                        foreach ($display_years as $year):
                            $val = $other_total[$year] ?? 0;
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

<!-- ===================================================================== -->
<!-- Laos Map Card -->
<!-- ===================================================================== -->
<div class="card shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <h5 class="fw-bold mb-3"><i class="fas fa-map me-2 text-primary"></i> TE Value by Province (<?= $from_year ?>–<?= $to_year ?> Total)</h5>
        <div id="mapContainer" style="width:100%; height:520px;"></div>
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

<script src="https://code.highcharts.com/maps/highmaps.js"></script>
<script src="https://code.highcharts.com/mapdata/countries/la/la-all.js"></script>
<script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
<script>
var mapData = <?= $mapDataJson ?>;

// Initialize Highmaps
var mapChart = Highcharts.mapChart('mapContainer', {
    chart: {
        map: 'countries/la/la-all',
        height: 500,
        animation: false,
    },
    title: { text: '' },
    mapNavigation: {
        enabled: true,
        buttonOptions: { verticalAlign: 'bottom' }
    },
    colorAxis: {
        min: 0,
        type: 'linear',
        stops: [
            [0, '#E6F3FF'],
            [0.25, '#B3D9FF'],
            [0.5, '#80BFFF'],
            [0.75, '#4DA6FF'],
            [1, '#0073E6']
        ],
        labels: {
            formatter: function() {
                if (this.value >= 1e12) return (this.value / 1e12).toFixed(1) + 'T';
                if (this.value >= 1e9) return (this.value / 1e9).toFixed(1) + 'B';
                if (this.value >= 1e6) return (this.value / 1e6).toFixed(1) + 'M';
                if (this.value >= 1e3) return (this.value / 1e3).toFixed(1) + 'K';
                return this.value.toFixed(0);
            }
        }
    },
    legend: {
        layout: 'vertical',
        align: 'left',
        verticalAlign: 'bottom',
        valueDecimals: 0
    },
    tooltip: {
        formatter: function() {
            var v = this.point.value;
            if (v === null || v === undefined) return '<b>' + this.point.name + '</b><br/>No data';
            var fmt = Number(v).toLocaleString();
            return '<b>' + this.point.name + '</b><br/>TE Value: ' + fmt;
        }
    },
    plotOptions: {
        map: {
            states: {
                hover: { color: '#0052CC' }
            }
        }
    },
    series: [{
        data: mapData,
        name: 'TE Value',
        joinBy: ['hc-key', 'hc_key'],
        dataLabels: {
            enabled: true,
            format: '{point.name}',
            style: { fontSize: '10px', textOutline: '2px contrast' }
        }
    }]
});


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

    // Capture table card
    html2canvas(document.getElementById('reportContent'), {
        scale: 2,
        backgroundColor: '#ffffff'
    }).then(function(tableCanvas) {
        var tableImgData = tableCanvas.toDataURL('image/png');
        var tableImgProps = pdf.getImageProperties(tableImgData);
        var tableWidth = pageWidth - 20;
        var tableHeight = (tableImgProps.height * tableWidth) / tableImgProps.width;
        pdf.addImage(tableImgData, 'PNG', 10, 10, tableWidth, tableHeight);

        // Capture map canvas
        var mapCanvas = document.querySelector('#mapContainer .highcharts-container canvas');
        if (mapCanvas) {
            // Ensure white background
            var ctx = mapCanvas.getContext('2d');
            ctx.globalCompositeOperation = 'destination-over';
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, mapCanvas.width, mapCanvas.height);

            setTimeout(function() {
                var mapImgData = mapCanvas.toDataURL('image/png');
                pdf.addPage();
                var mapImgProps = pdf.getImageProperties(mapImgData);
                var mapWidth = pageWidth - 20;
                var mapHeight = (mapImgProps.height * mapWidth) / mapImgProps.width;
                pdf.addImage(mapImgData, 'PNG', 10, 10, mapWidth, mapHeight);
                pdf.save('TE_by_Province_<?= $from_year ?>-<?= $to_year ?>.pdf');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i> Export PDF';
            }, 500);
        } else {
            // Fallback: html2canvas the mapContainer
            html2canvas(document.getElementById('mapContainer'), {
                scale: 2,
                backgroundColor: '#ffffff',
                allowTaint: true,
                useCORS: true
            }).then(function(mapCanvas2) {
                var mapImgData = mapCanvas2.toDataURL('image/png');
                pdf.addPage();
                var mapImgProps = pdf.getImageProperties(mapImgData);
                var mapWidth = pageWidth - 20;
                var mapHeight = (mapImgProps.height * mapWidth) / mapImgProps.width;
                pdf.addImage(mapImgData, 'PNG', 10, 10, mapWidth, mapHeight);
                pdf.save('TE_by_Province_<?= $from_year ?>-<?= $to_year ?>.pdf');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i> Export PDF';
            });
        }
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
