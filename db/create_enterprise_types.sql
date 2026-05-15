USE tax_ets;

CREATE TABLE IF NOT EXISTS enterprise_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(255) NOT NULL UNIQUE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO enterprise_types (id, type_name) VALUES 
(1, 'Individual Enterprise'),
(2, 'Limited Company'),
(3, 'Public Company'),
(4, 'Partnership Enterprise');

-- Ensure repo_moic is linked (it already has enterprise_type_id column)
ALTER TABLE repo_moic ADD CONSTRAINT fk_moic_ent_type FOREIGN KEY (enterprise_type_id) REFERENCES enterprise_types(id) ON DELETE SET NULL;
