<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    $tin = trim($_POST['tin']);
    $company_name = trim($_POST['company_name']);
    $staff_count = (int)$_POST['staff_count'];
    $remark = trim($_POST['remark']);

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE repo_molsw SET company_name = ?, staff_count = ?, remark = ? WHERE id = ?");
        $stmt->execute([$company_name, $staff_count, $remark, $id]);
    } else {
        // Insert
        // Check if TIN exists
        $check = $pdo->prepare("SELECT id FROM repo_molsw WHERE tin = ?");
        $check->execute([$tin]);
        if ($check->fetch()) {
            header("Location: repo_molsw.php?error=TIN already exists");
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO repo_molsw (tin, company_name, staff_count, remark) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tin, $company_name, $staff_count, $remark]);
    }
}

header("Location: repo_molsw.php");
exit;
