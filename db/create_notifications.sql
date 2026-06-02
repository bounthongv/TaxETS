-- Notification Table
USE tax_ets;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ref_id` VARCHAR(50), 
    `source` VARCHAR(100),
    `contents` TEXT,
    `notification_date` DATETIME,
    `emails` VARCHAR(255),
    `phones` VARCHAR(255),
    `status` ENUM('Sent', 'Unsent', 'Failed') DEFAULT 'Unsent',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data
INSERT INTO `notifications` (`ref_id`, `source`, `contents`, `notification_date`, `emails`, `phones`, `status`) VALUES
('763723844-000', 'MOF Lao', 'TIN: 763723844-000 -- Rule: 1000 -- Value Profit: 5,000,000 -- Rate Relief : 20.00 % -- Incentives Period Time: 5 -- Tax Standard PT rate ! -- Profit TaxAmountBenchmark: 1,000,000', '2026-06-01 09:00:00', 'info@company1.la', '020 1234567', 'Unsent'),
('602733805-900', 'MOF Lao', 'TIN: 602733805-900 -- Rule: 1000 -- Value Profit: 12,500,000 -- Rate Relief : 10.00 % -- Incentives Period Time: 3 -- Tax Standard PT rate ! -- Profit TaxAmountBenchmark: 1,250,000', '2026-06-01 10:30:00', 'contact@enterprise2.com', '020 9876543', 'Unsent'),
('452188923-000', 'ASYCUDA', 'Import HS: 0101.21.00 -- Declaration No: 4452 -- Actual Duty Paid: 0 -- Benchmark Duty: 50,000,000 -- Potential TE: 50,000,000', '2026-05-28 14:15:00', 'importer@logistics.la', '', 'Sent');
