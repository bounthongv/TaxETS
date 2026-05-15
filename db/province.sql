-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 10.123.0.165:3306
-- Generation Time: May 15, 2026 at 03:58 AM
-- Server version: 8.4.7
-- PHP Version: 8.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `soukchay_sysdata`
--

-- --------------------------------------------------------

--
-- Table structure for table `province`
--

CREATE TABLE `province` (
  `pro_id` varchar(10) NOT NULL,
  `pro_name` varchar(255) NOT NULL,
  `pro_name_lao` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `province`
--

INSERT INTO `province` (`pro_id`, `pro_name`, `pro_name_lao`) VALUES
('01', 'VIENTIANE CAPITAL', 'ນະຄອນຫຼວງວຽງຈັນ'),
('02', 'PHONGSALY', 'ຜົ້ງສາລີ'),
('03', 'LOUANGNAMTHA', 'ຫຼວງນໍ້າທາ'),
('04', 'OUDOMXAY', 'ອຸດົມໄຊ'),
('05', 'BOKEO', 'ບໍ່ແກ້ວ'),
('06', 'LOUANGPRABANG', 'ຫຼວງພະບາງ'),
('07', 'HUAPHANH', 'ຫົວພັນ'),
('08', 'SAYABOURY', 'ໄຊຍະບູລີ'),
('09', 'XIENGKHOUANG', 'ຊຽງຂວາງ'),
('10', 'VIENTIANE PROVINCE', 'ວຽງຈັນ'),
('11', 'BORIKHAMXAY', 'ບໍລິຄຳໄຊ'),
('12', 'KHAMMOUANE', 'ຄຳມ່ວນ'),
('13', 'SAVANNAKHET', 'ສະຫວັນນະເຂດ'),
('14', 'SARAVANH', 'ສາລະວັນ'),
('15', 'XEKONG', 'ເຊກອງ'),
('16', 'CHAMPASSAK', 'ຈຳປາສັກ'),
('17', 'ATTAPEU', 'ອັດຕະປື'),
('18', 'XAISOMBOUN', 'ໄຊສົມບູນ');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `province`
--
ALTER TABLE `province`
  ADD PRIMARY KEY (`pro_id`),
  ADD KEY `idx_province_pro_id` (`pro_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
