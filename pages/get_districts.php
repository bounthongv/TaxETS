<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

if (isset($_GET['province_id'])) {
    $stmt = $pdo->prepare("SELECT id, district_code, district_name FROM districts WHERE province_id = ? AND active = 1 ORDER BY district_name");
    $stmt->execute([$_GET['province_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
?>
