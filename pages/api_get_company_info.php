<?php
/**
 * API to fetch Company and Project info
 * Prioritizes MOIC for Company data, MPI for Project data
 */
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

header('Content-Type: application/json');

if (!isset($_GET['tin']) || empty($_GET['tin'])) {
    echo json_encode(['success' => false, 'message' => 'TIN is required']);
    exit;
}

$tin = trim($_GET['tin']);
$pdo = getDbConnection();

try {
    $result = [
        'success' => false,
        'company_name' => '',
        'project_name' => '',
        'source' => ''
    ];

    // 1. Prioritize MOIC for Company Name
    $stmt = $pdo->prepare("SELECT company_name FROM repo_moic WHERE tin = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$tin]);
    $moic = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($moic) {
        $result['company_name'] = $moic['company_name'];
        $result['source'] = 'MOIC';
        $result['success'] = true;
    }

    // 2. Fetch Project Name from MPI
    $stmt = $pdo->prepare("SELECT project_name, tin FROM repo_mpi WHERE tin = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$tin]);
    $mpi = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($mpi) {
        $result['project_name'] = $mpi['project_name'];
        if (!$result['company_name']) {
            // Fallback for company name if MOIC didn't have it
            // Assuming repo_mpi might have company info too if needed, but repo_mpi schema doesn't have it explicitly shown in DESCRIBE
            // Let's stick to what we found.
        }
        $result['source'] = $result['source'] ? $result['source'] . " & MPI" : "MPI";
        $result['success'] = true;
    }

    // 3. Fallback to main companies table
    if (!$result['company_name']) {
        $stmt = $pdo->prepare("SELECT company_name_en FROM companies WHERE tin = ? LIMIT 1");
        $stmt->execute([$tin]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($company) {
            $result['company_name'] = $company['company_name_en'];
            $result['source'] = "Main Directory";
            $result['success'] = true;
        }
    }

    if ($result['success']) {
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found for this TIN in MOIC, MPI, or Main Directory.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
