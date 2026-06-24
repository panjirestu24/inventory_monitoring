-- ============================================================
-- MIGRATION: Tambah kolom proof_image di tabel deliveries
-- Jalankan di phpMyAdmin → db_inventory → tab SQL
-- ============================================================
ALTER TABLE `deliveries`
  ADD COLUMN `proof_image` VARCHAR(255) DEFAULT NULL
    COMMENT 'Foto bukti pengiriman diterima'
  AFTER `actual_arrival`;
