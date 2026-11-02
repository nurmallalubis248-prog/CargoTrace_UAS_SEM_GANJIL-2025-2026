-- CargoTrace - database schema (MySQL / MariaDB, XAMPP)
-- Equivalent to the Laravel migrations in database/migrations/.
-- Usage: select database `cargotrace` in phpMyAdmin, then Import this file.
-- This file creates ALL tables AND fills them with the demo accounts and sample shipments.
-- Do NOT run "php artisan migrate" or "db:seed" after importing this file.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `cargotrace` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cargotrace`;

-- Remove old CargoTrace tables first so the import never fails with 'table already exists'.
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `shipment_exceptions`;
DROP TABLE IF EXISTS `tracking_events`;
DROP TABLE IF EXISTS `shipment_items`;
DROP TABLE IF EXISTS `shipments`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `carriers`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;


-- ---------------------------------------------------------------- users
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'customer',   -- admin | operator | customer
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` TEXT NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- master data
CREATE TABLE `carriers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `contact_email` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carriers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `country` VARCHAR(2) NOT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'PORT',        -- PORT | AIRPORT | WAREHOUSE | OTHER
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- shipments
CREATE TABLE `shipments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_number` VARCHAR(30) NOT NULL,
  `reference_number` VARCHAR(50) NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `carrier_id` BIGINT UNSIGNED NOT NULL,
  `origin_id` BIGINT UNSIGNED NOT NULL,
  `destination_id` BIGINT UNSIGNED NOT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `cargo_type` VARCHAR(30) NOT NULL,
  `weight_kg` DECIMAL(12,2) NOT NULL,
  `volume_cbm` DECIMAL(12,2) NULL,
  `expected_departure_at` DATETIME NOT NULL,
  `expected_arrival_at` DATETIME NOT NULL,
  `estimated_arrival_at` DATETIME NULL,
  `actual_arrival_at` DATETIME NULL,
  `current_status` VARCHAR(20) NOT NULL DEFAULT 'CREATED',
  `current_latitude` DECIMAL(10,7) NULL,
  `current_longitude` DECIMAL(10,7) NULL,
  `current_location_label` VARCHAR(255) NULL,
  `last_event_at` DATETIME NULL,
  `is_delayed` TINYINT(1) NOT NULL DEFAULT 0,
  `delay_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `delay_severity` VARCHAR(10) NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipments_shipment_number_unique` (`shipment_number`),
  KEY `shipments_reference_number_index` (`reference_number`),
  KEY `shipments_expected_arrival_at_index` (`expected_arrival_at`),
  KEY `shipments_current_status_index` (`current_status`),
  KEY `shipments_is_delayed_index` (`is_delayed`),
  CONSTRAINT `shipments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_origin_id_foreign` FOREIGN KEY (`origin_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_destination_id_foreign` FOREIGN KEY (`destination_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipment_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `weight_kg` DECIMAL(12,2) NULL,
  `volume_cbm` DECIMAL(12,2) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `shipment_items_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `shipment_items_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tracking_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(20) NOT NULL,                 -- STATUS_UPDATE | CHECKPOINT | ETA_UPDATE | EXCEPTION
  `status` VARCHAR(20) NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `location_label` VARCHAR(255) NULL,
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  `occurred_at` DATETIME NOT NULL,
  `description` TEXT NULL,
  `is_exception` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `tracking_events_shipment_id_occurred_at_index` (`shipment_id`, `occurred_at`),
  KEY `tracking_events_location_id_foreign` (`location_id`),
  KEY `tracking_events_created_by_foreign` (`created_by`),
  CONSTRAINT `tracking_events_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tracking_events_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tracking_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipment_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `severity` VARCHAR(10) NOT NULL,
  `status` VARCHAR(15) NOT NULL DEFAULT 'OPEN',
  `description` TEXT NOT NULL,
  `detected_at` DATETIME NOT NULL,
  `resolved_at` DATETIME NULL,
  `resolution_notes` TEXT NULL,
  `reported_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `shipment_exceptions_shipment_id_status_index` (`shipment_id`, `status`),
  KEY `shipment_exceptions_status_index` (`status`),
  KEY `shipment_exceptions_reported_by_foreign` (`reported_by`),
  CONSTRAINT `shipment_exceptions_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shipment_exceptions_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- notifications & audit
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `shipment_id` BIGINT UNSIGNED NULL,
  `type` VARCHAR(30) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_index` (`user_id`, `read_at`),
  KEY `notifications_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `changes` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`, `entity_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================ DATA (sample data, same as database/seeders/DatabaseSeeder.php)
-- Times are relative to the moment you import this file (UTC), exactly like the seeder uses now().
SET @now = UTC_TIMESTAMP();

-- users (password for all three accounts: password)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
  (1, 'Admin CargoTrace', 'admin@cargotrace.test', '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'admin', NULL, @now, @now),
  (2, 'Logistics Operator', 'operator@cargotrace.test', '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'operator', NULL, @now, @now),
  (3, 'PT Nusantara Export', 'customer1@cargotrace.test', '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'customer', NULL, @now, @now),
  (4, 'Samudra Trading Co.', 'customer2@cargotrace.test', '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'customer', NULL, @now, @now);

-- carriers
INSERT INTO `carriers` (`id`, `name`, `code`, `contact_email`, `is_active`, `created_at`, `updated_at`) VALUES
  (1, 'Maersk', 'MAEU', NULL, 1, @now, @now),
  (2, 'MSC', 'MSCU', NULL, 1, @now, @now),
  (3, 'CMA CGM', 'CMDU', NULL, 1, @now, @now),
  (4, 'Evergreen', 'EGLV', NULL, 1, @now, @now),
  (5, 'Ocean Network Express', 'ONEY', NULL, 1, @now, @now),
  (6, 'Hapag-Lloyd', 'HLCU', NULL, 1, @now, @now);

-- locations
INSERT INTO `locations` (`id`, `name`, `code`, `country`, `type`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`) VALUES
  (1, 'Port of Singapore', 'SGSIN', 'SG', 'PORT', 1.2644, 103.84, 1, @now, @now),
  (2, 'Port of Rotterdam', 'NLRTM', 'NL', 'PORT', 51.954, 4.142, 1, @now, @now),
  (3, 'Port of Shanghai', 'CNSHA', 'CN', 'PORT', 31.35, 121.6, 1, @now, @now),
  (4, 'Port of Los Angeles', 'USLAX', 'US', 'PORT', 33.7292, -118.262, 1, @now, @now),
  (5, 'Tanjung Priok (Jakarta)', 'IDJKT', 'ID', 'PORT', -6.1, 106.88, 1, @now, @now),
  (6, 'Port Botany (Sydney)', 'AUSYD', 'AU', 'PORT', -33.97, 151.22, 1, @now, @now),
  (7, 'Belawan (Medan)', 'IDBLW', 'ID', 'PORT', 3.783, 98.7, 1, @now, @now),
  (8, 'Nhava Sheva (Mumbai)', 'INNSA', 'IN', 'PORT', 18.95, 72.95, 1, @now, @now),
  (9, 'Port of Busan', 'KRPUS', 'KR', 'PORT', 35.1, 129.04, 1, @now, @now),
  (10, 'Port of Hamburg', 'DEHAM', 'DE', 'PORT', 53.54, 9.97, 1, @now, @now),
  (11, 'Port Klang', 'MYPKG', 'MY', 'PORT', 3.0, 101.39, 1, @now, @now),
  (12, 'Jebel Ali (Dubai)', 'AEJEA', 'AE', 'PORT', 25.01, 55.06, 1, @now, @now),
  (13, 'Port of Colombo', 'LKCMB', 'LK', 'PORT', 6.95, 79.85, 1, @now, @now),
  (14, 'Port of Yokohama', 'JPYOK', 'JP', 'PORT', 35.45, 139.65, 1, @now, @now),
  (15, 'Port of New York & New Jersey', 'USNYC', 'US', 'PORT', 40.69, -74.15, 1, @now, @now);

-- shipments
INSERT INTO `shipments` (`id`, `shipment_number`, `reference_number`, `customer_id`, `carrier_id`, `origin_id`, `destination_id`, `created_by`, `cargo_type`, `weight_kg`, `volume_cbm`, `expected_departure_at`, `expected_arrival_at`, `estimated_arrival_at`, `actual_arrival_at`, `current_status`, `current_latitude`, `current_longitude`, `current_location_label`, `last_event_at`, `is_delayed`, `delay_hours`, `delay_severity`, `deleted_at`, `created_at`, `updated_at`) VALUES
  (1, 'CT261102-K7Q2M', 'PO-48213', 3, 1, 1, 2, 2, 'CONTAINER_FCL', 18500.0, NULL, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL 1382400 SECOND), DATE_ADD(@now, INTERVAL 1382400 SECOND), NULL, 'IN_TRANSIT', 12.5, 45.0, 'Gulf of Aden', DATE_ADD(@now, INTERVAL -398400 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -398400 SECOND)),
  (2, 'CT261102-R4XD9', 'PO-73901', 4, 2, 1, 2, 2, 'REFRIGERATED', 12200.0, NULL, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL 691200 SECOND), DATE_ADD(@now, INTERVAL 835200 SECOND), NULL, 'IN_TRANSIT', 31.26, 32.31, 'Port Said approach', DATE_ADD(@now, INTERVAL -427500 SECOND), 1, 40.0, 'HIGH', NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (3, 'CT261102-T8WB3', 'PO-15642', 3, 3, 3, 4, 2, 'GENERAL', 24000.0, NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL 518400 SECOND), DATE_ADD(@now, INTERVAL 572400 SECOND), NULL, 'IN_TRANSIT', 36.0, -150.0, 'North Pacific (east)', DATE_ADD(@now, INTERVAL -281400 SECOND), 1, 15.0, 'MEDIUM', NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (4, 'CT261102-H2NZP', 'PO-90377', 4, 4, 3, 4, 2, 'CONTAINER_FCL', 9800.0, NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL 691200 SECOND), DATE_ADD(@now, INTERVAL 691200 SECOND), NULL, 'IN_TRANSIT', 38.0, 160.0, 'North Pacific (west)', DATE_ADD(@now, INTERVAL -336600 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -336600 SECOND)),
  (5, 'CT261102-V6CLA', 'PO-26458', 3, 5, 5, 6, 2, 'GENERAL', 6400.0, NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL 172800 SECOND), DATE_ADD(@now, INTERVAL 187200 SECOND), NULL, 'IN_TRANSIT', -11.5, 126.0, 'Timor Sea', DATE_ADD(@now, INTERVAL -252600 SECOND), 1, 4.0, 'LOW', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (6, 'CT261102-Y9FJE', 'PO-61084', 4, 6, 7, 8, 2, 'BULK', 31000.0, NULL, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND), 'DELIVERED', 18.95, 72.95, 'Nhava Sheva (Mumbai)', DATE_ADD(@now, INTERVAL -1555200 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (7, 'CT261102-M3GSU', 'PO-38529', 3, 1, 1, 2, 2, 'GENERAL', 8800.0, NULL, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND), 'DELIVERED', 51.954, 4.142, 'Port of Rotterdam', DATE_ADD(@now, INTERVAL -2574000 SECOND), 1, 5.0, 'LOW', NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (8, 'CT261102-D5PQK', 'PO-84710', 4, 5, 5, 6, 2, 'CONTAINER_LCL', 3100.0, NULL, DATE_ADD(@now, INTERVAL 345600 SECOND), DATE_ADD(@now, INTERVAL 1296000 SECOND), DATE_ADD(@now, INTERVAL 1296000 SECOND), NULL, 'BOOKED', -6.1, 106.88, 'Tanjung Priok (Jakarta)', DATE_ADD(@now, INTERVAL -97200 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (9, 'CT261102-B1TXR', 'PO-57236', 3, 2, 3, 4, 2, 'LIQUID', 21000.0, NULL, DATE_ADD(@now, INTERVAL 777600 SECOND), DATE_ADD(@now, INTERVAL 2160000 SECOND), DATE_ADD(@now, INTERVAL 2160000 SECOND), NULL, 'CREATED', 31.35, 121.6, 'Port of Shanghai', DATE_ADD(@now, INTERVAL -21600 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- shipment_items
INSERT INTO `shipment_items` (`id`, `shipment_id`, `description`, `quantity`, `weight_kg`, `volume_cbm`, `created_at`, `updated_at`) VALUES
  (1, 1, 'Container fcl consignment', 1, 18500.0, NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (2, 2, 'Refrigerated consignment', 1, 12200.0, NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (3, 3, 'General consignment', 1, 24000.0, NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (4, 4, 'Container fcl consignment', 1, 9800.0, NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (5, 5, 'General consignment', 1, 6400.0, NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (6, 6, 'Bulk consignment', 1, 31000.0, NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (7, 7, 'General consignment', 1, 8800.0, NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (8, 8, 'Container lcl consignment', 1, 3100.0, NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (9, 9, 'Liquid consignment', 1, 21000.0, NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- tracking_events
INSERT INTO `tracking_events` (`id`, `shipment_id`, `event_type`, `status`, `location_id`, `location_label`, `latitude`, `longitude`, `occurred_at`, `description`, `is_exception`, `created_by`, `created_at`, `updated_at`) VALUES
  (1, 1, 'STATUS_UPDATE', 'CREATED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1468800 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (2, 1, 'STATUS_UPDATE', 'BOOKED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1382400 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -1382400 SECOND), DATE_ADD(@now, INTERVAL -1382400 SECOND)),
  (3, 1, 'STATUS_UPDATE', 'PICKED_UP', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1209600 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL -1209600 SECOND)),
  (4, 1, 'STATUS_UPDATE', 'IN_TRANSIT', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1188000 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -1188000 SECOND), DATE_ADD(@now, INTERVAL -1188000 SECOND)),
  (5, 1, 'CHECKPOINT', NULL, NULL, 'Off Colombo, Sri Lanka', 6.95, 79.85, DATE_ADD(@now, INTERVAL -793200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -793200 SECOND), DATE_ADD(@now, INTERVAL -793200 SECOND)),
  (6, 1, 'CHECKPOINT', NULL, NULL, 'Gulf of Aden', 12.5, 45.0, DATE_ADD(@now, INTERVAL -398400 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -398400 SECOND), DATE_ADD(@now, INTERVAL -398400 SECOND)),
  (7, 2, 'STATUS_UPDATE', 'CREATED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1987200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (8, 2, 'STATUS_UPDATE', 'BOOKED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1900800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -1900800 SECOND), DATE_ADD(@now, INTERVAL -1900800 SECOND)),
  (9, 2, 'STATUS_UPDATE', 'PICKED_UP', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1728000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL -1728000 SECOND)),
  (10, 2, 'STATUS_UPDATE', 'IN_TRANSIT', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1706400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -1706400 SECOND), DATE_ADD(@now, INTERVAL -1706400 SECOND)),
  (11, 2, 'CHECKPOINT', NULL, NULL, 'Off Colombo, Sri Lanka', 6.95, 79.85, DATE_ADD(@now, INTERVAL -1280700 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -1280700 SECOND), DATE_ADD(@now, INTERVAL -1280700 SECOND)),
  (12, 2, 'CHECKPOINT', NULL, NULL, 'Gulf of Aden', 12.5, 45.0, DATE_ADD(@now, INTERVAL -855000 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -855000 SECOND), DATE_ADD(@now, INTERVAL -855000 SECOND)),
  (13, 2, 'CHECKPOINT', NULL, NULL, 'Port Said approach', 31.26, 32.31, DATE_ADD(@now, INTERVAL -429300 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -429300 SECOND), DATE_ADD(@now, INTERVAL -429300 SECOND)),
  (14, 2, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -427500 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -427500 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (15, 2, 'EXCEPTION', NULL, NULL, 'Port Said approach', 31.26, 32.31, DATE_ADD(@now, INTERVAL -7200 SECOND), '[PORT_CONGESTION] Anchorage queue at Port Said; berth window moved.', 1, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (16, 3, 'STATUS_UPDATE', 'CREATED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -1123200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (17, 3, 'STATUS_UPDATE', 'BOOKED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -1036800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (18, 3, 'STATUS_UPDATE', 'PICKED_UP', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -864000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (19, 3, 'STATUS_UPDATE', 'IN_TRANSIT', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -842400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -842400 SECOND), DATE_ADD(@now, INTERVAL -842400 SECOND)),
  (20, 3, 'CHECKPOINT', NULL, NULL, 'North Pacific (west)', 38.0, 160.0, DATE_ADD(@now, INTERVAL -562800 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -562800 SECOND), DATE_ADD(@now, INTERVAL -562800 SECOND)),
  (21, 3, 'CHECKPOINT', NULL, NULL, 'North Pacific (east)', 36.0, -150.0, DATE_ADD(@now, INTERVAL -283200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -283200 SECOND), DATE_ADD(@now, INTERVAL -283200 SECOND)),
  (22, 3, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -281400 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -281400 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (23, 3, 'EXCEPTION', NULL, NULL, 'North Pacific (east)', 36.0, -150.0, DATE_ADD(@now, INTERVAL -7200 SECOND), '[WEATHER_DELAY] Vessel reduced speed because of storm conditions.', 1, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (24, 4, 'STATUS_UPDATE', 'CREATED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -950400 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (25, 4, 'STATUS_UPDATE', 'BOOKED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -864000 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (26, 4, 'STATUS_UPDATE', 'PICKED_UP', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -691200 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (27, 4, 'STATUS_UPDATE', 'IN_TRANSIT', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -669600 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -669600 SECOND), DATE_ADD(@now, INTERVAL -669600 SECOND)),
  (28, 4, 'CHECKPOINT', NULL, NULL, 'North Pacific (west)', 38.0, 160.0, DATE_ADD(@now, INTERVAL -336600 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -336600 SECOND), DATE_ADD(@now, INTERVAL -336600 SECOND)),
  (29, 5, 'STATUS_UPDATE', 'CREATED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -1036800 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (30, 5, 'STATUS_UPDATE', 'BOOKED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -950400 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (31, 5, 'STATUS_UPDATE', 'PICKED_UP', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -777600 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (32, 5, 'STATUS_UPDATE', 'IN_TRANSIT', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -756000 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -756000 SECOND), DATE_ADD(@now, INTERVAL -756000 SECOND)),
  (33, 5, 'CHECKPOINT', NULL, NULL, 'Lombok Strait', -8.7, 115.7, DATE_ADD(@now, INTERVAL -505200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -505200 SECOND), DATE_ADD(@now, INTERVAL -505200 SECOND)),
  (34, 5, 'CHECKPOINT', NULL, NULL, 'Timor Sea', -11.5, 126.0, DATE_ADD(@now, INTERVAL -254400 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -254400 SECOND), DATE_ADD(@now, INTERVAL -254400 SECOND)),
  (35, 5, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -252600 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -252600 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (36, 6, 'STATUS_UPDATE', 'CREATED', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2851200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (37, 6, 'STATUS_UPDATE', 'BOOKED', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2764800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -2764800 SECOND), DATE_ADD(@now, INTERVAL -2764800 SECOND)),
  (38, 6, 'STATUS_UPDATE', 'PICKED_UP', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2592000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND)),
  (39, 6, 'STATUS_UPDATE', 'IN_TRANSIT', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2570400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -2570400 SECOND), DATE_ADD(@now, INTERVAL -2570400 SECOND)),
  (40, 6, 'CHECKPOINT', NULL, NULL, 'Andaman Sea', 7.5, 94.0, DATE_ADD(@now, INTERVAL -2233200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -2233200 SECOND), DATE_ADD(@now, INTERVAL -2233200 SECOND)),
  (41, 6, 'CHECKPOINT', NULL, NULL, 'Arabian Sea', 14.0, 75.0, DATE_ADD(@now, INTERVAL -1896000 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -1896000 SECOND), DATE_ADD(@now, INTERVAL -1896000 SECOND)),
  (42, 6, 'STATUS_UPDATE', 'DELIVERED', 8, 'Nhava Sheva (Mumbai)', 18.95, 72.95, DATE_ADD(@now, INTERVAL -1555200 SECOND), 'Cargo delivered to consignee.', 0, 2, DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (43, 6, 'EXCEPTION', NULL, NULL, 'Nhava Sheva (Mumbai)', 18.95, 72.95, DATE_ADD(@now, INTERVAL -1562400 SECOND), '[DAMAGED] Minor packaging damage noted at discharge.', 1, 2, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1562400 SECOND)),
  (44, 7, 'STATUS_UPDATE', 'CREATED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5443200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (45, 7, 'STATUS_UPDATE', 'BOOKED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5356800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -5356800 SECOND), DATE_ADD(@now, INTERVAL -5356800 SECOND)),
  (46, 7, 'STATUS_UPDATE', 'PICKED_UP', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5184000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -5184000 SECOND)),
  (47, 7, 'STATUS_UPDATE', 'IN_TRANSIT', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5162400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -5162400 SECOND), DATE_ADD(@now, INTERVAL -5162400 SECOND)),
  (48, 7, 'CHECKPOINT', NULL, NULL, 'Off Colombo, Sri Lanka', 6.95, 79.85, DATE_ADD(@now, INTERVAL -4645440 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -4645440 SECOND), DATE_ADD(@now, INTERVAL -4645440 SECOND)),
  (49, 7, 'CHECKPOINT', NULL, NULL, 'Gulf of Aden', 12.5, 45.0, DATE_ADD(@now, INTERVAL -4128480 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -4128480 SECOND), DATE_ADD(@now, INTERVAL -4128480 SECOND)),
  (50, 7, 'CHECKPOINT', NULL, NULL, 'Port Said approach', 31.26, 32.31, DATE_ADD(@now, INTERVAL -3611520 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -3611520 SECOND), DATE_ADD(@now, INTERVAL -3611520 SECOND)),
  (51, 7, 'CHECKPOINT', NULL, NULL, 'Strait of Gibraltar', 36.0, -5.6, DATE_ADD(@now, INTERVAL -3094560 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -3094560 SECOND), DATE_ADD(@now, INTERVAL -3094560 SECOND)),
  (52, 7, 'STATUS_UPDATE', 'DELIVERED', 2, 'Port of Rotterdam', 51.954, 4.142, DATE_ADD(@now, INTERVAL -2574000 SECOND), 'Cargo delivered to consignee.', 0, 2, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (53, 7, 'EXCEPTION', NULL, NULL, 'Port of Rotterdam', 51.954, 4.142, DATE_ADD(@now, INTERVAL -2581200 SECOND), '[DOCUMENTATION_ISSUE] Missing certificate of origin; corrected copy supplied.', 1, 2, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2581200 SECOND)),
  (54, 8, 'STATUS_UPDATE', 'CREATED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -108000 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (55, 8, 'STATUS_UPDATE', 'BOOKED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -97200 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -97200 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (56, 9, 'STATUS_UPDATE', 'CREATED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -21600 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- shipment_exceptions
INSERT INTO `shipment_exceptions` (`id`, `shipment_id`, `type`, `severity`, `status`, `description`, `detected_at`, `resolved_at`, `resolution_notes`, `reported_by`, `created_at`, `updated_at`) VALUES
  (1, 2, 'PORT_CONGESTION', 'HIGH', 'OPEN', 'Anchorage queue at Port Said; berth window moved.', DATE_ADD(@now, INTERVAL -7200 SECOND), NULL, NULL, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (2, 3, 'WEATHER_DELAY', 'MEDIUM', 'INVESTIGATING', 'Vessel reduced speed because of storm conditions.', DATE_ADD(@now, INTERVAL -7200 SECOND), NULL, NULL, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (3, 6, 'DAMAGED', 'LOW', 'RESOLVED', 'Minor packaging damage noted at discharge.', DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND), 'Handled with carrier and customer.', 2, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND)),
  (4, 7, 'DOCUMENTATION_ISSUE', 'MEDIUM', 'RESOLVED', 'Missing certificate of origin; corrected copy supplied.', DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND), 'Handled with carrier and customer.', 2, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND));

-- notifications
INSERT INTO `notifications` (`id`, `user_id`, `shipment_id`, `type`, `message`, `read_at`, `created_at`, `updated_at`) VALUES
  (1, 3, 1, 'SHIPMENT_CREATED', 'Shipment CT261102-K7Q2M has been created.', NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (2, 3, 1, 'STATUS_CHANGED', 'Shipment CT261102-K7Q2M is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -1382400 SECOND), DATE_ADD(@now, INTERVAL -1382400 SECOND)),
  (3, 3, 1, 'STATUS_CHANGED', 'Shipment CT261102-K7Q2M is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL -1209600 SECOND)),
  (4, 3, 1, 'STATUS_CHANGED', 'Shipment CT261102-K7Q2M is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -1188000 SECOND), DATE_ADD(@now, INTERVAL -1188000 SECOND)),
  (5, 4, 2, 'SHIPMENT_CREATED', 'Shipment CT261102-R4XD9 has been created.', NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (6, 4, 2, 'STATUS_CHANGED', 'Shipment CT261102-R4XD9 is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -1900800 SECOND), DATE_ADD(@now, INTERVAL -1900800 SECOND)),
  (7, 4, 2, 'STATUS_CHANGED', 'Shipment CT261102-R4XD9 is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL -1728000 SECOND)),
  (8, 4, 2, 'STATUS_CHANGED', 'Shipment CT261102-R4XD9 is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -1706400 SECOND), DATE_ADD(@now, INTERVAL -1706400 SECOND)),
  (9, 4, 2, 'SHIPMENT_DELAYED', 'Shipment CT261102-R4XD9 is delayed by 40.0 h (HIGH).', NULL, DATE_ADD(@now, INTERVAL -427500 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (10, 4, 2, 'EXCEPTION_OPENED', 'Exception PORT_CONGESTION (HIGH) on shipment CT261102-R4XD9.', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (11, 3, 3, 'SHIPMENT_CREATED', 'Shipment CT261102-T8WB3 has been created.', NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (12, 3, 3, 'STATUS_CHANGED', 'Shipment CT261102-T8WB3 is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (13, 3, 3, 'STATUS_CHANGED', 'Shipment CT261102-T8WB3 is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (14, 3, 3, 'STATUS_CHANGED', 'Shipment CT261102-T8WB3 is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -842400 SECOND), DATE_ADD(@now, INTERVAL -842400 SECOND)),
  (15, 3, 3, 'SHIPMENT_DELAYED', 'Shipment CT261102-T8WB3 is delayed by 15.0 h (MEDIUM).', NULL, DATE_ADD(@now, INTERVAL -281400 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (16, 3, 3, 'EXCEPTION_OPENED', 'Exception WEATHER_DELAY (MEDIUM) on shipment CT261102-T8WB3.', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (17, 4, 4, 'SHIPMENT_CREATED', 'Shipment CT261102-H2NZP has been created.', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (18, 4, 4, 'STATUS_CHANGED', 'Shipment CT261102-H2NZP is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (19, 4, 4, 'STATUS_CHANGED', 'Shipment CT261102-H2NZP is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (20, 4, 4, 'STATUS_CHANGED', 'Shipment CT261102-H2NZP is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -669600 SECOND), DATE_ADD(@now, INTERVAL -669600 SECOND)),
  (21, 3, 5, 'SHIPMENT_CREATED', 'Shipment CT261102-V6CLA has been created.', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (22, 3, 5, 'STATUS_CHANGED', 'Shipment CT261102-V6CLA is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (23, 3, 5, 'STATUS_CHANGED', 'Shipment CT261102-V6CLA is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (24, 3, 5, 'STATUS_CHANGED', 'Shipment CT261102-V6CLA is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -756000 SECOND), DATE_ADD(@now, INTERVAL -756000 SECOND)),
  (25, 3, 5, 'SHIPMENT_DELAYED', 'Shipment CT261102-V6CLA is delayed by 4.0 h (LOW).', NULL, DATE_ADD(@now, INTERVAL -252600 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (26, 4, 6, 'SHIPMENT_CREATED', 'Shipment CT261102-Y9FJE has been created.', NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (27, 4, 6, 'STATUS_CHANGED', 'Shipment CT261102-Y9FJE is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -2764800 SECOND), DATE_ADD(@now, INTERVAL -2764800 SECOND)),
  (28, 4, 6, 'STATUS_CHANGED', 'Shipment CT261102-Y9FJE is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND)),
  (29, 4, 6, 'STATUS_CHANGED', 'Shipment CT261102-Y9FJE is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -2570400 SECOND), DATE_ADD(@now, INTERVAL -2570400 SECOND)),
  (30, 4, 6, 'STATUS_CHANGED', 'Shipment CT261102-Y9FJE is now DELIVERED.', NULL, DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (31, 4, 6, 'EXCEPTION_OPENED', 'Exception DAMAGED (LOW) on shipment CT261102-Y9FJE.', NULL, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1562400 SECOND)),
  (32, 4, 6, 'EXCEPTION_RESOLVED', 'Exception DAMAGED on shipment CT261102-Y9FJE was resolved.', NULL, DATE_ADD(@now, INTERVAL -1533600 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND)),
  (33, 3, 7, 'SHIPMENT_CREATED', 'Shipment CT261102-M3GSU has been created.', NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (34, 3, 7, 'STATUS_CHANGED', 'Shipment CT261102-M3GSU is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -5356800 SECOND), DATE_ADD(@now, INTERVAL -5356800 SECOND)),
  (35, 3, 7, 'STATUS_CHANGED', 'Shipment CT261102-M3GSU is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -5184000 SECOND)),
  (36, 3, 7, 'STATUS_CHANGED', 'Shipment CT261102-M3GSU is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -5162400 SECOND), DATE_ADD(@now, INTERVAL -5162400 SECOND)),
  (37, 3, 7, 'STATUS_CHANGED', 'Shipment CT261102-M3GSU is now DELIVERED.', NULL, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (38, 3, 7, 'SHIPMENT_DELAYED', 'Shipment CT261102-M3GSU is delayed by 5.0 h (LOW).', NULL, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (39, 3, 7, 'EXCEPTION_OPENED', 'Exception DOCUMENTATION_ISSUE (MEDIUM) on shipment CT261102-M3GSU.', NULL, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2581200 SECOND)),
  (40, 3, 7, 'EXCEPTION_RESOLVED', 'Exception DOCUMENTATION_ISSUE on shipment CT261102-M3GSU was resolved.', NULL, DATE_ADD(@now, INTERVAL -2552400 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND)),
  (41, 4, 8, 'SHIPMENT_CREATED', 'Shipment CT261102-D5PQK has been created.', NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (42, 4, 8, 'STATUS_CHANGED', 'Shipment CT261102-D5PQK is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -97200 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (43, 3, 9, 'SHIPMENT_CREATED', 'Shipment CT261102-B1TXR has been created.', NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- audit_logs
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `changes`, `ip_address`, `created_at`, `updated_at`) VALUES
  (1, 2, 'shipment.created', 'Shipment', 1, '{"shipment_number":"CT261102-K7Q2M","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (2, 2, 'tracking.added', 'Shipment', 1, '{"event_id":2,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -1382400 SECOND), DATE_ADD(@now, INTERVAL -1382400 SECOND)),
  (3, 2, 'tracking.added', 'Shipment', 1, '{"event_id":3,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL -1209600 SECOND)),
  (4, 2, 'tracking.added', 'Shipment', 1, '{"event_id":4,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -1188000 SECOND), DATE_ADD(@now, INTERVAL -1188000 SECOND)),
  (5, 2, 'tracking.added', 'Shipment', 1, '{"event_id":5,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -793200 SECOND), DATE_ADD(@now, INTERVAL -793200 SECOND)),
  (6, 2, 'tracking.added', 'Shipment', 1, '{"event_id":6,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -398400 SECOND), DATE_ADD(@now, INTERVAL -398400 SECOND)),
  (7, 2, 'shipment.created', 'Shipment', 2, '{"shipment_number":"CT261102-R4XD9","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (8, 2, 'tracking.added', 'Shipment', 2, '{"event_id":8,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -1900800 SECOND), DATE_ADD(@now, INTERVAL -1900800 SECOND)),
  (9, 2, 'tracking.added', 'Shipment', 2, '{"event_id":9,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL -1728000 SECOND)),
  (10, 2, 'tracking.added', 'Shipment', 2, '{"event_id":10,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -1706400 SECOND), DATE_ADD(@now, INTERVAL -1706400 SECOND)),
  (11, 2, 'tracking.added', 'Shipment', 2, '{"event_id":11,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -1280700 SECOND), DATE_ADD(@now, INTERVAL -1280700 SECOND)),
  (12, 2, 'tracking.added', 'Shipment', 2, '{"event_id":12,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -855000 SECOND), DATE_ADD(@now, INTERVAL -855000 SECOND)),
  (13, 2, 'tracking.added', 'Shipment', 2, '{"event_id":13,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -429300 SECOND), DATE_ADD(@now, INTERVAL -429300 SECOND)),
  (14, 2, 'tracking.added', 'Shipment', 2, '{"event_id":14,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -427500 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (15, 2, 'exception.opened', 'ShipmentException', 1, '{"shipment_id":2,"type":"PORT_CONGESTION"}', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (16, 2, 'shipment.created', 'Shipment', 3, '{"shipment_number":"CT261102-T8WB3","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (17, 2, 'tracking.added', 'Shipment', 3, '{"event_id":17,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (18, 2, 'tracking.added', 'Shipment', 3, '{"event_id":18,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (19, 2, 'tracking.added', 'Shipment', 3, '{"event_id":19,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -842400 SECOND), DATE_ADD(@now, INTERVAL -842400 SECOND)),
  (20, 2, 'tracking.added', 'Shipment', 3, '{"event_id":20,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -562800 SECOND), DATE_ADD(@now, INTERVAL -562800 SECOND)),
  (21, 2, 'tracking.added', 'Shipment', 3, '{"event_id":21,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -283200 SECOND), DATE_ADD(@now, INTERVAL -283200 SECOND)),
  (22, 2, 'tracking.added', 'Shipment', 3, '{"event_id":22,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -281400 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (23, 2, 'exception.opened', 'ShipmentException', 2, '{"shipment_id":3,"type":"WEATHER_DELAY"}', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (24, 2, 'exception.updated', 'ShipmentException', 2, '{"status":{"from":"OPEN","to":"INVESTIGATING"}}', NULL, DATE_ADD(@now, INTERVAL -3600 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (25, 2, 'shipment.created', 'Shipment', 4, '{"shipment_number":"CT261102-H2NZP","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (26, 2, 'tracking.added', 'Shipment', 4, '{"event_id":25,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (27, 2, 'tracking.added', 'Shipment', 4, '{"event_id":26,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (28, 2, 'tracking.added', 'Shipment', 4, '{"event_id":27,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -669600 SECOND), DATE_ADD(@now, INTERVAL -669600 SECOND)),
  (29, 2, 'tracking.added', 'Shipment', 4, '{"event_id":28,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -336600 SECOND), DATE_ADD(@now, INTERVAL -336600 SECOND)),
  (30, 2, 'shipment.created', 'Shipment', 5, '{"shipment_number":"CT261102-V6CLA","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (31, 2, 'tracking.added', 'Shipment', 5, '{"event_id":30,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (32, 2, 'tracking.added', 'Shipment', 5, '{"event_id":31,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (33, 2, 'tracking.added', 'Shipment', 5, '{"event_id":32,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -756000 SECOND), DATE_ADD(@now, INTERVAL -756000 SECOND)),
  (34, 2, 'tracking.added', 'Shipment', 5, '{"event_id":33,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -505200 SECOND), DATE_ADD(@now, INTERVAL -505200 SECOND)),
  (35, 2, 'tracking.added', 'Shipment', 5, '{"event_id":34,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -254400 SECOND), DATE_ADD(@now, INTERVAL -254400 SECOND)),
  (36, 2, 'tracking.added', 'Shipment', 5, '{"event_id":35,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -252600 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (37, 2, 'shipment.created', 'Shipment', 6, '{"shipment_number":"CT261102-Y9FJE","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (38, 2, 'tracking.added', 'Shipment', 6, '{"event_id":37,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -2764800 SECOND), DATE_ADD(@now, INTERVAL -2764800 SECOND)),
  (39, 2, 'tracking.added', 'Shipment', 6, '{"event_id":38,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND)),
  (40, 2, 'tracking.added', 'Shipment', 6, '{"event_id":39,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -2570400 SECOND), DATE_ADD(@now, INTERVAL -2570400 SECOND)),
  (41, 2, 'tracking.added', 'Shipment', 6, '{"event_id":40,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -2233200 SECOND), DATE_ADD(@now, INTERVAL -2233200 SECOND)),
  (42, 2, 'tracking.added', 'Shipment', 6, '{"event_id":41,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -1896000 SECOND), DATE_ADD(@now, INTERVAL -1896000 SECOND)),
  (43, 2, 'tracking.added', 'Shipment', 6, '{"event_id":42,"event_type":"STATUS_UPDATE","status":"DELIVERED"}', NULL, DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (44, 2, 'exception.opened', 'ShipmentException', 3, '{"shipment_id":6,"type":"DAMAGED"}', NULL, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1562400 SECOND)),
  (45, 2, 'exception.updated', 'ShipmentException', 3, '{"status":{"from":"OPEN","to":"RESOLVED"},"resolution_notes":"updated"}', NULL, DATE_ADD(@now, INTERVAL -1533600 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND)),
  (46, 2, 'shipment.created', 'Shipment', 7, '{"shipment_number":"CT261102-M3GSU","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (47, 2, 'tracking.added', 'Shipment', 7, '{"event_id":45,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -5356800 SECOND), DATE_ADD(@now, INTERVAL -5356800 SECOND)),
  (48, 2, 'tracking.added', 'Shipment', 7, '{"event_id":46,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -5184000 SECOND)),
  (49, 2, 'tracking.added', 'Shipment', 7, '{"event_id":47,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -5162400 SECOND), DATE_ADD(@now, INTERVAL -5162400 SECOND)),
  (50, 2, 'tracking.added', 'Shipment', 7, '{"event_id":48,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -4645440 SECOND), DATE_ADD(@now, INTERVAL -4645440 SECOND)),
  (51, 2, 'tracking.added', 'Shipment', 7, '{"event_id":49,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -4128480 SECOND), DATE_ADD(@now, INTERVAL -4128480 SECOND)),
  (52, 2, 'tracking.added', 'Shipment', 7, '{"event_id":50,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -3611520 SECOND), DATE_ADD(@now, INTERVAL -3611520 SECOND)),
  (53, 2, 'tracking.added', 'Shipment', 7, '{"event_id":51,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -3094560 SECOND), DATE_ADD(@now, INTERVAL -3094560 SECOND)),
  (54, 2, 'tracking.added', 'Shipment', 7, '{"event_id":52,"event_type":"STATUS_UPDATE","status":"DELIVERED"}', NULL, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (55, 2, 'exception.opened', 'ShipmentException', 4, '{"shipment_id":7,"type":"DOCUMENTATION_ISSUE"}', NULL, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2581200 SECOND)),
  (56, 2, 'exception.updated', 'ShipmentException', 4, '{"status":{"from":"OPEN","to":"RESOLVED"},"resolution_notes":"updated"}', NULL, DATE_ADD(@now, INTERVAL -2552400 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND)),
  (57, 2, 'shipment.created', 'Shipment', 8, '{"shipment_number":"CT261102-D5PQK","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (58, 2, 'tracking.added', 'Shipment', 8, '{"event_id":55,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -97200 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (59, 2, 'shipment.created', 'Shipment', 9, '{"shipment_number":"CT261102-B1TXR","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));


SET FOREIGN_KEY_CHECKS = 1;