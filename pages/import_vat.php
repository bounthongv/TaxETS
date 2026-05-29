<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// --- Pre-load Dictionary for Smart Mapping ---
$prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
$dist_rows = $pdo->query("SELECT d.district_code AS dis_id, p.province_code AS pro_id, d.district_name AS dis_name FROM districts d LEFT JOIN provinces p ON d.province_id = p.id")->fetchAll();

$prov_map = [];
foreach ($prov_rows as $r) {
    $prov_map[strtoupper(trim($r['pro_name']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']];
}
$prov_aliases = [
    'BOLIKHAMSAI'  => '11', 'BOLIKHAMXAI'  => '11', 'BORIKHAMXAY'  => '11',
    'XIANGKHOUANG' => '09', 'XIENGKHOUANG' => '09',
    'VIENTIANE'    => '01', 'BOKEO'        => '05', 'LUANGPRABANG' => '06',
    'LUANGPHRABANG' => '06', 'LUANGNAMTHA'  => '03', 'OUDOMXAY'     => '04',
    'SAYABOURY'    => '08', 'SAYABURI'     => '08', 'XAYABOURY'    => '08',
    'SARAVANE'     => '14', 'SEKONG'       => '15', 'XEKONG'       => '15',
    'ATTAPEU'      => '17', 'ATTAPU'       => '17', 'XAISOMBOUN'   => '18',
];
$dist_map = [];
$dist_by_province = [];
foreach ($dist_rows as $r) {
    $dist_map[$r['pro_id'] . '|' . strtoupper(trim($r['dis_name']))] = $r['dis_id'];
    $dist_by_province[$r['pro_id']][] = ['dis_id' => $r['dis_id'], 'name' => strtoupper(trim($r['dis_name']))];
}

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    try {
        $file = $_FILES["excel_file"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error.");
        }
        if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
            throw new Exception("Invalid file type.");
        }

        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, false, true);
        $batch_id = "BATCH_" . date("YmdHis");
        $inserted = 0; $skipped = 0;
        $error_log = [];

        $cleanNum = function($val) {
            if (is_numeric($val)) return (float)$val;
            return (float)str_replace(',', '', (string)($val ?? '0'));
        };

        $parseDate = function($val) use (&$error_log, $batch_id) {
            if (empty($val)) return null;
            if (is_numeric($val) && strlen((string)$val) === 4) return (int)$val . "-01-01";
            if (is_numeric($val) && (float)$val > 10000) {
                try { return Date::excelToDateTimeObject($val)->format('Y-m-d'); } catch (Exception $e) {}
            }
            if (preg_match('/^(\d{4})[-|\/](\d{1,2})$/', (string)$val, $m)) return $m[1] . "-" . str_pad($m[2], 2, '0', STR_PAD_LEFT) . "-01";
            if (preg_match('/^(\d{1,2})[-|\/](\d{4})$/', (string)$val, $m)) return $m[2] . "-" . str_pad($m[1], 2, '0', STR_PAD_LEFT) . "-01";
            $ts = strtotime((string)$val);
            if ($ts) return date("Y-m-d", $ts);
            return null;
        };

        foreach ($data as $index => $row) {
            if ($index <= 1) continue; // Skip header rows
            $tin = trim($row["B"] ?? "");
            if (empty($tin)) { $skipped++; continue; }

            // Smart Mapping: Province
            $raw_prov = trim($row["A"] ?? "");
            $upper_prov = strtoupper($raw_prov);
            $prov_match = $prov_map[$upper_prov] ?? null;
            if (!$prov_match && isset($prov_aliases[$upper_prov])) {
                $alias_pro_id = $prov_aliases[$upper_prov];
                foreach ($prov_rows as $pr) {
                    if ($pr['pro_id'] == $alias_pro_id) {
                        $prov_match = ['pro_id' => $pr['pro_id'], 'name' => $pr['pro_name']];
                        break;
                    }
                }
            }
            if (!$prov_match && strlen($upper_prov) >= 3) {
                $best_score = 999; $best_match = null;
                foreach ($prov_map as $pname => $pdata) {
                    $score = levenshtein($upper_prov, $pname);
                    if ($score < $best_score) { $best_score = $score; $best_match = $pdata; }
                }
                if ($best_score <= 3 && $best_match) $prov_match = $best_match;
            }
            $pro_id = $prov_match['pro_id'] ?? null;
            if (!$pro_id && !empty($raw_prov)) {
                $error_log[] = "Row $index: Unknown Province '$raw_prov'";
            }
            $official_province = $prov_match['name'] ?? $raw_prov;

            $period = $parseDate($row["E"] ?? "");
            $input_date = $parseDate($row["F"] ?? "");

            $data = [
                "batch_id"                   => $batch_id,
                "province"                   => $official_province,
                "pro_id"                     => $pro_id,
                "tin"                        => $tin,
                "name"                       => $row["C"] ?? "",
                "filing_type"                => $row["D"] ?? "",
                "filing_period"              => $period,
                "input_date"                 => $input_date,
                "purchase_domestic_nonexempt"=> $cleanNum($row["H"] ?? 0),
                "purchase_domestic_exempt"   => $cleanNum($row["I"] ?? 0),
                "purchase_import_nonexempt"  => 0,
                "purchase_import_exempt"     => 0,
                "total_input_vat"            => 0,
                "sales_standard"             => 0,
                "sales_zero_rate"            => 0,
                "sales_exempt"               => $cleanNum($row["I"] ?? 0),
                "total_output_vat"           => 0,
                "vat_payable"                => 0,
                "vat_credit"                 => 0,
                "expert_te"                  => $cleanNum($row["J"] ?? 0),
                "provision_number"           => $row["AG"] ?? ""
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO import_vat_data ($cols) VALUES ($ph)")->execute(array_values($data));
            $inserted++;
        }

        $message = "<strong>Import Success!</strong> Imported $inserted records.<br>";
        $message .= "Skipped $skipped rows (missing TIN).<br>";

        if (!empty($error_log)) {
            $log_content = "IMPORT DIAGNOSTIC LOG - " . date("Y-m-d H:i:s") . "\r\n";
            $log_content .= "Batch: $batch_id\r\n";
            $log_content .= "----------------------------------------\r\n";
            $log_content .= implode("\r\n", $error_log);

            if (!is_dir(__DIR__ . "/../data/logs")) mkdir(__DIR__ . "/../data/logs", 0777, true);
            file_put_contents(__DIR__ . "/../data/logs/$batch_id.log", $log_content);

            $message .= "<br><a href='download_log.php?log_id=$batch_id' target='_blank' class='btn btn-sm btn-outline-danger mt-2'><i class='fas fa-download me-1'></i> Download Error Log</a>";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT batch_id, COUNT(*) as `rows`, MAX(id) as lid FROM import_vat_data GROUP BY batch_id ORDER BY lid DESC LIMIT 15")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2 text-primary"></i> Domestic VAT Data Import</h2>
    <p class="text-muted">Upload monthly VAT filing data from Expert Excel templates.</p>
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
                <a href="generate_vat_template.php" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-bold small"><i class="fas fa-download me-1"></i> Download Template</a>
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
      <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-table me-2"></i> Excel Column Mapping</div>
      <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light"><tr><th>Col</th><th>Field</th></tr></thead>
          <tbody>
            <tr><td>A</td><td>Province</td></tr>
            <tr><td>B</td><td>TIN</td></tr>
            <tr><td>C</td><td>Company Name</td></tr>
            <tr><td>D</td><td>Filing Type</td></tr>
            <tr><td>E</td><td>Filing Period</td></tr>
            <tr><td>F</td><td>Input Date</td></tr>
            <tr><td>G</td><td><em>Rate (auto-calculated)</em></td></tr>
            <tr><td>H</td><td>Domestic Non-Exempt Purchases</td></tr>
            <tr><td>I</td><td>Domestic Exempt Purchases / Exempt Sales</td></tr>
            <tr><td>J</td><td>Expert TE</td></tr>
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
            <h5>No VAT Data Found</h5>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch / Source</th><th>Records</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <?php
                $is_manual = (strpos($r["batch_id"], 'MANUAL') !== false);
                $log_file = __DIR__ . "/../data/logs/" . $r["batch_id"] . ".log";
                $has_log = file_exists($log_file);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace"><?= htmlspecialchars($r["batch_id"]) ?></small>
                    <?php if($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><span class="badge bg-primary rounded-pill px-3"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_vat.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="te_vat.php?batch=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                  <?php if($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="type" value="vat">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["batch_id"]) ?>">
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
            <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
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
document.getElementById("uploadForm").addEventListener("submit", function() {
    let btn = document.getElementById("importBtn");
    btn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Importing...";
    btn.classList.add("disabled");
});

function goToManualEntry() {
    const year = document.getElementById('manualTaxYear').value;
    window.location.href = `view_vat.php?batch=MANUAL_ENTRY_VAT_${year}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
