-- Land Concession Benchmark Rates
CREATE TABLE IF NOT EXISTS bm_land_concession (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_type CHAR(1) NOT NULL COMMENT 'A, B, or C',
    rate_per_sqm DECIMAL(15,2) NOT NULL COMMENT 'Rate in Kip per square meter',
    rate_per_sqm_usd DECIMAL(15,2) COMMENT 'Rate in USD per square meter',
    start_year SMALLINT NOT NULL,
    end_year SMALLINT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Land Concession Provisions (Exemption Rules)
CREATE TABLE IF NOT EXISTS land_concession_provisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_code VARCHAR(20) NOT NULL UNIQUE,
    provision_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) COMMENT 'Industrial/SEZ/Priority',
    exemption_years SMALLINT NOT NULL DEFAULT 0,
    conditions JSON,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Land Concession TE Results
CREATE TABLE IF NOT EXISTS te_land_concession_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    zone_type CHAR(1),
    benchmark_rate DECIMAL(15,2),
    land_value_kip DECIMAL(15,2),
    exemption_years SMALLINT,
    exemption_value DECIMAL(15,2),
    te_land_concession DECIMAL(15,2),
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Import land concession data (sample benchmark rates for 2025)
INSERT INTO bm_land_concession (zone_type, rate_per_sqm, rate_per_sqm_usd, start_year, end_year, active) VALUES
('A', 150000, 10.00, 2025, NULL, TRUE),
('B', 80000, 5.33, 2025, NULL, TRUE),
('C', 40000, 2.67, 2025, NULL, TRUE);

-- Sample provisions
INSERT INTO land_concession_provisions (provision_code, provision_name, category, exemption_years, conditions, active) VALUES
('LC_IND', 'Factory/Industrial Project', 'Industrial', 5, NULL, TRUE),
('LC_SEZ', 'SEZ Industrial Project', 'SEZ', 10, NULL, TRUE),
('LC_PRIORITY', 'Priority Project', 'Priority', 7, NULL, TRUE);

-- Add columns to companies table if not exist
-- ALTER TABLE companies ADD COLUMN land_area_sqm DECIMAL(15,2) AFTER address;
-- ALTER TABLE companies ADD COLUMN zone_type CHAR(1) DEFAULT 'A' COMMENT 'A, B, C' AFTER land_area_sqm;