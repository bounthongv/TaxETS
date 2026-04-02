<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$rows = $pdo->query("SELECT * FROM asycuda_imports ORDER BY id DESC LIMIT 500")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="fas fa-wine-bottle me-2"></i> ASYCUDA Data: Excise Tax</h2>
        <p class="text-muted">Common fields and Excise Tax benchmark vs paid data.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
            <table class="table table-hover table-sm mb-0 small text-nowrap">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="sticky-left bg-light border-end">TIN</th>
                        <th class="sticky-left-2 bg-light border-end">Regime</th>
                        <th>Province</th>
                        <th>Border</th>
                        <th>Type</th>
                        <th>Doc No</th>
                        <th>Doc Date</th>
                        <th>Assess No</th>
                        <th>Receipt No</th>
                        <th class="name-col">Importer Name</th>
                        <th class="name-col">Declarant Name</th>
                        <th>HS Code</th>
                        <th class="desc-col">Description</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Invoice LAK</th>
                        <th class="table-primary text-center border-start">Exempt_Excise (Benchmark)</th>
                        <th class="table-success text-center border-start">Excise (Paid)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="sticky-left bg-white border-end font-monospace"><?= htmlspecialchars($r['tin']) ?></td>
                        <td class="sticky-left-2 bg-white border-end text-center">
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($r['regime_code']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($r['province']) ?></td>
                        <td><?= htmlspecialchars($r['border_name']) ?></td>
                        <td><?= htmlspecialchars($r['type_customs']) ?></td>
                        <td><?= htmlspecialchars($r['doc_number']) ?></td>
                        <td><?= htmlspecialchars($r['doc_date']) ?></td>
                        <td><?= htmlspecialchars($r['assess_number']) ?></td>
                        <td><?= htmlspecialchars($r['receipt_no']) ?></td>
                        <td class="name-cell"><?= htmlspecialchars($r['importer_name']) ?></td>
                        <td class="name-cell"><?= htmlspecialchars($r['declarant_name']) ?></td>
                        <td class="font-monospace"><?= htmlspecialchars($r['hs_code']) ?></td>
                        <td class="desc-cell"><?= htmlspecialchars($r['goods_description']) ?></td>
                        <td class="text-end"><?= number_format($r['quantity']) ?></td>
                        <td><?= htmlspecialchars($r['unit']) ?></td>
                        <td class="text-end"><?= number_format($r['invoice_amount_lak']) ?></td>
                        <td class="text-end fw-bold text-primary border-start"><?= number_format($r['exempt_excise']) ?></td>
                        <td class="text-end fw-bold text-success border-start"><?= number_format($r['paid_excise']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="18" class="text-center p-4 text-muted">No ASYCUDA data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.sticky-left { position: sticky; left: 0; z-index: 10; }
.sticky-left-2 { position: sticky; left: 120px; z-index: 10; }
.name-col { min-width: 200px; }
.name-cell { white-space: normal; min-width: 200px; font-size: 0.8rem; line-height: 1.2; }
.desc-col { min-width: 250px; }
.desc-cell { white-space: normal; min-width: 250px; font-size: 0.8rem; line-height: 1.2; }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
