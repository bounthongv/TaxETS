-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 21, 2025 at 04:29 AM
-- Server version: 8.0.44-0ubuntu0.24.04.1
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `taxets`
--

-- --------------------------------------------------------

--
-- Table structure for table `calculation_data_profit_tax`
--

CREATE TABLE `calculation_data_profit_tax` (
  `id` int NOT NULL,
  `tin` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation_year` year NOT NULL,
  `revenue` decimal(20,2) DEFAULT NULL,
  `expense` decimal(20,2) DEFAULT NULL,
  `pt_paid` decimal(20,2) DEFAULT NULL,
  `reinvested_profit_amount` decimal(20,2) DEFAULT NULL,
  `reinvest_date` date DEFAULT NULL,
  `province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sector` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zone` int DEFAULT NULL,
  `is_vat_holder` tinyint(1) DEFAULT NULL,
  `staff_count` int DEFAULT NULL,
  `total_assets_billion` decimal(10,4) DEFAULT NULL,
  `annual_turnover_billion` decimal(10,4) DEFAULT NULL,
  `investment_license_date` date DEFAULT NULL,
  `date_first_revenue` date DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `stock_listing_date` date DEFAULT NULL,
  `tax_holiday_period_years` int DEFAULT NULL,
  `is_human_resource_dev` tinyint(1) DEFAULT '0',
  `is_innovative_green_tech` tinyint(1) DEFAULT '0',
  `is_sez_developer` tinyint(1) DEFAULT '0',
  `is_sez_investor` tinyint(1) DEFAULT '0',
  `is_in_sez_specified_activity` tinyint(1) DEFAULT '0',
  `is_public_benefit_income` tinyint(1) DEFAULT '0',
  `is_asset_rent_compliant` tinyint(1) DEFAULT '0',
  `is_real_estate_transfer` tinyint(1) DEFAULT '0',
  `ipl_activity_flags` json DEFAULT NULL,
  `applied_te_ids_from_import` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'List of TE IDs from colums AT-BN of the mport file',
  `te_1` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_3` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_4` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_5` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_6` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_7` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_8` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_9` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_10` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_11` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_12` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_13` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_14` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_15` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_16` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_17` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_18` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_19` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_20` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `te_other` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calculation_data_profit_tax`
--
ALTER TABLE `calculation_data_profit_tax`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tin_year` (`tin`,`calculation_year`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calculation_data_profit_tax`
--
ALTER TABLE `calculation_data_profit_tax`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
