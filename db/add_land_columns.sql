-- Add land concession columns to companies table
-- Run this in phpMyAdmin SQL tab

ALTER TABLE companies ADD COLUMN land_area_sqm DECIMAL(15,2) AFTER reinvest_amount;
ALTER TABLE companies ADD COLUMN zone_type CHAR(1) DEFAULT 'A' COMMENT 'A=Urban, B=Suburban, C=Rural' AFTER land_area_sqm;