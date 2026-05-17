USE tax_ets;

CREATE TABLE IF NOT EXISTS repo_taxris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    year INT NOT NULL,
    revenue DECIMAL(20, 2) DEFAULT 0,
    expense DECIMAL(20, 2) DEFAULT 0,
    net_profit DECIMAL(20, 2) DEFAULT 0,
    tax_paid DECIMAL(20, 2) DEFAULT 0,
    tax_rate_paid DECIMAL(5, 2) DEFAULT 0,
    total_assets DECIMAL(20, 2) DEFAULT 0,
    vat_system_status TINYINT(1) DEFAULT 0,
    reinvest_net_profit DECIMAL(20, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (tin, year)
);
