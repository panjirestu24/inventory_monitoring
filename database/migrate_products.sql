-- ============================================================
-- MIGRATION: Tambah tabel products (Daftar Produk/Jasa Percetakan)
-- Jalankan di phpMyAdmin → db_inventory → tab SQL
-- ============================================================

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `unit_id` INT(11) UNSIGNED DEFAULT NULL,
  `default_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_unit` (`unit_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data contoh produk percetakan
INSERT INTO `products` (`code`, `name`, `category_id`, `unit_id`, `default_price`, `description`) VALUES
('PRD001', 'Cetak Spanduk',       1, 9, 35000,  'Cetak spanduk full color, bahan flexi china'),
('PRD002', 'Cetak Banner Roll Up', 1, 8, 250000, 'Banner roll up standar 60x160cm'),
('PRD003', 'Kartu Nama',          1, 8, 55000,  'Kartu nama 2 sisi, art carton 260gr, per 100 lembar'),
('PRD004', 'Brosur A5',           1, 8, 85000,  'Brosur A5 2 sisi full color, per 100 lembar'),
('PRD005', 'Brosur A4',           1, 8, 150000, 'Brosur A4 2 sisi full color, per 100 lembar'),
('PRD006', 'Undangan',            1, 8, 175000, 'Undangan custom, per 100 lembar'),
('PRD007', 'Kalender Meja',       1, 8, 45000,  'Kalender meja 13 lembar, per pcs'),
('PRD008', 'Kalender Dinding',    1, 8, 35000,  'Kalender dinding 13 lembar, per pcs'),
('PRD009', 'Stiker Vinyl',        1, 9, 45000,  'Stiker vinyl cutting, per meter persegi'),
('PRD010', 'Nota/Kwitansi',       1, 8, 65000,  'Nota 2 ply NCR, per 100 set'),
('PRD011', 'Cetak Foto',          1, 8, 3500,   'Cetak foto glossy, per lembar A4'),
('PRD012', 'Laminating',          6, 9, 15000,  'Laminating glossy/doff, per meter persegi');
