USE tax_ets;

CREATE TABLE IF NOT EXISTS repo_mpi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_batch_id VARCHAR(50),
    tin VARCHAR(50) NOT NULL,
    project_name VARCHAR(255),
    investment_license_date DATE,
    activities TEXT,
    incentives TEXT,
    sector_id INT,
    tax_holiday_period VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES business_sectors(id) ON DELETE SET NULL,
    INDEX (tin)
);
