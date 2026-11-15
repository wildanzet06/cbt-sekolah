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
