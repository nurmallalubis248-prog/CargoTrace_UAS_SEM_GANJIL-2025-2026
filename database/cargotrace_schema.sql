-- CargoTrace - Safe Database Schema (Tidak menghapus data lama)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `cargotrace` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cargotrace`;

-- ---------------------------------------------------------------- migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- users
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'customer',
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
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
CREATE TABLE IF NOT EXISTS `carriers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `transport_mode` VARCHAR(10) NOT NULL DEFAULT 'SEA',
  `contact_email` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carriers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `country` VARCHAR(2) NOT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'PORT',
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- shipments
CREATE TABLE IF NOT EXISTS `shipments` (
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
  `expected_departure_at` DATETIME NULL,
  `expected_arrival_at` DATETIME NULL,
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

CREATE TABLE IF NOT EXISTS `shipment_items` (
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

CREATE TABLE IF NOT EXISTS `tracking_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(20) NOT NULL,
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

CREATE TABLE IF NOT EXISTS `shipment_exceptions` (
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
CREATE TABLE IF NOT EXISTS `notifications` (
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

CREATE TABLE IF NOT EXISTS `audit_logs` (
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

-- ================================================================ DATA DEFAULT (Aman, tidak menimpa data lama)
SET @now = UTC_TIMESTAMP();

INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_01_01_000001_create_users_table', 1),
  ('2026_01_01_000002_create_personal_access_tokens_table', 1),
  ('2026_01_01_000003_create_carriers_and_locations_tables', 1),
  ('2026_01_01_000004_create_shipments_tables', 1),
  ('2026_01_01_000005_create_notifications_and_audit_logs_tables', 1),
  ('2026_01_01_000006_add_transport_mode_to_carriers', 1),
  ('2026_01_01_000010_add_email_verified_at_to_users_table', 1),
  ('2026_01_01_000011_drop_payments_table', 1),
  ('2026_01_01_000012_drop_warehouses', 1),
  ('2026_01_01_000013_make_shipment_schedule_nullable', 2);

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
  (1, 'Admin CargoTrace', 'admin@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'admin', NULL, @now, @now),
  (2, 'Logistics Operator', 'operator@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'operator', NULL, @now, @now),
  (3, 'PT Nusantara Export', 'customer1@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'customer', NULL, @now, @now),
  (4, 'Samudra Trading Co.', 'customer2@cargotrace.test', @now, '$2y$12$Zx3Qm9vLpT7aKd2NwRs5eulBwwUGpfJpM0X7pabdNAXp/UzmC4kem', 'customer', NULL, @now, @now);

SET FOREIGN_KEY_CHECKS = 1;