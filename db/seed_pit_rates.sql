USE tax_ets;

-- Employment Brackets
INSERT INTO bm_pit_employment (start_year, end_year, min_income, max_income, rate_percentage) VALUES
(2020, 2099, 0, 1300000, 0.00),
(2020, 2099, 1300001, 5000000, 5.00),
(2020, 2099, 5000001, 15000000, 10.00),
(2020, 2099, 15000001, 25000000, 15.00),
(2020, 2099, 25000001, 65000000, 20.00),
(2020, 2099, 65000001, NULL, 25.00);

-- Flat Rates
INSERT INTO bm_pit_flat_rates (start_year, end_year, income_type, rate_percentage) VALUES
(2020, 2099, 'Online Sales', 2.00),
(2020, 2099, 'Lottery Winnings', 5.00),
(2020, 2099, 'Rental Income', 10.00),
(2020, 2099, 'Dividends', 10.00),
(2020, 2099, 'Intellectual Property', 5.00),
(2020, 2099, 'Real Estate Rights Transfer', 2.00),
(2020, 2099, 'Inheritance (non-lineage)', 2.00),
(2020, 2099, 'Consulting Fees', 5.00),
(2020, 2099, 'Gifts (cash/kind)', 5.00),
(2020, 2099, 'Shares Transfer', 2.00),
(2020, 2099, 'Loan Interest (non-bank)', 10.00);

-- Provisions
INSERT INTO individual_provisions (provision_number, legal_basis, type_of_te, description, purpose, start_year, end_year) VALUES
('21', 'ITL., Art. 35.3', 'Exemption', 'Overtime payments for employees receiving basic salary <= 2M LAK', 'Social', 2020, 2099),
('22', 'ITL., Art. 35.20', 'Exemption', 'Uniforms and equipment for preventing labour accidents', 'Social / Health', 2020, 2099),
('23', 'ITL., Art.35.3', 'Exemption', 'Personal allowance for spouse/children (max 15M/year)', 'Social', 2020, 2099),
('24', 'ITL., Art.35.3', 'Exemption', 'Government and National Assembly personnel allowances', 'Social', 2020, 2099),
('25', 'ITL., Art.35.3', 'Exemption', 'Allowances for students', 'Social', 2020, 2099),
('26', 'ITL., Art.35.4', 'Exemption', 'Profits from sale of shares on Lao stock exchange', 'Financial Sector', 2020, 2099),
('27', 'ITL., Art.35.4', 'Exemption', 'Dividends from stock exchange registered companies', 'Financial Sector', 2020, 2099),
('28', 'ITL., Art.35.11', 'Exemption', 'Interest on bank deposits and government bonds', 'Encourage Savings', 2020, 2099),
('29', 'ITL., Art.35.13', 'Exemption', 'Performance bonus for security monitoring/preventing illegal acts', 'National Security', 2020, 2099);

INSERT INTO individual_provisions (provision_number, legal_basis, type_of_te, description, purpose, limit_amount, start_year, end_year) VALUES
('30', 'Notice 0824/LSO', 'Deduction', 'Social security contributions (5.5%, max base 4.5M/mo)', 'Encourage Savings', 247500.00, 2020, 2099);
