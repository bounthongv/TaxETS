USE tax_ets;

-- Create new table for MSME Definitions under Benchmark
CREATE TABLE IF NOT EXISTS bm_msme_definition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    effective_date_from DATE NOT NULL,
    effective_date_to DATE DEFAULT NULL,
    sector_id INT DEFAULT NULL,
    legacy_item_id VARCHAR(50) DEFAULT NULL,
    item_name VARCHAR(255) NOT NULL,
    micro_value VARCHAR(255) NOT NULL,
    small_value VARCHAR(255) NOT NULL,
    medium_value VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES business_sectors(id) ON DELETE SET NULL,
    INDEX (effective_date_from)
);

-- Seed standard Lao PDR MSME Definitions (General)
INSERT INTO bm_msme_definition (effective_date_from, item_name, micro_value, small_value, medium_value) VALUES
('2020-01-01', 'Number of Employees', '1-5 staff', '6-50 staff', '51-99 staff'),
('2020-01-01', 'Total Assets', '< 250M LAK', '<= 1.5B LAK', '<= 6B LAK'),
('2020-01-01', 'Annual Turnover', '< 400M LAK', '<= 3B LAK', '<= 6B LAK');
