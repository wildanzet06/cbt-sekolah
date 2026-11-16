# Audit Bug & Celah Keamanan — CBT Sekolah

Tanggal: 15 November 2026
Lingkup: seluruh aplikasi (230 view, 80 controller/model, konfigurasi server, database)
Metode: pembacaan kode (controller/model/view ter-obfuscate di-decode lebih dulu),
pemindaian pola otomatis, dan uji HTTP langsung dengan 3 peran (admin, guru, siswa).

---

## 1. Ringkasan

| Tingkat | Jumlah | Sudah diperbaiki | Masih terbuka |
|---|---|---|---|
| Kritis | 3 | 3 | 0 |
| Tinggi | 4 | 2 | 2 |
| Sedang | 5 | 3 | 2 |
| Rendah | 3 | 0 | 3 |
| Bug fungsional | 3 | 2 | 1 |

Aplikasi **tidak rentan SQL injection** pada jalur input pengguna: query memakai
query builder / placeholder `?` (dari 80 file controller+model hanya ada 3 query
dengan interpolasi, semuanya memakai nilai internal: `date()`, nama tabel dari
kode, bukan dari request).

---

## 2. Kritis

### K1. Folder `.git/` dapat diunduh publik — **DIPERBAIKI**
`http://host/.git/HEAD`, `.git/config`, `.git/index` (270 KB), `.git/logs/HEAD`
semua merespons 200. Artinya seluruh riwayat kode (termasuk kredensial yang
pernah ter-commit) bisa diambil siapa pun.

Perbaikan: `.htaccess` root memblokir `\.git`, `.svn`, `.hg`, `.env`, `.idea`, dll.
Verifikasi: seluruh `.git/*` sekarang **403**.

### K2. Alat instalasi/updater/pembanding DB terbuka tanpa login — **DIPERBAIKI**
`Install.php`, `Update.php`, `Compare.php` tidak punya cek login. `Compare`
menjalankan `DROP`/`CREATE`/`ALTER TABLE` untuk menyamakan dua database;
`Update` mengubah struktur tabel dari `assets/app/db/database.json`; `Install`
menjalankan instalasi ulang.

Perbaikan:
- `application/config/routes.php`: untuk request dari luar komputer server,
  route `install/*`, `update/*`, `compare/*` diarahkan ke controller `Blocked`
  (404).
- `installer/.htaccess`: hanya boleh diakses dari `127.0.0.1`/`::1`.

### K3. Skema & cadangan database dapat diunduh — **DIPERBAIKI**
`docs/database_gabungan.sql` (kini satu-satunya file migrasi),
`assets/app/db/master.sql`, dan
`docs/backup/sebelum-pembagian-peserta.sql` (berisi NIS/nama siswa) merespons 200.

Perbaikan: `docs/.htaccess` menolak semua; `.htaccess` root memblokir ekstensi
`sql|md|log|lock|ini|bak|old|orig|sh|bat|ps1|dist`. Verifikasi: **403**.

---

## 3. Tinggi

### T1. Mode `development` selalu aktif → bocor query SQL & path file — **DIPERBAIKI**
`index.php` menentukan `ENVIRONMENT` dari `$envs[0]` = `development`, sehingga
`display_errors=1`. Inilah sebab halaman error menampilkan query SQL lengkap dan
path `C:/laragon/www/...` (seperti pada halaman Pelanggaran sebelumnya).

Perbaikan: default sekarang `production`; `development` hanya otomatis untuk
host lokal (`localhost`, `127.0.0.1`, `*.test`, `*.local`) atau bila `CI_ENV`
diisi. Dampak: di server asli error tidak lagi tampil ke pengguna (tetap masuk
log di `application/logs`).

### T2. XSS tersimpan pada komentar/balasan pengumuman — **DIPERBAIKI**
`assets/app/js/dashboard.js` menyisipkan `v.text` dan nama penulis ke HTML tanpa
escape (`append(comm)`). Siswa dapat menulis komentar berisi
`<img src=x onerror=...>`; skrip berjalan di dashboard admin/guru (sesi admin).

Perbaikan: helper `esc()` dan dipakai untuk `v.text` (komentar & balasan) dan
nama penulis. Isi pengumuman (ditulis admin lewat editor) sengaja tetap HTML.

### T3. Password siswa & guru tersimpan **plaintext** dan ditampilkan di form — **TERBUKA**
- `master_siswa.password` dan `master_guru.password` berisi teks asli
  (contoh: `12341234`), berbeda dengan tabel `users` yang sudah bcrypt.
- Form admin menampilkan password tersebut apa adanya:
  `views/master/siswa/edit.php:69`, `:374`, `:428`;
  `views/master/guru/edit.php:81`;
  `views/master/guru/profile.php:249`;
  `views/members/guru/wali/kelas.php:55` (kolom `U: … P: …`).

Dampak: siapa pun yang bisa membaca database/backup (atau memakai SQLi) langsung
memperoleh password semua siswa/guru; password juga terlihat di layar.

Rekomendasi: migrasi bertahap —
1. Saat login/aktivasi, tulis `password_hash()` ke `master_siswa`/`master_guru`
   dan pakai `password_verify()` dengan fallback plaintext (auto-upgrade).
2. Hapus nilai password dari form (ganti dengan tombol **Reset password**).
3. Hapus tampilan `U: … P: …` di halaman wali kelas.

### T4. IDOR pada endpoint ujian sisi siswa — **TERBUKA**
`Siswa::saveJawaban`, `Siswa::loadNomorSoal`, `Siswa::getTimer`,
`Siswa::checkTimer` menerima `id_siswa` (dan `id_soal_siswa`) dari POST tanpa
mencocokkan dengan siswa yang sedang login. Pola `id_soal_siswa` =
`id_siswa + '0' + id_jadwal + id_bank + jenis + nomor` mudah ditebak, sehingga
siswa yang login berpotensi mengubah jawaban/membaca soal siswa lain.

Rekomendasi: di awal tiap method tersebut, ambil siswa dari sesi
(`getDataSiswa($user->username, …)`) dan tolak (403) bila `id_siswa`/`id_soal_siswa`
tidak berawalan miliknya. Perubahan menyentuh `Siswa.php` (obfuscated) → perlu
dilakukan bersama uji alur ujian penuh (mulai → jawab → selesai → nilai).

---

## 4. Sedang

### S1. Folder `uploads/` tanpa proteksi — **DIPERBAIKI**
Daftar file bisa dibuka publik dan skrip di dalamnya berpotensi dieksekusi.
Perbaikan `uploads/.htaccess`: `Options -Indexes`, tolak ekstensi
`php|phtml|phar|pl|py|cgi|sh|asp|jsp|html|svg|xml`, dan `php_flag engine off`.
Verifikasi: `uploads/` → **403**.

### S2. Cookie sesi tanpa `HttpOnly` dan `Secure` — **DIPERBAIKI**
`cookie_httponly = FALSE` (cookie sesi bisa dibaca JavaScript) dan
`cookie_secure = FALSE`.
Perbaikan: `cookie_httponly = TRUE`; `cookie_secure` otomatis `TRUE` bila
request lewat HTTPS (tanpa memutus akses HTTP lokal). Verifikasi: cookie
`ci_session` sekarang `HttpOnly`.

### S3. Sekitar 5.300 echo tanpa escaping — **SEBAGIAN (bertahap)**
Dari 5.324 `<?=` di view, hanya 246 memakai `html_escape()`. Sebagian besar
aman (label statis/angka), tetapi data yang bisa diisi pengguna ber-Hak rendah
(nama siswa, nama guru, judul materi, dsb.) berpotensi XSS tersimpan.
Prioritas: nama & judul pada tabel daftar (DataTables), halaman cetak, dan
halaman yang dibuka admin.

### S4. Tiga endpoint dikecualikan dari CSRF — **TERBUKA**
`kelasmateri/saveMateri`, `kelasmateri/uploadfile`, `kelasmateri/deletefile`
dikecualikan di `config.php`, padahal dipanggil dari dalam aplikasi (bukan
callback pihak ketiga). Hanya `pembayaran/midtrans_notification` yang memang
perlu dikecualikan.
Rekomendasi: hapus 3 URI itu dari `csrf_exclude_uris`; template admin/guru sudah
menyisipkan token lewat `ajaxcsrf()`.

### S5. `encryption_key` tertulis di repo publik — **TERBUKA**
`application/config/config.php` berisi `encryption_key` statis dan repo
`github.com/wildanzet06/cbt-sekolah` publik. Siapa pun yang meng-clone tahu kunci
tersebut (dipakai library Encryption).
Rekomendasi: pindahkan ke variabel lingkungan/`config` di luar repo dan ganti
nilainya (perhatikan data terenkripsi lama bila ada).

---

## 5. Rendah

| # | Temuan | Saran |
|---|---|---|
| R1 | `sess_expiration = 7200*12` (24 jam), komentar menulis "1 jam" | turunkan ke 4–8 jam untuk akun admin |
| R2 | `csrf_regenerate = FALSE` (token tetap per sesi) | aman untuk mencegah CSRF lintas situs; regenerasi akan memutus AJAX yang memakai token saat halaman dimuat — biarkan atau sesuaikan JS lebih dulu |
| R3 | Proteksi login sudah baik: bcrypt, `track_login_attempts`, `lockout_time=600` | tidak ada aksi |

---

## 6. Bug fungsional

| # | Bug | Status |
|---|---|---|
| B1 | 9 tabel tambahan hilang → halaman Pelanggaran/Presensi/Tagihan error 1146 | **DIPERBAIKI**: skema dibuat ulang + `Pelanggaran_model` kini membuat tabelnya sendiri bila hilang |
| B2 | `Undefined variable: $gurus` di halaman Jadwal (guru) | **DIPERBAIKI** |
| B3 | `Invalid argument supplied for foreach()` di `Dbmanager.php:7` | terbuka (controller obfuscated; hanya notice) |
| B4 | `Dbclear::index()` (menu Pengaturan → Database → Bersihkan Data) **menghapus tabel** yang tidak ada di `assets/app/db/database.json` hanya dengan membuka halamannya — inilah penyebab tabel keuangan/presensi hilang 15 Nov | **DIPERBAIKI**: DROP otomatis dimatikan, tabel tambahan aplikasi dimasukkan ke daftar lindung |

---

## 7. Yang diuji dan hasilnya

- **Halaman**: 43 halaman admin + 13 halaman guru + 7 form edit + halaman siswa
  (`/akun/pelanggaran`, `/akun/kehadiran`) → 200, tanpa notice/error baru.
- **Injeksi SQL**: query builder/placeholder dipakai konsisten; 3 interpolasi
  memakai nilai internal (`date()`, nama tabel dari kode).
- **XSS refleksi**: tidak ada view yang mencetak `$_GET/$_POST` langsung.
- **XSS tersimpan**: 1 jalur nyata (komentar) sudah ditutup; sisanya bertahap (S3).
- **CSRF**: proteksi aktif untuk semua POST kecuali 3 URI (S4).
- **Upload**: tipe file dibatasi `jpeg|jpg|png|gif|mp3|mp4|doc|docx|xls|xlsx|ppt|pdf|zip`,
  tidak ada PHP; folder upload sekarang juga menolak eksekusi skrip.
- **Akses berkas sensitif**: `.git`, `docs/`, `*.sql`, `composer.*`, `options.json`,
  `README`, daftar `uploads/` → **403**.
- **Header keamanan**: `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.
- **Alat internal** (`install`, `update`, `compare`): hanya dari komputer server.

---

## 8. Rekomendasi lanjutan (urut prioritas)

1. Migrasi password `master_siswa`/`master_guru` ke bcrypt + hapus tampilan password (T3).
2. Kunci `id_siswa`/`id_soal_siswa` pada endpoint ujian ke sesi siswa (T4).
3. Escape bertahap pada view yang menampilkan data masukan pengguna (S3).
4. Hapus 3 URI dari pengecualian CSRF (S4).
5. Rotasi `encryption_key` dan simpan di luar repo (S5).
6. Rutin backup database (menu Pengaturan → Database → Backup) dan simpan di luar webroot.
