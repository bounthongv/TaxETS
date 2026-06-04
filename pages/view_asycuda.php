<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/batch_nav.php";
require_once __DIR__ . "/../includes/header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';
$auto_add = $_GET['auto_add'] ?? '';

if (!$batch) {
    die("Batch ID required.");
}

// --- Province Sync for existing records ---
try {
    $prov_rows = $pdo->query("SELECT province_code AS pro_id, province_name AS pro_name FROM provinces")->fetchAll();
    $prov_map = [];
    foreach ($prov_rows as $r) {
        $prov_map[strtoupper(trim($r['pro_name']))] = ['pro_id' => $r['pro_id'], 'name' => $r['pro_name']];
    }
    $prov_aliases = [
        'BOLIKHAMSAI'  => '11', 'BOLIKHAMXAI'  => '11', 'BORIKHAMXAY'  => '11',
        'BORIKHAMXAI'  => '11', 'BOLIKAMSAI'   => '11',
        'XIANGKHOUANG' => '09', 'XIENGKHOUANG' => '09',
        'VIENTIANE'    => '01',
        'BOKEO'        => '05', 'LUANGPRABANG' => '06', 'LUANGPHRABANG' => '06',
        'LOUANGPRABANG' => '06', 'LUANGPHABANG' => '06',
        'LUANGNAMTHA'  => '03', 'LOUANGNAMTHA' => '03',
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
    $fix_rows = $pdo->query("SELECT DISTINCT province FROM asycuda_imports WHERE province IS NOT NULL AND province != '' AND (pro_id IS NULL OR pro_id = '')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($fix_rows as $raw_prov) {
        $upper = strtoupper(trim($raw_prov));
        if (empty($upper)) continue;
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
            foreach ($prov_map as $pname => $pdata) {
                $score = levenshtein($upper, $pname);
                if ($score < $best_score) { $best_score = $score; $match = $pdata; }
            }
            if ($best_score > 3) $match = null;
        }
        if ($match) {
            $pdo->prepare("UPDATE asycuda_imports SET province = ?, pro_id = ? WHERE province = ? AND (pro_id IS NULL OR pro_id = '')")
                ->execute([$match['name'], $match['pro_id'], $raw_prov]);
        }
    }
} catch (Exception $e) {
    // Silent
}

// --- Handle Add / Update Record ---
$msg = '';
$msg_type = 'success';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        $action = $_POST["action"];
        $data = [
            "province"          => $_POST["province"] ?? "",
            "tin"               => $_POST["tin"] ?? "",
            "regime_code"       => ($_POST["process_type"] ?? "") . "-" . ($_POST["regime_f"] ?? ""),
            "process_type"      => $_POST["process_type"] ?? "",
            "regime_f"          => $_POST["regime_f"] ?? "",
            "type_customs"      => $_POST["type_customs"] ?? "",
            "doc_number"        => $_POST["doc_number"] ?? "",
            "doc_date"          => $_POST["doc_date"] ?: null,
            "importer_name"     => $_POST["importer_name"] ?? "",
            "declarant_tin"     => $_POST["declarant_tin"] ?? "",
            "declarant_name"    => $_POST["declarant_name"] ?? "",
            "hs_code"           => $_POST["hs_code"] ?? "",
            "goods_description" => $_POST["goods_description"] ?? "",
            "quantity"          => (float)($_POST["quantity"] ?? 0),
            "unit"              => $_POST["unit"] ?? "",
            "invoice_amount_lak" => (float)($_POST["invoice_amount_lak"] ?? 0),
            "paid_customs"      => (float)($_POST["paid_customs"] ?? 0),
            "paid_excise"       => (float)($_POST["paid_excise"] ?? 0),
            "paid_vat"          => (float)($_POST["paid_vat"] ?? 0),
            "exemp_customs"     => (float)($_POST["exemp_customs"] ?? 0),
            "exempt_excise"     => (float)($_POST["exempt_excise"] ?? 0),
            "exempt_vat"        => (float)($_POST["exempt_vat"] ?? 0),
        ];

        $customs_te = max(0, $data['exemp_customs'] - $data['paid_customs']);
        $excise_te = max(0, $data['exempt_excise'] - $data['paid_excise']);
        $vat_te = max(0, $data['exempt_vat'] - $data['paid_vat']);
        $total_te = $customs_te + $excise_te + $vat_te;

        if ($action === "update_record") {
            $id = (int)($_POST["id"] ?? 0);
            if (!$id) throw new Exception("Record ID required.");

            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = ($v === "" ? null : $v);
            }
            $values[] = $id;
            $pdo->prepare("UPDATE asycuda_imports SET " . implode(", ", $fields) . " WHERE id = ?")->execute($values);
            $pdo->prepare("UPDATE te_asycuda_result SET customs_te = ?, excise_te = ?, vat_te = ?, total_te = ? WHERE asycuda_id = ?")
                ->execute([$customs_te, $excise_te, $vat_te, $total_te, $id]);
            $msg = "Record updated successfully.";
        } elseif ($action === "add_record") {
            $data["import_batch_id"] = $batch;
            $cols = implode(", ", array_keys($data));
            $ph = implode(", ", array_fill(0, count($data), "?"));
            $pdo->prepare("INSERT INTO asycuda_imports ($cols) VALUES ($ph)")->execute(array_values($data));
            $asy_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO te_asycuda_result (asycuda_id, customs_te, excise_te, vat_te, total_te) VALUES (?, ?, ?, ?, ?)")
                ->execute([$asy_id, $customs_te, $excise_te, $vat_te, $total_te]);
            $msg = "Record added successfully.";
        }
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch records
$stmt = $pdo->prepare("SELECT i.*, YEAR(i.doc_date) as import_year, te.customs_te, te.excise_te, te.vat_te, te.total_te 
                       FROM asycuda_imports i 
                       LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                       WHERE i.import_batch_id = ?
                       ORDER BY i.id ASC");
$stmt->execute([$batch]);
$rows = $stmt->fetchAll();

// Summary
$total_records = count($rows);
$total_te = array_sum(array_column($rows, 'total_te'));
$total_invoice = array_sum(array_column($rows, 'invoice_amount_lak'));
$total_benchmark = array_sum(array_column($rows, 'exemp_customs')) + array_sum(array_column($rows, 'exempt_excise')) + array_sum(array_column($rows, 'exempt_vat'));
$total_paid = array_sum(array_column($rows, 'paid_customs')) + array_sum(array_column($rows, 'paid_excise')) + array_sum(array_column($rows, 'paid_vat'));
$is_manual = (strpos($batch, 'MANUAL') !== false);
$provinces = $pdo->query("SELECT province_name FROM provinces ORDER BY province_name")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="import_asycuda.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> ASYCUDA Batch Details</h2>
        <p class="text-muted">
            Batch: <code><?= htmlspecialchars($batch) ?></code>
            <?php if ($is_manual): ?><span class="badge bg-info ms-2">MANUAL</span><?php endif; ?>
        </p>
    </div>
    <div class="col-md-4 text-end">
        <?= batchHubBackButton() ?>
        <button class="btn btn-primary" onclick="addRecord()"><i class="fas fa-plus me-2"></i> Add Record to Batch</button>
        <a href="import_asycuda.php" class="btn btn-outline-secondary ms-1"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-3 g-2">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Total Records</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_invoice) ?></div>
            <div class="small opacity-75">Total Invoice (LAK)</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_benchmark) ?></div>
            <div class="small opacity-75">Total Benchmark</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">Total Paid</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small fw-bold text-muted">Search TIN / Doc No / Importer</label>
        <input type="text" id="customSearch" class="form-control" placeholder="Type to search...">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Regime</label>
        <select id="filterRegime" class="form-select">
          <option value="">All Regimes</option>
          <?php
          $regimes = $pdo->prepare("SELECT DISTINCT regime_code FROM asycuda_imports WHERE import_batch_id = ? AND regime_code != '' ORDER BY regime_code");
          $regimes->execute([$batch]);
          foreach ($regimes as $r): ?>
          <option value="<?= htmlspecialchars($r['regime_code']) ?>"><?= htmlspecialchars($r['regime_code']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Province</label>
        <select id="filterProvince" class="form-select">
          <option value="">All Provinces</option>
          <?php foreach ($provinces as $p): ?>
          <option value="<?= htmlspecialchars($p['province_name']) ?>"><?= htmlspecialchars($p['province_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label small fw-bold text-muted">Year</label>
        <select id="filterYear" class="form-select">
          <option value="">All Years</option>
          <?php
          $years = $pdo->prepare("SELECT DISTINCT YEAR(doc_date) as y FROM asycuda_imports WHERE import_batch_id = ? AND doc_date IS NOT NULL ORDER BY y DESC");
          $years->execute([$batch]);
          foreach ($years as $y): ?>
          <option value="<?= $y['y'] ?>"><?= $y['y'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-outline-secondary w-100" onclick="resetFilters()" title="Reset Filters"><i class="fas fa-undo"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="asyTable" class="table table-bordered table-hover w-100" style="font-size:0.85em">
                <thead class="table-light text-uppercase small">
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th>TIN</th>
                        <th>Regime</th>
                        <th>Province</th>
                        <th>Year</th>
                        <th>Doc No</th>
                        <th>Doc Date</th>
                        <th>Importer</th>
                        <th>HS Code</th>
                        <th>Qty</th>
                        <th class="text-end">Invoice LAK</th>
                        <th class="text-end">BM Customs</th>
                        <th class="text-end">BM Excise</th>
                        <th class="text-end">BM VAT</th>
                        <th class="text-end">Paid Customs</th>
                        <th class="text-end">Paid Excise</th>
                        <th class="text-end">Paid VAT</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="font-monospace fw-bold"><?= htmlspecialchars($r['tin']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['regime_code']) ?></span></td>
                        <td><?= htmlspecialchars($r['province']) ?></td>
                        <td><?= htmlspecialchars($r['import_year']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['doc_number']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['doc_date']) ?></td>
                        <td class="text-truncate" style="max-width:130px"><?= htmlspecialchars($r['importer_name']) ?></td>
                        <td class="font-monospace"><?= htmlspecialchars($r['hs_code']) ?></td>
                        <td class="text-end"><?= number_format($r['quantity']) ?></td>
                        <td class="text-end"><?= number_format($r['invoice_amount_lak']) ?></td>
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exemp_customs']) ?></td>
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exempt_excise']) ?></td>
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exempt_vat']) ?></td>
                        <td class="text-end"><?= number_format($r['paid_customs']) ?></td>
                        <td class="text-end"><?= number_format($r['paid_excise']) ?></td>
                        <td class="text-end"><?= number_format($r['paid_vat']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick='editRecord(<?= (int)$r["id"] ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit / Add Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title" id="modalTitle">ASYCUDA Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="modalAction" value="add_record">
          <input type="hidden" name="id" id="edit_id">
          <div class="row g-2">
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">TIN <span class="text-danger">*</span></label>
              <input type="text" name="tin" id="edit_tin" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Province</label>
              <select name="province" id="edit_province" class="form-select form-select-sm">
                <option value="">-- Select --</option>
                <?php foreach ($provinces as $p): ?>
                <option value="<?= htmlspecialchars($p['province_name']) ?>"><?= htmlspecialchars($p['province_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <label class="form-label small fw-bold">Process Type</label>
              <input type="text" name="process_type" id="edit_process_type" class="form-control form-control-sm" placeholder="e.g. 4000">
            </div>
            <div class="col-md-2 mb-2">
              <label class="form-label small fw-bold">Regime F</label>
              <input type="text" name="regime_f" id="edit_regime_f" class="form-control form-control-sm" placeholder="e.g. 480">
            </div>
            <div class="col-md-3 mb-2">
              <label class="form-label small fw-bold">Type Customs</label>
              <input type="text" name="type_customs" id="edit_type_customs" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 mb-2">
              <label class="form-label small fw-bold">Doc Number</label>
              <input type="text" name="doc_number" id="edit_doc_number" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 mb-2">
              <label class="form-label small fw-bold">Doc Date</label>
              <input type="date" name="doc_date" id="edit_doc_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Importer Name</label>
              <input type="text" name="importer_name" id="edit_importer_name" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Declarant TIN</label>
              <input type="text" name="declarant_tin" id="edit_declarant_tin" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Declarant Name</label>
              <input type="text" name="declarant_name" id="edit_declarant_name" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 mb-2">
              <label class="form-label small fw-bold">HS Code</label>
              <input type="text" name="hs_code" id="edit_hs_code" class="form-control form-control-sm">
            </div>
            <div class="col-md-5 mb-2">
              <label class="form-label small fw-bold">Goods Description</label>
              <input type="text" name="goods_description" id="edit_goods_description" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 mb-2">
              <label class="form-label small fw-bold">Quantity</label>
              <input type="number" step="0.01" name="quantity" id="edit_quantity" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 mb-2">
              <label class="form-label small fw-bold">Unit</label>
              <input type="text" name="unit" id="edit_unit" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Invoice Amount (LAK)</label>
              <input type="number" step="0.01" name="invoice_amount_lak" id="edit_invoice_amount_lak" class="form-control form-control-sm">
            </div>
          </div>
          <hr class="my-2">
          <div class="row g-2">
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold text-success">Paid Customs</label>
              <input type="number" step="0.01" name="paid_customs" id="edit_paid_customs" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold text-success">Paid Excise</label>
              <input type="number" step="0.01" name="paid_excise" id="edit_paid_excise" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold text-success">Paid VAT</label>
              <input type="number" step="0.01" name="paid_vat" id="edit_paid_vat" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold text-primary">Benchmark Customs</label>
              <input type="number" step="0.01" name="exemp_customs" id="edit_exemp_customs" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold text-primary">Benchmark Excise</label>
              <input type="number" step="0.01" name="exempt_excise" id="edit_exempt_excise" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold text-primary">Benchmark VAT</label>
              <input type="number" step="0.01" name="exempt_vat" id="edit_exempt_vat" class="form-control form-control-sm">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
const recordData = <?= json_encode(array_column($rows, null, 'id')) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const table = $('#asyTable').DataTable({
        dom: 'rtip',
        pageLength: 50,
        order: [],
        columnDefs: [{ targets: '_all', className: 'dt-body-left' }]
    });

    document.getElementById('customSearch').addEventListener('keyup', function() {
        table.search(this.value).draw();
    });
    document.getElementById('filterRegime').addEventListener('change', function() {
        table.column(2).search(this.value).draw();
    });
    document.getElementById('filterProvince').addEventListener('change', function() {
        table.column(3).search(this.value).draw();
    });
    document.getElementById('filterYear').addEventListener('change', function() {
        table.column(4).search(this.value).draw();
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_add') === '1') {
        const year = urlParams.get('year');
        addRecord();
    }
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterRegime').value = '';
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterYear').value = '';
    $('#asyTable').DataTable().search('').columns().search('').draw();
}

function addRecord() {
    document.getElementById('modalAction').value = 'add_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i> Add New ASYCUDA Record';
    document.getElementById('edit_id').value = '';
    document.querySelector('#recordModal form').reset();
    new bootstrap.Modal(document.getElementById('recordModal')).show();
}

function editRecord(id) {
    const r = recordData[id];
    if (!r) return;

    document.getElementById('modalAction').value = 'update_record';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit ASYCUDA Record';
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tin').value = r.tin || '';
    document.getElementById('edit_province').value = r.province || '';
    document.getElementById('edit_process_type').value = r.process_type || '';
    document.getElementById('edit_regime_f').value = r.regime_f || '';
    document.getElementById('edit_type_customs').value = r.type_customs || '';
    document.getElementById('edit_doc_number').value = r.doc_number || '';
    document.getElementById('edit_doc_date').value = r.doc_date || '';
    document.getElementById('edit_importer_name').value = r.importer_name || '';
    document.getElementById('edit_declarant_tin').value = r.declarant_tin || '';
    document.getElementById('edit_declarant_name').value = r.declarant_name || '';
    document.getElementById('edit_hs_code').value = r.hs_code || '';
    document.getElementById('edit_goods_description').value = r.goods_description || '';
    document.getElementById('edit_quantity').value = r.quantity || 0;
    document.getElementById('edit_unit').value = r.unit || '';
    document.getElementById('edit_invoice_amount_lak').value = r.invoice_amount_lak || 0;
    document.getElementById('edit_paid_customs').value = r.paid_customs || 0;
    document.getElementById('edit_paid_excise').value = r.paid_excise || 0;
    document.getElementById('edit_paid_vat').value = r.paid_vat || 0;
    document.getElementById('edit_exemp_customs').value = r.exemp_customs || 0;
    document.getElementById('edit_exempt_excise').value = r.exempt_excise || 0;
    document.getElementById('edit_exempt_vat').value = r.exempt_vat || 0;

    new bootstrap.Modal(document.getElementById('recordModal')).show();
}
</script>

<style>
table.dataTable td { padding: 6px 8px !important; vertical-align: middle; }
table.dataTable thead th { padding: 8px 6px !important; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
.dataTables_info { font-size: 0.8rem; padding: 8px 12px !important; }
.dataTables_paginate { padding: 8px 12px !important; }
.text-nowrap { white-space: nowrap; }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
