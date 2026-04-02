<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/header.php";

$pdo = getDbConnection();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 100;
$offset = ($page - 1) * $limit;

// Simple search
$search = $_GET['search'] ?? '';
$where = "";
$params = [];
if ($search) {
    $where = " WHERE hs_code LIKE ? OR description_en LIKE ? OR description_lo LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Count total records for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM bm_customs_tariff $where");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$stmt = $pdo->prepare("SELECT * FROM bm_customs_tariff $where LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$tariffs = $stmt->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-ship me-2"></i> Customs Benchmark (AHTN 2017)</h2>
        <p class="text-muted">Standard tariff rates used for reference and future TE calculations.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="import_tariff.php" class="btn btn-primary"><i class="fas fa-upload me-2"></i> Import Tariff</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <form class="row g-2" method="GET">
            <div class="col-auto">
                <input type="text" name="search" class="form-control" placeholder="Search HS Code or Description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                <a href="benchmark_customs.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
            <div class="col text-end pt-2 text-muted small">
                Total Records: <strong><?= number_format($total_records) ?></strong>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-width: 100%; overflow-x: auto;">
            <table class="table table-hover table-sm mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="sticky-column bg-light text-nowrap">HS Code</th>
                        <th class="text-nowrap">Sub-code</th>
                        <th class="desc-col">Description (LO)</th>
                        <th class="desc-col">Description (EN)</th>
                        <th class="text-nowrap">Unit</th>
                        <th class="rate-col">Normal</th>
                        <th class="rate-col">MFN</th>
                        <th class="rate-col">ATIGA</th>
                        <th class="rate-col">ACFTA</th>
                        <th class="rate-col">AKFTA</th>
                        <th class="rate-col">AJCEP</th>
                        <th class="rate-col">AANZ</th>
                        <th class="rate-col">AIFTA</th>
                        <th class="rate-col">APTA</th>
                        <th class="rate-col">VN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tariffs)): ?>
                    <tr><td colspan="15" class="text-center p-4 text-muted">No tariff data found. Please import the Excel file.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tariffs as $t): ?>
                        <tr>
                            <td class="fw-bold sticky-column bg-white text-nowrap"><?= htmlspecialchars($t['hs_code']) ?></td>
                            <td class="text-center text-nowrap"><?= htmlspecialchars($t['sub_code']) ?></td>
                            <td class="desc-cell"><?= htmlspecialchars($t['description_lo']) ?></td>
                            <td class="desc-cell"><?= htmlspecialchars($t['description_en']) ?></td>
                            <td class="text-nowrap text-center"><?= htmlspecialchars($t['unit']) ?></td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_normal']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_mfn']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell text-primary fw-bold"><?= htmlspecialchars($t['rate_atiga']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_acfta']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_akfta']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_ajcep']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_aanzfta']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_aifta']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_apta']) ?>%</td>
                            <td class="text-center text-nowrap rate-cell"><?= htmlspecialchars($t['rate_laoviet']) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>
    .sticky-column {
        position: sticky;
        left: 0;
        z-index: 5;
        border-right: 2px solid #dee2e6 !important;
        box-shadow: 2px 0 5px rgba(0,0,0,0.05);
    }
    .table th, .table td {
        padding: 8px 10px;
        vertical-align: middle;
    }
    .desc-col {
        min-width: 250px;
        max-width: 350px;
    }
    .desc-cell {
        white-space: normal;
        min-width: 250px;
        max-width: 350px;
        line-height: 1.4;
        font-size: 0.85rem;
    }
    .rate-col {
        min-width: 80px;
        text-align: center;
    }
    .rate-cell {
        background-color: rgba(0,0,0,0.01);
    }
    .text-nowrap {
        white-space: nowrap;
    }
    </style>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Page <?= $page ?> of <?= $total_pages ?>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    </div>
</div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
