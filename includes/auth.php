<?php
// Auth check - include at top of pages that need login
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Update last activity
if (isset($_SESSION["session_token"])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
    $stmt->execute([$_SESSION["session_token"]]);
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