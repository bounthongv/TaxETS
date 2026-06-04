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

// --- Ensure pro_id column exists ---
try {
    $pdo->exec("ALTER TABLE asycuda_imports ADD COLUMN pro_id VARCHAR(10) DEFAULT NULL AFTER province");
} catch (Exception $e) {
    // Column already exists
}

// --- Pre-load Province Smart Mapping ---
$prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
$prov_map = [];
$prov_by_id = [];
foreach ($prov_rows as $r) {
    $prov_map[strtoupper(trim($r['pro_name']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']];
    $prov_by_id[$r['pro_id']] = $r['pro_name'];
}
$prov_aliases = [
    'BOLIKHAMSAI'  => '11', 'BOLIKHAMXAI'  => '11', 'BORIKHAMXAY'  => '11',
    'BORIKHAMXAI'  => '11', 'BOLIKAMSAI'   => '11',
    'XIANGKHOUANG' => '09', 'XIENGKHOUANG' => '09',
    'VIENTIANE'    => '01',
    'BOKEO'        => '05', 'LUANGPRABANG' => '06', 'LUANGPHRABANG' => '06',
    'LOUANGPRABANG' => '06', 'LUANGPHABANG' => '06',
    'LUANGNAMTHA'  => '03', 'LOUANGNAMTHA' => '03', 'LUANG NAMTHA' => '03',
    'OUDOMXAY'     => '04', 'OUDOMXAI'     => '04', 'UDOMXAY'      => '04',
    'SAYABOURY'    => '08', 'SAYABURI'     => '08', 'XAYABOURY'    => '08',
    'SAIYABOULY'   => '08', 'SAYABULI'     => '08',
    'SARAVANE'     => '14', 'SARAVAN'      => '14', 'SALAVANH'     => '14',
    'SEKONG'       => '15', 'XEKONG'       => '15',
    'ATTAPEU'      => '17', 'ATTAPU'       => '17', 'ATTAPUE'      => '17',
    'XAISOMBOUN'   => '18', 'XAISOMBUN'    => '18', 'SAISOMBOUN'   => '18',
    'KHAMMOUAN'    => '12', 'KAMMOUANE'    => '12',
    'HUAPHANH'     => '07', 'HOUAPHANH'    => '07', 'HUAPHAN'      => '07',
    'PHONGSALY'    => '02', 'PHONGSALI'    => '02', 'PONGSALY'     => '02',
    'SAVANNAKET'   => '13',
];

// --- Province Sync: fix existing records with unmapped or mismatched province names ---
$resolveProvince = function ($raw) use ($prov_map, $prov_aliases, $prov_rows) {
    $upper = strtoupper(trim($raw ?? ''));
    if (empty($upper)) return null;
    $match = $prov_map[$upper] ?? null;
    if (!$match && isset($prov_aliases[$upper])) {
        $alias_id = $prov_aliases[$upper];
        foreach ($prov_rows as $pr) {
            if ($pr['pro_id'] == $alias_id) {
                $match = ['pro_id' => $pr['pro_id'], 'name' => $pr['pro_name']];
                break;
            }
        }
    }
    if (!$match && strlen($upper) >= 3) {
        $best_score = 999;
        $best_match = null;
        foreach ($prov_map as $pname => $pdata) {
            $score = levenshtein($upper, $pname);
            if ($score < $best_score) { $best_score = $score; $best_match = $pdata; }
        }
        if ($best_score <= 3 && $best_match) $match = $best_match;
    }
    return $match;
};

try {
    $fix = $pdo->query("SELECT DISTINCT province FROM asycuda_imports WHERE province IS NOT NULL AND province != '' AND (pro_id IS NULL OR pro_id = '')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($fix as $raw_prov) {
        $resolved = $resolveProvince($raw_prov);
        if ($resolved) {
            $pdo->prepare("UPDATE asycuda_imports SET province = ?, pro_id = ? WHERE province = ? AND (pro_id IS NULL OR pro_id = '')")
                ->execute([$resolved['name'], $resolved['pro_id'], $raw_prov]);
        }
    }
} catch (Exception $e) {
    // Silent
}

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    try {
        $file = $_FILES["excel_file"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error.");
        }
        if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx", "xls"])) {
            throw new Exception("Invalid file type.");
        }

        $spreadsheet = IOFactory::load($file["tmp_name"]);
        $sheet = $spreadsheet->getActiveSheet();
        $batch_id = "BATCH_ASYCUDA_" . date("YmdHis");
        $imported = 0;
        $error_log = [];

        $pdo->beginTransaction();

        $parseDate = function ($col) use ($sheet, &$error_log) {
            $v = $sheet->getCell($col)->getCalculatedValue();
            if (!$v) return null;
            if (is_numeric($v)) {
                try { return Date::excelToDateTimeObject($v)->format("Y-m-d"); } catch (Exception $e) {}
            }
            $ts = strtotime(str_replace('/', '-', $v));
            if ($ts) return date("Y-m-d", $ts);
            return null;
        };

        $cleanNum = function ($col) use ($sheet) {
            $val = $sheet->getCell($col)->getCalculatedValue();
            if (is_numeric($val)) return (float)$val;
            return (float)str_replace(',', '', (string)($val ?? '0'));
        };

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $tin = trim($sheet->getCell("B" . $row)->getValue());
            if (empty($tin)) continue;

            $regime = trim($sheet->getCell("G" . $row)->getValue()) . "-" . trim($sheet->getCell("H" . $row)->getValue());

            // Smart Mapping: Province
            $raw_prov = trim($sheet->getCell("A" . $row)->getValue() ?? "");
            $prov_match = $resolveProvince($raw_prov);
            $pro_id = $prov_match['pro_id'] ?? null;
            if (!$pro_id && !empty($raw_prov)) {
                $error_log[] = "Row $row: Unknown Province '$raw_prov'";
            }
            $official_province = $prov_match['name'] ?? $raw_prov;

            $data = [
                "import_batch_id"   => $batch_id,
                "province"          => $official_province,
                "pro_id"            => $pro_id,
                "tin"               => $tin,
                "no_seq"            => $sheet->getCell("C" . $row)->getValue(),
                "border_code"       => $sheet->getCell("D" . $row)->getValue(),
                "border_name"       => $sheet->getCell("E" . $row)->getValue(),
                "type_customs"      => $sheet->getCell("F" . $row)->getValue(),
                "process_type"      => $sheet->getCell("G" . $row)->getValue(),
                "regime_f"          => $sheet->getCell("H" . $row)->getValue(),
                "regime_code"       => $regime,
                "special_role"      => $sheet->getCell("I" . $row)->getValue(),
                "doc_number"        => $sheet->getCell("J" . $row)->getValue(),
                "doc_date"          => $parseDate("K" . $row),
                "assess_number"     => $sheet->getCell("L" . $row)->getValue(),
                "assess_date"       => $parseDate("M" . $row),
                "receipt_no"        => $sheet->getCell("N" . $row)->getValue(),
                "receipt_date"      => $parseDate("O" . $row),
                "importer_name"     => $sheet->getCell("P" . $row)->getValue(),
                "declarant_tin"     => $sheet->getCell("Q" . $row)->getValue(),
                "declarant_name"    => $sheet->getCell("R" . $row)->getValue(),
                "export_country"    => $sheet->getCell("S" . $row)->getValue(),
                "dest_country"      => $sheet->getCell("T" . $row)->getValue(),
                "origin_country"    => $sheet->getCell("U" . $row)->getValue(),
                "list_no"           => $sheet->getCell("V" . $row)->getValue(),
                "hs_code"           => $sheet->getCell("W" . $row)->getValue(),
                "goods_description" => $sheet->getCell("X" . $row)->getValue(),
                "quantity"          => $cleanNum("Y" . $row),
                "unit"              => $sheet->getCell("Z" . $row)->getValue(),
                "declare_weight"    => $cleanNum("AA" . $row),
                "actual_weight"     => $cleanNum("AB" . $row),
                "invoice_usd"       => $cleanNum("AC" . $row),
                "invoice_amount_lak" => $cleanNum("AD" . $row),
                "paid_customs"      => $cleanNum("AE" . $row),
                "paid_excise"       => $cleanNum("AF" . $row),
                "paid_vat"          => $cleanNum("AG" . $row),
                "paid_profit"       => $cleanNum("AH" . $row),
                "paid_road_fund"    => $cleanNum("AI" . $row),
                "paid_total"        => $cleanNum("AJ" . $row),
                "status_aj"         => $sheet->getCell("AK" . $row)->getValue(),
                "exemp_customs"     => $cleanNum("AL" . $row),
                "exempt_excise"     => $cleanNum("AM" . $row),
                "exempt_vat"        => $cleanNum("AN" . $row),
                "te_customs_excel"  => $cleanNum("AO" . $row),
                "te_excise_excel"   => $cleanNum("AP" . $row),
                "te_vat_excel"      => $cleanNum("AQ" . $row),
                "provision_customs" => $sheet->getCell("AR" . $row)->getValue(),
            ];

            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $stmt = $pdo->prepare("INSERT INTO asycuda_imports ($cols) VALUES ($ph)");
            $stmt->execute(array_values($data));
            $asy_id = $pdo->lastInsertId();

            $customs_te = max(0, $data['exemp_customs'] - $data['paid_customs']);
            $excise_te = max(0, $data['exempt_excise'] - $data['paid_excise']);
            $vat_te = max(0, $data['exempt_vat'] - $data['paid_vat']);
            $total_te = $customs_te + $excise_te + $vat_te;

            $stmt_te = $pdo->prepare("INSERT INTO te_asycuda_result (asycuda_id, customs_te, excise_te, vat_te, total_te) VALUES (?, ?, ?, ?, ?)");
            $stmt_te->execute([$asy_id, $customs_te, $excise_te, $vat_te, $total_te]);

            $imported++;
            if ($imported % 500 == 0) {
                $pdo->commit();
                $pdo->beginTransaction();
            }
        }
        $pdo->commit();

        $message = "<strong>Import Success!</strong> Imported $imported records from ASYCUDA export.<br>";
        $message .= "Go to calculation pages to process tax expenditures.";

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
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch recent batches
$recent = $pdo->query("SELECT import_batch_id, DATE(import_date) as idate, COUNT(*) as `rows`, COALESCE(SUM(te.total_te), 0) as total_te 
                       FROM asycuda_imports i 
                       LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                       GROUP BY import_batch_id ORDER BY MAX(i.id) DESC LIMIT 15")->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2 text-primary"></i> ASYCUDA Data Import</h2>
    <p class="text-muted">Upload the standard ASYCUDA Excel export to calculate Customs, Excise, and VAT Tax Expenditures.</p>
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
      <div class="card-header bg-white text-dark fw-bold"><i class="fas fa-upload me-2 text-primary"></i> Upload ASYCUDA File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i> Standard ASYCUDA export format (columns A&ndash;AR).</div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="importBtn"><i class="fas fa-file-import me-2"></i> Import ASYCUDA Data</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3 shadow-sm">
      <div class="card-header bg-secondary text-white fw-bold"><i class="fas fa-table me-2"></i> Excel Column Mapping</div>
      <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light"><tr><th>Excel Col</th><th>System Field</th></tr></thead>
          <tbody>
            <tr><td>A</td><td>Province</td></tr>
            <tr><td>B</td><td>TIN</td></tr>
            <tr><td>G &amp; H</td><td>Regime Code (e.g., 4000-480)</td></tr>
            <tr><td>J &amp; K</td><td>Declaration No / Date</td></tr>
            <tr><td>L &amp; M</td><td>Assess No / Date</td></tr>
            <tr><td>P</td><td>Importer Name</td></tr>
            <tr><td>W</td><td>HS Code</td></tr>
            <tr><td>X</td><td>Goods Description</td></tr>
            <tr><td>AD</td><td>Invoice Amount (LAK)</td></tr>
            <tr class="table-success"><td>AE, AF, AG</td><td>Paid Customs, Excise, VAT</td></tr>
            <tr class="table-info"><td>AL, AM, AN</td><td><strong>Benchmark</strong> Customs, Excise, VAT</td></tr>
            <tr><td>AO, AP, AQ</td><td>TE from Excel (reference only)</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i> Recent ASYCUDA Batches & Manual Entries</span>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fas fa-plus me-1"></i> Add Manual Entry
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No ASYCUDA Data Found</h5>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch / Source</th><th>Date</th><th>Records</th><th class="text-end">Total TE (LAK)</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <?php
                $is_manual = (strpos($r["import_batch_id"], 'MANUAL') !== false);
                $log_file = __DIR__ . "/../data/logs/" . $r["import_batch_id"] . ".log";
                $has_log = file_exists($log_file);
              ?>
              <tr class="<?= $is_manual ? 'table-info' : '' ?>">
                <td>
                    <small class="font-monospace"><?= htmlspecialchars($r["import_batch_id"]) ?></small>
                    <?php if ($is_manual): ?><span class="badge bg-info ms-1">MANUAL</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r["idate"]) ?></td>
                <td><span class="badge bg-primary rounded-pill px-3"><?= number_format($r["rows"]) ?></span></td>
                <td class="fw-bold text-end">
                    <?php if ($r["total_te"] > 0): ?>
                        <span class="text-danger"><?= number_format($r["total_te"], 2) ?></span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark opacity-75 small">Pending Calc</span>
                    <?php endif; ?>
                </td>
                <td>
                  <a href="view_asycuda.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="view_asycuda.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="TE Calculation"><i class="fas fa-calculator"></i></a>
                  <?php if ($has_log): ?>
                    <a href="download_log.php?log_id=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete this batch and all TE results?')">
                    <input type="hidden" name="type" value="asy">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["import_batch_id"]) ?>">
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
        <h5 class="modal-title">Manual Data Entry for ASYCUDA</h5>
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
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    window.location.href = `view_asycuda.php?batch=MANUAL_ENTRY_ASYCUDA_${year}_${stamp}&auto_add=1&year=${year}`;
}
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
