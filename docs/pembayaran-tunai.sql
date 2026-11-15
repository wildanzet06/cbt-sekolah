-- =====================================================================
-- PEMBAYARAN TUNAI (cash di sekolah)
-- Menambah nilai 'tunai' pada kolom metode_pembayaran, supaya admin bisa
-- mencatat pembayaran SPP yang dibayar tunai langsung dari menu Keuangan.
--
-- Aman dijalankan berulang. Backup database lebih dulu.
--   mysql -u root cbt < docs/pembayaran-tunai.sql
-- =====================================================================

ALTER TABLE cbt_pembayaran_spp
    MODIFY COLUMN metode_pembayaran ENUM('transfer_manual','midtrans','tunai') NOT NULL;

-- Pemeriksaan
SELECT COLUMN_TYPE AS tipe_metode_pembayaran
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'cbt_pembayaran_spp'
  AND COLUMN_NAME = 'metode_pembayaran';
