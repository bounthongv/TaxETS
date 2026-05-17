USE tax_ets;

CREATE TABLE IF NOT EXISTS bm_art9_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id VARCHAR(50) NOT NULL UNIQUE,
    short_name VARCHAR(255) NOT NULL,
    short_name_en VARCHAR(255),
    content TEXT,
    more_info TEXT,
    tax_rule_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Note: We already have a 'config_rules.php' which likely manages tax rules.
-- I will check if there's a table for tax rules.
