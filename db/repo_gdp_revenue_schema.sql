USE tax_ets;

CREATE TABLE IF NOT EXISTS repo_gdp_revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    gdp_year INT NOT NULL,
    gdp_value DECIMAL(20, 2) DEFAULT 0,
    revenue_value DECIMAL(20, 2) DEFAULT 0,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (gdp_year)
);
