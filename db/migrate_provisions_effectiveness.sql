USE tax_ets;

-- Add effectiveness period to profit_provisions (Corporate Income Tax)
ALTER TABLE profit_provisions 
    ADD COLUMN IF NOT EXISTS start_year INT DEFAULT 2000,
    ADD COLUMN IF NOT EXISTS end_year INT DEFAULT 2099;

-- Add effectiveness period to individual_provisions (Personal Income Tax)  
ALTER TABLE individual_provisions 
    ADD COLUMN IF NOT EXISTS start_year INT DEFAULT 2000,
    ADD COLUMN IF NOT EXISTS end_year INT DEFAULT 2099;

SELECT 'Added effectiveness period to provisions tables' AS result;
