-- =====================================================================
-- DATABASE TAMBAHAN: Keuangan (SPP & tagihan), Keringanan, Presensi
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
    metode_pembayaran  ENUM('transfer_manual','midtrans') NOT NULL,
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

-- ---------------------------------------------------------------------
-- 2. Pengaturan nominal SPP
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cbt_setting_spp (
    id           INT           NOT NULL AUTO_INCREMENT,
    nominal_spp  DECIMAL(15,2) NOT NULL,
    keterangan   VARCHAR(255)  DEFAULT NULL,
    updated_at   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Isi awal (hanya jika tabel masih kosong). Ubah nominalnya lewat menu aplikasi.
INSERT INTO cbt_setting_spp (nominal_spp, keterangan)
SELECT 500000, 'SPP Bulanan Default' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM cbt_setting_spp);

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
-- Pemeriksaan: harus tampil 8 tabel
-- ---------------------------------------------------------------------
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('cbt_pembayaran_spp','cbt_setting_spp','cbt_jenis_tagihan','cbt_tagihan',
                     'cbt_keringanan','cbt_password_diganti','cbt_presensi_sesi','cbt_presensi')
ORDER BY TABLE_NAME;
