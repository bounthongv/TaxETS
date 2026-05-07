<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/db.php";

session_start();

if (isset($_SESSION["user_id"])) {
    $pdo = getDbConnection();
    
    if (isset($_SESSION["session_token"])) {
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_online = 0 WHERE session_token = ?");
        $stmt->execute([$_SESSION["session_token"]]);
    }
    
    require_once __DIR__ . "/includes/user_history.php";
    logUserAction($pdo, $_SESSION["user_id"], $_SESSION["user_name"], "LOGOUT", "User logged out", $_SERVER["REMOTE_ADDR"] ?? "");
}

session_destroy();
header("Location: " . BASE_URL . "/login.php");
exit;