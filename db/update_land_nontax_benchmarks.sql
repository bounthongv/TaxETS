-- Update Land Concession Benchmark Table
DROP TABLE IF EXISTS bm_land_concession;
CREATE TABLE bm_land_concession (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_no VARCHAR(50),
    article_name VARCHAR(255),
    item_no VARCHAR(50),
    item_name TEXT,
    rate_zone1 DECIMAL(15,2) DEFAULT 0,
    rate_zone2 DECIMAL(15,2) DEFAULT 0,
    rate_zone3 DECIMAL(15,2) DEFAULT 0,
    rate_search DECIMAL(15,2) DEFAULT 0,
    rate_survey DECIMAL(15,2) DEFAULT 0,
    rate_analysis DECIMAL(15,2) DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'USD/ha/year',
    start_year SMALLINT NOT NULL DEFAULT 2025,
    end_year SMALLINT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Article 10 (Agricultural)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, unit) VALUES
('Article 10', 'Rates for concession of state land for agricultural activities', '1', 'Crop and food crop cultivation activities', 5.00, 10.00, 15.00, 'USD/ha/year'),
('Article 10', 'Rates for concession of state land for agricultural activities', '2', 'Large animal husbandry activities such as cattle, buffalo, goats, sheep, etc.', 5.00, 10.00, 20.00, 'USD/ha/year'),
('Article 10', 'Rates for concession of state land for agricultural activities', '3', 'Fruit tree and tree planting activities', 5.00, 10.00, 20.00, 'USD/ha/year'),
('Article 10', 'Rates for concession of state land for agricultural activities', '4', 'Economic crop cultivation activities', 6.00, 10.00, 20.00, 'USD/ha/year'),
('Article 10', 'Rates for concession of state land for agricultural activities', '5', 'Activities of cultivating non-timber forest products and medicinal plants', 7.00, 15.00, 25.00, 'USD/ha/year');

-- Seed Article 11 (Tree plantation)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, unit) VALUES
('Article 11', 'Rates of state land acquisition for tree plantation activities', '1', 'Economic tree planting activities for 10 years or more', 8.00, 16.00, 25.00, 'USD/ha/year'),
('Article 11', 'Rates of state land acquisition for tree plantation activities', '2', 'Tree planting activities have been growing for the past 10 years', 10.00, 20.00, 30.00, 'USD/ha/year'),
('Article 11', 'Rates of state land acquisition for tree plantation activities', '3', 'Rubber plantation activities', 30.00, 40.00, 50.00, 'USD/ha/year');

-- Seed Article 12 (Mining)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_search, rate_survey, rate_analysis, rate_zone1, unit) VALUES
('Article 12', 'State land concession rates for mining activities', '1', 'Gemstones and Semi-Precious Stones: Opal, Agate, Amethyst', 1.00, 1.00, 2.00, 300.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '2', 'Spinal, Garnet, Topaz', 1.00, 1.00, 2.00, 500.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '3', 'Diamond, Ruby, Sapphire, Emerald, Jade', 2.00, 2.00, 3.00, 700.00, 'USD/ha/year');

-- Create Natural Resource Benchmark Table
CREATE TABLE IF NOT EXISTS bm_natural_resource (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_no VARCHAR(50),
    item_name TEXT,
    rate_percentage DECIMAL(15,2) DEFAULT 0,
    start_year SMALLINT NOT NULL DEFAULT 2025,
    end_year SMALLINT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Natural Resource
INSERT INTO bm_natural_resource (item_no, item_name, rate_percentage) VALUES
('1', 'Gemstones and gemstones - diamonds, rubies, sapphires, emeralds, - gemstones', 10.00),
('2', 'Ornamental stone Quartz, marble, sapphire, slate, jasper', 5.00),
('3', 'Coal - lignite, sub-bihumite, pyhumite, anthracite', 6.00),
('4', 'Charcoal', 2.00);
