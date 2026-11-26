-- CargoTrace - database schema (MySQL / MariaDB, XAMPP)
-- Equivalent to the Laravel migrations in database/migrations/.
-- Usage: select database `cargotrace` in phpMyAdmin, then Import this file.
-- This ONE file creates ALL tables (users, carriers + transport mode, locations, warehouses, shipments, tracking, exceptions,
-- notifications, audit logs) AND fills them with demo accounts and sample data.
-- Lifecycle supported: CREATED > BOOKED > PICKED_UP > IN_TRANSIT > ARRIVED > DELIVERED (status columns are VARCHAR, no enum to change).
-- Import ONLY this file (it replaces any older patch files).
-- Do NOT run "php artisan migrate" or "db:seed" after importing this file (use one way or the other, never both).

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
DROP TABLE IF EXISTS `warehouses`;
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
  `email_verified_at` TIMESTAMP NULL,
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
  `transport_mode` VARCHAR(10) NOT NULL DEFAULT 'SEA',   -- SEA | AIR | ROAD (ROAD shipments get real-road routes on the map)
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

-- ---------------------------------------------------------------- warehouses
CREATE TABLE `warehouses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(12) NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `capacity_kg` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_location_id_foreign` (`location_id`),
  CONSTRAINT `warehouses_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
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
  `warehouse_id` BIGINT UNSIGNED NULL,
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
  KEY `shipments_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `shipments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_origin_id_foreign` FOREIGN KEY (`origin_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_destination_id_foreign` FOREIGN KEY (`destination_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `shipments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
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

-- ================================================================ DATA (contoh data, sesuai tampilan dashboard)
-- Waktu dihitung relatif terhadap saat file ini di-import (UTC), seperti seeder yang memakai now().
SET @now = UTC_TIMESTAMP();

-- users (password untuk semua akun: password)
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
  (1, 'Admin CargoTrace', 'admin@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'admin', NULL, @now, @now),
  (2, 'Logistics Operator', 'operator@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'operator', NULL, @now, @now),
  (3, 'PT Nusantara Export', 'customer1@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'customer', NULL, @now, @now),
  (4, 'Samudra Trading Co.', 'customer2@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'customer', NULL, @now, @now);

-- carriers (6 laut, 2 darat, 2 udara)
INSERT INTO `carriers` (`id`, `name`, `code`, `transport_mode`, `contact_email`, `is_active`, `created_at`, `updated_at`) VALUES
  (1, 'Maersk', 'MAEU', 'SEA', NULL, 1, @now, @now),
  (2, 'MSC', 'MSCU', 'SEA', NULL, 1, @now, @now),
  (3, 'CMA CGM', 'CMDU', 'SEA', NULL, 1, @now, @now),
  (4, 'Evergreen', 'EGLV', 'SEA', NULL, 1, @now, @now),
  (5, 'Ocean Network Express', 'ONEY', 'SEA', NULL, 1, @now, @now),
  (6, 'Hapag-Lloyd', 'HLCU', 'SEA', NULL, 1, @now, @now),
  (7, 'JNE Trucking', 'JNET', 'ROAD', NULL, 1, @now, @now),
  (8, 'DHL Road Freight', 'DHLR', 'ROAD', NULL, 1, @now, @now),
  (9, 'Garuda Cargo', 'GIAC', 'AIR', NULL, 1, @now, @now),
  (10, 'Emirates SkyCargo', 'EKSC', 'AIR', NULL, 1, @now, @now);

-- locations (pelabuhan, bandara, gudang)
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
  (15, 'Port of New York & New Jersey', 'USNYC', 'US', 'PORT', 40.69, -74.15, 1, @now, @now),
  (16, 'Changi Airport (Singapore)', 'SGSIA', 'SG', 'AIRPORT', 1.3644, 103.9915, 1, @now, @now),
  (17, 'Dubai International Airport', 'AEDXB', 'AE', 'AIRPORT', 25.2532, 55.3657, 1, @now, @now),
  (18, 'Soekarno-Hatta Airport (Jakarta)', 'IDCGK', 'ID', 'AIRPORT', -6.1256, 106.6559, 1, @now, @now),
  (19, 'Bandung (Warehouse)', 'IDBDO', 'ID', 'WAREHOUSE', -6.9175, 107.6191, 1, @now, @now),
  (20, 'Medan (Warehouse)', 'IDMES', 'ID', 'WAREHOUSE', 3.5952, 98.6722, 1, @now, @now),
  (21, 'Surabaya (Warehouse)', 'IDSUB', 'ID', 'WAREHOUSE', -7.2575, 112.7521, 1, @now, @now);

-- warehouses (location_id dicari lewat kode lokasi)
INSERT INTO `warehouses` (`id`, `name`, `code`, `location_id`, `capacity_kg`, `is_active`, `created_at`, `updated_at`) VALUES
  (1, 'Jakarta Distribution Hub', 'JKT-WH', (SELECT `id` FROM `locations` WHERE `code` = 'IDJKT' LIMIT 1), 60000, 1, @now, @now),
  (2, 'Surabaya Warehouse', 'SUB-WH', (SELECT `id` FROM `locations` WHERE `code` = 'IDSUB' LIMIT 1), 40000, 1, @now, @now),
  (3, 'Medan Warehouse', 'MES-WH', (SELECT `id` FROM `locations` WHERE `code` = 'IDMES' LIMIT 1), 30000, 1, @now, @now),
  (4, 'Bandung Warehouse', 'BDO-WH', (SELECT `id` FROM `locations` WHERE `code` = 'IDBDO' LIMIT 1), 25000, 1, @now, @now);

-- shipments (13): 9 IN_TRANSIT, 2 DELIVERED, 1 BOOKED, 1 CREATED; 6 terlambat
INSERT INTO `shipments` (`id`, `shipment_number`, `reference_number`, `customer_id`, `carrier_id`, `origin_id`, `destination_id`, `warehouse_id`, `created_by`, `cargo_type`, `weight_kg`, `volume_cbm`, `expected_departure_at`, `expected_arrival_at`, `estimated_arrival_at`, `actual_arrival_at`, `current_status`, `current_latitude`, `current_longitude`, `current_location_label`, `last_event_at`, `is_delayed`, `delay_hours`, `delay_severity`, `deleted_at`, `created_at`, `updated_at`) VALUES
  (1, 'CT261109-HDYAK', 'PO-38529', 3, 1, 1, 2, NULL, 2, 'GENERAL', 11380.8, NULL, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND), 'DELIVERED', 51.954, 4.142, 'Port of Rotterdam', DATE_ADD(@now, INTERVAL -2574000 SECOND), 1, 5.0, 'LOW', NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (2, 'CT261109-W5HNE', 'PO-61084', 4, 6, 7, 8, NULL, 2, 'BULK', 31000.0, NULL, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND), 'DELIVERED', 18.95, 72.95, 'Nhava Sheva (Mumbai)', DATE_ADD(@now, INTERVAL -1555200 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (3, 'CT261109-ND9PX', 'PO-73901', 4, 2, 1, 2, NULL, 2, 'REFRIGERATED', 12200.0, NULL, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL 691200 SECOND), DATE_ADD(@now, INTERVAL 835200 SECOND), NULL, 'IN_TRANSIT', 31.26, 32.31, 'Port Said approach', DATE_ADD(@now, INTERVAL -427500 SECOND), 1, 40.0, 'HIGH', NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (4, 'CT261109-KR0AT', 'PO-48213', 3, 1, 1, 2, NULL, 2, 'CONTAINER_FCL', 18500.0, NULL, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL 1382400 SECOND), DATE_ADD(@now, INTERVAL 1382400 SECOND), NULL, 'IN_TRANSIT', 12.5, 45.0, 'Gulf of Aden', DATE_ADD(@now, INTERVAL -398400 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -398400 SECOND)),
  (5, 'CT261109-OTX7Y', 'PO-15642', 3, 3, 3, 4, NULL, 2, 'GENERAL', 24000.0, NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL 518400 SECOND), DATE_ADD(@now, INTERVAL 572400 SECOND), NULL, 'IN_TRANSIT', 36.0, -150.0, 'North Pacific (east)', DATE_ADD(@now, INTERVAL -281400 SECOND), 1, 15.0, 'MEDIUM', NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (6, 'CT261109-LYAWE', 'PO-26458', 3, 5, 5, 6, NULL, 2, 'GENERAL', 6400.0, NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL 172800 SECOND), DATE_ADD(@now, INTERVAL 187200 SECOND), NULL, 'IN_TRANSIT', -11.5, 126.0, 'Timor Sea', DATE_ADD(@now, INTERVAL -252600 SECOND), 1, 4.0, 'LOW', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (7, 'CT261109-0F6SM', 'PO-90377', 4, 4, 3, 4, NULL, 2, 'CONTAINER_FCL', 9800.0, NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL 691200 SECOND), DATE_ADD(@now, INTERVAL 691200 SECOND), NULL, 'IN_TRANSIT', 38.0, 160.0, 'North Pacific (west)', DATE_ADD(@now, INTERVAL -336600 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -336600 SECOND)),
  (8, 'CT261109-BFCDL', 'PO-52816', 4, 5, 5, 6, NULL, 2, 'CONTAINER_LCL', 3100.0, NULL, DATE_ADD(@now, INTERVAL -604800 SECOND), DATE_ADD(@now, INTERVAL 345600 SECOND), DATE_ADD(@now, INTERVAL 345600 SECOND), NULL, 'IN_TRANSIT', -8.7, 115.7, 'Lombok Strait', DATE_ADD(@now, INTERVAL -345600 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -345600 SECOND)),
  (9, 'CT261109-GKMDQ', 'PO-84370', 3, 7, 19, 20, 4, 2, 'GENERAL', 4200.0, NULL, DATE_ADD(@now, INTERVAL -345600 SECOND), DATE_ADD(@now, INTERVAL 86400 SECOND), DATE_ADD(@now, INTERVAL 194400 SECOND), NULL, 'IN_TRANSIT', -5.4, 105.25, 'Trans-Sumatra Toll Road (Lampung)', DATE_ADD(@now, INTERVAL -84600 SECOND), 1, 30.0, 'HIGH', NULL, DATE_ADD(@now, INTERVAL -432000 SECOND), DATE_ADD(@now, INTERVAL -84600 SECOND)),
  (10, 'CT261109-G2D0R', 'PO-67159', 4, 10, 16, 17, NULL, 2, 'GENERAL', 2400.0, NULL, DATE_ADD(@now, INTERVAL -28800 SECOND), DATE_ADD(@now, INTERVAL 14400 SECOND), DATE_ADD(@now, INTERVAL 43200 SECOND), NULL, 'IN_TRANSIT', 18.0, 67.0, 'Over the Arabian Sea', DATE_ADD(@now, INTERVAL -7200 SECOND), 1, 8.0, 'MEDIUM', NULL, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (11, 'CT261109-E8MTV', 'PO-30942', 4, 8, 21, 19, 2, 2, 'CONTAINER_LCL', 1800.0, NULL, DATE_ADD(@now, INTERVAL 172800 SECOND), DATE_ADD(@now, INTERVAL 345600 SECOND), DATE_ADD(@now, INTERVAL 345600 SECOND), NULL, 'BOOKED', -7.2575, 112.7521, 'Surabaya (Warehouse)', DATE_ADD(@now, INTERVAL -97200 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (12, 'CT261109-P2ZUC', 'PO-71584', 3, 9, 18, 16, NULL, 2, 'GENERAL', 850.0, NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL 3600 SECOND), DATE_ADD(@now, INTERVAL 3600 SECOND), NULL, 'IN_TRANSIT', -2.5, 105.2, 'Over the Java Sea', DATE_ADD(@now, INTERVAL -3600 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -50400 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (13, 'CT261109-J3QXB', 'PO-57236', 3, 2, 3, 4, NULL, 2, 'LIQUID', 21000.0, NULL, DATE_ADD(@now, INTERVAL 777600 SECOND), DATE_ADD(@now, INTERVAL 2160000 SECOND), DATE_ADD(@now, INTERVAL 2160000 SECOND), NULL, 'CREATED', NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), 0, 0.0, NULL, NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- shipment_items
INSERT INTO `shipment_items` (`id`, `shipment_id`, `description`, `quantity`, `weight_kg`, `volume_cbm`, `created_at`, `updated_at`) VALUES
  (1, 1, 'General consignment', 1, 11380.8, NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (2, 2, 'Bulk consignment', 1, 31000.0, NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (3, 3, 'Refrigerated consignment', 1, 12200.0, NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (4, 4, 'Container fcl consignment', 1, 18500.0, NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (5, 5, 'General consignment', 1, 24000.0, NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (6, 6, 'General consignment', 1, 6400.0, NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (7, 7, 'Container fcl consignment', 1, 9800.0, NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (8, 8, 'Container lcl consignment', 1, 3100.0, NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (9, 9, 'General consignment', 1, 4200.0, NULL, DATE_ADD(@now, INTERVAL -432000 SECOND), DATE_ADD(@now, INTERVAL -432000 SECOND)),
  (10, 10, 'General consignment', 1, 2400.0, NULL, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -172800 SECOND)),
  (11, 11, 'Container lcl consignment', 1, 1800.0, NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (12, 12, 'General consignment', 1, 850.0, NULL, DATE_ADD(@now, INTERVAL -50400 SECOND), DATE_ADD(@now, INTERVAL -50400 SECOND)),
  (13, 13, 'Liquid consignment', 1, 21000.0, NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- tracking_events
INSERT INTO `tracking_events` (`id`, `shipment_id`, `event_type`, `status`, `location_id`, `location_label`, `latitude`, `longitude`, `occurred_at`, `description`, `is_exception`, `created_by`, `created_at`, `updated_at`) VALUES
  (1, 1, 'STATUS_UPDATE', 'CREATED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5443200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (2, 1, 'STATUS_UPDATE', 'BOOKED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5356800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -5356800 SECOND), DATE_ADD(@now, INTERVAL -5356800 SECOND)),
  (3, 1, 'STATUS_UPDATE', 'PICKED_UP', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5184000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -5184000 SECOND)),
  (4, 1, 'STATUS_UPDATE', 'IN_TRANSIT', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -5162400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -5162400 SECOND), DATE_ADD(@now, INTERVAL -5162400 SECOND)),
  (5, 1, 'CHECKPOINT', NULL, NULL, 'Off Colombo, Sri Lanka', 6.95, 79.85, DATE_ADD(@now, INTERVAL -4645440 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -4645440 SECOND), DATE_ADD(@now, INTERVAL -4645440 SECOND)),
  (6, 1, 'CHECKPOINT', NULL, NULL, 'Gulf of Aden', 12.5, 45.0, DATE_ADD(@now, INTERVAL -4128480 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -4128480 SECOND), DATE_ADD(@now, INTERVAL -4128480 SECOND)),
  (7, 1, 'CHECKPOINT', NULL, NULL, 'Port Said approach', 31.26, 32.31, DATE_ADD(@now, INTERVAL -3611520 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -3611520 SECOND), DATE_ADD(@now, INTERVAL -3611520 SECOND)),
  (8, 1, 'CHECKPOINT', NULL, NULL, 'Strait of Gibraltar', 36.0, -5.6, DATE_ADD(@now, INTERVAL -3094560 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -3094560 SECOND), DATE_ADD(@now, INTERVAL -3094560 SECOND)),
  (9, 1, 'STATUS_UPDATE', 'DELIVERED', 2, 'Port of Rotterdam', 51.954, 4.142, DATE_ADD(@now, INTERVAL -2574000 SECOND), 'Cargo delivered to consignee.', 0, 2, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (10, 1, 'EXCEPTION', NULL, NULL, 'Port of Rotterdam', 51.954, 4.142, DATE_ADD(@now, INTERVAL -2581200 SECOND), '[DOCUMENTATION_ISSUE] Missing certificate of origin; corrected copy supplied.', 1, 2, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2581200 SECOND)),
  (11, 2, 'STATUS_UPDATE', 'CREATED', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2851200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (12, 2, 'STATUS_UPDATE', 'BOOKED', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2764800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -2764800 SECOND), DATE_ADD(@now, INTERVAL -2764800 SECOND)),
  (13, 2, 'STATUS_UPDATE', 'PICKED_UP', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2592000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND)),
  (14, 2, 'STATUS_UPDATE', 'IN_TRANSIT', 7, 'Belawan (Medan)', 3.783, 98.7, DATE_ADD(@now, INTERVAL -2570400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -2570400 SECOND), DATE_ADD(@now, INTERVAL -2570400 SECOND)),
  (15, 2, 'CHECKPOINT', NULL, NULL, 'Andaman Sea', 7.5, 94.0, DATE_ADD(@now, INTERVAL -2233200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -2233200 SECOND), DATE_ADD(@now, INTERVAL -2233200 SECOND)),
  (16, 2, 'CHECKPOINT', NULL, NULL, 'Arabian Sea', 14.0, 75.0, DATE_ADD(@now, INTERVAL -1896000 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -1896000 SECOND), DATE_ADD(@now, INTERVAL -1896000 SECOND)),
  (17, 2, 'STATUS_UPDATE', 'DELIVERED', 8, 'Nhava Sheva (Mumbai)', 18.95, 72.95, DATE_ADD(@now, INTERVAL -1555200 SECOND), 'Cargo delivered to consignee.', 0, 2, DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (18, 2, 'EXCEPTION', NULL, NULL, 'Nhava Sheva (Mumbai)', 18.95, 72.95, DATE_ADD(@now, INTERVAL -1562400 SECOND), '[DAMAGED] Minor packaging damage noted at discharge.', 1, 2, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1562400 SECOND)),
  (19, 3, 'STATUS_UPDATE', 'CREATED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1987200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (20, 3, 'STATUS_UPDATE', 'BOOKED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1900800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -1900800 SECOND), DATE_ADD(@now, INTERVAL -1900800 SECOND)),
  (21, 3, 'STATUS_UPDATE', 'PICKED_UP', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1728000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL -1728000 SECOND)),
  (22, 3, 'STATUS_UPDATE', 'IN_TRANSIT', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1706400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -1706400 SECOND), DATE_ADD(@now, INTERVAL -1706400 SECOND)),
  (23, 3, 'CHECKPOINT', NULL, NULL, 'Off Colombo, Sri Lanka', 6.95, 79.85, DATE_ADD(@now, INTERVAL -1280700 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -1280700 SECOND), DATE_ADD(@now, INTERVAL -1280700 SECOND)),
  (24, 3, 'CHECKPOINT', NULL, NULL, 'Gulf of Aden', 12.5, 45.0, DATE_ADD(@now, INTERVAL -855000 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -855000 SECOND), DATE_ADD(@now, INTERVAL -855000 SECOND)),
  (25, 3, 'CHECKPOINT', NULL, NULL, 'Port Said approach', 31.26, 32.31, DATE_ADD(@now, INTERVAL -429300 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -429300 SECOND), DATE_ADD(@now, INTERVAL -429300 SECOND)),
  (26, 3, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -427500 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -427500 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (27, 3, 'EXCEPTION', NULL, NULL, 'Port Said approach', 31.26, 32.31, DATE_ADD(@now, INTERVAL -7200 SECOND), '[PORT_CONGESTION] Anchorage queue at Port Said; berth window moved.', 1, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (28, 4, 'STATUS_UPDATE', 'CREATED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1468800 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (29, 4, 'STATUS_UPDATE', 'BOOKED', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1382400 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -1382400 SECOND), DATE_ADD(@now, INTERVAL -1382400 SECOND)),
  (30, 4, 'STATUS_UPDATE', 'PICKED_UP', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1209600 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL -1209600 SECOND)),
  (31, 4, 'STATUS_UPDATE', 'IN_TRANSIT', 1, 'Port of Singapore', 1.2644, 103.84, DATE_ADD(@now, INTERVAL -1188000 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -1188000 SECOND), DATE_ADD(@now, INTERVAL -1188000 SECOND)),
  (32, 4, 'CHECKPOINT', NULL, NULL, 'Off Colombo, Sri Lanka', 6.95, 79.85, DATE_ADD(@now, INTERVAL -793200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -793200 SECOND), DATE_ADD(@now, INTERVAL -793200 SECOND)),
  (33, 4, 'CHECKPOINT', NULL, NULL, 'Gulf of Aden', 12.5, 45.0, DATE_ADD(@now, INTERVAL -398400 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -398400 SECOND), DATE_ADD(@now, INTERVAL -398400 SECOND)),
  (34, 5, 'STATUS_UPDATE', 'CREATED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -1123200 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (35, 5, 'STATUS_UPDATE', 'BOOKED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -1036800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (36, 5, 'STATUS_UPDATE', 'PICKED_UP', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -864000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (37, 5, 'STATUS_UPDATE', 'IN_TRANSIT', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -842400 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -842400 SECOND), DATE_ADD(@now, INTERVAL -842400 SECOND)),
  (38, 5, 'CHECKPOINT', NULL, NULL, 'North Pacific (west)', 38.0, 160.0, DATE_ADD(@now, INTERVAL -562800 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -562800 SECOND), DATE_ADD(@now, INTERVAL -562800 SECOND)),
  (39, 5, 'CHECKPOINT', NULL, NULL, 'North Pacific (east)', 36.0, -150.0, DATE_ADD(@now, INTERVAL -283200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -283200 SECOND), DATE_ADD(@now, INTERVAL -283200 SECOND)),
  (40, 5, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -281400 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -281400 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (41, 5, 'EXCEPTION', NULL, NULL, 'North Pacific (east)', 36.0, -150.0, DATE_ADD(@now, INTERVAL -7200 SECOND), '[WEATHER_DELAY] Vessel reduced speed because of storm conditions.', 1, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (42, 6, 'STATUS_UPDATE', 'CREATED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -1036800 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (43, 6, 'STATUS_UPDATE', 'BOOKED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -950400 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (44, 6, 'STATUS_UPDATE', 'PICKED_UP', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -777600 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (45, 6, 'STATUS_UPDATE', 'IN_TRANSIT', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -756000 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -756000 SECOND), DATE_ADD(@now, INTERVAL -756000 SECOND)),
  (46, 6, 'CHECKPOINT', NULL, NULL, 'Lombok Strait', -8.7, 115.7, DATE_ADD(@now, INTERVAL -505200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -505200 SECOND), DATE_ADD(@now, INTERVAL -505200 SECOND)),
  (47, 6, 'CHECKPOINT', NULL, NULL, 'Timor Sea', -11.5, 126.0, DATE_ADD(@now, INTERVAL -254400 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -254400 SECOND), DATE_ADD(@now, INTERVAL -254400 SECOND)),
  (48, 6, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -252600 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -252600 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (49, 7, 'STATUS_UPDATE', 'CREATED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -950400 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (50, 7, 'STATUS_UPDATE', 'BOOKED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -864000 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (51, 7, 'STATUS_UPDATE', 'PICKED_UP', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -691200 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (52, 7, 'STATUS_UPDATE', 'IN_TRANSIT', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -669600 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -669600 SECOND), DATE_ADD(@now, INTERVAL -669600 SECOND)),
  (53, 7, 'CHECKPOINT', NULL, NULL, 'North Pacific (west)', 38.0, 160.0, DATE_ADD(@now, INTERVAL -336600 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -336600 SECOND), DATE_ADD(@now, INTERVAL -336600 SECOND)),
  (54, 8, 'STATUS_UPDATE', 'CREATED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -777600 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (55, 8, 'STATUS_UPDATE', 'BOOKED', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -691200 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (56, 8, 'STATUS_UPDATE', 'PICKED_UP', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -604800 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -604800 SECOND), DATE_ADD(@now, INTERVAL -604800 SECOND)),
  (57, 8, 'STATUS_UPDATE', 'IN_TRANSIT', 5, 'Tanjung Priok (Jakarta)', -6.1, 106.88, DATE_ADD(@now, INTERVAL -583200 SECOND), 'Vessel departed origin port.', 0, 2, DATE_ADD(@now, INTERVAL -583200 SECOND), DATE_ADD(@now, INTERVAL -583200 SECOND)),
  (58, 8, 'CHECKPOINT', NULL, NULL, 'Lombok Strait', -8.7, 115.7, DATE_ADD(@now, INTERVAL -345600 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -345600 SECOND), DATE_ADD(@now, INTERVAL -345600 SECOND)),
  (59, 9, 'STATUS_UPDATE', 'CREATED', 19, 'Bandung (Warehouse)', -6.9175, 107.6191, DATE_ADD(@now, INTERVAL -432000 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -432000 SECOND), DATE_ADD(@now, INTERVAL -432000 SECOND)),
  (60, 9, 'STATUS_UPDATE', 'BOOKED', 19, 'Bandung (Warehouse)', -6.9175, 107.6191, DATE_ADD(@now, INTERVAL -388800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -388800 SECOND), DATE_ADD(@now, INTERVAL -388800 SECOND)),
  (61, 9, 'STATUS_UPDATE', 'PICKED_UP', 19, 'Bandung (Warehouse)', -6.9175, 107.6191, DATE_ADD(@now, INTERVAL -345600 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -345600 SECOND), DATE_ADD(@now, INTERVAL -345600 SECOND)),
  (62, 9, 'STATUS_UPDATE', 'IN_TRANSIT', 19, 'Bandung (Warehouse)', -6.9175, 107.6191, DATE_ADD(@now, INTERVAL -343800 SECOND), 'Truck departed origin warehouse.', 0, 2, DATE_ADD(@now, INTERVAL -343800 SECOND), DATE_ADD(@now, INTERVAL -343800 SECOND)),
  (63, 9, 'CHECKPOINT', NULL, NULL, 'Trans-Java Toll Road (Cikampek)', -6.4, 107.45, DATE_ADD(@now, INTERVAL -259200 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -259200 SECOND), DATE_ADD(@now, INTERVAL -259200 SECOND)),
  (64, 9, 'CHECKPOINT', NULL, NULL, 'Merak Port (Cilegon)', -5.93, 106.0, DATE_ADD(@now, INTERVAL -172800 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -172800 SECOND)),
  (65, 9, 'CHECKPOINT', NULL, NULL, 'Trans-Sumatra Toll Road (Lampung)', -5.4, 105.25, DATE_ADD(@now, INTERVAL -86400 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -86400 SECOND), DATE_ADD(@now, INTERVAL -86400 SECOND)),
  (66, 9, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -84600 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -84600 SECOND), DATE_ADD(@now, INTERVAL -84600 SECOND)),
  (67, 9, 'EXCEPTION', NULL, NULL, 'Trans-Sumatra Toll Road (Lampung)', -5.4, 105.25, DATE_ADD(@now, INTERVAL -43200 SECOND), '[MISROUTED] Shipment was routed through the wrong transfer hub; re-routing in progress.', 1, 2, DATE_ADD(@now, INTERVAL -43200 SECOND), DATE_ADD(@now, INTERVAL -43200 SECOND)),
  (68, 10, 'STATUS_UPDATE', 'CREATED', 16, 'Changi Airport (Singapore)', 1.3644, 103.9915, DATE_ADD(@now, INTERVAL -172800 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -172800 SECOND)),
  (69, 10, 'STATUS_UPDATE', 'BOOKED', 16, 'Changi Airport (Singapore)', 1.3644, 103.9915, DATE_ADD(@now, INTERVAL -158400 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -158400 SECOND), DATE_ADD(@now, INTERVAL -158400 SECOND)),
  (70, 10, 'STATUS_UPDATE', 'PICKED_UP', 16, 'Changi Airport (Singapore)', 1.3644, 103.9915, DATE_ADD(@now, INTERVAL -32400 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -32400 SECOND), DATE_ADD(@now, INTERVAL -32400 SECOND)),
  (71, 10, 'STATUS_UPDATE', 'IN_TRANSIT', 16, 'Changi Airport (Singapore)', 1.3644, 103.9915, DATE_ADD(@now, INTERVAL -28800 SECOND), 'Aircraft departed origin airport.', 0, 2, DATE_ADD(@now, INTERVAL -28800 SECOND), DATE_ADD(@now, INTERVAL -28800 SECOND)),
  (72, 10, 'CHECKPOINT', NULL, NULL, 'Over the Bay of Bengal', 12.0, 87.0, DATE_ADD(@now, INTERVAL -18000 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -18000 SECOND), DATE_ADD(@now, INTERVAL -18000 SECOND)),
  (73, 10, 'CHECKPOINT', NULL, NULL, 'Over the Arabian Sea', 18.0, 67.0, DATE_ADD(@now, INTERVAL -9000 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -9000 SECOND), DATE_ADD(@now, INTERVAL -9000 SECOND)),
  (74, 10, 'ETA_UPDATE', NULL, NULL, NULL, NULL, NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), 'Carrier revised the ETA.', 0, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (75, 10, 'EXCEPTION', NULL, NULL, 'Over the Arabian Sea', 18.0, 67.0, DATE_ADD(@now, INTERVAL -10800 SECOND), '[WEATHER_DELAY] Flight held for en-route weather; reduced cruise speed.', 1, 2, DATE_ADD(@now, INTERVAL -10800 SECOND), DATE_ADD(@now, INTERVAL -10800 SECOND)),
  (76, 11, 'STATUS_UPDATE', 'CREATED', 21, 'Surabaya (Warehouse)', -7.2575, 112.7521, DATE_ADD(@now, INTERVAL -108000 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (77, 11, 'STATUS_UPDATE', 'BOOKED', 21, 'Surabaya (Warehouse)', -7.2575, 112.7521, DATE_ADD(@now, INTERVAL -97200 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -97200 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (78, 12, 'STATUS_UPDATE', 'CREATED', 18, 'Soekarno-Hatta Airport (Jakarta)', -6.1256, 106.6559, DATE_ADD(@now, INTERVAL -50400 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -50400 SECOND), DATE_ADD(@now, INTERVAL -50400 SECOND)),
  (79, 12, 'STATUS_UPDATE', 'BOOKED', 18, 'Soekarno-Hatta Airport (Jakarta)', -6.1256, 106.6559, DATE_ADD(@now, INTERVAL -46800 SECOND), 'Booking confirmed with carrier.', 0, 2, DATE_ADD(@now, INTERVAL -46800 SECOND), DATE_ADD(@now, INTERVAL -46800 SECOND)),
  (80, 12, 'STATUS_UPDATE', 'PICKED_UP', 18, 'Soekarno-Hatta Airport (Jakarta)', -6.1256, 106.6559, DATE_ADD(@now, INTERVAL -9000 SECOND), 'Cargo picked up and gated in at origin terminal.', 0, 2, DATE_ADD(@now, INTERVAL -9000 SECOND), DATE_ADD(@now, INTERVAL -9000 SECOND)),
  (81, 12, 'STATUS_UPDATE', 'IN_TRANSIT', 18, 'Soekarno-Hatta Airport (Jakarta)', -6.1256, 106.6559, DATE_ADD(@now, INTERVAL -7200 SECOND), 'Aircraft departed origin airport.', 0, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (82, 12, 'CHECKPOINT', NULL, NULL, 'Over the Java Sea', -2.5, 105.2, DATE_ADD(@now, INTERVAL -3600 SECOND), 'Position report (demo data).', 0, 2, DATE_ADD(@now, INTERVAL -3600 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (83, 13, 'STATUS_UPDATE', 'CREATED', 3, 'Port of Shanghai', 31.35, 121.6, DATE_ADD(@now, INTERVAL -21600 SECOND), 'Shipment created.', 0, 2, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- shipment_exceptions (4 terbuka/diselidiki, 2 selesai)
INSERT INTO `shipment_exceptions` (`id`, `shipment_id`, `type`, `severity`, `status`, `description`, `detected_at`, `resolved_at`, `resolution_notes`, `reported_by`, `created_at`, `updated_at`) VALUES
  (1, 1, 'DOCUMENTATION_ISSUE', 'MEDIUM', 'RESOLVED', 'Missing certificate of origin; corrected copy supplied.', DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND), 'Handled with carrier and customer.', 2, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND)),
  (2, 2, 'DAMAGED', 'LOW', 'RESOLVED', 'Minor packaging damage noted at discharge.', DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND), 'Handled with carrier and customer.', 2, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND)),
  (3, 3, 'PORT_CONGESTION', 'HIGH', 'OPEN', 'Anchorage queue at Port Said; berth window moved.', DATE_ADD(@now, INTERVAL -7200 SECOND), NULL, NULL, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (4, 5, 'WEATHER_DELAY', 'MEDIUM', 'INVESTIGATING', 'Vessel reduced speed because of storm conditions.', DATE_ADD(@now, INTERVAL -7200 SECOND), NULL, NULL, 2, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (5, 9, 'MISROUTED', 'HIGH', 'OPEN', 'Shipment was routed through the wrong transfer hub; re-routing in progress.', DATE_ADD(@now, INTERVAL -43200 SECOND), NULL, NULL, 2, DATE_ADD(@now, INTERVAL -43200 SECOND), DATE_ADD(@now, INTERVAL -43200 SECOND)),
  (6, 10, 'WEATHER_DELAY', 'MEDIUM', 'OPEN', 'Flight held for en-route weather; reduced cruise speed.', DATE_ADD(@now, INTERVAL -10800 SECOND), NULL, NULL, 2, DATE_ADD(@now, INTERVAL -10800 SECOND), DATE_ADD(@now, INTERVAL -10800 SECOND));

-- notifications
INSERT INTO `notifications` (`id`, `user_id`, `shipment_id`, `type`, `message`, `read_at`, `created_at`, `updated_at`) VALUES
  (1, 3, 1, 'SHIPMENT_CREATED', 'Shipment CT261109-HDYAK has been created.', NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (2, 3, 1, 'STATUS_CHANGED', 'Shipment CT261109-HDYAK is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -5356800 SECOND), DATE_ADD(@now, INTERVAL -5356800 SECOND)),
  (3, 3, 1, 'STATUS_CHANGED', 'Shipment CT261109-HDYAK is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -5184000 SECOND)),
  (4, 3, 1, 'STATUS_CHANGED', 'Shipment CT261109-HDYAK is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -5162400 SECOND), DATE_ADD(@now, INTERVAL -5162400 SECOND)),
  (5, 3, 1, 'STATUS_CHANGED', 'Shipment CT261109-HDYAK is now DELIVERED.', NULL, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (6, 3, 1, 'SHIPMENT_DELAYED', 'Shipment CT261109-HDYAK is delayed by 5.0 h (LOW).', NULL, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (7, 3, 1, 'EXCEPTION_OPENED', 'Exception DOCUMENTATION_ISSUE (MEDIUM) on shipment CT261109-HDYAK.', NULL, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2581200 SECOND)),
  (8, 3, 1, 'EXCEPTION_RESOLVED', 'Exception DOCUMENTATION_ISSUE on shipment CT261109-HDYAK was resolved.', NULL, DATE_ADD(@now, INTERVAL -2552400 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND)),
  (9, 4, 2, 'SHIPMENT_CREATED', 'Shipment CT261109-W5HNE has been created.', NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (10, 4, 2, 'STATUS_CHANGED', 'Shipment CT261109-W5HNE is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -2764800 SECOND), DATE_ADD(@now, INTERVAL -2764800 SECOND)),
  (11, 4, 2, 'STATUS_CHANGED', 'Shipment CT261109-W5HNE is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND)),
  (12, 4, 2, 'STATUS_CHANGED', 'Shipment CT261109-W5HNE is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -2570400 SECOND), DATE_ADD(@now, INTERVAL -2570400 SECOND)),
  (13, 4, 2, 'STATUS_CHANGED', 'Shipment CT261109-W5HNE is now DELIVERED.', NULL, DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (14, 4, 2, 'EXCEPTION_OPENED', 'Exception DAMAGED (LOW) on shipment CT261109-W5HNE.', NULL, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1562400 SECOND)),
  (15, 4, 2, 'EXCEPTION_RESOLVED', 'Exception DAMAGED on shipment CT261109-W5HNE was resolved.', NULL, DATE_ADD(@now, INTERVAL -1533600 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND)),
  (16, 4, 3, 'SHIPMENT_CREATED', 'Shipment CT261109-ND9PX has been created.', NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (17, 4, 3, 'STATUS_CHANGED', 'Shipment CT261109-ND9PX is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -1900800 SECOND), DATE_ADD(@now, INTERVAL -1900800 SECOND)),
  (18, 4, 3, 'STATUS_CHANGED', 'Shipment CT261109-ND9PX is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL -1728000 SECOND)),
  (19, 4, 3, 'STATUS_CHANGED', 'Shipment CT261109-ND9PX is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -1706400 SECOND), DATE_ADD(@now, INTERVAL -1706400 SECOND)),
  (20, 4, 3, 'SHIPMENT_DELAYED', 'Shipment CT261109-ND9PX is delayed by 40.0 h (HIGH).', NULL, DATE_ADD(@now, INTERVAL -427500 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (21, 4, 3, 'EXCEPTION_OPENED', 'Exception PORT_CONGESTION (HIGH) on shipment CT261109-ND9PX.', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (22, 3, 4, 'SHIPMENT_CREATED', 'Shipment CT261109-KR0AT has been created.', NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (23, 3, 4, 'STATUS_CHANGED', 'Shipment CT261109-KR0AT is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -1382400 SECOND), DATE_ADD(@now, INTERVAL -1382400 SECOND)),
  (24, 3, 4, 'STATUS_CHANGED', 'Shipment CT261109-KR0AT is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL -1209600 SECOND)),
  (25, 3, 4, 'STATUS_CHANGED', 'Shipment CT261109-KR0AT is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -1188000 SECOND), DATE_ADD(@now, INTERVAL -1188000 SECOND)),
  (26, 3, 5, 'SHIPMENT_CREATED', 'Shipment CT261109-OTX7Y has been created.', NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (27, 3, 5, 'STATUS_CHANGED', 'Shipment CT261109-OTX7Y is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (28, 3, 5, 'STATUS_CHANGED', 'Shipment CT261109-OTX7Y is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (29, 3, 5, 'STATUS_CHANGED', 'Shipment CT261109-OTX7Y is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -842400 SECOND), DATE_ADD(@now, INTERVAL -842400 SECOND)),
  (30, 3, 5, 'SHIPMENT_DELAYED', 'Shipment CT261109-OTX7Y is delayed by 15.0 h (MEDIUM).', NULL, DATE_ADD(@now, INTERVAL -281400 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (31, 3, 5, 'EXCEPTION_OPENED', 'Exception WEATHER_DELAY (MEDIUM) on shipment CT261109-OTX7Y.', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (32, 3, 6, 'SHIPMENT_CREATED', 'Shipment CT261109-LYAWE has been created.', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (33, 3, 6, 'STATUS_CHANGED', 'Shipment CT261109-LYAWE is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (34, 3, 6, 'STATUS_CHANGED', 'Shipment CT261109-LYAWE is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (35, 3, 6, 'STATUS_CHANGED', 'Shipment CT261109-LYAWE is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -756000 SECOND), DATE_ADD(@now, INTERVAL -756000 SECOND)),
  (36, 3, 6, 'SHIPMENT_DELAYED', 'Shipment CT261109-LYAWE is delayed by 4.0 h (LOW).', NULL, DATE_ADD(@now, INTERVAL -252600 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (37, 4, 7, 'SHIPMENT_CREATED', 'Shipment CT261109-0F6SM has been created.', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (38, 4, 7, 'STATUS_CHANGED', 'Shipment CT261109-0F6SM is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (39, 4, 7, 'STATUS_CHANGED', 'Shipment CT261109-0F6SM is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (40, 4, 7, 'STATUS_CHANGED', 'Shipment CT261109-0F6SM is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -669600 SECOND), DATE_ADD(@now, INTERVAL -669600 SECOND)),
  (41, 4, 8, 'SHIPMENT_CREATED', 'Shipment CT261109-BFCDL has been created.', NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (42, 4, 8, 'STATUS_CHANGED', 'Shipment CT261109-BFCDL is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (43, 4, 8, 'STATUS_CHANGED', 'Shipment CT261109-BFCDL is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -604800 SECOND), DATE_ADD(@now, INTERVAL -604800 SECOND)),
  (44, 4, 8, 'STATUS_CHANGED', 'Shipment CT261109-BFCDL is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -583200 SECOND), DATE_ADD(@now, INTERVAL -583200 SECOND)),
  (45, 3, 9, 'SHIPMENT_CREATED', 'Shipment CT261109-GKMDQ has been created.', NULL, DATE_ADD(@now, INTERVAL -432000 SECOND), DATE_ADD(@now, INTERVAL -432000 SECOND)),
  (46, 3, 9, 'STATUS_CHANGED', 'Shipment CT261109-GKMDQ is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -388800 SECOND), DATE_ADD(@now, INTERVAL -388800 SECOND)),
  (47, 3, 9, 'STATUS_CHANGED', 'Shipment CT261109-GKMDQ is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -345600 SECOND), DATE_ADD(@now, INTERVAL -345600 SECOND)),
  (48, 3, 9, 'STATUS_CHANGED', 'Shipment CT261109-GKMDQ is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -343800 SECOND), DATE_ADD(@now, INTERVAL -343800 SECOND)),
  (49, 3, 9, 'SHIPMENT_DELAYED', 'Shipment CT261109-GKMDQ is delayed by 30.0 h (HIGH).', NULL, DATE_ADD(@now, INTERVAL -84600 SECOND), DATE_ADD(@now, INTERVAL -84600 SECOND)),
  (50, 3, 9, 'EXCEPTION_OPENED', 'Exception MISROUTED (HIGH) on shipment CT261109-GKMDQ.', NULL, DATE_ADD(@now, INTERVAL -43200 SECOND), DATE_ADD(@now, INTERVAL -43200 SECOND)),
  (51, 4, 10, 'SHIPMENT_CREATED', 'Shipment CT261109-G2D0R has been created.', NULL, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -172800 SECOND)),
  (52, 4, 10, 'STATUS_CHANGED', 'Shipment CT261109-G2D0R is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -158400 SECOND), DATE_ADD(@now, INTERVAL -158400 SECOND)),
  (53, 4, 10, 'STATUS_CHANGED', 'Shipment CT261109-G2D0R is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -32400 SECOND), DATE_ADD(@now, INTERVAL -32400 SECOND)),
  (54, 4, 10, 'STATUS_CHANGED', 'Shipment CT261109-G2D0R is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -28800 SECOND), DATE_ADD(@now, INTERVAL -28800 SECOND)),
  (55, 4, 10, 'SHIPMENT_DELAYED', 'Shipment CT261109-G2D0R is delayed by 8.0 h (MEDIUM).', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (56, 4, 10, 'EXCEPTION_OPENED', 'Exception WEATHER_DELAY (MEDIUM) on shipment CT261109-G2D0R.', NULL, DATE_ADD(@now, INTERVAL -10800 SECOND), DATE_ADD(@now, INTERVAL -10800 SECOND)),
  (57, 4, 11, 'SHIPMENT_CREATED', 'Shipment CT261109-E8MTV has been created.', NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (58, 4, 11, 'STATUS_CHANGED', 'Shipment CT261109-E8MTV is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -97200 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (59, 3, 12, 'SHIPMENT_CREATED', 'Shipment CT261109-P2ZUC has been created.', NULL, DATE_ADD(@now, INTERVAL -50400 SECOND), DATE_ADD(@now, INTERVAL -50400 SECOND)),
  (60, 3, 12, 'STATUS_CHANGED', 'Shipment CT261109-P2ZUC is now BOOKED.', NULL, DATE_ADD(@now, INTERVAL -46800 SECOND), DATE_ADD(@now, INTERVAL -46800 SECOND)),
  (61, 3, 12, 'STATUS_CHANGED', 'Shipment CT261109-P2ZUC is now PICKED_UP.', NULL, DATE_ADD(@now, INTERVAL -9000 SECOND), DATE_ADD(@now, INTERVAL -9000 SECOND)),
  (62, 3, 12, 'STATUS_CHANGED', 'Shipment CT261109-P2ZUC is now IN_TRANSIT.', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (63, 3, 13, 'SHIPMENT_CREATED', 'Shipment CT261109-J3QXB has been created.', NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

-- audit_logs
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `changes`, `ip_address`, `created_at`, `updated_at`) VALUES
  (1, 2, 'shipment.created', 'Shipment', 1, '{"shipment_number":"CT261109-HDYAK","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -5443200 SECOND), DATE_ADD(@now, INTERVAL -5443200 SECOND)),
  (2, 2, 'tracking.added', 'Shipment', 1, '{"event_id":2,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -5356800 SECOND), DATE_ADD(@now, INTERVAL -5356800 SECOND)),
  (3, 2, 'tracking.added', 'Shipment', 1, '{"event_id":3,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -5184000 SECOND), DATE_ADD(@now, INTERVAL -5184000 SECOND)),
  (4, 2, 'tracking.added', 'Shipment', 1, '{"event_id":4,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -5162400 SECOND), DATE_ADD(@now, INTERVAL -5162400 SECOND)),
  (5, 2, 'tracking.added', 'Shipment', 1, '{"event_id":5,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -4645440 SECOND), DATE_ADD(@now, INTERVAL -4645440 SECOND)),
  (6, 2, 'tracking.added', 'Shipment', 1, '{"event_id":6,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -4128480 SECOND), DATE_ADD(@now, INTERVAL -4128480 SECOND)),
  (7, 2, 'tracking.added', 'Shipment', 1, '{"event_id":7,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -3611520 SECOND), DATE_ADD(@now, INTERVAL -3611520 SECOND)),
  (8, 2, 'tracking.added', 'Shipment', 1, '{"event_id":8,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -3094560 SECOND), DATE_ADD(@now, INTERVAL -3094560 SECOND)),
  (9, 2, 'tracking.added', 'Shipment', 1, '{"event_id":9,"event_type":"STATUS_UPDATE","status":"DELIVERED"}', NULL, DATE_ADD(@now, INTERVAL -2574000 SECOND), DATE_ADD(@now, INTERVAL -2574000 SECOND)),
  (10, 2, 'exception.opened', 'ShipmentException', 1, '{"shipment_id":1,"type":"DOCUMENTATION_ISSUE"}', NULL, DATE_ADD(@now, INTERVAL -2581200 SECOND), DATE_ADD(@now, INTERVAL -2581200 SECOND)),
  (11, 2, 'exception.updated', 'ShipmentException', 1, '{"status":{"from":"OPEN","to":"RESOLVED"},"resolution_notes":"updated"}', NULL, DATE_ADD(@now, INTERVAL -2552400 SECOND), DATE_ADD(@now, INTERVAL -2552400 SECOND)),
  (12, 2, 'shipment.created', 'Shipment', 2, '{"shipment_number":"CT261109-W5HNE","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -2851200 SECOND), DATE_ADD(@now, INTERVAL -2851200 SECOND)),
  (13, 2, 'tracking.added', 'Shipment', 2, '{"event_id":12,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -2764800 SECOND), DATE_ADD(@now, INTERVAL -2764800 SECOND)),
  (14, 2, 'tracking.added', 'Shipment', 2, '{"event_id":13,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -2592000 SECOND), DATE_ADD(@now, INTERVAL -2592000 SECOND)),
  (15, 2, 'tracking.added', 'Shipment', 2, '{"event_id":14,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -2570400 SECOND), DATE_ADD(@now, INTERVAL -2570400 SECOND)),
  (16, 2, 'tracking.added', 'Shipment', 2, '{"event_id":15,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -2233200 SECOND), DATE_ADD(@now, INTERVAL -2233200 SECOND)),
  (17, 2, 'tracking.added', 'Shipment', 2, '{"event_id":16,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -1896000 SECOND), DATE_ADD(@now, INTERVAL -1896000 SECOND)),
  (18, 2, 'tracking.added', 'Shipment', 2, '{"event_id":17,"event_type":"STATUS_UPDATE","status":"DELIVERED"}', NULL, DATE_ADD(@now, INTERVAL -1555200 SECOND), DATE_ADD(@now, INTERVAL -1555200 SECOND)),
  (19, 2, 'exception.opened', 'ShipmentException', 2, '{"shipment_id":2,"type":"DAMAGED"}', NULL, DATE_ADD(@now, INTERVAL -1562400 SECOND), DATE_ADD(@now, INTERVAL -1562400 SECOND)),
  (20, 2, 'exception.updated', 'ShipmentException', 2, '{"status":{"from":"OPEN","to":"RESOLVED"},"resolution_notes":"updated"}', NULL, DATE_ADD(@now, INTERVAL -1533600 SECOND), DATE_ADD(@now, INTERVAL -1533600 SECOND)),
  (21, 2, 'shipment.created', 'Shipment', 3, '{"shipment_number":"CT261109-ND9PX","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -1987200 SECOND), DATE_ADD(@now, INTERVAL -1987200 SECOND)),
  (22, 2, 'tracking.added', 'Shipment', 3, '{"event_id":20,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -1900800 SECOND), DATE_ADD(@now, INTERVAL -1900800 SECOND)),
  (23, 2, 'tracking.added', 'Shipment', 3, '{"event_id":21,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -1728000 SECOND), DATE_ADD(@now, INTERVAL -1728000 SECOND)),
  (24, 2, 'tracking.added', 'Shipment', 3, '{"event_id":22,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -1706400 SECOND), DATE_ADD(@now, INTERVAL -1706400 SECOND)),
  (25, 2, 'tracking.added', 'Shipment', 3, '{"event_id":23,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -1280700 SECOND), DATE_ADD(@now, INTERVAL -1280700 SECOND)),
  (26, 2, 'tracking.added', 'Shipment', 3, '{"event_id":24,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -855000 SECOND), DATE_ADD(@now, INTERVAL -855000 SECOND)),
  (27, 2, 'tracking.added', 'Shipment', 3, '{"event_id":25,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -429300 SECOND), DATE_ADD(@now, INTERVAL -429300 SECOND)),
  (28, 2, 'tracking.added', 'Shipment', 3, '{"event_id":26,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -427500 SECOND), DATE_ADD(@now, INTERVAL -427500 SECOND)),
  (29, 2, 'exception.opened', 'ShipmentException', 3, '{"shipment_id":3,"type":"PORT_CONGESTION"}', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (30, 2, 'shipment.created', 'Shipment', 4, '{"shipment_number":"CT261109-KR0AT","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -1468800 SECOND), DATE_ADD(@now, INTERVAL -1468800 SECOND)),
  (31, 2, 'tracking.added', 'Shipment', 4, '{"event_id":29,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -1382400 SECOND), DATE_ADD(@now, INTERVAL -1382400 SECOND)),
  (32, 2, 'tracking.added', 'Shipment', 4, '{"event_id":30,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -1209600 SECOND), DATE_ADD(@now, INTERVAL -1209600 SECOND)),
  (33, 2, 'tracking.added', 'Shipment', 4, '{"event_id":31,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -1188000 SECOND), DATE_ADD(@now, INTERVAL -1188000 SECOND)),
  (34, 2, 'tracking.added', 'Shipment', 4, '{"event_id":32,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -793200 SECOND), DATE_ADD(@now, INTERVAL -793200 SECOND)),
  (35, 2, 'tracking.added', 'Shipment', 4, '{"event_id":33,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -398400 SECOND), DATE_ADD(@now, INTERVAL -398400 SECOND)),
  (36, 2, 'shipment.created', 'Shipment', 5, '{"shipment_number":"CT261109-OTX7Y","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -1123200 SECOND), DATE_ADD(@now, INTERVAL -1123200 SECOND)),
  (37, 2, 'tracking.added', 'Shipment', 5, '{"event_id":35,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (38, 2, 'tracking.added', 'Shipment', 5, '{"event_id":36,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (39, 2, 'tracking.added', 'Shipment', 5, '{"event_id":37,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -842400 SECOND), DATE_ADD(@now, INTERVAL -842400 SECOND)),
  (40, 2, 'tracking.added', 'Shipment', 5, '{"event_id":38,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -562800 SECOND), DATE_ADD(@now, INTERVAL -562800 SECOND)),
  (41, 2, 'tracking.added', 'Shipment', 5, '{"event_id":39,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -283200 SECOND), DATE_ADD(@now, INTERVAL -283200 SECOND)),
  (42, 2, 'tracking.added', 'Shipment', 5, '{"event_id":40,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -281400 SECOND), DATE_ADD(@now, INTERVAL -281400 SECOND)),
  (43, 2, 'exception.opened', 'ShipmentException', 4, '{"shipment_id":5,"type":"WEATHER_DELAY"}', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (44, 2, 'exception.updated', 'ShipmentException', 4, '{"status":{"from":"OPEN","to":"INVESTIGATING"}}', NULL, DATE_ADD(@now, INTERVAL -3600 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (45, 2, 'shipment.created', 'Shipment', 6, '{"shipment_number":"CT261109-LYAWE","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -1036800 SECOND), DATE_ADD(@now, INTERVAL -1036800 SECOND)),
  (46, 2, 'tracking.added', 'Shipment', 6, '{"event_id":43,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (47, 2, 'tracking.added', 'Shipment', 6, '{"event_id":44,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (48, 2, 'tracking.added', 'Shipment', 6, '{"event_id":45,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -756000 SECOND), DATE_ADD(@now, INTERVAL -756000 SECOND)),
  (49, 2, 'tracking.added', 'Shipment', 6, '{"event_id":46,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -505200 SECOND), DATE_ADD(@now, INTERVAL -505200 SECOND)),
  (50, 2, 'tracking.added', 'Shipment', 6, '{"event_id":47,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -254400 SECOND), DATE_ADD(@now, INTERVAL -254400 SECOND)),
  (51, 2, 'tracking.added', 'Shipment', 6, '{"event_id":48,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -252600 SECOND), DATE_ADD(@now, INTERVAL -252600 SECOND)),
  (52, 2, 'shipment.created', 'Shipment', 7, '{"shipment_number":"CT261109-0F6SM","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -950400 SECOND), DATE_ADD(@now, INTERVAL -950400 SECOND)),
  (53, 2, 'tracking.added', 'Shipment', 7, '{"event_id":50,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -864000 SECOND), DATE_ADD(@now, INTERVAL -864000 SECOND)),
  (54, 2, 'tracking.added', 'Shipment', 7, '{"event_id":51,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (55, 2, 'tracking.added', 'Shipment', 7, '{"event_id":52,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -669600 SECOND), DATE_ADD(@now, INTERVAL -669600 SECOND)),
  (56, 2, 'tracking.added', 'Shipment', 7, '{"event_id":53,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -336600 SECOND), DATE_ADD(@now, INTERVAL -336600 SECOND)),
  (57, 2, 'shipment.created', 'Shipment', 8, '{"shipment_number":"CT261109-BFCDL","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -777600 SECOND), DATE_ADD(@now, INTERVAL -777600 SECOND)),
  (58, 2, 'tracking.added', 'Shipment', 8, '{"event_id":55,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -691200 SECOND), DATE_ADD(@now, INTERVAL -691200 SECOND)),
  (59, 2, 'tracking.added', 'Shipment', 8, '{"event_id":56,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -604800 SECOND), DATE_ADD(@now, INTERVAL -604800 SECOND)),
  (60, 2, 'tracking.added', 'Shipment', 8, '{"event_id":57,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -583200 SECOND), DATE_ADD(@now, INTERVAL -583200 SECOND)),
  (61, 2, 'tracking.added', 'Shipment', 8, '{"event_id":58,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -345600 SECOND), DATE_ADD(@now, INTERVAL -345600 SECOND)),
  (62, 2, 'shipment.created', 'Shipment', 9, '{"shipment_number":"CT261109-GKMDQ","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -432000 SECOND), DATE_ADD(@now, INTERVAL -432000 SECOND)),
  (63, 2, 'tracking.added', 'Shipment', 9, '{"event_id":60,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -388800 SECOND), DATE_ADD(@now, INTERVAL -388800 SECOND)),
  (64, 2, 'tracking.added', 'Shipment', 9, '{"event_id":61,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -345600 SECOND), DATE_ADD(@now, INTERVAL -345600 SECOND)),
  (65, 2, 'tracking.added', 'Shipment', 9, '{"event_id":62,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -343800 SECOND), DATE_ADD(@now, INTERVAL -343800 SECOND)),
  (66, 2, 'tracking.added', 'Shipment', 9, '{"event_id":63,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -259200 SECOND), DATE_ADD(@now, INTERVAL -259200 SECOND)),
  (67, 2, 'tracking.added', 'Shipment', 9, '{"event_id":64,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -172800 SECOND)),
  (68, 2, 'tracking.added', 'Shipment', 9, '{"event_id":65,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -86400 SECOND), DATE_ADD(@now, INTERVAL -86400 SECOND)),
  (69, 2, 'tracking.added', 'Shipment', 9, '{"event_id":66,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -84600 SECOND), DATE_ADD(@now, INTERVAL -84600 SECOND)),
  (70, 2, 'exception.opened', 'ShipmentException', 5, '{"shipment_id":9,"type":"MISROUTED"}', NULL, DATE_ADD(@now, INTERVAL -43200 SECOND), DATE_ADD(@now, INTERVAL -43200 SECOND)),
  (71, 2, 'shipment.created', 'Shipment', 10, '{"shipment_number":"CT261109-G2D0R","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -172800 SECOND), DATE_ADD(@now, INTERVAL -172800 SECOND)),
  (72, 2, 'tracking.added', 'Shipment', 10, '{"event_id":69,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -158400 SECOND), DATE_ADD(@now, INTERVAL -158400 SECOND)),
  (73, 2, 'tracking.added', 'Shipment', 10, '{"event_id":70,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -32400 SECOND), DATE_ADD(@now, INTERVAL -32400 SECOND)),
  (74, 2, 'tracking.added', 'Shipment', 10, '{"event_id":71,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -28800 SECOND), DATE_ADD(@now, INTERVAL -28800 SECOND)),
  (75, 2, 'tracking.added', 'Shipment', 10, '{"event_id":72,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -18000 SECOND), DATE_ADD(@now, INTERVAL -18000 SECOND)),
  (76, 2, 'tracking.added', 'Shipment', 10, '{"event_id":73,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -9000 SECOND), DATE_ADD(@now, INTERVAL -9000 SECOND)),
  (77, 2, 'tracking.added', 'Shipment', 10, '{"event_id":74,"event_type":"ETA_UPDATE","status":null}', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (78, 2, 'exception.opened', 'ShipmentException', 6, '{"shipment_id":10,"type":"WEATHER_DELAY"}', NULL, DATE_ADD(@now, INTERVAL -10800 SECOND), DATE_ADD(@now, INTERVAL -10800 SECOND)),
  (79, 2, 'shipment.created', 'Shipment', 11, '{"shipment_number":"CT261109-E8MTV","customer_id":4}', NULL, DATE_ADD(@now, INTERVAL -108000 SECOND), DATE_ADD(@now, INTERVAL -108000 SECOND)),
  (80, 2, 'tracking.added', 'Shipment', 11, '{"event_id":77,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -97200 SECOND), DATE_ADD(@now, INTERVAL -97200 SECOND)),
  (81, 2, 'shipment.created', 'Shipment', 12, '{"shipment_number":"CT261109-P2ZUC","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -50400 SECOND), DATE_ADD(@now, INTERVAL -50400 SECOND)),
  (82, 2, 'tracking.added', 'Shipment', 12, '{"event_id":79,"event_type":"STATUS_UPDATE","status":"BOOKED"}', NULL, DATE_ADD(@now, INTERVAL -46800 SECOND), DATE_ADD(@now, INTERVAL -46800 SECOND)),
  (83, 2, 'tracking.added', 'Shipment', 12, '{"event_id":80,"event_type":"STATUS_UPDATE","status":"PICKED_UP"}', NULL, DATE_ADD(@now, INTERVAL -9000 SECOND), DATE_ADD(@now, INTERVAL -9000 SECOND)),
  (84, 2, 'tracking.added', 'Shipment', 12, '{"event_id":81,"event_type":"STATUS_UPDATE","status":"IN_TRANSIT"}', NULL, DATE_ADD(@now, INTERVAL -7200 SECOND), DATE_ADD(@now, INTERVAL -7200 SECOND)),
  (85, 2, 'tracking.added', 'Shipment', 12, '{"event_id":82,"event_type":"CHECKPOINT","status":null}', NULL, DATE_ADD(@now, INTERVAL -3600 SECOND), DATE_ADD(@now, INTERVAL -3600 SECOND)),
  (86, 2, 'shipment.created', 'Shipment', 13, '{"shipment_number":"CT261109-J3QXB","customer_id":3}', NULL, DATE_ADD(@now, INTERVAL -21600 SECOND), DATE_ADD(@now, INTERVAL -21600 SECOND));

SET FOREIGN_KEY_CHECKS = 1;
