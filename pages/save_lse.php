<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    $tin = trim($_POST['tin']);
    $company_name = trim($_POST['company_name']);
    $listing_date = !empty($_POST['listing_date']) ? $_POST['listing_date'] : null;
    $remark = trim($_POST['remark']);

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE repo_lse SET company_name = ?, listing_date = ?, remark = ? WHERE id = ?");
        $stmt->execute([$company_name, $listing_date, $remark, $id]);
    } else {
        // Insert
        $check = $pdo->prepare("SELECT id FROM repo_lse WHERE tin = ?");
        $check->execute([$tin]);
        if ($check->fetch()) {
            header("Location: repo_lse.php?error=TIN already exists");
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO repo_lse (tin, company_name, listing_date, remark) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tin, $company_name, $listing_date, $remark]);
    }
}

header("Location: repo_lse.php");
exit;
