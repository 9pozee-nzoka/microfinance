/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: microfinance
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  CONSTRAINT `branches_status_check` CHECK (`status` in ('active','inactive'))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES
(1,'Head Office','HQ001','Nairobi','+254700000001','headoffice@mweelacash.co.ke','active','2026-06-03 12:14:59','2026-06-03 12:14:59');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_scores`
--

DROP TABLE IF EXISTS `credit_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `savings_history_score` int(11) NOT NULL DEFAULT 0,
  `repayment_history_score` int(11) NOT NULL DEFAULT 0,
  `income_stability_score` int(11) NOT NULL DEFAULT 0,
  `guarantor_strength_score` int(11) NOT NULL DEFAULT 0,
  `collateral_value_score` int(11) NOT NULL DEFAULT 0,
  `total_score` int(11) NOT NULL,
  `rating` varchar(20) NOT NULL,
  `positive_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`positive_factors`)),
  `negative_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`negative_factors`)),
  `recommendation` text DEFAULT NULL,
  `calculated_by` bigint(20) unsigned DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_scores_customer_id_foreign` (`customer_id`),
  KEY `credit_scores_calculated_by_foreign` (`calculated_by`),
  CONSTRAINT `credit_scores_calculated_by_foreign` FOREIGN KEY (`calculated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `credit_scores_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `credit_scores_rating_check` CHECK (`rating` in ('excellent','good','fair','poor','bad'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_scores`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_scores` WRITE;
/*!40000 ALTER TABLE `credit_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_scores` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customer_temp_passwords`
--

DROP TABLE IF EXISTS `customer_temp_passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_temp_passwords` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `temp_password` varchar(255) NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_temp_passwords_customer_id_foreign` (`customer_id`),
  KEY `customer_temp_passwords_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_temp_passwords_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_temp_passwords_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_temp_passwords`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customer_temp_passwords` WRITE;
/*!40000 ALTER TABLE `customer_temp_passwords` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_temp_passwords` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_number` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `kra_pin_number` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `education_level` varchar(50) DEFAULT NULL,
  `customer_type` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) NOT NULL DEFAULT 'Kenyan',
  `address` text DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `residential_county` varchar(100) DEFAULT NULL,
  `residential_sub_county` varchar(100) DEFAULT NULL,
  `residential_ward` varchar(100) DEFAULT NULL,
  `residential_estate` varchar(100) DEFAULT NULL,
  `residential_house_number` varchar(50) DEFAULT NULL,
  `employment_type` varchar(30) DEFAULT NULL,
  `employer_name` varchar(255) DEFAULT NULL,
  `monthly_income` decimal(15,2) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `next_of_kin_name` varchar(255) NOT NULL,
  `next_of_kin_phone` varchar(255) NOT NULL,
  `next_of_kin_relationship` varchar(255) NOT NULL,
  `next_of_kin_address` varchar(255) DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `relationship_officer_id` bigint(20) unsigned NOT NULL,
  `share_capital` decimal(15,2) NOT NULL DEFAULT 0.00,
  `savings_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_score` int(11) NOT NULL DEFAULT 0,
  `credit_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `qualified_amount` decimal(15,2) DEFAULT NULL,
  `id_front_path` varchar(255) DEFAULT NULL,
  `id_back_path` varchar(255) DEFAULT NULL,
  `passport_photo_path` varchar(255) DEFAULT NULL,
  `kra_pin_path` varchar(255) DEFAULT NULL,
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `kyc_verified_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `last_transaction_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_number_unique` (`customer_number`),
  UNIQUE KEY `customers_phone_number_unique` (`phone_number`),
  UNIQUE KEY `customers_id_number_unique` (`id_number`),
  KEY `customers_branch_id_foreign` (`branch_id`),
  KEY `customers_relationship_officer_id_foreign` (`relationship_officer_id`),
  KEY `customers_kyc_verified_by_foreign` (`kyc_verified_by`),
  KEY `customers_status_branch_id_index` (`status`,`branch_id`),
  KEY `customers_phone_number_index` (`phone_number`),
  KEY `customers_id_number_index` (`id_number`),
  KEY `customers_user_id_foreign` (`user_id`),
  CONSTRAINT `customers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `customers_kyc_verified_by_foreign` FOREIGN KEY (`kyc_verified_by`) REFERENCES `users` (`id`),
  CONSTRAINT `customers_relationship_officer_id_foreign` FOREIGN KEY (`relationship_officer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_gender_check` CHECK (`gender` in ('male','female','other')),
  CONSTRAINT `customers_employment_type_check` CHECK (`employment_type` in ('salaried','self_employed','business','farmer','other')),
  CONSTRAINT `customers_status_check` CHECK (`status` in ('pending','active','suspended','rejected','dormant')),
  CONSTRAINT `customers_marital_status_check` CHECK (`marital_status` is null or `marital_status` in ('single','married','divorced','widowed')),
  CONSTRAINT `customers_education_level_check` CHECK (`education_level` is null or `education_level` in ('none','primary','secondary','diploma','degree','masters','phd')),
  CONSTRAINT `customers_customer_type_check` CHECK (`customer_type` is null or `customer_type` in ('permanent','non_permanent'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `guarantors`
--

DROP TABLE IF EXISTS `guarantors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guarantors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `guarantor_customer_id` bigint(20) unsigned NOT NULL,
  `guaranteed_amount` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `sms_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sms_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guarantors_loan_id_foreign` (`loan_id`),
  KEY `guarantors_guarantor_customer_id_foreign` (`guarantor_customer_id`),
  CONSTRAINT `guarantors_guarantor_customer_id_foreign` FOREIGN KEY (`guarantor_customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `guarantors_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `guarantors_status_check` CHECK (`status` in ('pending','accepted','rejected','released'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guarantors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `guarantors` WRITE;
/*!40000 ALTER TABLE `guarantors` DISABLE KEYS */;
/*!40000 ALTER TABLE `guarantors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(6) NOT NULL,
  `reserved_at` int(11) DEFAULT NULL,
  `available_at` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loan_product_rates`
--

DROP TABLE IF EXISTS `loan_product_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_product_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_product_id` bigint(20) unsigned NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `term_weeks` int(11) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lpr_unique_combo` (`loan_product_id`,`principal_amount`,`term_weeks`),
  CONSTRAINT `loan_product_rates_loan_product_id_foreign` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_product_rates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loan_product_rates` WRITE;
/*!40000 ALTER TABLE `loan_product_rates` DISABLE KEYS */;
INSERT INTO `loan_product_rates` VALUES
(1,1,3000.00,4,20.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(2,1,4000.00,4,20.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(3,2,5000.00,4,20.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(4,2,5000.00,6,30.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(5,2,6000.00,4,25.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(6,2,6000.00,6,33.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(7,2,7000.00,4,28.50,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(8,2,7000.00,6,42.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(9,2,8000.00,4,25.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(10,2,8000.00,6,37.50,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(11,2,9000.00,4,22.22,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(12,2,9000.00,6,33.33,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(13,2,10000.00,4,20.00,'2026-06-03 08:15:51','2026-06-03 08:15:51'),
(14,2,10000.00,6,30.00,'2026-06-03 08:15:51','2026-06-03 08:15:51');
/*!40000 ALTER TABLE `loan_product_rates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loan_products`
--

DROP TABLE IF EXISTS `loan_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `interest_method` varchar(20) NOT NULL DEFAULT 'flat',
  `interest_rate` decimal(5,2) NOT NULL,
  `min_term_weeks` int(11) NOT NULL,
  `max_term_weeks` int(11) NOT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `processing_fee_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `insurance_fee_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `late_penalty_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grace_period_days` int(11) NOT NULL DEFAULT 0,
  `min_guarantors` int(11) NOT NULL DEFAULT 0,
  `min_savings_multiplier` decimal(5,2) NOT NULL DEFAULT 0.00,
  `requires_collateral` tinyint(1) NOT NULL DEFAULT 0,
  `collateral_type` varchar(20) NOT NULL DEFAULT 'none',
  `min_membership_months` int(11) NOT NULL DEFAULT 0,
  `min_credit_score` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_products_code_unique` (`code`),
  CONSTRAINT `loan_products_interest_method_check` CHECK (`interest_method` in ('flat','reducing_balance')),
  CONSTRAINT `loan_products_collateral_type_check` CHECK (`collateral_type` in ('none','land','vehicle','equipment','goods')),
  CONSTRAINT `loan_products_status_check` CHECK (`status` in ('active','inactive'))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_products`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loan_products` WRITE;
/*!40000 ALTER TABLE `loan_products` DISABLE KEYS */;
INSERT INTO `loan_products` VALUES
(1,'Chemsha','CHEMSHA','Short-term loan product: Chemsha','flat',20.00,4,4,3000.00,4000.00,2.00,1.00,1.00,3,1,0.20,0,'none',0,0,'active','2026-06-03 12:15:00','2026-06-03 12:15:00'),
(2,'Jijenge','JIJENGE','Short-term loan product: Jijenge','flat',20.00,4,6,5000.00,10000.00,2.00,1.00,1.00,3,1,0.20,0,'none',0,0,'active','2026-06-03 12:15:00','2026-06-03 12:15:00');
/*!40000 ALTER TABLE `loan_products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loan_repayments`
--

DROP TABLE IF EXISTS `loan_repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_repayments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `principal_portion` decimal(15,2) NOT NULL,
  `interest_portion` decimal(15,2) NOT NULL,
  `penalty_portion` decimal(15,2) NOT NULL DEFAULT 0.00,
  `excess_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(30) NOT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `mpesa_receipt_number` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `cheque_number` varchar(255) DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_repayments_schedule_id_foreign` (`schedule_id`),
  KEY `loan_repayments_customer_id_foreign` (`customer_id`),
  KEY `loan_repayments_received_by_foreign` (`received_by`),
  KEY `loan_repayments_branch_id_foreign` (`branch_id`),
  KEY `loan_repayments_confirmed_by_foreign` (`confirmed_by`),
  KEY `loan_repayments_loan_id_payment_method_index` (`loan_id`,`payment_method`),
  KEY `loan_repayments_transaction_reference_index` (`transaction_reference`),
  KEY `loan_repayments_mpesa_receipt_number_index` (`mpesa_receipt_number`),
  KEY `loan_repayments_created_at_index` (`created_at`),
  CONSTRAINT `loan_repayments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `loan_repayments_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_repayments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `loan_repayments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `loan_repayments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_repayments_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `repayment_schedules` (`id`),
  CONSTRAINT `loan_repayments_payment_method_check` CHECK (`payment_method` in ('mpesa','bank_transfer','cash','cheque','salary_deduction','standing_order')),
  CONSTRAINT `loan_repayments_status_check` CHECK (`status` in ('pending','confirmed','reversed','suspense'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_repayments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loan_repayments` WRITE;
/*!40000 ALTER TABLE `loan_repayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_repayments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_number` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `relationship_officer_id` bigint(20) unsigned NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_amount` decimal(15,2) NOT NULL,
  `processing_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `processing_fee_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `processing_fee_paid_at` timestamp NULL DEFAULT NULL,
  `insurance_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_repayable` decimal(15,2) NOT NULL,
  `term_weeks` int(11) NOT NULL,
  `weekly_installment` decimal(15,2) NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `purpose_description` text DEFAULT NULL,
  `collateral_description` varchar(255) DEFAULT NULL,
  `collateral_value` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `disbursed_by` bigint(20) unsigned DEFAULT NULL,
  `disbursed_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `disbursement_method` varchar(20) DEFAULT NULL,
  `disbursement_reference` varchar(255) DEFAULT NULL,
  `mpesa_receipt_number` varchar(255) DEFAULT NULL,
  `total_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_paid_principal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_paid_interest` decimal(15,2) NOT NULL DEFAULT 0.00,
  `outstanding_balance` decimal(15,2) NOT NULL,
  `arrears_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `days_in_arrears` int(11) NOT NULL DEFAULT 0,
  `risk_category` varchar(20) NOT NULL DEFAULT 'low',
  `application_date` date NOT NULL,
  `disbursement_date` date DEFAULT NULL,
  `first_due_date` date DEFAULT NULL,
  `maturity_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `is_restructured` tinyint(1) NOT NULL DEFAULT 0,
  `original_loan_id` bigint(20) unsigned DEFAULT NULL,
  `restructure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `processing_fee_paid_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loans_loan_number_unique` (`loan_number`),
  KEY `loans_product_id_foreign` (`product_id`),
  KEY `loans_branch_id_foreign` (`branch_id`),
  KEY `loans_relationship_officer_id_foreign` (`relationship_officer_id`),
  KEY `loans_reviewed_by_foreign` (`reviewed_by`),
  KEY `loans_approved_by_foreign` (`approved_by`),
  KEY `loans_disbursed_by_foreign` (`disbursed_by`),
  KEY `loans_original_loan_id_foreign` (`original_loan_id`),
  KEY `loans_status_branch_id_index` (`status`,`branch_id`),
  KEY `loans_customer_id_status_index` (`customer_id`,`status`),
  KEY `loans_loan_number_index` (`loan_number`),
  KEY `loans_disbursement_date_index` (`disbursement_date`),
  KEY `loans_processing_fee_paid_by_foreign` (`processing_fee_paid_by`),
  CONSTRAINT `loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loans_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `loans_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `loans_disbursed_by_foreign` FOREIGN KEY (`disbursed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loans_original_loan_id_foreign` FOREIGN KEY (`original_loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `loans_processing_fee_paid_by_foreign` FOREIGN KEY (`processing_fee_paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `loan_products` (`id`),
  CONSTRAINT `loans_relationship_officer_id_foreign` FOREIGN KEY (`relationship_officer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `loans_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loans_purpose_check` CHECK (`purpose` in ('business','education','medical','agriculture','home_improvement','other')),
  CONSTRAINT `loans_status_check` CHECK (`status` in ('pending','under_review','partially_approved','approved','rejected','disbursed','active','completed','defaulted','written_off','restructured')),
  CONSTRAINT `loans_disbursement_method_check` CHECK (`disbursement_method` is null or `disbursement_method` in ('mpesa','bank_transfer','cash','internal')),
  CONSTRAINT `loans_risk_category_check` CHECK (`risk_category` in ('low','medium','high','watch','default'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'026_05_24_000001_create_branches_table',1),
(5,'2026_05_24_000002_create_customers_table',1),
(6,'2026_05_24_000003_create_loan_products_table',1),
(7,'2026_05_24_000004_create_loans_table',1),
(8,'2026_05_24_000005_create_repayment_schedules_table',1),
(9,'2026_05_24_000006_create_loan_repayments_table',1),
(10,'2026_05_24_000007_create_transactions_table',1),
(11,'2026_05_24_000008_create_guarantors_table',1),
(12,'2026_05_24_000009_create_credit_scores_table',1),
(13,'2026_05_24_000010_create_suspense_accounts_table',1),
(14,'2026_05_24_000011_add_extra_fields_to_users_table',1),
(15,'2026_05_24_154642_create_personal_access_tokens_table',1),
(16,'2026_05_24_154905_create_permission_tables',1),
(17,'2026_05_25_000001_create_sms_logs_table',1),
(18,'2026_05_25_100001_add_user_id_to_customers_table',1),
(19,'2026_05_25_200001_create_mpesa_transactions_table',1),
(20,'2026_06_03_000001_add_customer_fields_to_customers_table',1),
(21,'2026_06_03_000002_create_loan_product_rates_table',1),
(22,'2026_06_03_000003_create_customer_temp_passwords_table',1),
(23,'2026_06_03_000004_add_processing_fee_paid_to_loans_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1),
(4,'App\\Models\\User',2);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mpesa_transactions`
--

DROP TABLE IF EXISTS `mpesa_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mpesa_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `loan_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `account_reference` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `merchant_request_id` varchar(255) DEFAULT NULL,
  `checkout_request_id` varchar(255) DEFAULT NULL,
  `conversation_id` varchar(255) DEFAULT NULL,
  `originator_conversation_id` varchar(255) DEFAULT NULL,
  `mpesa_receipt_number` varchar(255) DEFAULT NULL,
  `result_code` varchar(255) DEFAULT NULL,
  `result_desc` text DEFAULT NULL,
  `raw_callback` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_callback`)),
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `initiated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mpesa_transactions_customer_id_foreign` (`customer_id`),
  KEY `mpesa_transactions_initiated_by_foreign` (`initiated_by`),
  KEY `mpesa_transactions_type_status_index` (`type`,`status`),
  KEY `mpesa_transactions_checkout_request_id_index` (`checkout_request_id`),
  KEY `mpesa_transactions_conversation_id_index` (`conversation_id`),
  KEY `mpesa_transactions_loan_id_index` (`loan_id`),
  CONSTRAINT `mpesa_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mpesa_transactions_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mpesa_transactions_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mpesa_transactions_type_check` CHECK (`type` in ('stk_push','b2c')),
  CONSTRAINT `mpesa_transactions_status_check` CHECK (`status` in ('pending','completed','failed','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mpesa_transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mpesa_transactions` WRITE;
/*!40000 ALTER TABLE `mpesa_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mpesa_transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `repayment_schedules`
--

DROP TABLE IF EXISTS `repayment_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repayment_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_amount` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `principal_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repayment_schedules_loan_id_installment_number_unique` (`loan_id`,`installment_number`),
  KEY `repayment_schedules_due_date_status_index` (`due_date`,`status`),
  CONSTRAINT `repayment_schedules_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `repayment_schedules_status_check` CHECK (`status` in ('pending','partial','paid','overdue','waived'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repayment_schedules`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `repayment_schedules` WRITE;
/*!40000 ALTER TABLE `repayment_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `repayment_schedules` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'super_admin','web','2026-06-03 12:14:58','2026-06-03 12:14:58'),
(2,'admin','web','2026-06-03 12:14:58','2026-06-03 12:14:58'),
(3,'branch_manager','web','2026-06-03 12:14:58','2026-06-03 12:14:58'),
(4,'loan_officer','web','2026-06-03 12:14:58','2026-06-03 12:14:58'),
(5,'credit_committee','web','2026-06-03 12:14:58','2026-06-03 12:14:58'),
(6,'cashier','web','2026-06-03 12:14:58','2026-06-03 12:14:58'),
(7,'auditor','web','2026-06-03 12:14:59','2026-06-03 12:14:59');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('bLH00vuehcm4CUbgU9Y0mKfNydecMcD2M8VK7DvQ',1,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiI2RUNMblM1SWhhdm01WGQ2WDdSYzI1MFFxalRBM1U1OG1xR1k4cTNjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAxIiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1780480202),
('hpV5iQer1f8A0uQu1pQzfwNQaX1RyXIw24ynPk1G',NULL,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiJRWjBOZWFZaFJKVHpwaHJHMzF0UUM4TDNqb0VOczFVZG1RUUF1VFlzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAxXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1780479207),
('HWSrnNeZKwNwIuMp10DsXap1uwEpG7LODPepDE4C',NULL,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiJITVR6R3I3ckRDcURnaDFKamtlNk83SHRXUzZueHBDcGdJUmRkZGpQIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1780479252),
('isvK7Ey8Nk0qFwSEdpjmQViK1X6EQ2psaTz7bd2m',NULL,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiJQMUVaMDJ2YmJUNWpDVzNTQXRrRnp1S01tVTQ0a2xOb284MVBBSm0xIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAxXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1780479263),
('NhoeckW1kTW9T9zyoz1NnuNWgs7FQVv5psBd94P6',NULL,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiJMWnVsS2VLWDc2QmpLUjZxYXVhb00wRjRra3FBeEpBSjBIWHBFT1hLIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1780479230),
('OsQ0F9y0ezWxsJMOYt7IrorKJ9w3AAHFyLjRhqRf',NULL,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiJZSWVTU3paOFRyT0RDVTNWRnZvSlhRaWpKZG5YckU0T3ZYMWd3RjFZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAxXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1780479218),
('RG1xcV0O9EBQFGwNE7STaInyNO2zZy18wMlHu3Bj',1,'127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJVZ3dYcFpsVVFNazNxajB3Z3dOdDB6Ukx3RnVrWnBhYXJDQlJrcUNhIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDEifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAxXC9sb2FuLXByb2R1Y3RzIiwicm91dGUiOiJsb2FuLXByb2R1Y3RzLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1780479990),
('RiKRpINfHgTdQo4eNM469YTtCuvt0b0HbcVsZPpv',1,'127.0.0.1','curl/8.18.0','eyJfdG9rZW4iOiJYODJCQmVCSkp3WUZSMG9TZTBzNVRmNmZyUG1VZ1JVWDlES1NINlJ4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAxXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1780479278);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `loan_id` bigint(20) unsigned DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_type` varchar(30) NOT NULL DEFAULT 'custom',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `at_message_id` varchar(255) DEFAULT NULL,
  `at_status` varchar(255) DEFAULT NULL,
  `at_cost` decimal(8,4) DEFAULT NULL,
  `at_response` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `is_bulk` tinyint(1) NOT NULL DEFAULT 0,
  `bulk_batch_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_logs_created_by_foreign` (`created_by`),
  KEY `sms_logs_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `sms_logs_customer_id_message_type_index` (`customer_id`,`message_type`),
  KEY `sms_logs_loan_id_index` (`loan_id`),
  KEY `sms_logs_bulk_batch_id_index` (`bulk_batch_id`),
  CONSTRAINT `sms_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_logs_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_logs_message_type_check` CHECK (`message_type` in ('payment_reminder','overdue_notice','payment_received','loan_approved','loan_disbursed','custom')),
  CONSTRAINT `sms_logs_status_check` CHECK (`status` in ('pending','sent','failed','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sms_logs` WRITE;
/*!40000 ALTER TABLE `sms_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sms_schedules`
--

DROP TABLE IF EXISTS `sms_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_type` varchar(30) NOT NULL,
  `trigger_days` int(11) NOT NULL DEFAULT 0,
  `target` varchar(30) NOT NULL DEFAULT 'all_active',
  `target_product_id` bigint(20) unsigned DEFAULT NULL,
  `target_branch_id` bigint(20) unsigned DEFAULT NULL,
  `message_template` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `total_sent` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_schedules_target_product_id_foreign` (`target_product_id`),
  KEY `sms_schedules_target_branch_id_foreign` (`target_branch_id`),
  KEY `sms_schedules_created_by_foreign` (`created_by`),
  CONSTRAINT `sms_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_schedules_target_branch_id_foreign` FOREIGN KEY (`target_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_schedules_target_product_id_foreign` FOREIGN KEY (`target_product_id`) REFERENCES `loan_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_schedules_trigger_type_check` CHECK (`trigger_type` in ('days_before_due','days_after_due','on_due_date','manual')),
  CONSTRAINT `sms_schedules_target_check` CHECK (`target` in ('all_active','overdue','due_today','specific_product','specific_branch')),
  CONSTRAINT `sms_schedules_status_check` CHECK (`status` in ('active','paused','draft'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_schedules`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sms_schedules` WRITE;
/*!40000 ALTER TABLE `sms_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_schedules` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `suspense_accounts`
--

DROP TABLE IF EXISTS `suspense_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suspense_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(255) NOT NULL,
  `source` varchar(20) NOT NULL,
  `external_reference` varchar(255) NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `bill_reference` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `matched_customer_id` bigint(20) unsigned DEFAULT NULL,
  `matched_loan_id` bigint(20) unsigned DEFAULT NULL,
  `matched_repayment_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'unmatched',
  `resolution_notes` text DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suspense_accounts_reference_number_unique` (`reference_number`),
  KEY `suspense_accounts_matched_customer_id_foreign` (`matched_customer_id`),
  KEY `suspense_accounts_matched_loan_id_foreign` (`matched_loan_id`),
  KEY `suspense_accounts_matched_repayment_id_foreign` (`matched_repayment_id`),
  KEY `suspense_accounts_resolved_by_foreign` (`resolved_by`),
  KEY `suspense_accounts_status_source_index` (`status`,`source`),
  KEY `suspense_accounts_external_reference_index` (`external_reference`),
  CONSTRAINT `suspense_accounts_matched_customer_id_foreign` FOREIGN KEY (`matched_customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `suspense_accounts_matched_loan_id_foreign` FOREIGN KEY (`matched_loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `suspense_accounts_matched_repayment_id_foreign` FOREIGN KEY (`matched_repayment_id`) REFERENCES `loan_repayments` (`id`),
  CONSTRAINT `suspense_accounts_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `suspense_accounts_source_check` CHECK (`source` in ('mpesa','bank','cash')),
  CONSTRAINT `suspense_accounts_status_check` CHECK (`status` in ('unmatched','matched','refunded','escalated'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suspense_accounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `suspense_accounts` WRITE;
/*!40000 ALTER TABLE `suspense_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `suspense_accounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `loan_id` bigint(20) unsigned DEFAULT NULL,
  `repayment_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_type` varchar(30) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `source` varchar(20) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `bill_reference` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `failure_reason` text DEFAULT NULL,
  `is_reconciled` tinyint(1) NOT NULL DEFAULT 0,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `narration` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_transaction_number_unique` (`transaction_number`),
  KEY `transactions_loan_id_foreign` (`loan_id`),
  KEY `transactions_repayment_id_foreign` (`repayment_id`),
  KEY `transactions_created_by_foreign` (`created_by`),
  KEY `transactions_branch_id_foreign` (`branch_id`),
  KEY `transactions_customer_id_transaction_type_index` (`customer_id`,`transaction_type`),
  KEY `transactions_external_reference_index` (`external_reference`),
  KEY `transactions_status_is_reconciled_index` (`status`,`is_reconciled`),
  KEY `transactions_created_at_index` (`created_at`),
  CONSTRAINT `transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `transactions_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `transactions_repayment_id_foreign` FOREIGN KEY (`repayment_id`) REFERENCES `loan_repayments` (`id`),
  CONSTRAINT `transactions_transaction_type_check` CHECK (`transaction_type` in ('loan_disbursement','loan_repayment','savings_deposit','savings_withdrawal','share_capital','processing_fee','insurance_fee','penalty','interest_income','refund','adjustment')),
  CONSTRAINT `transactions_direction_check` CHECK (`direction` in ('debit','credit')),
  CONSTRAINT `transactions_source_check` CHECK (`source` is null or `source` in ('mpesa','bank','cash','internal','system')),
  CONSTRAINT `transactions_status_check` CHECK (`status` in ('pending','completed','failed','reversed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_employee_id_unique` (`employee_id`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `users_status_check` CHECK (`status` in ('active','inactive','suspended'))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'System Administrator','pauljohns730@gmail.com',NULL,'$2y$12$cvsb5A32KRlERMaxyJsjROIOPhPNzt/bf/jzTWgcAdJx2.HvxljNy',NULL,'2026-06-03 12:14:59','2026-06-03 12:14:59','+254746186990',1,'EMP-001','System Administrator','active',NULL,NULL,NULL,NULL,NULL),
(2,'Relationship Officer','josephann62@gmail.com',NULL,'$2y$12$NGMatSYSnJY5NENovyQ2MOTR/b.Kcwexl.HcOVzci2V9P/ahFuAD6',NULL,'2026-06-03 12:14:59','2026-06-03 12:14:59','+254711111111',1,'EMP-002','Relationship Officer','active',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-03  7:55:27
