-- Salary Tax Seed Data
-- Provisions and benchmark rates for Salary Tax (Withholding Tax)
-- Based on Lao PIT Law provisions applicable to salary/wage withholding

USE tax_ets;

-- ============================================================
-- SEED: Salary Tax Provisions
-- ============================================================
INSERT INTO salary_provisions (provision_number, legal_basis, type_of_te, description, purpose) VALUES
('T21', 'PIT Law Art. 22', 'Exemption', 'Personal Allowance / Base Exemption', 'Basic personal exemption for individual taxpayers'),
('T22', 'PIT Law Art. 23', 'Deduction', 'Spouse Allowance', 'Deduction for married taxpayers with non-earning spouse'),
('T23', 'PIT Law Art. 24', 'Deduction', 'Child / Educational Allowance', 'Deduction for dependent children under 18 or in education'),
('T24', 'PIT Law Art. 25', 'Exemption', 'Senior / Elderly Allowance', 'Additional exemption for taxpayers aged 60+'),
('T25', 'PIT Law Art. 26', 'Exemption', 'Disability Allowance', 'Exemption for persons with disabilities'),
('T26', 'PIT Law Art. 27', 'Deduction', 'Insurance Premium Deduction', 'Deduction for insurance premiums paid'),
('T27', 'PIT Law Art. 28', 'Deduction', 'Life Insurance', 'Deduction for life insurance premiums'),
('T28', 'PIT Law Art. 29', 'Deduction', 'Housing Interest', 'Deduction for housing loan interest'),
('T29', 'PIT Law Art. 30', 'Deduction', 'Social Security Contribution', 'Deduction for mandatory social security contributions'),
('T30', 'PIT Law Art. 31', 'Exemption', 'Other Exemptions', 'Other tax-exempt income per PIT law')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================================
-- SEED: Benchmark Rates (default 10% effective rate)
-- ============================================================
-- The rate represents the estimated effective tax rate applicable
-- to the exempt amount for each provision. 10% approximates the
-- average effective PIT rate under progressive brackets for
-- typical wage earners in Lao PDR.
INSERT INTO bm_salary_rates (start_year, end_year, provision_number, rate_percentage, description) VALUES
(2020, 2099, 'T21', 10.00, 'Personal Allowance - estimated effective rate'),
(2020, 2099, 'T22', 10.00, 'Spouse Allowance - estimated effective rate'),
(2020, 2099, 'T23', 10.00, 'Child Allowance - estimated effective rate'),
(2020, 2099, 'T24', 10.00, 'Senior Allowance - estimated effective rate'),
(2020, 2099, 'T25', 10.00, 'Disability Allowance - estimated effective rate'),
(2020, 2099, 'T26', 10.00, 'Insurance Premium - estimated effective rate'),
(2020, 2099, 'T27', 10.00, 'Life Insurance - estimated effective rate'),
(2020, 2099, 'T28', 10.00, 'Housing Interest - estimated effective rate'),
(2020, 2099, 'T29', 5.00,  'Social Security Contribution - reduced rate'),
(2020, 2099, 'T30', 10.00, 'Other Exemptions - estimated effective rate'),
(2020, 2099, 'Multiple', 10.00, 'Default rate for multiple/unspecified provisions')
ON DUPLICATE KEY UPDATE rate_percentage = VALUES(rate_percentage);
