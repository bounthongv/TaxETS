-- ASYCUDA Data Tables
USE tax_ets;

CREATE TABLE IF NOT EXISTS `asycuda_imports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `import_batch_id` VARCHAR(50),
    `import_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `province` VARCHAR(100),
    `pro_id` VARCHAR(10) DEFAULT NULL,
    `tin` VARCHAR(50),
    `border_code` VARCHAR(20),
    `border_name` VARCHAR(100),
    `regime_code` VARCHAR(20), -- ProcessType (G) + F (H)
    `doc_number` VARCHAR(50),
    `doc_date` DATE,
    `importer_name` TEXT,
    `hs_code` VARCHAR(20),
    `goods_description` TEXT,
    `invoice_amount_lak` DECIMAL(20, 2) DEFAULT 0,
    
    -- Paid Amounts
    `paid_customs` DECIMAL(20, 2) DEFAULT 0,
    `paid_excise` DECIMAL(20, 2) DEFAULT 0,
    `paid_vat` DECIMAL(20, 2) DEFAULT 0,
    
    -- Benchmarks (Original names from Excel)
    `exemp_customs` DECIMAL(20, 2) DEFAULT 0, -- Benchmark Customs
    `exempt_excise` DECIMAL(20, 2) DEFAULT 0, -- Benchmark Excise
    `exempt_vat` DECIMAL(20, 2) DEFAULT 0,    -- Benchmark VAT
    
    INDEX (`import_batch_id`),
    INDEX (`regime_code`),
    INDEX (`tin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `te_asycuda_result` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `asycuda_id` INT NOT NULL,
    `customs_te` DECIMAL(20, 2) DEFAULT 0,
    `excise_te` DECIMAL(20, 2) DEFAULT 0,
    `vat_te` DECIMAL(20, 2) DEFAULT 0,
    `total_te` DECIMAL(20, 2) DEFAULT 0,
    FOREIGN KEY (`asycuda_id`) REFERENCES `asycuda_imports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
