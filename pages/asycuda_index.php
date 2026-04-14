<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="col-md-6 text-center text-muted">
        <i class="fas fa-file-invoice fa-4x mb-4 opacity-50 text-indigo"></i>
        <h3 class="fw-normal text-dark">ASYCUDA Data Menu</h3>
        <p class="fs-5">Please select a sub-menu under <strong>Data from ASYCUDA</strong> on the left to proceed.</p>
        <p class="text-secondary">For example, click <strong class="text-primary">"Import New Data"</strong> to begin importing your files.</p>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
