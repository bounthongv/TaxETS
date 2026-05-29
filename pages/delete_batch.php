<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection();
$type = $_POST["type"] ?? "cit";
$batch_id = $_POST["batch_id"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($batch_id)) {
    // Persistent Log Deletion
    $log_path = __DIR__ . "/../data/logs/" . $batch_id . ".log";
    if (file_exists($log_path)) {
        unlink($log_path);
    }

    switch ($type) {
        case "vat":
            $stmt = $pdo->prepare("DELETE FROM import_vat_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_vat.php";
            break;
        case "salary":
            $stmt = $pdo->prepare("DELETE FROM import_salary_tax_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_salary.php";
            break;
        case "pit":
            $stmt = $pdo->prepare("DELETE FROM import_pit_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_individual.php";
            break;
        case "sez_dev":
            $stmt = $pdo->prepare("DELETE FROM import_sez_data WHERE batch_id = ? AND type = 'Developer'");
            $stmt->execute([$batch_id]);
            $redirect = "import_sez_dev.php";
            break;
        case "sez_inv":
            $stmt = $pdo->prepare("DELETE FROM import_sez_data WHERE batch_id = ? AND type = 'Investor'");
            $stmt->execute([$batch_id]);
            $redirect = "import_sez_inv.php";
            break;
        case "resource":
            $stmt = $pdo->prepare("DELETE FROM import_resource_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_resource.php";
            break;
        case "royalty":
            $stmt = $pdo->prepare("DELETE FROM import_royalty_data WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_royalty.php";
            break;
        case "land":
            $stmt = $pdo->prepare("DELETE FROM repo_land_concession_data WHERE import_batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_land_concession.php";
            break;
        case "asy":
            $stmt = $pdo->prepare("DELETE tar FROM te_asycuda_result tar 
                                 JOIN asycuda_imports ai ON tar.asycuda_id = ai.id 
                                 WHERE ai.import_batch_id = ?");
            $stmt->execute([$batch_id]);
            $stmt = $pdo->prepare("DELETE FROM asycuda_imports WHERE import_batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_asycuda.php";
            break;
        case "cit":
        default:
            $stmt = $pdo->prepare("DELETE FROM companies WHERE import_batch_id = ?");
            $stmt->execute([$batch_id]);
            $redirect = "import_cit.php";
            break;
    }
} else {
    $redirect = "index.php";
}

header("Location: $redirect");
exit;
