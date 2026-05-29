<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';
$message = '';
$msg_type = 'success';

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

// --- Handle Update Record ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] === "update_record") {
            $id = (int)($_POST["id"] ?? 0);
            if (!$id) throw new Exception("Record ID required.");

            $exempt_vat = (float)($_POST["exempt_vat"] ?? 0);
            $paid_vat = (float)($_POST["paid_vat"] ?? 0);
            $vat_te = max(0, $exempt_vat - $paid_vat);

            $stmt = $pdo->prepare("UPDATE asycuda_imports SET exempt_vat = ?, paid_vat = ? WHERE id = ?");
            $stmt->execute([$exempt_vat, $paid_vat, $id]);

            $stmt_te = $pdo->prepare("UPDATE te_asycuda_result SET vat_te = ?, total_te = customs_te + excise_te + vat_te WHERE asycuda_id = ?");
            $stmt_te->execute([$vat_te, $id]);

            $message = "Import VAT record updated successfully.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// --- Batch List Mode (no batch selected) ---
if (!$batch):
    $all_rows = $pdo->query("SELECT i.*, YEAR(i.doc_date) as import_year 
                            FROM asycuda_imports i 
                            ORDER BY i.id DESC")->fetchAll();

    $total_records = count($all_rows);
    $total_bm = array_sum(array_column($all_rows, 'exempt_vat'));
    $total_paid = array_sum(array_column($all_rows, 'paid_vat'));

    $batches = $pdo->query("SELECT import_batch_id, MIN(DATE(import_date)) as idate, COUNT(*) as `rows`,
                            SUM(exempt_vat) as total_bm, SUM(paid_vat) as total_paid
                            FROM asycuda_imports i
                            GROUP BY import_batch_id ORDER BY MAX(i.id) DESC")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-file-invoice-dollar me-2 text-info"></i> Import VAT (ASYCUDA)</h2>
        <p class="text-muted">View, edit, and calculate Tax Expenditure for Import VAT from ASYCUDA imports.</p>
    </div>
    <div class="col-md-4 text-end"></div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row mb-3 g-2">
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Total Records</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_bm) ?></div>
            <div class="small opacity-75">Total BM VAT</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">Total Paid VAT</div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-secondary"></i> Import Batches</div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
            <h5>No ASYCUDA Data Found</h5>
            <p>Import data from <a href="import_asycuda.php">Import New Data</a> page.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Batch</th><th>Date</th><th>Records</th><th class="text-end">BM VAT</th><th class="text-end">Paid VAT</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <?php
                    $log_file = __DIR__ . "/../data/logs/" . $b["import_batch_id"] . ".log";
                    $has_log = file_exists($log_file);
                ?>
                <tr>
                    <td><small class="font-monospace"><?= htmlspecialchars($b["import_batch_id"]) ?></small></td>
                    <td><?= htmlspecialchars($b["idate"]) ?></td>
                    <td><span class="badge bg-primary rounded-pill px-3"><?= number_format($b["rows"]) ?></span></td>
                    <td class="text-end fw-bold text-info"><?= number_format($b["total_bm"]) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($b["total_paid"]) ?></td>
                    <td>
                        <a href="?batch=<?= urlencode($b["import_batch_id"]) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                        <a href="te_asycuda_vat.php?batch=<?= urlencode($b["import_batch_id"]) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                        <?php if ($has_log): ?>
                        <a href="download_log.php?log_id=<?= urlencode($b["import_batch_id"]) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                        <?php endif; ?>
                        <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete this batch and all TE results?')">
                            <input type="hidden" name="type" value="asy">
                            <input type="hidden" name="batch_id" value="<?= htmlspecialchars($b["import_batch_id"]) ?>">
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

<?php
// --- Batch Detail Mode ---
else:
    $stmt = $pdo->prepare("SELECT i.*, YEAR(i.doc_date) as import_year, te.customs_te, te.excise_te, te.vat_te, te.total_te 
                           FROM asycuda_imports i 
                           LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                           WHERE i.import_batch_id = ?
                           ORDER BY i.id ASC");
    $stmt->execute([$batch]);
    $rows = $stmt->fetchAll();

    $total_records = count($rows);
    $total_bm = array_sum(array_column($rows, 'exempt_vat'));
    $total_paid = array_sum(array_column($rows, 'paid_vat'));

    $regimes = $pdo->prepare("SELECT DISTINCT regime_code FROM asycuda_imports WHERE import_batch_id = ? AND regime_code != '' ORDER BY regime_code");
    $regimes->execute([$batch]);
    $regime_list = $regimes->fetchAll();

    $years = $pdo->prepare("SELECT DISTINCT YEAR(doc_date) as y FROM asycuda_imports WHERE import_batch_id = ? AND doc_date IS NOT NULL ORDER BY y DESC");
    $years->execute([$batch]);
    $year_list = $years->fetchAll();

    $provinces = $pdo->query("SELECT province_name FROM provinces ORDER BY province_name")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><a href="asycuda_vat.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Import VAT: Batch Details</h2>
        <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> — <strong><?= $total_records ?></strong> records</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="te_asycuda_vat.php?batch=<?= urlencode($batch) ?>" class="btn btn-success"><i class="fas fa-calculator me-2"></i> Run TE Calculation</a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-3 g-2">
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_records) ?></div>
            <div class="small opacity-75">Records</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_bm) ?></div>
            <div class="small opacity-75">BM VAT</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <div class="fs-5 fw-bold"><?= number_format($total_paid) ?></div>
            <div class="small opacity-75">Paid VAT</div>
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
                    <?php foreach ($regime_list as $r): ?>
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
                    <option value="">All</option>
                    <?php foreach ($year_list as $y): ?>
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

<!-- Data Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="vatTable" class="table table-bordered table-hover w-100" style="font-size:0.85em">
                <thead class="text-uppercase small">
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th class="table-secondary">TIN</th>
                        <th class="table-secondary">Regime</th>
                        <th class="table-secondary">Province</th>
                        <th class="table-secondary">Year</th>
                        <th class="table-secondary">Doc No</th>
                        <th class="table-secondary">Doc Date</th>
                        <th class="table-secondary">Importer</th>
                        <th class="table-secondary">HS Code</th>
                        <th class="table-secondary">Qty</th>
                        <th class="table-secondary text-end">Invoice LAK</th>
                        <th class="text-end fw-bold">BM VAT</th>
                        <th class="text-end fw-bold">Paid VAT</th>
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
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exempt_vat']) ?></td>
                        <td class="text-end text-success fw-bold"><?= number_format($r['paid_vat']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick='editRecord(<?= (int)$r["id"] ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="14" class="text-center p-4 text-muted">No records found for this batch.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title"><i class="fas fa-edit me-2 text-info"></i> Edit Import VAT Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_record">
          <input type="hidden" name="id" id="edit_id">

          <!-- Common Fields (Read-Only) -->
          <div class="card bg-light border-0 mb-3">
            <div class="card-header bg-transparent fw-bold py-2 small text-muted">
              <i class="fas fa-info-circle me-1"></i> Common Information <small>(imported from ASYCUDA — not editable)</small>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold mb-0">TIN</label>
                  <input type="text" id="edit_tin" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold mb-0">Regime Code</label>
                  <input type="text" id="edit_regime" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold mb-0">Province</label>
                  <input type="text" id="edit_province" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold mb-0">Doc Number</label>
                  <input type="text" id="edit_doc_number" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-3 mb-2">
                  <label class="form-label small fw-bold mb-0">Doc Date</label>
                  <input type="text" id="edit_doc_date" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-5 mb-2">
                  <label class="form-label small fw-bold mb-0">Importer Name</label>
                  <input type="text" id="edit_importer" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-3 mb-2">
                  <label class="form-label small fw-bold mb-0">HS Code</label>
                  <input type="text" id="edit_hs_code" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-5 mb-2">
                  <label class="form-label small fw-bold mb-0">Goods Description</label>
                  <input type="text" id="edit_description" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-2 mb-2">
                  <label class="form-label small fw-bold mb-0">Quantity</label>
                  <input type="text" id="edit_qty" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold mb-0">Invoice Amount (LAK)</label>
                  <input type="text" id="edit_invoice" class="form-control form-control-sm bg-white" readonly tabindex="-1">
                </div>
              </div>
            </div>
          </div>

          <!-- Import VAT Fields (Editable) -->
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2 small">
              <i class="fas fa-edit me-1 text-info"></i> Import VAT Fields <small class="text-muted">(editable)</small>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold text-primary">Benchmark VAT</label>
                  <input type="number" step="0.01" name="exempt_vat" id="edit_exempt_vat" class="form-control form-control-sm border-primary">
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small fw-bold text-success">Paid VAT</label>
                  <input type="number" step="0.01" name="paid_vat" id="edit_paid_vat" class="form-control form-control-sm border-success">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white"><i class="fas fa-save me-1"></i> Save Changes</button>
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
    const table = $('#vatTable').DataTable({
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
});

function resetFilters() {
    document.getElementById('customSearch').value = '';
    document.getElementById('filterRegime').value = '';
    document.getElementById('filterProvince').value = '';
    document.getElementById('filterYear').value = '';
    $('#vatTable').DataTable().search('').columns().search('').draw();
}

function editRecord(id) {
    const r = recordData[id];
    if (!r) return;

    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_tin').value = r.tin || '';
    document.getElementById('edit_regime').value = r.regime_code || '';
    document.getElementById('edit_province').value = r.province || '';
    document.getElementById('edit_doc_number').value = r.doc_number || '';
    document.getElementById('edit_doc_date').value = r.doc_date || '';
    document.getElementById('edit_importer').value = r.importer_name || '';
    document.getElementById('edit_hs_code').value = r.hs_code || '';
    document.getElementById('edit_description').value = r.goods_description || '';
    document.getElementById('edit_qty').value = r.quantity ? Number(r.quantity).toLocaleString() : '0';
    document.getElementById('edit_invoice').value = r.invoice_amount_lak ? Number(r.invoice_amount_lak).toLocaleString() : '0';
    document.getElementById('edit_exempt_vat').value = r.exempt_vat || 0;
    document.getElementById('edit_paid_vat').value = r.paid_vat || 0;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<style>
table.dataTable td { padding: 6px 8px !important; vertical-align: middle; }
table.dataTable thead th { padding: 8px 6px !important; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
.dataTables_info { font-size: 0.8rem; padding: 8px 12px !important; }
.dataTables_paginate { padding: 8px 12px !important; }
</style>

<?php
endif;
require_once __DIR__ . "/../includes/footer.php";
