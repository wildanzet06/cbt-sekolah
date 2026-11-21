# PRD — Perbaikan Keamanan Tinggi (Tindak Lanjut Audit 21 Nov 2026)

## 1. Konteks

Melanjutkan `PRD-PERBAIKAN-KEAMANAN-KRITIS.md`. Setelah temuan kritis selesai (commit `39f20c2`), berikutnya adalah temuan TINGGI yang bisa merusak data nilai, melemahkan proteksi upload, atau membocorkan kredensial.

## 2. Temuan yang diperbaiki pada kelompok ini

| No | File | Masalah |
|---|---|---|
| 1 | `application/controllers/Cbtnilai.php` | `simpanKoreksi()` memakai input POST sebagai **nama kolom UPDATE** → SQL injection; nilai tidak dibatasi 0–100; ID nilai gabungan bisa tabrakan. |
| 2 | `application/controllers/Guruview.php`, `Kelasmateri.php` | `deleteFile()` menerima path dari user dan bisa menghapus `uploads/.htaccess` (pelindung folder upload). |
| 3 | `application/controllers/Kelasmateri.php` | `saveMateri()` menentukan ekstensi file dari string MIME base64 → berpotensi menulis file `.php`. |
| 4 | `application/controllers/Usersiswa.php` + 4 view/JS | Kata sandi siswa/guru masih dikirim ke browser; `Usersiswa::update()` hanya `set_rules()` tanpa `run()`/update/redirect. |

Temuan XSS dan mutasi via GET akan masuk kelompok commit terpisah setelah kelompok ini aman.

## 3. Tujuan

- Tidak ada input pengguna yang dipakai sebagai identifier SQL.
- File yang dihapus hanya boleh berada di dalam subfolder upload yang diizinkan, bukan dotfile.
- Upload gambar inline hanya menghasilkan jpg/jpeg/png/gif.
- Kata sandi tidak lagi dikirim ke DOM; form ubah siswa benar-benar menyimpan perubahan.

## 4. Ruang lingkup

**Diubah:** controller yang disebut di atas, view `users/siswa/edit.php`, `users/guru/edit.php`, `users/siswa/data.php`, `assets/app/js/users/guru/data.js`.

**Tidak diubah:** SPP/pembayaran, tampilan GarudaCBT, sidebar navy Data Siswa/Data Guru, struktur database, hook, dan view lainnya.

## 5. Pengujian

- `php -l` dan `node --check` untuk file terkait.
- Uji SQLi: kirim `jenis=pg_nilai=1 -- ` → harus ditolak/diabaikan, tidak mengubah baris lain.
- Uji hapus file: kirim `src=uploads/.htaccess` → harus ditolak; file tetap ada.
- Uji upload base64 `data:x/php;base64,...` → harus ditolak.
- Uji form ubah siswa: submit perubahan → data tersimpan dan ada redirect/flashdata.
- Pastikan tidak ada error konsol baru dan tampilan tidak berubah.

## 6. Rollback

Kembalikan file terkait ke commit sebelum kelompok ini bila ada regresi.
