-- Migration: align companies table with expert CIT import template (profit-tax-template-apis (Toukta).xlsx)
-- Adds loss_carryforward; keeps re_invested_profit and reinvest metadata already used by the importer.

ALTER TABLE companies
  ADD COLUMN IF NOT EXISTS loss_carryforward DECIMAL(20, 2) DEFAULT 0 AFTER pt_paid,
  ADD COLUMN IF NOT EXISTS reinvest_date DATE DEFAULT NULL AFTER tax_holiday_years,
  ADD COLUMN IF NOT EXISTS reinvest_amount DECIMAL(20, 2) DEFAULT 0 AFTER reinvest_date;
