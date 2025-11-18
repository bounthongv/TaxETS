<?php

require_once __DIR__ . '/../src/Services/Database.php';

use TaxETS\Services\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    $sql = "
    -- Ensure you are using the correct database
    USE `taxets`;

    -- ===================================================================================
    -- SCRIPT TO CREATE TABLES FOR DATA IMPORT AND CALCULATION RESULTS
    -- ===================================================================================

    -- Step 1: Create the table to hold the imported data for calculation.
    -- This table can be cleared and re-populated each time a new file is imported.
    DROP TABLE IF EXISTS `calculation_data_profit_tax`;
    CREATE TABLE `calculation_data_profit_tax` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `tin` VARCHAR(20) NOT NULL,
      `calculation_year` YEAR NOT NULL,
      `company_name` VARCHAR(255) NULL,
      `revenue` DECIMAL(20, 2) NULL,
      `expense` DECIMAL(20, 2) NULL,
      `net_profit` DECIMAL(20, 2) NULL,
      `pt_paid` DECIMAL(20, 2) NULL,
      `sector` VARCHAR(100) NULL,
      `is_vat_holder` BOOLEAN DEFAULT TRUE, -- Corresponds to 'List of enterprises holding VAT system'
      `zone` INT NULL, -- We can combine Zone 1, 2, 3 into a single field
      `staff_count` INT NULL,
      `total_assets_billion` DECIMAL(10, 4) NULL,
      `annual_turnover_billion` DECIMAL(10, 4) NULL,
      `date_first_revenue` DATE NULL, -- The field we decided to add
      `investment_license_date` DATE NULL,
      `registration_date` DATE NULL,
      `stock_listing_date` DATE NULL,
      `reinvested_profit_amount` DECIMAL(20, 2) NULL,
      `is_human_resource_dev` BOOLEAN DEFAULT FALSE,
      `is_innovative_green_tech` BOOLEAN DEFAULT FALSE,
      `is_sez_developer` BOOLEAN DEFAULT FALSE,
      `is_sez_investor` BOOLEAN DEFAULT FALSE,
      `is_in_sez_specified_activity` BOOLEAN DEFAULT FALSE,
      `is_public_benefit_income` BOOLEAN DEFAULT FALSE,
      `is_asset_rent_compliant` BOOLEAN DEFAULT FALSE,
      `is_real_estate_transfer` BOOLEAN DEFAULT FALSE,
      `invested_in_ipl_art9_activity_2` BOOLEAN DEFAULT FALSE, -- Using specific names for each activity
      `invested_in_ipl_art9_activity_3` BOOLEAN DEFAULT FALSE,
      `invested_in_ipl_art9_activity_4` BOOLEAN DEFAULT FALSE,
      `invested_in_ipl_art9_activity_5` BOOLEAN DEFAULT FALSE,
      `invested_in_ipl_art9_activity_6` BOOLEAN DEFAULT FALSE,
      PRIMARY KEY (`id`),
      INDEX `idx_tin_year` (`tin`, `calculation_year`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Step 2: Create the table to store the final calculation results.
    DROP TABLE IF EXISTS `calculation_results_profit_tax`;
    CREATE TABLE `calculation_results_profit_tax` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `calculation_data_id` INT NOT NULL, -- Link back to the source data
      `tin` VARCHAR(20) NOT NULL,
      `calculation_year` YEAR NOT NULL,
      `benchmark_tax` DECIMAL(20, 2) NOT NULL,
      `applied_te_provision_id` INT NULL, -- The ID of the winning TE rule
      `actual_tax_payable` DECIMAL(20, 2) NOT NULL,
      `final_te_result` DECIMAL(20, 2) NOT NULL,
      `calculation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`calculation_data_id`) REFERENCES `calculation_data_profit_tax`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);

    echo "Tables created successfully." . PHP_EOL;

} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage() . PHP_EOL;
}
