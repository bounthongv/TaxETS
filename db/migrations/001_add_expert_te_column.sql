-- Migration 001: Add expert_te column for hybrid testing
-- Adds Expert TE column to both companies (for import storage)
-- and te_profit_result (for display in calculation results).
-- Part of the hybrid implementation plan (docs/11-hybrid-testing.md)

ALTER TABLE companies
  ADD COLUMN expert_te DECIMAL(20,2) DEFAULT NULL AFTER reinvest_amount;

ALTER TABLE te_profit_result
  ADD COLUMN expert_te DECIMAL(20,2) DEFAULT NULL AFTER profit_tax_te;
