-- Concession Milestones Table
USE tax_ets;

CREATE TABLE IF NOT EXISTS `concession_milestones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tin` VARCHAR(50) NOT NULL,
    `company_name` VARCHAR(255),
    `project_name` VARCHAR(255),
    `milestone_type` ENUM('MOU', 'Prospecting', 'Survey', 'Feasibility', 'Construction', 'Operation', 'Other') NOT NULL,
    `start_date` DATE,
    `end_date` DATE NOT NULL, -- The deadline
    `remind_days` INT DEFAULT 30, -- How many days before end_date to start notifying
    `responsible_person` VARCHAR(100),
    `contact_email` VARCHAR(255),
    `last_notified_at` DATETIME,
    `status` ENUM('Active', 'Completed', 'Extended', 'Terminated') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed Data for Concession Milestones
INSERT INTO `concession_milestones` (`tin`, `company_name`, `project_name`, `milestone_type`, `start_date`, `end_date`, `remind_days`, `responsible_person`, `contact_email`) VALUES
('763723844-000', 'Lao Mining Co.', 'Sepon Extension', 'MOU', '2026-01-01', '2026-07-01', 30, 'Mr. Somchai', 'somchai@laomining.la'),
('602733805-900', 'Green Power Ltd', 'Nam Theun 3', 'Survey', '2025-12-15', '2026-06-15', 15, 'Ms. Toukta', 'toukta@greenpower.com'),
('452188923-000', 'Agri-Tech Lao', 'Savannakhet Plantation', 'Feasibility', '2026-03-01', '2026-09-01', 30, 'Mr. Kham', 'kham@agritech.la');
