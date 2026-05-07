-- Creates the base database (Run this on your Ubuntu server)
CREATE DATABASE IF NOT EXISTS tax_ets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tax_ets;

-- 1. STANDARD BENCHMARK RATES
CREATE TABLE bm_profit_standard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    category VARCHAR(50) NOT NULL, -- e.g., 'Standard', 'Tobacco', 'Mining/Electricity'
    rate_percentage DECIMAL(5, 2) NOT NULL -- e.g., 20.00, 24.00
);

-- 2. MANDATORY PROFIT BASE RATES (For Non-VAT Holders)
CREATE TABLE bm_profit_mandatory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    sector VARCHAR(100) NOT NULL, -- e.g., 'Production', 'Commerce', 'Services'
    sub_sector VARCHAR(255),
    profit_base_rate DECIMAL(5, 2) NOT NULL -- e.g., 3.00, 5.00
);

-- 3. MICRO ENTERPRISE RATES (For VAT Holders under threshold / SME)
CREATE TABLE bm_profit_sme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    sector VARCHAR(100) NOT NULL,
    turnover_min DECIMAL(15, 2) DEFAULT 0,
    turnover_max DECIMAL(15, 2),
    rate_percentage DECIMAL(5, 2) NOT NULL -- e.g., 1.00, 2.00
);

-- 4. TAX PROVISIONS (The 20 profit tax provisions)
CREATE TABLE profit_provisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_number VARCHAR(10) NOT NULL, -- e.g., '1', '2', '1A'
    legal_reference VARCHAR(255),
    description TEXT,
    target_rate DECIMAL(5, 2) DEFAULT NULL, -- Null if exemption (0%) or calculated elsewhere
    is_exemption BOOLEAN DEFAULT FALSE
);

-- 5. PROVISION CONDITIONS (Dynamic Rule Engine for Profit Tax)
CREATE TABLE profit_provision_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL, -- Matches columns in 'companies' table
    operator VARCHAR(20) NOT NULL, -- '=', '>=', '<=', 'BETWEEN', 'YEARS_PASSED'
    value_1 VARCHAR(255),
    value_2 VARCHAR(255),
    FOREIGN KEY (provision_id) REFERENCES profit_provisions(id) ON DELETE CASCADE
);

-- 6. IMPORTED COMPANIES (From Excel)
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tax_year INT NOT NULL,
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    province VARCHAR(100),
    district VARCHAR(100),
    sector VARCHAR(100),
    is_vat_holder BOOLEAN DEFAULT FALSE,
    zone_1 BOOLEAN DEFAULT FALSE,
    zone_2 BOOLEAN DEFAULT FALSE,
    zone_3 BOOLEAN DEFAULT FALSE,
    revenue DECIMAL(20, 2) DEFAULT 0,
    expense DECIMAL(20, 2) DEFAULT 0,
    net_profit DECIMAL(20, 2) DEFAULT 0,
    re_invested_profit DECIMAL(20, 2) DEFAULT 0,
    pt_paid DECIMAL(20, 2) DEFAULT 0,
    activity_type VARCHAR(100),
    staff_count INT DEFAULT 0,
    total_assets DECIMAL(20, 2) DEFAULT 0,
    registration_date DATE,
    investment_license_date DATE
);

-- 7. TE RESULTS (Profit Tax)
CREATE TABLE te_profit_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    benchmark_rate_applied DECIMAL(5, 2),
    benchmark_pt DECIMAL(20, 2) DEFAULT 0,
    pt_te DECIMAL(20, 2) DEFAULT 0,
    matched_provisions VARCHAR(255), -- Comma separated provision numbers (e.g., "1, 6")
    profit_tax_te DECIMAL(20, 2) DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 8. USER ROLES
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. SYSTEM USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100),
    role_id INT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO roles (role_name, role_description) VALUES ('SUPER ADMIN', 'Full system access');
INSERT INTO roles (role_name, role_description) VALUES ('ADMIN', 'Administrative access');
INSERT INTO roles (role_name, role_description) VALUES ('USER', 'Basic user access');
INSERT INTO users (name, email, password, position, role_id, active) 
VALUES ('Administrator', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 1, 1);
