USE tax_ets;

-- 1. Business Categories Table
CREATE TABLE IF NOT EXISTS business_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Business Categories
INSERT IGNORE INTO business_categories (category_name) VALUES 
('Agriculture & Forestry'),
('Manufacturing & Processing'),
('Energy & Mining'),
('Services & Tourism'),
('Commerce & Trading'),
('Technology & Innovation'),
('Logistics & Transport'),
('Education & Health');

-- 2. SEZO Repository Table
CREATE TABLE IF NOT EXISTS repo_sezo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    province_id INT,
    district_id INT,
    category_id INT,
    type ENUM('Investor', 'Developer') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES business_categories(id) ON DELETE SET NULL,
    INDEX (tin)
);
