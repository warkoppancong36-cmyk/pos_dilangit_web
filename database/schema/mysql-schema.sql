/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `condition` enum('excellent','good','fair','poor','damaged') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `status` enum('active','inactive','maintenance','disposed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `supplier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warranty_until` date DEFAULT NULL,
  `assigned_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_asset_code_unique` (`asset_code`),
  KEY `assets_category_status_index` (`category`,`status`),
  KEY `assets_location_department_index` (`location`,`department`),
  KEY `assets_asset_code_index` (`asset_code`),
  KEY `assets_assigned_to_index` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_registers` (
  `id_cash_register` bigint unsigned NOT NULL AUTO_INCREMENT,
  `register_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `register_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `current_cash_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `supported_payment_methods` json NOT NULL,
  `hardware_config` json DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cash_register`),
  UNIQUE KEY `cash_registers_register_code_unique` (`register_code`),
  KEY `cash_registers_created_by_foreign` (`created_by`),
  KEY `cash_registers_updated_by_foreign` (`updated_by`),
  KEY `cash_registers_active_index` (`active`),
  KEY `cash_registers_register_code_index` (`register_code`),
  CONSTRAINT `cash_registers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_registers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_transactions` (
  `id_cash_transaction` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cash_register` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_shift` bigint unsigned DEFAULT NULL,
  `id_order` bigint unsigned DEFAULT NULL,
  `type` enum('in','out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('sale','manual','initial','adjustment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `transaction_date` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cash_transaction`),
  UNIQUE KEY `cash_transactions_reference_number_unique` (`reference_number`),
  KEY `cash_transactions_id_user_foreign` (`id_user`),
  KEY `cash_transactions_id_shift_foreign` (`id_shift`),
  KEY `cash_transactions_id_order_foreign` (`id_order`),
  KEY `cash_transactions_id_cash_register_transaction_date_index` (`id_cash_register`,`transaction_date`),
  KEY `cash_transactions_type_index` (`type`),
  KEY `cash_transactions_source_index` (`source`),
  KEY `cash_transactions_transaction_date_index` (`transaction_date`),
  CONSTRAINT `cash_transactions_id_cash_register_foreign` FOREIGN KEY (`id_cash_register`) REFERENCES `cash_registers` (`id_cash_register`) ON DELETE CASCADE,
  CONSTRAINT `cash_transactions_id_order_foreign` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`) ON DELETE SET NULL,
  CONSTRAINT `cash_transactions_id_shift_foreign` FOREIGN KEY (`id_shift`) REFERENCES `shifts` (`id_shift`) ON DELETE SET NULL,
  CONSTRAINT `cash_transactions_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id_category` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_category`),
  KEY `categories_created_by_foreign` (`created_by`),
  KEY `categories_updated_by_foreign` (`updated_by`),
  KEY `categories_deleted_by_foreign` (`deleted_by`),
  KEY `categories_active_sort_order_index` (`active`,`sort_order`),
  CONSTRAINT `categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_loyalty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_loyalty` (
  `id_customer_loyalty` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_customer` bigint unsigned NOT NULL,
  `id_loyalty_program` bigint unsigned NOT NULL,
  `current_points` int NOT NULL DEFAULT '0',
  `lifetime_points` int NOT NULL DEFAULT '0',
  `redeemed_points` int NOT NULL DEFAULT '0',
  `current_tier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tier_achieved_date` date DEFAULT NULL,
  `tier_expiry_date` date DEFAULT NULL,
  `visits_count` int NOT NULL DEFAULT '0',
  `total_spent` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_activity` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `achievements` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_customer_loyalty`),
  UNIQUE KEY `customer_loyalty_id_customer_id_loyalty_program_unique` (`id_customer`,`id_loyalty_program`),
  KEY `customer_loyalty_id_loyalty_program_foreign` (`id_loyalty_program`),
  KEY `customer_loyalty_current_points_index` (`current_points`),
  KEY `customer_loyalty_current_tier_index` (`current_tier`),
  KEY `customer_loyalty_active_index` (`active`),
  CONSTRAINT `customer_loyalty_id_customer_foreign` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id_customer`) ON DELETE CASCADE,
  CONSTRAINT `customer_loyalty_id_loyalty_program_foreign` FOREIGN KEY (`id_loyalty_program`) REFERENCES `loyalty_programs` (`id_loyalty_program`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id_customer` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `total_visits` int NOT NULL DEFAULT '0',
  `total_spent` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_visit` timestamp NULL DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_customer`),
  KEY `customers_created_by_foreign` (`created_by`),
  KEY `customers_updated_by_foreign` (`updated_by`),
  KEY `customers_deleted_by_foreign` (`deleted_by`),
  KEY `customers_active_index` (`active`),
  KEY `customers_customer_code_index` (`customer_code`),
  KEY `customers_phone_index` (`phone`),
  KEY `customers_email_index` (`email`),
  CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discounts` (
  `id_discount` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('percentage','fixed_amount','buy_x_get_y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `minimum_amount` decimal(15,2) DEFAULT NULL,
  `maximum_discount` decimal(15,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_customer` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT '0',
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `applicable_products` json DEFAULT NULL,
  `applicable_categories` json DEFAULT NULL,
  `customer_groups` json DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_discount`),
  UNIQUE KEY `discounts_code_unique` (`code`),
  KEY `discounts_created_by_foreign` (`created_by`),
  KEY `discounts_updated_by_foreign` (`updated_by`),
  KEY `discounts_active_valid_from_valid_until_index` (`active`,`valid_from`,`valid_until`),
  KEY `discounts_code_index` (`code`),
  KEY `discounts_type_index` (`type`),
  CONSTRAINT `discounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `id_inventory` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_product` bigint unsigned DEFAULT NULL,
  `id_variant` bigint unsigned DEFAULT NULL,
  `id_item` bigint unsigned DEFAULT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `reserved_stock` int NOT NULL DEFAULT '0',
  `available_stock` int GENERATED ALWAYS AS ((`current_stock` - `reserved_stock`)) STORED,
  `reorder_level` int NOT NULL DEFAULT '0',
  `max_stock_level` int DEFAULT NULL,
  `average_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_restocked` timestamp NULL DEFAULT NULL,
  `last_counted` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_inventory`),
  UNIQUE KEY `inventory_id_product_id_variant_unique` (`id_product`,`id_variant`),
  UNIQUE KEY `inventory_id_product_unique` (`id_product`),
  UNIQUE KEY `inventory_id_variant_unique` (`id_variant`),
  UNIQUE KEY `inventory_id_item_unique` (`id_item`),
  KEY `inventory_created_by_foreign` (`created_by`),
  KEY `inventory_updated_by_foreign` (`updated_by`),
  KEY `inventory_current_stock_index` (`current_stock`),
  KEY `inventory_reorder_level_index` (`reorder_level`),
  KEY `inventory_id_item_index` (`id_item`),
  CONSTRAINT `inventory_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `items` (`id_item`) ON DELETE CASCADE,
  CONSTRAINT `inventory_id_product_foreign` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE,
  CONSTRAINT `inventory_id_variant_foreign` FOREIGN KEY (`id_variant`) REFERENCES `variants` (`id_variant`) ON DELETE CASCADE,
  CONSTRAINT `inventory_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id_movement` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_inventory` bigint unsigned NOT NULL,
  `movement_type` enum('in','out','adjustment','transfer','waste','return') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `stock_before` int NOT NULL,
  `stock_after` int NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `batch_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `movement_date` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_movement`),
  KEY `inventory_movements_created_by_foreign` (`created_by`),
  KEY `inventory_movements_id_inventory_movement_date_index` (`id_inventory`,`movement_date`),
  KEY `inventory_movements_movement_type_index` (`movement_type`),
  KEY `inventory_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_movements_movement_date_index` (`movement_date`),
  CONSTRAINT `inventory_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_movements_id_inventory_foreign` FOREIGN KEY (`id_inventory`) REFERENCES `inventory` (`id_inventory`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_per_unit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `storage_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `is_takeaway_only` tinyint(1) NOT NULL DEFAULT '0',
  `is_delivery` tinyint(1) NOT NULL DEFAULT '0',
  `is_takeaway` tinyint(1) NOT NULL DEFAULT '0',
  `properties` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_item`),
  UNIQUE KEY `items_item_code_unique` (`item_code`),
  KEY `items_created_by_foreign` (`created_by`),
  KEY `items_updated_by_foreign` (`updated_by`),
  KEY `items_active_index` (`active`),
  KEY `items_item_code_index` (`item_code`),
  CONSTRAINT `items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_programs` (
  `id_loyalty_program` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('points','visits','amount_spent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `earning_rules` json NOT NULL,
  `redemption_rules` json NOT NULL,
  `tier_threshold` int DEFAULT NULL,
  `tier_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tier_discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `benefits` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_loyalty_program`),
  KEY `loyalty_programs_created_by_foreign` (`created_by`),
  KEY `loyalty_programs_updated_by_foreign` (`updated_by`),
  KEY `loyalty_programs_active_index` (`active`),
  KEY `loyalty_programs_type_index` (`type`),
  KEY `loyalty_programs_tier_threshold_index` (`tier_threshold`),
  CONSTRAINT `loyalty_programs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loyalty_programs_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id_order_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_order` bigint unsigned NOT NULL,
  `id_product` bigint unsigned DEFAULT NULL,
  `id_variant` bigint unsigned DEFAULT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `subtotal_before_discount` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `customizations` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','preparing','ready','served') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `prepared_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_order_item`),
  KEY `order_items_id_order_index` (`id_order`),
  KEY `order_items_status_index` (`status`),
  KEY `order_items_id_product_index` (`id_product`),
  KEY `order_items_id_variant_index` (`id_variant`),
  CONSTRAINT `order_items_id_order_foreign` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`) ON DELETE CASCADE,
  CONSTRAINT `order_items_id_product_foreign` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE SET NULL,
  CONSTRAINT `order_items_id_variant_foreign` FOREIGN KEY (`id_variant`) REFERENCES `variants` (`id_variant`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id_order` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_customer` bigint unsigned DEFAULT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_shift` bigint unsigned DEFAULT NULL,
  `order_type` enum('dine_in','takeaway','delivery') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','pending','preparing','ready','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `table_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_count` int NOT NULL DEFAULT '1',
  `subtotal` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `service_charge` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `customer_info` json DEFAULT NULL,
  `order_date` timestamp NOT NULL,
  `prepared_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_order`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_created_by_foreign` (`created_by`),
  KEY `orders_updated_by_foreign` (`updated_by`),
  KEY `orders_status_order_date_index` (`status`,`order_date`),
  KEY `orders_id_customer_index` (`id_customer`),
  KEY `orders_id_user_index` (`id_user`),
  KEY `orders_order_number_index` (`order_number`),
  KEY `orders_order_type_index` (`order_type`),
  KEY `orders_id_shift_foreign` (`id_shift`),
  CONSTRAINT `orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_id_customer_foreign` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id_customer`) ON DELETE SET NULL,
  CONSTRAINT `orders_id_shift_foreign` FOREIGN KEY (`id_shift`) REFERENCES `shifts` (`id_shift`) ON DELETE SET NULL,
  CONSTRAINT `orders_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id_payment` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_order` bigint unsigned NOT NULL,
  `payment_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('cash','credit_card','debit_card','qris','gopay','ovo','dana','shopeepay','bank_transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `cash_received` decimal(15,2) DEFAULT NULL,
  `change_amount` decimal(15,2) DEFAULT NULL,
  `reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','paid','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NOT NULL,
  `payment_details` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_payment`),
  UNIQUE KEY `payments_payment_number_unique` (`payment_number`),
  KEY `payments_processed_by_foreign` (`processed_by`),
  KEY `payments_id_order_index` (`id_order`),
  KEY `payments_payment_method_index` (`payment_method`),
  KEY `payments_status_index` (`status`),
  KEY `payments_payment_date_index` (`payment_date`),
  KEY `payments_payment_number_index` (`payment_number`),
  CONSTRAINT `payments_id_order_foreign` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`) ON DELETE CASCADE,
  CONSTRAINT `payments_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id_permission` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  KEY `permissions_module_action_index` (`module`,`action`),
  KEY `permissions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
DROP TABLE IF EXISTS `ppn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ppn` (
  `id_ppn` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ppn`),
  KEY `m_ppn_created_by_foreign` (`created_by`),
  KEY `m_ppn_updated_by_foreign` (`updated_by`),
  KEY `m_ppn_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `m_ppn_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `m_ppn_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `m_ppn_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_items` (
  `id_product_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity_needed` decimal(15,3) NOT NULL,
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_per_unit` decimal(15,2) DEFAULT NULL,
  `is_critical` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_product_item`),
  UNIQUE KEY `product_items_product_id_item_id_unique` (`product_id`,`item_id`),
  KEY `product_items_created_by_foreign` (`created_by`),
  KEY `product_items_updated_by_foreign` (`updated_by`),
  KEY `product_items_product_id_index` (`product_id`),
  KEY `product_items_item_id_index` (`item_id`),
  KEY `product_items_is_critical_index` (`is_critical`),
  CONSTRAINT `product_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id_item`) ON DELETE CASCADE,
  CONSTRAINT `product_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id_product`) ON DELETE CASCADE,
  CONSTRAINT `product_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_recipe_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_recipe_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_recipe_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_recipe_items_product_recipe_id_item_id_unique` (`product_recipe_id`,`item_id`),
  KEY `product_recipe_items_item_id_foreign` (`item_id`),
  KEY `product_recipe_items_product_recipe_id_index` (`product_recipe_id`),
  CONSTRAINT `product_recipe_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id_item`) ON DELETE CASCADE,
  CONSTRAINT `product_recipe_items_product_recipe_id_foreign` FOREIGN KEY (`product_recipe_id`) REFERENCES `product_recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_recipes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `portion_size` int DEFAULT NULL,
  `portion_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preparation_time` int DEFAULT NULL,
  `difficulty_level` enum('easy','medium','hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `instructions` json DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id_product` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `cost_per_unit` decimal(15,2) DEFAULT NULL COMMENT 'HPP/Cost per unit',
  `hpp_method` enum('current','latest','average') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HPP calculation method',
  `hpp_calculated_at` timestamp NULL DEFAULT NULL COMMENT 'When HPP was last calculated',
  `cost` decimal(15,2) DEFAULT NULL,
  `markup_percentage` decimal(5,2) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` json DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_product`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  KEY `products_active_status_index` (`active`,`status`),
  KEY `products_featured_index` (`featured`),
  KEY `products_category_id_index` (`category_id`),
  KEY `products_sku_index` (`sku`),
  KEY `products_barcode_index` (`barcode`),
  KEY `products_slug_index` (`slug`),
  KEY `products_price_index` (`price`),
  KEY `products_brand_index` (`brand`),
  KEY `products_created_at_index` (`created_at`),
  KEY `products_created_by_foreign` (`created_by`),
  KEY `products_updated_by_foreign` (`updated_by`),
  KEY `products_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id_category`) ON DELETE CASCADE,
  CONSTRAINT `products_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id_promotion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('happy_hour','buy_one_get_one','combo_deal','member_discount','seasonal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `promotion_rules` json NOT NULL,
  `discount_value` decimal(15,2) DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `valid_days` json DEFAULT NULL,
  `valid_time_from` time DEFAULT NULL,
  `valid_time_until` time DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int NOT NULL DEFAULT '0',
  `applicable_products` json DEFAULT NULL,
  `applicable_categories` json DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `banner_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_promotion`),
  KEY `promotions_created_by_foreign` (`created_by`),
  KEY `promotions_updated_by_foreign` (`updated_by`),
  KEY `promotions_active_valid_from_valid_until_index` (`active`,`valid_from`,`valid_until`),
  KEY `promotions_type_index` (`type`),
  KEY `promotions_priority_index` (`priority`),
  CONSTRAINT `promotions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id_purchase_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned DEFAULT NULL,
  `item_id` bigint unsigned DEFAULT NULL,
  `quantity_ordered` decimal(15,3) NOT NULL,
  `quantity_received` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `unit_cost` decimal(15,2) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `status` enum('pending','partial','received','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quality_check` json DEFAULT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_purchase_item`),
  KEY `purchase_items_received_by_foreign` (`received_by`),
  KEY `purchase_items_purchase_id_index` (`purchase_id`),
  KEY `purchase_items_item_id_index` (`item_id`),
  KEY `purchase_items_status_index` (`status`),
  KEY `purchase_items_expected_delivery_date_index` (`expected_delivery_date`),
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id_purchase`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id_purchase` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `status` enum('pending','ordered','received','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_purchase`),
  UNIQUE KEY `purchases_purchase_number_unique` (`purchase_number`),
  KEY `purchases_created_by_foreign` (`created_by`),
  KEY `purchases_updated_by_foreign` (`updated_by`),
  KEY `purchases_purchase_date_index` (`purchase_date`),
  KEY `purchases_status_index` (`status`),
  KEY `purchases_supplier_id_index` (`supplier_id`),
  CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id_supplier`) ON DELETE CASCADE,
  CONSTRAINT `purchases_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id_permission`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `permissions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id_shift` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_cash_register` bigint unsigned DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `opening_cash` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closing_cash` decimal(15,2) DEFAULT NULL,
  `expected_cash` decimal(15,2) DEFAULT NULL,
  `cash_difference` decimal(15,2) DEFAULT NULL,
  `total_orders` int NOT NULL DEFAULT '0',
  `total_sales` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','closed','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `opening_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `closing_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payment_summary` json DEFAULT NULL,
  `opened_by` bigint unsigned NOT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_shift`),
  KEY `shifts_opened_by_foreign` (`opened_by`),
  KEY `shifts_closed_by_foreign` (`closed_by`),
  KEY `shifts_id_user_status_index` (`id_user`,`status`),
  KEY `shifts_start_time_index` (`start_time`),
  KEY `shifts_status_index` (`status`),
  KEY `shifts_id_cash_register_foreign` (`id_cash_register`),
  CONSTRAINT `shifts_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_id_cash_register_foreign` FOREIGN KEY (`id_cash_register`) REFERENCES `cash_registers` (`id_cash_register`) ON DELETE SET NULL,
  CONSTRAINT `shifts_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifts_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id_movement` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_product` bigint unsigned NOT NULL,
  `id_variant` bigint unsigned DEFAULT NULL,
  `movement_type` enum('in','out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` enum('purchase','sale','adjustment','return_customer','return_supplier','expired','transfer_in','transfer_out','production','waste') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `stock_before` int NOT NULL,
  `stock_after` int NOT NULL,
  `reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_movement`),
  KEY `stock_movements_created_by_foreign` (`created_by`),
  KEY `stock_movements_id_product_movement_type_index` (`id_product`,`movement_type`),
  KEY `stock_movements_id_variant_movement_type_index` (`id_variant`,`movement_type`),
  KEY `stock_movements_reason_index` (`reason`),
  KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `stock_movements_created_at_index` (`created_at`),
  CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_id_product_foreign` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_id_variant_foreign` FOREIGN KEY (`id_variant`) REFERENCES `variants` (`id_variant`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id_supplier` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_supplier`),
  UNIQUE KEY `suppliers_code_unique` (`code`),
  KEY `suppliers_created_by_foreign` (`created_by`),
  KEY `suppliers_updated_by_foreign` (`updated_by`),
  KEY `suppliers_deleted_by_foreign` (`deleted_by`),
  KEY `suppliers_code_index` (`code`),
  CONSTRAINT `suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suppliers_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suppliers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `url` varchar(1000) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `user_role` varchar(255) DEFAULT NULL,
  `token_id` varchar(255) DEFAULT NULL,
  `request_headers` json DEFAULT NULL,
  `request_payload` longtext,
  `request_ip` varchar(45) NOT NULL,
  `user_agent` text,
  `response_status` int NOT NULL,
  `response_payload` longtext,
  `response_headers` json DEFAULT NULL,
  `execution_time` decimal(8,3) DEFAULT NULL,
  `memory_usage` bigint DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `platform` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `transaction_type` varchar(255) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` varchar(255) DEFAULT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '1',
  `error_message` text,
  `stack_trace` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_date` date NOT NULL DEFAULT (curdate()),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`created_date`),
  UNIQUE KEY `unique_transaction_id` (`transaction_id`,`created_date`),
  KEY `idx_user_created` (`user_id`,`created_date`),
  KEY `idx_transaction_type_created` (`transaction_type`,`created_date`),
  KEY `idx_endpoint_method` (`endpoint`,`method`),
  KEY `idx_successful_created` (`is_successful`,`created_date`),
  KEY `idx_response_status_created` (`response_status`,`created_date`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
/*!50500 PARTITION BY RANGE  COLUMNS(created_date)
(PARTITION p202408 VALUES LESS THAN ('2024-09-01') ENGINE = InnoDB,
 PARTITION p202409 VALUES LESS THAN ('2024-10-01') ENGINE = InnoDB,
 PARTITION p202410 VALUES LESS THAN ('2024-11-01') ENGINE = InnoDB,
 PARTITION p202411 VALUES LESS THAN ('2024-12-01') ENGINE = InnoDB,
 PARTITION p202412 VALUES LESS THAN ('2025-01-01') ENGINE = InnoDB,
 PARTITION p202501 VALUES LESS THAN ('2025-02-01') ENGINE = InnoDB,
 PARTITION p202502 VALUES LESS THAN ('2025-03-01') ENGINE = InnoDB,
 PARTITION p202503 VALUES LESS THAN ('2025-04-01') ENGINE = InnoDB,
 PARTITION p202504 VALUES LESS THAN ('2025-05-01') ENGINE = InnoDB,
 PARTITION p202505 VALUES LESS THAN ('2025-06-01') ENGINE = InnoDB,
 PARTITION p202506 VALUES LESS THAN ('2025-07-01') ENGINE = InnoDB,
 PARTITION p202507 VALUES LESS THAN ('2025-08-01') ENGINE = InnoDB,
 PARTITION p202508 VALUES LESS THAN ('2025-09-01') ENGINE = InnoDB,
 PARTITION p202509 VALUES LESS THAN ('2025-10-01') ENGINE = InnoDB,
 PARTITION p202510 VALUES LESS THAN ('2025-11-01') ENGINE = InnoDB,
 PARTITION p202511 VALUES LESS THAN ('2025-12-01') ENGINE = InnoDB,
 PARTITION p202512 VALUES LESS THAN ('2026-01-01') ENGINE = InnoDB,
 PARTITION p_future VALUES LESS THAN (MAXVALUE) ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_type` enum('login','logout','failed_login') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `device_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '1',
  `failure_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `login_at` timestamp NOT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `session_duration` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_login_logs_user_id_login_at_index` (`user_id`,`login_at`),
  KEY `user_login_logs_login_type_login_at_index` (`login_type`,`login_at`),
  KEY `user_login_logs_ip_address_index` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `type` enum('grant','deny') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'grant',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `granted_by` bigint unsigned NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_permission_id_unique` (`user_id`,`permission_id`),
  KEY `user_permissions_permission_id_foreign` (`permission_id`),
  KEY `user_permissions_granted_by_foreign` (`granted_by`),
  KEY `user_permissions_user_id_type_index` (`user_id`,`type`),
  KEY `user_permissions_expires_at_index` (`expires_at`),
  CONSTRAINT `user_permissions_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `user_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id_permission`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_device` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_attempts` int NOT NULL DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `variant_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_attributes` (
  `id_variant_attribute` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_product` bigint unsigned NOT NULL,
  `attribute_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'select',
  `attribute_values` json NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_variant_attribute`),
  UNIQUE KEY `product_attribute_unique` (`id_product`,`attribute_name`),
  KEY `variant_attributes_created_by_foreign` (`created_by`),
  KEY `variant_attributes_updated_by_foreign` (`updated_by`),
  KEY `variant_attributes_id_product_active_index` (`id_product`,`active`),
  KEY `variant_attributes_sort_order_index` (`sort_order`),
  CONSTRAINT `variant_attributes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `variant_attributes_id_product_foreign` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE,
  CONSTRAINT `variant_attributes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `variant_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_items` (
  `id_variant_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_variant` bigint unsigned NOT NULL,
  `id_item` bigint unsigned NOT NULL,
  `quantity_needed` decimal(10,3) NOT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_per_unit` decimal(15,2) DEFAULT NULL,
  `is_critical` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_variant_item`),
  UNIQUE KEY `variant_item_unique` (`id_variant`,`id_item`),
  KEY `variant_items_created_by_foreign` (`created_by`),
  KEY `variant_items_updated_by_foreign` (`updated_by`),
  KEY `variant_items_id_variant_active_index` (`id_variant`,`active`),
  KEY `variant_items_id_item_index` (`id_item`),
  CONSTRAINT `variant_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `variant_items_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `items` (`id_item`) ON DELETE CASCADE,
  CONSTRAINT `variant_items_id_variant_foreign` FOREIGN KEY (`id_variant`) REFERENCES `variants` (`id_variant`) ON DELETE CASCADE,
  CONSTRAINT `variant_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variants` (
  `id_variant` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_product` bigint unsigned NOT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_values` json NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `hpp_method` enum('current','latest','average') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HPP calculation method',
  `hpp_calculated_at` timestamp NULL DEFAULT NULL COMMENT 'When HPP was last calculated',
  `barcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_variant`),
  UNIQUE KEY `variants_sku_unique` (`sku`),
  KEY `variants_created_by_foreign` (`created_by`),
  KEY `variants_updated_by_foreign` (`updated_by`),
  KEY `variants_deleted_by_foreign` (`deleted_by`),
  KEY `variants_id_product_active_index` (`id_product`,`active`),
  KEY `variants_sku_index` (`sku`),
  CONSTRAINT `variants_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `variants_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `variants_id_product_foreign` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE,
  CONSTRAINT `variants_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_08_02_141854_create_table_ppn',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_08_02_145452_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_08_02_150200_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_08_02_150235_add_pos_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_08_02_150507_create_user_login_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_08_02_170250_create_categories_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_08_02_170259_create_suppliers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_08_02_170307_create_products_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_08_02_170314_create_variants_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_08_02_170320_create_customers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_08_02_170334_create_orders_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_08_02_170341_create_order_items_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_08_02_170347_create_payments_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_08_02_170354_create_inventory_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_08_02_170402_create_inventory_movements_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_08_02_170408_create_discounts_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_08_02_170415_create_promotions_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_08_02_170423_create_loyalty_programs_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_08_02_170430_create_customer_loyalty_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_08_02_170437_create_shifts_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_08_02_170443_create_cash_registers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_08_02_171117_add_shifts_foreign_key_to_orders_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_08_02_171406_add_cash_register_foreign_key_to_shifts_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_08_02_172635_modify_user_login_logs_user_id_nullable',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_08_02_154851_create_transaction_logs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_08_03_000001_add_fields_to_suppliers_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_08_03_000002_create_stock_movements_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_08_03_000003_create_purchases_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_08_03_000004_create_purchase_items_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_08_03_143921_add_user_tracking_to_products_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_08_03_000005_add_stock_to_variants_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_08_03_164635_update_suppliers_table_structure',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_08_03_000000_create_suppliers_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_08_03_000001_create_stock_movements_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_08_03_000002_create_purchases_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_08_03_000003_create_purchase_items_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_08_05_000000_add_missing_fields_to_suppliers_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_08_09_000000_create_cash_transactions_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_08_09_201600_create_cash_transactions_table_manual',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_08_09_214736_add_stock_to_variants_table_fix',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_08_09_223057_update_purchases_status_enum',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_08_09_225353_remove_stock_columns_from_products_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_08_10_095237_remove_stock_columns_from_variants_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_08_10_221919_create_items_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_08_10_222025_create_product_items_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_08_13_170856_add_markup_percentage_to_products_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_08_13_193855_update_purchase_items_table_structure',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_08_13_190954_add_soft_deletes_to_purchase_items_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_08_13_194600_manual_map_purchase_items',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_08_13_194758_cleanup_purchase_items_old_columns',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_08_14_065625_drop_item_id_from_inventory_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_08_14_063640_drop_unused_fields_from_purchase_items_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_08_14_074030_remove_stock_columns_from_items_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_08_14_074614_remove_supplier_id_from_items_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_08_15_125636_add_delivery_takeaway_to_items_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_08_15_135412_remove_is_takeaway_only_from_items_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_08_12_000000_create_product_recipes_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_08_12_000001_create_product_recipe_items_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_08_12_082755_add_hpp_performance_indexes',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_08_13_194439_map_purchase_items_products_to_items',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_08_13_222000_add_item_support_to_inventory_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_08_14_063141_make_id_product_nullable_in_purchase_items_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_08_14_064919_add_purchase_id_foreign_key_to_purchase_items',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_08_15_085942_add_is_takeaway_only_to_items_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_08_17_000000_create_assets_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_08_17_000001_create_permissions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_08_17_000002_create_role_permissions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_08_17_000003_create_user_permissions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_08_18_010715_create_assets_table_physical',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_08_19_000000_create_variant_items_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_08_19_000001_create_variant_attributes_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_08_20_072605_add_hpp_fields_to_products_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_08_20_074107_add_hpp_fields_to_variants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_08_22_103648_add_customer_and_queue_number_to_orders_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_08_22_125832_add_discount_fields_to_order_items_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_08_23_070157_remove_keterangan_no_urut_from_orders_table',39);
