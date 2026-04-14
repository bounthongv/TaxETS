<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$batch = $_GET['batch'] ?? '';

if (!$batch) {
    die("Batch ID required.");
}

$stmt = $pdo->prepare("SELECT i.*, te.customs_te, te.excise_te, te.vat_te, te.total_te 
                       FROM asycuda_imports i 
                       LEFT JOIN te_asycuda_result te ON i.id = te.asycuda_id 
                       WHERE i.import_batch_id = ?");
$stmt->execute([$batch]);
$rows = $stmt->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-list me-2"></i> ASYCUDA Import Details</h2>
        <p class="text-muted">Batch: <code><?= htmlspecialchars($batch) ?></code> | Records: <?= count($rows) ?></p>
    </div>
    <div class="col-md-4 text-end">
        <a href="import_asycuda.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back to Import</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 750px; overflow-y: auto;">
            <table class="table table-hover table-sm mb-0 small">
                <thead class="table-light sticky-top">
                    <tr class="text-nowrap">
                        <th class="sticky-left bg-light border-end">TIN</th>
                        <th class="sticky-left-2 bg-light border-end">Regime</th>
                        <th>Province</th>
                        <th>Border</th>
                        <th>Type</th>
                        <th>Doc No</th>
                        <th>Doc Date</th>
                        <th>Assess No</th>
                        <th>Assess Date</th>
                        <th>Receipt No</th>
                        <th>Receipt Date</th>
                        <th class="name-col">Importer Name</th>
                        <th class="name-col">Declarant Name</th>
                        <th>HS Code</th>
                        <th class="desc-col">Description</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Invoice LAK</th>
                        
                        <th class="table-primary">Exemp_Customs (BM)</th>
                        <th class="table-primary">Exempt_Excise (BM)</th>
                        <th class="table-primary">Exempt_VAT (BM)</th>
                        
                        <th class="table-success">Paid Customs</th>
                        <th class="table-success">Paid Excise</th>
                        <th class="table-success">Paid VAT</th>
                        <th class="table-success">Paid Profit</th>
                        
                        <th class="table-danger sticky-right bg-danger text-white border-start">TE Customs</th>
                        <th class="table-danger sticky-right-2 bg-danger text-white border-start">TE Excise</th>
                        <th class="table-danger sticky-right-3 bg-danger text-white border-start">TE VAT</th>
                        <th class="table-danger sticky-right-4 bg-dark text-white fw-bold border-start">Total TE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="sticky-left bg-white border-end font-monospace"><?= htmlspecialchars($r['tin']) ?></td>
                        <td class="sticky-left-2 bg-white border-end text-center">
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($r['regime_code']) ?></span>
                        </td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['province']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['border_name']) ?></td>
                        <td><?= htmlspecialchars($r['type_customs']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['doc_number']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['doc_date']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['assess_number']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['assess_date']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['receipt_no']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($r['receipt_date']) ?></td>
                        
                        <td class="name-cell"><?= htmlspecialchars($r['importer_name']) ?></td>
                        <td class="name-cell"><?= htmlspecialchars($r['declarant_name']) ?></td>
                        
                        <td class="font-monospace"><?= htmlspecialchars($r['hs_code']) ?></td>
                        <td class="desc-cell"><?= htmlspecialchars($r['goods_description']) ?></td>
                        
                        <td class="text-end"><?= number_format($r['quantity']) ?></td>
                        <td><?= htmlspecialchars($r['unit']) ?></td>
                        <td class="text-end"><?= number_format($r['invoice_amount_lak']) ?></td>
                        
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exemp_customs']) ?></td>
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exempt_excise']) ?></td>
                        <td class="text-end text-primary fw-bold"><?= number_format($r['exempt_vat']) ?></td>
                        
                        <td class="text-end"><?= number_format($r['paid_customs']) ?></td>
                        <td class="text-end"><?= number_format($r['paid_excise']) ?></td>
                        <td class="text-end"><?= number_format($r['paid_vat']) ?></td>
                        <td class="text-end"><?= number_format($r['paid_profit']) ?></td>
                        
                        <td class="text-end fw-bold text-danger sticky-right border-start"><?= number_format($r['customs_te']) ?></td>
                        <td class="text-end fw-bold text-danger sticky-right-2 border-start"><?= number_format($r['excise_te']) ?></td>
                        <td class="text-end fw-bold text-danger sticky-right-3 border-start"><?= number_format($r['vat_te']) ?></td>
                        <td class="text-end fw-bold text-danger sticky-right-4 border-start bg-light"><?= number_format($r['total_te']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.sticky-left { position: sticky; left: 0; z-index: 10; }
.sticky-left-2 { position: sticky; left: 120px; z-index: 10; }

.sticky-right-4 { position: sticky; right: 0; z-index: 10; min-width: 100px; }
.sticky-right-3 { position: sticky; right: 100px; z-index: 10; min-width: 100px; }
.sticky-right-2 { position: sticky; right: 200px; z-index: 10; min-width: 100px; }
.sticky-right { position: sticky; right: 300px; z-index: 10; min-width: 100px; }

.table th, .table td { padding: 8px 12px; }
.name-col { min-width: 200px; }
.name-cell { white-space: normal; min-width: 200px; font-size: 0.8rem; line-height: 1.2; }
.desc-col { min-width: 250px; }
.desc-cell { white-space: normal; min-width: 250px; font-size: 0.8rem; line-height: 1.2; }
.text-nowrap { white-space: nowrap; }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
