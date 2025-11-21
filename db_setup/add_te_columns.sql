-- SQL script to add TE#1 to TE#20 and TE#Other columns to calculation_data_profit_tax table
-- This will allow us to import individual TE columns for verification and calculation of applied_te_ids



-- Add the new TE columns (TE#1 to TE#20 and TE#Other)
ALTER TABLE `calculation_data_profit_tax` 
ADD COLUMN `te_1` VARCHAR(50) NULL,
ADD COLUMN `te_2` VARCHAR(50) NULL,
ADD COLUMN `te_3` VARCHAR(50) NULL,
ADD COLUMN `te_4` VARCHAR(50) NULL,
ADD COLUMN `te_5` VARCHAR(50) NULL,
ADD COLUMN `te_6` VARCHAR(50) NULL,
ADD COLUMN `te_7` VARCHAR(50) NULL,
ADD COLUMN `te_8` VARCHAR(50) NULL,
ADD COLUMN `te_9` VARCHAR(50) NULL,
ADD COLUMN `te_10` VARCHAR(50) NULL,
ADD COLUMN `te_11` VARCHAR(50) NULL,
ADD COLUMN `te_12` VARCHAR(50) NULL,
ADD COLUMN `te_13` VARCHAR(50) NULL,
ADD COLUMN `te_14` VARCHAR(50) NULL,
ADD COLUMN `te_15` VARCHAR(50) NULL,
ADD COLUMN `te_16` VARCHAR(50) NULL,
ADD COLUMN `te_17` VARCHAR(50) NULL,
ADD COLUMN `te_18` VARCHAR(50) NULL,
ADD COLUMN `te_19` VARCHAR(50) NULL,
ADD COLUMN `te_20` VARCHAR(50) NULL,
ADD COLUMN `te_other` VARCHAR(50) NULL;

