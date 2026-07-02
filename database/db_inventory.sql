-- ============================================================
-- DATABASE: db_inventory
-- Ranum Indocraft — Sistem Inventory & Monitoring Percetakan
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
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `product_materials`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `deliveries`;
DROP TABLE IF EXISTS `stock_transactions`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `units`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- STRUKTUR TABEL (12 tabel)
-- ============================================================

-- Tabel: users (Data Pengguna / Akun Login)
CREATE TABLE `users` (
  `id_users`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `name`       VARCHAR(100) NOT NULL                   COMMENT 'Nama lengkap pengguna',
  `email`      VARCHAR(100) NOT NULL UNIQUE             COMMENT 'Email untuk login',
  `password`   VARCHAR(255) NOT NULL                   COMMENT 'Password ter-hash (bcrypt)',
  `role`       ENUM('admin','operator') NOT NULL DEFAULT 'operator' COMMENT 'Hak akses: admin / operator',
  `avatar`     VARCHAR(255) DEFAULT NULL               COMMENT 'Path foto profil',
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1           COMMENT '1=aktif, 0=nonaktif',
  `last_login` DATETIME DEFAULT NULL                   COMMENT 'Waktu login terakhir',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data akun pengguna sistem';

-- Tabel: categories (Kategori Bahan Baku)
CREATE TABLE `categories` (
  `id_categories` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `name`          VARCHAR(100) NOT NULL                   COMMENT 'Nama kategori',
  `description`   TEXT DEFAULT NULL                       COMMENT 'Keterangan kategori',
  `color`         VARCHAR(7) DEFAULT '#6366f1'            COMMENT 'Warna hex untuk tampilan',
  `icon`          VARCHAR(50) DEFAULT 'box'               COMMENT 'Nama ikon Feather',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categories`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kategori bahan baku (kertas, tinta, dll)';

-- Tabel: units (Satuan Bahan Baku)
CREATE TABLE `units` (
  `id_units`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `name`       VARCHAR(50) NOT NULL                    COMMENT 'Nama satuan (Kilogram, Rim, dll)',
  `symbol`     VARCHAR(10) NOT NULL                    COMMENT 'Simbol satuan (Kg, Rim, dll)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_units`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Satuan ukuran bahan baku';

-- Tabel: customers (Data Pelanggan)
CREATE TABLE `customers` (
  `id_customers`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `code`           VARCHAR(20) NOT NULL UNIQUE             COMMENT 'Kode unik pelanggan (CUS001)',
  `name`           VARCHAR(150) NOT NULL                   COMMENT 'Nama pelanggan / perusahaan',
  `contact_person` VARCHAR(100) DEFAULT NULL               COMMENT 'Nama kontak PIC',
  `phone`          VARCHAR(20) DEFAULT NULL                COMMENT 'Nomor telepon / WhatsApp',
  `email`          VARCHAR(100) DEFAULT NULL               COMMENT 'Alamat email pelanggan',
  `address`        TEXT DEFAULT NULL                       COMMENT 'Alamat lengkap',
  `city`           VARCHAR(100) DEFAULT NULL               COMMENT 'Kota',
  `notes`          TEXT DEFAULT NULL                       COMMENT 'Catatan tambahan',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1           COMMENT '1=aktif, 0=nonaktif',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_customers`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data pelanggan percetakan';

-- Tabel: items (Bahan Baku / Inventory)
CREATE TABLE `items` (
  `id_items`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `code`           VARCHAR(30) NOT NULL UNIQUE             COMMENT 'Kode unik bahan (ITM001)',
  `name`           VARCHAR(150) NOT NULL                   COMMENT 'Nama bahan baku',
  `category_id`    INT(11) UNSIGNED NOT NULL               COMMENT 'FK → categories.id_categories',
  `unit_id`        INT(11) UNSIGNED NOT NULL               COMMENT 'FK → units.id_units',
  `description`    TEXT DEFAULT NULL                       COMMENT 'Keterangan bahan',
  `image`          VARCHAR(255) DEFAULT NULL               COMMENT 'Path foto bahan',
  `stock`          DECIMAL(12,2) NOT NULL DEFAULT 0        COMMENT 'Stok saat ini',
  `min_stock`      DECIMAL(12,2) NOT NULL DEFAULT 0        COMMENT 'Batas minimum stok (peringatan)',
  `max_stock`      DECIMAL(12,2) NOT NULL DEFAULT 0        COMMENT 'Batas maksimum stok',
  `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Harga beli per satuan (Rp)',
  `selling_price`  DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Harga jual per satuan (Rp)',
  `location`       VARCHAR(100) DEFAULT NULL               COMMENT 'Lokasi penyimpanan di gudang',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1           COMMENT '1=aktif, 0=nonaktif',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_items`),
  KEY `fk_items_category` (`category_id`),
  KEY `fk_items_unit`     (`unit_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id_categories`) ON DELETE RESTRICT,
  CONSTRAINT `fk_items_unit`     FOREIGN KEY (`unit_id`)     REFERENCES `units`      (`id_units`)      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data bahan baku dan stok inventory';

-- Tabel: products (Produk / Jasa Percetakan)
CREATE TABLE `products` (
  `id_products`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `code`          VARCHAR(30) NOT NULL UNIQUE             COMMENT 'Kode unik produk (PRD001)',
  `name`          VARCHAR(150) NOT NULL                   COMMENT 'Nama produk / jasa',
  `category_id`   INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → categories.id_categories',
  `unit_id`       INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → units.id_units',
  `default_price` DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Harga jual default (Rp)',
  `description`   TEXT DEFAULT NULL                       COMMENT 'Keterangan produk',
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1           COMMENT '1=aktif, 0=nonaktif',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_products`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_unit`     (`unit_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id_categories`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_unit`     FOREIGN KEY (`unit_id`)     REFERENCES `units`      (`id_units`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daftar produk dan jasa percetakan';

-- Tabel: product_materials (BOM — Bill of Materials)
CREATE TABLE `product_materials` (
  `id_product_materials` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `product_id`           INT(11) UNSIGNED NOT NULL               COMMENT 'FK → products.id_products',
  `item_id`              INT(11) UNSIGNED NOT NULL               COMMENT 'FK → items.id_items',
  `qty_per_unit`         DECIMAL(12,4) NOT NULL DEFAULT 1        COMMENT 'Jumlah bahan per 1 pcs produk',
  `notes`                VARCHAR(200) DEFAULT NULL               COMMENT 'Keterangan tambahan',
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_product_materials`),
  UNIQUE KEY `uk_product_item` (`product_id`, `item_id`),
  KEY `fk_pm_product` (`product_id`),
  KEY `fk_pm_item`    (`item_id`),
  CONSTRAINT `fk_pm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id_products`) ON DELETE CASCADE,
  CONSTRAINT `fk_pm_item`    FOREIGN KEY (`item_id`)    REFERENCES `items`    (`id_items`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bill of Materials — kebutuhan bahan per produk';

-- Tabel: orders (Data Order Cetak)
CREATE TABLE `orders` (
  `id_orders`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `order_number`   VARCHAR(30) NOT NULL UNIQUE             COMMENT 'Nomor order unik (ORD-2507-0001)',
  `customer_id`    INT(11) UNSIGNED NOT NULL               COMMENT 'FK → customers.id_customers',
  `operator_id`    INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → users.id_users (operator)',
  `title`          VARCHAR(200) NOT NULL                   COMMENT 'Judul / nama pesanan',
  `description`    TEXT DEFAULT NULL                       COMMENT 'Keterangan detail pesanan',
  `status`         ENUM('pending','confirmed','in_progress','quality_check','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Status produksi order',
  `priority`       ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal' COMMENT 'Tingkat prioritas pengerjaan',
  `quantity`       INT(11) NOT NULL DEFAULT 1              COMMENT 'Total jumlah pesanan',
  `unit_price`     DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Harga satuan (Rp)',
  `total_price`    DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Total sebelum diskon & pajak (Rp)',
  `discount`       DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Potongan harga (Rp)',
  `tax`            DECIMAL(5,2) NOT NULL DEFAULT 0         COMMENT 'Persentase PPN (%)',
  `grand_total`    DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Total akhir setelah diskon & pajak (Rp)',
  `start_date`     DATE DEFAULT NULL                       COMMENT 'Tanggal mulai produksi',
  `due_date`       DATE DEFAULT NULL                       COMMENT 'Tanggal jatuh tempo selesai',
  `completed_date` DATETIME DEFAULT NULL                   COMMENT 'Waktu order benar-benar selesai',
  `notes`          TEXT DEFAULT NULL                       COMMENT 'Catatan / instruksi khusus',
  `created_by`     INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → users.id_users (pembuat order)',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_orders`),
  KEY `fk_orders_customer`  (`customer_id`),
  KEY `fk_orders_operator`  (`operator_id`),
  CONSTRAINT `fk_orders_customer`  FOREIGN KEY (`customer_id`)  REFERENCES `customers` (`id_customers`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_operator`  FOREIGN KEY (`operator_id`)  REFERENCES `users`     (`id_users`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data order cetak dari pelanggan';

-- Tabel: order_items (Detail Item dalam Order)
CREATE TABLE `order_items` (
  `id_order_items` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `order_id`       INT(11) UNSIGNED NOT NULL               COMMENT 'FK → orders.id_orders',
  `item_id`        INT(11) UNSIGNED NOT NULL               COMMENT 'FK → items.id_items (bahan terpakai)',
  `quantity`       DECIMAL(12,2) NOT NULL                  COMMENT 'Jumlah bahan yang dipakai',
  `unit_price`     DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Harga bahan saat order dibuat (Rp)',
  `notes`          TEXT DEFAULT NULL                       COMMENT 'Keterangan tambahan',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_order_items`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_item`  (`item_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id_orders`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_item`  FOREIGN KEY (`item_id`)  REFERENCES `items`  (`id_items`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detail bahan baku yang dipakai per order';

-- Tabel: deliveries (Data Pengiriman)
CREATE TABLE `deliveries` (
  `id_deliveries`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `order_id`            INT(11) UNSIGNED NOT NULL               COMMENT 'FK → orders.id_orders',
  `status`              ENUM('prepared','shipping','arrived','received') NOT NULL DEFAULT 'prepared' COMMENT 'Status pengiriman',
  `destination_address` TEXT DEFAULT NULL                       COMMENT 'Alamat tujuan pengiriman',
  `destination_city`    VARCHAR(100) DEFAULT NULL               COMMENT 'Kota tujuan',
  `recipient_name`      VARCHAR(150) DEFAULT NULL               COMMENT 'Nama penerima',
  `recipient_phone`     VARCHAR(20) DEFAULT NULL                COMMENT 'Nomor HP penerima',
  `estimated_arrival`   DATE DEFAULT NULL                       COMMENT 'Estimasi tanggal tiba',
  `actual_arrival`      DATETIME DEFAULT NULL                   COMMENT 'Waktu tiba sebenarnya',
  `proof_image`         VARCHAR(255) DEFAULT NULL               COMMENT 'Path foto bukti pengiriman diterima',
  `notes`               TEXT DEFAULT NULL                       COMMENT 'Catatan pengiriman',
  `created_by`          INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → users.id_users',
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_deliveries`),
  UNIQUE KEY `uk_delivery_order` (`order_id`),
  KEY `fk_delivery_order`   (`order_id`),
  KEY `fk_delivery_creator` (`created_by`),
  CONSTRAINT `fk_delivery_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id_orders`) ON DELETE CASCADE,
  CONSTRAINT `fk_delivery_creator` FOREIGN KEY (`created_by`) REFERENCES `users`  (`id_users`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data pengiriman order ke pelanggan';

-- Tabel: stock_transactions (Riwayat Mutasi Stok)
CREATE TABLE `stock_transactions` (
  `id_stock_transactions` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `item_id`               INT(11) UNSIGNED NOT NULL               COMMENT 'FK → items.id_items',
  `type`                  ENUM('in','out','adjustment','return') NOT NULL COMMENT 'Jenis mutasi: masuk/keluar/penyesuaian/retur',
  `reference_type`        ENUM('purchase','order','adjustment','return','initial') NOT NULL DEFAULT 'adjustment' COMMENT 'Asal transaksi',
  `reference_id`          INT(11) UNSIGNED DEFAULT NULL           COMMENT 'ID referensi (misal id_orders)',
  `quantity`              DECIMAL(12,2) NOT NULL                  COMMENT 'Jumlah yang berubah',
  `stock_before`          DECIMAL(12,2) NOT NULL                  COMMENT 'Stok sebelum transaksi',
  `stock_after`           DECIMAL(12,2) NOT NULL                  COMMENT 'Stok sesudah transaksi',
  `unit_price`            DECIMAL(15,2) NOT NULL DEFAULT 0        COMMENT 'Harga satuan saat transaksi (Rp)',
  `notes`                 TEXT DEFAULT NULL                       COMMENT 'Keterangan transaksi',
  `created_by`            INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → users.id_users',
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_stock_transactions`),
  KEY `fk_stocktx_item` (`item_id`),
  KEY `fk_stocktx_user` (`created_by`),
  CONSTRAINT `fk_stocktx_item` FOREIGN KEY (`item_id`)    REFERENCES `items` (`id_items`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stocktx_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_users`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Riwayat semua mutasi stok bahan baku (FIFO log)';

-- Tabel: notifications (Notifikasi Sistem)
CREATE TABLE `notifications` (
  `id_notifications` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `type`             ENUM('low_stock','order_status','system','maintenance') NOT NULL COMMENT 'Jenis notifikasi',
  `title`            VARCHAR(200) NOT NULL                   COMMENT 'Judul notifikasi',
  `message`          TEXT NOT NULL                           COMMENT 'Isi pesan notifikasi',
  `reference_type`   VARCHAR(50) DEFAULT NULL                COMMENT 'Tipe referensi (orders, items, dll)',
  `reference_id`     INT(11) UNSIGNED DEFAULT NULL           COMMENT 'ID data yang dirujuk',
  `is_read`          TINYINT(1) NOT NULL DEFAULT 0           COMMENT '0=belum dibaca, 1=sudah dibaca',
  `user_id`          INT(11) UNSIGNED DEFAULT NULL           COMMENT 'FK → users.id_users (penerima)',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notifications`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_users`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notifikasi sistem (stok kritis, status order, dll)';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATA AWAL (SEED DATA)
-- ============================================================

-- Users (password semua = "password")
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@ranumindocraft.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Operator 1',    'operator1@ranumindocraft.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator');

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
-- SELESAI — 12 tabel aktif:
--   users, categories, units, customers,
--   items, products, product_materials, orders,
--   order_items, deliveries, stock_transactions,
--   notifications
-- ============================================================
-- AKUN LOGIN (password = "password"):
--   admin@ranumindocraft.com     → Admin (akses penuh)
--   operator1@ranumindocraft.com → Operator
-- ============================================================
