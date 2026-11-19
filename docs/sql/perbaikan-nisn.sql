-- Perbaikan NISN 11 digit.
--
-- Skema lama memakai INT(10) UNSIGNED ZEROFILL, sehingga NISN seperti
-- 45555555778 meluap menjadi 4294967295. Insert master_siswa lalu gagal dan
-- buku_induk ikut tersimpan dengan uid = NULL.
--
-- Jalankan sekali pada database yang sudah terpasang. Instalasi baru sudah
-- memakai VARCHAR(50) lewat assets/app/db/master.sql.

ALTER TABLE `master_siswa`
  MODIFY COLUMN `nisn` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
