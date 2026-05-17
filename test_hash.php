<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/db.php";

$pdo = getDbConnection();
$hash = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@example.com'");
$stmt->execute([$hash]);

echo "Password updated! Hash: " . $hash;
?>