USE tax_ets;

-- Clear existing benchmark data to ensure a clean state
DELETE FROM bm_profit_standard;
DELETE FROM bm_profit_mandatory;
DELETE FROM bm_profit_sme;

-----------------------------------------------------------
-- 1. PERIOD: 2018 - 2019
-----------------------------------------------------------

-- Standard & Tobacco Rates
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES 
(2018, 2019, 'Standard', 24.00),
(2018, 2019, 'Tobacco/Mining', 26.00);

-- Mandatory Base Rates (Estimated Profit)
INSERT INTO bm_profit_mandatory (start_year, end_year, sector, sub_sector, profit_base_rate) VALUES
(2018, 2019, 'Production', 'Agricultural & Industrial', 3.00),
(2018, 2019, 'Commerce', 'General', 5.00),
(2018, 2019, 'Service', 'Transport (Goods/Pax)', 5.00),
(2018, 2019, 'Service', 'Construction & Repairs', 10.00),
(2018, 2019, 'Service', 'Wood/Minerals (Trading)', 20.00),
(2018, 2019, 'Service', 'Planted Trees (Trading)', 5.00),
(2018, 2019, 'Service', 'Extraction (Soil/Sand/Rock)', 15.00),
(2018, 2019, 'Service', 'Entertainment', 25.00);

-- SME/Micro Rates (Lump sum or Revenue based)
INSERT INTO bm_profit_sme (start_year, end_year, sector, turnover_min, turnover_max, rate_percentage) VALUES
('2018', '2019', 'Production', 50000001, 120000000, 3.00),
('2018', '2019', 'Commerce', 50000001, 120000000, 4.00),
('2018', '2019', 'Service', 50000001, 120000000, 5.00),
('2018', '2019', 'Production', 120000001, 240000000, 4.00),
('2018', '2019', 'Commerce', 120000001, 240000000, 5.00),
('2018', '2019', 'Service', 120000001, 240000000, 6.00);


-----------------------------------------------------------
-- 2. PERIOD: 2020 - 2021
-----------------------------------------------------------

-- Standard & Tobacco Rates (Lowered)
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES 
(2020, 2021, 'Standard', 20.00),
(2020, 2021, 'Tobacco', 22.00);

-- Mandatory Base Rates (Note: Prod/Comm swapped from 2018)
INSERT INTO bm_profit_mandatory (start_year, end_year, sector, sub_sector, profit_base_rate) VALUES
(2020, 2021, 'Production', 'Agricultural & Industrial', 5.00),
(2020, 2021, 'Commerce', 'General', 3.00),
(2020, 2021, 'Service', 'Transport (Goods/Pax)', 5.00),
(2020, 2021, 'Service', 'Construction & Repairs', 10.00),
(2020, 2021, 'Service', 'Wood/Minerals (Trading)', 20.00),
(2020, 2021, 'Service', 'Planted Trees (Trading)', 5.00),
(2020, 2021, 'Service', 'Extraction (Soil/Sand/Rock)', 15.00),
(2020, 2021, 'Service', 'Entertainment', 25.00);

-- SME/Micro Rates (Broadened Brackets)
INSERT INTO bm_profit_sme (start_year, end_year, sector, turnover_min, turnover_max, rate_percentage) VALUES
('2020', '2021', 'All', 50000001, 400000000, 1.00),
('2020', '2021', 'All', 400000001, 1200000000, 2.00),
('2020', '2021', 'All', 1200000001, 4000000000, 3.00);


-----------------------------------------------------------
-- 3. PERIOD: 2022 - 2099 (Latest)
-----------------------------------------------------------

-- Standard & Tobacco Rates
INSERT INTO bm_profit_standard (start_year, end_year, category, rate_percentage) VALUES 
(2022, 2099, 'Standard', 20.00),
(2022, 2099, 'Tobacco', 22.00);

-- Mandatory Base Rates
INSERT INTO bm_profit_mandatory (start_year, end_year, sector, sub_sector, profit_base_rate) VALUES
(2022, 2099, 'Production', 'Agricultural & Industrial', 5.00),
(2022, 2099, 'Commerce', 'General', 3.00),
(2022, 2099, 'Service', 'Transport (Goods/Pax)', 5.00),
(2022, 2099, 'Service', 'Construction & Repairs', 10.00),
(2022, 2099, 'Service', 'Wood/Minerals (Trading)', 20.00),
(2022, 2099, 'Service', 'Planted Trees (Trading)', 5.00),
(2022, 2099, 'Service', 'Extraction (Soil/Sand/Rock)', 15.00),
(2022, 2099, 'Service', 'Real Estate (Buying/Selling/Rent)', 10.00),
(2022, 2099, 'Service', 'Entertainment', 25.00);

-- SME/Micro Rates
INSERT INTO bm_profit_sme (start_year, end_year, sector, turnover_min, turnover_max, rate_percentage) VALUES
('2022', '2099', 'All', 50000001, 400000000, 1.00),
('2022', '2099', 'All', 400000001, 1200000000, 2.00),
('2022', '2099', 'All', 1200000001, 4000000000, 3.00);
