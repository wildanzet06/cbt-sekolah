# Audit Keamanan & Bug — CBT Sekolah

**Tanggal audit:** 15–16 November 2026
**Lingkup:** seluruh aplikasi (`application/controllers`, `models`, `views`, `config`,
`helpers`, `hooks`, folder `installer`, aset JS/CSS, konfigurasi Apache/.htaccess)
**Metode:** pembacaan kode (controller/model/view), pemetaan hak akses per peran,
uji akses langsung dengan akun admin/guru/siswa, uji IDOR & path traversal,
pemindaian pola SQLi/XSS, pemeriksaan konfigurasi server, serta uji regresi halaman.

Status: **Diperbaiki** = sudah ditutup di kode. **Terbuka** = belum diperbaiki
(butuh keputusan/perubahan alur), dengan rekomendasi.

---

## 1. Ringkasan temuan

| ID | Temuan | Risiko | Status |
|----|--------|--------|--------|
| K1 | Halaman **Bersihkan Data** (`Dbclear::index`) menghapus tabel begitu halaman dibuka (tanpa konfirmasi) | **Kritis** | Diperbaiki |
| K2 | **Installer** terbuka bagi siapa pun yang bisa menjangkau foldernya; POST bisa menimpa `application/config/database.php` (pengambilalihan aplikasi) | **Kritis** | Diperbaiki |
| K3 | Route `compare` bisa **menghapus tabel** yang tidak ada di `database.json` | **Kritis** | Diperbaiki (ditutup total) |
| T1 | Guru bisa membuka `datasiswa`: tambah/ubah/hapus siswa, ubah username & password siswa | **Tinggi** | Diperbaiki |
| T2 | Guru bisa memanggil aksi admin `bukurapor`: `editnilairapor`, `backupnilai`, `restorenilai` | **Tinggi** | Diperbaiki |
| T3 | IDOR ujian: siswa bisa mengirim jawaban / membaca timer **atas nama siswa lain** (`savejawaban`, `gettimer`, dsb.) | **Tinggi** | Diperbaiki |
| T4 | Path traversal pada hapus file (`unlink($_POST['src'])`) di beberapa controller + `hapusBackup($src)` | **Tinggi** | Diperbaiki |
| T5 | Endpoint `kelasmateri` (upload/hapus/simpan materi) dikecualikan dari CSRF | **Sedang** | Diperbaiki |
| T7 | `Usersiswa` tanpa cek admin: guru bisa `edit`/`update` akun siswa (ganti username & password) | **Tinggi** | Diperbaiki |
| T8 | Banyak aksi pengubah data bisa dipicu lewat **GET** (`pembayaran/verifikasi` berupa tautan, hapus posting, reset login, hapus bank soal, dll.) → CSRF lewat `<img>`/tautan | **Tinggi** | Diperbaiki |
| T9 | `assets/app/db/database.json` (skema 112 KB) dan `installer/includes/*` bisa diakses langsung lewat web | **Rendah** | Diperbaiki |
| T6 | Tabel tambahan hilang → semua halaman keuangan error `Table ... doesn't exist` | **Tinggi** | Diperbaiki (self-healing) |
| S1 | Password siswa/guru tersimpan **plaintext** di `master_siswa`/`master_guru` dan ditampilkan di form | **Tinggi** | Terbuka |
| S2 | ~5.000 keluaran `<?=` tanpa `html_escape()` di view (XSS) | **Sedang** | Sebagian |
| S3 | `encryption_key` statis di dalam repositori publik | **Sedang** | Terbuka |
| S4 | IDOR `Siswa::bukaMateri/bukaTugas`: materi/tugas kelas lain bisa dibuka lewat ID | **Rendah** | Terbuka |
| S5 | Integritas ujian: jawaban masih bisa dikirim setelah waktu habis (timer di sisi klien) | **Sedang** | Terbuka |
| S6 | Route `update` masih bisa diakses dari komputer server (menjalankan SQL migrasi) | **Rendah** | Terbuka |
| B1 | Notice/warning PHP di beberapa halaman (materi, materi jadwal, presensi, backup) | **Rendah** | Diperbaiki |
| B2 | Halaman siswa dibuka admin → 500 "Data siswa tidak ditemukan" | **Rendah** | Diperbaiki |
| B3 | Akses ditolak mengembalikan status 500 (bukan 403) di Pembayaran/Tagihan | **Rendah** | Diperbaiki |

---

## 2. Detail temuan kritis

### K1. Halaman "Bersihkan Data" menghapus tabel saat dibuka

`Dbclear::index()` menjalankan, untuk **setiap** tabel yang tidak terdaftar di
`assets/app/db/database.json` dan tidak ada di daftar `$excludes`:

```php
$this->dbforge->drop_table($table, true);   // DROP TABLE
```

Artinya sekadar **membuka** menu *Pengaturan → Database → Bersihkan Data* sudah
menghapus semua tabel tambahan (keuangan, tagihan, presensi). Bukti dari log
Apache: `GET /dbclear` pada 15/11 15:29:17 dan 17:31:12; tabel hilang tepat
setelahnya dan aplikasi membuat ulang tabel yang punya *auto-create* pada
17:31:46–17:49.

**Perbaikan:** dua pemanggilan `drop_table()` di `index()` dihapus; 13 tabel
tambahan dimasukkan ke daftar lindung sehingga tidak muncul di daftar hapus.
Tombol hapus per tabel (yang punya konfirmasi + backup otomatis) tetap ada.

### K2. Installer terbuka

`installer/install.php` menerima POST berisi host/user/password/nama database,
membuat database, mengimpor `master.sql`, lalu **menulis ulang**
`application/config/database.php`. Folder ini hanya dibatasi `.htaccess`
(Require ip 127.0.0.1). Bila `AllowOverride` dimatikan — atau lewat CSRF dari
browser di komputer yang sama — siapa pun bisa mengarahkan aplikasi ke database
miliknya sendiri (termasuk membuat akun admin baru) ⇒ pengambilalihan total.

**Perbaikan:** berkas baru `installer/includes/cek_terpasang.php` yang memeriksa
apakah tabel `users` sudah ada. Bila aplikasi sudah terpasang → **HTTP 403**
("Installer dinonaktifkan"). Diuji: GET/POST ke `installer/` dan
`installer/install.php` → 403, `database.php` tidak berubah.

### K3. Route `compare`

`Compare` membandingkan database dengan `database.json` dan menjalankan
`drop_table` untuk tabel yang tidak dikenal — sumber risiko yang sama dengan K1.

**Perbaikan:** route `compare` (dan turunannya) selalu diarahkan ke `blocked`
(404), termasuk dari localhost.

---

## 3. Detail temuan otorisasi

### T1. Guru bisa mengelola data siswa

`Datasiswa::__construct` mengizinkan **admin atau guru**:

```php
if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group("guru")) { show_error(...); }
```

Padahal menu "Siswa" hanya ada di sidebar admin, dan seluruh method
(`create`, `updateData`, `delete`, `editLogin`, `do_import`, `update_foto`, …)
tanpa cek tambahan. Guru dapat menghapus siswa atau mengganti password login
siswa (pengambilalihan akun siswa).

**Perbaikan (Akses_hook):** `datasiswa` dan `bukuinduk` masuk `$khusus_admin_semua`
(seluruh method hanya admin). Diuji: guru → **403** di `datasiswa`,
`datasiswa/edit/1`, `bukuinduk`; admin tetap normal.

### T2. Guru bisa mengedit/memulihkan nilai rapor

`Bukurapor` mengizinkan guru (menu "Arsip Rapor" memang milik guru), tetapi
`editNilaiRapor`, `backupNilai`, dan `restoreNilai` adalah aksi admin dan tidak
memeriksa peran. Guru dapat mengubah nilai siswa atau menimpa nilai dengan backup.

**Perbaikan (Akses_hook):** `bukurapor` → `editnilairapor`, `backupnilai`,
`restorenilai` hanya admin. Halaman arsip (`index`) tetap bisa dibuka guru.

### T3. IDOR pada ujian siswa

`Siswa::saveJawaban()` mengambil `siswa` (id_siswa) dari **POST** dan langsung
menyimpannya; `getTimer($id_siswa, $id_jadwal)` mengambil dari **URI**. Tidak ada
pemeriksaan bahwa id tersebut milik siswa yang login ⇒ siswa dapat mengisi
jawaban siswa lain atau membaca timer siswa lain.

**Perbaikan (Akses_hook):** untuk peran siswa pada method ujian
(`savejawaban`, `loadnomorsoal`, `gettimer`, `checktimer`, `resettimer`,
`selesaiujian`, `ulangiujian`, `validasisiswa`, `soalsiswa`, `saveujian`,
`savelogujian`), `id_siswa` dari POST/URI dibandingkan dengan `id_siswa` milik
akun yang login (dipetakan lewat `master_siswa.username`). Bila berbeda → 403.
Diuji: kirim `siswa=999` → **403**; `getTimer/999/1` → **403**; data sendiri tetap
bisa dikirim.

### T4. Path traversal pada hapus file

Beberapa controller menghapus file dengan pola:

```php
$src = $this->input->post('src');
$file_name = str_replace(base_url(), '', $src);
unlink($file_name);
```

`$src = ../../index.php` dapat menghapus berkas di luar folder uploads.
`Dbmanager::hapusBackup($src)` juga `unlink("./backups/" . $src)`.

**Perbaikan (Akses_hook):** pada method `deletefile`/`delete_file`/`hapusfile`/
`hapusbackup`, nilai `src` (POST) dan segmen URI ditolak bila mengandung `..`,
path absolut Windows (`C:\`), atau UNC (`\\server`); nama backup wajib cocok
`^[A-Za-z0-9._-]+$`. Diuji: `src=../../index.php` → **403**, `index.php` utuh.

### T5. CSRF pada endpoint kelasmateri

`csrf_exclude_uris` memuat `kelasmateri/saveMateri`, `uploadfile`, `deletefile`.
Ketiganya sebenarnya **sudah mengirim token** (form memakai `form_open()`, dan
upload summernote menambahkan token secara manual), sehingga pengecualian itu
tidak perlu dan membuka celah CSRF (guru yang sedang login bisa dipaksa
mengunggah/menghapus file dari situs lain).

**Perbaikan:** pengecualian dihapus; hanya `pembayaran/midtrans_notification`
(notifikasi server Midtrans) yang tersisa. Diuji: POST tanpa token → **403**,
dengan token → diteruskan.

### T7. Guru bisa mengubah akun siswa (Usersiswa)

`Usersiswa::__construct` hanya memeriksa `logged_in()` — tidak ada cek admin.
Method `edit($id)`, `update()` (mengganti `username` dan password siswa
berdasarkan `id_siswa` dari POST), dan `delete()` dapat dipanggil guru mana pun.
Method bantuan `is_has_access()` juga rusak (`!$group === "admin"` selalu
bernilai salah) sehingga tidak pernah melindungi apa pun.

**Perbaikan (Akses_hook):** `usersiswa` masuk `$khusus_admin_semua` (semua
method hanya admin), dengan pengecualian `usersiswa/change_password` yang tetap
boleh untuk semua pengguna karena hanya mengubah password **milik sendiri**
(nilai `identity` diambil dari sesi, bukan dari input).

Diuji: guru → 403 pada `usersiswa`, `usersiswa/edit/1`, dan POST
`usersiswa/update`; siswa tetap bisa `change_password`; admin normal.

### T8. Aksi pengubah data lewat GET (CSRF)

Banyak endpoint mengubah data tetapi tidak memeriksa metode HTTP, dan UI-nya
memanggil dengan GET:

```
GET pembayaran/verifikasi/{id}      (tautan <a href> — admin memverifikasi pembayaran)
GET pengumuman/hapuspost/{id}       (AJAX type:"GET")
GET cbtbanksoal/deleteBank?id_bank= (AJAX type:"GET")
GET usersiswa/reset_login/{user}    (AJAX type:"GET")
GET datamapel/aktifkan/{id}, cbtrekap/backupnilai/{id}, dbmanager/truncate, ...
```

GET tidak dilindungi token CSRF, sehingga halaman lain cukup memuat
`<img src="http://localhost/cbt-sekolah/pembayaran/verifikasi/12">` untuk
memaksa admin/guru yang sedang login melakukan aksi tersebut.

**Perbaikan:**
1. `Akses_hook` aturan 7: method mutasi (`delete`, `hapus*`, `activate`,
   `reset_login`, `truncate`, `backupnilai`, `verifikasi`, `aktifkan`, …) hanya
   boleh lewat **POST** atau **AJAX** (header `X-Requested-With`). Permintaan
   GET biasa (tautan/gambar) → 403.
2. Tombol **Verifikasi** pembayaran diubah dari `<a href>` menjadi form POST
   (otomatis membawa token CSRF).

Diuji: GET `/pembayaran/verifikasi/x` → 403; AJAX GET tetap 200 (fitur lama
tidak rusak); POST ber-token → 303 dan pembayaran terverifikasi.

### T9. Berkas internal terbuka lewat web

`assets/app/db/database.json` (skema lengkap, 112 KB) dan
`installer/includes/taskCoreClass.php` dapat diunduh langsung.

**Perbaikan:** `assets/app/db/.htaccess` dan `installer/includes/.htaccess`
menolak semua akses (403); aplikasi tetap membacanya lewat filesystem.

---

## 4. Ketersediaan data (bukan keamanan, tapi berdampak besar)

### T6. Tabel tambahan hilang → semua halaman keuangan error

Delapan tabel (`cbt_pembayaran_spp`, `cbt_setting_spp`, `cbt_jenis_tagihan`,
`cbt_tagihan`, `cbt_keringanan`, `cbt_password_diganti`, `cbt_presensi`,
`cbt_presensi_sesi`) hilang pada 15/11 dan 16/11 sehingga halaman keuangan
menampilkan `Table 'cbt.xxx' doesn't exist`.

**Perbaikan:** helper `application/helpers/skema_helper.php` dengan
`skema_tambahan_pastikan($db)`, dipanggil dari constructor `Pembayaran_model`,
`Tagihan_model`, `Presensi_model`. Satu query pengecekan; bila ada yang kurang,
tabel dibuat ulang (idempoten), kolom versi lama dilengkapi, enum
`metode_pembayaran` dipastikan memuat `tunai`, lalu setting SPP + jenis "SPP"
diisi. Diuji: 9 tabel dihapus manual → semua halaman keuangan normal kembali.

---

## 5. Temuan yang masih terbuka

### S1. Password siswa/guru plaintext (Tinggi)

Kolom `master_siswa.password` dan `master_guru.password` berisi teks biasa
(contoh: `12341234`) dan nilai itu ditampilkan di form Data Siswa/Guru. Bila
database atau backup bocor, seluruh akun siswa/guru terbaca langsung.

Rekomendasi:
1. Simpan dengan `password_hash()`; saat login, verifikasi dengan
   `password_verify()` dan migrasikan otomatis nilai lama (rehash saat login sukses).
2. Jangan tampilkan password di form; gunakan "Reset password" (isi ulang).
3. Akun login tetap memakai `users` (ion_auth/bcrypt) — kolom plaintext ini
   sebaiknya dihapus setelah migrasi.

### S2. XSS: keluaran tanpa escaping (Sedang)

Terdapat ±5.000 `<?=` di `application/views` tanpa `html_escape()`. Sebagian
besar berisi angka/nilai internal, tetapi beberapa menampilkan data dari database
(nama siswa, judul materi, keterangan). Vektor yang sudah diperbaiki: komentar &
balasan postingan (JS `esc()`), serta `pelanggaran` (sudah `html_escape`).

Rekomendasi: terapkan `html_escape()` pada semua keluaran data pengguna; untuk
isi materi yang memang HTML (summernote), gunakan pembersih HTML (mis. `HTMLPurifier`)
dan batasi tag yang diizinkan.

### S3. Encryption key statis (Sedang)

`$config['encryption_key']` di `application/config/config.php` bernilai tetap dan
ikut di repositori publik (dipakai untuk cookie/enkripsi). Siapa pun yang tahu
nilainya dapat memalsukan data terenkripsi yang memakai key tersebut.

Rekomendasi: ganti dengan nilai acak baru per instalasi (minimal 32 karakter),
simpan di file yang tidak ikut repositori (`application/config/*.local.php` atau
environment), lalu dokumentasikan cara mengisinya.

### S4. IDOR materi/tugas (Rendah)

`/siswa/bukaMateri/{id_kjm}/{jamke}` dan `bukaTugas` mengambil data berdasarkan ID
tanpa memeriksa apakah materi itu milik kelas siswa tersebut. Siswa dapat membaca
materi kelas lain bila mengetahui ID-nya.

Rekomendasi: sebelum menampilkan, pastikan `id_kjm` terdaftar untuk kelas siswa
(join `kelas_jadwal_materi` → `kelas_siswa` pada tahun pelajaran & semester aktif).

### S5. Integritas ujian (Sedang)

Waktu ujian dihitung dan dikirim dari sisi klien (`elapsed`), dan `saveJawaban`
tidak memeriksa apakah sesi ujian masih berjalan. Siswa yang paham teknis dapat
mengirim jawaban setelah waktu habis atau mengubah `elapsed`.

Rekomendasi: hitung sisa waktu di server dari `log_ujian` (waktu mulai + durasi),
tolak penyimpanan bila melewati batas, dan catat percobaan tersebut.

### S6. Route `update` (Rendah)

`/update` masih bisa dibuka dari komputer server dan dapat menjalankan perintah
migrasi dari `database.json`. Untuk sekolah yang tidak pernah update manual,
route ini bisa ditutup seperti `compare`.

---

## 6. Hasil uji akses per peran

Uji langsung dengan akun admin, guru, dan siswa (`GET`, tanpa token):

| Halaman | Guru | Siswa |
|---|---|---|
| `settings`, `dbmanager`, `dbclear`, `useradmin`, `userguru`, `usersiswa` | 403 | 403 |
| `datasiswa` | **403** (dulu 200) | 403 |
| `usersiswa` (termasuk `edit`) | **403** (dulu 200) | 403 |
| `usersiswa/change_password` | 200 (ganti password sendiri) | 200 |
| `bukuinduk` | **403** (dulu 200) | 403 |
| `bukurapor` (arsip) | 200 | 403 |
| `bukurapor/editnilairapor` | **403** (dulu 200) | 403 |
| `pembayaran`, `pembayaran/tunai`, `pembayaran/laporan`, `pembayaran/setting_spp`, `tagihan` | **403** (dulu 500) | 403 |
| `presensi`, `presensi/rekap`, `cbtsesi`, `cbtruang`, `cbtjenis`, `cbtnomorpeserta`, `cbtpengawas`, `cbtalokasi`, `ujiansekolah/pengaturan` | 403 | 403 |
| `dashboard`, `kelasmateri`, `ujiansekolah`, `nilaimanual`, `pelanggaran` | 200 | 403 (admin/guru) / 200 (siswa) |

Tanpa login, seluruh halaman di atas → **307** (redirect ke login). Yang tidak
pernah terbuka tanpa login: `.git/`, `application/logs/`, `uploads/`, `backups/`
→ **403**.

---

## 7. Perlindungan yang sudah terpasang

- `.htaccess` root: blokir `.git`, dotfiles, folder `docs/`, ekstensi sensitif
  (`.sql`, `.log`, `.bak`, `.env`, …), `Options -Indexes`, security headers
  (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`).
- `uploads/.htaccess`: tanpa listing, tolak ekstensi skrip, `php_flag engine off`.
- `backups/.htaccess`: tolak akses web (403).
- `installer/.htaccess`: hanya localhost + penjaga "sudah terpasang" (K2).
- CSRF aktif (`csrf_protection = TRUE`), token diregenerasi otomatis oleh CI.
- Cookie `HttpOnly`, `Secure` otomatis bila HTTPS; sesi disimpan di file.
- Login dibatasi 3 percobaan gagal / 10 menit (ion_auth).
- `Akses_hook` menolak siswa ke halaman admin/guru dan menolak non-admin ke
  aksi sensitif (User Management, data siswa, arsip rapor, ujian milik orang lain).
- Mode `production` otomatis untuk akses non-localhost (pesan error tidak
  menampilkan detail database).

---

## 8. Cara menguji ulang

1. **Otorisasi:** login sebagai guru → buka `/datasiswa`, `/bukurapor/editnilairapor`
   → harus 403. Login sebagai siswa → `/settings`, `/tagihan` → 403.
2. **IDOR ujian:** sebagai siswa, POST `/siswa/saveJawaban` dengan `siswa=999`
   → 403 (JSON).
3. **Path traversal:** POST `/cbtbanksoal/deletefile` dengan `src=../../index.php`
   → 403; pastikan `index.php` masih ada.
4. **CSRF:** POST `/kelasmateri/deletefile` tanpa `csrf_token` → 403.
5. **Installer:** buka `/installer/` → 403 "Installer dinonaktifkan".
6. **Bersihkan Data:** buat tabel uji, buka `/dbclear`, pastikan tabel uji masih ada.
7. **Self-healing:** hapus satu tabel tambahan (mis. `cbt_tagihan`), buka
   `/tagihan` → tabel dibuat ulang dan halaman normal.

---

## 9. Catatan penutup

Semua perbaikan sudah diuji regresi: 28 halaman admin, 15 halaman guru, dan
6 halaman siswa — **0 error/warning PHP**. Dua area yang sebaiknya dikerjakan
berikutnya adalah **S1 (password plaintext)** dan **S5 (integritas waktu ujian)**,
karena keduanya berdampak langsung pada data akademik.
---

## 10. Audit pass 3 (18 November 2026) — modul baru & pengerasan XSS

Audit difokuskan pada enam fitur yang baru ditambahkan (backup, WA, QR kwitansi,
surat peringatan, buku kas, statistik) dan pola keluaran JSON di seluruh view.

### Yang diperiksa dan hasilnya

| Area baru | Uji | Hasil |
|-----------|-----|-------|
| `Kas`, `Statistik`, `Backup` | akses guru & siswa | **403** (hanya admin) |
| `pelanggaran/surat` | akses guru | 200 (memang untuk guru) |
| `simpan_setting_sp`, `backup/buat`, `backup/pulihkan`, `kas/simpan` | POST oleh guru | **403**, data tidak berubah |
| `kas/simpan` | nominal `-5000`, `0`, tanggal besok | ditolak (303 + pesan), **0 baris tersimpan** |
| `backup/pulihkan` | nama `../config/database.php`, `../../index.php` | ditolak; `database.php` utuh |
| `cekkwitansi` | id `0`, `-1`, `abc`, kode salah | halaman "Tidak Valid", tanpa error |
| injeksi SQL | `bulan=1' OR 1=1--`, `tahun=2026' OR 1=1--`, `kelas=…'` | tidak ada error SQL, data tetap |
| kwitansi publik | kode benar vs salah | **Asli** vs **Tidak Valid**, nama disamarkan |

### Temuan baru & perbaikan

| ID | Temuan | Risiko | Status |
|----|--------|--------|--------|
| X1 | Pola `JSON.parse('<?= json_encode(...) ?>')` di **18 file / 35 titik**: data yang mengandung apostrof (mis. nama "Ma'ruf") merusak JavaScript, dan bila data berasal dari input guru/admin bisa menjadi celah injeksi | **Sedang** | Diperbaiki |
| X2 | `data-list='<?= json_encode(...) ?>'` pada detail bank soal: apostrof pada opsi jawaban keluar dari atribut | **Sedang** | Diperbaiki |
| X3 | Ekspor CSV buku kas rawan **CSV formula injection** (sel diawali `=`, `+`, `-`, `@`) | **Rendah** | Diperbaiki |

**Perbaikan X1:** semua pola diubah menjadi objek JSON langsung dengan flag
`JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT`, sehingga apostrof, `<`,
`>`, `&`, dan kutip di-escape menjadi `\u0027`, `\u003C`, dst. Diuji dengan
running text berisi `Ma'ruf & <b>sekolah</b>` → keluar sebagai
`"Uji Ma\u0027ruf \u0026 \u003Cb\u003Esekolah\u003C\/b\u003E"` (aman, tidak
merusak halaman).

**Perbaikan X2:** atribut `data-list` memakai `JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP`.
**Perbaikan X3:** nilai sel yang diawali `= + - @` diberi awalan `'`.

### Catatan

- Format fallback backup CI `dbutil` dengan `format=txt` sudah menghasilkan SQL
  yang bisa dipulihkan (diperiksa di `system/database/DB_utility.php`).
- File QR (`assets/app/js/qrcode.min.js`) memakai lisensi MIT dan disimpan lokal
  sehingga fitur tetap berjalan tanpa internet.
- Folder `backups/` tetap ditolak akses web (403) dan file `.sql` tidak ikut repo.
- Regresi setelah perbaikan: 15 halaman admin + 13 guru + 6 siswa → **0 error**.
---

## 11. Audit pass 4 (18 November 2026) — verifikasi laporan eksternal

Laporan audit independen (dari clone repo publik) berisi 6 temuan kritis dan
beberapa temuan menengah. Semua diverifikasi terhadap kode aktual, lalu
diperbaiki bila benar. Ringkasannya:

### Enam temuan kritis

| # | Temuan | Verifikasi | Perbaikan |
|---|--------|------------|-----------|
| 1 | SQLi `Siswa::getPost` lewat `?kelas=` | **BENAR**. `Post_model::getPostForUser` menyambung `"... LIKE " . $kelas` mentah | `getPostForUser` memakai `group_start()->like()->or_like()->group_end()`; nilai `kelas` dibatasi 50 karakter |
| 2 | `Siswa::deleteFile` menghapus file sembarang | **SEBAGIAN**. Method `deleteFile` tidak ada di `Siswa`, tetapi pola `unlink(str_replace(base_url(),'',$src))` ada di 5 controller lain | Hook kini menolak `src` yang tidak mengandung `uploads/` (selain `..`/path absolut) |
| 3 | Install/Update tanpa autentikasi | **BENAR**. Route hanya dijaga `REMOTE_ADDR`; di balik tunnel semua terlihat `127.0.0.1` | `Akses_hook` menolak `install` & `update` (403) bila tabel `users` sudah ada; `compare` sudah ditutup total |
| 4 | Endpoint ujian tanpa cek peran/kepemilikan | **BENAR untuk** `resetTimer`, `applyAction`, `leavecbt`, `olahNilai`, `id_soal_siswa` pada `saveJawaban`. `saveSoalSiswa` mengambil `id_siswa` dari server (`$shuffle[0]->id_siswa`), bukan dari klien | Hook memeriksa pemilik `id_soal_siswa` (tabel `cbt_soal_siswa`) dan `id_durasi` (`cbt_durasi_siswa`), segmen URI `leavecbt`/`olahNilai`, memblokir `applyAction` untuk siswa, dan memblokir `saveSoalSiswa` bila mengirim `id_siswa` orang lain |
| 5 | Stored XSS jawaban siswa → guru/admin | **BENAR**. `views/cbt/nilai/detail.php` (4 titik) & `views/cbt/status/detail.php` (1 titik) mencetak jawaban mentah | Semua keluaran jawaban dibungkus `html_escape()` |
| 6 | PHP Object Injection `unserialize(jawaban_siswa)` | **BENAR**. Ada 335 panggilan `unserialize()` pada data DB | Semua diberi `['allowed_classes' => ['stdClass']]` (252 di controller/model + 83 di view); objek kelas lain tidak lagi dihidupkan |

### Temuan menengah

| Temuan | Verifikasi | Perbaikan |
|--------|------------|-----------|
| Query mentah `Cbt_model` dengan `$jadwal` | **BENAR** untuk 6 `get_where(..., "id_...=" . $var)` | Diubah menjadi `get_where($table, array($key => $var))` |
| Mode development dari header Host | **BENAR**. `ENVIRONMENT` menjadi development bila `HTTP_HOST=localhost` | Environment hanya `production`, kecuali `CI_ENV` atau file `.dev-mode` |
| `base_url` dari `HTTP_HOST` | **BENAR** (risiko injeksi Host) | Host disanitasi regex `^[A-Za-z0-9.-]+(:\d{1,5})?$`, fallback `SERVER_NAME` |
| `encryption_key` statis di repo | **BENAR** | Kunci dirotasi (`31382675…`). Catatan: QR kwitansi yang sudah dicetak sebelum rotasi menjadi tidak valid |
| `Datasiswa::update_foto` path traversal (`nis` + `ext`) | **BENAR** | `nis` disanitasi `[A-Za-z0-9_-]`, ekstensi dibatasi jpg/jpeg/png/gif |
| Lockout DoS lewat IP bersama (tunnel) | **Masuk akal** | `track_login_ip_address = FALSE` → lockout per akun, bukan per IP |
| `mysqldump -p<pass>` terlihat di daftar proses | **BENAR** | Kredensial dikirim lewat `--defaults-extra-file` sementara yang langsung dihapus |
| Sesi 24 jam vs komentar "1 jam" | **BENAR** | `sess_expiration` = 14400 (4 jam) + komentar diperbaiki |
| Tidak ada CSP/HSTS/SameSite | **BENAR** | Ditambahkan di `.htaccess`: CSP, HSTS (env HTTPS), `SameSite=Lax`, `Permissions-Policy` |
| `docs/backup/Siswa.php.sebelum-patch` di repo publik | **BENAR** | `docs/backup/` dihapus dari repo |

### Yang tidak diubah / catatan

- **Password siswa/guru plaintext** (`master_siswa.password`, `master_guru.password`)
  tetap seperti sebelumnya; perlu migrasi ke bcrypt + ubah alur login/reset. Ini
  prioritas berikutnya (lihat S1).
- **Komentar & balasan siswa** serta **materi guru** adalah HTML kaya; escaping
  massal akan merusak tampilan. XSS di area itu perlu pembersih HTML
  (HTMLPurifier) sebagai langkah terpisah.
- **Ketergantungan header keamanan pada Apache**: `.htaccess` tidak berlaku di
  nginx/IIS. Untuk nginx, tambahkan header dan `location ~* ^/(uploads|backups|docs)/ { deny all; }`.
- Dependensi Composer sudah diperbarui: `dompdf` 3.x, `PhpSpreadsheet` 1.30,
  `PhpWord` 1.4; `composer.lock` kini kompatibel dengan PHP 7.4 (web) dan 8.3
  (CLI) serta bebas advisori keamanan. Jalankan `composer install` di server
  bila folder `vendor/` belum ada.
- Pola hook saat ini *deny-list*; controller baru otomatis terbuka untuk siswa
  kecuali ditambahkan. Disarankan pindah ke base controller dengan peran wajib.
- CodeIgniter 3.1.13 adalah rilis terakhir seri 3 (EOL); pertimbangkan upgrade
  jangka panjang.

### Hasil uji setelah perbaikan

- `saveJawaban`/`resetTimer` dengan ID milik siswa lain → **403**; dengan ID
  sendiri → **200** dan tersimpan.
- `leavecbt`, `olahNilai`, `applyAction`, `saveSoalSiswa` (ID asing) → **403**.
- `deletefile` dengan `src=application/config/database.php` → **403**;
  `database.php` utuh.
- `/install` dan `/update` → **403** setelah aplikasi terpasang.
- Backup manual tetap berhasil (kredensial lewat file).
- QR kwitansi tetap terverifikasi dengan kunci baru.
- Regresi: 24 halaman admin + 12 guru + 9 siswa → **0 error**.
### Tambahan pass 4 (19 November 2026)

Celah pada temuan #3 ternyata **lebih luas** dari perbaikan pertama: `Akses_hook`
keluar lebih awal untuk pengunjung yang belum login, sehingga `/install` dan
`/update` masih terbuka tanpa autentikasi pada aplikasi yang sudah terpasang
(uji tanpa sesi: `/update` → 200).

**Perbaikan:** pemeriksaan installer dipindah ke **awal** `Akses_hook`, sebelum
cek login, dan menolak bila tabel `users` sudah **berisi akun**
(`count_all('users') > 0`). Pemeriksaan "sudah terpasang" di
`installer/includes/cek_terpasang.php` juga diubah dari "tabel users ada" menjadi
"ada akun di tabel users", agar instalasi baru (tabel users masih kosong) tetap
bisa berjalan.

**Hasil uji tanpa login:** `/install` → 403, `/update` → 403, `/compare` → 404,
`/installer/` → 403. Pada database baru (users kosong) installer tetap terbuka.

Panduan pemasangan di komputer sekolah lain ada di `docs/PANDUAN-INSTALASI.md`.
---

## 12. Audit pass 5 (18 November 2026) — index.php & jalur installer

Laporan lanjutan menyoroti blok pemeriksaan database di `index.php` (warisan
bawaan GarudaCBT). Semua temuan diverifikasi dan diperbaiki.

| # | Temuan | Verifikasi | Perbaikan |
|---|--------|------------|-----------|
| P1 | Koneksi MySQL ekstra pada **setiap** request (termasuk AJAX polling ujian) | **BENAR** — `@new mysqli()` + `SHOW DATABASES LIKE` dijalankan sebelum CodeIgniter dimuat | Pemeriksaan hanya jalan **bila belum ada** `installer/TERPASANG`. Setelah penanda ada: **nol** koneksi tambahan. Penanda dibuat otomatis saat admin login pertama |
| P2 | Tidak ada penanganan error koneksi (PHP 8.1+ melempar exception) | **BENAR** — `@new mysqli` tidak mencegah exception, hasilnya fatal error | Dibungkus `try/catch`; kegagalan = halaman **503** "Database belum dapat dihubungi", bukan fatal/blank |
| P3 | `SHOW DATABASES LIKE '$dbname'` rawan wildcard `_`/`%` dan tanpa escape | **BENAR** | Diganti prepared statement: `SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME = ?` (perbandingan persis, nilai di-bind) |
| P4 | `header("Location: installer")` tanpa `exit`; redirect ke installer saat DB kosong bisa jadi jalur instal ulang | **BENAR** (tanpa exit) | Kedua redirect kini `exit`. Redirect ke installer **hanya** untuk error 1049 (database belum ada); error lain → 503. Guard installer menolak bila status tidak bisa diverifikasi (server DB mati / akses ditolak), jadi tidak ada jalur instal ulang |
| P5 | `include 'application/config/database.php'` path relatif; `$db`/`$data` (berisi password DB) tertinggal di scope global | **BENAR** | Include memakai `__DIR__`, dilakukan **di dalam closure** sehingga variabel tidak bocor; variabel penanda di-`unset` |
| P6 | `.dev-mode` bisa ikut ter-deploy | **BENAR** (belum diabaikan) | Ditambahkan ke `.gitignore`; file tidak ada di server. `.htaccess` juga memblokir akses langsung |

### Penanda instalasi (`installer/TERPASANG`)

- Ditulis oleh `installer/install.php` **hanya bila instalasi sukses**.
- Dibuat otomatis oleh `Akses_hook` saat admin login (untuk instalasi yang sudah ada).
- Diabaikan git dan diblokir dari akses web (403).
- Bila penanda tidak ada (mis. dihapus), `index.php` memeriksa database sekali
  per request — masih aman, hanya kehilangan optimasi.

### Hasil uji

| Skenario | Hasil |
|---|---|
| Normal (penanda ada) | `/auth` 200, `/install` 403, tanpa koneksi tambahan |
| Tanpa penanda, DB normal | `/auth` 200, `/install` 403 |
| Tanpa penanda, host DB salah (server mati) | `/auth` **503** (bukan redirect), `/installer/` **403** |
| Tanpa penanda, database belum ada (1049) | `/auth` **302 → installer** (alur instalasi baru tetap jalan) |
| Akses `installer/TERPASANG` lewat web | **403** |
| Regresi 13 halaman utama (admin) | **0 error** |
---

## 13. Audit pass 6 (18 November 2026) — verifikasi daftar area lanjutan

Sembilan area yang diminta diperiksa. Hasilnya:

| # | Area | Hasil |
|---|------|-------|
| 1 | `csrf_protection` | **TRUE**; `csrf_regenerate` FALSE (token tidak berubah tiap request agar AJAX stabil) |
| 2 | `encryption_key`, `sess_*`, database.php | Kunci sudah dirotasi; sesi `files`, 4 jam, `HttpOnly`, `Secure` otomatis di HTTPS. `database.php` di repo masih berisi kredensial lokal bawaan (`root`, tanpa sandi) — aman untuk lokal, **jangan** deploy apa adanya tanpa menjalankan installer |
| 3 | Query mentah dengan input user | Tidak ditemukan lagi yang bisa disuntik. `Auth` memakai `where()` ber-binding; sisa `get_where` mentah di `Cbt_model` sudah diubah menjadi array binding (pass 4) |
| 4 | Role tiap controller / IDOR | `Kelasmateri`: guru **selalu** memakai `id_guru` dari sesi; parameter `?id=` hanya berlaku untuk admin → klaim "guru membaca kelas lain" tidak berlaku di versi ini. `Cbtpengawas` hanya admin; guru memakai `getPengawasByGuru` (jadwalnya sendiri) |
| 5 | Validasi upload | `uploads/.htaccess` menolak `.php/.phtml/.html/.svg/.xml` dan mematikan mesin PHP; uji langsung `uploads/x.php` → **403**. Ekstensi ganda (`x.php.jpg`) hanya dilayani sebagai gambar (tidak dieksekusi). `Cbtbanksoal` memakai `encrypt_name`; `allowed_types` tidak memuat php/html/svg. Import Excel/CSV diproses di sisi klien (array dikirim ke server), bukan file yang dieksekusi |
| 6 | Backup/Restore | Folder `backups/` → **403** dari web; tidak ada endpoint unduh; halaman backup hanya untuk admin dan POST. File dump memakai nama ber-timestamp |
| 7 | Timer/jawaban/deteksi pindah tab | **Diperbaiki**: `saveJawaban` kini mengabaikan `elapsed` dari klien dan menghitung dari `cbt_durasi_siswa.mulai`; jawaban setelah ujian selesai **ditolak**; bila waktu melebihi `cbt_jadwal.durasi_ujian` (+120 detik toleransi) ujian otomatis ditutup dan jawaban ditolak. Deteksi pindah tab tetap di sisi klien (`visibilitychange` → `leavecbt`), sehingga masih bisa diakali dengan mematikan JS — namun batas waktu & penolakan setelah selesai sekarang ditegakkan server |
| 8 | Token ujian | Dibuat 6 karakter A–Z. Generator diubah dari `mt_rand()` ke **`random_int()`** (CSPRNG). Validasi dilakukan di server (`cbt_jadwal.token_ujian` / `cbt_token`) dan dibandingkan di `validasiSiswa` |
| 9 | Berkas sensitif via web | `composer.json`, `composer.lock`, `options.json`, `README.md`, `docs/`, `application/`, `system/`, `uploads/`, `backups/` → **403**. Path seperti `logs/` dan `.dev-mode` yang tidak ada hanya menampilkan halaman login (CI `404_override`), bukan berkasnya. Catatan: aturan ini dari `.htaccess` (Apache); untuk nginx perlu konfigurasi manual (ada contoh di `docs/PANDUAN-INSTALASI.md`) |

### Temuan baru yang diperbaiki (pass 6)

| ID | Temuan | Risiko | Perbaikan |
|----|--------|--------|-----------|
| G1 | `Guruview::deleteFile()` mengambil `src` dari **GET**, sedangkan hook hanya memeriksa POST → guru bisa menghapus file sembarang lewat URL (mis. `?src=application/config/database.php`) | **Tinggi** | Hook memeriksa `src` dari POST **dan** GET, tetap wajib berada di folder `uploads/` |
| G2 | `saveJawaban` menyimpan `lama_ujian` dari `elapsed` kiriman klien | **Sedang** | Dihitung ulang dari `mulai` di server |
| G3 | Jawaban masih bisa disimpan setelah ujian selesai | **Sedang** | Ditolak dengan pesan "Ujian sudah selesai" |
| G4 | Batas durasi ujian hanya di sisi klien | **Sedang** | Server menolak & menutup ujian bila melewati `durasi_ujian` + toleransi |
| G5 | Token memakai `mt_rand()` (bukan CSPRNG) | **Rendah** | Diganti `random_int()` |

### Uji

- `Guruview/deletefile?src=application/config/database.php` → **403**, file utuh.
- `saveJawaban` dengan `elapsed=00:00:01` → tersimpan `lama_ujian=00:10:01` (waktu asli server).
- Setelah `selesai` diisi → jawaban ditolak `{"status":false,"msg":"Ujian sudah selesai."}`.
- `durasi_ujian=5` menit dengan waktu berjalan 10 menit → ditolak & status ujian ditutup.
- Regresi 13 halaman admin → **0 error**.
---

## 14. Audit pass 7 (18 November 2026) — otorisasi bank soal, XSS bacaan, sesi

Pemeriksaan menyeluruh ulang pada fitur baru (bacaan, bank soal, simpan & buat
soal) dan jalur otorisasi.

### Temuan & perbaikan

| ID | Temuan | Risiko | Perbaikan |
|----|--------|--------|-----------|
| H1 | **IDOR bank soal**: guru bisa mengubah bank soal milik guru lain. Diuji dengan guru kedua: `POST cbtbanksoal/saveBank` dengan `id_bank` milik guru lain mengubah `bank_kode` (HTTP 200, data berubah). Halaman `buatsoal/{id}` dan `detail/{id}` juga bisa dibuka. | **Tinggi** | `Akses_hook` menolak guru yang mengakses bank soal guru lain (semua method bank: buatsoal, detail, saveBank, deleteBank, saveSoal, hapusSoal, import, dst). Ditambah pemeriksaan langsung di `Cbtbanksoal::buatsoal()` dan `detail()` lewat `cek_bank_guru()`. Diuji: guru lain → **403**; pemilik → **200**; `saveSoal` lintas guru → **403** |
| H2 | **Stored XSS pada bacaan**: isi bacaan (`cbt_bacaan.isi`) disimpan sebagai HTML dan ditampilkan di halaman ujian siswa. Guru yang akunnya dibobol bisa menyisipkan `<script>`/`onerror`. | **Sedang–Tinggi** | Helper baru `bacaan_isi_aman()` (allowlist tag aman, buang script/style/iframe/on*, href/src/javascript:) dipakai saat **menyimpan** dan saat **menampilkan** di ujian. Uji: `<script>` dan `<img onerror>` hilang, `<strong>` tetap |
| H3 | **Session fixation**: tidak ada regenerasi ID sesi setelah login berhasil. | **Rendah–Sedang** | `Auth::cek_login` memanggil `$this->session->sess_regenerate(TRUE)` setelah login sukses. Login tetap berjalan normal |

### Yang diverifikasi aman

- Semua POST mutasi tanpa login → **403** (CSRF/guard), GET halaman admin tanpa login → **307 ke /auth**.
- Batas percobaan login 3x sudah ada (ion_auth `is_max_login_attempts_exceeded`).
- `Cekkwitansi` meng-escape nama, bulan, metode, dan kode; hanya menampilkan data pembayaran.
- `Backup_model::pulihkan/hapus` memakai `basename()` + validasi nama + folder `backups/`.
- `uploads/.htaccess` menolak `.php` (403) dan mesin PHP dimatikan.
- QR kwitansi: kode baru (kunci database) dan kode lama (kunci sebelum rotasi) sama-sama valid; kode acak tidak valid.
- Timer/jawaban ujian tetap ditegakkan server (elapsed dari `mulai`, tolak setelah selesai, batas `durasi_ujian`).

### Sisa risiko (belum diperbaiki)

1. Password siswa/guru masih plaintext di `master_siswa`/`master_guru`.
2. Konten soal (rich text) masih HTML mentah dari guru — vektor XSS internal bila akun guru dibobol.
3. Deteksi pindah tab berbasis JS.
4. Aturan `.htaccess` tidak berlaku di nginx.
5. Beberapa endpoint riwayat (cbtcetak/cbtstatus) memakai `id` bank/kelas tanpa pemeriksaan kepemilikan menyeluruh.
## Verifikasi temuan `index.php` (19 November 2026)

Temuan audit lama dicek ulang terhadap kode di `main`. Sebagian besar sudah
diperbaiki sebelum audit ini; dua hal kecil baru ditambahkan.

| # | Temuan | Status di kode sekarang |
|---|---|---|
| 1 | DB gagal -> semua pengunjung diarahkan ke installer | **Sudah aman.** Pengecekan hanya jalan bila `installer/TERPASANG` belum ada; `mysqli` dibungkus `try/catch`; error selain 1049 menghasilkan **503** ("Database belum dapat dihubungi"), bukan installer. Nama database dicek dengan **prepared statement** ke `information_schema.SCHEMATA`, bukan `SHOW DATABASES LIKE`. |
| 2 | Satu koneksi + query ekstra tiap request | **Berkurang.** Setelah `TERPASANG` ada, tidak ada koneksi tambahan. Sekarang `index.php` juga **membuat penanda otomatis** setelah database terbukti ada, jadi server yang belum punya penanda tidak terus-menerus melakukan query. |
| 3 | Redirect relatif tanpa `exit` | **Diperbaiki.** Redirect memakai path absolut dari `dirname($_SERVER['SCRIPT_NAME'])` dan diikuti `exit`. Diuji dengan nama database salah: respons `302 Location: /cbt-sekolah/installer`. |
| 4 | Nama database disisipkan ke SQL | **Tidak ada lagi.** Diganti prepared statement `SELECT ... WHERE SCHEMA_NAME = ?`. |
| 5 | `.dev-mode` bocor ke produksi | **Aman.** Sudah ada di `.gitignore`; mengakses `/.dev-mode` di server hanya menghasilkan halaman login (file tidak ada), bukan isi file. Mode development hanya aktif lewat env `CI_ENV` atau file itu. |
| 6 | Kredensial DB di repo publik | **Perlu dicek, hasil: tidak ada password asli.** `application/config/database.php` berisi contoh `root` tanpa password sejak commit awal; tidak ada kredensial produksi di riwayat. Tetap disarankan memakai user MySQL khusus (bukan root) dan password kuat di server. |

### Pemeriksaan server produksi (cbt.sandev.store)

- `/installer/` -> **403 Installer dinonaktifkan**
- `/update` -> **403 Sudah Terpasang**
- `/backup` -> redirect ke `/auth` (admin-only)
- `/application/config/database.php` -> **403**
- `/application/logs/`, `/backups/` -> **403**
- `/.dev-mode` -> halaman login (tidak membocorkan isi)

### Catatan

- `installer/.htaccess` membatasi akses ke `127.0.0.1`/`::1`. Bila Apache
  berada di belakang reverse proxy, semua request bisa tampak dari
  `127.0.0.1`; penjaga `TERPASANG` + pengecekan akun `users` tetap menutup
  installer setelah aplikasi terpasang.
- Folder `installer/` sebaiknya dihapus dari server setelah instalasi selesai
  (simpan salinannya di luar web root) sebagai lapisan tambahan.
