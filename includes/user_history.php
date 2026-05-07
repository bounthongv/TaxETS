<?php
// Helper function to log user actions

function logUserAction(PDO $pdo, ?int $user_id, string $user_name, string $action, string $details = "", string $ip_address = "") {
    if (empty($ip_address)) {
        $ip_address = $_SERVER["REMOTE_ADDR"] ?? "unknown";
    }
    
    $stmt = $pdo->prepare("INSERT INTO user_history (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_name, $action, $details, $ip_address]);
}