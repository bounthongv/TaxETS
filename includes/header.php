<?php require_once __DIR__ . "/../config.php"; ?>
<?php require_once __DIR__ . "/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper d-flex">
    <?php include __DIR__ . "/sidebar_FIXED.php"; ?>
    <div id="content">
        <nav class="navbar navbar-expand-lg topbar">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-outline-success"><i class="fas fa-align-left"></i></button>
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-3"><i class="fas fa-user-circle fa-lg text-secondary"></i> Admin</span>
                    <a href="#" class="btn btn-sm btn-outline-danger">Logout</a>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
