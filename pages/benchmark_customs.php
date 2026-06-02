<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();
$message = "";
$msg_type = "success";

// Handle POST actions (Simplified for brevity, matches previous logic)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] === "save_tariff") {
            $id = $_POST["id"] ?? null;
            $data = [
                $_POST["hs_code"], $_POST["sub_code"], $_POST["description_lo"], $_POST["description_en"], $_POST["unit"],
                $_POST["rate_normal"], $_POST["rate_mfn"], $_POST["rate_atiga"], $_POST["rate_acfta"], $_POST["rate_akfta"],
                $_POST["rate_ajcep"], $_POST["rate_aanzfta"], $_POST["rate_aifta"], $_POST["rate_apta"], $_POST["rate_laoviet"],
                isset($_POST["is_header"]) ? 1 : 0, $_POST["level"] ?? 0
            ];
            if ($id) {
                $sql = "UPDATE bm_customs_tariff SET hs_code=?, sub_code=?, description_lo=?, description_en=?, unit=?, 
                        rate_normal=?, rate_mfn=?, rate_atiga=?, rate_acfta=?, rate_akfta=?, rate_ajcep=?, 
                        rate_aanzfta=?, rate_aifta=?, rate_apta=?, rate_laoviet=?, is_header=?, level=? WHERE id=?";
                $data[] = $id;
                $pdo->prepare($sql)->execute($data);
                $message = "Record updated successfully.";
            }
        }
    } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); $msg_type = "danger"; }
}

// 1. GLOBAL SEARCH LOGIC
$search = $_GET['search'] ?? '';
$search_results = [];
if ($search) {
    $stmt = $pdo->prepare("SELECT t.*, c.chapter_code, c.name_en as chapter_name, s.section_code, s.id as section_id 
                           FROM bm_customs_tariff t
                           JOIN bm_customs_chapters c ON t.chapter_id = c.id
                           JOIN bm_customs_sections s ON c.section_id = s.id
                           WHERE t.hs_code LIKE ? OR t.description_en LIKE ? OR t.description_lo LIKE ?
                           ORDER BY t.row_idx ASC LIMIT 200");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $search_results = $stmt->fetchAll();
}

// Hierarchy Selection
$selected_section_id = $_GET['section_id'] ?? null;
$selected_chapter_id = $_GET['chapter_id'] ?? null;

// Fetch Sections for Sidebar
$sections = $pdo->query("SELECT * FROM bm_customs_sections ORDER BY id ASC")->fetchAll();

// Fetch Chapters for Selected Section
$chapters = [];
if ($selected_section_id) {
    $stmt = $pdo->prepare("SELECT * FROM bm_customs_chapters WHERE section_id = ? ORDER BY id ASC");
    $stmt->execute([$selected_section_id]);
    $chapters = $stmt->fetchAll();
}

// Fetch Items for Selected Chapter
$tariffs = [];
if ($selected_chapter_id && !$search) {
    $stmt = $pdo->prepare("SELECT * FROM bm_customs_tariff WHERE chapter_id = ? ORDER BY row_idx ASC");
    $stmt->execute([$selected_chapter_id]);
    $tariffs = $stmt->fetchAll();
}

require_once __DIR__ . "/../includes/header.php";
?>

<style>
    .section-nav { height: calc(100vh - 240px); overflow-y: auto; border-right: 1px solid #dee2e6; }
    .nav-link.section-link { color: #495057; border-radius: 8px; margin-bottom: 2px; font-size: 0.85rem; padding: 8px 12px; }
    .nav-link.section-link:hover { background-color: #f8f9fa; }
    .nav-link.section-link.active { background-color: #e7f1ff; color: #0d6efd; font-weight: bold; }
    .tariff-row-header { background-color: #f8f9fa; font-weight: bold; }
    .level-1 { padding-left: 1.5rem !important; }
    .level-2 { padding-left: 2.5rem !important; }
    .level-3 { padding-left: 3.5rem !important; }
    .level-4 { padding-left: 4.5rem !important; }
    .level-5 { padding-left: 5.5rem !important; }
    .hover-shadow:hover { box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; transform: translateY(-2px); }
    .transition { transition: all 0.2s ease-in-out; }
</style>

<div class="row mb-4 align-items-end">
    <div class="col-md-6">
        <h2 class="mb-1"><i class="fas fa-ship me-2 text-primary"></i> Customs Benchmark</h2>
        <p class="text-muted mb-0">AHTN 2017 Hierarchical Database with Global Search.</p>
    </div>
    <div class="col-md-6">
        <form method="GET" class="d-flex gap-2">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Global search HS code or product..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary px-4">Search</button>
            </div>
            <?php if ($search): ?>
                <a href="benchmark_customs.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4">
    <i class="fas fa-<?= $msg_type == "success" ? "check-circle" : "exclamation-triangle" ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Sidebar: Sections -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list-ol me-2 text-primary"></i> Sections</h6>
            </div>
            <div class="card-body p-2 section-nav">
                <nav class="nav flex-column">
                    <?php foreach ($sections as $s): ?>
                    <a class="nav-link section-link <?= ($selected_section_id == $s['id'] && !$search) ? 'active' : '' ?>" 
                       href="?section_id=<?= $s['id'] ?>">
                        <span class="badge bg-primary-subtle text-primary me-2"><?= $s['section_code'] ?></span>
                        <?= htmlspecialchars(substr($s['name_en'], 0, 50)) ?><?= strlen($s['name_en']) > 50 ? '...' : '' ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9">
        <?php if ($search): ?>
            <!-- SEARCH RESULTS VIEW -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-search me-2"></i> Search Results for "<?= htmlspecialchars($search) ?>"</h5>
                    <span class="badge bg-light text-dark border"><?= count($search_results) ?> matches found</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 small">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr class="text-center">
                                    <th style="width: 120px;">HS Code</th>
                                    <th>Description (LO / EN)</th>
                                    <th>Location</th>
                                    <th style="width: 80px;">ATIGA</th>
                                    <th style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($search_results)): ?>
                                    <tr><td colspan="5" class="text-center p-5 text-muted">No results found for your search.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($search_results as $t): ?>
                                    <tr>
                                        <td class="text-center fw-bold text-primary"><?= htmlspecialchars($t['hs_code'] ?: '-') ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($t['description_en']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($t['description_lo']) ?></div>
                                        </td>
                                        <td class="small">
                                            <div class="text-muted"><?= $t['section_code'] ?></div>
                                            <div class="fw-bold text-dark">Chapter <?= $t['chapter_code'] ?></div>
                                        </td>
                                        <td class="text-center fw-bold text-primary bg-primary-subtle"><?= $t['rate_atiga'] ?>%</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="?section_id=<?= $t['section_id'] ?>&chapter_id=<?= $t['chapter_id'] ?>" class="btn btn-sm btn-outline-info" title="Go to Chapter"><i class="fas fa-external-link-alt"></i></a>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editTariff(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fas fa-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif (!$selected_section_id): ?>
            <!-- WELCOME VIEW -->
            <div class="card border-0 shadow-sm text-center p-5" style="border-radius: 12px;">
                <div class="p-5">
                    <i class="fas fa-book-open fa-4x text-light mb-4"></i>
                    <h4 class="text-muted">Explore AHTN 2017 Benchmark</h4>
                    <p class="text-muted">Select a Section from the left or use the global search above.</p>
                </div>
            </div>

        <?php elseif (!$selected_chapter_id): ?>
            <!-- CHAPTER GRID VIEW -->
            <?php $curr_sec = $sections[array_search($selected_section_id, array_column($sections, 'id'))]; ?>
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                <div class="card-body bg-primary text-white" style="border-radius: 12px;">
                    <h5 class="mb-1 fw-bold"><?= htmlspecialchars($curr_sec['section_code']) ?>: <?= htmlspecialchars($curr_sec['name_en']) ?></h5>
                    <p class="mb-0 opacity-75 small Lao-font"><?= htmlspecialchars($curr_sec['name_lo']) ?></p>
                </div>
            </div>
            
            <div class="row row-cols-1 row-cols-lg-2 g-3">
                <?php foreach ($chapters as $c): ?>
                <div class="col">
                    <a href="?section_id=<?= $selected_section_id ?>&chapter_id=<?= $c['id'] ?>" 
                       class="card h-100 border-0 shadow-sm text-decoration-none hover-shadow p-3 transition" style="border-radius: 12px;">
                        <div class="d-flex align-items-start">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold shadow-sm" style="width: 45px; height: 45px; flex-shrink: 0;">
                                <?= htmlspecialchars($c['chapter_code']) ?>
                            </div>
                            <div>
                                <div class="text-dark fw-bold mb-1"><?= htmlspecialchars($c['name_en']) ?></div>
                                <div class="text-muted small Lao-font"><?= htmlspecialchars($c['name_lo']) ?></div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

        <?php else: 
            $current_chap = $chapters[array_search($selected_chapter_id, array_column($chapters, 'id'))];
        ?>
            <!-- CHAPTER DETAIL VIEW -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-white p-2 px-3 rounded shadow-sm">
                    <li class="breadcrumb-item"><a href="benchmark_customs.php?section_id=<?= $selected_section_id ?>" class="text-decoration-none">Section <?= $sections[array_search($selected_section_id, array_column($sections, 'id'))]['section_code'] ?></a></li>
                    <li class="breadcrumb-item active">Chapter <?= $current_chap['chapter_code'] ?></li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">Chapter <?= htmlspecialchars($current_chap['chapter_code']) ?>: <?= htmlspecialchars($current_chap['name_en']) ?></h5>
                        <p class="text-muted small mb-0 Lao-font"><?= htmlspecialchars($current_chap['name_lo']) ?></p>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.location='benchmark_customs.php?section_id=<?= $selected_section_id ?>'">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 small">
                            <thead class="bg-light text-muted small text-uppercase text-center">
                                <tr>
                                    <th style="width: 110px;">HS Code</th>
                                    <th style="width: 50px;">Sub</th>
                                    <th class="text-start">Description (LO / EN)</th>
                                    <th style="width: 60px;">Unit</th>
                                    <th style="width: 60px;">Normal</th>
                                    <th style="width: 60px;" class="bg-primary-subtle">ATIGA</th>
                                    <th style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tariffs as $t): ?>
                                <tr class="<?= $t['is_header'] ? 'tariff-row-header' : '' ?>">
                                    <td class="text-center fw-bold text-primary"><?= htmlspecialchars($t['hs_code'] ?: '-') ?></td>
                                    <td class="text-center text-muted"><?= htmlspecialchars($t['sub_code']) ?></td>
                                    <td class="level-<?= min($t['level'], 5) ?>">
                                        <div class="<?= $t['is_header'] ? 'fw-bold' : '' ?>"><?= htmlspecialchars($t['description_en']) ?></div>
                                        <div class="text-muted small Lao-font"><?= htmlspecialchars($t['description_lo']) ?></div>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($t['unit']) ?></td>
                                    <td class="text-center"><?= $t['rate_normal'] ?>%</td>
                                    <td class="text-center fw-bold text-primary bg-primary-subtle"><?= $t['rate_atiga'] ?>%</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" onclick='editTariff(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
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
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal (Updated to include all description fields) -->
<div class="modal fade" id="tariffModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <form method="POST">
                <div class="modal-header bg-primary text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                    <h5 class="modal-title" id="modalTitle fw-bold"><i class="fas fa-edit me-2"></i>Edit Tariff Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" name="action" value="save_tariff">
                    <input type="hidden" name="id" id="tariff_id">
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">HS Code</label>
                            <input type="text" name="hs_code" id="tariff_hs_code" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Sub Code</label>
                            <input type="text" name="sub_code" id="tariff_sub_code" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Indentation Level</label>
                            <input type="number" name="level" id="tariff_level" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_header" id="tariff_is_header">
                                <label class="form-check-label fw-bold">Is Category Header</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">English Description</label>
                            <textarea name="description_en" id="tariff_description_en" class="form-control shadow-sm" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-info">Lao Description (ຊື່ພາສາລາວ)</label>
                            <textarea name="description_lo" id="tariff_description_lo" class="form-control shadow-sm Lao-font" rows="3"></textarea>
                        </div>

                        <div class="col-12"><hr class="my-2 text-muted"></div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Normal Rate (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="rate_normal" id="tariff_rate_normal" class="form-control">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-primary">ATIGA Rate (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="rate_atiga" id="tariff_rate_atiga" class="form-control border-primary">
                                <span class="input-group-text border-primary text-primary">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">MFN Rate (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="rate_mfn" id="tariff_rate_mfn" class="form-control">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Unit</label>
                            <input type="text" name="unit" id="tariff_unit" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTariff(t) {
    document.getElementById('tariff_id').value = t.id;
    document.getElementById('tariff_hs_code').value = t.hs_code || '';
    document.getElementById('tariff_sub_code').value = t.sub_code || '';
    document.getElementById('tariff_description_en').value = t.description_en || '';
    document.getElementById('tariff_description_lo').value = t.description_lo || '';
    document.getElementById('tariff_unit').value = t.unit || '';
    document.getElementById('tariff_rate_normal').value = t.rate_normal;
    document.getElementById('tariff_rate_atiga').value = t.rate_atiga;
    document.getElementById('tariff_rate_mfn').value = t.rate_mfn;
    document.getElementById('tariff_level').value = t.level;
    document.getElementById('tariff_is_header').checked = parseInt(t.is_header) === 1;
    new bootstrap.Modal(document.getElementById('tariffModal')).show();
}
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
