-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 11:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tax_ets`
--

-- --------------------------------------------------------

--
-- Table structure for table `bm_customs_tariff`
--

CREATE TABLE `bm_customs_tariff` (
  `id` int(11) NOT NULL,
  `hs_code` varchar(20) DEFAULT NULL,
  `sub_code` varchar(10) DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `description_lo` text DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `rate_normal` varchar(10) DEFAULT NULL,
  `rate_mfn` varchar(10) DEFAULT NULL,
  `rate_atiga` varchar(10) DEFAULT NULL,
  `rate_acfta` varchar(10) DEFAULT NULL,
  `rate_akfta` varchar(10) DEFAULT NULL,
  `rate_ajcep` varchar(10) DEFAULT NULL,
  `rate_aanzfta` varchar(10) DEFAULT NULL,
  `rate_aifta` varchar(10) DEFAULT NULL,
  `rate_apta` varchar(10) DEFAULT NULL,
  `rate_laoviet` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bm_customs_tariff`
--
ALTER TABLE `bm_customs_tariff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hs_code` (`hs_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bm_customs_tariff`
--
ALTER TABLE `bm_customs_tariff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
