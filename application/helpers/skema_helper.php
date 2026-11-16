<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penjaga skema tabel tambahan (keuangan, tagihan, presensi).
 *
 * Tabel-tabel ini tidak termasuk skema dasar GarudaCBT, jadi bisa hilang bila
 * database dipulihkan dari dump lama atau tabelnya terhapus. Dulu hal itu
 * membuat halaman keuangan menampilkan "Table ... doesn't exist".
 *
 * Fungsi di sini memastikan tabelnya ada sebelum dipakai (idempoten):
 * cepat bila semua sudah ada (1 query), dan membuat/melengkapi bila kurang.
 */

if (!function_exists('skema_tambahan_pastikan')) {
    function skema_tambahan_pastikan($db)
    {
        static $selesai = false;
        if ($selesai) {
            return true;
        }
        $selesai = true;

        $tabel = array(
            'cbt_pembayaran_spp', 'cbt_setting_spp', 'cbt_jenis_tagihan', 'cbt_tagihan',
            'cbt_keringanan', 'cbt_password_diganti', 'cbt_presensi_sesi', 'cbt_presensi',
            'cbt_rekening'
        );

        $in  = "'" . implode("','", $tabel) . "'";
        $row = $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name IN ($in)"
        )->row();

        if ($row && (int) $row->c === count($tabel)) {
            return true;
        }

        // Ada yang kurang: buat ulang. Jangan tampilkan halaman error database
        // bila satu perintah gagal (mis. hak akses), cukup lanjut.
        $debug = $db->db_debug;
        $db->db_debug = false;

        $ddl = array(
            "CREATE TABLE IF NOT EXISTS cbt_pembayaran_spp (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_setting_spp (
                id           INT           NOT NULL AUTO_INCREMENT,
                nominal_spp  DECIMAL(15,2) NOT NULL,
                keterangan   VARCHAR(255)  DEFAULT NULL,
                instruksi_transfer TEXT    NULL,
                updated_at   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_jenis_tagihan (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_tagihan (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_keringanan (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_password_diganti (
                username      VARCHAR(100) NOT NULL,
                diganti_pada  INT UNSIGNED NOT NULL,
                PRIMARY KEY (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_presensi_sesi (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_presensi (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                sesi_id     INT UNSIGNED NOT NULL,
                siswa_id    INT          NOT NULL,
                status      CHAR(1)      NOT NULL,
                keterangan  VARCHAR(255) NULL,
                updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_presensi (sesi_id, siswa_id),
                KEY idx_siswa (siswa_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_rekening (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                bank       VARCHAR(50)  NOT NULL,
                nomor      VARCHAR(50)  NOT NULL,
                atas_nama  VARCHAR(100) NOT NULL,
                urut       INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        foreach ($ddl as $sql) {
            $db->query($sql);
        }

        // Kolom yang mungkin belum ada pada pemasangan versi lama.
        $kolom = array(
            array('cbt_pembayaran_spp', 'tagihan_id',        'ALTER TABLE cbt_pembayaran_spp ADD COLUMN tagihan_id INT UNSIGNED DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'catatan_admin',     'ALTER TABLE cbt_pembayaran_spp ADD COLUMN catatan_admin VARCHAR(255) DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'diverifikasi_oleh', 'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'diverifikasi_pada', 'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_pada DATETIME DEFAULT NULL'),
            array('cbt_setting_spp',    'instruksi_transfer', 'ALTER TABLE cbt_setting_spp ADD COLUMN instruksi_transfer TEXT NULL'),
            array('cbt_jenis_tagihan',  'boleh_cicil',       'ALTER TABLE cbt_jenis_tagihan ADD COLUMN boleh_cicil TINYINT(1) NOT NULL DEFAULT 0')
        );

        foreach ($kolom as $k) {
            if (!$db->field_exists($k[1], $k[0])) {
                $db->query($k[2]);
            }
        }

        // Pastikan nilai 'tunai' tersedia pada metode pembayaran.
        $metode = $db->query("SHOW COLUMNS FROM cbt_pembayaran_spp LIKE 'metode_pembayaran'")->row();
        if ($metode && stripos((string) $metode->Type, "'tunai'") === false) {
            $db->query("ALTER TABLE cbt_pembayaran_spp
                        MODIFY COLUMN metode_pembayaran ENUM('transfer_manual','midtrans','tunai') NOT NULL");
        }

        // Isi awal (sama seperti docs/database_gabungan.sql), hanya bila kosong.
        $db->query("INSERT INTO cbt_setting_spp (nominal_spp, keterangan)
                    SELECT 500000, 'SPP Bulanan Default' FROM DUAL
                    WHERE NOT EXISTS (SELECT 1 FROM cbt_setting_spp)");
        $db->query("INSERT IGNORE INTO cbt_jenis_tagihan (kode, nama, tipe, nominal_default, aktif)
                    VALUES ('SPP', 'SPP Bulanan', 'bulanan', 500000, 1)");
        $db->query("UPDATE cbt_jenis_tagihan SET nominal_default = 500000
                    WHERE kode = 'SPP' AND nominal_default = 0");

        $db->db_debug = $debug;
        return true;
    }
}
