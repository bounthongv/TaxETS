USE tax_ets;

CREATE TABLE IF NOT EXISTS bm_customs_regime_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    regime_code VARCHAR(10) NOT NULL,
    description VARCHAR(255) NOT NULL,
    effective_date_from DATE DEFAULT NULL,
    effective_date_to DATE DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (regime_code)
);

-- Seed with common ASYCUDA regime codes if applicable, or leave empty as per user's directive that it's for classification.
-- For now, I'll just create the table.
