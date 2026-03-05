USE tax_ets;
-- Schema update v2: Add all flag columns to companies table to match Excel import
-- Run this after schema.sql if the companies table is already created

ALTER TABLE companies
  ADD COLUMN IF NOT EXISTS annual_turnover DECIMAL(20,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS tax_holiday_years INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS flag_hr_dev BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_eco_friendly BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_sez_developer BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_sez_investor BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_act_production_services BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_public_benefit BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_compliant_rental BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_real_estate_transfer BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS flag_act_1_4_7_8_9 BOOLEAN DEFAULT FALSE COMMENT 'IPL Art.9 activities 1,4,7,8,9',
  ADD COLUMN IF NOT EXISTS flag_act_2_3_5_6 BOOLEAN DEFAULT FALSE COMMENT 'IPL Art.9 activities 2,3,5,6',
  ADD COLUMN IF NOT EXISTS stock_exchange_listing_date DATE DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS reinvest_date DATE DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS reinvest_amount DECIMAL(20,2) DEFAULT 0;

SELECT 'Schema updated successfully' AS result;
