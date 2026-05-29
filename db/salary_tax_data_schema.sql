CREATE TABLE IF NOT EXISTS `import_salary_tax_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `import_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tin` varchar(50) DEFAULT NULL,
  `tax_year` int(4) DEFAULT NULL,
  `filing_type` varchar(50) DEFAULT NULL,
  `filing_period` varchar(50) DEFAULT NULL,
  `input_date` date DEFAULT NULL,
  `total_salaries_wages_cash` decimal(20,2) DEFAULT 0,
  `other_fringe_benefits` decimal(20,2) DEFAULT 0,
  `total_taxable_amount` decimal(20,2) DEFAULT 0,
  `tax_exempt_amount` decimal(20,2) DEFAULT 0,
  `tax_amount` decimal(20,2) DEFAULT 0,
  `adjustment_amount` decimal(20,2) DEFAULT 0,
  `carryforward_amount` decimal(20,2) DEFAULT 0,
  `total_amount_due` decimal(20,2) DEFAULT 0,
  
  -- Calculated Fields
  `benchmark_tax` decimal(20,2) DEFAULT 0,
  `te_amount` decimal(20,2) DEFAULT 0,
  `provision_number` varchar(50) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tin` (`tin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
