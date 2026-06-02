-- Royalty Benchmark Table
USE tax_ets;

CREATE TABLE IF NOT EXISTS `bm_royalty_fees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provision_code` VARCHAR(50) NOT NULL,
    `provision_name` VARCHAR(255) NOT NULL,
    `rate_percentage` DECIMAL(5, 2) NOT NULL,
    `start_year` SMALLINT(4) DEFAULT 2026,
    `end_year` SMALLINT(4) DEFAULT 3000,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
