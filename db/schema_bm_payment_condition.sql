USE tax_ets;

CREATE TABLE IF NOT EXISTS bm_payment_condition_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condition_code VARCHAR(10) NOT NULL,
    description VARCHAR(255) NOT NULL,
    effective_date_from DATE DEFAULT NULL,
    effective_date_to DATE DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (condition_code)
);

-- Seed with codes from legacy system/docs
INSERT INTO bm_payment_condition_codes (condition_code, description) VALUES
('000', 'Payment or Exemption of Customs Duties and Taxes/Fees as follow regular Conditions'),
('100', 'Royalty Exemption (Applicable for Exportation EX1 “10”)'),
('442', 'Exemption of 5% Industry Tax and General Sales Tax, Exemption of Industry Tax and General Sales Tax according to the General Trade Law'),
('443', 'Exemption of Industry Tax and General Sales Tax, Exemption of Industry Tax and General Sales Tax according to the General Trade Law'),
('445', 'Payment of Customs Duties and Taxes/Fees 50% based on the Investment Promotion Law'),
('450', 'Importation for Exporation (Applicable only to Code 45)'),
('460', 'Customs Duties and Tax are exempted for Diplomatic Officials and International Organizations'),
('470', 'Customs Duties and Tax Exemption for Grant Aid and Loan Projects of the Government'),
('480', 'Customs Duties and Tax Exemption for Government’ Investment Project'),
('481', 'Customs Duties and Tax Exemption for Private’ Invested Project'),
('490', 'Customs Duties and Tax Exemption under Government Incentives'),
('600', 'Customs Duties and Tax Exemption (Applicable on Re-Importation Code “60”)');
