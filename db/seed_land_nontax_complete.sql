-- Complete Migration for Land Concession and Natural Resource Benchmarks

-- Article 12 (Mining) - More items
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_search, rate_survey, rate_analysis, rate_zone1, unit) VALUES
('Article 12', 'State land concession rates for mining activities', '10', 'Energy minerals group: Peat', 0.50, 1.00, 2.00, 30.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '10', 'Energy minerals group: Anthracite', 1.00, 1.00, 2.00, 70.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '10', 'Energy minerals group: Lignite', 1.00, 1.00, 2.00, 70.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '11', 'Ground water, mineral water, natural thermal water', 0.50, 1.00, 3.00, 20.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '2', 'Precious metals: Gold, Platinum, Silver', 1.00, 2.00, 3.00, 100.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '3', 'Base Metals: Lead, Zinc', 1.00, 2.00, 3.00, 60.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '3', 'Base Metals: Copper', 1.00, 2.00, 3.00, 80.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '4', 'The group of Kou and Vuong PhamTin and Tungsten', 1.00, 2.00, 3.00, 100.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '5', 'Rare metal group: Antimony, Molybdenum, Bismuth, Mercury, Aluminum', 1.00, 2.00, 3.00, 60.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '6', 'Group of elements related to iron: Iron, Manganese, Pynte', 1.00, 2.00, 3.00, 70.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '6', 'Group of elements related to iron: Chrome, Nickel', 1.00, 2.00, 3.00, 80.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '7', 'Industrial minerals (Alunite, Asbestos, Barite, Latalite, Kaolin, Limestone, Martile)', 1.00, 2.00, 3.00, 50.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '8', 'Construction materials: limestone, andesite, granodiorite, basalt, granite', 1.00, 2.00, 3.00, 50.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '9', 'Evaporite Minerals: Potash', 0.50, 1.00, 3.00, 20.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '9', 'Evaporite Minerals: Halite', 0.50, 1.00, 3.00, 20.00, 'USD/ha/year'),
('Article 12', 'State land concession rates for mining activities', '9', 'Evaporite Minerals: Gypsum', 1.00, 2.00, 5.00, 30.00, 'USD/ha/year');

-- Article 6 (Industrial)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, unit) VALUES
('Article 6', 'Leasing of state land for industrial activities', '1', 'Pharmaceutical manufacturing plants, medical equipment manufacturing, hygiene products', 100.00, 200.00, 300.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '10', 'Manufacturing and assembling machinery, electrical equipment and appliances, radio, television, communication equipment', 200.00, 400.00, 600.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '11', 'Coal-fired power plant', 200.00, 400.00, 600.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '12', 'Non-metallic mineral processing plants, fuel, coal and primary metal processing plants, metal products and cement factories', 200.00, 400.00, 600.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '13', 'Wood resin processing plant, paper production, animal skins', 200.00, 400.00, 600.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '14', 'Recycling plant', 100.00, 200.00, 400.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '15', 'Chemical processing plants, chemical products, plastic products', 500.00, 800.00, 1000.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '2', 'Educational equipment factory, sports equipment, musical instruments, children''s toys', 100.00, 200.00, 400.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '3', 'Printing houses, distributors and printed media', 100.00, 200.00, 400.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '4', 'Construction machinery manufacturing plant', 100.00, 200.00, 400.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '5', 'Power plant, power distribution station', 100.00, 200.00, 400.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '6', 'Slaughterhouses, food processing, non-alcoholic beverages, agricultural products, agricultural equipment manufacturing, handicrafts', 100.00, 200.00, 300.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '7', 'Yarn, fiber, textile and garment manufacturing plants', 200.00, 300.00, 600.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '8', 'Manufacturing plant for household appliances, mechanical brain machines', 200.00, 300.00, 600.00, 'USD/ha/year'),
('Article 6', 'Leasing of state land for industrial activities', '9', 'Manufacturing machinery, transport vehicles and spare parts', 200.00, 300.00, 600.00, 'USD/ha/year');

-- Article 7 (Tourist)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, unit) VALUES
('Article 7', 'Rental rates for state land used for service activities in tourist areas', '01', 'Tourist area type 1', 70.00, 100.00, 200.00, 'USD/ha/year'),
('Article 7', 'Rental rates for state land used for service activities in tourist areas', '02', 'Tourist area type 2', 100.00, 200.00, 300.00, 'USD/ha/year'),
('Article 7', 'Rental rates for state land used for service activities in tourist areas', '03', 'Tourist area type 3', 200.00, 300.00, 400.00, 'USD/ha/year'),
('Article 7', 'Rental rates for state land used for service activities in tourist areas', '04', 'Tourist area type 4', 300.00, 400.00, 500.00, 'USD/ha/year');

-- Article 8 (Services & Housing)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, unit) VALUES
('Article 8', 'Rental rates for state land for the construction of services and housing', '1', 'Service law that is designed to satisfy benefits such as parks, schools, playgrounds, and rehabilitation centers', 100.00, 300.00, 500.00, 'USD/ha/year'),
('Article 8', 'Rental rates for state land for the construction of services and housing', '2', 'Build houses and buildings for living', 300.00, 500.00, 1000.00, 'USD/ha/year'),
('Article 8', 'Rental rates for state land for the construction of services and housing', '3', 'Personal service activities such as banks, grocery stores', 500.00, 10000.00, 50000.00, 'USD/ha/year'),
('Article 8', 'Rental rates for state land for the construction of services and housing', '4', 'Construction activities for housing projects, guest houses, cross-country skiing trails, hiking trails, conference centers, etc.', 1000.00, 5000.00, 10000.00, 'USD/ha/year'),
('Article 8', 'Rental rates for state land for the construction of services and housing', '5', 'Infrastructure development activities: telecommunications, markets, passenger car parking, freight terminals and warehousing systems', 1000.00, 5000.00, 10000.00, 'USD/ha/year'),
('Article 8', 'Rental rates for state land for the construction of services and housing', '6', 'Personal service activities such as hotels, restaurants, entertainment venues', 5000.00, 30000.00, 70000.00, 'USD/ha/year');

-- Article 9 (Sports)
INSERT INTO bm_land_concession (article_no, article_name, item_no, item_name, rate_zone1, rate_zone2, rate_zone3, unit) VALUES
('Article 9', 'Rental rate for state land for the construction of sports stadiums', '1', 'Create a general sports field activity', 50.00, 150.00, 250.00, 'USD/ha/year'),
('Article 9', 'Rental rate for state land for the construction of sports stadiums', '2', 'Create horse racing events, all types of racing', 100.00, 150.00, 200.00, 'USD/ha/year'),
('Article 9', 'Rental rate for state land for the construction of sports stadiums', '3', 'Create a small field activity', 150.00, 250.00, 450.00, 'USD/ha/year');

-- Natural Resource Compensation (Mineral resources)
INSERT INTO bm_natural_resource (item_no, item_name, rate_percentage) VALUES
('11', 'Stone', 3.00),
('2', 'Semi-precious stones - agate, alexandrite - Aerolite, Hordeolite, Pyrite, Beryl, Spinel, Topaz, Kitzorite, Opal, Tourmaline, Agate, Garnet: Quartz, Amethyst', 7.00),
('3', 'Precious metals: silver, gold, platinum', 7.00),
('4', 'Base metals (non-ferrous metals) - copper, lead, tin, zinc, aluminum', 6.00),
('5', 'Steel and steel alloys: steel, titanium, manganese, chromium, vanadium, nickel, cobalt, molybdenum, stainless steel', 6.00),
('6', 'Metals that have a metal oxide and interact with nonmetals Antimony, arsenic, beryllium, cadmium, bosmuth, magnesium, mercury, hydride and selenium, cosulphur, titanium, zirconium.', 7.00),
('7', 'Ephedra - gypsum, anhydride, potash salt, magnesium salt', 4.00),
('7', 'Salt to eat', 2.00),
('8', 'Industrial minerals: fluoride, barite, phosponte, mica', 4.00),
('9', 'Land-based industrial resources - limestone, dolomite, magnesia, laterite, clay, tuff, asbestos, glass sand, tin, alunite, ore, feldspar, diorite, feldspar, hyocite, slate', 4.00);
