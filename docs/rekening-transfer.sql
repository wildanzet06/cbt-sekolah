-- =====================================================================
-- REKENING TUJUAN TRANSFER (untuk pembayaran SPP manual)
-- Nomor rekening yang tampil di halaman pembayaran siswa.
--
-- Aman dijalankan berulang.
--   mysql -u root cbt < docs/rekening-transfer.sql
-- =====================================================================

-- 1. Tabel rekening (boleh lebih dari satu bank)
CREATE TABLE IF NOT EXISTS `cbt_rekening` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank`       VARCHAR(50)  NOT NULL,
    `nomor`      VARCHAR(50)  NOT NULL,
    `atas_nama`  VARCHAR(100) NOT NULL,
    `urut`       INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Instruksi tambahan untuk siswa
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_setting_spp ADD COLUMN instruksi_transfer TEXT NULL',
    'SELECT ''instruksi_transfer sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_setting_spp'
      AND COLUMN_NAME = 'instruksi_transfer');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Isi rekening lewat menu: Keuangan > Setting Pembayaran
SELECT * FROM cbt_rekening ORDER BY urut, id;
