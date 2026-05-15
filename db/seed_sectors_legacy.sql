USE tax_ets;

-- Expand business_sectors with legacy MPI sectors
-- First, ensure the table is clean or just insert missing ones
-- I'll truncate and re-seed to have a clean, numbered list that matches legacy IDs if possible, 
-- but since repo_sezo already uses IDs, I'll just use INSERT IGNORE.

INSERT IGNORE INTO business_sectors (id, sector_name) VALUES 
(2, 'Agriculture'),
(3, 'Construction'),
(4, 'Commerce'),
(5, 'Finance / Insurance'),
(6, 'Consultancy'),
(7, 'Education'),
(8, 'Energy'),
(9, 'Hotel and Restaurant'),
(10, 'Industry and Handicraft'),
(11, 'Mining'),
(12, 'Public health'),
(13, 'Service'),
(14, 'Manufacturing'),
(15, 'Electricity, gas, steam and air conditioning supply'),
(16, 'Water supply; sewerage, waste management and remediation activities'),
(17, 'Real estate activities'),
(18, 'Professional, scientific and technical activities'),
(19, 'Public administration and defence; compulsory social security'),
(20, 'Arts, entertainment and recreation'),
(21, 'Activities of households'),
(22, 'Activities of extraterritorial organizations and bodies'),
(23, 'Banking');
