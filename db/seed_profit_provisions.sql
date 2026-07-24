USE tax_ets;

-- Seed all 20 Profit Tax Provisions and their rule conditions
-- Based on: 1b-provision-profit-tax.pdf and CIT Test_Toukta.xlsx formulas
-- Column references mapped to companies table fields

-- Clear existing data (safe to re-run)
DELETE FROM profit_provision_conditions;
DELETE FROM profit_provisions;
ALTER TABLE profit_provisions AUTO_INCREMENT = 1;

-- =============================================================
-- Insert Provisions
-- =============================================================

INSERT INTO profit_provisions (provision_number, legal_reference, description, target_rate, is_exemption, start_year, end_year) VALUES
('1',  'ITL Art.16(2)',      'Small entity: 6-50 staff, assets <=1.5B, turnover <=3B, registered <=3 years', 3.00, 0, 2018, 2099),
('2',  'ITL Art.16(2)',      'Medium entity: 51-99 staff, assets <=6B, turnover <=6B, registered <=3 years', 5.00, 0, 2018, 2099),
('3',  'ITL Art.16(2)',      'HR development businesses (schools, hospitals, etc.) after tax holiday', 5.00, 0, 2018, 2099),
('4',  'ITL Art.16(2)',      'Eco-friendly/clean energy tech businesses after tax holiday', 7.00, 0, 2018, 2099),
('5',  'ITL Art.16(2)',      'Companies listed on Lao Stock Exchange (within 48 months of listing)', 13.00, 0, 2018, 2099),
('6',  'ITL Art.16(2)',      'Micro enterprises registered as VAT holders (0.1%)', 0.10, 0, 2018, 2099),
('7',  'IPL Art.9,10,11',   'Zone 1: IPL Art.9 activities 1,4,7,8,9 - Exemption within 120 months', NULL, 1, 2018, 2099),
('8',  'IPL Art.9,10,11',   'Zone 2: IPL Art.9 activities 1,4,7,8,9 - Exemption within 48 months', NULL, 1, 2018, 2099),
('9',  'IPL Art.14',         'Re-invested profit exemption for 1 year (12 months from re-invest date)', NULL, 1, 2018, 2099),
('10', 'IPL Art.9,10,11',   'Zone 1: IPL Art.9 activities 2,3,5,6 - Exemption within 180 months', NULL, 1, 2018, 2099),
('11', 'IPL Art.9,10,11',   'Zone 2: IPL Art.9 activities 2,3,5,6 - Exemption within 112 months', NULL, 1, 2018, 2099),
('12', 'IPL Art.9,10,11',   'SEZ Zone 1 developer + activity code Y - Exemption within 192 months', NULL, 1, 2018, 2099),
('13', 'IPL Art.9,10,11',   'SEZ Zone 1 developer + activity code Y - 5% rate months 192-252', 5.00, 0, 2018, 2099),
('14', 'IPL Art.9,10,11',   'SEZ Zone 2 developer + activity code Y - Exemption within 96 months', NULL, 1, 2018, 2099),
('15', 'IPL Art.9,10,11',   'SEZ Zone 2 developer + activity code Y - 5% rate months 96-156', 5.00, 0, 2018, 2099),
('16', 'IPL Art.9,10,11',   'SEZ investor (Zone 1 or 2) + specific activities - Exemption phase', NULL, 1, 2018, 2099),
('17', 'IPL Art.9,10,11',   'SEZ investor (Zone 1 or 2) + specific activities - Rate relief after exemption', NULL, 0, 2018, 2099),
('18', 'ITL Art.',           'Income from activities providing public benefit or social purpose', NULL, 1, 2018, 2099),
('19', 'ITL Art.',           'Rental income: compliant business operator', NULL, 1, 2018, 2099),
('20', 'ITL Art.',           'Income from transfer of real estate rights (on balance sheet)', NULL, 1, 2018, 2099);

-- =============================================================
-- Insert Rule Conditions for each Provision
-- Based exactly on Excel formulas (AS2:BL2)
-- Fields map to companies table columns
-- =============================================================

-- PROVISION 1: =IF(AND(AQ>=6, AQ<=50, AO<=1.5, AP<=3, TODAY()-T<=1095), 1, "")
-- AQ=staff_count, AO=total_assets(billion), AP=annual_turnover(billion), T=registration_date
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'staff_count', 'BETWEEN', '6', '50' FROM profit_provisions WHERE provision_number='1';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'total_assets', '<=', '1500000000', NULL FROM profit_provisions WHERE provision_number='1';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'annual_turnover', '<=', '3000000000', NULL FROM profit_provisions WHERE provision_number='1';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'registration_date', 'YEARS_PASSED_LESS_THAN', '3', NULL FROM profit_provisions WHERE provision_number='1';

-- PROVISION 2: =IF(AND(AQ>=51, AQ<=99, AO<=6, AP<=6, TODAY()-T<=1095), 2, "")
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'staff_count', 'BETWEEN', '51', '99' FROM profit_provisions WHERE provision_number='2';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'total_assets', '<=', '6000000000', NULL FROM profit_provisions WHERE provision_number='2';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'annual_turnover', '<=', '6000000000', NULL FROM profit_provisions WHERE provision_number='2';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'registration_date', 'YEARS_PASSED_LESS_THAN', '3', NULL FROM profit_provisions WHERE provision_number='2';

-- PROVISION 3: =IF(AND(U=1, TODAY()>EDATE(B, S*12)), 3, "")
-- U=flag_hr_dev, B=investment_license_date, S=tax_holiday_years
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_hr_dev', '=', '1', NULL FROM profit_provisions WHERE provision_number='3';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'TAX_HOLIDAY_ENDED', 'tax_holiday_years', NULL FROM profit_provisions WHERE provision_number='3';

-- PROVISION 4: =IF(AND(V=1, TODAY()>EDATE(B, S*12)), 4, "")
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_eco_friendly', '=', '1', NULL FROM profit_provisions WHERE provision_number='4';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'TAX_HOLIDAY_ENDED', 'tax_holiday_years', NULL FROM profit_provisions WHERE provision_number='4';

-- PROVISION 5: =IF(AND(ISNUMBER(AR), TODAY()<=EDATE(AR, 48)), 5, "")
-- AR=stock_exchange_listing_date
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'stock_exchange_listing_date', 'EDATE_MONTHS_NOT_EXCEEDED', '48', NULL FROM profit_provisions WHERE provision_number='5';

-- PROVISION 6: =IF(AND(AQ>=6, AQ<=50, AO<=1.5, AP<=3, AL=1), 6, "")
-- AL=is_vat_holder
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'staff_count', 'BETWEEN', '6', '50' FROM profit_provisions WHERE provision_number='6';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'total_assets', '<=', '1500000000', NULL FROM profit_provisions WHERE provision_number='6';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'annual_turnover', '<=', '3000000000', NULL FROM profit_provisions WHERE provision_number='6';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'is_vat_holder', '=', '1', NULL FROM profit_provisions WHERE provision_number='6';

-- PROVISION 7: =IF(AND(G=1, OR(AC,AF,AI,AJ,AK)=1, TODAY()<=EDATE(B,120)), 7, "")
-- G=zone_1, AC=act1, AF=act4, AI=act7, AJ=act8, AK=act9
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_1', '=', '1', NULL FROM profit_provisions WHERE provision_number='7';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_1_4_7_8_9', '=', '1', NULL FROM profit_provisions WHERE provision_number='7';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_NOT_EXCEEDED', '120', NULL FROM profit_provisions WHERE provision_number='7';

-- PROVISION 8: =IF(AND(H=1, OR(AC,AF,AI,AJ,AK)=1, TODAY()<=EDATE(B,48)), 8, "")
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_2', '=', '1', NULL FROM profit_provisions WHERE provision_number='8';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_1_4_7_8_9', '=', '1', NULL FROM profit_provisions WHERE provision_number='8';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_NOT_EXCEEDED', '48', NULL FROM profit_provisions WHERE provision_number='8';

-- PROVISION 9: =IF(AND(ISNUMBER(AM), AN>0, TODAY()<=EDATE(AM,12)), 9, "")
-- AM=reinvest_date, AN=reinvest_amount
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'reinvest_amount', '>', '0', NULL FROM profit_provisions WHERE provision_number='9';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'reinvest_date', 'EDATE_MONTHS_NOT_EXCEEDED', '12', NULL FROM profit_provisions WHERE provision_number='9';

-- PROVISION 10: Zone 1 + Art.9 sect 2,3,5,6 + within 180 months
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_1', '=', '1', NULL FROM profit_provisions WHERE provision_number='10';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_2_3_5_6', '=', '1', NULL FROM profit_provisions WHERE provision_number='10';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_NOT_EXCEEDED', '180', NULL FROM profit_provisions WHERE provision_number='10';

-- PROVISION 11: Zone 2 + Art.9 sect 2,3,5,6 + within 112 months
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_2', '=', '1', NULL FROM profit_provisions WHERE provision_number='11';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_2_3_5_6', '=', '1', NULL FROM profit_provisions WHERE provision_number='11';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_NOT_EXCEEDED', '112', NULL FROM profit_provisions WHERE provision_number='11';

-- PROVISION 12: SEZ Zone 1 + developer + act Y + within 192 months
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_1', '=', '1', NULL FROM profit_provisions WHERE provision_number='12';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_sez_developer', '=', '1', NULL FROM profit_provisions WHERE provision_number='12';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_production_services', '=', '1', NULL FROM profit_provisions WHERE provision_number='12';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_NOT_EXCEEDED', '192', NULL FROM profit_provisions WHERE provision_number='12';

-- PROVISION 13: SEZ Zone 1 developer + act Y + months 192-252
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_1', '=', '1', NULL FROM profit_provisions WHERE provision_number='13';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_sez_developer', '=', '1', NULL FROM profit_provisions WHERE provision_number='13';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_production_services', '=', '1', NULL FROM profit_provisions WHERE provision_number='13';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_BETWEEN', '192', '252' FROM profit_provisions WHERE provision_number='13';

-- PROVISION 14: SEZ Zone 2 + developer + act Y + within 96 months
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_2', '=', '1', NULL FROM profit_provisions WHERE provision_number='14';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_sez_developer', '=', '1', NULL FROM profit_provisions WHERE provision_number='14';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_production_services', '=', '1', NULL FROM profit_provisions WHERE provision_number='14';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_NOT_EXCEEDED', '96', NULL FROM profit_provisions WHERE provision_number='14';

-- PROVISION 15: SEZ Zone 2 developer + act Y + months 96-156
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_2', '=', '1', NULL FROM profit_provisions WHERE provision_number='15';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_sez_developer', '=', '1', NULL FROM profit_provisions WHERE provision_number='15';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_production_services', '=', '1', NULL FROM profit_provisions WHERE provision_number='15';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'investment_license_date', 'EDATE_MONTHS_BETWEEN', '96', '156' FROM profit_provisions WHERE provision_number='15';

-- PROVISION 16: SEZ investor (Zone 1) + specific activities (exemption phase)
-- Engine only supports AND, so this is the Zone 1 path.
-- Zone 2 path would need a separate provision or OR support in engine.
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_sez_investor', '=', '1', NULL FROM profit_provisions WHERE provision_number='16';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_1', '=', '1', NULL FROM profit_provisions WHERE provision_number='16';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_1_4_7_8_9', '=', '1', NULL FROM profit_provisions WHERE provision_number='16';

-- PROVISION 17: SEZ investor (Zone 1) after exemption (rate relief)
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_sez_investor', '=', '1', NULL FROM profit_provisions WHERE provision_number='17';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'zone_1', '=', '1', NULL FROM profit_provisions WHERE provision_number='17';
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_act_1_4_7_8_9', '=', '1', NULL FROM profit_provisions WHERE provision_number='17';

-- PROVISION 18: =IF(Z=1, 18, "") - Public benefit income
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_public_benefit', '=', '1', NULL FROM profit_provisions WHERE provision_number='18';

-- PROVISION 19: =IF(AA=1, 19, "") - Compliant rental income
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_compliant_rental', '=', '1', NULL FROM profit_provisions WHERE provision_number='19';

-- PROVISION 20: =IF(AB=1, 20, "") - Real estate transfer income
INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2)
SELECT id, 'flag_real_estate_transfer', '=', '1', NULL FROM profit_provisions WHERE provision_number='20';

SELECT CONCAT('Seeded ', COUNT(*), ' provisions') as result FROM profit_provisions;
SELECT CONCAT('Seeded ', COUNT(*), ' provision conditions') as result FROM profit_provision_conditions;
