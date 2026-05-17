<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM repo_taxris WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) { die("Record not found"); }
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><a href="repo_taxris.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a> Edit TaxRIS Yearly Data</h2>
        <p class="text-muted">Modify record for <strong><?= htmlspecialchars($record['company_name'] ?: $record['tin']) ?></strong> - Year: <strong><?= $record['year'] ?></strong>.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="save_taxris.php" method="POST">
            <input type="hidden" name="id" value="<?= $record['id'] ?>">
            
            <!-- 1. Basic & Financial Info -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-info-circle me-2"></i> 1. Basic & Financial Information</h5></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">TIN</label>
                    <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($record['tin']) ?>" required readonly bg-light>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label fw-bold">Organisation (Company Name)</label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($record['company_name']) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Fiscal Year <span class="text-danger">*</span></label>
                    <input type="number" name="year" class="form-control" required value="<?= $record['year'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Revenue</label>
                    <input type="number" step="0.01" name="revenue" class="form-control" value="<?= $record['revenue'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Expense</label>
                    <input type="number" step="0.01" name="expense" class="form-control" value="<?= $record['expense'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Net Profit</label>
                    <input type="number" step="0.01" name="net_profit" class="form-control" value="<?= $record['net_profit'] ?>">
                </div>
            </div>

            <!-- 2. Tax & Asset Details -->
            <div class="row border-bottom mb-4 pb-3">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-money-bill-wave me-2"></i> 2. Tax & Asset Details</h5></div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Tax Paid</label>
                    <input type="number" step="0.01" name="tax_paid" class="form-control" value="<?= $record['tax_paid'] ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Rate Paid (%)</label>
                    <input type="number" step="0.01" name="tax_rate_paid" class="form-control" value="<?= $record['tax_rate_paid'] ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">TE Dummy</label>
                    <input type="text" name="te_dummy" class="form-control" value="<?= htmlspecialchars($record['te_dummy']) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Total Assets</label>
                    <input type="number" step="0.01" name="total_assets" class="form-control" value="<?= $record['total_assets'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Annual turnover (billion Kip)</label>
                    <input type="number" step="0.01" name="annual_turnover_bn" class="form-control" value="<?= $record['annual_turnover_bn'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Total assets (billion Kip)</label>
                    <input type="number" step="0.01" name="total_assets_bn" class="form-control" value="<?= $record['total_assets_bn'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">VAT System</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="vat_system_status" id="vatToggle" value="1" <?= $record['vat_system_status'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="vatToggle">Holding VAT System</label>
                    </div>
                </div>
            </div>

            <!-- 3. Re-investment & Specific Income -->
            <div class="row mb-4">
                <div class="col-12"><h5 class="text-primary"><i class="fas fa-hand-holding-heart me-2"></i> 3. Re-investment & Specific Categories</h5></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Re-Invest Net Profit</label>
                    <input type="number" step="0.01" name="reinvest_net_profit" class="form-control" value="<?= $record['reinvest_net_profit'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Date of Re-invest</label>
                    <input type="date" name="reinvest_date" class="form-control" value="<?= $record['reinvest_date'] ?>">
                </div>
                
                <div class="col-12 mt-3">
                    <div class="row">
                        <?php 
                        $selects = [
                            'is_public_income' => 'Income from public benefit activities (Art, Sport, etc.)',
                            'is_asset_rent' => 'Rent from assets of compliant business operators',
                            'is_real_estate_transfer' => 'Income from transfer of real estate rights (in balance sheet)',
                            'is_vat_enterprise' => 'Included in list of enterprises holding VAT system'
                        ];
                        foreach ($selects as $key => $label): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold"><?= $label ?></label>
                            <select name="<?= $key ?>" class="form-select form-select-sm">
                                <option value="2" <?= $record[$key] == 2 ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= $record[$key] == 1 ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="repo_taxris.php" class="btn btn-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5"><i class="fas fa-save me-2"></i> Update Yearly Record</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
