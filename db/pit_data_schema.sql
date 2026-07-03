-- ============================================================
-- PIT (Individual Tax) Import Data Schema — v2.0
-- Matches expert-confirmed PIT-template-apis.xlsx columns.
-- ============================================================
CREATE TABLE IF NOT EXISTS `import_pit_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `import_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tax_year` int(4) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,          -- Kept for backward compat
  `filing_date` date DEFAULT NULL,                     -- Col A
  `ptin` varchar(50) DEFAULT NULL,                     -- Col C
  `individual_name` varchar(255) DEFAULT NULL,         -- Col D

  -- Raw Amounts from template (#21-#30)
  `amount_21` decimal(15,2) DEFAULT '0.00',            -- Col E
  `amount_22` decimal(15,2) DEFAULT '0.00',            -- Col F
  `amount_23_1` decimal(15,2) DEFAULT '0.00',          -- Col G
  `amount_23_2` decimal(15,2) DEFAULT '0.00',          -- Col H
  `amount_24` decimal(15,2) DEFAULT '0.00',            -- Col I
  `amount_25` decimal(15,2) DEFAULT '0.00',            -- Col J
  `amount_26` decimal(15,2) DEFAULT '0.00',            -- Col K
  `amount_27` decimal(15,2) DEFAULT '0.00',            -- Col M
  `amount_28_1` decimal(15,2) DEFAULT '0.00',          -- Col N
  `amount_28_2` decimal(15,2) DEFAULT '0.00',          -- Col P
  `amount_29` decimal(15,2) DEFAULT '0.00',            -- Col Q

  -- Condition Flags (Yes/No)
  `is_stock_listed` tinyint(1) DEFAULT '0',             -- Col L
  `is_banking_system` tinyint(1) DEFAULT '0',           -- Col O
  `is_ss_member` tinyint(1) DEFAULT '0',                -- Col R (was old position AB)
  `ss_contribution` decimal(15,2) DEFAULT '0.00',       -- Col S (provision #30 amount)
  `use_fallback` tinyint(1) DEFAULT '0',                -- Col T

  -- User Fallback TE values (from template Col U-AF)
  `user_te_21` decimal(15,2) DEFAULT NULL,              -- Col U
  `user_te_22` decimal(15,2) DEFAULT NULL,              -- Col V
  `user_te_23_1` decimal(15,2) DEFAULT NULL,            -- Col W
  `user_te_23_2` decimal(15,2) DEFAULT NULL,            -- Col X
  `user_te_24` decimal(15,2) DEFAULT NULL,              -- Col Y
  `user_te_25` decimal(15,2) DEFAULT NULL,              -- Col Z
  `user_te_26` decimal(15,2) DEFAULT NULL,              -- Col AA
  `user_te_27` decimal(15,2) DEFAULT NULL,              -- Col AB
  `user_te_28_1` decimal(15,2) DEFAULT NULL,            -- Col AC
  `user_te_28_2` decimal(15,2) DEFAULT NULL,            -- Col AD
  `user_te_29` decimal(15,2) DEFAULT NULL,              -- Col AE
  `user_te_30` decimal(15,2) DEFAULT NULL,              -- Col AF
  `user_te_total` decimal(15,2) DEFAULT NULL,            -- Col AG

  -- User Meta
  `user_fallback_reason` varchar(255) DEFAULT NULL,     -- Col AH
  `user_comment` text DEFAULT NULL,                     -- Col AI

  -- Legacy Expert TE columns (kept for backward compat, no longer written)
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
  `expert_te_total` decimal(15,2) DEFAULT '0.00',

  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tax_year` (`tax_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
