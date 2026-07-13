<?php
/**
 * Domestic VAT Data Import — Expert-confirmed template v1.0 (17 cols A-Q)
 * =====================================================================
 * Menu: Data Requirement to estimate TE > Domestic VAT
 * Template: vat-template-apis-standard.xlsx (17 columns A-Q)
 * Engine: te_vat.php (Domestic VAT TE calculation)
 *
 * Column mapping (A-Q):
 *   A  Province               → province
 *   B  TIN                    → tin
 *   C  Company Name           → name
 *   D  Filing Type            → filing_type
 *   E  Filing Period          → filing_period
 *   F  Input Date             → input_date
 *   G  Description            → description
 *   H  VAT Rate %             → vat_rate
 *   I  Domestic Sale Exempt   → sales_exempt
 *   J  User TE                → user_te
 *   K  Provision Number       → provision_number
 *   L  User Benchmark Rate    → user_benchmark_rate
 *   M  User Benchmark VAT     → user_benchmark_vat
 *   N  Use User Fallback?     → use_user_fallback (Yes/No → 1/0)
 *   O  System Benchmark Rate %→ system_benchmark_rate
 *   P  User Fallback Reason   → user_fallback_reason
 *   Q  User Comment           → user_comment
 *
 * Old template columns (sales_standard, sales_zero_rate, total_input_vat,
 * vat_payable, vat_credit, purchase_*) are kept in the DB for backward
 * compatibility with pre-v1.0 imports but are no longer written.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();
$message = '';
$msg_type = 'success';

// --- Pre-load Province & District Maps ---
$prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
$dist_rows = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id")->fetchAll();

$prov_map = [];
foreach ($prov_rows as $r) {
    $prov_map[strtoupper(trim($r['pro_name']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']];
}
$prov_aliases = [
    'VIENTIANE'                => '01',
    'VIENTIANE CAPITAL'        => '01',
    'VIENTIANE CAPITAL PROVINCE' => '01',
    'VIENTIANE PREFECTURE'     => '01',
    'NAXAYTHONG'               => '01',
    'PHONGSALY'                => '02',
    'PHONGSALI'                => '02',
    'LUANGNAMTHA'              => '03',
    'LUANG NAMTHA'             => '03',
    'LUANGNAMTA'               => '03',
    'OUDOMXAY'                 => '04',
    'OUDOMXAI'                 => '04',
    'UDOMXAY'                  => '04',
    'BOKEO'                    => '05',
    'LUANGPRABANG'             => '06',
    'LUANGPHRABANG'            => '06',
    'LUANG PRABANG'            => '06',
    'LUANG PHRA BANG'          => '06',
    'LUANGPHRABANG'            => '06',
    'HUAPHANH'                 => '07',
    'HUAPHAN'                  => '07',
    'HOUAPHAN'                 => '07',
    'HOUAPHANH'                => '07',
    'SAYABOURY'                => '08',
    'SAYABURI'                 => '08',
    'XAYABOURY'                => '08',
    'XIANGKHOUANG'             => '09',
    'XIENGKHOUANG'             => '09',
    'XIENG KHOUNG'             => '09',
    'XIANG KHOANG'             => '09',
    'VIENTIANE PROVINCE'       => '10',
    'BOLIKHAMSAI'              => '11',
    'BOLIKHAMXAI'              => '11',
    'BORIKHAMXAY'              => '11',
    'BORIKHAMXAI'              => '11',
    'KHAMOUANE'                => '12',
    'KHAMMOUANE'               => '12',
    'KHAMMUANE'                => '12',
    'SAVANNAKHET'              => '13',
    'SAVANAKHET'               => '13',
    'SARAVANH'                 => '14',
    'SARAVANE'                 => '14',
    'SEKONG'                   => '15',
    'XEKONG'                   => '15',
    'CHAMPASAK'                => '16',
    'CHAMPASSAK'               => '16',
    'CHAMPASSACK'              => '16',
    'ATTAPEU'                  => '17',
    'ATTAPU'                   => '17',
    'ATTAPUE'                  => '17',
    'XAISOMBOUN'               => '18',
    'XAYSOMBOUN'               => '18',
    'XAISOMBOU'                => '18',
];
$dist_map = [];
$dist_by_province = [];
foreach ($dist_rows as $r) {
    $dist_map[$r['pro_id'] . '|' . strtoupper(trim($r['dis_name']))] = $r['dis_id'];
    $dist_by_province[$r['pro_id']][] = ['dis_id' => $r['dis_id'], 'name' => strtoupper(trim($r['dis_name']))];
}

// --- Helper functions ---
$cleanNum = function ($val) {
    if (is_numeric($val)) return (float)$val;
    return (float)str_replace(',', '', (string)($val ?? '0'));
};

$parseDate = function ($val) {
    if (empty($val)) return null;
    if (is_numeric($val) && strlen((string)$val) === 4) return (int)$val . '-01-01';
    if (is_numeric($val) && (float)$val > 10000) {
        try { return Date::excelToDateTimeObject($val)->format('Y-m-d'); } catch (Exception $e) {}
    }
    if (preg_match('/^(\d{4})[-|\/](\d{1,2})$/', (string)$val, $m)) return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-01';
    if (preg_match('/^(\d{1,2})[-|\/](\d{4})$/', (string)$val, $m)) return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
    $ts = strtotime((string)$val);
    if ($ts) return date('Y-m-d', $ts);
    return null;
};

$resolveProvince = function ($raw) use ($prov_map, $prov_aliases, $prov_rows, &$error_log) {
    $raw_prov = trim($raw ?? '');
    if (empty($raw_prov)) return [null, null, null];
    // Strip "code | " prefix from dropdown (e.g., "01 | Vientiane Capital" → "Vientiane Capital")
    $raw_prov = preg_replace('/^\d+\s*\|\s*/', '', $raw_prov);
    $stripped = preg_replace('/\s+(Province|Prefecture)\s*$/i', '', $raw_prov);
    if (strtoupper($stripped) !== 'VIENTIANE') $raw_prov = $stripped;
    $upper = strtoupper($raw_prov);
    $match = $prov_map[$upper] ?? null;
    if (!$match && isset($prov_aliases[$upper])) {
        $alias_id = $prov_aliases[$upper];
        foreach ($prov_rows as $pr) {
            if ($pr['pro_id'] == $alias_id) { $match = ['pro_id' => $pr['pro_id'], 'name' => $pr['pro_name']]; break; }
        }
    }
    if (!$match && strlen($upper) >= 3) {
        $best_score = 999; $best_match = null;
        foreach ($prov_map as $pname => $pdata) {
            $score = levenshtein($upper, $pname);
            if ($score < $best_score) { $best_score = $score; $best_match = $pdata; }
        }
        if ($best_score <= 3 && $best_match) $match = $best_match;
    }
    $pro_id = $match['pro_id'] ?? null;
    if (!$pro_id && !empty($raw_prov)) {
        $error_log[] = "Unknown Province '$raw_prov'";
    }
    return [$match['name'] ?? $raw_prov, $pro_id, $pro_id ? $match['name'] : null];
};

// --- Handle Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        $file = $_FILES['excel_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error.');
        if (!in_array(pathinfo($file['name'], PATHINFO_EXTENSION), ['xlsx', 'xls'])) {
            throw new Exception('Invalid file type.');
        }

        $spreadsheet = IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, false, true);
        $batch_id = 'VAT_BATCH_' . date('YmdHis');
        $inserted = 0;
        $skipped = 0;
        $unmapped_prov = 0;
        $error_log = [];
        $empty_streak = 0;

        // Headers in row 1, data from row 2+
        foreach ($data as $index => $row) {
            if ($index <= 1) continue;

            $tin = trim($row['B'] ?? '');

            // Empty-row detection (cols A-I)
            $is_empty = true;
            foreach (range('A', 'I') as $col) {
                if (trim((string)($row[$col] ?? '')) !== '') { $is_empty = false; break; }
            }
            if ($is_empty) {
                $empty_streak++;
                if ($empty_streak >= 20) break;
                continue;
            }
            $empty_streak = 0;

            if (empty($tin)) {
                $skipped++;
                $error_log[] = "Row $index: TIN is required";
                continue;
            }

            // Province mapping
            $raw_prov = trim($row['A'] ?? '');
            [$official_province, $pro_id, $matched_name] = $resolveProvince($raw_prov);
            if (!$pro_id && !empty($raw_prov)) $unmapped_prov++;

            $period = $parseDate($row['E'] ?? '');
            $input_date = $parseDate($row['F'] ?? '');

            // Use User Fallback (Yes/No → 1/0)
            $fb = strtolower(trim($row['N'] ?? ''));
            $use_fallback = ($fb === 'yes' || $fb === '1' || $fb === 'y') ? 1 : 0;

            $record = [
                'batch_id'              => $batch_id,
                'province'              => $official_province,
                'tin'                   => $tin,
                'name'                  => trim($row['C'] ?? ''),
                'filing_type'           => trim($row['D'] ?? ''),
                'filing_period'         => $period,
                'input_date'            => $input_date,
                'description'           => trim($row['G'] ?? ''),
                'vat_rate'              => $cleanNum($row['H'] ?? ''),
                'sales_exempt'          => $cleanNum($row['I'] ?? 0),
                'user_te'               => $cleanNum($row['J'] ?? ''),
                'provision_number'      => trim($row['K'] ?? ''),
                'user_benchmark_rate'   => $cleanNum($row['L'] ?? ''),
                'user_benchmark_vat'    => $cleanNum($row['M'] ?? ''),
                'use_user_fallback'     => $use_fallback,
                'system_benchmark_rate' => $cleanNum($row['O'] ?? ''),
                'user_fallback_reason'  => trim($row['P'] ?? ''),
                'user_comment'          => trim($row['Q'] ?? ''),
            ];

            $cols = implode(', ', array_keys($record));
            $ph   = implode(', ', array_fill(0, count($record), '?'));
            $pdo->prepare("INSERT INTO import_vat_data ($cols) VALUES ($ph)")->execute(array_values($record));
            $inserted++;
        }

        if ($inserted > 0) {
            $message = "<strong>Import Success!</strong> Imported $inserted records.<br>";
        } else {
            $message = "<strong>No Domestic VAT records imported.</strong> Add data rows to the template and upload again.<br>";
            $msg_type = 'warning';
        }
        if ($skipped > 0) {
            $message .= "Warning: Skipped $skipped row(s) with data but no TIN.<br>";
        }
        $message .= $unmapped_prov === 0
            ? 'All provinces mapped.<br>'
            : "Warning: $unmapped_prov row(s) have unknown province names. Review the imported data and error log.<br>";
        $message .= 'Processed up to row ' . ($index - 1) . ' of ' . count($data) . " total rows in sheet.<br>";
        if ($inserted > 0) {
            $message .= "<br><a href='view_vat.php?batch=$batch_id' class='btn btn-sm btn-outline-primary mt-2 me-2'><i class='fas fa-eye me-1'></i> View Imported Data</a>";
        }

        if (!empty($error_log)) {
            $msg_type = 'warning';
            $log_content = "DOMESTIC VAT IMPORT DIAGNOSTIC LOG - " . date("Y-m-d H:i:s") . "\r\n";
            $log_content .= "Batch: $batch_id\r\n";
            $log_content .= "----------------------------------------\r\n";
            $log_content .= implode("\r\n", $error_log);
            if (!is_dir(__DIR__ . '/../data/logs')) mkdir(__DIR__ . '/../data/logs', 0777, true);
            file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);
            $message .= "<a href='download_log.php?log_id=$batch_id' target='_blank' class='btn btn-sm btn-outline-danger mt-2'><i class='fas fa-download me-1'></i> Download Error Log</a>";
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $msg_type = 'danger';
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT batch_id, COUNT(*) AS `rows`, MAX(id) AS lid FROM import_vat_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-invoice me-2 text-primary"></i> Domestic VAT Data Import</h2>
    <p class="text-muted">Upload monthly VAT filing data from the expert-confirmed Domestic VAT template (v1.0, 17 columns A-Q).</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-start border-4 border-<?= $msg_type ?>">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-md-5">
    <div class="card shadow-sm border-0 border-top border-4 border-primary">
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-primary"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-bold mb-0">Excel File (.xlsx)</label>
                <a href="generate_domestic_vat_template.php" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-bold small"><i class="fas fa-download me-1"></i> Download Template</a>
            </div>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import Batch</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3 shadow-sm">
      <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-table me-2"></i> Expert-Confirmed Column Mapping (v1.0)</div>
      <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light">
            <tr><th>Col</th><th>Field</th><th>Group</th></tr>
          </thead>
          <tbody>
            <tr style="background:#D6E0F0"><td>A</td><td>Province</td><td><span class="badge bg-dark">Primary Required</span></td></tr>
            <tr style="background:#D6E0F0"><td>B</td><td>TIN</td><td><span class="badge bg-dark">Primary Required</span></td></tr>
            <tr><td>C</td><td>Company Name</td><td><span class="badge bg-secondary">Primary Optional</span></td></tr>
            <tr><td>D</td><td>Filing Type</td><td><span class="badge bg-secondary">Primary Optional</span></td></tr>
            <tr style="background:#D6E0F0"><td>E</td><td>Filing Period</td><td><span class="badge bg-dark">Primary Required</span></td></tr>
            <tr><td>F</td><td>Input Date</td><td><span class="badge bg-secondary">Primary Optional</span></td></tr>
            <tr><td>G</td><td>Description</td><td><span class="badge bg-secondary">Primary Optional</span></td></tr>
            <tr><td>H</td><td>VAT Rate %</td><td><span class="badge bg-secondary">Primary Optional</span></td></tr>
            <tr style="background:#D6E0F0"><td>I</td><td>Domestic Sale Exempt LAK</td><td><span class="badge bg-dark">Primary Required</span></td></tr>
            <tr style="background:#FCE4D6"><td>J</td><td>User TE</td><td><span class="badge" style="background:#C65911;color:#fff">User Fallback</span></td></tr>
            <tr style="background:#D6E0F0"><td>K</td><td>Provision Number</td><td><span class="badge bg-dark">Primary Required</span></td></tr>
            <tr style="background:#FCE4D6"><td>L</td><td>User Benchmark Rate</td><td><span class="badge" style="background:#C65911;color:#fff">User Fallback</span></td></tr>
            <tr style="background:#FCE4D6"><td>M</td><td>User Benchmark VAT</td><td><span class="badge" style="background:#C65911;color:#fff">User Fallback</span></td></tr>
            <tr style="background:#FCE4D6"><td>N</td><td>Use User Fallback?</td><td><span class="badge" style="background:#C65911;color:#fff">User Fallback</span></td></tr>
            <tr><td>O</td><td>System Benchmark Rate %</td><td><span class="badge bg-secondary">Primary Optional</span></td></tr>
            <tr style="background:#FCE4D6"><td>P</td><td>User Fallback Reason</td><td><span class="badge" style="background:#C65911;color:#fff">User Fallback</span></td></tr>
            <tr style="background:#FCE4D6"><td>Q</td><td>User Comment</td><td><span class="badge" style="background:#C65911;color:#fff">User Fallback</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No Domestic VAT Data Found</h5>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch / Source</th><th>Records</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <?php
                $is_manual = (strpos($r['batch_id'], 'MANUAL') !== false);
                $log_file = __DIR__ . '/../data/logs/' . $r['batch_id'] . '.log';
                $has_log = file_exists($log_file);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace"><?= htmlspecialchars($r['batch_id']) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><span class="badge bg-primary rounded-pill px-3"><?= $r['rows'] ?></span></td>
                <td>
                  <a href="view_vat.php?batch=<?= urlencode($r['batch_id']) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_vat.php?batch=<?= urlencode($r['batch_id']) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r['batch_id']) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="vat">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r['batch_id']) ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manual Data Entry for Domestic VAT</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Select the Tax Year for the manual records you want to manage.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Tax Year</label>
          <select id="manualTaxYear" class="form-select">
            <?php for ($y = date('Y'); $y >= 2015; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="goToManualEntry()">Manage Records</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', function() {
    let btn = document.getElementById('importBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Importing...';
    btn.classList.add('disabled');
});

function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    window.location.href = `view_vat.php?batch=MANUAL_ENTRY_VAT_${year}_${stamp}&auto_add=1&year=${year}`;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
