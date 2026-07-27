<?php
// Auth check - include at top of pages that need login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Update last activity and validate session
if (isset($_SESSION["session_token"])) {
    $pdo = getDbConnection();
    if (tableExists($pdo, 'user_sessions')) {
        // Check if this session is still valid (not invalidated by another login)
        $stmt = $pdo->prepare("SELECT is_online FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$_SESSION["session_token"]]);
        $row = $stmt->fetch();
        if (!$row || !$row['is_online']) {
            session_destroy();
            header("Location: " . BASE_URL . "/login.php?reason=kicked");
            exit;
        }
        // Update last activity
        $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
        $stmt->execute([$_SESSION["session_token"]]);
    }
}

// ─── Role-Based Permission Check ───────────────────────────────────────────
// Derive module name from the current page filename.
// Example: /pages/import_cit.php → "import_cit"
$current_page = basename($_SERVER["SCRIPT_NAME"] ?? $_SERVER["PHP_SELF"] ?? "");
$current_module = str_replace(".php", "", $current_page);

// Skip permission check for login, API endpoints, and static assets
$skip_modules = ["login", "index", "change_password", "api_get_company_info", "get_districts", "get_villages", "save_gdp", "save_lse", "save_moic", "save_molsw", "save_mpi", "save_sezo", "save_taxris", "save_art9_activity"];

if (!in_array($current_module, $skip_modules)) {
    // Cache permission check to avoid repeated DB queries
    if (!function_exists('checkPagePermission')) {
        function checkPagePermission($pdo, $module) {
            $role_id = $_SESSION["role_id"] ?? null;
            if (!$role_id) return false;

            // SUPER ADMIN (id=1) and ADMIN (id=2) bypass all checks
            if ((int)$role_id <= 2) return true;

            // Check role_permissions table
            $stmt = $pdo->prepare("SELECT can_read FROM role_permissions WHERE role_id = ? AND module = ?");
            $stmt->execute([$role_id, $module]);
            $perm = $stmt->fetch();
            return $perm && $perm['can_read'];
        }
    }

    $pdo_local = $pdo ?? getDbConnection();
    if (!checkPagePermission($pdo_local, $current_module)) {
        http_response_code(403);
        die("
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Access Denied - Tax-ETS</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; min-height: 100vh; }
        .denied-card { max-width: 500px; margin: 0 auto; text-align: center; }
    </style>
</head>
<body>
    <div class='denied-card'>
        <div class='card shadow-sm border-0 p-4' style='border-radius: 16px;'>
            <i class='fas fa-lock fa-4x text-danger mb-3'></i>
            <h3 class='text-danger'>Access Denied</h3>
            <p class='text-muted'>You do not have permission to access this module.</p>
            <p class='small text-muted'>Module: " . htmlspecialchars($current_module) . "</p>
            <a href='" . BASE_URL . "/index.php' class='btn btn-primary mt-2'>Back to Dashboard</a>
        </div>
    </div>
</body>
</html>");
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

function getCurrentUserId() {
    return $_SESSION["user_id"] ?? null;
}

function getCurrentUserName() {
    return $_SESSION["user_name"] ?? "Guest";
}
