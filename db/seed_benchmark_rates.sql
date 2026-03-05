USE tax_ets;

-- =============================================================
-- SEED BENCHMARK RATES for Lao PDR Profit Tax
-- Based on: Income Tax Law (ITL) and Investment Promotion Law (IPL)
-- Reference: 1a-Benchmark by year_Profit Tax.pdf
-- =============================================================

-- Clear existing benchmark data (safe to re-run)
DELETE FROM bm_profit_standard;
DELETE FROM bm_profit_mandatory;
DELETE FROM bm_profit_sme;

-- =============================================================
-- 1. STANDARD PROFIT TAX RATES (bm_profit_standard)
-- ITL Art.16 - Applied to VAT-holder large enterprises
-- =============================================================

-- Standard rate: 20% (applicable from 2020 onwards per ITL)
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES
(2020, 2099, 'Standard', 20.00);

-- Tobacco sector: 24% (higher rate per ITL)
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES
(2020, 2099, 'Tobacco', 24.00);

-- Mining and Electricity: 24% (per ITL)
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES
(2020, 2099, 'Mining/Electricity', 24.00);

-- Historical rates (pre-2020, if needed for older data)
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES
(2015, 2019, 'Standard', 24.00),
(2015, 2019, 'Tobacco', 26.00),
(2015, 2019, 'Mining/Electricity', 26.00);

-- =============================================================
-- 2. MANDATORY PROFIT BASE RATES (bm_profit_mandatory)
-- For Non-VAT Holders (mandatory system / lump-sum)
-- ITL Art.35 - Estimated profit as % of revenue by sector
-- =============================================================

INSERT INTO bm_profit_mandatory (start_year, end_year, sector, sub_sector, profit_base_rate) VALUES
-- Production sector
(2020, 2099, 'Production', 'General Production', 5.00),
(2020, 2099, 'Production', 'Handicraft', 5.00),
(2020, 2099, 'Production', 'Processing', 7.00),

-- Commerce sector (buy/sell)
(2020, 2099, 'Commerce', 'General Commerce', 3.00),
(2020, 2099, 'Commerce', 'Retail', 3.00),
(2020, 2099, 'Commerce', 'Wholesale', 3.00),

-- Services sector
(2020, 2099, 'Services', 'General Services', 15.00),
(2020, 2099, 'Services', 'Restaurant/Hotel', 10.00),
(2020, 2099, 'Services', 'Transport', 10.00),
(2020, 2099, 'Services', 'Professional Services', 20.00),
(2020, 2099, 'Services', 'Construction', 10.00),

-- Mixed / Other
(2020, 2099, 'Mixed', 'General Mixed', 7.00);

-- =============================================================
-- 3. SME / MICRO ENTERPRISE RATES (bm_profit_sme)
-- Per ITL Art.16(2) - Simplified tax for small businesses
-- Turnover thresholds in LAK
-- =============================================================

INSERT INTO bm_profit_sme (start_year, end_year, sector, turnover_min, turnover_max, rate_percentage) VALUES
-- Micro enterprises (annual turnover up to 400M LAK)
(2020, 2099, 'All', 0, 50000000, 0.10),            -- Under 50M: 0.1%
(2020, 2099, 'All', 50000001, 100000000, 0.50),     -- 50M - 100M: 0.5%
(2020, 2099, 'All', 100000001, 200000000, 1.00),    -- 100M - 200M: 1%
(2020, 2099, 'All', 200000001, 400000000, 1.50),    -- 200M - 400M: 1.5%
(2020, 2099, 'All', 400000001, NULL, 2.00);          -- Over 400M: 2% (still SME threshold)

SELECT CONCAT('Seeded ', COUNT(*), ' standard rates') AS result FROM bm_profit_standard;
SELECT CONCAT('Seeded ', COUNT(*), ' mandatory rates') AS result FROM bm_profit_mandatory;
SELECT CONCAT('Seeded ', COUNT(*), ' SME rates') AS result FROM bm_profit_sme;
