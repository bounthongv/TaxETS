-- =============================================================
-- Tax-ETS Complete Database Schema
-- Generated: 2026-07-23 15:29 from local XAMPP MariaDB
-- 71 tables. Use this for fresh installs.
-- NOTE: Individual module files (asycuda_schema.sql, etc.)
--       are outdated. This file is the source of truth.
-- =============================================================

CREATE DATABASE IF NOT EXISTS tax_ets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tax_ets;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alert_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alert_type` varchar(50) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asycuda_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `import_date` datetime DEFAULT current_timestamp(),
  `province` varchar(100) DEFAULT NULL,
  `pro_id` varchar(10) DEFAULT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `no_seq` varchar(50) DEFAULT NULL,
  `border_code` varchar(20) DEFAULT NULL,
  `border_name` varchar(100) DEFAULT NULL,
  `type_customs` varchar(20) DEFAULT NULL,
  `process_type` varchar(20) DEFAULT NULL,
  `regime_f` varchar(20) DEFAULT NULL,
  `special_role` varchar(50) DEFAULT NULL,
  `regime_code` varchar(20) DEFAULT NULL,
  `doc_number` varchar(50) DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `assess_number` varchar(50) DEFAULT NULL,
  `assess_date` date DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `importer_name` text DEFAULT NULL,
  `declarant_tin` varchar(50) DEFAULT NULL,
  `declarant_name` text DEFAULT NULL,
  `export_country` varchar(10) DEFAULT NULL,
  `dest_country` varchar(10) DEFAULT NULL,
  `origin_country` varchar(10) DEFAULT NULL,
  `list_no` varchar(20) DEFAULT NULL,
  `hs_code` varchar(20) DEFAULT NULL,
  `goods_description` text DEFAULT NULL,
  `quantity` decimal(20,4) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `declare_weight` decimal(20,4) DEFAULT NULL,
  `actual_weight` decimal(20,4) DEFAULT NULL,
  `invoice_usd` decimal(20,4) DEFAULT NULL,
  `invoice_amount_lak` decimal(20,2) DEFAULT 0.00,
  `paid_customs` decimal(20,2) DEFAULT 0.00,
  `paid_excise` decimal(20,2) DEFAULT 0.00,
  `paid_vat` decimal(20,2) DEFAULT 0.00,
  `paid_profit` decimal(20,2) DEFAULT 0.00,
  `paid_road_fund` decimal(20,2) DEFAULT 0.00,
  `paid_total` decimal(20,2) DEFAULT 0.00,
  `status_aj` varchar(50) DEFAULT NULL,
  `exemp_customs` decimal(20,2) DEFAULT 0.00,
  `exempt_excise` decimal(20,2) DEFAULT 0.00,
  `exempt_vat` decimal(20,2) DEFAULT 0.00,
  `te_customs_excel` decimal(20,2) DEFAULT NULL,
  `te_excise_excel` decimal(20,2) DEFAULT NULL,
  `te_vat_excel` decimal(20,2) DEFAULT NULL,
  `provision_customs` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_batch_id` (`import_batch_id`),
  KEY `regime_code` (`regime_code`),
  KEY `tin` (`tin`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_art9_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` varchar(50) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  `short_name_en` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `more_info` text DEFAULT NULL,
  `tax_rule_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_customs_chapters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `chapter_code` varchar(20) NOT NULL,
  `name_lo` text DEFAULT NULL,
  `name_en` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `order_idx` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `section_id` (`section_id`),
  CONSTRAINT `bm_customs_chapters_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `bm_customs_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_customs_duty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `ahtn_chapter` varchar(10) NOT NULL,
  `ahtn_description` varchar(255) DEFAULT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_customs_regime_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regime_code` varchar(10) NOT NULL,
  `description` varchar(255) NOT NULL,
  `effective_date_from` date DEFAULT NULL,
  `effective_date_to` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `regime_code` (`regime_code`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_customs_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_code` varchar(20) NOT NULL,
  `name_lo` text DEFAULT NULL,
  `name_en` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `order_idx` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_customs_tariff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `rate_laoviet` varchar(10) DEFAULT NULL,
  `chapter_id` int(11) DEFAULT NULL,
  `is_header` tinyint(1) DEFAULT 0,
  `level` int(11) DEFAULT 0,
  `row_idx` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hs_code` (`hs_code`)
) ENGINE=InnoDB AUTO_INCREMENT=15973 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_excise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `indicator` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variable_name` varchar(255) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL,
  `effective_from` date DEFAULT '1970-01-01',
  `effective_to` date DEFAULT '3000-01-01',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_land_concession` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_no` varchar(50) DEFAULT NULL,
  `article_name` varchar(255) DEFAULT NULL,
  `item_no` varchar(50) DEFAULT NULL,
  `item_name` text DEFAULT NULL,
  `rate_zone1` decimal(15,2) DEFAULT 0.00,
  `rate_zone2` decimal(15,2) DEFAULT 0.00,
  `rate_zone3` decimal(15,2) DEFAULT 0.00,
  `rate_search` decimal(15,2) DEFAULT 0.00,
  `rate_survey` decimal(15,2) DEFAULT 0.00,
  `rate_analysis` decimal(15,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'USD/ha/year',
  `start_year` smallint(6) NOT NULL DEFAULT 2025,
  `end_year` smallint(6) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_msme_definition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `effective_date_from` date NOT NULL,
  `effective_date_to` date DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `legacy_item_id` varchar(50) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `micro_value` varchar(255) NOT NULL,
  `small_value` varchar(255) NOT NULL,
  `medium_value` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sector_id` (`sector_id`),
  KEY `effective_date_from` (`effective_date_from`),
  CONSTRAINT `bm_msme_definition_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `business_sectors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_natural_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_no` varchar(50) DEFAULT NULL,
  `item_name` text DEFAULT NULL,
  `rate_percentage` decimal(15,2) DEFAULT 0.00,
  `start_year` smallint(6) NOT NULL DEFAULT 2025,
  `end_year` smallint(6) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_payment_condition_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `condition_code` varchar(10) NOT NULL,
  `description` varchar(255) NOT NULL,
  `effective_date_from` date DEFAULT NULL,
  `effective_date_to` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `condition_code` (`condition_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_pit_employment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `min_income` decimal(20,2) NOT NULL,
  `max_income` decimal(20,2) DEFAULT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_pit_flat_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `income_type` varchar(100) NOT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_profit_mandatory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `sector` varchar(100) NOT NULL,
  `sub_sector` varchar(255) DEFAULT NULL,
  `profit_base_rate` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_profit_sme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `sector` varchar(100) NOT NULL,
  `turnover_min` decimal(15,2) DEFAULT 0.00,
  `turnover_max` decimal(15,2) DEFAULT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_profit_standard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_royalty_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_code` varchar(50) NOT NULL,
  `provision_name` varchar(255) NOT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  `start_year` smallint(6) DEFAULT 2026,
  `end_year` smallint(6) DEFAULT 3000,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_salary_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `provision_number` varchar(10) NOT NULL,
  `rate_percentage` decimal(5,2) NOT NULL DEFAULT 10.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provision_year` (`provision_number`,`start_year`,`end_year`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_sez_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('Developer','Investor') NOT NULL,
  `provision_number` varchar(50) NOT NULL,
  `legal_basis` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `type_of_te` varchar(100) DEFAULT NULL,
  `start_year` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bm_vat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_sectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sector_name` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sector_name` (`sector_name`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tax_year` int(11) NOT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `pro_id` varchar(10) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `dis_id` varchar(50) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `is_vat_holder` tinyint(1) DEFAULT 0,
  `zone_1` tinyint(1) DEFAULT 0,
  `zone_2` tinyint(1) DEFAULT 0,
  `zone_3` tinyint(1) DEFAULT 0,
  `revenue` decimal(20,2) DEFAULT 0.00,
  `expense` decimal(20,2) DEFAULT 0.00,
  `net_profit` decimal(20,2) DEFAULT 0.00,
  `re_invested_profit` decimal(20,2) DEFAULT 0.00,
  `pt_paid` decimal(20,2) DEFAULT 0.00,
  `loss_carryforward` decimal(20,2) DEFAULT 0.00,
  `activity_type` varchar(100) DEFAULT NULL,
  `staff_count` int(11) DEFAULT 0,
  `total_assets` decimal(20,2) DEFAULT 0.00,
  `registration_date` date DEFAULT NULL,
  `investment_license_date` date DEFAULT NULL,
  `annual_turnover` decimal(20,2) DEFAULT 0.00,
  `tax_holiday_years` int(11) DEFAULT 0,
  `flag_hr_dev` tinyint(1) DEFAULT 0,
  `flag_eco_friendly` tinyint(1) DEFAULT 0,
  `flag_sez_developer` tinyint(1) DEFAULT 0,
  `flag_sez_investor` tinyint(1) DEFAULT 0,
  `flag_act_production_services` tinyint(1) DEFAULT 0,
  `flag_public_benefit` tinyint(1) DEFAULT 0,
  `flag_compliant_rental` tinyint(1) DEFAULT 0,
  `flag_real_estate_transfer` tinyint(1) DEFAULT 0,
  `flag_act_1_4_7_8_9` tinyint(1) DEFAULT 0 COMMENT 'IPL Art.9 activities 1,4,7,8,9',
  `flag_act_2_3_5_6` tinyint(1) DEFAULT 0 COMMENT 'IPL Art.9 activities 2,3,5,6',
  `stock_exchange_listing_date` date DEFAULT NULL,
  `reinvest_date` date DEFAULT NULL,
  `reinvest_amount` decimal(20,2) DEFAULT 0.00,
  `expert_te` decimal(20,2) DEFAULT NULL,
  `land_area_sqm` decimal(15,2) DEFAULT NULL,
  `land_concession_article` varchar(50) DEFAULT NULL,
  `land_concession_item` varchar(50) DEFAULT NULL,
  `land_concession_zone` int(11) DEFAULT 1,
  `resource_extraction_item` varchar(50) DEFAULT NULL,
  `sales_value_kip` decimal(15,2) DEFAULT 0.00,
  `zone_type` char(1) DEFAULT 'A' COMMENT 'A=Urban, B=Suburban, C=Rural',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2664 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `concession_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `milestone_type` enum('MOU','Prospecting','Survey','Feasibility','Construction','Operation','Other') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date NOT NULL,
  `remind_days` int(11) DEFAULT 30,
  `responsible_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `last_notified_at` datetime DEFAULT NULL,
  `status` enum('Active','Completed','Extended','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customs_provision_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `operator` varchar(20) NOT NULL,
  `value_1` varchar(255) DEFAULT NULL,
  `value_2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provision_id` (`provision_id`),
  CONSTRAINT `customs_provision_conditions_ibfk_1` FOREIGN KEY (`provision_id`) REFERENCES `customs_provisions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customs_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_number` varchar(10) NOT NULL,
  `start_year` int(11) DEFAULT 2020,
  `end_year` int(11) DEFAULT 2099,
  `legal_basis` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `type_of_te` enum('Exemption','Reduction') DEFAULT 'Exemption',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district_code` varchar(10) NOT NULL,
  `district_name` varchar(100) NOT NULL,
  `district_name_lao` varchar(100) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `zone` tinyint(4) DEFAULT NULL COMMENT '1=SEZ, 2=Promotion Zone',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `district_code` (`district_code`),
  KEY `province_id` (`province_id`),
  CONSTRAINT `districts_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1080 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_project_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `excise_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_number` varchar(20) NOT NULL,
  `start_year` int(11) NOT NULL DEFAULT 2020,
  `end_year` int(11) NOT NULL DEFAULT 2099,
  `legal_basis` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `type_of_te` enum('Exemption','Reduction') DEFAULT 'Exemption',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_pit_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `import_date` datetime NOT NULL DEFAULT current_timestamp(),
  `tax_year` int(11) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `individual_name` varchar(255) DEFAULT NULL,
  `filing_date` date DEFAULT NULL,
  `ptin` varchar(50) DEFAULT NULL,
  `amount_21` decimal(15,2) DEFAULT 0.00,
  `amount_22` decimal(15,2) DEFAULT 0.00,
  `amount_23_1` decimal(15,2) DEFAULT 0.00,
  `amount_23_2` decimal(15,2) DEFAULT 0.00,
  `amount_24` decimal(15,2) DEFAULT 0.00,
  `amount_25` decimal(15,2) DEFAULT 0.00,
  `amount_26` decimal(15,2) DEFAULT 0.00,
  `amount_27` decimal(15,2) DEFAULT 0.00,
  `amount_28_1` decimal(15,2) DEFAULT 0.00,
  `amount_28_2` decimal(15,2) DEFAULT 0.00,
  `amount_29` decimal(15,2) DEFAULT 0.00,
  `is_stock_listed` tinyint(1) DEFAULT 0,
  `is_banking_system` tinyint(1) DEFAULT 0,
  `is_ss_member` tinyint(1) DEFAULT 0,
  `ss_contribution` decimal(15,2) DEFAULT 0.00,
  `use_fallback` tinyint(1) DEFAULT 0,
  `expert_te_21` decimal(15,2) DEFAULT 0.00,
  `expert_te_22` decimal(15,2) DEFAULT 0.00,
  `expert_te_23_1` decimal(15,2) DEFAULT 0.00,
  `expert_te_23_2` decimal(15,2) DEFAULT 0.00,
  `expert_te_24` decimal(15,2) DEFAULT 0.00,
  `expert_te_25` decimal(15,2) DEFAULT 0.00,
  `expert_te_26` decimal(15,2) DEFAULT 0.00,
  `expert_te_27` decimal(15,2) DEFAULT 0.00,
  `expert_te_28_1` decimal(15,2) DEFAULT 0.00,
  `expert_te_28_2` decimal(15,2) DEFAULT 0.00,
  `expert_te_29` decimal(15,2) DEFAULT 0.00,
  `user_te_21` decimal(15,2) DEFAULT NULL,
  `user_te_22` decimal(15,2) DEFAULT NULL,
  `user_te_23_1` decimal(15,2) DEFAULT NULL,
  `user_te_23_2` decimal(15,2) DEFAULT NULL,
  `user_te_24` decimal(15,2) DEFAULT NULL,
  `user_te_25` decimal(15,2) DEFAULT NULL,
  `user_te_26` decimal(15,2) DEFAULT NULL,
  `user_te_27` decimal(15,2) DEFAULT NULL,
  `user_te_28_1` decimal(15,2) DEFAULT NULL,
  `user_te_28_2` decimal(15,2) DEFAULT NULL,
  `user_te_29` decimal(15,2) DEFAULT NULL,
  `user_te_30` decimal(15,2) DEFAULT NULL,
  `user_te_total` decimal(15,2) DEFAULT NULL,
  `expert_te_total` decimal(15,2) DEFAULT 0.00,
  `user_fallback_reason` varchar(255) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tax_year` (`tax_year`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_resource_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `import_date` datetime DEFAULT current_timestamp(),
  `tax_year` int(11) NOT NULL,
  `receipt_date` date DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `license_date` date DEFAULT NULL,
  `resource_type` varchar(255) DEFAULT NULL,
  `actual_rate` decimal(15,2) DEFAULT 0.00,
  `fee_collected` decimal(20,2) DEFAULT 0.00,
  `paid_currency` varchar(10) DEFAULT NULL,
  `exchange_rate` decimal(15,2) DEFAULT NULL,
  `use_user_fallback` tinyint(1) DEFAULT 0,
  `user_benchmark_rate` decimal(15,2) DEFAULT NULL,
  `user_benchmark_fee` decimal(15,2) DEFAULT NULL,
  `user_te` decimal(15,2) DEFAULT NULL,
  `user_fallback_reason` varchar(255) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  `benchmark_rate` decimal(15,2) DEFAULT 0.00,
  `contracted_rate` decimal(15,2) DEFAULT NULL,
  `sale_quantity` decimal(15,2) DEFAULT NULL,
  `benchmark_fee` decimal(20,2) DEFAULT 0.00,
  `te_amount` decimal(20,2) DEFAULT 0.00,
  `calculated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tin` (`tin`),
  KEY `idx_year` (`tax_year`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_royalty_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `tax_year` int(11) NOT NULL,
  `import_date` datetime DEFAULT current_timestamp(),
  `tin` varchar(50) NOT NULL,
  `license_date` date DEFAULT NULL,
  `electricity_sale_value` decimal(20,2) DEFAULT 0.00,
  `actual_rate` decimal(15,2) DEFAULT 0.00,
  `fee_collected` decimal(20,2) DEFAULT 0.00,
  `benchmark_rate` decimal(15,2) DEFAULT 0.00,
  `benchmark_fee` decimal(20,2) DEFAULT 0.00,
  `te_amount` decimal(20,2) DEFAULT 0.00,
  `calculated_at` datetime DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `contracted_rate` decimal(15,2) DEFAULT NULL,
  `paid_currency` varchar(10) DEFAULT NULL,
  `exchange_rate` decimal(15,2) DEFAULT NULL,
  `use_user_fallback` tinyint(1) DEFAULT 0,
  `user_benchmark_rate` decimal(15,2) DEFAULT NULL,
  `user_benchmark_fee` decimal(15,2) DEFAULT NULL,
  `user_te` decimal(15,2) DEFAULT NULL,
  `user_fallback_reason` varchar(255) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tin` (`tin`),
  KEY `idx_year` (`tax_year`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_salary_tax_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `tax_year` int(11) DEFAULT NULL,
  `import_date` datetime NOT NULL DEFAULT current_timestamp(),
  `tin` varchar(50) DEFAULT NULL,
  `filing_type` varchar(50) DEFAULT NULL,
  `filing_period` varchar(50) DEFAULT NULL,
  `input_date` date DEFAULT NULL,
  `total_salaries_wages_cash` decimal(20,2) DEFAULT 0.00,
  `other_fringe_benefits` decimal(20,2) DEFAULT 0.00,
  `total_taxable_amount` decimal(20,2) DEFAULT 0.00,
  `tax_exempt_amount` decimal(20,2) DEFAULT 0.00,
  `tax_amount` decimal(20,2) DEFAULT 0.00,
  `adjustment_amount` decimal(20,2) DEFAULT 0.00,
  `carryforward_amount` decimal(20,2) DEFAULT 0.00,
  `total_amount_due` decimal(20,2) DEFAULT 0.00,
  `benchmark_tax` decimal(20,2) DEFAULT 0.00,
  `te_amount` decimal(20,2) DEFAULT 0.00,
  `provision_number` varchar(50) DEFAULT NULL,
  `calculated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tin` (`tin`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_sez_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `tax_year` int(11) DEFAULT NULL,
  `import_date` datetime DEFAULT current_timestamp(),
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `license_date` date DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `pro_id` varchar(10) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `village` varchar(255) DEFAULT NULL,
  `sez_name` varchar(255) DEFAULT NULL,
  `sez_developer` tinyint(1) DEFAULT 0,
  `sez_investor` tinyint(1) DEFAULT 0,
  `dis_id` varchar(50) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `sector` varchar(50) DEFAULT NULL,
  `type` enum('Developer','Investor') NOT NULL,
  `amount_infra_basic` decimal(20,2) DEFAULT 0.00,
  `amount_infra_other` decimal(20,2) DEFAULT 0.00,
  `amount_utility_usage` decimal(20,2) DEFAULT 0.00,
  `amount_infra_dev` decimal(20,2) DEFAULT 0.00,
  `use_user_fallback` tinyint(1) DEFAULT 0,
  `user_benchmark_rate` decimal(15,2) DEFAULT NULL,
  `user_te` decimal(15,2) DEFAULT NULL,
  `user_fallback_reason` varchar(255) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  `benchmark_tax` decimal(20,2) DEFAULT 0.00,
  `te_amount` decimal(20,2) DEFAULT 0.00,
  `provision_number` varchar(50) DEFAULT NULL,
  `calculated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_tin` (`tin`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_vat_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `import_date` datetime DEFAULT current_timestamp(),
  `province` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `pro_id` varchar(10) DEFAULT NULL,
  `dis_id` varchar(50) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `filing_type` varchar(50) DEFAULT NULL,
  `filing_period` date DEFAULT NULL,
  `input_date` date DEFAULT NULL,
  `purchase_domestic_nonexempt` decimal(15,2) DEFAULT 0.00,
  `purchase_domestic_exempt` decimal(15,2) DEFAULT 0.00,
  `purchase_import_nonexempt` decimal(15,2) DEFAULT 0.00,
  `purchase_import_exempt` decimal(15,2) DEFAULT 0.00,
  `total_input_vat` decimal(15,2) DEFAULT 0.00,
  `sales_standard` decimal(15,2) DEFAULT 0.00,
  `sales_zero_rate` decimal(15,2) DEFAULT 0.00,
  `sales_exempt` decimal(15,2) DEFAULT 0.00,
  `total_output_vat` decimal(15,2) DEFAULT 0.00,
  `vat_payable` decimal(15,2) DEFAULT 0.00,
  `vat_credit` decimal(15,2) DEFAULT 0.00,
  `expert_te` decimal(15,2) DEFAULT 0.00,
  `system_te` decimal(15,2) DEFAULT NULL,
  `benchmark_output_vat` decimal(15,2) DEFAULT NULL,
  `calculated_vat_payable` decimal(15,2) DEFAULT NULL,
  `provision_number` varchar(20) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `vat_rate` decimal(5,2) DEFAULT NULL,
  `user_te` decimal(15,2) DEFAULT NULL,
  `user_benchmark_rate` decimal(5,2) DEFAULT NULL,
  `user_benchmark_vat` decimal(15,2) DEFAULT NULL,
  `use_user_fallback` tinyint(1) DEFAULT 0,
  `system_benchmark_rate` decimal(5,2) DEFAULT NULL,
  `user_fallback_reason` varchar(255) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=342 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `individual_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_number` varchar(10) NOT NULL,
  `start_year` int(11) DEFAULT 2020,
  `end_year` int(11) DEFAULT 2099,
  `legal_basis` varchar(255) DEFAULT NULL,
  `type_of_te` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `limit_amount` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(10) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `land_concession_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_code` varchar(20) NOT NULL,
  `provision_name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL COMMENT 'Industrial/SEZ/Priority',
  `exemption_years` smallint(6) NOT NULL DEFAULT 0,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `provision_code` (`provision_code`),
  CONSTRAINT `land_concession_provisions_chk_1` CHECK (json_valid(`conditions`))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moic_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moic_enterprise_category_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_id` int(11) NOT NULL,
  `main_category_id` int(11) NOT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_id` (`enterprise_id`),
  KEY `main_category_id` (`main_category_id`),
  KEY `sub_category_id` (`sub_category_id`),
  CONSTRAINT `moic_enterprise_category_map_ibfk_1` FOREIGN KEY (`enterprise_id`) REFERENCES `repo_moic` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moic_enterprise_category_map_ibfk_2` FOREIGN KEY (`main_category_id`) REFERENCES `moic_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moic_enterprise_category_map_ibfk_3` FOREIGN KEY (`sub_category_id`) REFERENCES `moic_sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moic_sub_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `main_category_id` int(11) DEFAULT NULL,
  `sub_category_name` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `main_category_id` (`main_category_id`),
  CONSTRAINT `moic_sub_categories_ibfk_1` FOREIGN KEY (`main_category_id`) REFERENCES `moic_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=524 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `natural_resource_provisions` (
  `id` int(11) NOT NULL,
  `provision_code` varchar(50) NOT NULL,
  `provision_name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `exemption_years` smallint(6) NOT NULL DEFAULT 0,
  `reduction_percentage` decimal(5,2) DEFAULT 0.00,
  `period_time` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref_id` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `contents` text DEFAULT NULL,
  `notification_date` datetime DEFAULT NULL,
  `emails` varchar(255) DEFAULT NULL,
  `phones` varchar(255) DEFAULT NULL,
  `status` enum('Sent','Unsent','Failed') DEFAULT 'Unsent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profit_provision_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `operator` varchar(20) NOT NULL,
  `value_1` varchar(255) DEFAULT NULL,
  `value_2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provision_id` (`provision_id`),
  CONSTRAINT `profit_provision_conditions_ibfk_1` FOREIGN KEY (`provision_id`) REFERENCES `profit_provisions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profit_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_number` varchar(10) NOT NULL,
  `start_year` int(11) DEFAULT 2020,
  `end_year` int(11) DEFAULT 2099,
  `legal_reference` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `target_rate` decimal(5,2) DEFAULT NULL,
  `is_exemption` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `province_code` varchar(10) NOT NULL,
  `province_name` varchar(100) NOT NULL,
  `province_name_lao` varchar(100) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `province_code` (`province_code`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_gdp_revenue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `gdp_year` int(11) NOT NULL,
  `gdp_value` decimal(20,2) DEFAULT 0.00,
  `revenue_value` decimal(20,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `gdp_year` (`gdp_year`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_land_concession_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tax_year` int(11) NOT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `pro_id` varchar(10) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `dis_id` varchar(50) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `confirm_date` date DEFAULT NULL,
  `concession_area_ha` decimal(20,4) DEFAULT 0.0000,
  `benchmark_rate_usd` decimal(20,4) DEFAULT 0.0000,
  `contracted_rate_usd` decimal(20,4) DEFAULT 0.0000,
  `concession_fee_paid_usd` decimal(20,4) DEFAULT 0.0000,
  `paid_currency` varchar(10) DEFAULT NULL,
  `exchange_rate` decimal(15,2) DEFAULT NULL,
  `use_user_fallback` tinyint(1) DEFAULT 0,
  `user_benchmark_rate` decimal(15,2) DEFAULT NULL,
  `user_benchmark_value` decimal(15,2) DEFAULT NULL,
  `user_nontax_te` decimal(15,2) DEFAULT NULL,
  `user_fallback_reason` varchar(255) DEFAULT NULL,
  `user_comment` text DEFAULT NULL,
  `benchmark_value_usd` decimal(20,4) DEFAULT 0.0000,
  `non_tax_te_usd` decimal(20,4) DEFAULT 0.0000,
  `provision_code` varchar(50) DEFAULT NULL,
  `provision_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `import_batch_id` (`import_batch_id`),
  KEY `tin` (`tin`),
  KEY `tax_year` (`tax_year`)
) ENGINE=InnoDB AUTO_INCREMENT=1718 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_lse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `listing_date` date DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tin` (`tin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_moic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `village_id` int(11) DEFAULT NULL,
  `village` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `enterprise_type_id` int(11) DEFAULT NULL,
  `license_date` date DEFAULT NULL,
  `first_revenue_date` date DEFAULT NULL,
  `incentive_grant_date` date DEFAULT NULL,
  `incentive_tax_policy` text DEFAULT NULL,
  `investor_fund_rate` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `business_size_staff` int(11) DEFAULT 0,
  `registered_capital` decimal(20,2) DEFAULT 0.00,
  `vat_system_status` int(11) DEFAULT NULL,
  `hr_dev_scope` tinyint(1) DEFAULT NULL,
  `innovative_tech_scope` tinyint(1) DEFAULT NULL,
  `art9_p2_scope` tinyint(1) DEFAULT NULL,
  `art9_p3_scope` tinyint(1) DEFAULT NULL,
  `art9_p4_scope` tinyint(1) DEFAULT NULL,
  `art9_p5_scope` tinyint(1) DEFAULT NULL,
  `art9_p6_scope` tinyint(1) DEFAULT NULL,
  `prod_industry_scope` tinyint(1) DEFAULT NULL,
  `tourism_scope` tinyint(1) DEFAULT NULL,
  `public_health_scope` tinyint(1) DEFAULT NULL,
  `edu_scope` tinyint(1) DEFAULT NULL,
  `sport_scope` tinyint(1) DEFAULT NULL,
  `real_estate_scope` tinyint(1) DEFAULT NULL,
  `micro_ent_scope` tinyint(1) DEFAULT NULL,
  `agri_handicraft_scope` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `industry_manuf_scope` tinyint(1) DEFAULT 2,
  `commerce_service_scope` tinyint(1) DEFAULT 2,
  `electric_mining_scope` tinyint(1) DEFAULT 2,
  `agri_industrial_scope` tinyint(1) DEFAULT 2,
  `commerce_scope` tinyint(1) DEFAULT 2,
  `transport_scope` tinyint(1) DEFAULT 2,
  `construction_scope` tinyint(1) DEFAULT 2,
  `wood_exploitation_scope` tinyint(1) DEFAULT 2,
  `extraction_filling_scope` tinyint(1) DEFAULT 2,
  `entertainment_scope` tinyint(1) DEFAULT 2,
  `consultancy_scope` tinyint(1) DEFAULT 2,
  `brokers_agents_scope` tinyint(1) DEFAULT 2,
  `real_estate_dev_sale_scope` tinyint(1) DEFAULT 2,
  `other_service_scope` tinyint(1) DEFAULT 2,
  `tobacco_scope` tinyint(1) DEFAULT 2,
  `mining_activity_scope` tinyint(1) DEFAULT 2,
  `sez_developer_scope` tinyint(1) DEFAULT 2,
  `sez_investor_scope` tinyint(1) DEFAULT 2,
  `sector_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tin` (`tin`),
  KEY `fk_moic_village` (`village_id`),
  KEY `fk_moic_ent_type` (`enterprise_type_id`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `fk_moic_ent_type` FOREIGN KEY (`enterprise_type_id`) REFERENCES `enterprise_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_moic_village` FOREIGN KEY (`village_id`) REFERENCES `villages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repo_moic_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `enterprise_project_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_molsw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `staff_count` int(11) DEFAULT 0,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tin` (`tin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_mpi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `investment_license_date` date DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `incentives` text DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `tax_holiday_period` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sector_id` (`sector_id`),
  KEY `tin` (`tin`),
  CONSTRAINT `repo_mpi_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `business_sectors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_sezo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `type` enum('Investor','Developer') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `province_id` (`province_id`),
  KEY `district_id` (`district_id`),
  KEY `category_id` (`category_id`),
  KEY `tin` (`tin`),
  KEY `fk_sezo_sector` (`sector_id`),
  CONSTRAINT `fk_sezo_sector` FOREIGN KEY (`sector_id`) REFERENCES `business_sectors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repo_sezo_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repo_sezo_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repo_sezo_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `business_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_taxris` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `revenue` decimal(20,2) DEFAULT 0.00,
  `expense` decimal(20,2) DEFAULT 0.00,
  `net_profit` decimal(20,2) DEFAULT 0.00,
  `tax_paid` decimal(20,2) DEFAULT 0.00,
  `te_dummy` varchar(255) DEFAULT NULL,
  `tax_rate_paid` decimal(5,2) DEFAULT 0.00,
  `total_assets` decimal(20,2) DEFAULT 0.00,
  `vat_system_status` tinyint(1) DEFAULT 0,
  `reinvest_net_profit` decimal(20,2) DEFAULT 0.00,
  `reinvest_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_public_income` tinyint(1) DEFAULT 2,
  `is_asset_rent` tinyint(1) DEFAULT 2,
  `is_real_estate_transfer` tinyint(1) DEFAULT 2,
  `is_vat_enterprise` tinyint(1) DEFAULT 2,
  `total_assets_bn` decimal(20,2) DEFAULT 0.00,
  `annual_turnover_bn` decimal(20,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `tin` (`tin`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_read` tinyint(1) DEFAULT 1,
  `can_update` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_module` (`role_id`,`module`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_number` varchar(10) NOT NULL,
  `legal_basis` varchar(255) DEFAULT NULL,
  `type_of_te` varchar(50) DEFAULT 'Exemption',
  `description` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `provision_number` (`provision_number`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `te_asycuda_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asycuda_id` int(11) NOT NULL,
  `customs_te` decimal(20,2) DEFAULT 0.00,
  `excise_te` decimal(20,2) DEFAULT 0.00,
  `vat_te` decimal(20,2) DEFAULT 0.00,
  `total_te` decimal(20,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `asycuda_id` (`asycuda_id`),
  CONSTRAINT `te_asycuda_result_ibfk_1` FOREIGN KEY (`asycuda_id`) REFERENCES `asycuda_imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `te_individual_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_year` int(11) NOT NULL,
  `tin` varchar(50) NOT NULL,
  `individual_name` varchar(255) DEFAULT NULL,
  `filing_date` date DEFAULT NULL,
  `employment_income` decimal(20,2) DEFAULT 0.00,
  `other_income` decimal(20,2) DEFAULT 0.00,
  `actual_tax_paid` decimal(20,2) DEFAULT 0.00,
  `benchmark_calculated_tax` decimal(20,2) DEFAULT 0.00,
  `te_amount` decimal(20,2) DEFAULT 0.00,
  `matched_provisions` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `te_land_concession_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `zone_type` char(1) DEFAULT NULL,
  `benchmark_rate` decimal(15,2) DEFAULT NULL,
  `land_value_kip` decimal(15,2) DEFAULT NULL,
  `exemption_years` smallint(6) DEFAULT NULL,
  `exemption_value` decimal(15,2) DEFAULT NULL,
  `te_land_concession` decimal(15,2) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `te_land_concession_result_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `te_profit_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `benchmark_rate_applied` decimal(5,2) DEFAULT NULL,
  `benchmark_pt` decimal(20,2) DEFAULT 0.00,
  `pt_te` decimal(20,2) DEFAULT 0.00,
  `matched_provisions` varchar(255) DEFAULT NULL,
  `profit_tax_te` decimal(20,2) DEFAULT 0.00,
  `expert_te` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `te_profit_result_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4069 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_online` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vat_provision_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `operator` varchar(20) NOT NULL,
  `value_1` varchar(255) DEFAULT NULL,
  `value_2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provision_id` (`provision_id`),
  CONSTRAINT `vat_provision_conditions_ibfk_1` FOREIGN KEY (`provision_id`) REFERENCES `vat_provisions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vat_provisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provision_number` varchar(20) NOT NULL,
  `start_year` int(11) DEFAULT 2020,
  `end_year` int(11) DEFAULT 2099,
  `legal_basis` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `type_of_te` enum('Exemption','Rate Relief') DEFAULT 'Exemption',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `village_code` varchar(20) NOT NULL,
  `village_name` varchar(255) NOT NULL,
  `village_name_lao` varchar(255) DEFAULT NULL,
  `district_code` varchar(10) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_village_code` (`village_code`),
  KEY `idx_district` (`district_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8829 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

