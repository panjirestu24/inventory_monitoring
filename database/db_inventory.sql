-- ============================================================
-- DATABASE: db_inventory
-- Sistem Inventory & Monitoring Percetakan
-- ============================================================
-- CARA IMPORT:
--   1. Buka phpMyAdmin
--   2. Buat database baru: db_inventory
--   3. Klik database tersebut → tab Import
--   4. Pilih file ini → klik Go
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- Drop semua tabel (urutan terbalik karena foreign key)
DROP TABLE IF EXISTS `machine_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `product_materials`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `deliveries`;
DROP TABLE IF EXISTS `stock_transactions`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `machines`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `units`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- STRUKTUR TABEL (14 tabel)
-- ============================================================

CREATE TABLE `users` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','operator') NOT NULL DEFAULT 'operator',
  `avatar`     VARCHAR(255) DEFAULT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `color`       VARCHAR(7) DEFAULT '#6366f1',
  `icon`        VARCHAR(50) DEFAULT 'box',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `units` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(50) NOT NULL,
  `symbol`     VARCHAR(10) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(20) NOT NULL UNIQUE,
  `name`           VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone`          VARCHAR(20) DEFAULT NULL,
  `email`          VARCHAR(100) DEFAULT NULL,
  `address`        TEXT DEFAULT NULL,
  `city`           VARCHAR(100) DEFAULT NULL,
  `notes`          TEXT DEFAULT NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `machines` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`             VARCHAR(20) NOT NULL UNIQUE,
  `name`             VARCHAR(100) NOT NULL,
  `type`             VARCHAR(100) DEFAULT NULL,
  `brand`            VARCHAR(100) DEFAULT NULL,
  `serial_number`    VARCHAR(100) DEFAULT NULL,
  `status`           ENUM('active','idle','maintenance','offline') NOT NULL DEFAULT 'idle',
  `purchase_date`    DATE DEFAULT NULL,
  `last_maintenance` DATE DEFAULT NULL,
  `next_maintenance` DATE DEFAULT NULL,
  `notes`            TEXT DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `items` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(30) NOT NULL UNIQUE,
  `name`           VARCHAR(150) NOT NULL,
  `category_id`    INT(11) UNSIGNED NOT NULL,
  `unit_id`        INT(11) UNSIGNED NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `image`          VARCHAR(255) DEFAULT NULL,
  `stock`          DECIMAL(12,2) NOT NULL DEFAULT 0,
  `min_stock`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `max_stock`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `selling_price`  DECIMAL(15,2) NOT NULL DEFAULT 0,
  `location`       VARCHAR(100) DEFAULT NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_items_category` (`category_id`),
  KEY `fk_items_unit` (`unit_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_items_unit`     FOREIGN KEY (`unit_id`)     REFERENCES `units`      (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(30) NOT NULL UNIQUE,
  `name`          VARCHAR(150) NOT NULL,
  `category_id`   INT(11) UNSIGNED DEFAULT NULL,
  `unit_id`       INT(11) UNSIGNED DEFAULT NULL,
  `default_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `description`   TEXT DEFAULT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_unit`     (`unit_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_unit`     FOREIGN KEY (`unit_id`)     REFERENCES `units`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_materials` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`   INT(11) UNSIGNED NOT NULL COMMENT 'Produk jasa',
  `item_id`      INT(11) UNSIGNED NOT NULL COMMENT 'Bahan baku yang dipakai',
  `qty_per_unit` DECIMAL(12,4) NOT NULL DEFAULT 1 COMMENT 'Qty bahan per 1 pcs produk',
  `notes`        VARCHAR(200) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product_item` (`product_id`, `item_id`),
  KEY `fk_pm_product` (`product_id`),
  KEY `fk_pm_item`    (`item_id`),
  CONSTRAINT `fk_pm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pm_item`    FOREIGN KEY (`item_id`)    REFERENCES `items`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number`   VARCHAR(30) NOT NULL UNIQUE,
  `customer_id`    INT(11) UNSIGNED NOT NULL,
  `machine_id`     INT(11) UNSIGNED DEFAULT NULL,
  `operator_id`    INT(11) UNSIGNED DEFAULT NULL,
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `status`         ENUM('pending','confirmed','in_progress','quality_check','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority`       ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `quantity`       INT(11) NOT NULL DEFAULT 1,
  `unit_price`     DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total_price`    DECIMAL(15,2) NOT NULL DEFAULT 0,
  `discount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tax`            DECIMAL(5,2) NOT NULL DEFAULT 0,
  `grand_total`    DECIMAL(15,2) NOT NULL DEFAULT 0,
  `start_date`     DATE DEFAULT NULL,
  `due_date`       DATE DEFAULT NULL,
  `completed_date` DATETIME DEFAULT NULL,
  `notes`          TEXT DEFAULT NULL,
  `created_by`     INT(11) UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_orders_customer` (`customer_id`),
  KEY `fk_orders_machine`  (`machine_id`),
  KEY `fk_orders_operator` (`operator_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_machine`  FOREIGN KEY (`machine_id`)  REFERENCES `machines`  (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_operator` FOREIGN KEY (`operator_id`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT(11) UNSIGNED NOT NULL,
  `item_id`    INT(11) UNSIGNED NOT NULL,
  `quantity`   DECIMAL(12,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes`      TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_item`  (`item_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_item`  FOREIGN KEY (`item_id`)  REFERENCES `items`  (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `deliveries` (
  `id`                  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`            INT(11) UNSIGNED NOT NULL,
  `status`              ENUM('prepared','shipping','arrived','received') NOT NULL DEFAULT 'prepared',
  `destination_address` TEXT DEFAULT NULL,
  `destination_city`    VARCHAR(100) DEFAULT NULL,
  `recipient_name`      VARCHAR(150) DEFAULT NULL,
  `recipient_phone`     VARCHAR(20) DEFAULT NULL,
  `estimated_arrival`   DATE DEFAULT NULL,
  `actual_arrival`      DATETIME DEFAULT NULL,
  `proof_image`         VARCHAR(255) DEFAULT NULL COMMENT 'Foto bukti pengiriman diterima',
  `notes`               TEXT DEFAULT NULL,
  `created_by`          INT(11) UNSIGNED DEFAULT NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_delivery_order` (`order_id`),
  KEY `fk_delivery_order`   (`order_id`),
  KEY `fk_delivery_creator` (`created_by`),
  CONSTRAINT `fk_delivery_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_delivery_creator` FOREIGN KEY (`created_by`) REFERENCES `users`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_transactions` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`        INT(11) UNSIGNED NOT NULL,
  `type`           ENUM('in','out','adjustment','return') NOT NULL,
  `reference_type` ENUM('purchase','order','adjustment','return','initial') NOT NULL DEFAULT 'adjustment',
  `reference_id`   INT(11) UNSIGNED DEFAULT NULL,
  `quantity`       DECIMAL(12,2) NOT NULL,
  `stock_before`   DECIMAL(12,2) NOT NULL,
  `stock_after`    DECIMAL(12,2) NOT NULL,
  `unit_price`     DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes`          TEXT DEFAULT NULL,
  `created_by`     INT(11) UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_stocktx_item` (`item_id`),
  KEY `fk_stocktx_user` (`created_by`),
  CONSTRAINT `fk_stocktx_item` FOREIGN KEY (`item_id`)    REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stocktx_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `machine_logs` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `machine_id`       INT(11) UNSIGNED NOT NULL,
  `order_id`         INT(11) UNSIGNED DEFAULT NULL,
  `event`            ENUM('start','pause','resume','stop','error','maintenance_start','maintenance_end') NOT NULL,
  `description`      TEXT DEFAULT NULL,
  `operator_id`      INT(11) UNSIGNED DEFAULT NULL,
  `duration_minutes` INT(11) DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_machinelogs_machine` (`machine_id`),
  KEY `fk_machinelogs_order`   (`order_id`),
  CONSTRAINT `fk_machinelogs_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_machinelogs_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`           ENUM('low_stock','order_status','machine_alert','system','maintenance') NOT NULL,
  `title`          VARCHAR(200) NOT NULL,
  `message`        TEXT NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id`   INT(11) UNSIGNED DEFAULT NULL,
  `is_read`        TINYINT(1) NOT NULL DEFAULT 0,
  `user_id`        INT(11) UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATA AWAL (SEED DATA)
-- ============================================================

-- Users (password semua = "password")
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@percetakan.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Operator 1',    'operator1@percetakan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator');

-- Units
INSERT INTO `units` (`name`, `symbol`) VALUES
('Rim',      'Rim'),
('Lembar',   'Lbr'),
('Kilogram', 'Kg'),
('Liter',    'L'),
('Roll',     'Roll'),
('Botol',    'Btl'),
('Kaleng',   'Klg'),
('Pcs',      'Pcs'),
('Meter',    'M'),
('Box',      'Box');

-- Categories
INSERT INTO `categories` (`name`, `description`, `color`, `icon`) VALUES
('Kertas',     'Bahan baku kertas berbagai jenis',    '#6366f1', 'file'),
('Tinta',      'Tinta cetak dan toner',               '#8b5cf6', 'droplet'),
('Plate',      'Plate cetak offset dan digital',      '#06b6d4', 'layers'),
('Chemical',   'Bahan kimia proses cetak',            '#f59e0b', 'flask'),
('Finishing',  'Bahan finishing dan binding',         '#10b981', 'package'),
('Spare Part', 'Suku cadang mesin',                   '#ef4444', 'tool');

-- Customers
INSERT INTO `customers` (`code`, `name`, `contact_person`, `phone`, `city`) VALUES
('CUS001', 'PT. Maju Bersama',   'Rina Wijaya',    '0812-3456789', 'Jakarta'),
('CUS002', 'CV. Kreasi Digital', 'Doni Prasetyo',  '0813-9876543', 'Bandung'),
('CUS003', 'Toko Buku Cerdas',   'Maya Sari',      '0856-1234567', 'Surabaya'),
('CUS004', 'PT. Advertise Pro',  'Hendra Gunawan', '0811-5432198', 'Jakarta'),
('CUS005', 'UD. Paket Hemat',    'Siti Aminah',    '0821-7654321', 'Yogyakarta');

-- Machines
INSERT INTO `machines` (`code`, `name`, `type`, `brand`, `status`) VALUES
('MCH001', 'Mesin Offset 1',   'Offset Printing',     'Heidelberg', 'active'),
('MCH002', 'Mesin Offset 2',   'Offset Printing',     'Komori',     'idle'),
('MCH003', 'Mesin Digital 1',  'Digital Printing',    'HP Indigo',  'active'),
('MCH004', 'Mesin Cutting',    'Cutting & Finishing', 'Polar',      'idle'),
('MCH005', 'Mesin Laminating', 'Finishing',            'GMP',        'maintenance');

-- Items (Bahan Baku)
INSERT INTO `items` (`code`, `name`, `category_id`, `unit_id`, `stock`, `min_stock`, `max_stock`, `purchase_price`, `location`) VALUES
('ITM001', 'Kertas HVS A4 70gr',     1, 1, 50,  10, 200, 45000,  'Gudang A-1'),
('ITM002', 'Kertas HVS A3 80gr',     1, 1, 30,  8,  150, 85000,  'Gudang A-1'),
('ITM003', 'Kertas Art Paper 150gr', 1, 1, 20,  5,  100, 125000, 'Gudang A-2'),
('ITM004', 'Kertas Duplex 230gr',    1, 1, 15,  5,  80,  95000,  'Gudang A-2'),
('ITM005', 'Tinta Cyan Offset',      2, 3, 25,  5,  50,  320000, 'Gudang B-1'),
('ITM006', 'Tinta Magenta Offset',   2, 3, 22,  5,  50,  320000, 'Gudang B-1'),
('ITM007', 'Tinta Yellow Offset',    2, 3, 20,  5,  50,  300000, 'Gudang B-1'),
('ITM008', 'Tinta Black Offset',     2, 3, 8,   5,  50,  280000, 'Gudang B-1'),
('ITM009', 'Plate CTCP A1',          3, 8, 100, 20, 500, 35000,  'Gudang C-1'),
('ITM010', 'Cairan Developer Plate', 4, 4, 3,   2,  20,  180000, 'Gudang D-1'),
('ITM011', 'Fountain Solution',      4, 4, 10,  3,  30,  95000,  'Gudang D-1'),
('ITM012', 'Blanket Offset B2',      6, 8, 4,   2,  10,  750000, 'Gudang C-2');

-- Products (Jasa/Produk Percetakan)
INSERT INTO `products` (`code`, `name`, `category_id`, `unit_id`, `default_price`, `description`) VALUES
('PRD001', 'Cetak Spanduk',        1, 9, 35000,  'Cetak spanduk full color, bahan flexi china'),
('PRD002', 'Cetak Banner Roll Up', 1, 8, 250000, 'Banner roll up standar 60x160cm'),
('PRD003', 'Kartu Nama',           1, 8, 55000,  'Kartu nama 2 sisi, art carton 260gr, per 100 lembar'),
('PRD004', 'Brosur A5',            1, 8, 85000,  'Brosur A5 2 sisi full color, per 100 lembar'),
('PRD005', 'Brosur A4',            1, 8, 150000, 'Brosur A4 2 sisi full color, per 100 lembar'),
('PRD006', 'Undangan',             1, 8, 175000, 'Undangan custom, per 100 lembar'),
('PRD007', 'Kalender Meja',        1, 8, 45000,  'Kalender meja 13 lembar, per pcs'),
('PRD008', 'Kalender Dinding',     1, 8, 35000,  'Kalender dinding 13 lembar, per pcs'),
('PRD009', 'Stiker Vinyl',         1, 9, 45000,  'Stiker vinyl cutting, per meter persegi'),
('PRD010', 'Nota/Kwitansi',        1, 8, 65000,  'Nota 2 ply NCR, per 100 set'),
('PRD011', 'Cetak Foto',           1, 8, 3500,   'Cetak foto glossy, per lembar A4'),
('PRD012', 'Laminating',           6, 9, 15000,  'Laminating glossy/doff, per meter persegi');

-- ============================================================
-- SELESAI — 14 tabel aktif:
--   users, categories, units, customers, machines,
--   items, products, product_materials, orders,
--   order_items, deliveries, stock_transactions,
--   machine_logs, notifications
-- ============================================================
-- AKUN LOGIN (password = "password"):
--   admin@percetakan.com     → Admin (akses penuh)
--   operator1@percetakan.com → Operator
-- ============================================================
