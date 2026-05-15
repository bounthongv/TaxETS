-- Tax-ETS Backup 2026-05-07 06:30:21

-- Table: roles
INSERT INTO roles (id, role_name, role_description, created_at) VALUES
('1', 'SUPER ADMIN', 'Full system access', '2026-05-06 16:19:04'),
('2', 'ADMIN', 'Administrative access', '2026-05-06 16:19:04'),
('3', 'ACCOUNTING', 'Accounting team access', '2026-05-06 16:19:04'),
('4', 'TECH_TEAM_MOF', 'Technical team access', '2026-05-06 16:19:04'),
('5', 'USER', 'Basic user access', '2026-05-06 16:19:04');

-- Table: users
INSERT INTO users (id, name, email, password, position, phone, role_id, photo, active, created_at, updated_at) VALUES
('1', 'Administrator', 'admin@example.com', '$2y$12$iae8xInFlkJ12An0eLGT3.hbMBs7KKHxh8AIutU8VtbT98XWrLAQ6', 'System Admin', NULL, '1', NULL, '1', '2026-05-06 16:19:04', '2026-05-07 11:03:17');

-- Table: user_history
INSERT INTO user_history (id, user_id, user_name, action, details, ip_address, created_at) VALUES
('1', '1', 'Administrator', 'LOGOUT', 'User logged out', '::1', '2026-05-07 11:06:20'),
('2', '1', 'Administrator', 'LOGIN', 'User logged in', '::1', '2026-05-07 11:06:23');

-- Table: user_sessions
INSERT INTO user_sessions (id, user_id, session_token, ip_address, login_at, last_activity, is_online) VALUES
('1', '1', '74e183606c320c1569007ab039d781d2e8baa6d45d32ef9c0ac2094f1ce1c73b', '::1', '2026-05-07 11:03:41', '2026-05-07 11:03:41', '1'),
('2', '1', 'b7fe64c944265baf02042a12e0d6587d8fc7b0c4dd33938573190df332e6515f', '::1', '2026-05-07 11:04:26', '2026-05-07 11:06:20', '0'),
('3', '1', '2c5a23926b7d2fdf1a5da808c2079437d43d474be401c08a2b4986a0b1ded915', '::1', '2026-05-07 11:06:23', '2026-05-07 11:30:13', '1');

-- Table: role_permissions
INSERT INTO role_permissions (id, role_id, module, can_create, can_read, can_update, can_delete) VALUES
('1', '1', 'system_users', '1', '1', '0', '0'),
('2', '1', 'system_roles', '1', '1', '0', '0'),
('3', '1', 'dictionary_province', '0', '1', '0', '0'),
('4', '1', 'dictionary_district', '0', '1', '0', '0'),
('5', '1', 'dictionary_zone', '0', '1', '0', '0'),
('6', '1', 'config_rates', '0', '1', '0', '0'),
('7', '1', 'benchmark_individual', '0', '1', '0', '0'),
('8', '1', 'benchmark_vat', '0', '1', '0', '0'),
('9', '1', 'benchmark_customs', '0', '1', '0', '0'),
('10', '1', 'benchmark_excise', '0', '1', '0', '0'),
('11', '1', 'config_provisions', '0', '1', '0', '0'),
('12', '1', 'repo_individual', '0', '1', '0', '0'),
('13', '1', 'repo_vat', '0', '1', '0', '0'),
('14', '1', 'import_cit', '0', '1', '0', '0'),
('15', '1', 'import_individual', '0', '1', '0', '0'),
('16', '1', 'import_vat', '0', '1', '0', '0'),
('17', '1', 'calculator', '0', '1', '0', '0'),
('18', '1', 'report_tax_type', '0', '1', '0', '0');

-- Table: bm_profit_standard
INSERT INTO bm_profit_standard (id, start_year, end_year, category, rate_percentage) VALUES
('7', '2018', '2019', 'Standard', '24.00'),
('8', '2018', '2019', 'Tobacco/Mining', '26.00'),
('9', '2020', '2021', 'Standard', '20.00'),
('10', '2020', '2021', 'Tobacco', '22.00'),
('11', '2022', '2099', 'Standard', '20.00'),
('12', '2022', '2099', 'Tobacco', '22.00');

-- Table: bm_profit_mandatory
INSERT INTO bm_profit_mandatory (id, start_year, end_year, sector, sub_sector, profit_base_rate) VALUES
('13', '2018', '2019', 'Production', 'Agricultural & Industrial', '3.00'),
('14', '2018', '2019', 'Commerce', 'General', '5.00'),
('15', '2018', '2019', 'Service', 'Transport (Goods/Pax)', '5.00'),
('16', '2018', '2019', 'Service', 'Construction & Repairs', '10.00'),
('17', '2018', '2019', 'Service', 'Wood/Minerals (Trading)', '20.00'),
('18', '2018', '2019', 'Service', 'Planted Trees (Trading)', '5.00'),
('19', '2018', '2019', 'Service', 'Extraction (Soil/Sand/Rock)', '15.00'),
('20', '2018', '2019', 'Service', 'Entertainment', '25.00'),
('21', '2020', '2021', 'Production', 'Agricultural & Industrial', '5.00'),
('22', '2020', '2021', 'Commerce', 'General', '3.00'),
('23', '2020', '2021', 'Service', 'Transport (Goods/Pax)', '5.00'),
('24', '2020', '2021', 'Service', 'Construction & Repairs', '10.00'),
('25', '2020', '2021', 'Service', 'Wood/Minerals (Trading)', '20.00'),
('26', '2020', '2021', 'Service', 'Planted Trees (Trading)', '5.00'),
('27', '2020', '2021', 'Service', 'Extraction (Soil/Sand/Rock)', '15.00'),
('28', '2020', '2021', 'Service', 'Entertainment', '25.00'),
('29', '2022', '2099', 'Production', 'Agricultural & Industrial', '5.00'),
('30', '2022', '2099', 'Commerce', 'General', '3.00'),
('31', '2022', '2099', 'Service', 'Transport (Goods/Pax)', '5.00'),
('32', '2022', '2099', 'Service', 'Construction & Repairs', '10.00'),
('33', '2022', '2099', 'Service', 'Wood/Minerals (Trading)', '20.00'),
('34', '2022', '2099', 'Service', 'Planted Trees (Trading)', '5.00'),
('35', '2022', '2099', 'Service', 'Extraction (Soil/Sand/Rock)', '15.00'),
('36', '2022', '2099', 'Service', 'Real Estate (Buying/Selling/Rent)', '10.00'),
('37', '2022', '2099', 'Service', 'Entertainment', '25.00');

-- Table: bm_profit_sme
INSERT INTO bm_profit_sme (id, start_year, end_year, sector, turnover_min, turnover_max, rate_percentage) VALUES
('6', '2018', '2019', 'Production', '50000001.00', '120000000.00', '3.00'),
('7', '2018', '2019', 'Commerce', '50000001.00', '120000000.00', '4.00'),
('8', '2018', '2019', 'Service', '50000001.00', '120000000.00', '5.00'),
('9', '2018', '2019', 'Production', '120000001.00', '240000000.00', '4.00'),
('10', '2018', '2019', 'Commerce', '120000001.00', '240000000.00', '5.00'),
('11', '2018', '2019', 'Service', '120000001.00', '240000000.00', '6.00'),
('12', '2020', '2021', 'All', '50000001.00', '400000000.00', '1.00'),
('13', '2020', '2021', 'All', '400000001.00', '1200000000.00', '2.00'),
('14', '2020', '2021', 'All', '1200000001.00', '4000000000.00', '3.00'),
('15', '2022', '2099', 'All', '50000001.00', '400000000.00', '1.00'),
('16', '2022', '2099', 'All', '400000001.00', '1200000000.00', '2.00'),
('17', '2022', '2099', 'All', '1200000001.00', '4000000000.00', '3.00');

-- Table: profit_provisions
INSERT INTO profit_provisions (id, provision_number, start_year, end_year, legal_reference, description, target_rate, is_exemption) VALUES
('1', '1', '2020', '2099', 'ITL Art.16(2)', 'Small entity: 6-50 staff, assets <=1.5B, turnover <=3B, registered <=3 years', '3.00', '0'),
('2', '2', '2020', '2099', 'ITL Art.16(2)', 'Medium entity: 51-99 staff, assets <=6B, turnover <=6B, registered <=3 years', '5.00', '0'),
('3', '3', '2020', '2099', 'ITL Art.16(2)', 'HR development businesses (schools, hospitals, etc.) after tax holiday', '5.00', '0'),
('4', '4', '2020', '2099', 'ITL Art.16(2)', 'Eco-friendly/clean energy tech businesses after tax holiday', '7.00', '0'),
('5', '5', '2020', '2099', 'ITL Art.16(2)', 'Companies listed on Lao Stock Exchange (within 48 months of listing)', '13.00', '0'),
('6', '6', '2020', '2099', 'ITL Art.16(2)', 'Micro enterprises registered as VAT holders (0.1%)', '0.10', '0'),
('7', '7', '2020', '2099', 'IPL Art.9,10,11', 'Zone 1: IPL Art.9 activities 1,4,7,8,9 - Exemption within 120 months', NULL, '1'),
('8', '8', '2020', '2099', 'IPL Art.9,10,11', 'Zone 2: IPL Art.9 activities 1,4,7,8,9 - Exemption within 48 months', NULL, '1'),
('9', '9', '2020', '2099', 'IPL Art.14', 'Re-invested profit exemption for 1 year (12 months from re-invest date)', NULL, '1'),
('10', '10', '2020', '2099', 'IPL Art.9,10,11', 'Zone 1: IPL Art.9 activities 2,3,5,6 - Exemption within 180 months', NULL, '1'),
('11', '11', '2020', '2099', 'IPL Art.9,10,11', 'Zone 2: IPL Art.9 activities 2,3,5,6 - Exemption within 112 months', NULL, '1'),
('12', '12', '2020', '2099', 'IPL Art.9,10,11', 'SEZ Zone 1 developer + activity code Y - Exemption within 192 months', NULL, '1'),
('13', '13', '2020', '2099', 'IPL Art.9,10,11', 'SEZ Zone 1 developer + activity code Y - 5% rate months 192-252', '5.00', '0'),
('14', '14', '2020', '2099', 'IPL Art.9,10,11', 'SEZ Zone 2 developer + activity code Y - Exemption within 96 months', NULL, '1'),
('15', '15', '2020', '2099', 'IPL Art.9,10,11', 'SEZ Zone 2 developer + activity code Y - 5% rate months 96-156', '5.00', '0'),
('16', '16', '2020', '2099', 'IPL Art.9,10,11', 'SEZ investor (Zone 1 or 2) + specific activities - Exemption phase', NULL, '1'),
('17', '17', '2020', '2099', 'IPL Art.9,10,11', 'SEZ investor (Zone 1 or 2) + specific activities - Rate relief after exemption', NULL, '0'),
('18', '18', '2020', '2099', 'ITL Art.', 'Income from activities providing public benefit or social purpose', NULL, '1'),
('19', '19', '2020', '2099', 'ITL Art.', 'Rental income: compliant business operator', NULL, '1'),
('20', '20', '2020', '2099', 'ITL Art.', 'Income from transfer of real estate rights (on balance sheet)', NULL, '1');

-- Table: profit_provision_conditions
INSERT INTO profit_provision_conditions (id, provision_id, field_name, operator, value_1, value_2) VALUES
('1', '1', 'staff_count', 'BETWEEN', '6', '50'),
('2', '1', 'total_assets', '<=', '1500000000', NULL),
('3', '1', 'annual_turnover', '<=', '3000000000', NULL),
('4', '1', 'registration_date', 'YEARS_PASSED_LESS_TH', '3', NULL),
('5', '2', 'staff_count', 'BETWEEN', '51', '99'),
('6', '2', 'total_assets', '<=', '6000000000', NULL),
('7', '2', 'annual_turnover', '<=', '6000000000', NULL),
('8', '2', 'registration_date', 'YEARS_PASSED_LESS_TH', '3', NULL),
('9', '3', 'flag_hr_dev', '=', '1', NULL),
('10', '3', 'investment_license_date', 'TAX_HOLIDAY_ENDED', 'tax_holiday_years', NULL),
('11', '4', 'flag_eco_friendly', '=', '1', NULL),
('12', '4', 'investment_license_date', 'TAX_HOLIDAY_ENDED', 'tax_holiday_years', NULL),
('13', '5', 'stock_exchange_listing_date', 'EDATE_MONTHS_NOT_EXC', '48', NULL),
('14', '6', 'staff_count', 'BETWEEN', '6', '50'),
('15', '6', 'total_assets', '<=', '1500000000', NULL),
('16', '6', 'annual_turnover', '<=', '3000000000', NULL),
('17', '6', 'is_vat_holder', '=', '1', NULL),
('18', '7', 'zone_1', '=', '1', NULL),
('19', '7', 'flag_act_1_4_7_8_9', '=', '1', NULL),
('20', '7', 'investment_license_date', 'EDATE_MONTHS_NOT_EXC', '120', NULL),
('21', '8', 'zone_2', '=', '1', NULL),
('22', '8', 'flag_act_1_4_7_8_9', '=', '1', NULL),
('23', '8', 'investment_license_date', 'EDATE_MONTHS_NOT_EXC', '48', NULL),
('24', '9', 'reinvest_amount', '>', '0', NULL),
('25', '9', 'reinvest_date', 'EDATE_MONTHS_NOT_EXC', '12', NULL),
('26', '10', 'zone_1', '=', '1', NULL),
('27', '10', 'flag_act_2_3_5_6', '=', '1', NULL),
('28', '10', 'investment_license_date', 'EDATE_MONTHS_NOT_EXC', '180', NULL),
('29', '11', 'zone_2', '=', '1', NULL),
('30', '11', 'flag_act_2_3_5_6', '=', '1', NULL),
('31', '11', 'investment_license_date', 'EDATE_MONTHS_NOT_EXC', '112', NULL),
('32', '12', 'zone_1', '=', '1', NULL),
('33', '12', 'flag_sez_developer', '=', '1', NULL),
('34', '12', 'flag_act_production_services', '=', '1', NULL),
('35', '12', 'investment_license_date', 'EDATE_MONTHS_NOT_EXC', '192', NULL),
('36', '13', 'zone_1', '=', '1', NULL),
('37', '13', 'flag_sez_developer', '=', '1', NULL),
('38', '13', 'flag_act_production_services', '=', '1', NULL),
('39', '13', 'investment_license_date', 'EDATE_MONTHS_BETWEEN', '192', '252'),
('40', '14', 'zone_2', '=', '1', NULL),
('41', '14', 'flag_sez_developer', '=', '1', NULL),
('42', '14', 'flag_act_production_services', '=', '1', NULL),
('43', '14', 'investment_license_date', 'EDATE_MONTHS_NOT_EXC', '96', NULL),
('44', '15', 'zone_2', '=', '1', NULL),
('45', '15', 'flag_sez_developer', '=', '1', NULL),
('46', '15', 'flag_act_production_services', '=', '1', NULL),
('47', '15', 'investment_license_date', 'EDATE_MONTHS_BETWEEN', '96', '156'),
('48', '16', 'flag_sez_investor', '=', '1', NULL),
('49', '17', 'flag_sez_investor', '=', '1', NULL),
('50', '18', 'flag_public_benefit', '=', '1', NULL),
('51', '19', 'flag_compliant_rental', '=', '1', NULL),
('52', '20', 'flag_real_estate_transfer', '=', '1', NULL);

