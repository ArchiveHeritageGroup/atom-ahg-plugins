-- ============================================================
-- ahgGrapPlugin - Database Schema
-- Generated from actual database structure
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: archive
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `grap_compliance_check`
--

DROP TABLE IF EXISTS `grap_compliance_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_compliance_check` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `check_date` datetime NOT NULL,
  `check_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('passed','failed','warning','not_applicable') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_date` (`check_date`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  CONSTRAINT `fk_grap_compliance_object` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_financial_year_snapshot`
--

DROP TABLE IF EXISTS `grap_financial_year_snapshot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_financial_year_snapshot` (
  `id` int NOT NULL AUTO_INCREMENT,
  `repository_id` int DEFAULT NULL,
  `financial_year_end` date NOT NULL,
  `asset_class` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_assets` int DEFAULT '0',
  `total_carrying_amount` decimal(18,2) DEFAULT '0.00',
  `total_impairment` decimal(18,2) DEFAULT '0.00',
  `total_revaluation_surplus` decimal(18,2) DEFAULT '0.00',
  `additions_count` int DEFAULT '0',
  `additions_value` decimal(18,2) DEFAULT '0.00',
  `disposals_count` int DEFAULT '0',
  `disposals_value` decimal(18,2) DEFAULT '0.00',
  `impairments_count` int DEFAULT '0',
  `impairments_value` decimal(18,2) DEFAULT '0.00',
  `revaluations_count` int DEFAULT '0',
  `snapshot_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_snapshot` (`repository_id`,`financial_year_end`,`asset_class`),
  KEY `idx_repo` (`repository_id`),
  KEY `idx_fy` (`financial_year_end`),
  KEY `idx_class` (`asset_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_heritage_asset`
--

DROP TABLE IF EXISTS `grap_heritage_asset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_heritage_asset` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `recognition_status` varchar(50) DEFAULT NULL,
  `recognition_status_reason` varchar(255) DEFAULT NULL,
  `recognition_date` date DEFAULT NULL,
  `measurement_basis` varchar(50) DEFAULT NULL,
  `acquisition_method` varchar(50) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `cost_of_acquisition` decimal(15,2) DEFAULT '0.00',
  `fair_value_at_acquisition` decimal(15,2) DEFAULT NULL,
  `nominal_value` decimal(15,2) DEFAULT '1.00',
  `donor_name` varchar(255) DEFAULT NULL,
  `donor_restrictions` text,
  `initial_carrying_amount` decimal(15,2) DEFAULT '0.00',
  `current_carrying_amount` decimal(15,2) DEFAULT '0.00',
  `last_valuation_date` date DEFAULT NULL,
  `last_valuation_amount` decimal(15,2) DEFAULT NULL,
  `valuation_method` varchar(50) DEFAULT NULL,
  `valuer_name` varchar(255) DEFAULT NULL,
  `valuer_credentials` varchar(255) DEFAULT NULL,
  `valuation_report_reference` varchar(255) DEFAULT NULL,
  `revaluation_frequency` varchar(50) DEFAULT NULL,
  `revaluation_surplus` decimal(15,2) DEFAULT '0.00',
  `depreciation_policy` varchar(50) DEFAULT NULL,
  `useful_life_years` int DEFAULT NULL,
  `residual_value` decimal(15,2) DEFAULT '0.00',
  `depreciation_method` varchar(50) DEFAULT NULL,
  `annual_depreciation` decimal(15,2) DEFAULT '0.00',
  `accumulated_depreciation` decimal(15,2) DEFAULT '0.00',
  `last_impairment_date` date DEFAULT NULL,
  `impairment_indicators` tinyint(1) DEFAULT '0',
  `impairment_indicators_details` text,
  `impairment_loss` decimal(15,2) DEFAULT '0.00',
  `recoverable_service_amount` decimal(15,2) DEFAULT NULL,
  `derecognition_date` date DEFAULT NULL,
  `derecognition_reason` varchar(50) DEFAULT NULL,
  `derecognition_proceeds` decimal(15,2) DEFAULT NULL,
  `gain_loss_on_derecognition` decimal(15,2) DEFAULT NULL,
  `asset_class` varchar(50) DEFAULT NULL,
  `asset_sub_class` varchar(100) DEFAULT NULL,
  `gl_account_code` varchar(50) DEFAULT NULL,
  `cost_center` varchar(50) DEFAULT NULL,
  `fund_source` varchar(100) DEFAULT NULL,
  `budget_vote` varchar(50) DEFAULT NULL,
  `heritage_significance` varchar(50) DEFAULT NULL,
  `significance_statement` text,
  `restrictions_on_use` text,
  `restrictions_on_disposal` text,
  `conservation_requirements` text,
  `conservation_commitments` text,
  `insurance_required` tinyint(1) DEFAULT '1',
  `insurance_value` decimal(15,2) DEFAULT NULL,
  `insurance_policy_number` varchar(100) DEFAULT NULL,
  `notes` text,
  `insurance_provider` varchar(255) DEFAULT NULL,
  `insurance_expiry_date` date DEFAULT NULL,
  `risk_assessment_date` date DEFAULT NULL,
  `risk_level` varchar(50) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `storage_conditions` text,
  `condition_rating` varchar(50) DEFAULT NULL,
  `last_condition_assessment` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `initial_recognition_date` date DEFAULT NULL,
  `initial_recognition_value` decimal(15,2) DEFAULT NULL,
  `acquisition_method_grap` varchar(50) DEFAULT NULL,
  `heritage_significance_rating` varchar(50) DEFAULT NULL,
  `restrictions_use_disposal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_revaluation_date` date DEFAULT NULL,
  `revaluation_amount` decimal(15,2) DEFAULT NULL,
  `insurance_coverage_required` decimal(15,2) DEFAULT NULL,
  `insurance_coverage_actual` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_io` (`object_id`),
  KEY `idx_recognition_status` (`recognition_status`),
  KEY `idx_asset_class` (`asset_class`),
  KEY `idx_gl_account` (`gl_account_code`),
  KEY `idx_cost_center` (`cost_center`),
  KEY `idx_acquisition_date` (`acquisition_date`),
  KEY `idx_valuation_date` (`last_valuation_date`),
  KEY `idx_heritage_significance` (`heritage_significance`),
  CONSTRAINT `grap_heritage_asset_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_impairment_assessment`
--

DROP TABLE IF EXISTS `grap_impairment_assessment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_impairment_assessment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_heritage_asset_id` int NOT NULL,
  `assessment_date` date NOT NULL,
  `physical_damage` tinyint(1) DEFAULT '0',
  `physical_damage_details` text,
  `obsolescence` tinyint(1) DEFAULT '0',
  `obsolescence_details` text,
  `change_in_use` tinyint(1) DEFAULT '0',
  `change_in_use_details` text,
  `external_factors` tinyint(1) DEFAULT '0',
  `external_factors_details` text,
  `impairment_identified` tinyint(1) DEFAULT '0',
  `carrying_amount_before` decimal(15,2) DEFAULT NULL,
  `recoverable_service_amount` decimal(15,2) DEFAULT NULL,
  `impairment_loss` decimal(15,2) DEFAULT NULL,
  `carrying_amount_after` decimal(15,2) DEFAULT NULL,
  `reversal_applicable` tinyint(1) DEFAULT '0',
  `reversal_amount` decimal(15,2) DEFAULT NULL,
  `reversal_date` date DEFAULT NULL,
  `assessor_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grap_heritage_asset_id` (`grap_heritage_asset_id`),
  KEY `idx_assessment_date` (`assessment_date`),
  KEY `idx_impairment_identified` (`impairment_identified`),
  CONSTRAINT `grap_impairment_assessment_ibfk_1` FOREIGN KEY (`grap_heritage_asset_id`) REFERENCES `grap_heritage_asset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_journal_entry`
--

DROP TABLE IF EXISTS `grap_journal_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_journal_entry` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_heritage_asset_id` int NOT NULL,
  `journal_date` date NOT NULL,
  `journal_number` varchar(50) DEFAULT NULL,
  `journal_type` enum('recognition','revaluation','depreciation','impairment','derecognition','adjustment','transfer') NOT NULL,
  `debit_account` varchar(50) NOT NULL,
  `debit_amount` decimal(15,2) NOT NULL,
  `credit_account` varchar(50) NOT NULL,
  `credit_amount` decimal(15,2) NOT NULL,
  `description` text,
  `reference_document` varchar(255) DEFAULT NULL,
  `fiscal_year` int DEFAULT NULL,
  `fiscal_period` int DEFAULT NULL,
  `posted` tinyint(1) DEFAULT '0',
  `posted_by` int DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `reversed` tinyint(1) DEFAULT '0',
  `reversal_journal_id` int DEFAULT NULL,
  `reversal_date` date DEFAULT NULL,
  `reversal_reason` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grap_heritage_asset_id` (`grap_heritage_asset_id`),
  KEY `idx_journal_date` (`journal_date`),
  KEY `idx_journal_type` (`journal_type`),
  KEY `idx_fiscal_year` (`fiscal_year`),
  KEY `idx_posted` (`posted`),
  CONSTRAINT `grap_journal_entry_ibfk_1` FOREIGN KEY (`grap_heritage_asset_id`) REFERENCES `grap_heritage_asset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_movement_register`
--

DROP TABLE IF EXISTS `grap_movement_register`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_movement_register` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_heritage_asset_id` int NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('loan_out','loan_return','transfer','exhibition','conservation','storage_change','other') NOT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `reason` text,
  `authorized_by` varchar(255) DEFAULT NULL,
  `authorization_date` date DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `condition_on_departure` enum('excellent','good','fair','poor') DEFAULT NULL,
  `condition_on_return` enum('excellent','good','fair','poor') DEFAULT NULL,
  `condition_notes` text,
  `insurance_confirmed` tinyint(1) DEFAULT '0',
  `insurance_value` decimal(15,2) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grap_heritage_asset_id` (`grap_heritage_asset_id`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_movement_type` (`movement_type`),
  CONSTRAINT `grap_movement_register_ibfk_1` FOREIGN KEY (`grap_heritage_asset_id`) REFERENCES `grap_heritage_asset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_spectrum_procedure_link`
--

DROP TABLE IF EXISTS `grap_spectrum_procedure_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_spectrum_procedure_link` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `grap_asset_id` int NOT NULL,
  `spectrum_procedure` varchar(50) NOT NULL COMMENT 'acquisition, loan_in, loan_out, movement, valuation, condition, deaccession',
  `spectrum_record_id` int NOT NULL,
  `link_type` enum('initial_recognition','subsequent_measurement','impairment','disposal','audit') NOT NULL,
  `link_date` date NOT NULL,
  `financial_impact` decimal(15,2) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grap` (`grap_asset_id`),
  KEY `idx_spectrum` (`spectrum_procedure`,`spectrum_record_id`),
  KEY `idx_type` (`link_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_transaction_log`
--

DROP TABLE IF EXISTS `grap_transaction_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_transaction_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `transaction_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `transaction_data` json DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_grap_trans_object` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grap_valuation_history`
--

DROP TABLE IF EXISTS `grap_valuation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `grap_valuation_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_heritage_asset_id` int NOT NULL,
  `valuation_date` date NOT NULL,
  `previous_value` decimal(15,2) DEFAULT NULL,
  `new_value` decimal(15,2) NOT NULL,
  `valuation_change` decimal(15,2) DEFAULT NULL,
  `valuation_method` enum('market_approach','cost_approach','income_approach','expert_opinion') DEFAULT NULL,
  `valuer_name` varchar(255) DEFAULT NULL,
  `valuer_credentials` varchar(255) DEFAULT NULL,
  `valuer_organization` varchar(255) DEFAULT NULL,
  `valuation_report_reference` varchar(255) DEFAULT NULL,
  `revaluation_surplus_change` decimal(15,2) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_valuation_date` (`valuation_date`),
  KEY `idx_grap_asset` (`grap_heritage_asset_id`),
  CONSTRAINT `grap_valuation_history_ibfk_1` FOREIGN KEY (`grap_heritage_asset_id`) REFERENCES `grap_heritage_asset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_grap_data`
--

DROP TABLE IF EXISTS `spectrum_grap_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_grap_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `information_object_id` int NOT NULL,
  `recognition_status` varchar(50) DEFAULT NULL,
  `recognition_status_reason` varchar(255) DEFAULT NULL,
  `measurement_basis` varchar(50) DEFAULT NULL,
  `initial_recognition_date` date DEFAULT NULL,
  `initial_recognition_value` decimal(15,2) DEFAULT NULL,
  `carrying_amount` decimal(15,2) DEFAULT NULL,
  `acquisition_method_grap` varchar(50) DEFAULT NULL,
  `cost_of_acquisition` decimal(15,2) DEFAULT NULL,
  `fair_value_at_acquisition` decimal(15,2) DEFAULT NULL,
  `donor_restrictions` text,
  `last_revaluation_date` date DEFAULT NULL,
  `revaluation_amount` decimal(15,2) DEFAULT NULL,
  `valuer_credentials` varchar(255) DEFAULT NULL,
  `valuation_method` varchar(50) DEFAULT NULL,
  `revaluation_frequency` varchar(50) DEFAULT NULL,
  `depreciation_policy` varchar(50) DEFAULT NULL,
  `useful_life_years` int DEFAULT NULL,
  `residual_value` decimal(15,2) DEFAULT NULL,
  `depreciation_method` varchar(50) DEFAULT NULL,
  `accumulated_depreciation` decimal(15,2) DEFAULT NULL,
  `last_impairment_assessment_date` date DEFAULT NULL,
  `impairment_indicators` tinyint(1) DEFAULT '0',
  `impairment_indicators_details` text,
  `impairment_loss_amount` decimal(15,2) DEFAULT NULL,
  `derecognition_date` date DEFAULT NULL,
  `derecognition_reason` varchar(50) DEFAULT NULL,
  `derecognition_value` decimal(15,2) DEFAULT NULL,
  `gain_loss_on_derecognition` decimal(15,2) DEFAULT NULL,
  `asset_class` varchar(50) DEFAULT NULL,
  `gl_account_code` varchar(50) DEFAULT NULL,
  `cost_center` varchar(50) DEFAULT NULL,
  `fund_source` varchar(100) DEFAULT NULL,
  `restrictions_use_disposal` text,
  `heritage_significance_rating` varchar(50) DEFAULT NULL,
  `conservation_commitments` text,
  `insurance_coverage_required` decimal(15,2) DEFAULT NULL,
  `insurance_coverage_actual` decimal(15,2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grap_io` (`information_object_id`),
  KEY `idx_grap_asset_class` (`asset_class`),
  KEY `idx_grap_recognition` (`recognition_status`),
  KEY `idx_grap_gl_account` (`gl_account_code`),
  KEY `idx_grap_cost_center` (`cost_center`),
  KEY `idx_grap_recognition_date` (`initial_recognition_date`),
  CONSTRAINT `spectrum_grap_data_ibfk_1` FOREIGN KEY (`information_object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_grap_depreciation_schedule`
--

DROP TABLE IF EXISTS `spectrum_grap_depreciation_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_grap_depreciation_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_data_id` int NOT NULL,
  `fiscal_year` int NOT NULL,
  `fiscal_period` varchar(20) DEFAULT NULL,
  `opening_value` decimal(15,2) DEFAULT NULL,
  `depreciation_amount` decimal(15,2) DEFAULT NULL,
  `closing_value` decimal(15,2) DEFAULT NULL,
  `calculated_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_deprec_period` (`grap_data_id`,`fiscal_year`,`fiscal_period`),
  KEY `idx_fiscal_year` (`fiscal_year`),
  CONSTRAINT `spectrum_grap_depreciation_schedule_ibfk_1` FOREIGN KEY (`grap_data_id`) REFERENCES `spectrum_grap_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_grap_journal`
--

DROP TABLE IF EXISTS `spectrum_grap_journal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_grap_journal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_data_id` int NOT NULL,
  `journal_date` date NOT NULL,
  `journal_type` varchar(50) NOT NULL,
  `debit_account` varchar(50) DEFAULT NULL,
  `credit_account` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text,
  `reference_number` varchar(100) DEFAULT NULL,
  `posted_by` int DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grap_data_id` (`grap_data_id`),
  KEY `idx_journal_date` (`journal_date`),
  KEY `idx_journal_type` (`journal_type`),
  CONSTRAINT `spectrum_grap_journal_ibfk_1` FOREIGN KEY (`grap_data_id`) REFERENCES `spectrum_grap_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_grap_revaluation_history`
--

DROP TABLE IF EXISTS `spectrum_grap_revaluation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_grap_revaluation_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grap_data_id` int NOT NULL,
  `revaluation_date` date NOT NULL,
  `previous_value` decimal(15,2) DEFAULT NULL,
  `new_value` decimal(15,2) DEFAULT NULL,
  `revaluation_surplus` decimal(15,2) DEFAULT NULL,
  `valuer_name` varchar(255) DEFAULT NULL,
  `valuer_credentials` varchar(255) DEFAULT NULL,
  `valuation_method` varchar(50) DEFAULT NULL,
  `valuation_report_reference` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grap_data_id` (`grap_data_id`),
  KEY `idx_reval_date` (`revaluation_date`),
  CONSTRAINT `spectrum_grap_revaluation_history_ibfk_1` FOREIGN KEY (`grap_data_id`) REFERENCES `spectrum_grap_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 17:01:32
