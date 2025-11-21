-- Ensure you are using the correct database
USE `taxets`;

-- Alter the `calculation_results_profit_tax` table to modify `applied_te_provision_id`
-- and add a foreign key constraint.

-- Step 1: Drop existing foreign key constraint if it exists (optional, but good for re-runs)
-- This might be needed if the table was created with a FK on applied_te_provision_id as VARCHAR
-- ALTER TABLE `calculation_results_profit_tax`
-- DROP FOREIGN KEY `fk_applied_te_provision_id`; -- Replace with actual FK name if known

-- Step 2: Alter the column type to INT NULL
ALTER TABLE `calculation_results_profit_tax`
MODIFY COLUMN `applied_te_provision_id` INT NULL;

-- Step 3: Add the foreign key constraint
ALTER TABLE `calculation_results_profit_tax`
ADD CONSTRAINT `fk_applied_te_provision_id`
FOREIGN KEY (`applied_te_provision_id`) REFERENCES `te_provisions`(`id`) ON DELETE SET NULL;

-- Add an index for the new foreign key for performance
ALTER TABLE `calculation_results_profit_tax`
ADD INDEX `idx_applied_te_provision_id` (`applied_te_provision_id`);


ALTER TABLE `calculation_results_profit_tax`
ADD COLUMN `crosss_check_difference` DECIMAL(20,0) NOT NULL
AFTER `system_pt_te`;
