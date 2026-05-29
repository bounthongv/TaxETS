-- Add Strict ID columns to companies table for dictionary mapping
ALTER TABLE companies
    ADD COLUMN pro_id VARCHAR(10) AFTER province,
    ADD COLUMN dis_id VARCHAR(50) AFTER district,
    ADD COLUMN sector_id INT AFTER sector;

-- Note: Foreign keys can be added later if desired, using SET NULL for safety
