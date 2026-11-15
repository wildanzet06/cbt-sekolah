/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-12.3.3-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cbt
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `cbt_sesi_siswa`
--

DROP TABLE IF EXISTS `cbt_sesi_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cbt_sesi_siswa` (
  `siswa_id` int NOT NULL,
  `kelas_id` int DEFAULT NULL,
  `ruang_id` int NOT NULL,
  `sesi_id` int NOT NULL,
  `tp_id` int NOT NULL,
  `smt_id` int NOT NULL,
  PRIMARY KEY (`siswa_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbt_sesi_siswa`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cbt_sesi_siswa` WRITE;
/*!40000 ALTER TABLE `cbt_sesi_siswa` DISABLE KEYS */;
INSERT INTO `cbt_sesi_siswa` VALUES
(1,1,2,2,5,1),
(2,1,2,2,5,1),
(3,1,2,2,5,1),
(4,1,2,2,5,1),
(5,1,2,2,5,1),
(6,1,2,2,5,1),
(7,1,2,2,5,1),
(8,1,2,2,5,1),
(9,1,2,2,5,1),
(10,1,2,2,5,1),
(11,1,2,2,5,1),
(12,1,2,2,5,1),
(13,1,2,2,5,1),
(14,1,2,2,5,1),
(15,1,2,2,5,1),
(16,1,2,2,5,1),
(17,1,2,2,5,1),
(18,1,2,2,5,1),
(19,1,2,2,5,1),
(20,1,2,2,5,1),
(21,1,2,2,5,1),
(22,1,2,2,5,1),
(23,1,2,2,5,1),
(24,1,2,2,5,1);
/*!40000 ALTER TABLE `cbt_sesi_siswa` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cbt_nomor_peserta`
--

DROP TABLE IF EXISTS `cbt_nomor_peserta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cbt_nomor_peserta` (
  `id_nomor` int NOT NULL,
  `id_siswa` int DEFAULT NULL,
  `id_tp` int NOT NULL,
  `id_smt` int NOT NULL DEFAULT '1',
  `nomor_peserta` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_nomor`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbt_nomor_peserta`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cbt_nomor_peserta` WRITE;
/*!40000 ALTER TABLE `cbt_nomor_peserta` DISABLE KEYS */;
INSERT INTO `cbt_nomor_peserta` VALUES
(15,1,5,1,'2627.07.003'),
(25,2,5,1,'2627.07.001'),
(35,3,5,1,'2627.07.002'),
(45,4,5,1,'2627.07.004'),
(55,5,5,1,'2627.07.005'),
(65,6,5,1,'2627.07.006'),
(75,7,5,1,'2627.07.007'),
(85,8,5,1,'2627.07.008'),
(95,9,5,1,'2627.07.009'),
(105,10,5,1,'2627.07.010'),
(115,11,5,1,'2627.07.011'),
(125,12,5,1,'2627.07.012'),
(135,13,5,1,'2627.07.013'),
(145,14,5,1,'2627.07.014'),
(155,15,5,1,'2627.07.015'),
(165,16,5,1,'2627.07.016'),
(175,17,5,1,'2627.07.017'),
(185,18,5,1,'2627.07.018'),
(195,19,5,1,'2627.07.019'),
(205,20,5,1,'2627.07.020'),
(215,21,5,1,'2627.07.021'),
(225,22,5,1,'2627.07.022'),
(235,23,5,1,'2627.07.023'),
(245,24,5,1,'2627.07.024');
/*!40000 ALTER TABLE `cbt_nomor_peserta` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cbt_kelas_ruang`
--

DROP TABLE IF EXISTS `cbt_kelas_ruang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cbt_kelas_ruang` (
  `id_kelas_ruang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_kelas` int DEFAULT NULL,
  `id_ruang` int NOT NULL,
  `id_sesi` int NOT NULL DEFAULT '0',
  `id_tp` int NOT NULL,
  `id_smt` int NOT NULL,
  `set_siswa` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_kelas_ruang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbt_kelas_ruang`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cbt_kelas_ruang` WRITE;
/*!40000 ALTER TABLE `cbt_kelas_ruang` DISABLE KEYS */;
/*!40000 ALTER TABLE `cbt_kelas_ruang` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cbt_jadwal`
--

DROP TABLE IF EXISTS `cbt_jadwal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cbt_jadwal` (
  `id_jadwal` int NOT NULL AUTO_INCREMENT,
  `id_tp` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_smt` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_bank` int DEFAULT NULL,
  `id_jenis` int DEFAULT NULL,
  `tgl_mulai` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tgl_selesai` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `durasi_ujian` int NOT NULL,
  `pengawas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `acak_soal` int NOT NULL,
  `acak_opsi` int NOT NULL,
  `hasil_tampil` int NOT NULL,
  `token` int NOT NULL,
  `status` int NOT NULL,
  `ulang` int NOT NULL,
  `reset_login` int NOT NULL,
  `rekap` int NOT NULL DEFAULT '0',
  `jam_ke` int NOT NULL DEFAULT '0',
  `jarak` int NOT NULL DEFAULT '0',
  `time_create` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `token_ujian` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id_jadwal`) USING BTREE,
  UNIQUE KEY `idjawal_relation` (`id_jadwal`) USING BTREE,
  UNIQUE KEY `id_bank_soal` (`id_bank`) USING BTREE,
  KEY `idx_jns_fc` (`id_jenis`) USING BTREE,
  CONSTRAINT `id_bank_soal` FOREIGN KEY (`id_bank`) REFERENCES `cbt_bank_soal` (`id_bank`),
  CONSTRAINT `id_jns_idx_ifc` FOREIGN KEY (`id_jenis`) REFERENCES `cbt_jenis` (`id_jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbt_jadwal`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cbt_jadwal` WRITE;
/*!40000 ALTER TABLE `cbt_jadwal` DISABLE KEYS */;
INSERT INTO `cbt_jadwal` VALUES
(1,'5','1',1,1,'2026-11-14','2026-11-15',60,NULL,1,1,0,0,1,0,0,1,2,30,'2026-11-14 12:00:22',NULL,NULL);
/*!40000 ALTER TABLE `cbt_jadwal` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cbt_pengawas`
--

DROP TABLE IF EXISTS `cbt_pengawas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cbt_pengawas` (
  `id_pengawas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_jadwal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_tp` int NOT NULL,
  `id_smt` int NOT NULL,
  `id_ruang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_sesi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_guru` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_pengawas`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbt_pengawas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cbt_pengawas` WRITE;
/*!40000 ALTER TABLE `cbt_pengawas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cbt_pengawas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cbt_bank_soal`
--

DROP TABLE IF EXISTS `cbt_bank_soal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cbt_bank_soal` (
  `id_bank` int NOT NULL AUTO_INCREMENT,
  `bank_jenis_id` int NOT NULL DEFAULT '0',
  `bank_kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `bank_level` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_kelas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_mapel_id` int DEFAULT NULL,
  `bank_jurusan_id` int NOT NULL DEFAULT '0',
  `bank_guru_id` int DEFAULT NULL,
  `bank_nama` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kkm` int DEFAULT '0',
  `jml_soal` int NOT NULL DEFAULT '0',
  `jml_esai` int NOT NULL DEFAULT '0',
  `tampil_pg` int NOT NULL DEFAULT '0',
  `tampil_esai` int NOT NULL DEFAULT '0',
  `bobot_pg` int NOT NULL DEFAULT '0',
  `bobot_esai` int NOT NULL DEFAULT '0',
  `opsi` int NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` int NOT NULL DEFAULT '0',
  `soal_agama` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_tp` int NOT NULL,
  `id_smt` int NOT NULL,
  `deskripsi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `jml_kompleks` int NOT NULL DEFAULT '0',
  `tampil_kompleks` int NOT NULL DEFAULT '0',
  `bobot_kompleks` int NOT NULL DEFAULT '0',
  `jml_jodohkan` int NOT NULL DEFAULT '0',
  `tampil_jodohkan` int NOT NULL DEFAULT '0',
  `bobot_jodohkan` int NOT NULL DEFAULT '0',
  `jml_isian` int NOT NULL DEFAULT '0',
  `tampil_isian` int NOT NULL DEFAULT '0',
  `bobot_isian` int NOT NULL DEFAULT '0',
  `status_soal` int NOT NULL DEFAULT '0' COMMENT '0=belum selesai, 1=sudah selesai',
  PRIMARY KEY (`id_bank`) USING BTREE,
  UNIQUE KEY `kode_bank_soal` (`bank_kode`(100))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbt_bank_soal`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cbt_bank_soal` WRITE;
/*!40000 ALTER TABLE `cbt_bank_soal` DISABLE KEYS */;
INSERT INTO `cbt_bank_soal` VALUES
(1,0,'bing123','7','a:2:{i:0;a:1:{s:8:\"kelas_id\";s:1:\"1\";}i:1;a:1:{s:8:\"kelas_id\";N;}}',27,0,1,'',0,10,0,10,0,100,0,4,'2026-11-14 03:04:29',1,'-',5,1,NULL,0,0,0,0,0,0,0,0,0,1);
/*!40000 ALTER TABLE `cbt_bank_soal` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-15 11:14:54
