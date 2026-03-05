<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// --- Handle Upload ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"];
    $tax_year = (int)$_POST["tax_year"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error."; $msg_type = "danger";
    } elseif (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), ["xlsx","xls"])) {
        $message = "Invalid file type."; $msg_type = "danger";
    } else {
        $vendor = __DIR__ . "/../vendor/autoload.php";
        if (!file_exists($vendor)) {
            $message = "PhpSpreadsheet not installed. Run <code>composer install</code> on the server."; $msg_type = "warning";
        } else {

            try {
                $spreadsheet = IOFactory::load($file["tmp_name"]);
                $sheet = $spreadsheet->getActiveSheet();
                $batch_id = "BATCH_" . date("YmdHis");
                $imported = 0; $skipped = 0;

                for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                    $tin = trim($sheet->getCell("D" . $row)->getCalculatedValue());
                    if (empty($tin)) { $skipped++; continue; }

                    // Helper: get boolean flag (1 or 0) from cell
                    $flag = function($col) use ($sheet, $row) {
                        $v = $sheet->getCell($col . $row)->getCalculatedValue();
                        return ($v == 1 || strtolower(trim($v)) === "yes") ? 1 : 0;
                    };
                    // Helper: get date string
                    $dateVal = function($col) use ($sheet, $row) {
                        $v = $sheet->getCell($col . $row)->getCalculatedValue();
                        if (!$v) return null;
                        if (is_numeric($v)) {
                            $d = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v);
                            return $d->format("Y-m-d");
                        }
                        return $v;
                    };

                    // Compute composite flags from individual activity columns
                    $act1 = $flag("AC"); $act2 = $flag("AD"); $act3 = $flag("AE");
                    $act4 = $flag("AF"); $act5 = $flag("AG"); $act6 = $flag("AH");
                    $act7 = $flag("AI"); $act8 = $flag("AJ"); $act9 = $flag("AK");
                    $flag_act_1_4_7_8_9 = ($act1 || $act4 || $act7 || $act8 || $act9) ? 1 : 0;
                    $flag_act_2_3_5_6   = ($act2 || $act3 || $act5 || $act6) ? 1 : 0;

                    $excel_year = (int)$sheet->getCell("A" . $row)->getCalculatedValue();
                    $data = [
                        "import_batch_id"            => $batch_id,
                        "tax_year"                   => $excel_year > 0 ? $excel_year : $tax_year,
                        "tin"                        => $tin,
                        "company_name"               => $sheet->getCell("C" . $row)->getCalculatedValue(),
                        "province"                   => $sheet->getCell("E" . $row)->getCalculatedValue(),
                        "district"                   => $sheet->getCell("F" . $row)->getCalculatedValue(),
                        "zone_1"                     => $flag("G"),
                        "zone_2"                     => $flag("H"),
                        "zone_3"                     => $flag("I"),
                        "sector"                     => $sheet->getCell("J" . $row)->getCalculatedValue(),
                        "revenue"                    => (float)$sheet->getCell("K" . $row)->getCalculatedValue(),
                        "expense"                    => (float)$sheet->getCell("L" . $row)->getCalculatedValue(),
                        "net_profit"                 => (float)$sheet->getCell("M" . $row)->getCalculatedValue(),
                        "re_invested_profit"         => (float)$sheet->getCell("N" . $row)->getCalculatedValue(),
                        "pt_paid"                    => (float)$sheet->getCell("O" . $row)->getCalculatedValue(),
                        "tax_holiday_years"          => (int)$sheet->getCell("S" . $row)->getCalculatedValue(),
                        "investment_license_date"    => $dateVal("B"),
                        "flag_hr_dev"                => $flag("U"),
                        "flag_eco_friendly"          => $flag("V"),
                        "flag_sez_developer"         => $flag("W"),
                        "flag_sez_investor"          => $flag("X"),
                        "flag_act_production_services" => $flag("Y"),
                        "flag_public_benefit"        => $flag("Z"),
                        "flag_compliant_rental"      => $flag("AA"),
                        "flag_real_estate_transfer"  => $flag("AB"),
                        "flag_act_1_4_7_8_9"         => $flag_act_1_4_7_8_9,
                        "flag_act_2_3_5_6"           => $flag_act_2_3_5_6,
                        "is_vat_holder"              => $flag("AL"),
                        "reinvest_date"              => $dateVal("AM"),
                        "reinvest_amount"            => (float)$sheet->getCell("AN" . $row)->getCalculatedValue(),
                        "total_assets"               => (float)$sheet->getCell("AO" . $row)->getCalculatedValue(),
                        "annual_turnover"            => (float)$sheet->getCell("AP" . $row)->getCalculatedValue(),
                        "staff_count"                => (int)$sheet->getCell("AQ" . $row)->getCalculatedValue(),
                        "stock_exchange_listing_date" => $dateVal("AR"),
                        "registration_date"          => $dateVal("T"),
                    ];

                    $cols = implode(", ", array_keys($data));
                    $ph = implode(", ", array_fill(0, count($data), "?"));
                    $pdo->prepare("INSERT INTO companies ($cols) VALUES ($ph)")->execute(array_values($data));
                    $imported++;
                }
                $message = "Import done! <strong>$imported companies</strong> imported, $skipped skipped.";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage(); $msg_type = "danger";
            }
        }
    }
}

$recent = $pdo->query("SELECT import_batch_id, tax_year, COUNT(*) as `rows`, MAX(id) as lid FROM companies GROUP BY import_batch_id, tax_year ORDER BY lid DESC LIMIT 10")->fetchAll();
require_once __DIR__ . "/../includes/header.php";
?>

<div class="row mb-3">
  <div class="col-12">
    <h2><i class="fas fa-file-import me-2"></i> CIT Data Import</h2>
    <p class="text-muted">Upload the CIT Excel template to import company data. Row 1 = headers, data starts from row 2.</p>
  </div>
</div>
<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-upload me-2"></i> Upload Excel File</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="mb-3">
            <label class="form-label fw-bold">Tax Year</label>
            <select name="tax_year" class="form-select" required>
              <?php for ($y = date("Y"); $y >= 2015; $y--): ?>
              <option value="<?= $y ?>"><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Excel File (.xlsx)</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg" id="importBtn"><i class="fas fa-upload me-2"></i> Import</button>
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
            <tr><td>A</td><td>Year</td></tr>
            <tr><td>B</td><td>Investment License Date</td></tr>
            <tr><td>C</td><td>Company Name</td></tr>
            <tr><td>D</td><td>TIN (Tax ID)</td></tr>
            <tr><td>E</td><td>Province</td></tr>
            <tr><td>F</td><td>District</td></tr>
            <tr><td>G / H / I</td><td>Zone 1 / Zone 2 / Zone 3</td></tr>
            <tr><td>J</td><td>Sector</td></tr>
            <tr><td>K / L / M</td><td>Revenue / Expense / Net Profit</td></tr>
            <tr><td>N</td><td>Re-invested Profit</td></tr>
            <tr><td>O</td><td>PT Paid (from TaxRIS)</td></tr>
            <tr><td>S</td><td>Tax Holiday Period (years)</td></tr>
            <tr><td>T</td><td>Registration Date</td></tr>
            <tr><td>U / V</td><td>Flag: HR Dev / Eco Friendly</td></tr>
            <tr><td>W / X</td><td>Flag: SEZ Developer / SEZ Investor</td></tr>
            <tr><td>Y</td><td>Flag: Production/Services Activity</td></tr>
            <tr><td>Z / AA / AB</td><td>Flag: Public Benefit / Rental / Real Estate</td></tr>
            <tr><td>AC-AK</td><td>IPL Art.9 Activities 1-9</td></tr>
            <tr><td>AL</td><td>VAT Holder (1/0)</td></tr>
            <tr><td>AM / AN</td><td>Re-invest Date / Amount</td></tr>
            <tr><td>AO / AP / AQ</td><td>Total Assets / Annual Turnover / Staff</td></tr>
            <tr><td>AR</td><td>Stock Exchange Listing Date</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold"><i class="fas fa-history me-2"></i> Recent Import Batches</div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-4 text-center text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No data imported yet.</div>
        <?php else: ?>
          <table class="table table-hover mb-0 datatable w-100">
            <thead class="table-light"><tr><th>Batch ID</th><th>Year</th><th>Companies</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td><small class="font-monospace"><?= htmlspecialchars($r["import_batch_id"]) ?></small></td>
                <td><?= $r["tax_year"] ?></td>
                <td><span class="badge bg-success rounded-pill"><?= $r["rows"] ?></span></td>
                <td>
                  <a href="view_companies.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                  <a href="calculator.php?batch=<?= urlencode($r["import_batch_id"]) ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-calculator"></i> Calculate</a>
                  <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete batch?')">
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($r["import_batch_id"]) ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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

<script>
document.getElementById("uploadForm").addEventListener("submit", function() {
    document.getElementById("importBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i> Importing...";
    document.getElementById("importBtn").disabled = true;
});
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
