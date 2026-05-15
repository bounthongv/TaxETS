<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    $id = $_POST['id'] ?? null;
    
    // Core fields
    $fields = [
        'tin' => $_POST['tin'],
        'company_name' => $_POST['company_name'],
        'province_id' => $_POST['province_id'] ?: null,
        'district_id' => $_POST['district_id'] ?: null,
        'village_id' => $_POST['village_id'] ?: null,
        'address' => $_POST['address'],
        'enterprise_type_id' => $_POST['enterprise_type_id'] ?: null,
        'license_date' => $_POST['license_date'] ?: null,
        'first_revenue_date' => $_POST['first_revenue_date'] ?: null,
        'incentive_grant_date' => $_POST['incentive_grant_date'] ?: null,
        'incentive_tax_policy' => $_POST['incentive_tax_policy'],
        'investor_fund_rate' => $_POST['investor_fund_rate'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'business_size_staff' => (int)$_POST['business_size_staff'],
        'registered_capital' => (float)$_POST['registered_capital'],
        'vat_system_status' => isset($_POST['vat_system_status']) ? 1 : 0,
        // Existing scopes
        'hr_dev_scope' => (int)$_POST['hr_dev_scope'],
        'innovative_tech_scope' => (int)$_POST['innovative_tech_scope'],
        'art9_p2_scope' => (int)$_POST['art9_p2_scope'],
        'art9_p3_scope' => (int)$_POST['art9_p3_scope'],
        'art9_p4_scope' => (int)$_POST['art9_p4_scope'],
        'art9_p5_scope' => (int)$_POST['art9_p5_scope'],
        'art9_p6_scope' => (int)$_POST['art9_p6_scope'],
        'prod_industry_scope' => (int)$_POST['prod_industry_scope'],
        'tourism_scope' => (int)$_POST['tourism_scope'],
        'public_health_scope' => (int)$_POST['public_health_scope'],
        'edu_scope' => (int)$_POST['edu_scope'],
        'sport_scope' => (int)$_POST['sport_scope'],
        'real_estate_scope' => (int)$_POST['real_estate_scope'],
        'micro_ent_scope' => (int)$_POST['micro_ent_scope'],
        'agri_handicraft_scope' => (int)$_POST['agri_handicraft_scope'],
        // NEW scopes from user request
        'industry_manuf_scope' => (int)$_POST['industry_manuf_scope'],
        'commerce_service_scope' => (int)$_POST['commerce_service_scope'],
        'electric_mining_scope' => (int)$_POST['electric_mining_scope'],
        'agri_industrial_scope' => (int)$_POST['agri_industrial_scope'],
        'commerce_scope' => (int)$_POST['commerce_scope'],
        'transport_scope' => (int)$_POST['transport_scope'],
        'construction_scope' => (int)$_POST['construction_scope'],
        'wood_exploitation_scope' => (int)$_POST['wood_exploitation_scope'],
        'extraction_filling_scope' => (int)$_POST['extraction_filling_scope'],
        'entertainment_scope' => (int)$_POST['entertainment_scope'],
        'consultancy_scope' => (int)$_POST['consultancy_scope'],
        'brokers_agents_scope' => (int)$_POST['brokers_agents_scope'],
        'real_estate_dev_sale_scope' => (int)$_POST['real_estate_dev_sale_scope'],
        'other_service_scope' => (int)$_POST['other_service_scope'],
        'tobacco_scope' => (int)$_POST['tobacco_scope'],
        'mining_activity_scope' => (int)$_POST['mining_activity_scope'],
        'sez_developer_scope' => (int)$_POST['sez_developer_scope'],
        'sez_investor_scope' => (int)$_POST['sez_investor_scope'],
    ];

    $cols = array_keys($fields);
    $vals = array_values($fields);

    if ($id) {
        $set = implode(" = ?, ", $cols) . " = ?";
        $stmt = $pdo->prepare("UPDATE repo_moic SET $set WHERE id = ?");
        $vals[] = $id;
        $stmt->execute($vals);
        $enterprise_id = $id;
        
        // Clear existing categories for update
        $pdo->prepare("DELETE FROM moic_enterprise_category_map WHERE enterprise_id = ?")->execute([$enterprise_id]);
    } else {
        $placeholders = implode(", ", array_fill(0, count($cols), "?"));
        $stmt = $pdo->prepare("INSERT INTO repo_moic (" . implode(", ", $cols) . ") VALUES ($placeholders)");
        $stmt->execute($vals);
        $enterprise_id = $pdo->lastInsertId();
    }

    // Save Categories
    if (!empty($_POST['categories_data'])) {
        $cats = json_decode($_POST['categories_data'], true);
        if (is_array($cats)) {
            $cat_stmt = $pdo->prepare("INSERT INTO moic_enterprise_category_map (enterprise_id, main_category_id, sub_category_id) VALUES (?, ?, ?)");
            foreach ($cats as $c) {
                $cat_stmt->execute([$enterprise_id, $c['main_id'], $c['sub_id'] ?: null]);
            }
        }
    }
}

header("Location: repo_moic.php");
exit;
