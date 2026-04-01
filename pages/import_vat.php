<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Add this

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["vat_file"])) {
    $file = $_FILES["vat_file"]["tmp_name"];
    $batch_id = "VAT-" . date("Ymd-His");
    
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, false, true); 
        
        $inserted = 0;
        foreach ($data as $index => $row) {
            if ($index <= 1) continue; // Skip headers
            if (empty($row["B"])) continue; // Skip empty rows (TIN)
            
            // Map columns from Domestic VAT Test.xlsx structure (A-J template)
            // A=Province, B=TIN, C=Name, D=FilingType, E=FilingPeriod, F=InputDate, H=Rate, I=S_Ex, J=ExpertTE
            
            $stmt = $pdo->prepare("INSERT INTO import_vat_data (batch_id, province, tin, name, filing_type, filing_period, input_date, purchase_domestic_nonexempt, purchase_domestic_exempt, purchase_import_nonexempt, purchase_import_exempt, total_input_vat, sales_standard, sales_zero_rate, sales_exempt, total_output_vat, vat_payable, vat_credit, expert_te, provision_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Helper to clean numeric strings
            $cleanNum = function($val) {
                if (is_numeric($val)) return (float)$val;
                return (float)str_replace(',', '', (string)$val);
            };

            // Date parsing (Handles Excel numeric dates and various string formats)
            $parseDate = function($val, $colName) use ($index) {
                if (empty($val)) return null;
                
                // If it's a 4-digit year only
                if (is_numeric($val) && strlen((string)$val) === 4) {
                    return $val . "-01-01";
                }

                if (is_numeric($val) && $val > 10000) { // Likely Excel date serial
                    try {
                        return Date::excelToDateTimeObject($val)->format('Y-m-d');
                    } catch (Exception $e) {}
                }

                // Try common formats
                $ts = strtotime((string)$val);
                if ($ts) return date("Y-m-d", $ts);

                // Handle YYYYMM or similar if needed
                if (preg_match('/^(\d{4})[-|\/](\d{1,2})$/', (string)$val, $matches)) {
                    return $matches[1] . "-" . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . "-01";
                }

                return null;
            };

            $period = $parseDate($row["E"] ?? "", "E");
            $input_date = $parseDate($row["F"] ?? "", "F");

            $stmt->execute([
                $batch_id, $row["A"], $row["B"], $row["C"], $row["D"], $period, $input_date,
                $cleanNum($row["H"] ?? 0), $cleanNum($row["I"] ?? 0), 0, 0,
                0, 0, 0, $cleanNum($row["I"] ?? 0),
                0, 0, 0, $cleanNum($row["J"] ?? 0),
                $row["AG"] ?? ""
            ]);
            $inserted++;
        }
        $message = "Successfully imported $inserted records into batch $batch_id.";
    } catch (Exception $e) {
        $message = "Import error: " . $e->getMessage(); $msg_type = "danger";
    }
}

// Fetch Batches
$batches = [];
try { $batches = $pdo->query("SELECT batch_id, import_date, COUNT(*) as row_count FROM import_vat_data GROUP BY batch_id ORDER BY import_date DESC")->fetchAll(); } catch (Exception $e) {}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12 text-start">
    <h2><i class="fas fa-file-import me-2 text-primary"></i> Domestic VAT Data Import</h2>
    <p class="text-muted">Import monthly VAT filing data from Expert Excel templates.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Select VAT Excel File (.xlsx)</label>
        <div class="input-group">
            <input type="file" name="vat_file" class="form-control" accept=".xlsx" required>
            <button class="btn btn-primary px-4"><i class="fas fa-upload me-2"></i> Import Data</button>
        </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
  <div class="card-header bg-white"><span class="fw-bold"><i class="fas fa-history me-2 text-primary"></i> Recent VAT Import Batches</span></div>
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light small fw-bold"><tr><th class="ps-4">Batch ID</th><th>Import Date</th><th>Rows</th><th class="pe-4 text-end">Actions</th></tr></thead>
        <tbody>
            <?php foreach ($batches as $b): ?>
            <tr>
                <td class="ps-4 fw-bold"><?= htmlspecialchars($b["batch_id"]) ?></td>
                <td><?= date("d M Y H:i", strtotime($b["import_date"])) ?></td>
                <td><span class="badge bg-secondary"><?= $b["row_count"] ?> records</span></td>
                <td class="pe-4 text-end">
                    <a href="te_vat.php?batch=<?= urlencode($b["batch_id"]) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-calculator me-1"></i> Calculate TE</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

