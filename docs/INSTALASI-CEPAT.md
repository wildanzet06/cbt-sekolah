# Instalasi Cepat (Cara Paling Simpel)

Ada dua cara simpel. Pilih sesuai kondisi.

---

## Cara A — Pindah dari komputer lama (tanpa instalasi, data ikut)

Paling cepat dan paling aman karena data, akun, dan pengaturan sudah ada.
**Tidak perlu** installer, wizard, atau impor SQL manual — cukup salin folder
dan satu file backup.

### Di komputer LAMA

1. Login admin → **Pengaturan → Database → Backup Otomatis**
   → klik **Backup Sekarang**.
2. Salin folder aplikasi **utuh** (`C:\laragon\www\cbt-sekolah`) ke flashdisk.
   Folder `backups/` dan `uploads/` ikut tersalin.

### Di komputer BARU

1. Pasang **Laragon** (atau XAMPP) — pilih PHP 8.3, lalu **Start All**.
2. Salin folder tadi ke `C:\laragon\www\cbt-sekolah` (lokasi sama).
3. Buka phpMyAdmin (`http://localhost/phpmyadmin`):
   - **New** → nama database `cbt` → collation `utf8mb4_general_ci`.
   - Pilih database `cbt` → tab **Import** → pilih file
     `cbt-sekolah/backups/…sql` (yang paling baru) → **Go**.
4. Buka `http://localhost/cbt-sekolah/auth` → login dengan akun lama.

Selesai. Tidak ada data yang perlu diisi ulang.

> Kalau user/password MySQL di komputer baru berbeda, sesuaikan
> `application/config/database.php` (4 baris di bagian `$db['default']`).

---

## Cara B — Instalasi baru dengan satu perintah

Untuk sekolah baru (belum punya data). Butuh PHP di PATH (Laragon:
buka menu **Terminal**).

### Langkah

1. Salin folder aplikasi ke `C:\laragon\www\cbt-sekolah`.
2. Start Laragon (Apache + MySQL).
3. Klik dua kali **`pasang.bat`** di folder aplikasi — skrip otomatis mencari
   PHP (PATH / Laragon / XAMPP) dan selalu berjalan dari folder aplikasinya.
   Bisa juga lewat Terminal Laragon: `php tools/pasang.php`.

   > **Penting:** nama database yang diketik menentukan data mana yang dipakai.
   > Ketik `cbt` untuk memakai database yang sudah ada, atau nama baru
   > (mis. `testing`) untuk percobaan. `application/config/database.php`
   > akan diarahkan ke database tersebut.
4. Ikuti pertanyaan singkat (nama database & username admin) → selesai.
5. Buka `http://localhost/cbt-sekolah/auth` → login:

   ```
   admin / Admin12345
   ```

   (ganti password setelah login)

### Yang dilakukan `pasang.bat` otomatis

- membuat database `cbt`,
- mengimpor `assets/app/db/master.sql` (skema dasar),
- mengimpor `docs/database_gabungan.sql` (keuangan, tagihan, presensi,
  pelanggaran, nilai manual, ujian sekolah, kas, ambang SP),
- menulis `application/config/database.php`,
- membuat akun administrator + grup admin,
- menyiapkan folder `uploads/`, `backups/`, `logs/`, `cache/`.

Kalau ingin memakai database/akun lain:

```bash
php tools/pasang.php --db=cbt --user=root --pass=rahasia --admin=adminsekolah --sandi=SandiKuat123
```

Kalau ingin **memulihkan file backup** ke database baru sekaligus:

```bash
php tools/pasang.php --db=cbt --pulihkan=backups/manual-2026-11-18_120809.sql
```

---

## Setelah terpasang (2 menit)

1. **Pengaturan → Profile Sekolah** — nama sekolah, alamat, kepala sekolah, logo.
2. **Data Umum → Tahun Pelajaran & Semester** — aktifkan yang berlaku.
3. **Keuangan → Setting Pembayaran** — nominal SPP, rekening, instruksi.
4. Buka **Pengaturan → Database → Backup Otomatis**, pastikan backup pertama
   sudah muncul.

Panduan lengkap (termasuk akses LAN, nginx, dan troubleshooting):
[docs/PANDUAN-INSTALASI.md](PANDUAN-INSTALASI.md).

---

## Ringkasan perbandingan

| | Cara A (pindah) | Cara B (`pasang.bat`) | Manual (PANDUAN) |
|---|---|---|---|
| Kecepatan | ±10 menit | ±5 menit | ±30 menit |
| Data lama | ikut | kosong | kosong |
| Perlu impor SQL manual | 1 kali (phpMyAdmin) | tidak | 2 kali |
| Perlu buat admin | tidak | otomatis | lewat wizard |
| Cocok untuk | ganti komputer | sekolah baru | kontrol penuh |
