-- VAT Benchmark Rates
CREATE TABLE IF NOT EXISTS `bm_vat` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `rate_percentage` DECIMAL(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed VAT Rates
INSERT INTO `bm_vat` (`start_date`, `end_date`, `rate_percentage`) VALUES 
('2010-01-01', '2021-12-31', 10.00),
('2022-01-01', '2024-03-31', 7.00),
('2024-04-01', '2099-12-31', 10.00);

-- VAT Provisions
CREATE TABLE IF NOT EXISTS `vat_provisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provision_number` VARCHAR(20) NOT NULL,
    `start_year` INT(4) DEFAULT 2020,
    `end_year` INT(4) DEFAULT 2099,
    `legal_basis` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `purpose` VARCHAR(255),
    `type_of_te` ENUM('Exemption', 'Rate Relief') DEFAULT 'Exemption'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import VAT Data
CREATE TABLE IF NOT EXISTS `import_vat_data` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `batch_id` VARCHAR(50) NOT NULL,
    `import_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `province` VARCHAR(255),
    `tin` VARCHAR(50),
    `name` VARCHAR(255),
    `filing_type` VARCHAR(50),
    `filing_period` DATE,
    `input_date` DATE,
    `purchase_domestic_nonexempt` DECIMAL(15,2) DEFAULT 0,
    `purchase_domestic_exempt` DECIMAL(15,2) DEFAULT 0,
    `purchase_import_nonexempt` DECIMAL(15,2) DEFAULT 0,
    `purchase_import_exempt` DECIMAL(15,2) DEFAULT 0,
    `total_input_vat` DECIMAL(15,2) DEFAULT 0,
    `sales_standard` DECIMAL(15,2) DEFAULT 0,
    `sales_zero_rate` DECIMAL(15,2) DEFAULT 0,
    `sales_exempt` DECIMAL(15,2) DEFAULT 0,
    `total_output_vat` DECIMAL(15,2) DEFAULT 0,
    `vat_payable` DECIMAL(15,2) DEFAULT 0,
    `vat_credit` DECIMAL(15,2) DEFAULT 0,
    `expert_te` DECIMAL(15,2) DEFAULT 0,
    `system_te` DECIMAL(15,2) DEFAULT NULL,
    `benchmark_output_vat` DECIMAL(15,2) DEFAULT NULL,
    `calculated_vat_payable` DECIMAL(15,2) DEFAULT NULL,
    `provision_number` VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
