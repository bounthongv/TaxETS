<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

if (isset($_GET['district_code'])) {
    $stmt = $pdo->prepare("SELECT id, village_name FROM villages WHERE district_code = ? AND active = 1 ORDER BY village_name");
    $stmt->execute([$_GET['district_code']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
?>
