<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection();
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["batch_id"])) {
    $stmt = $pdo->prepare("DELETE FROM companies WHERE import_batch_id = ?");
    $stmt->execute([$_POST["batch_id"]]);
}
header("Location: import_cit.php");
exit;
?>
