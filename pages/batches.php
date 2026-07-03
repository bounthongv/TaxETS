<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/report_filters.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();
$errors = [];

$typeFilter = $_GET["type"] ?? "";
$search = trim($_GET["q"] ?? "");

$batchTypes = [
    "cit" => ["label" => "Profit Tax", "icon" => "fa-building", "view" => "view_companies.php", "calc" => "calculator.php", "import" => "import_cit.php", "delete" => "cit"],
    "pit" => ["label" => "Individual Tax", "icon" => "fa-user", "view" => "view_individual.php", "calc" => "te_individual.php", "import" => "import_individual.php", "delete" => "pit"],
    "salary" => ["label" => "Salary Tax", "icon" => "fa-wallet", "view" => "view_salary.php", "calc" => "te_salary_tax.php", "import" => "import_salary.php", "delete" => "salary"],
    "vat" => ["label" => "Domestic VAT", "icon" => "fa-receipt", "view" => "view_vat.php", "calc" => "te_vat.php", "import" => "import_domestic_vat.php", "delete" => "vat"],
    "sez_dev" => ["label" => "SEZ Developer", "icon" => "fa-hard-hat", "view" => "view_sez_dev.php", "calc" => "te_sez_dev.php", "import" => "import_sez_dev.php", "delete" => "sez_dev"],
    "sez_inv" => ["label" => "SEZ Investor", "icon" => "fa-helmet-safety", "view" => "view_sez_inv.php", "calc" => "te_sez_inv.php", "import" => "import_sez_inv.php", "delete" => "sez_inv"],
    "resource" => ["label" => "Resource Fee", "icon" => "fa-oil-can", "view" => "view_resource.php", "calc" => "te_resource.php", "import" => "import_resource.php", "delete" => "resource"],
    "royalty" => ["label" => "Royalty Fee", "icon" => "fa-gem", "view" => "view_royalty.php", "calc" => "te_royalty.php", "import" => "import_royalty.php", "delete" => "royalty"],
    "land" => ["label" => "Land Concession", "icon" => "fa-tree", "view" => "repo_land_concession.php", "calc" => "calculate_land_concession.php", "import" => "import_land_concession.php", "delete" => "land"],
    "asy" => ["label" => "ASYCUDA", "icon" => "fa-ship", "view" => "view_asycuda.php", "calc" => "", "import" => "import_asycuda.php", "delete" => "asy"],
];

function batchTimestampExpr(string $column): string {
    return "STR_TO_DATE(REGEXP_SUBSTR({$column}, '[0-9]{14}'), '%Y%m%d%H%i%s')";
}

function addBatchRows(PDO $pdo, array &$rows, string $type, string $sql): void {
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row["type"] = $type;
        $rows[] = $row;
    }
}

$rows = [];
try {
    addBatchRows($pdo, $rows, "cit", "SELECT c.import_batch_id AS batch_id, COUNT(*) AS row_count, MIN(c.tax_year) AS min_year, MAX(c.tax_year) AS max_year, COALESCE(SUM(r.profit_tax_te), 0) AS total_te, " . batchTimestampExpr("c.import_batch_id") . " AS import_date FROM companies c LEFT JOIN te_profit_result r ON r.company_id = c.id WHERE c.import_batch_id IS NOT NULL AND c.import_batch_id != '' GROUP BY c.import_batch_id");
    addBatchRows($pdo, $rows, "pit", "SELECT batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, 0 AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_pit_data WHERE batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "salary", "SELECT batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, COALESCE(SUM(te_amount), 0) AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_salary_tax_data WHERE batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "vat", "SELECT batch_id, COUNT(*) AS row_count, MIN(YEAR(filing_period)) AS min_year, MAX(YEAR(filing_period)) AS max_year, COALESCE(SUM(expert_te), 0) AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_vat_data WHERE batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "sez_dev", "SELECT batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, COALESCE(SUM(te_amount), 0) AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_sez_data WHERE type = 'Developer' AND batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "sez_inv", "SELECT batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, COALESCE(SUM(te_amount), 0) AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_sez_data WHERE type = 'Investor' AND batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "resource", "SELECT batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, COALESCE(SUM(te_amount), 0) AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_resource_data WHERE batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "royalty", "SELECT batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, COALESCE(SUM(te_amount), 0) AS total_te, COALESCE(MAX(NULLIF(import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("batch_id") . ") AS import_date FROM import_royalty_data WHERE batch_id IS NOT NULL AND batch_id != '' GROUP BY batch_id");
    addBatchRows($pdo, $rows, "land", "SELECT import_batch_id AS batch_id, COUNT(*) AS row_count, MIN(tax_year) AS min_year, MAX(tax_year) AS max_year, COALESCE(SUM(non_tax_te_usd), 0) AS total_te, COALESCE(MAX(NULLIF(created_at, '0000-00-00 00:00:00')), " . batchTimestampExpr("import_batch_id") . ") AS import_date FROM repo_land_concession_data WHERE import_batch_id IS NOT NULL AND import_batch_id != '' GROUP BY import_batch_id");
    addBatchRows($pdo, $rows, "asy", "SELECT i.import_batch_id AS batch_id, COUNT(*) AS row_count, MIN(YEAR(i.doc_date)) AS min_year, MAX(YEAR(i.doc_date)) AS max_year, COALESCE(SUM(r.total_te), 0) AS total_te, COALESCE(MAX(NULLIF(i.import_date, '0000-00-00 00:00:00')), " . batchTimestampExpr("i.import_batch_id") . ") AS import_date FROM asycuda_imports i LEFT JOIN te_asycuda_result r ON r.asycuda_id = i.id WHERE i.import_batch_id IS NOT NULL AND i.import_batch_id != '' GROUP BY i.import_batch_id");
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

$rows = array_values(array_filter($rows, function(array $row) use ($typeFilter, $search): bool {
    if ($typeFilter !== "" && $row["type"] !== $typeFilter) {
        return false;
    }
    if ($search !== "") {
        $haystack = strtolower(($row["batch_id"] ?? "") . " " . ($row["type"] ?? ""));
        return strpos($haystack, strtolower($search)) !== false;
    }
    return true;
}));

usort($rows, function(array $a, array $b): int {
    return strcmp((string)($b["import_date"] ?? ""), (string)($a["import_date"] ?? ""));
});

$totalRows = array_sum(array_map(fn($row) => (int)$row["row_count"], $rows));
$totalTe = array_sum(array_map(fn($row) => (float)$row["total_te"], $rows));
$batchCount = count($rows);
?>
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold text-dark"><i class="fas fa-layer-group me-2 text-primary"></i> Batch Management Hub</h2>
        <p class="text-muted mb-0">Central view of imported TE estimation batches across all tax and non-tax types.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="import_cit.php" class="btn btn-primary shadow-sm"><i class="fas fa-file-import me-2"></i> Import Data</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars(implode("; ", $errors)) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="small text-muted text-uppercase fw-bold">Batches</div><div class="fs-3 fw-bold"><?= number_format($batchCount) ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="small text-muted text-uppercase fw-bold">Imported Rows</div><div class="fs-3 fw-bold"><?= number_format($totalRows) ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="small text-muted text-uppercase fw-bold">Recorded TE</div><div class="fs-3 fw-bold"><?= number_format($totalTe, 0) ?></div></div></div></div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light rounded-3">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Tax Type</label>
                <select name="type" class="form-select border-0 shadow-sm">
                    <option value="">All types</option>
                    <?php foreach ($batchTypes as $key => $meta): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $typeFilter === $key ? "selected" : "" ?>><?= htmlspecialchars($meta["label"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted text-uppercase">Search Batch ID</label>
                <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control border-0 shadow-sm" placeholder="Batch ID">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100 shadow-sm"><i class="fas fa-filter me-2"></i> Filter</button></div>
            <div class="col-md-2"><a href="batches.php" class="btn btn-outline-secondary w-100 border-0">Reset</a></div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4">Tax Type</th>
                        <th>Batch ID</th>
                        <th class="text-end">Rows</th>
                        <th>Years</th>
                        <th>Import Date</th>
                        <th class="text-end">TE</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">No batches found.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): $meta = $batchTypes[$row["type"]]; $batchId = (string)$row["batch_id"]; ?>
                    <tr>
                        <td class="ps-4"><i class="fas <?= htmlspecialchars($meta["icon"]) ?> me-2 text-primary"></i><?= htmlspecialchars($meta["label"]) ?></td>
                        <td><small class="font-monospace"><?= htmlspecialchars($batchId) ?></small></td>
                        <td class="text-end"><?= number_format((int)$row["row_count"]) ?></td>
                        <td>
                            <?php if (!empty($row["min_year"]) && !empty($row["max_year"])): ?>
                                <?= (int)$row["min_year"] === (int)$row["max_year"] ? htmlspecialchars((string)(int)$row["min_year"]) : htmlspecialchars((int)$row["min_year"] . " - " . (int)$row["max_year"]) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($row["import_date"]) ? htmlspecialchars(date("Y-m-d H:i", strtotime($row["import_date"]))) : '<span class="text-muted">-</span>' ?></td>
                        <td class="text-end"><?= (float)$row["total_te"] > 0 ? number_format((float)$row["total_te"], 0) : '<span class="text-muted">-</span>' ?></td>
                        <td class="text-end pe-4">
                            <a href="<?= htmlspecialchars($meta["view"]) ?>?batch=<?= urlencode($batchId) ?>&from=batches" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                            <?php if ($row["type"] === "asy"): ?>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" title="Calculate"><i class="fas fa-calculator"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="te_asycuda_customs.php?batch=<?= urlencode($batchId) ?>">Customs Duty TE</a></li>
                                        <li><a class="dropdown-item" href="te_asycuda_excise.php?batch=<?= urlencode($batchId) ?>">Excise Tax TE</a></li>
                                        <li><a class="dropdown-item" href="te_asycuda_vat.php?batch=<?= urlencode($batchId) ?>">Import VAT TE</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($meta["calc"]) ?>?batch=<?= urlencode($batchId) ?>" class="btn btn-sm btn-outline-success" title="Calculate"><i class="fas fa-calculator"></i></a>
                            <?php endif; ?>
                            <?php if (file_exists(__DIR__ . "/../data/logs/" . $batchId . ".log")): ?>
                                <a href="download_log.php?log_id=<?= urlencode($batchId) ?>" class="btn btn-sm btn-outline-danger" title="Download Log"><i class="fas fa-file-alt"></i></a>
                            <?php endif; ?>
                            <form method="POST" action="delete_batch.php" class="d-inline" onsubmit="return confirm('Delete this batch and related TE results?')">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($meta["delete"]) ?>">
                                <input type="hidden" name="batch_id" value="<?= htmlspecialchars($batchId) ?>">
                                <button class="btn btn-sm btn-outline-secondary" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
