-- Land Concession Repository Table
CREATE TABLE IF NOT EXISTS repo_land_concession_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tax_year INT NOT NULL,
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    
    -- Dictionary Mapping
    pro_id VARCHAR(10),
    province VARCHAR(255),
    dis_id VARCHAR(50),
    district VARCHAR(255),
    
    confirm_date DATE,
    concession_area_ha DECIMAL(20,4) DEFAULT 0,
    benchmark_rate_usd DECIMAL(20,4) DEFAULT 0,
    contracted_rate_usd DECIMAL(20,4) DEFAULT 0,
    concession_fee_paid_usd DECIMAL(20,4) DEFAULT 0,
    
    -- Calculated/TE fields
    benchmark_value_usd DECIMAL(20,4) DEFAULT 0,
    non_tax_te_usd DECIMAL(20,4) DEFAULT 0,
    
    provision_code VARCHAR(50),
    provision_name VARCHAR(255),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (import_batch_id),
    INDEX (tin),
    INDEX (tax_year)
);
