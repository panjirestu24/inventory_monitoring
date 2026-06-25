-- ============================================================
-- MIGRATION: Hapus role viewer dari sistem
-- Jalankan di phpMyAdmin → db_inventory → tab SQL
-- ============================================================

-- 1. Ubah user yang masih role viewer jadi operator
UPDATE `users` SET `role` = 'operator' WHERE `role` = 'viewer';

-- 2. Hapus akun demo viewer (jika ada)
DELETE FROM `users` WHERE `email` = 'viewer@percetakan.com';

-- 3. Ubah kolom ENUM — hapus pilihan 'viewer'
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','operator') NOT NULL DEFAULT 'operator';

-- ============================================================
-- Selesai. Role viewer sudah dihapus dari sistem.
-- ============================================================
