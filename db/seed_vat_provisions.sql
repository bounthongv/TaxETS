USE tax_ets;

-- Clear existing data if necessary to prevent duplicates
DELETE FROM vat_provisions;
ALTER TABLE vat_provisions AUTO_INCREMENT = 1;

-- Seed VAT Provisions into the Repository Database
INSERT INTO vat_provisions (provision_number, start_year, end_year, legal_basis, type_of_te, description, purpose) VALUES
(31, 2020, 2099, 'VAT Law Art. 12.1.1', 'Exemption', 'Import of: all types of crop seeds, animals for breeding, animal sperms, vaccines, equipment and liquid nitrogen for storing vaccines and animal sperms, animal feeds, raw materials to produce animal feeds and vaccines', 'Food security'),
(32, 2020, 2099, 'VAT Law Art. 12.1.2', 'Exemption', 'Import of: Raw materials used in the production of fertilizers, agro-processing products, organic fertilizers, [scientific] fertilizers [and] pesticides that are not dangerous to the ecosystem, human and animal health and life', 'Food security'),
(33, 2020, 2099, 'VAT Law Art. 12.1.3', 'Exemption', 'Import of: Equipment and machinery used in agriculture', 'Food security'),
(34, 2020, 2099, 'VAT Law Art. 12.1.5; IPL Art. 12', 'Exemption', 'Import of: Materials [and] equipment that could not be supplied or produced in Lao PDR and machinery that are used as fixed assets and used directly in production', 'Investment Promotion / Export Promotion'),
(35, 2020, 2099, 'VAT Law Art. 12.1.13', 'Exemption', 'Import of: Animal medicines, artificial organs for transplantation for animal bodies', 'Food security'),
(36, 2020, 2099, 'VAT Law Art. 12.1.14', 'Exemption', 'Import of: Traditional medicines, artificial organs for transplantation for human bodies, human blood and supporting equipment for patients, the disabled and elderly', 'Social - Health'),
(37, 2020, 2099, 'VAT Law Art. 12.1.15', 'Exemption', 'Import of: Medical tools [and] equipment, [laboratory] equipments or wheelchairs for public services of hospitals, health care centers', 'Social - Health'),
(38, 2020, 2099, 'VAT Law Art. 12.1.16', 'Exemption', 'Import of: Vehicles serving professional activities, contributing to public benefits such as fire trucks, ambulances, vehicles equipped with repair facilities, outside television and radio broadcast vehicles and other professional vehicles', 'Not available on the local market'),
(39, 2020, 2099, 'VAT Law Art. 12.2.1', 'Exemption', 'Domestic supply of: Unprocessed agricultural or preliminary processed products including peeled, grinded [and] milled', 'Food security'),
(40, 2020, 2099, 'VAT Law Art. 12.2.2', 'Exemption', 'Domestic supply of: All kinds of live or deceased animals, whole or in parts, that are unprocessed or preliminary processed to ensure freshness or non-perishability', 'Food security'),
(41, 2020, 2099, 'VAT Law Art. 12.2.3', 'Exemption', 'Domestic supply of: afforestation, plantation of industrial, fruit and medicinal trees', 'Food security'),
(42, 2020, 2099, 'VAT Law Art. 12.2.4', 'Exemption', 'Domestic supply of: all types of crop seeds, animals for breeding, animal feeds, vaccines, raw materials to produce animal feeds and vaccines', 'Food security'),
(43, 2020, 2099, 'VAT Law Art. 12.2.5', 'Exemption', 'Domestic supply of: Raw materials used in the production of fertilizers, agroprocessing products, organic fertilizers, [scientific] fertilizers and pesticides that are not dangerous to the ecosystem, human and animal health and life', 'Food security'),
(44, 2020, 2099, 'VAT Law Art. 12.2.7', 'Exemption', 'Domestic supply of: equipment and machinery used in agricultural activities', 'Food security'),
(45, 2020, 2099, 'VAT Law Art. 12.2.11', 'Exemption', 'Domestic supply of: textbooks and learning materials', 'Social - Education'),
(46, 2020, 2099, 'VAT Law Art. 12.2.13', 'Exemption', 'The supply of education services by e.g. childcare centres, kindergartens, primary and secondary schools, vocational schools, colleges, academies, universities, sporting and athletic schools', 'Social - Education')
;
