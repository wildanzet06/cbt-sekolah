# Fitur Tambahan (Keuangan, Tagihan, Presensi, Akun Siswa)

Fitur-fitur ini ditambahkan di atas GarudaCBT. Tidak ada tabel bawaan GarudaCBT yang diubah.

## Daftar fitur

- **Keuangan:** pembayaran SPP (transfer manual dengan verifikasi admin, dan Midtrans), jenis tagihan,
  pembuatan tagihan massal, keringanan/gratis per siswa, cicilan, daftar tagihan, ekspor Excel (CSV), kwitansi.
- **Presensi per pelajaran:** admin mencatat Hadir/Izin/Sakit/Alpa per kelas, rekap bulanan, ekspor.
  Siswa melihat kehadirannya lewat menu *Pengaturan → Kehadiran Saya*.
- **Akun siswa:** ganti password sendiri, tampilan dashboard yang ramah HP.
- **Keamanan:** penjaga akses (`application/hooks/Akses_hook.php`) agar siswa tidak bisa membuka halaman admin.

## Cara memasang

1. Pasang GarudaCBT seperti biasa lewat folder `installer`.
2. Jalankan `docs/database_tambahan.sql` pada database aplikasi (aman dijalankan berulang).
3. Salin `application/config/midtrans.php.example` menjadi `application/config/midtrans.php`, lalu isi kunci Midtrans.
   File `midtrans.php` **tidak ikut** repositori karena berisi kunci rahasia.
4. Pastikan di `application/config/config.php`:
   - `$config['enable_hooks'] = TRUE;`
   - `pembayaran/midtrans_notification` terdaftar di `$config['csrf_exclude_uris']`
5. Pastikan `application/config/hooks.php` memuat entri `Akses_hook`.

## Catatan

- Kunci Midtrans sandbox hanya bekerja dengan `midtrans_is_production = FALSE`; kunci produksi dengan `TRUE`.
- Alamat notifikasi Midtrans harus alamat publik (HTTPS), bukan `localhost`.
- Untuk server sungguhan, atur `ENVIRONMENT` di `index.php` menjadi `production`.
