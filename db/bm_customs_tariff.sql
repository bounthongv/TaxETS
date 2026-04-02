-- Customs Benchmark Tariff Table
USE tax_ets;

CREATE TABLE IF NOT EXISTS `bm_customs_tariff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hs_code` VARCHAR(20),
    `description_en` TEXT,
    `description_lo` TEXT,
    `unit` VARCHAR(20),
    `rate_normal` VARCHAR(10),
    `rate_mfn` VARCHAR(10),
    `rate_atiga` VARCHAR(10),
    `rate_acfta` VARCHAR(10), -- Using 2019 or latest for 'decoration'
    `rate_akfta` VARCHAR(10),
    `rate_ajcep` VARCHAR(10),
    `rate_aanzfta` VARCHAR(10),
    `rate_aifta` VARCHAR(10),
    `rate_apta` VARCHAR(10),
    `rate_laoviet` VARCHAR(10),
    INDEX (`hs_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
