-- Isi ulang password awal untuk data lama.
-- Trigger lama mengosongkan kolom password saat INSERT/UPDATE, sehingga
-- tombol mata admin tidak menampilkan apa pun untuk akun yang dibuat dulu.
--
-- Hanya baris yang hash login-nya terverifikasi dengan password '12341234'
-- yang diupdate. Baris lain sengaja dibiarkan kosong.
-- Jalankan sekali; aman dijalankan ulang.

UPDATE `master_siswa` SET `password` = '12341234' WHERE `password` = '' AND `id_siswa` IN (1,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42);
UPDATE `master_guru` SET `password` = '12341234' WHERE `password` = '' AND `id_guru` IN (1,10);
