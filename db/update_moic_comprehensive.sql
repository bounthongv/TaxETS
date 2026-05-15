USE tax_ets;

ALTER TABLE repo_moic
ADD COLUMN industry_manuf_scope TINYINT(1) DEFAULT 2,
ADD COLUMN commerce_service_scope TINYINT(1) DEFAULT 2,
ADD COLUMN electric_mining_scope TINYINT(1) DEFAULT 2,
ADD COLUMN agri_industrial_scope TINYINT(1) DEFAULT 2,
ADD COLUMN commerce_scope TINYINT(1) DEFAULT 2,
ADD COLUMN transport_scope TINYINT(1) DEFAULT 2,
ADD COLUMN construction_scope TINYINT(1) DEFAULT 2,
ADD COLUMN wood_exploitation_scope TINYINT(1) DEFAULT 2,
ADD COLUMN extraction_filling_scope TINYINT(1) DEFAULT 2,
ADD COLUMN entertainment_scope TINYINT(1) DEFAULT 2,
ADD COLUMN consultancy_scope TINYINT(1) DEFAULT 2,
ADD COLUMN brokers_agents_scope TINYINT(1) DEFAULT 2,
ADD COLUMN real_estate_dev_sale_scope TINYINT(1) DEFAULT 2,
ADD COLUMN other_service_scope TINYINT(1) DEFAULT 2,
ADD COLUMN tobacco_scope TINYINT(1) DEFAULT 2,
ADD COLUMN mining_activity_scope TINYINT(1) DEFAULT 2,
ADD COLUMN sez_developer_scope TINYINT(1) DEFAULT 2,
ADD COLUMN sez_investor_scope TINYINT(1) DEFAULT 2,
ADD COLUMN sector_id INT,
ADD COLUMN main_category_id INT,
ADD COLUMN sub_category_id INT;

-- We already have business_sectors and business_categories. 
-- Let's ensure we have a hierarchical structure if needed, or just separate tables.
-- The user mentioned "Main Category" and "Industry Sub Category".

CREATE TABLE IF NOT EXISTS moic_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS moic_sub_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    main_category_id INT,
    sub_category_name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (main_category_id) REFERENCES moic_categories(id) ON DELETE CASCADE
);

-- Seed some data for testing
INSERT IGNORE INTO moic_categories (category_name) VALUES ('Industrial'), ('Agriculture'), ('Services'), ('Trading');
INSERT IGNORE INTO moic_sub_categories (main_category_id, sub_category_name) VALUES 
(1, 'Food Processing'), (1, 'Textiles'), (2, 'Rice Farming'), (3, 'Hospitality');
