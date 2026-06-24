-- ============================================================
-- MIGRATION: Tambah tabel deliveries
-- Jalankan script ini jika database sudah ada sebelumnya
-- ============================================================

CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('prepared','shipping','arrived','received') NOT NULL DEFAULT 'prepared',
  `destination_address` TEXT DEFAULT NULL,
  `destination_city` VARCHAR(100) DEFAULT NULL,
  `recipient_name` VARCHAR(150) DEFAULT NULL,
  `recipient_phone` VARCHAR(20) DEFAULT NULL,
  `estimated_arrival` DATE DEFAULT NULL,
  `actual_arrival` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_delivery_order` (`order_id`),
  KEY `fk_delivery_order` (`order_id`),
  KEY `fk_delivery_creator` (`created_by`),
  CONSTRAINT `fk_delivery_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_delivery_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
