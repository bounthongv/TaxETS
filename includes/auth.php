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

function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

function getCurrentUserId() {
    return $_SESSION["user_id"] ?? null;
}

function getCurrentUserName() {
    return $_SESSION["user_name"] ?? "Guest";
}
