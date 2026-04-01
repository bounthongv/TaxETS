<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection();
$type = $_POST["type"] ?? "cit";
$batch_id = $_POST["batch_id"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($batch_id)) {
    switch ($type) {
        case "pit":
            $stmt = $pdo->prepare("DELETE FROM import_pit_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_individual.php";
            break;
        case "vat":
            $stmt = $pdo->prepare("DELETE FROM import_vat_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_vat.php";
            break;
        case "cit":
        default:
            $stmt = $pdo->prepare("DELETE FROM companies WHERE import_batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_cit.php";
            break;
    }
}
else {
    $redirect = "index.php";
}

header("Location: $redirect");
exit;
?>
