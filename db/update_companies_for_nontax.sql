-- Add detailed land concession columns to companies table
ALTER TABLE companies ADD COLUMN IF NOT EXISTS land_concession_article VARCHAR(50) AFTER land_area_sqm;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS land_concession_item VARCHAR(50) AFTER land_concession_article;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS land_concession_zone INT DEFAULT 1 AFTER land_concession_item;

-- Update Natural Resource related columns if needed
ALTER TABLE companies ADD COLUMN IF NOT EXISTS resource_extraction_item VARCHAR(50) AFTER land_concession_zone;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS sales_value_kip DECIMAL(15,2) DEFAULT 0 AFTER resource_extraction_item;
