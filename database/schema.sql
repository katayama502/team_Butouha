CREATE DATABASE IF NOT EXISTS `butouha_app`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `butouha_app`;

CREATE TABLE IF NOT EXISTS `important_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `audio_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contribution_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `audio_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `other_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `audio_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_users` (`username`, `password_hash`, `role`, `display_name`) VALUES
  ('admin', '$2y$12$BmumvoQ0gn6T1vmtgSv33.CHakBDtfplvnEHqCRDSEmNjgQse34Dq', 'admin', '管理者'),
  ('user', '$2y$12$v3A/MfuRBFSjB49rR3zgVOp.aamf3V.Zj9oBcISQtKbS.4RGayAa2', 'user', '一般ユーザー')
ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`),
  `role` = VALUES(`role`),
  `display_name` = VALUES(`display_name`);

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room` ENUM('large','small') NOT NULL,
  `reserved_at` DATETIME NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `reserved_for` VARCHAR(100) NOT NULL,
  `note` TEXT DEFAULT NULL,
  `document_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_datetime_unique` (`room`, `reserved_at`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `reservations_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `app_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
