USE tax_ets;

CREATE TABLE IF NOT EXISTS repo_lse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tin VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    listing_date DATE,
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (tin)
);
