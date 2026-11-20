# PRD — CBT Final Hasil Audit

## 1. Tujuan

Menyiapkan paket CBT final yang aman untuk dipasang di sekolah, tanpa menghilangkan fitur bawaan, tanpa mengubah tampilan yang sudah baik, dan tanpa mengubah struktur database secara sembarangan.

Baseline yang dipakai adalah **repo kerja `cbt-sekolah`**, bukan `cbt-master.zip` yang terakhir diaudit. Alasannya: repo kerja masih memiliki modul SPP dan proteksi yang sudah diperbaiki sebelumnya, sementara ZIP terakhir kehilangan keduanya.

## 2. Prinsip perubahan

- Fitur bawaan tetap dipertahankan, termasuk SPP/multipayment.
- Tampilan GarudaCBT dan sidebar navy Data Siswa/Data Guru tidak diubah tanpa alasan.
- Struktur database tidak diubah, kecuali patch NISN yang sudah disepakati dan sudah tersedia terpisah.
- Setiap perbaikan menunjuk ke file dan baris yang jelas.
- Setelah perubahan, dilakukan pengujian ulang agar tidak muncul bug baru.
- Kunci rahasia tidak masuk repo atau ZIP; hanya file `.example` yang ikut.
- Midtrans produksi ditunda sampai kredensial produksi tersedia.

## 3. Ringkasan audit ZIP vs repo kerja

Pemeriksaan terhadap `C:\Users\Admin\Downloads\Compressed\cbt-master.zip` menunjukkan temuan pertama masih benar untuk ZIP tersebut: `db_size.php` rusak, `.htaccess` hanya berisi rewrite, upload `0777`, cookie `FALSE`, CSRF dikecualikan untuk 3 URI `kelasmateri`, environment bawaan `development`, password ditampilkan, dan tidak ada SPP.

Namun temuan tersebut **tidak berlaku lagi di repo kerja**. Berikut ringkasannya:

| Temuan ZIP | Kondisi repo kerja | Status |
| --- | --- | --- |
| `application/helpers/db_size.php` rusak | Sudah memakai `$CI =& get_instance();` dan `$CI->db->escape(...)` | Selesai |
| `.htaccess` tanpa proteksi | Sudah ada blokir dotfile, `composer.json`, `composer.lock`, SQL, docs, dan `Options -Indexes` | Selesai |
| Upload folder `0777` dan bisa mengeksekusi PHP | `MY_Upload.php` sudah `0755`; `uploads/.htaccess` sudah menolak skrip dan directory listing | Selesai |
| Cookie sesi tidak aman | `cookie_secure` mengikuti HTTPS, `cookie_httponly = TRUE` | Selesai |
| CSRF dikecualikan untuk `kelasmateri/*` | Hanya `pembayaran/midtrans_notification` yang dikecualikan | Selesai |
| Environment bawaan `development` | `index.php` sudah memakai `production` sebagai bawaan | Selesai |
| Password ditampilkan di View | Form edit siswa/guru dan halaman wali kelas sudah tidak menampilkan password | Selesai |
| Modul SPP tidak ada | Controller, model, dan alur pembayaran SPP ada | Selesai |
| Kunci enkripsi/QR hardcoded | Kunci dibaca dari environment atau `encryption.local.php` yang di-ignore | Selesai |
| `404_override` ke halaman kustom | Dikembalikan ke 404 bawaan CI agar status HTTP benar-benar 404 | Selesai |
| Lockout login bisa menyasar akun orang lain | `track_login_ip_address` diaktifkan | Selesai |
| `sess_regenerate_destroy = FALSE` | Masih perlu diubah menjadi `TRUE` | Perubahan terakhir |

Catatan jumlah file: laporan menyebut 2.191 file, sedangkan penghitungan entri ZIP aktual 2.555. Jumlah PHP 487 dan JS 760 cocok. Perbedaan total kemungkinan karena metode hitung yang berbeda; tidak mengubah kesimpulan audit.

## 4. Perubahan yang masih dilakukan

### 4.1 Perketat regenerasi ID sesi

- **File:** `application/config/config.php`
- **Baris:** 139
- **Sebelum:** `$config['sess_regenerate_destroy'] = FALSE;`
- **Sesudah:** `$config['sess_regenerate_destroy'] = TRUE;`

**Alasan:** saat CodeIgniter memperbarui ID sesi secara otomatis, sesi lama ikut dihapus. Ini mengurangi risiko session fixation.

**Dampak yang diharapkan:** pengguna tetap login normal; sesi lama tidak lagi bisa dipakai ulang setelah ID sesi diperbarui.

**Penjelasan teknis singkat:** `system/libraries/Session/Session.php:150` memakai setelan ini ketika auto-regenerasi sesi. Setelan ini tidak mengubah alur login ion_auth yang sudah ada.

## 5. Risiko yang sengaja tidak diubah

### Password siswa dan guru masih tersimpan plaintext

- Kolom `master_siswa.password` bertipe `mediumtext`.
- Kolom `master_guru.password` bertipe `longtext`.
- Kolom tersebut masih dipakai alur aktivasi/register ion_auth.

Mengubahnya menjadi hash memerlukan perubahan skema dan alur aktivasi/register. Sesuai prinsip “jangan mengubah struktur database sembarangan”, migrasi ini **tidak dilakukan** dalam paket final ini. Yang sudah dilakukan adalah menghilangkan tampilan password dari halaman admin dan wali kelas.

Jika later ingin migrasi password, perlu PRD dan pengujian terpisah.

### Midtrans produksi

Menunggu kredensial produksi. Konfigurasi yang ada sudah memisahkan file rahasia dan contoh, tetapi tidak diisi dengan kunci produksi.

## 6. Kriteria uji

### Statis

- `php -l` lolos untuk seluruh file PHP yang berubah.
- `node --check assets/app/js/tableToExcel.js` lolos.
- Tidak ada literal kunci enkripsi lama di berkas terlacak.
- Tidak ada `encryption.local.php` atau file rahasia lain di ZIP final.
- Composer valid dan tidak ada advisori keamanan.

### Runtime ringan pada tab browser yang sudah ada

- `/dashboard`, `/datasiswa`, `/dataguru`, `/cbtnilai`: tidak ada error konsol.
- URL yang tidak ada: menampilkan halaman 404 dengan status HTTP 404.
- `/cekkwitansi/2/{kode}`: kunci DB, kunci saat ini, dan kunci lama tetap sah; kode acak ditolak.

### Alur CBT penuh

Belum bisa diuji tanpa akun uji/sesi login yang tersedia. Item berikut tetap menjadi catatan pengujian yang perlu dilakukan sekolah sebelum pemakaian luas:

- token ujian;
- sesi dan ruang ujian;
- pengerjaan soal, timer, dan autosave;
- koreksi dan penilaian;
- rekap nilai dan rapor;
- import/export.

## 7. Pengemasan final

ZIP final dibuat dari repo kerja dengan pengecualian:

- `.git/`
- `vendor/`
- `application/config/encryption.local.php`
- `application/config/midtrans.php`
- log aplikasi
- cadangan/backup
- isi `uploads/` kecuali `index.html` dan `.htaccess`

Nama paket yang direncanakan: `C:\Users\Admin\Downloads\Compressed\cbt-sekolah-final-audit.zip`.

## 8. Hasil verifikasi

### Statis

- `php -l` lolos untuk `application/config/config.php` dan `application/helpers/db_size.php`.
- `node --check assets/app/js/tableToExcel.js` lolos.
- `composer validate --no-check-publish` valid.
- `composer check-platform-reqs` sukses di PHP 8.3.35.
- `composer audit --locked`: tidak ada advisori keamanan.
- Tidak ada `encryption.local.php`, `midtrans.php`, atau `vendor/` di berkas terlacak.
- Kedua kunci lama tidak ditemukan di berkas terlacak.

### Browser pada tab yang sudah ada

- `/cekkwitansi/2/0000000000000000`: ditolak dengan pesan “Kwitansi Tidak Valid”, 0 error konsol.
- Kunci DB, kunci saat ini, dan kunci lama: semuanya diterima sebagai “Kwitansi Asli”, 0 error konsol.
- URL tidak ada: status HTTP 404 dan halaman “404 Page Not Found”, tanpa error konsol selain pesan 404 yang memang diharapkan.
- `/dashboard`, `/datasiswa`, `/dataguru`, dan `/cbtnilai`: mengarah ke halaman Login karena sesi uji sudah tidak tersedia. Tidak ada error konsol, tetapi halaman login tidak bisa dipakai sebagai bukti bahwa alur internal bebas bug.

### Keterbatasan

Pengujian alur CBT penuh masih memerlukan akun uji/sesi login. Item token, ruang ujian, pengerjaan soal, timer/autosave, koreksi, rekap, rapor, dan import/export belum bisa dibuktikan secara runtime.

### Paket final

ZIP final dibuat dari `HEAD` dengan `git archive`, sehingga hanya berkas terlacak yang ikut.

- **Lokasi:** `C:\Users\Admin\Downloads\Compressed\cbt-sekolah-final-audit.zip`
- **Integritas:** `unzip -t` lolos.
- **Isi:** 2.688 entri, 608 file PHP, 761 file JavaScript.
- **Tidak ikut:** `.git/`, `vendor/`, `encryption.local.php`, `midtrans.php`, `application/logs/log-*.php`, dan isi `backups/` kecuali `.htaccess` serta `index.html`.
- **Masih ikut:** `composer.json`, `composer.lock`, kedua file `.example`, `uploads/.htaccess`, template import, dokumentasi, dan patch NISN.

Selisih 9 file PHP antara folder lokal dan ZIP adalah 1 file kunci lokal serta 8 log aplikasi; semuanya memang sengaja tidak ikut.

## 9. Status penyelesaian

- [x] Audit ulang ZIP dan perbandingan dengan repo kerja.
- [x] Penetapan baseline repo kerja.
- [x] PRD ini.
- [x] Perubahan `sess_regenerate_destroy`.
- [x] Verifikasi ulang statis dan browser sejauh sesi tersedia.
- [x] Commit dan push per kelompok.
- [x] Pembuatan serta pemeriksaan isi ZIP final.

## 10. Tambahan setelah paket final awal

Setelah paket final awal dibuat, ditemukan masalah login siswa: 41 dari 42 akun siswa punya password login kosong walaupun password di Data Siswa sudah terisi.

Perbaikan dan dokumentasinya ada di:

- `docs/PRD-PERBAIKAN-PASSWORD-SISWA.md`
- `tools/perbaiki-password-siswa.php`

Data lokal sudah diperbaiki setelah backup dibuat. ZIP final perlu digenerate ulang agar menyertakan dua berkas tersebut.

## 11. Tambahan: aktivasi siswa baru

Agar masalah password kosong tidak terulang saat ada murid baru, alur **User Management → Data User Siswa → Aktifkan** kini menolak akun yang username atau password Data Siswanya masih kosong, dan pesan sukses/gagal mengikuti hasil registrasi yang sebenarnya.

Dokumentasi dan pengujian mandirinya:

- `docs/PRD-AKTIVASI-SISWA.md`
- `tools/test-aktivasi-siswa.php`

Catatan penting untuk sekolah: menambah murid di **Data Siswa** saja belum membuat murid bisa login. Murid tetap harus diaktifkan di **Data User Siswa**, dengan username dan password Data Siswa sudah terisi.
