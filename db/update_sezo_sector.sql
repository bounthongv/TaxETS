USE tax_ets;

-- 1. Business Sectors Table
CREATE TABLE IF NOT EXISTS business_sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sector_name VARCHAR(255) NOT NULL UNIQUE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Business Sectors
INSERT IGNORE INTO business_sectors (sector_name) VALUES 
('Industrial & Manufacturing'),
('Services & Tourism'),
('Trade & Commerce'),
('Agriculture & Processing'),
('Infrastructure & Construction');

-- 2. Update Business Categories with better seed data
INSERT IGNORE INTO business_categories (category_name) VALUES 
('Garment & Textiles'),
('Electronics & Components'),
('Food & Beverage Processing'),
('Logistics & Warehousing'),
('Banking & Financial Services'),
('Hotels & Resorts'),
('Import-Export Trading'),
('IT & Software Development'),
('Renewable Energy'),
('Real Estate Development');

-- 3. Update repo_sezo to include sector_id
-- Check if column exists first to avoid error if re-run
SET @dbname = DATABASE();
SET @tablename = "repo_sezo";
SET @columnname = "sector_id";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE repo_sezo ADD COLUMN sector_id INT AFTER district_id, ADD CONSTRAINT fk_sezo_sector FOREIGN KEY (sector_id) REFERENCES business_sectors(id) ON DELETE SET NULL"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
