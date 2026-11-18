# Panduan Instalasi di Komputer Sekolah Lain

> **Cari cara tercepat?** Lihat [INSTALASI-CEPAT.md](INSTALASI-CEPAT.md):
> salin folder + restore backup (data ikut), atau jalankan pasang.bat satu klik.
>
> Panduan di bawah ini adalah versi lengkap/manual.

Panduan ini untuk memasang aplikasi **CBT Sekolah** (repositori
`https://github.com/wildanzet06/cbt-sekolah`) di komputer/server sekolah yang baru.

---

## 1. Yang dibutuhkan

### Perangkat lunak

| Komponen | Keterangan |
|---|---|
| Sistem operasi | Windows 10/11, Linux, atau macOS |
| Web server | **Apache 2.4** (paling mudah: Laragon/XAMPP) — harus aktif `mod_rewrite` dan `mod_headers` |
| PHP | **8.1 – 8.3** (disarankan 8.3) |
| Database | **MySQL 8.0** atau **MariaDB 10.4+** |
| Browser | Chrome/Edge/Firefox versi terbaru |

Ekstensi PHP yang harus aktif: `mysqli`, `mbstring`, `json`, `gd`, `zip`,
`fileinfo`, `dom`/`xml`, `iconv`, `calendar`, `curl` (untuk Midtrans, opsional).

> Semua aset (Bootstrap, AdminLTE, Select2, Chart.js, QR) disimpan lokal, jadi
> aplikasi **tidak butuh internet** setelah terpasang.

### Perangkat keras (perkiraan)

- RAM: 2 GB minimal (4 GB nyaman)
- Disk: ±500 MB untuk aplikasi + ruang untuk data & backup
- Akses ke folder web server (mis. `C:\laragon\www\`)

### Yang harus disiapkan

1. Nama database baru: **`cbt`** (utf8mb4).
2. User database + password (boleh `root` untuk lokal).
3. Akun admin sekolah: nama lengkap, username, password, email.
4. Data sekolah: nama, alamat, kepala sekolah, NIP, logo (opsional).

---

## 2. Instalasi (langkah singkat)

### Langkah 1 — Ambil kode aplikasi

```bash
git clone https://github.com/wildanzet06/cbt-sekolah.git
```

Atau unduh ZIP dari GitHub lalu ekstrak.

### Langkah 2 — Letakkan di folder web server

| Lingkungan | Contoh lokasi |
|---|---|
| Laragon (Windows) | `C:\laragon\www\cbt-sekolah` |
| XAMPP (Windows) | `C:\xampp\htdocs\cbt-sekolah` |
| Linux (Apache) | `/var/www/html/cbt-sekolah` |

### Langkah 3 — Buat database

Lewat phpMyAdmin: **New** → nama `cbt` → collation `utf8mb4_general_ci`.

Atau lewat terminal:

```bash
mysql -u root -e "CREATE DATABASE cbt CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### Langkah 4 — Jalankan installer database

Buka:

```
http://localhost/cbt-sekolah/installer/
```

Isi:

| Kolom | Isi |
|---|---|
| Hostname | `localhost` |
| Host Username | `root` (atau user DB) |
| Host Password | password DB (kosong bila root tanpa password) |
| Nama Database | `cbt` |

Installer akan:
- mengimpor `assets/app/db/master.sql` (skema dasar, ±74 tabel), dan
- menulis `application/config/database.php`.

> Installer hanya bisa dibuka dari komputer server sendiri. Kalau aplikasi
> **sudah pernah terpasang** (sudah ada akun), installer otomatis menolak (403).

### Langkah 5 — Wizard aplikasi

Buka:

```
http://localhost/cbt-sekolah/install
```

Ikuti langkahnya: cek database → data sekolah → **buat akun administrator**.
Setelah selesai, login lewat:

```
http://localhost/cbt-sekolah/auth
```

### Langkah 6 — Impor tabel tambahan

Buka phpMyAdmin → pilih database `cbt` → tab **Import** → pilih berkas:

```
docs/database_gabungan.sql
```

Berkas ini menambahkan tabel modul: keuangan/tagihan, presensi, poin
pelanggaran, nilai manual, Ujian Sekolah, buku kas, dan ambang poin SP.
Aman dijalankan berulang.

> Sebagian tabel juga dibuat otomatis oleh aplikasi saat halamannya dibuka,
> tetapi impor sekali di awal lebih rapi.

### Langkah 7 — Isi data pokok

Urutan yang disarankan setelah login sebagai admin:

1. **Pengaturan → Profile Sekolah**
   Nama sekolah, alamat, kepala sekolah/NIP, logo, nama aplikasi.
2. **Data Umum → Tahun Pelajaran** dan **Semester** (aktifkan yang berlaku).
3. **Data Umum → Mata Pelajaran, Kelas/Rombel, Guru, Siswa.**
   (Siswa bisa ditambah manual atau impor.)
4. **Keuangan → Setting Pembayaran** (nominal SPP, rekening, instruksi) dan
   **Jenis Tagihan**, lalu **Buat Tagihan**.
5. **Ujian Sekolah** (Daftar Ujian → Pengaturan → Kumpulan Soal → Hasil).
6. **User Management** untuk akun guru/siswa bila belum dibuat.

---

## 3. Izin folder (wajib)

Folder berikut harus bisa ditulis oleh web server:

```
uploads/            (foto, materi, bukti transfer)
backups/            (backup database otomatis)
application/logs/   (log aplikasi)
application/cache/  (cache)
application/config/database.php   (hanya saat instalasi)
```

**Windows (Laragon/XAMPP):** klik kanan folder → Properties → Security →
beri izin *Modify* untuk user yang menjalankan Apache.

**Linux:**

```bash
sudo chown -R www-data:www-data uploads backups application/logs application/cache
sudo chmod -R 775 uploads backups application/logs application/cache
```

---

## 4. Konfigurasi web server

### Apache

Pastikan `httpd.conf` (atau vhost) mengizinkan `.htaccess`:

```apache
<Directory "C:/laragon/www/cbt-sekolah">
    AllowOverride All
    Require all granted
</Directory>
```

Aktifkan modul: `rewrite_module`, `headers_module`, `authz_core_module`.

### nginx (bila tidak memakai Apache)

`.htaccess` **tidak dibaca** nginx, jadi aturan keamanan harus ditulis ulang:

```nginx
server {
    root /var/www/html/cbt-sekolah;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    # Jangan pernah mengeksekusi skrip di folder upload
    location ~* ^/uploads/.*\.(php|phtml|phar|pl|py|cgi|sh|html?|svg|xml)$ { deny all; }
    location ~* ^/(backups|docs|installer)/ { deny all; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header Referrer-Policy strict-origin-when-cross-origin;
}
```

---

## 5. Pengaturan yang harus disesuaikan

Buka `application/config/config.php`:

1. **`encryption_key`** — ganti dengan nilai acak baru **khusus instalasi ini**
   (jangan memakai nilai bawaan repo). Contoh membuatnya:

   ```bash
   php -r "echo bin2hex(random_bytes(16));"
   ```

2. **Zona waktu** — sesuaikan:

   ```php
   date_default_timezone_set('Asia/Jakarta');  // atau Asia/Makassar / Asia/Jayapura
   ```

3. **`ENVIRONMENT`** — biarkan **production** (default). Jangan membuat berkas
   `.dev-mode` di server sekolah; itu hanya untuk debugging di komputer
   pengembang dan menampilkan pesan error detail.

4. **`base_url`** — otomatis mengikuti alamat yang dipakai. Tidak perlu diubah.

Bila user/password database berbeda, sesuaikan
`application/config/database.php`.

---

## 6. Akses dari komputer lain di jaringan sekolah

1. Pastikan Apache mendengarkan alamat jaringan (`Listen 80`) dan
   `Require all granted` untuk folder aplikasi.
2. Tambahkan aturan firewall masuk (inbound) untuk port **80** (dan 443 bila HTTPS).
3. Cari IP komputer server: `ipconfig` (Windows) atau `ip addr` (Linux).
4. Buka dari komputer lain:

   ```
   http://192.168.1.10/cbt-sekolah
   ```

> Aplikasi bisa diakses lewat IP/domain apa pun karena `base_url` mengikuti
> Host yang dipakai. Jangan membuka aplikasi ke internet tanpa HTTPS.

---

## 7. Memindahkan data dari komputer lama

### Cara A — lewat menu Backup (disarankan)

1. Di komputer **lama**: menu **Pengaturan → Database → Backup Otomatis** →
   klik **Backup Sekarang**, lalu salin file `backups/*.sql` terbaru.
2. Salin juga folder `uploads/` (foto, materi, bukti transfer).
3. Di komputer **baru**: pasang aplikasi (langkah 1–5), login sebagai admin,
   buka **Backup Otomatis** → unggah/salin file `.sql` tadi ke folder
   `backups/` → klik **Pulihkan**.
4. Salin isi `uploads/` dari komputer lama.

### Cara B — lewat mysqldump / phpMyAdmin

1. Di komputer lama:

   ```bash
   mysqldump -u root cbt > cbt-lama.sql
   ```

2. Di komputer baru: buat database `cbt`, lalu impor `cbt-lama.sql`
   (phpMyAdmin → Import, atau `mysql -u root cbt < cbt-lama.sql`).
3. Salin seluruh folder aplikasi + `uploads/`, sesuaikan
   `application/config/database.php`.
4. **Jangan** menjalankan installer lagi di database yang sudah berisi data.

---

## 8. Setelah terpasang

- **Backup otomatis** sudah aktif (harian, disimpan 14 hari) dan bisa
  dipulihkan satu klik. Biasakan menyalin folder `backups/` ke flashdisk/Drive.
- Ganti password admin bawaan, lalu buat akun guru dan siswa.
- Uji dari komputer lain (LAN) dan dari HP.
- Periksa keamanan cepat:
  - `http://…/cbt-sekolah/docs/` → harus **403**
  - `http://…/cbt-sekolah/.git/config` → harus **403**
  - `http://…/cbt-sekolah/install` dan `/update` → harus **403/404**

---

## 9. Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| Semua halaman **403** | `AllowOverride All` belum aktif, atau `Require all granted` belum diset |
| Halaman **500** | Ekstensi PHP kurang (mysqli/gd/zip) — cek `application/logs/` |
| Halaman **putih** | Cek `application/logs/log-YYYY-MM-DD.php`; biasanya izin folder atau ekstensi |
| `Table … doesn't exist` | Jalankan `docs/database_gabungan.sql`, atau buka halaman terkait (tabel dibuat otomatis) |
| Upload gagal | Izin folder `uploads/`; naikkan `upload_max_filesize` & `post_max_size` (mis. 20M) |
| Backup gagal | `mysqldump` tidak ditemukan — pastikan MySQL client ada di PATH; aplikasi juga punya fallback |
| CSS/JS tidak muncul | `mod_rewrite` mati atau `base_url` salah |
| Sering logout sendiri | Sesi 4 jam; naikkan `sess_expiration` di `config.php` bila perlu |
| Installer menolak (403) | Aplikasi sudah terpasang. Untuk memasang ulang, kosongkan database dulu |

---

## 10. Ringkasan perintah (Linux)

```bash
cd /var/www/html
git clone https://github.com/wildanzet06/cbt-sekolah.git
mysql -u root -e "CREATE DATABASE cbt CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root cbt < cbt-sekolah/docs/database_gabungan.sql
# buka http://IP/cbt-sekolah/installer/ lalu http://IP/cbt-sekolah/install
```
