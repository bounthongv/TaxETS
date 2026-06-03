-- Salary Tax Schema
-- Implements the same pattern as PIT: reference tables + import table + result columns
-- Run this after main schema.sql

USE tax_ets;

-- ============================================================
-- 1. SALARY TAX PROVISIONS (Reference table, like individual_provisions)
-- ============================================================
CREATE TABLE IF NOT EXISTS salary_provisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_number VARCHAR(10) NOT NULL UNIQUE,
    legal_basis VARCHAR(255),
    type_of_te VARCHAR(50) DEFAULT 'Exemption',  -- Exemption or Deduction
    description TEXT,
    purpose VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. SALARY TAX BENCHMARK RATES (Like bm_pit_flat_rates)
-- ============================================================
CREATE TABLE IF NOT EXISTS bm_salary_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    provision_number VARCHAR(10) NOT NULL,
    rate_percentage DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_provision_year (provision_number, start_year, end_year)
);

-- ============================================================
-- 3. IMPORT SALARY TAX DATA (Already exists, add if not exists)
-- ============================================================
CREATE TABLE IF NOT EXISTS import_salary_tax_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(50) NOT NULL,
    tax_year INT(4) DEFAULT NULL,
    import_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tin VARCHAR(50) DEFAULT NULL,
    filing_type VARCHAR(50) DEFAULT NULL,
    filing_period VARCHAR(50) DEFAULT NULL,
    input_date DATE DEFAULT NULL,
    total_salaries_wages_cash DECIMAL(20,2) DEFAULT 0.00,
    other_fringe_benefits DECIMAL(20,2) DEFAULT 0.00,
    total_taxable_amount DECIMAL(20,2) DEFAULT 0.00,
    tax_exempt_amount DECIMAL(20,2) DEFAULT 0.00,
    tax_amount DECIMAL(20,2) DEFAULT 0.00,
    adjustment_amount DECIMAL(20,2) DEFAULT 0.00,
    carryforward_amount DECIMAL(20,2) DEFAULT 0.00,
    total_amount_due DECIMAL(20,2) DEFAULT 0.00,

    -- Calculated Fields
    benchmark_tax DECIMAL(20,2) DEFAULT 0.00,
    te_amount DECIMAL(20,2) DEFAULT 0.00,
    provision_number VARCHAR(50) DEFAULT NULL,
    calculated_at DATETIME DEFAULT NULL,

    KEY idx_batch_id (batch_id),
    KEY idx_tin (tin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
