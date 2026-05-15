<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

$pdo = getDbConnection();

if (isset($_GET['main_category_id'])) {
    $stmt = $pdo->prepare("SELECT id, sub_category_name FROM moic_sub_categories WHERE main_category_id = ? AND active = 1 ORDER BY sub_category_name");
    $stmt->execute([$_GET['main_category_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
?>
