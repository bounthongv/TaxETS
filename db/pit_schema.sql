USE tax_ets;

-- 1. Employment Income Brackets (Progressive)
CREATE TABLE IF NOT EXISTS bm_pit_employment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    min_income DECIMAL(20, 2) NOT NULL,
    max_income DECIMAL(20, 2), -- NULL for the top bracket
    rate_percentage DECIMAL(5, 2) NOT NULL
);

-- 2. Other Income Types (Flat Rates)
CREATE TABLE IF NOT EXISTS bm_pit_flat_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    income_type VARCHAR(100) NOT NULL,
    rate_percentage DECIMAL(5, 2) NOT NULL
);

-- 3. Individual Tax Provisions
CREATE TABLE IF NOT EXISTS individual_provisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_number VARCHAR(10) NOT NULL,
    legal_basis VARCHAR(255),
    type_of_te VARCHAR(50), -- Exemption or Deduction
    description TEXT,
    purpose VARCHAR(255),
    limit_amount DECIMAL(20, 2) DEFAULT NULL -- e.g., 5,000,000 for personal allowance
);

-- 4. Results for Individual TE
CREATE TABLE IF NOT EXISTS te_individual_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_year INT NOT NULL,
    tin VARCHAR(50),
    individual_name VARCHAR(255),
    employment_income DECIMAL(20, 2) DEFAULT 0,
    other_income DECIMAL(20, 2) DEFAULT 0,
    actual_tax_paid DECIMAL(20, 2) DEFAULT 0,
    benchmark_calculated_tax DECIMAL(20, 2) DEFAULT 0,
    te_amount DECIMAL(20, 2) DEFAULT 0,
    matched_provisions VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
