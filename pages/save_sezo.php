<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    $tin = trim($_POST['tin']);
    $company_name = trim($_POST['company_name']);
    $province_id = !empty($_POST['province_id']) ? $_POST['province_id'] : null;
    $district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $sector_id = !empty($_POST['sector_id']) ? $_POST['sector_id'] : null;
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $type = $_POST['type']; // Investor or Developer
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $remark = trim($_POST['remark']);

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE repo_sezo SET company_name = ?, province_id = ?, district_id = ?, sector_id = ?, category_id = ?, type = ?, is_active = ?, remark = ? WHERE id = ?");
        $stmt->execute([$company_name, $province_id, $district_id, $sector_id, $category_id, $type, $is_active, $remark, $id]);
    } else {
        // Insert
        $check = $pdo->prepare("SELECT id FROM repo_sezo WHERE tin = ?");
        $check->execute([$tin]);
        if ($check->fetch()) {
            header("Location: repo_sezo.php?error=TIN already exists");
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO repo_sezo (tin, company_name, province_id, district_id, sector_id, category_id, type, is_active, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tin, $company_name, $province_id, $district_id, $sector_id, $category_id, $type, $is_active, $remark]);
    }
}

header("Location: repo_sezo.php");
exit;
