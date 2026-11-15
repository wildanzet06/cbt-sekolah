-- =====================================================================
-- Migrasi modul "Ujian Sekolah" (menu Daftar Ujian)
-- PRD: docs/PRD-UJIAN-SIMPEL.md  (Fase 0)
--
-- Sifat migrasi: ADITIF. Tidak ada kolom/tabel lama yang diubah atau
-- dihapus, sehingga halaman lama (Jadwal, Bank Soal, Cetak, Token, dll.)
-- tetap berjalan apa adanya.
--
-- Cara pakai:
--   mysql -u root cbt < docs/ujiansekolah.sql
-- atau jalankan file ini lewat phpMyAdmin.
--
-- Modul baru juga menjalankan migrasi ini otomatis saat pertama kali
-- dibuka (cek kolom, lalu ALTER hanya jika belum ada). File ini disediakan
-- untuk eksekusi manual / audit.
--
-- BACKUP dulu sebelum menjalankan:
--   mysqldump -u root cbt > backup-cbt-sebelum-ujian-sekolah.sql
-- =====================================================================

-- 1. Kapasitas ruang (default 35 sesuai keputusan #1)
ALTER TABLE `cbt_ruang`
    ADD COLUMN `kapasitas` INT NOT NULL DEFAULT 35;

-- 2. Token per ujian + pembuat ujian
ALTER TABLE `cbt_jadwal`
    ADD COLUMN `token_ujian` VARCHAR(10) NULL DEFAULT NULL,
    ADD COLUMN `created_by`  INT NULL DEFAULT NULL;

-- 3. Catatan tambahan ujian (hanya dipakai bila ujian disimpan sebagai draf
--    tanpa kumpulan soal, sehingga mapel/kelas belum bisa diturunkan dari
--    cbt_bank_soal; juga menyimpan catatan bebas). Aman: tidak dibaca kode lama.
CREATE TABLE IF NOT EXISTS `cbt_jadwal_meta` (
    `id_jadwal` INT         NOT NULL,
    `id_mapel`  INT         NULL DEFAULT NULL,
    `kelas`     VARCHAR(255) NULL DEFAULT NULL COMMENT 'id_kelas dipisah koma',
    `catatan`   VARCHAR(255) NULL DEFAULT NULL,
    `updated`   DATETIME    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_jadwal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
