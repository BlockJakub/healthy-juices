-- HealthyJuices normalized schema
-- Run these statements on your MySQL/MariaDB server (adjust names and user privileges as needed)

-- 1) Create the database (change name if you prefer)
CREATE DATABASE IF NOT EXISTS `healthy_blog` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `healthy_blog`;

-- 2) Users table (basic auth storage)
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Normalized user entries
-- Keep a JSON `payload` column as a fallback/archive for the original form if you want
CREATE TABLE IF NOT EXISTS `user_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `entry_date` DATE NOT NULL,
  `water` DECIMAL(5,2) DEFAULT NULL,
  `sleep` DECIMAL(4,2) DEFAULT NULL,
  `steps` INT UNSIGNED DEFAULT 0,
  `breakfast` TINYINT(1) NOT NULL DEFAULT 0,
  `lunch` TINYINT(1) NOT NULL DEFAULT 0,
  `dinner` TINYINT(1) NOT NULL DEFAULT 0,
  `snack` TINYINT(1) NOT NULL DEFAULT 0,
  `smoking_pattern` ENUM('never','every_day','when_possible','occasionally','one_per_year') NOT NULL DEFAULT 'never',
  `smoked24` TINYINT(1) NOT NULL DEFAULT 0,
  `cigarettes` SMALLINT UNSIGNED DEFAULT NULL,
  `feel_weak` TINYINT(1) NOT NULL DEFAULT 0,
  `alcohol_units` DECIMAL(6,2) DEFAULT 0,
  `drugs` TINYINT(1) NOT NULL DEFAULT 0,
  `drug_type` VARCHAR(100) DEFAULT NULL,
  `risk_score` DECIMAL(6,2) DEFAULT NULL,
  `health_score` DECIMAL(6,2) DEFAULT NULL,
  `payload` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_user_entry_date` (`user_id`,`entry_date`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_user_entries_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Helpful indexes for queries
CREATE INDEX IF NOT EXISTS `idx_user_date` ON `user_entries` (`user_id`, `entry_date`);
CREATE INDEX IF NOT EXISTS `idx_user_scores` ON `user_entries` (`user_id`, `health_score`);

-- Example: create a DB user and grant privileges (run as a privileged DB user)
-- CREATE USER 'healthy_user'@'localhost' IDENTIFIED BY 'very_strong_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `healthy_blog`.* TO 'healthy_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Migration notes: if you already have a table that stores a JSON payload column named `payload`, you can
-- add normalized columns and populate them from JSON using JSON_EXTRACT/JSON_UNQUOTE. Below are examples.

-- 1) Add the new columns (if they don't already exist):
-- ALTER TABLE user_entries
--   ADD COLUMN water DECIMAL(5,2) DEFAULT NULL,
--   ADD COLUMN sleep DECIMAL(4,2) DEFAULT NULL,
--   ADD COLUMN steps INT UNSIGNED DEFAULT 0,
--   ADD COLUMN breakfast TINYINT(1) DEFAULT 0,
--   ADD COLUMN lunch TINYINT(1) DEFAULT 0,
--   ADD COLUMN dinner TINYINT(1) DEFAULT 0,
--   ADD COLUMN snack TINYINT(1) DEFAULT 0,
--   ADD COLUMN smoking_pattern ENUM('never','every_day','when_possible','occasionally','one_per_year') DEFAULT 'never',
--   ADD COLUMN smoked24 TINYINT(1) DEFAULT 0,
--   ADD COLUMN cigarettes SMALLINT UNSIGNED DEFAULT NULL,
--   ADD COLUMN feel_weak TINYINT(1) DEFAULT 0,
--   ADD COLUMN alcohol_units DECIMAL(6,2) DEFAULT 0,
--   ADD COLUMN drugs TINYINT(1) DEFAULT 0,
--   ADD COLUMN drug_type VARCHAR(100) DEFAULT NULL,
--   ADD COLUMN risk_score DECIMAL(6,2) DEFAULT NULL,
--   ADD COLUMN health_score DECIMAL(6,2) DEFAULT NULL;

-- 2) Populate new columns from existing JSON payload (example for a few fields)
-- UPDATE user_entries
-- SET
--   water = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.water')), '') + 0,
--   sleep = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sleep')), '') + 0,
--   steps = COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.steps')), ''), 0) + 0,
--   breakfast = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.meals.breakfast')) = 'yes'),
--   lunch = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.meals.lunch')) = 'yes'),
--   dinner = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.meals.dinner')) = 'yes'),
--   snack = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.meals.snack')) = 'yes'),
--   smoking_pattern = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.smokingPattern')), 'never'),
--   smoked24 = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.smoked24')) = 'yes'),
--   cigarettes = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.cigarettes')), '') + 0,
--   feel_weak = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.feelWeak')) = 'yes'),
--   alcohol_units = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.alcoholUnits')), '') + 0,
--   drugs = (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.drugs')) = 'yes'),
--   drug_type = JSON_UNQUOTE(JSON_EXTRACT(payload, '$.drugType'));

-- Notes on migration: run updates in small batches (e.g. add WHERE id BETWEEN x AND y) on large tables to avoid long locks.
-- Verify each mapping carefully before dropping the JSON `payload` column. Keep backups.

-- Example query: fetch last 30 entries for a user
-- SELECT entry_date, water, sleep, steps, health_score, risk_score
-- FROM user_entries
-- WHERE user_id = ?
-- ORDER BY entry_date DESC
-- LIMIT 30;

-- End of schema
