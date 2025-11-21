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
    -- Table to store clean, RAW input data from Excel or manual entry
    DROP TABLE IF EXISTS `calculation_data_profit_tax`;
    CREATE TABLE `calculation_data_profit_tax` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `tin` VARCHAR(20) NOT NULL,
      `company_name` VARCHAR(255) NULL,
      `calculation_year` YEAR NOT NULL,
      `revenue` DECIMAL(20, 2) NULL,
      `expense` DECIMAL(20, 2) NULL,
      `pt_paid` DECIMAL(20, 2) NULL,
      `reinvested_profit_amount` DECIMAL(20, 2) NULL,
      `reinvest_date` DATE NULL,
      `province` VARCHAR(100) NULL,
      `district` VARCHAR(100) NULL,
      `sector` VARCHAR(100) NULL,
      `zone` INT NULL,
      `is_vat_holder` BOOLEAN NULL,
      `staff_count` INT NULL,
      `total_assets_billion` DECIMAL(10, 4) NULL,
      `annual_turnover_billion` DECIMAL(10, 4) NULL,
      `investment_license_date` DATE NULL,
      `date_first_revenue` DATE NULL,
      `registration_date` DATE NULL,
      `stock_listing_date` DATE NULL,
      `tax_holiday_period_years` INT NULL,
      `is_human_resource_dev` BOOLEAN DEFAULT FALSE,
      `is_innovative_green_tech` BOOLEAN DEFAULT FALSE,
      `is_sez_developer` BOOLEAN DEFAULT FALSE,
      `is_sez_investor` BOOLEAN DEFAULT FALSE,
      `is_in_sez_specified_activity` BOOLEAN DEFAULT FALSE,
      `is_public_benefit_income` BOOLEAN DEFAULT FALSE,
      `is_asset_rent_compliant` BOOLEAN DEFAULT FALSE,
      `is_real_estate_transfer` BOOLEAN DEFAULT FALSE,
      `ipl_activity_flags` JSON NULL,
      `applied_te_ids_from_import` VARCHAR(255) NULL,
      -- New columns for TE#1 to TE#20 and TE#Other
      `te_1` VARCHAR(50) NULL,
      `te_2` VARCHAR(50) NULL,
      `te_3` VARCHAR(50) NULL,
      `te_4` VARCHAR(50) NULL,
      `te_5` VARCHAR(50) NULL,
      `te_6` VARCHAR(50) NULL,
      `te_7` VARCHAR(50) NULL,
      `te_8` VARCHAR(50) NULL,
      `te_9` VARCHAR(50) NULL,
      `te_10` VARCHAR(50) NULL,
      `te_11` VARCHAR(50) NULL,
      `te_12` VARCHAR(50) NULL,
      `te_13` VARCHAR(50) NULL,
      `te_14` VARCHAR(50) NULL,
      `te_15` VARCHAR(50) NULL,
      `te_16` VARCHAR(50) NULL,
      `te_17` VARCHAR(50) NULL,
      `te_18` VARCHAR(50) NULL,
      `te_19` VARCHAR(50) NULL,
      `te_20` VARCHAR(50) NULL,
      `te_other` VARCHAR(50) NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_tin_year` (`tin`, `calculation_year`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Table to store the output of a calculation run
    DROP TABLE IF EXISTS `calculation_results_profit_tax`;
    CREATE TABLE `calculation_results_profit_tax` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `source_data_id` INT NOT NULL,
      `tin` VARCHAR(20) NOT NULL,
      `calculation_year` YEAR NOT NULL,
      `system_net_profit` DECIMAL(20, 2) NOT NULL,
      `system_benchmark_tax` DECIMAL(20, 2) NOT NULL,
      `applied_te_provision_id` INT NULL,
      `system_actual_tax_payable` DECIMAL(20, 2) NOT NULL,
      `system_pt_te` DECIMAL(20, 2) NOT NULL,
      `cross_check_difference` DECIMAL(20, 0) NOT NULL,
      `calculation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`source_data_id`) REFERENCES `calculation_data_profit_tax`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);

    echo "Tables created successfully." . PHP_EOL;

} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage() . PHP_EOL;
}
