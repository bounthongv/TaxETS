-- AHTN 2017 Hierarchical Schema
USE tax_ets;

-- 1. SECTIONS (e.g. SECTION I: LIVE ANIMALS)
CREATE TABLE IF NOT EXISTS `bm_customs_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_code` VARCHAR(20) NOT NULL, -- I, II, III...
    `name_lo` TEXT,
    `name_en` TEXT,
    `notes` TEXT,
    `order_idx` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CHAPTERS (e.g. Chapter 1: Live animals)
CREATE TABLE IF NOT EXISTS `bm_customs_chapters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_id` INT NOT NULL,
    `chapter_code` VARCHAR(20) NOT NULL, -- 1, 2, 3...
    `name_lo` TEXT,
    `name_en` TEXT,
    `notes` TEXT,
    `order_idx` INT DEFAULT 0,
    FOREIGN KEY (`section_id`) REFERENCES `bm_customs_sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ENHANCED TARIFF TABLE
-- We keep the existing columns but add hierarchy links and header flag
ALTER TABLE `bm_customs_tariff` 
ADD COLUMN `chapter_id` INT DEFAULT NULL,
ADD COLUMN `is_header` TINYINT(1) DEFAULT 0,
ADD COLUMN `level` INT DEFAULT 0, -- Nesting level (number of dashes)
ADD COLUMN `row_idx` INT DEFAULT NULL; -- To maintain original Excel order

-- Ensure foreign key for chapter
-- (Running as separate check if table already has data, but for now we just link by ID)
