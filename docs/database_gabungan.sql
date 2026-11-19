-- =====================================================================
-- DATABASE GABUNGAN (CBT)
-- Satu file berisi semua migrasi/tabel tambahan aplikasi.
-- Isi SQL diambil apa adanya dari file-file aslinya (tidak diubah).
--
--   1. database_tambahan.sql    Keuangan (SPP/tagihan), keringanan, presensi, poin pelanggaran, setting pembayaran + rekening
--   2. pembayaran-tunai.sql     Metode pembayaran tunai (cash)
--   3. rekening-transfer.sql    Rekening tujuan transfer (idempoten; juga sudah ada di database_tambahan)
--   4. nilai_manual.sql         Tabel nilai manual (opsional; dibuat otomatis oleh aplikasi)
--   5. ujiansekolah.sql         Kapasitas ruang, token per ujian, created_by, cbt_jadwal_meta
--
-- Semua perintah aman dijalankan berulang (CREATE IF NOT EXISTS / pengecekan
-- information_schema), kecuali bagian 5 (ujiansekolah.sql) yang memakai
-- ALTER TABLE langsung. Bila kolomnya sudah ada, MySQL bisa melaporkan
-- "Duplicate column name ..." — pesan itu boleh diabaikan karena kolomnya
-- memang sudah ada.
--
-- Cara pakai:
--   mysql -u root cbt < docs/database_gabungan.sql
-- atau buka lewat phpMyAdmin (Import).
--
-- BACKUP dulu sebelum menjalankan:
--   mysqldump -u root cbt > backup-cbt-sebelum-migrasi.sql
-- =====================================================================

-- =====================================================================
-- FILE: database_tambahan.sql
-- =====================================================================
-- =====================================================================
-- DATABASE TAMBAHAN: Keuangan (SPP & tagihan), Keringanan, Presensi,
-- Poin Pelanggaran Siswa
-- =====================================================================
-- Jalankan SEKALI SETELAH instalasi GarudaCBT selesai, pada database
-- aplikasi (di contoh ini bernama "cbt").
--
-- Aman dijalankan berulang: tabel yang sudah ada tidak dibuat ulang dan
-- kolom yang sudah ada tidak ditambah lagi. Backup database lebih dulu.
-- Ditulis untuk MySQL 5.7+ / MariaDB 10.3+.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Pembayaran SPP (catatan setiap pembayaran)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_pembayaran_spp (
    id                 INT          NOT NULL AUTO_INCREMENT,
    siswa_id           INT          NOT NULL,
    bulan              VARCHAR(20)  NOT NULL,
    tahun              INT          NOT NULL,
    jumlah_bayar       DECIMAL(15,2) NOT NULL,
    metode_pembayaran  ENUM('transfer_manual','midtrans','tunai') NOT NULL,
    status_pembayaran  ENUM('pending','success','failed') DEFAULT 'pending',
    tanggal_bayar      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    bukti_transfer     VARCHAR(255) DEFAULT NULL,
    order_id           VARCHAR(100) DEFAULT NULL,
    transaction_id     VARCHAR(100) DEFAULT NULL,
    created_at         TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    tagihan_id         INT UNSIGNED DEFAULT NULL,
    catatan_admin      VARCHAR(255) DEFAULT NULL,
    diverifikasi_oleh  VARCHAR(100) DEFAULT NULL,
    diverifikasi_pada  DATETIME     DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_tagihan (tagihan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kolom tambahan jika tabelnya sudah ada dari versi lama
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_pembayaran_spp ADD COLUMN tagihan_id INT UNSIGNED DEFAULT NULL, ADD INDEX idx_tagihan (tagihan_id)',
    'SELECT ''tagihan_id sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_pembayaran_spp' AND COLUMN_NAME = 'tagihan_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_pembayaran_spp ADD COLUMN catatan_admin VARCHAR(255) DEFAULT NULL',
    'SELECT ''catatan_admin sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_pembayaran_spp' AND COLUMN_NAME = 'catatan_admin');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_oleh VARCHAR(100) DEFAULT NULL',
    'SELECT ''diverifikasi_oleh sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_pembayaran_spp' AND COLUMN_NAME = 'diverifikasi_oleh');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_pada DATETIME DEFAULT NULL',
    'SELECT ''diverifikasi_pada sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_pembayaran_spp' AND COLUMN_NAME = 'diverifikasi_pada');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Metode pembayaran tunai (siswa bayar cash di sekolah)
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_pembayaran_spp MODIFY COLUMN metode_pembayaran ENUM(''transfer_manual'',''midtrans'',''tunai'') NOT NULL',
    'SELECT ''metode tunai sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_pembayaran_spp'
      AND COLUMN_NAME = 'metode_pembayaran' AND COLUMN_TYPE NOT LIKE '%tunai%');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2. Pengaturan nominal SPP
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_setting_spp (
    id           INT           NOT NULL AUTO_INCREMENT,
    nominal_spp  DECIMAL(15,2) NOT NULL,
    keterangan   VARCHAR(255)  DEFAULT NULL,
    instruksi_transfer TEXT    NULL,
  kunci_kwitansi     VARCHAR(64) NULL,
    updated_at   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ambang poin Surat Peringatan (SP1/SP2/SP3)
CREATE TABLE IF NOT EXISTS cbt_setting_poin (
    id         INT NOT NULL AUTO_INCREMENT,
    sp1        INT NOT NULL DEFAULT 25,
    sp2        INT NOT NULL DEFAULT 50,
    sp3        INT NOT NULL DEFAULT 75,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO cbt_setting_poin (sp1, sp2, sp3)
SELECT 25, 50, 75 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM cbt_setting_poin);

-- Buku kas sekolah (pemasukan lain & pengeluaran)
CREATE TABLE IF NOT EXISTS cbt_kas (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tanggal     DATE NOT NULL,
    jenis       ENUM('masuk','keluar') NOT NULL DEFAULT 'keluar',
    kategori    VARCHAR(60)  NOT NULL,
    keterangan  VARCHAR(255) NULL,
    nominal     DECIMAL(15,2) NOT NULL DEFAULT 0,
    dibuat_oleh VARCHAR(100) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tanggal (tanggal),
    KEY idx_jenis (jenis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Rekening tujuan transfer manual (boleh lebih dari satu bank)
CREATE TABLE IF NOT EXISTS cbt_rekening (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bank       VARCHAR(50)  NOT NULL,
    nomor      VARCHAR(50)  NOT NULL,
    atas_nama  VARCHAR(100) NOT NULL,
    urut       INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Isi awal (hanya jika tabel masih kosong). Ubah nominalnya lewat menu aplikasi.
INSERT INTO cbt_setting_spp (nominal_spp, keterangan)
SELECT 500000, 'SPP Bulanan Default' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM cbt_setting_spp);

-- Kolom instruksi_transfer untuk pemasangan lama
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_setting_spp ADD COLUMN instruksi_transfer TEXT NULL',
    'SELECT ''instruksi_transfer sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_setting_spp'
      AND COLUMN_NAME = 'instruksi_transfer');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Kolom kunci_kwitansi (kunci QR kwitansi stabil, tidak ikut rotasi encryption_key)
SET @sql = (SELECT IF(COUNT(*) = 0,
'ALTER TABLE cbt_setting_spp ADD COLUMN kunci_kwitansi VARCHAR(64) NULL',
'SELECT ''kunci_kwitansi sudah ada'' AS info')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_setting_spp'
AND COLUMN_NAME = 'kunci_kwitansi');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 3. Jenis tagihan, tagihan, dan keringanan
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_jenis_tagihan (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode             VARCHAR(20)  NOT NULL,
    nama             VARCHAR(100) NOT NULL,
    tipe             ENUM('bulanan','sekali') NOT NULL DEFAULT 'sekali',
    nominal_default  INT UNSIGNED NOT NULL DEFAULT 0,
    boleh_cicil      TINYINT(1)   NOT NULL DEFAULT 0,
    keterangan       VARCHAR(255) NULL,
    aktif            TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cbt_jenis_tagihan ADD COLUMN boleh_cicil TINYINT(1) NOT NULL DEFAULT 0 AFTER nominal_default',
    'SELECT ''boleh_cicil sudah ada'' AS info')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_jenis_tagihan' AND COLUMN_NAME = 'boleh_cicil');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS cbt_tagihan (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id       INT          NOT NULL,
    jenis_id       INT UNSIGNED NOT NULL,
    periode_bulan  VARCHAR(20)  NULL,
    periode_tahun  SMALLINT     NULL,
    keterangan     VARCHAR(255) NULL,
    nominal        INT UNSIGNED NOT NULL,
    diskon         INT UNSIGNED NOT NULL DEFAULT 0,
    jatuh_tempo    DATE         NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_tagihan (siswa_id, jenis_id, periode_bulan, periode_tahun),
    KEY idx_jenis (jenis_id),
    KEY idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cbt_keringanan (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id    INT          NOT NULL,
    jenis_id    INT UNSIGNED NULL,
    tipe        ENUM('persen','nominal') NOT NULL DEFAULT 'persen',
    nilai       INT UNSIGNED NOT NULL,
    keterangan  VARCHAR(255) NULL,
    aktif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_siswa (siswa_id),
    KEY idx_jenis (jenis_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jenis tagihan awal: SPP bulanan (nominal mengikuti pengaturan SPP di atas)
INSERT IGNORE INTO cbt_jenis_tagihan (kode, nama, tipe, nominal_default, keterangan)
VALUES ('SPP', 'SPP Bulanan', 'bulanan', 0, 'Iuran bulanan');

UPDATE cbt_jenis_tagihan j
JOIN (SELECT nominal_spp FROM cbt_setting_spp ORDER BY id LIMIT 1) s
SET j.nominal_default = s.nominal_spp
WHERE j.kode = 'SPP' AND j.nominal_default = 0;

-- ---------------------------------------------------------------------
-- 4. Penanda "password sudah diganti pengguna" (hanya nama + waktu)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_password_diganti (
    username      VARCHAR(100) NOT NULL,
    diganti_pada  INT UNSIGNED NOT NULL,
    PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. Presensi per pelajaran (H = Hadir, I = Izin, S = Sakit, A = Alpa)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_presensi_sesi (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_tp        INT          NOT NULL,
    id_smt       INT          NOT NULL,
    id_kelas     INT          NOT NULL,
    tanggal      DATE         NOT NULL,
    jam_ke       TINYINT UNSIGNED NOT NULL,
    mapel_id     INT          NULL,
    mapel_nama   VARCHAR(100) NULL,
    dibuat_oleh  VARCHAR(100) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sesi (id_kelas, tanggal, jam_ke),
    KEY idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cbt_presensi (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sesi_id     INT UNSIGNED NOT NULL,
    siswa_id    INT          NOT NULL,
    status      CHAR(1)      NOT NULL,
    keterangan  VARCHAR(255) NULL,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_presensi (sesi_id, siswa_id),
    KEY idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. Poin pelanggaran siswa (input manual oleh admin/guru)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_pelanggaran_siswa (
    id_pelanggaran     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_siswa           INT          NOT NULL,
    id_kelas           INT          NULL,
    id_tp              INT          NOT NULL,
    id_smt             INT          NOT NULL,
    tanggal            DATE         NOT NULL,
    jenis_pelanggaran  VARCHAR(150) NOT NULL,
    keterangan         TEXT         NULL,
    poin               INT          NOT NULL DEFAULT 0,
    id_guru            INT          NULL,
    dicatat_oleh       VARCHAR(100) NULL,
    created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pelanggaran),
    KEY idx_siswa (id_siswa),
    KEY idx_kelas (id_kelas),
    KEY idx_periode (id_tp, id_smt),
    KEY idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Pemeriksaan: harus tampil 9 tabel
-- ---------------------------------------------------------------------
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('cbt_pembayaran_spp','cbt_setting_spp','cbt_jenis_tagihan','cbt_tagihan',
                     'cbt_keringanan','cbt_password_diganti','cbt_presensi_sesi','cbt_presensi',
                     'cbt_pelanggaran_siswa')
ORDER BY TABLE_NAME;

-- =====================================================================
-- FILE: pembayaran-tunai.sql
-- =====================================================================
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

-- =====================================================================
-- FILE: rekening-transfer.sql
-- =====================================================================
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

-- =====================================================================
-- FILE: nilai_manual.sql
-- =====================================================================
-- Opsional: tabel ini dibuat OTOMATIS saat menu Nilai Manual pertama kali dibuka.
-- Jalankan manual hanya jika Anda lebih suka membuatnya sendiri.
CREATE TABLE IF NOT EXISTS `cbt_nilai_manual` (
  `id_nm` int NOT NULL AUTO_INCREMENT,
  `id_tp` int NOT NULL,
  `id_smt` int NOT NULL,
  `id_mapel` int NOT NULL,
  `id_kelas` int NOT NULL,
  `kode_jenis` varchar(10) NOT NULL DEFAULT 'PH',
  `aspek` tinyint NOT NULL DEFAULT 1,
  `nama_ujian` varchar(100) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `nilai_maks` int NOT NULL DEFAULT 100,
  `id_guru` int DEFAULT NULL,
  `slot_rapor` varchar(4) DEFAULT NULL,
  `rapor_terkirim` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_nm`),
  KEY `idx_kelas_mapel` (`id_tp`,`id_smt`,`id_kelas`,`id_mapel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cbt_nilai_manual_detail` (
  `id_nmd` int NOT NULL AUTO_INCREMENT,
  `id_nm` int NOT NULL,
  `id_siswa` int NOT NULL,
  `nilai` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`id_nmd`),
  UNIQUE KEY `uniq_nm_siswa` (`id_nm`,`id_siswa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- FILE: ujiansekolah.sql
-- =====================================================================
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
-- Bacaan/stimulus bersama untuk beberapa soal
CREATE TABLE IF NOT EXISTS cbt_bacaan (
    id_bacaan  INT          NOT NULL AUTO_INCREMENT,
    judul      VARCHAR(255) NOT NULL,
    isi        LONGTEXT     NULL,
    created_by INT          NULL,
    created_at TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bacaan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = (SELECT IF(COUNT(*) = 0,
'ALTER TABLE cbt_soal ADD COLUMN id_bacaan INT NULL',
'SELECT ''id_bacaan sudah ada'' AS info')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_soal'
AND COLUMN_NAME = 'id_bacaan');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- Judul ujian (opsional) pada jadwal ujian
SET @sql = (SELECT IF(COUNT(*) = 0,
'ALTER TABLE cbt_jadwal ADD COLUMN judul VARCHAR(255) NULL',
'SELECT ''judul sudah ada'' AS info')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cbt_jadwal'
AND COLUMN_NAME = 'judul');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
