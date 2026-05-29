-- Update Land Concession Provisions to include legacy ID mapping if needed
-- (Assuming we want to keep legacy IDs for precision as per the screenshot)

-- Drop and Recreate Natural Resource Provisions with Legacy IDs
DROP TABLE IF EXISTS natural_resource_provisions;
CREATE TABLE natural_resource_provisions (
    id INT PRIMARY KEY, -- Using legacy ID for precision
    provision_code VARCHAR(50) NOT NULL,
    provision_name VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    exemption_years SMALLINT NOT NULL DEFAULT 0,
    reduction_percentage DECIMAL(5,2) DEFAULT 0,
    period_time INT DEFAULT 0 COMMENT 'Legacy Period Time',
    description TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed with exact legacy data from Repository.html / legay-repo-non-tax.png
INSERT INTO natural_resource_provisions (id, provision_code, provision_name, category, exemption_years, period_time, description, active) VALUES
(134, '70', '6.1 Royalty Fees', 'Royalty Fee', 0, 0, '6.1 Royalty Fees', TRUE),
(135, '71', '6.2 Rental of State Land', 'Resource Fee', 0, 0, '6.2 Rental of State Land', TRUE),
(141, '95', 'Other for Non-tax', 'Other', 0, 0, 'Other for Non-tax', TRUE),
(10, '10_1', 'Exemption--IPL Art. 15', 'Other', 10, 120, 'Investors investing in sectors as specified in Art. 9... Zone 1: exemption for ten years; for investment in sectors as specified in points 2, 3, 5 and 6 of Art. 9 of the Law on Investment Promotion will receive additional five years of such exemption;', TRUE),
(128, '10_2', 'Exemption--IPL Art. 15', 'Other', 3, 36, 'Investors investing in sectors as specified in Art. 9... Zone 1: additional five years for specific sectors;', TRUE),
(11, '11_1', 'Exemption--IPL Art. 15', 'Other', 5, 60, 'Investors investing in sectors as specified in Art. 9... Zone 2: receive exemption for five years; for investment in sectors as specified in points 2, 3, 5 and 6 of Article 9 herein will receive additional three years of such exemption;', TRUE),
(129, '11_2', 'Exemption--IPL Art. 15', 'Other', 3, 36, 'Investors investing in sectors as specified in Art. 9... Zone 2: additional three years for specific sectors;', TRUE);
