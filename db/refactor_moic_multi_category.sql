USE tax_ets;

-- 1. Create mapping table for multiple categories
CREATE TABLE IF NOT EXISTS moic_enterprise_category_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enterprise_id INT NOT NULL,
    main_category_id INT NOT NULL,
    sub_category_id INT,
    FOREIGN KEY (enterprise_id) REFERENCES repo_moic(id) ON DELETE CASCADE,
    FOREIGN KEY (main_category_id) REFERENCES moic_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_category_id) REFERENCES moic_sub_categories(id) ON DELETE SET NULL
);

-- 2. Optional: Migration (if any existing data)
-- INSERT INTO moic_enterprise_category_map (enterprise_id, main_category_id, sub_category_id)
-- SELECT id, main_category_id, sub_category_id FROM repo_moic WHERE main_category_id IS NOT NULL;

-- 3. Clean up the old single-assignment columns
-- ALTER TABLE repo_moic DROP COLUMN main_category_id;
-- ALTER TABLE repo_moic DROP COLUMN sub_category_id;
