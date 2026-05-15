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
-- Table structure for table `district`
--

CREATE TABLE `district` (
  `dis_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `pro_id` varchar(10) NOT NULL,
  `dis_name` varchar(255) NOT NULL,
  `dis_name_lao` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `district`
--

INSERT INTO `district` (`dis_id`, `pro_id`, `dis_name`, `dis_name_lao`) VALUES
('0101', '01', 'CHANTHABULY', 'ຈັນທະບູລີ'),
('0102', '01', 'SIKHOTTABONG', 'ສີໂຄດຕະບອງ'),
('0103', '01', 'XAYSETTHA', 'ໄຊເສດຖາ'),
('0104', '01', 'SISATTANAK', 'ສີສັດຕະນາກ'),
('0105', '01', 'NAXAYTHONG', 'ນາຊາຍທອງ'),
('0106', '01', 'XAYTHANY', 'ໄຊທານີ'),
('0107', '01', 'HATXAYFONG', 'ຫາດຊາຍຟອງ'),
('0108', '01', 'SANGTHONG', 'ສັງທອງ'),
('0109', '01', 'PAKNGEUM', 'ປາກງື່ມ'),
('0201', '02', 'PHONGSALY', 'ຜົ້ງສາລີ'),
('0202', '02', 'MAI', 'ໃໝ່'),
('0203', '02', 'KHOA', 'ຂວາ'),
('0204', '02', 'SAMPHAN', 'ສຳພັນ'),
('0205', '02', 'BOUN-NUA', 'ບຸນເໜືອ'),
('0206', '02', 'GNOT-OU', 'ຍອດອູ'),
('0207', '02', 'BOUN-TAI', 'ບຸນໃຕ້'),
('0301', '03', 'LUANG NAMTHA', 'ຫຼວງນໍ້າທາ'),
('0302', '03', 'SING', 'ສິງ'),
('0303', '03', 'LONG', 'ລອງ'),
('0304', '03', 'VIENGPHOUKHA', 'ວຽງພູຄາ'),
('0305', '03', 'NALAE', 'ນາແລ'),
('0401', '04', 'XAY', 'ໄຊ'),
('0402', '04', 'LA', 'ຫລາ'),
('0403', '04', 'NAMOR', 'ນາໝໍ້'),
('0404', '04', 'NGA', 'ງາ'),
('0405', '04', 'BENG', 'ແບງ'),
('0406', '04', 'HOUN', 'ຮຸນ'),
('0407', '04', 'PARKBENG', 'ປາກແບງ'),
('0501', '05', 'HOUAYXAY', 'ຫ້ວຍຊາຍ'),
('0502', '05', 'TONPHEUNG', 'ຕົ້ນເຜິ້ງ'),
('0503', '05', 'MEUNG', 'ເມິງ'),
('0504', '05', 'PHAOUDOM', 'ຜາອຸດົມ'),
('0505', '05', 'PARKTHA', 'ປາກທາ'),
('0601', '06', 'LUANGPRABANG', 'ນະຄອນຫຼວງພະບາງ'),
('0602', '06', 'XIENG NGEUN', 'ຊຽງເງິນ'),
('0603', '06', 'NAN', 'ນານ'),
('0604', '06', 'PAK-OU', 'ປາກອູ'),
('0605', '06', 'NAMBAK', 'ນໍ້າບາກ'),
('0606', '06', 'NGOY', 'ງອຍ'),
('0607', '06', 'PARKXENG', 'ປາກແຊງ'),
('0608', '06', 'PHONXAY', 'ໂພນໄຊ'),
('0609', '06', 'CHOMPHET', 'ຈອມເພັດ'),
('0610', '06', 'VIENGKHAM', 'ວຽງຄຳ'),
('0611', '06', 'PHOUKHOUN', 'ພູຄູນ'),
('0701', '07', 'XAM-NUA', 'ຊຳເໜືອ'),
('0702', '07', 'XIENGKHOR', 'ຊຽງຄໍ້'),
('0703', '07', 'HIEM', 'ຮ້ຽມ'),
('0704', '07', 'VIENGXAY', 'ວຽງ​ໄຊ'),
('0705', '07', 'HUAMEUANG', 'ຫົວເມືອງ'),
('0706', '07', 'XAMTAY', 'ຊຳໃຕ້'),
('0707', '07', 'SOPBAO', 'ສົບເບົາ'),
('0708', '07', 'ET', 'ແອດ'),
('0709', '07', 'KOUAN', 'ກວັນ'),
('0710', '07', 'XON', 'ຊ່ອນ'),
('0801', '08', 'XAYABOURY', 'ໄຊຍະບູລີ'),
('0802', '08', 'KHOP', 'ຄອບ'),
('0803', '08', 'HONGSA', 'ຫົງສາ'),
('0804', '08', 'NGEUNE', 'ເງິນ'),
('0805', '08', 'XIENGHONE', 'ຊຽງຮ່ອນ'),
('0806', '08', 'PHIENG', 'ພຽງ'),
('0807', '08', 'PAKLAY', 'ປາກລາຍ'),
('0808', '08', 'KENETHAO', 'ແກ່ນທ້າວ'),
('0809', '08', 'BOTENE', 'ບໍ່ແຕນ'),
('0810', '08', 'THONGMIXAY', 'ທົ່ງມີໄຊ'),
('0811', '08', 'XAYSATHAN', 'ໄຊສະຖານ'),
('0901', '09', 'PEK', 'ແປກ'),
('0902', '09', 'KHAM', 'ຄຳ'),
('0903', '09', 'NONGHAETH', 'ໜອງແຮດ'),
('0904', '09', 'KHOUNE', 'ຄູນ'),
('0905', '09', 'MOK', 'ໝອກ'),
('0906', '09', 'PHOUKOUTH', 'ພູກູດ'),
('0907', '09', 'PHAXAY', 'ຜາໄຊ'),
('1001', '10', 'PHONHONG', 'ໂພນໂຮງ'),
('1002', '10', 'THOULAKHOM', 'ທຸລະຄົມ'),
('1003', '10', 'KEO OUDOM', 'ແກ້ວອຸດົມ'),
('1004', '10', 'KASY', 'ກາສີ'),
('1005', '10', 'VANGVIENG', 'ວັງວຽງ'),
('1006', '10', 'FEUANG', 'ເຟືອງ'),
('1007', '10', 'XANAKHAME', 'ຊະນະຄາມ'),
('1008', '10', 'MAETH', 'ແມດ'),
('1009', '10', 'VIENGKHAM', 'ວຽງຄຳ'),
('1010', '10', 'HINHEUP', 'ຫີນເຫີບ'),
('1011', '10', 'MUEN', 'ໝື່ນ'),
('1101', '11', 'PAKXAN', 'ປາກຊັນ'),
('1102', '11', 'THAPHABATH', 'ທ່າພະບາດ'),
('1103', '11', 'PAKKADING', 'ປາກກະດິງ'),
('1104', '11', 'BOLIKHANH', 'ບໍລິຄັນ'),
('1105', '11', 'KHAMKEUTH', 'ຄຳເກີດ'),
('1106', '11', 'VIENGTHONG', 'ວຽງທອງ'),
('1107', '11', 'XAYCHAMPHONE', 'ໄຊຈຳພອນ'),
('1201', '12', 'THAKHEK', 'ທ່າແຂກ'),
('1202', '12', 'MAHAXAY', 'ມະຫາໄຊ'),
('1203', '12', 'NONGBOK', 'ໜອງບົກ'),
('1204', '12', 'HINBOUNE', 'ຫິນບູນ'),
('1205', '12', 'NHOMMALATH', 'ຍົມມະລາດ'),
('1206', '12', 'BOUALAPHA', 'ບົວລະພາ'),
('1207', '12', 'NAKAY', 'ນາກາຍ'),
('1208', '12', 'XEBANGFAY', 'ເຊບັ້ງໄຟ'),
('1209', '12', 'XAYBOUATHONG', 'ໄຊບົວທອງ'),
('1210', '12', 'KHOUNKHAM', 'ຄູນຄຳ'),
('1301', '13', 'NAKHONE KAYSONE PHOMVIHANE', 'ນະຄອນໄກສອນພົມວິຫານ'),
('1302', '13', 'OUTHOUMPHONE', 'ອຸທຸມພອນ'),
('1303', '13', 'ATSAPHANGTHONG', 'ອາດສະພັງທອງ'),
('1304', '13', 'PHINE', 'ພີນ'),
('1305', '13', 'SEPONH', 'ເຊໂປນ'),
('1306', '13', 'NONG', 'ນອງ'),
('1307', '13', 'THAPANGTHONG', 'ທ່າປາງທອງ'),
('1308', '13', 'SONGKHONE', 'ສອງຄອນ'),
('1309', '13', 'CHAMPHONE', 'ຈຳພອນ'),
('1310', '13', 'XONNABOULY', 'ຊົນນະບູລີ '),
('1311', '13', 'XAYBOULY', 'ໄຊບູລີ'),
('1312', '13', 'VILABOULY', 'ວິລະບູລີ'),
('1313', '13', 'ATSAPHONE', 'ອາດສະພອນ'),
('1314', '13', 'XAYPHOUTHONG', 'ໄຊພູທອງ'),
('1315', '13', 'PHALANXAY', 'ພະລານໄຊ'),
('1401', '14', 'SARAVANH', 'ສາລະວັນ'),
('1402', '14', 'TA OY', 'ຕະໂອ້ຍ'),
('1403', '14', 'TOUMLANE', 'ຕຸ້ມລານ'),
('1404', '14', 'LAKHONEPHENG', 'ລະຄອນເພັງ'),
('1405', '14', 'VAPY', 'ວາປີ'),
('1406', '14', 'KHONGXEDON', 'ຄົງເຊໂດນ'),
('1407', '14', 'LAO NGAM', 'ເລົ່າງາມ'),
('1408', '14', 'SAMOUAY', 'ສະໝ້ວຍ'),
('1501', '15', 'LAMAM', 'ລະມາມ'),
('1502', '15', 'KALUM', 'ກະລຶມ'),
('1503', '15', 'DAKCHEUNG', 'ດາກຈຶງ'),
('1504', '15', 'THATENG', 'ທ່າແຕງ'),
('1601', '16', 'NAKHONE PAKSE', 'ນະຄອນ ປາກເຊ'),
('1602', '16', 'SANASOMBOUNE', 'ຊະນະສົມບູນ'),
('1603', '16', 'BACHIENGCHALEUNSOUK', 'ບາຈຽງຈະເລີນສຸກ'),
('1604', '16', 'PAKSONG', 'ປາກຊ່ອງ'),
('1605', '16', 'PATHOUMPHONE', 'ປະທຸມພອນ'),
('1606', '16', 'PHONTHONG', 'ໂພນທອງ'),
('1607', '16', 'CHAMPASSAK', 'ຈຳປາສັກ'),
('1608', '16', 'SOUKHOUMA', 'ສຸຂຸມາ'),
('1609', '16', 'MOUNLAPAMOK', 'ມູນລະປະໂມກ'),
('1610', '16', 'KHONG', 'ໂຂງ'),
('1701', '17', 'XAYSETTHA', 'ໄຊເສດຖາ'),
('1702', '17', 'SAMAKKHIXAY', 'ສາມັກຄີໄຊ'),
('1703', '17', 'SANAMXAY', 'ສະໜາມໄຊ'),
('1704', '17', 'SANXAI', 'ຊານໄຊ'),
('1705', '17', 'PHOUVONG', 'ພູວົງ'),
('1801', '18', 'ANOUVONG', 'ອະນຸວົງ'),
('1802', '18', 'THATHOM', 'ທ່າໂທມ'),
('1803', '18', 'LONGCHENG', 'ລ້ອງແຈ້ງ'),
('1804', '18', 'HOM', 'ຮົ່ມ'),
('1805', '18', 'LONGXAN', 'ລ້ອງຊານ');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `district`
--
ALTER TABLE `district`
  ADD PRIMARY KEY (`dis_id`),
  ADD KEY `idx_district_dis_id` (`dis_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
