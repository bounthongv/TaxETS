CREATE TABLE IF NOT EXISTS `import_pit_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `import_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tax_year` int(4) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `ptin` varchar(50) DEFAULT NULL,

  -- Raw Amounts (from Excel, e.g. amount to be exempted/calculated)
  `amount_21` decimal(15,2) DEFAULT '0.00',
  `amount_22` decimal(15,2) DEFAULT '0.00',
  `amount_23_1` decimal(15,2) DEFAULT '0.00',
  `amount_23_2` decimal(15,2) DEFAULT '0.00',
  `amount_24` decimal(15,2) DEFAULT '0.00',
  `amount_25` decimal(15,2) DEFAULT '0.00',
  `amount_26` decimal(15,2) DEFAULT '0.00',
  `amount_27` decimal(15,2) DEFAULT '0.00',
  `amount_28_1` decimal(15,2) DEFAULT '0.00',
  `amount_28_2` decimal(15,2) DEFAULT '0.00',
  `amount_29` decimal(15,2) DEFAULT '0.00',
  `is_ss_member` tinyint(1) DEFAULT '0', -- Provision 30

  -- Expert calculated TE (from Excel)
  `expert_te_21` decimal(15,2) DEFAULT '0.00',
  `expert_te_22` decimal(15,2) DEFAULT '0.00',
  `expert_te_23_1` decimal(15,2) DEFAULT '0.00',
  `expert_te_23_2` decimal(15,2) DEFAULT '0.00',
  `expert_te_24` decimal(15,2) DEFAULT '0.00',
  `expert_te_25` decimal(15,2) DEFAULT '0.00',
  `expert_te_26` decimal(15,2) DEFAULT '0.00',
  `expert_te_27` decimal(15,2) DEFAULT '0.00',
  `expert_te_28_1` decimal(15,2) DEFAULT '0.00',
  `expert_te_28_2` decimal(15,2) DEFAULT '0.00',
  `expert_te_29` decimal(15,2) DEFAULT '0.00',
  
  -- Totals
  `expert_te_total` decimal(15,2) DEFAULT '0.00',

  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tax_year` (`tax_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
