-- Domestic VAT Import Data Table (import_vat_data)
-- Expert-confirmed template v1.0: 17 columns A-Q
-- Old template columns (purchase_*, sales_standard, etc.) kept for backward compat
-- Renamed file: import_vat.php → import_domestic_vat.php (2026-07-02)
-- Generator: generate_domestic_vat_template.php

ALTER TABLE import_vat_data
  ADD COLUMN IF NOT EXISTS `description` varchar(255) DEFAULT NULL AFTER input_date,
  ADD COLUMN IF NOT EXISTS `vat_rate` decimal(5,2) DEFAULT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS `user_te` decimal(15,2) DEFAULT NULL AFTER sales_exempt,
  ADD COLUMN IF NOT EXISTS `user_benchmark_rate` decimal(5,2) DEFAULT NULL AFTER provision_number,
  ADD COLUMN IF NOT EXISTS `user_benchmark_vat` decimal(15,2) DEFAULT NULL AFTER user_benchmark_rate,
  ADD COLUMN IF NOT EXISTS `use_user_fallback` tinyint(1) DEFAULT 0 AFTER user_benchmark_vat,
  ADD COLUMN IF NOT EXISTS `system_benchmark_rate` decimal(5,2) DEFAULT NULL AFTER use_user_fallback,
  ADD COLUMN IF NOT EXISTS `user_fallback_reason` varchar(255) DEFAULT NULL AFTER system_benchmark_rate,
  ADD COLUMN IF NOT EXISTS `user_comment` text DEFAULT NULL AFTER user_fallback_reason;
