USE tax_ets;

CREATE TABLE IF NOT EXISTS vat_provision_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provision_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    operator VARCHAR(20) NOT NULL,
    value_1 VARCHAR(255),
    value_2 VARCHAR(255),
    FOREIGN KEY (provision_id) REFERENCES vat_provisions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
