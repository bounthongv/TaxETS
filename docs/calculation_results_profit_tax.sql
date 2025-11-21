-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 21, 2025 at 04:28 AM
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
-- Table structure for table `calculation_results_profit_tax`
--

CREATE TABLE `calculation_results_profit_tax` (
  `id` int NOT NULL,
  `source_data_id` int NOT NULL,
  `tin` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `calculation_year` year NOT NULL,
  `system_net_profit` decimal(20,2) NOT NULL,
  `system_benchmark_tax` decimal(20,2) NOT NULL,
  `applied_te_provision_id` int DEFAULT NULL,
  `system_actual_tax_payable` decimal(20,2) NOT NULL,
  `system_pt_te` decimal(20,2) NOT NULL,
  `cross_check_difference` decimal(20,0) NOT NULL,
  `calculation_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calculation_results_profit_tax`
--
ALTER TABLE `calculation_results_profit_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `source_data_id` (`source_data_id`),
  ADD KEY `idx_applied_te_provision_id` (`applied_te_provision_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calculation_results_profit_tax`
--
ALTER TABLE `calculation_results_profit_tax`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `calculation_results_profit_tax`
--
ALTER TABLE `calculation_results_profit_tax`
  ADD CONSTRAINT `calculation_results_profit_tax_ibfk_1` FOREIGN KEY (`source_data_id`) REFERENCES `calculation_data_profit_tax` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applied_te_provision_id` FOREIGN KEY (`applied_te_provision_id`) REFERENCES `te_provisions` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
