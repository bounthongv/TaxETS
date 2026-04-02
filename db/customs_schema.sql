-- Customs Duty Module Schema
USE tax_ets;

-- 1. BENCHMARK RATES (MFN rates by AHTN chapter from Customs Tariff Schedule of Lao PDR 2017)
CREATE TABLE IF NOT EXISTS `bm_customs_duty` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `start_year` INT NOT NULL,
    `end_year` INT NOT NULL,
    `ahtn_chapter` VARCHAR(10) NOT NULL,
    `ahtn_description` VARCHAR(255),
    `rate_percentage` DECIMAL(5, 2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed benchmark rates from 4a-benchmark-custom-duty.pdf
DELETE FROM bm_customs_duty;

-- 0% MFN chapters
INSERT INTO `bm_customs_duty` (`start_year`, `end_year`, `ahtn_chapter`, `ahtn_description`, `rate_percentage`) VALUES
(2017, 2099, '25', 'Salt, sulphur, earths, stone, plastering materials, lime and cement', 0.00),
(2017, 2099, '26', 'Ores, slag and ash', 0.00),
(2017, 2099, '88', 'Aircraft, spacecraft, and parts thereof', 0.00),
(2017, 2099, '89', 'Ships, boats and floating structures', 0.00);

-- 5% MFN chapters
INSERT INTO `bm_customs_duty` (`start_year`, `end_year`, `ahtn_chapter`, `ahtn_description`, `rate_percentage`) VALUES
(2017, 2099, '01', 'Live animals', 5.00),
(2017, 2099, '02', 'Meat and edible meat offal', 5.00),
(2017, 2099, '03', 'Fish, crustaceans, molluscs and other aquatic invertebrates', 5.00),
(2017, 2099, '04', 'Dairy produce, birds\' eggs, natural honey, edible products of animal origin', 5.00),
(2017, 2099, '05', 'Products of animal origin, not elsewhere specified', 5.00),
(2017, 2099, '06', 'Live trees and other plants, bulbs, roots and the like, cut flowers and ornamental foliage', 5.00),
(2017, 2099, '07', 'Edible vegetables and certain roots and tubers', 5.00),
(2017, 2099, '08', 'Edible fruit and nuts, peel of citrus fruit or melons', 5.00),
(2017, 2099, '09', 'Coffee, tea, maté and spices', 5.00),
(2017, 2099, '10', 'Cereals', 5.00),
(2017, 2099, '11', 'Products of the milling industry, malt, starches, inulin, wheat gluten', 5.00),
(2017, 2099, '12', 'Oil seeds and oleaginous fruits, miscellaneous grains, seeds and fruit, industrial or medicinal plants, straw and fodder', 5.00),
(2017, 2099, '13', 'Lac, gums, resins and other vegetable saps and extracts', 5.00),
(2017, 2099, '15', 'Animal or vegetable fats and oils and their cleavage products, prepared edible fats, animal or vegetable waxes', 5.00),
(2017, 2099, '16', 'Preparations of meat, of fish, of crustaceans, molluscs or other aquatic invertebrates, or of insects', 5.00),
(2017, 2099, '17', 'Sugars and sugar confectionery', 5.00),
(2017, 2099, '18', 'Cocoa and cocoa preparations', 5.00),
(2017, 2099, '19', 'Preparations of cereals, flour, starch or milk, pastrycooks\' products', 5.00),
(2017, 2099, '20', 'Preparations of vegetables, fruit, nuts or other parts of plants', 5.00),
(2017, 2099, '21', 'Miscellaneous edible preparations', 5.00),
(2017, 2099, '23', 'Residues and waste from the food industries, prepared animal fodder', 5.00),
(2017, 2099, '42', 'Articles of leather, saddlery and harness, travel goods, handbags and similar containers', 5.00),
(2017, 2099, '43', 'Furskins and artificial fur, manufactures thereof', 5.00),
(2017, 2099, '44', 'Wood and articles of wood, wood charcoal', 5.00),
(2017, 2099, '45', 'Cork and articles of cork', 5.00),
(2017, 2099, '46', 'Manufactures of straw, of esparto or of other plaiting materials, basketware and wickerwork', 5.00),
(2017, 2099, '48', 'Paper and paperboard, articles of paper pulp, of paper or of paperboard', 5.00),
(2017, 2099, '49', 'Printed books, newspapers, pictures and other products of the printing industry, manuscripts, typescripts and plans', 5.00),
(2017, 2099, '50', 'Silk', 5.00),
(2017, 2099, '51', 'Wool, fine or coarse animal hair, horsehair yarn and woven fabric', 5.00),
(2017, 2099, '52', 'Cotton', 5.00),
(2017, 2099, '53', 'Other vegetable textile fibres, paper yarn and woven fabrics of paper yarn', 5.00),
(2017, 2099, '54', 'Man-made filaments, strip and the like of man-made textile materials', 5.00),
(2017, 2099, '55', 'Man-made staple fibres', 5.00),
(2017, 2099, '56', 'Wadding, felt and nonwovens, special yarns, twine, cordage, ropes and cables and articles thereof', 5.00),
(2017, 2099, '57', 'Carpets and other textile floor coverings', 5.00),
(2017, 2099, '58', 'Special woven fabrics, tufted textile fabrics, lace, tapestries, trimmings, embroidery', 5.00),
(2017, 2099, '59', 'Impregnated, coated, covered or laminated textile fabrics, textile articles of a kind suitable for industrial use', 5.00),
(2017, 2099, '60', 'Knitted or crocheted fabrics', 5.00),
(2017, 2099, '61', 'Articles of apparel and clothing accessories, knitted or crocheted', 5.00),
(2017, 2099, '62', 'Articles of apparel and clothing accessories, not knitted or crocheted', 5.00),
(2017, 2099, '63', 'Other made up textile articles, sets, worn clothing and worn textile articles, rags', 5.00),
(2017, 2099, '68', 'Articles of stone, plaster, cement, asbestos, mica or similar materials', 5.00),
(2017, 2099, '69', 'Ceramic products', 5.00),
(2017, 2099, '70', 'Glass and glassware', 5.00),
(2017, 2099, '73', 'Articles of iron or steel', 5.00),
(2017, 2099, '74', 'Copper and articles thereof', 5.00),
(2017, 2099, '75', 'Nickel and articles thereof', 5.00),
(2017, 2099, '76', 'Aluminium and articles thereof', 5.00),
(2017, 2099, '78', 'Lead and articles thereof', 5.00),
(2017, 2099, '79', 'Zinc and articles thereof', 5.00),
(2017, 2099, '80', 'Tin and articles thereof', 5.00),
(2017, 2099, '81', 'Other base metals, cermets, articles thereof', 5.00),
(2017, 2099, '82', 'Tools, implements, cutlery, spoons and forks, of base metal, parts thereof of base metal', 5.00),
(2017, 2099, '83', 'Miscellaneous articles of base metal', 5.00),
(2017, 2099, '91', 'Clocks and watches and parts thereof', 5.00),
(2017, 2099, '92', 'Musical instruments, parts and accessories of such articles', 5.00),
(2017, 2099, '94', 'Furniture, bedding, mattresses, mattress supports, cushions and similar stuffed furnishings', 5.00),
(2017, 2099, '95', 'Toys, games and sports requisites, parts and accessories thereof', 5.00),
(2017, 2099, '96', 'Miscellaneous manufactured articles', 5.00);

-- 10% MFN chapters
INSERT INTO `bm_customs_duty` (`start_year`, `end_year`, `ahtn_chapter`, `ahtn_description`, `rate_percentage`) VALUES
(2017, 2099, '14', 'Vegetable plaiting materials, vegetable products not elsewhere specified', 10.00),
(2017, 2099, '22', 'Beverages, spirits and vinegar', 10.00),
(2017, 2099, '33', 'Essential oils and resinoids, perfumery, cosmetic or toilet preparations', 10.00),
(2017, 2099, '34', 'Soap, organic surface-active agents, washing preparations, lubricating preparations, artificial waxes, polishes, candles and similar articles', 10.00),
(2017, 2099, '35', 'Albuminoidal substances, modified starches, glues, enzymes', 10.00),
(2017, 2099, '36', 'Explosives, pyrotechnic products, matches, pyrophoric alloys, certain combustible preparations', 10.00),
(2017, 2099, '37', 'Photographic or cinematographic goods', 10.00),
(2017, 2099, '38', 'Miscellaneous chemical products', 10.00),
(2017, 2099, '39', 'Plastics and articles thereof', 10.00),
(2017, 2099, '40', 'Rubber and articles thereof', 10.00),
(2017, 2099, '41', 'Raw hides and skins (other than furskins) and leather', 10.00),
(2017, 2099, '47', 'Pulp of wood or of other fibrous cellulosic material, recovered (waste and scrap) paper or paperboard', 10.00),
(2017, 2099, '64', 'Footwear, gaiters and the like, parts of such articles', 10.00),
(2017, 2099, '65', 'Headgear and parts thereof', 10.00),
(2017, 2099, '66', 'Umbrellas, sun umbrellas, walking sticks, seat-sticks, whips, riding-crops and parts thereof', 10.00),
(2017, 2099, '67', 'Prepared feathers and down and articles made of feathers or of down, artificial flowers, articles of human hair', 10.00),
(2017, 2099, '93', 'Arms and ammunition, parts and accessories thereof', 10.00);

-- 15% MFN chapters
INSERT INTO `bm_customs_duty` (`start_year`, `end_year`, `ahtn_chapter`, `ahtn_description`, `rate_percentage`) VALUES
(2017, 2099, '24', 'Tobacco and manufactured tobacco substitutes (general)', 15.00),
(2017, 2099, '30', 'Pharmaceutical products', 15.00),
(2017, 2099, '32', 'Tanning or dyeing extracts, tannins and their derivatives, dyes, pigments and other colouring matter', 15.00);

-- 20% MFN (specific tobacco sub-items)
INSERT INTO `bm_customs_duty` (`start_year`, `end_year`, `ahtn_chapter`, `ahtn_description`, `rate_percentage`) VALUES
(2017, 2099, '24.02', 'Cigars, cheroots, cigarillos and cigarettes, of tobacco or of tobacco substitutes (20%)', 20.00);

-- 40% MFN (specific tobacco sub-items)
INSERT INTO `bm_customs_duty` (`start_year`, `end_year`, `ahtn_chapter`, `ahtn_description`, `rate_percentage`) VALUES
(2017, 2099, '24.02.10', 'Cigars, cheroots and cigarillos, containing tobacco (40%)', 40.00),
(2017, 2099, '24.02.20', 'Cigarettes containing tobacco (40%)', 40.00);

-- 2. CUSTOMS PROVISIONS (9 categories from 4b-provision-custom-duty.pdf)
CREATE TABLE IF NOT EXISTS `customs_provisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provision_number` VARCHAR(10) NOT NULL,
    `start_year` INT DEFAULT 2020,
    `end_year` INT DEFAULT 2099,
    `legal_basis` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `purpose` VARCHAR(255),
    `type_of_te` ENUM('Exemption', 'Reduction') DEFAULT 'Exemption'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELETE FROM customs_provisions;

INSERT INTO `customs_provisions` (`provision_number`, `start_year`, `end_year`, `legal_basis`, `description`, `purpose`, `type_of_te`) VALUES
('1', 2016, 2099, 'IPL Art 10.1', 'Exemptions on import of goods for diplomatic and consular missions, international organizations, and technical assistance organizations of the United Nations, including vehicles with diplomatic plates', 'Diplomatic & consular missions', 'Exemption'),
('2', 2016, 2099, 'IPL Art 10.2', 'Exemptions on import of goods for development projects financed by foreign donors under bilateral or multilateral agreements with the Lao PDR government', 'Development projects (foreign donors)', 'Exemption'),
('3', 2016, 2099, 'Decision 247 Art 6', 'Exemptions and reductions on import of goods by State-Owned Enterprises (SOE) under Prime Ministerial Decision No. 247/PM, including investment projects related to electricity, mining, and other strategic sectors', 'State-Owned Enterprises', 'Exemption'),
('4', 2016, 2099, 'IPL Art 10,16,17,20,25,32,33,35-38', 'Reductions and exemptions on import of goods by investment entities under the Investment Promotion Law, including raw materials, equipment, machinery, and construction materials for promoted sectors and zones', 'Investment promotion (IPL)', 'Reduction'),
('5', 2020, 2099, 'IPL Art 13.1 / ATIGA', 'Exemptions on import of goods originating from ASEAN member states under the ASEAN Trade in Goods Agreement (ATIGA), with applicable tariff rates per the Common Effective Preferential Tariff (CEPT) scheme', 'ASEAN Trade in Goods Agreement', 'Exemption'),
('6', 2020, 2099, 'IPL Art 13.1 / ACFTA', 'Exemptions on import of goods originating from the People\'s Republic of China under the ASEAN-China Free Trade Area (ACFTA) agreement', 'ASEAN-China FTA', 'Exemption'),
('7', 2020, 2099, 'IPL Art 13.1 / AANZFTA', 'Exemptions on import of goods originating from Australia and New Zealand under the ASEAN-Australia-New Zealand Free Trade Area agreement', 'ASEAN-Australia-NZ FTA', 'Exemption'),
('8', 2020, 2099, 'IPL Art 13.1 / AJCEP', 'Exemptions on import of goods originating from Japan under the ASEAN-Japan Comprehensive Economic Partnership agreement', 'ASEAN-Japan CEPA', 'Exemption'),
('9', 2020, 2099, 'IPL Art 13.1 / APTA', 'Exemptions on import of goods under the Asia-Pacific Trade Agreement (APTA), formerly the Bangkok Agreement, covering concessions among developing member countries', 'Asia-Pacific Trade Agreement', 'Exemption');

-- 3. PROVISION CONDITIONS (Dynamic Rule Engine)
CREATE TABLE IF NOT EXISTS `customs_provision_conditions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provision_id` INT NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `operator` VARCHAR(20) NOT NULL,
    `value_1` VARCHAR(255),
    `value_2` VARCHAR(255),
    FOREIGN KEY (`provision_id`) REFERENCES `customs_provisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
