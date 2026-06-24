-- ============================================================
-- DATABASE: db_inventory
-- Sistem Inventory & Monitoring Percetakan
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ============================================================
-- TABLE: users (Manajemen Pengguna)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','operator','viewer') NOT NULL DEFAULT 'operator',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categories (Kategori Bahan Baku)
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT '#6366f1',
  `icon` VARCHAR(50) DEFAULT 'box',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: suppliers (Data Supplier)
-- ============================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: units (Satuan Barang)
-- ============================================================
CREATE TABLE IF NOT EXISTS `units` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: items (Bahan Baku / Barang)
-- ============================================================
CREATE TABLE IF NOT EXISTS `items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `unit_id` INT(11) UNSIGNED NOT NULL,
  `supplier_id` INT(11) UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `stock` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `min_stock` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `max_stock` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `location` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_items_category` (`category_id`),
  KEY `fk_items_unit` (`unit_id`),
  KEY `fk_items_supplier` (`supplier_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_items_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_items_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: customers (Data Customer)
-- ============================================================
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: machines (Mesin Percetakan)
-- ============================================================
CREATE TABLE IF NOT EXISTS `machines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(100) DEFAULT NULL,
  `brand` VARCHAR(100) DEFAULT NULL,
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active','idle','maintenance','offline') NOT NULL DEFAULT 'idle',
  `purchase_date` DATE DEFAULT NULL,
  `last_maintenance` DATE DEFAULT NULL,
  `next_maintenance` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: orders (Order / Pesanan Cetak)
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT(11) UNSIGNED NOT NULL,
  `machine_id` INT(11) UNSIGNED DEFAULT NULL,
  `operator_id` INT(11) UNSIGNED DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending','confirmed','in_progress','quality_check','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `discount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tax` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `start_date` DATE DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `completed_date` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_orders_customer` (`customer_id`),
  KEY `fk_orders_machine` (`machine_id`),
  KEY `fk_orders_operator` (`operator_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_operator` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: order_items (Detail Bahan yang Digunakan per Order)
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `item_id` INT(11) UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_item` (`item_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: stock_transactions (Mutasi Stok)
-- ============================================================
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) UNSIGNED NOT NULL,
  `type` ENUM('in','out','adjustment','return') NOT NULL,
  `reference_type` ENUM('purchase','order','adjustment','return','initial') NOT NULL DEFAULT 'adjustment',
  `reference_id` INT(11) UNSIGNED DEFAULT NULL,
  `quantity` DECIMAL(12,2) NOT NULL,
  `stock_before` DECIMAL(12,2) NOT NULL,
  `stock_after` DECIMAL(12,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_stocktx_item` (`item_id`),
  KEY `fk_stocktx_user` (`created_by`),
  CONSTRAINT `fk_stocktx_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stocktx_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: purchases (Pembelian Bahan Baku)
-- ============================================================
CREATE TABLE IF NOT EXISTS `purchases` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_number` VARCHAR(30) NOT NULL UNIQUE,
  `supplier_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('draft','ordered','partial','received','cancelled') NOT NULL DEFAULT 'draft',
  `purchase_date` DATE NOT NULL,
  `expected_date` DATE DEFAULT NULL,
  `received_date` DATE DEFAULT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_purchases_supplier` (`supplier_id`),
  CONSTRAINT `fk_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: purchase_items (Detail Item Pembelian)
-- ============================================================
CREATE TABLE IF NOT EXISTS `purchase_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` INT(11) UNSIGNED NOT NULL,
  `item_id` INT(11) UNSIGNED NOT NULL,
  `quantity_ordered` DECIMAL(12,2) NOT NULL,
  `quantity_received` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_purchaseitems_purchase` (`purchase_id`),
  KEY `fk_purchaseitems_item` (`item_id`),
  CONSTRAINT `fk_purchaseitems_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchaseitems_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: machine_logs (Log Aktivitas Mesin - Realtime Monitoring)
-- ============================================================
CREATE TABLE IF NOT EXISTS `machine_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `machine_id` INT(11) UNSIGNED NOT NULL,
  `order_id` INT(11) UNSIGNED DEFAULT NULL,
  `event` ENUM('start','pause','resume','stop','error','maintenance_start','maintenance_end') NOT NULL,
  `description` TEXT DEFAULT NULL,
  `operator_id` INT(11) UNSIGNED DEFAULT NULL,
  `duration_minutes` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_machinelogs_machine` (`machine_id`),
  KEY `fk_machinelogs_order` (`order_id`),
  CONSTRAINT `fk_machinelogs_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_machinelogs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications (Notifikasi Sistem)
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('low_stock','order_status','machine_alert','system','maintenance') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT(11) UNSIGNED DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_logs (Log Aktivitas User)
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `reference_id` INT(11) UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_actlog_user` (`user_id`),
  CONSTRAINT `fk_actlog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: deliveries (Pengiriman Order)
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

-- ============================================================
-- TABLE: settings (Pengaturan Sistem)
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT DEFAULT NULL,
  `group` VARCHAR(50) DEFAULT 'general',
  `label` VARCHAR(100) DEFAULT NULL,
  `type` ENUM('text','number','boolean','json','select') DEFAULT 'text',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL (SEED DATA)
-- ============================================================

-- Users
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@percetakan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Operator 1', 'operator1@percetakan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator'),
('Viewer', 'viewer@percetakan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer');

-- Units
INSERT INTO `units` (`name`, `symbol`) VALUES
('Rim', 'Rim'),
('Lembar', 'Lbr'),
('Kilogram', 'Kg'),
('Liter', 'L'),
('Roll', 'Roll'),
('Botol', 'Btl'),
('Kaleng', 'Klg'),
('Pcs', 'Pcs'),
('Meter', 'M'),
('Box', 'Box');

-- Categories
INSERT INTO `categories` (`name`, `description`, `color`, `icon`) VALUES
('Kertas', 'Bahan baku kertas berbagai jenis', '#6366f1', 'file'),
('Tinta', 'Tinta cetak dan toner', '#8b5cf6', 'droplet'),
('Plate', 'Plate cetak offset dan digital', '#06b6d4', 'layers'),
('Chemical', 'Bahan kimia proses cetak', '#f59e0b', 'flask'),
('Finishing', 'Bahan finishing dan binding', '#10b981', 'package'),
('Spare Part', 'Suku cadang mesin', '#ef4444', 'tool');

-- Suppliers
INSERT INTO `suppliers` (`code`, `name`, `contact_person`, `phone`, `email`, `city`) VALUES
('SUP001', 'PT. Sinar Kertas Nusantara', 'Budi Santoso', '021-5551234', 'budi@sinarkertas.com', 'Jakarta'),
('SUP002', 'CV. Tinta Jaya Abadi', 'Sari Dewi', '021-5565678', 'sari@tintajaya.com', 'Tangerang'),
('SUP003', 'PT. Grafika Mandiri', 'Ahmad Fauzi', '031-7789012', 'ahmad@grafikamandiri.com', 'Surabaya'),
('SUP004', 'UD. Chemical Printing', 'Lisa Putri', '022-4456789', 'lisa@chemprint.com', 'Bandung');

-- Machines
INSERT INTO `machines` (`code`, `name`, `type`, `brand`, `status`) VALUES
('MCH001', 'Mesin Offset 1', 'Offset Printing', 'Heidelberg', 'active'),
('MCH002', 'Mesin Offset 2', 'Offset Printing', 'Komori', 'idle'),
('MCH003', 'Mesin Digital 1', 'Digital Printing', 'HP Indigo', 'active'),
('MCH004', 'Mesin Cutting', 'Cutting & Finishing', 'Polar', 'idle'),
('MCH005', 'Mesin Laminating', 'Finishing', 'GMP', 'maintenance');

-- Customers
INSERT INTO `customers` (`code`, `name`, `contact_person`, `phone`, `city`) VALUES
('CUS001', 'PT. Maju Bersama', 'Rina Wijaya', '0812-3456789', 'Jakarta'),
('CUS002', 'CV. Kreasi Digital', 'Doni Prasetyo', '0813-9876543', 'Bandung'),
('CUS003', 'Toko Buku Cerdas', 'Maya Sari', '0856-1234567', 'Surabaya'),
('CUS004', 'PT. Advertise Pro', 'Hendra Gunawan', '0811-5432198', 'Jakarta'),
('CUS005', 'UD. Paket Hemat', 'Siti Aminah', '0821-7654321', 'Yogyakarta');

-- Items (Bahan Baku)
INSERT INTO `items` (`code`, `name`, `category_id`, `unit_id`, `supplier_id`, `stock`, `min_stock`, `max_stock`, `purchase_price`, `location`) VALUES
('ITM001', 'Kertas HVS A4 70gr', 1, 1, 1, 50, 10, 200, 45000, 'Gudang A-1'),
('ITM002', 'Kertas HVS A3 80gr', 1, 1, 1, 30, 8, 150, 85000, 'Gudang A-1'),
('ITM003', 'Kertas Art Paper 150gr', 1, 1, 1, 20, 5, 100, 125000, 'Gudang A-2'),
('ITM004', 'Kertas Duplex 230gr', 1, 1, 1, 15, 5, 80, 95000, 'Gudang A-2'),
('ITM005', 'Tinta Cyan Offset', 2, 3, 2, 25, 5, 50, 320000, 'Gudang B-1'),
('ITM006', 'Tinta Magenta Offset', 2, 3, 2, 22, 5, 50, 320000, 'Gudang B-1'),
('ITM007', 'Tinta Yellow Offset', 2, 3, 2, 20, 5, 50, 300000, 'Gudang B-1'),
('ITM008', 'Tinta Black Offset', 2, 3, 2, 8, 5, 50, 280000, 'Gudang B-1'),
('ITM009', 'Plate CTCP A1', 3, 8, 3, 100, 20, 500, 35000, 'Gudang C-1'),
('ITM010', 'Cairan Developer Plate', 4, 4, 4, 3, 2, 20, 180000, 'Gudang D-1'),
('ITM011', 'Fountain Solution', 4, 4, 4, 10, 3, 30, 95000, 'Gudang D-1'),
('ITM012', 'Blanket Offset B2', 6, 8, 3, 4, 2, 10, 750000, 'Gudang C-2');

-- Settings
INSERT INTO `settings` (`key`, `value`, `group`, `label`, `type`) VALUES
('company_name', 'Percetakan Modern', 'general', 'Nama Perusahaan', 'text'),
('company_address', 'Jl. Industri Percetakan No. 1', 'general', 'Alamat', 'text'),
('company_phone', '021-12345678', 'general', 'Telepon', 'text'),
('company_email', 'info@percetakan.com', 'general', 'Email', 'text'),
('low_stock_alert', '1', 'notification', 'Alert Stok Rendah', 'boolean'),
('order_prefix', 'ORD', 'order', 'Prefix Order', 'text'),
('purchase_prefix', 'PRC', 'purchase', 'Prefix Pembelian', 'text'),
('tax_percentage', '11', 'finance', 'PPN (%)', 'number'),
('refresh_interval', '30', 'monitoring', 'Interval Refresh (detik)', 'number');
