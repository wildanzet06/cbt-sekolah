# Fitur Tambahan (Keuangan, Tagihan, Presensi, Poin Pelanggaran, Akun Siswa)

Fitur-fitur ini ditambahkan di atas GarudaCBT. Tidak ada tabel bawaan GarudaCBT yang diubah.

## Daftar fitur

- **Keuangan:** pembayaran SPP (transfer manual dengan verifikasi admin, dan Midtrans), **pembayaran tunai
  (cash) yang dicatat admin** — menu *Keuangan → Pembayaran Tunai* atau tombol **Tunai** di Daftar Tagihan,
  langsung tercatat lunas/cicilan tanpa menunggu unggah bukti — jenis tagihan,
  pembuatan tagihan massal, keringanan/gratis per siswa, cicilan, daftar tagihan, ekspor Excel (CSV), kwitansi.
- **Presensi per pelajaran:** admin mencatat Hadir/Izin/Sakit/Alpa per kelas, rekap bulanan, ekspor.
  Siswa melihat kehadirannya lewat menu *Pengaturan → Kehadiran Saya*.
- **Poin pelanggaran siswa:** admin dan guru mencatat pelanggaran secara manual (tanggal, jenis, poin,
  keterangan). Tersedia rekap per siswa dan per kelas serta ekspor Excel (`.xlsx`).
  Data dipisah per tahun pelajaran & semester. Guru hanya dapat mengubah/menghapus catatan yang ia buat
  sendiri; admin dapat mengubah semua catatan. Siswa/orang tua melihat poin sendiri lewat menu
  *Poin Pelanggaran* di dashboard siswa atau *Pengaturan → Poin Pelanggaran Saya*.
- **Akun siswa:** ganti password sendiri, tampilan dashboard yang ramah HP.
- **Keamanan:** penjaga akses (`application/hooks/Akses_hook.php`) agar siswa tidak bisa membuka halaman admin.

- **Backup otomatis & restore:** menu *Pengaturan → Database → Backup Otomatis*.
  Backup dibuat sekali sehari (otomatis saat admin membuka halaman), disimpan 14 hari,
  dan bisa dipulihkan satu klik (dibuat backup pengaman sebelum restore).
- **Pengingat SPP via WhatsApp:** tombol **WA** di Daftar Tagihan membuka WhatsApp
  dengan pesan tagihan siap kirim (nama, jumlah, jatuh tempo, rekening). Tanpa API.
- **Kwitansi ber-QR:** kwitansi (layar & cetak) memuat QR menuju halaman publik
  `/cekkwitansi/{id}/{kode}` untuk memeriksa keaslian; nama siswa disamarkan.
- **Surat Peringatan otomatis:** menu *Poin Pelanggaran → Surat Peringatan*.
  Ambang poin SP1/SP2/SP3 dapat diatur; siswa otomatis masuk level SP dan bisa
  dicetak surat resmi berkop sekolah (SP1/SP2/SP3).
- **Buku Kas Sekolah:** menu *Keuangan → Buku Kas*. Catat pengeluaran & pemasukan
  lain; pemasukan SPP dihitung otomatis, ada saldo dan rekap bulanan + ekspor CSV.
- **Statistik:** menu *Keuangan → Statistik*. Grafik penerimaan SPP 12 bulan,
  tunggakan per kelas, kehadiran per bulan (H/I/S/A), dan jenis pelanggaran terbanyak.

## Cara memasang

1. Pasang GarudaCBT seperti biasa lewat folder `installer`.
2. Jalankan `docs/database_gabungan.sql` pada database aplikasi — satu file berisi semua
   tabel/migrasi tambahan (keuangan, tagihan, presensi, poin pelanggaran, nilai manual,
   ujian sekolah, pembayaran tunai, rekening). Aman dijalankan berulang.
   Nomor rekening **diisi lewat aplikasi**: menu *Keuangan → Setting Pembayaran*.
3. Salin `application/config/midtrans.php.example` menjadi `application/config/midtrans.php`, lalu isi kunci Midtrans.
   File `midtrans.php` **tidak ikut** repositori karena berisi kunci rahasia.
4. Poin pelanggaran memakai tabel `cbt_pelanggaran_siswa` yang juga dibuat pada langkah 2.
   Tidak perlu mengubah tabel siswa/guru yang sudah ada.
5. Pastikan di `application/config/config.php`:
   - `$config['enable_hooks'] = TRUE;`
   - `pembayaran/midtrans_notification` terdaftar di `$config['csrf_exclude_uris']`
6. Pastikan `application/config/hooks.php` memuat entri `Akses_hook`.

## Catatan

- Halaman **Pengaturan → Database → Bersihkan Data** (`Dbclear`) pada GarudaCBT asli
  menghapus tabel yang tidak dikenal **begitu halamannya dibuka** (tanpa konfirmasi).
  Perilaku itu sudah dimatikan: membuka halaman tidak menghapus apa pun, dan tabel
  tambahan (keuangan, tagihan, presensi, pelanggaran, nilai manual, rekening, ujian)
  ikut dilindungi sehingga tidak muncul di daftar hapus.
- Tabel tambahan (keuangan, tagihan, presensi) **dibuat otomatis** oleh aplikasi bila
  hilang, lewat `application/helpers/skema_helper.php`. Jadi kalau database dipulihkan
  dari dump lama atau tabelnya terhapus, halaman keuangan tidak lagi menampilkan
  "Table ... doesn't exist" — cukup buka halamannya, tabel dan pengaturan awal SPP
  (nominal Rp 500.000 + jenis "SPP") dibuat ulang. Isi/data lamanya tetap tidak ikut
  kembali, jadi backup tetap penting.
- Kunci Midtrans sandbox hanya bekerja dengan `midtrans_is_production = FALSE`; kunci produksi dengan `TRUE`.
- Alamat notifikasi Midtrans harus alamat publik (HTTPS), bukan `localhost`.
- Untuk server sungguhan, atur `ENVIRONMENT` di `index.php` menjadi `production`.
---

## Bacaan/Stimulus bersama untuk beberapa soal

Tidak ada menu terpisah. Pengaturan dilakukan langsung di halaman **Kumpulan Soal → Buat Soal** atau dari tombol **Buat Soal** di halaman **Edit Bank Soal**.

- Satu bacaan (mis. "Text 1: My Trip to the Beach") dapat dipakai oleh banyak soal.
- Saat membuat soal di **Kumpulan Soal → Buat Soal**, pilih bacaan pada dropdown
  **Bacaan / Stimulus (opsional)**.
- Halaman ujian menampilkan bacaan di atas soal sesuai pilihan tersebut, sehingga
  tidak perlu menempel teks bacaan berulang di setiap soal.
- Bacaan mendukung HTML sederhana (`<p>`, `<strong>`, `<br>`, `<table>`).
- Menghapus bacaan tidak menghapus soal; soal hanya kehilangan bacaannya.

Tabel: `cbt_bacaan`; kolom penghubung: `cbt_soal.id_bacaan`.

## Kode QR kwitansi

Kode verifikasi kwitansi = HMAC-SHA256 dari id pembayaran dengan **kunci khusus
yang disimpan di database** (`cbt_setting_spp.kunci_kwitansi`), bukan dari
`encryption_key` di config.

Alasannya: dulu kunci memakai `encryption_key`. Saat kunci itu dirotasi
(untuk keamanan), semua QR kwitansi yang sudah dicetak menjadi tidak valid.
Sekarang rotasi `encryption_key` tidak lagi memengaruhi kwitansi.

Halaman verifikasi tetap menerima kode lama:
1. kunci khusus database (kwitansi baru),
2. `encryption_key` saat ini (kwitansi sebelum perbaikan),
3. kunci lama sebelum rotasi (kwitansi paling lama).

Tidak ada masa berlaku: QR valid selama data pembayaran masih ada di database.

### Cara pakai (tanpa menu terpisah)

1. Buka **Kumpulan Soal**, klik **Buat Soal** pada bank yang diinginkan.
   Tombol yang sama juga ada di halaman **Edit Bank Soal**.
2. Di panel **Bacaan / Stimulus**:
   - centang **Bacaan stimulus** (mengaktifkan isian nomor),
   - isi **Berlaku untuk nomor** (dari ... sampai ...),
   - untuk bacaan baru: isi **judul** + **isi bacaan** pada bagian
     **Tambah bacaan baru** lalu klik **Simpan bacaan** (langsung diterapkan
     ke nomor tersebut),
   - untuk bacaan yang sudah tersimpan: pilih pada **Bacaan tersimpan**,
     centang **Bacaan stimulus**, lalu klik **Terapkan**.
3. **Lepas bacaan** menghapus pemasangan pada rentang nomor tersebut.
4. Ringkasan "Terpasang saat ini" menampilkan bacaan per rentang nomor.
5. Dropdown bacaan per soal sudah dihapus; pengaturan hanya lewat panel ini.
### Tombol Simpan & Buat Soal

Di halaman **Buat/Edit Bank Soal** hanya ada satu tombol:
**Simpan & Buat Soal**. Setelah bank disimpan, aplikasi langsung membuka
halaman **Buat Soal** untuk bank tersebut (`cbtbanksoal/buatsoal/{id}?tab=1`),
jadi tidak perlu kembali ke daftar lalu mencari bank lagi.
## Mode sederhana Pengaturan Ujian

Halaman **Ujian Sekolah → Pengaturan Ujian** kini default ke mode sederhana:

- Tab yang tampil hanya **Peserta & Ruang** dan **Token**.
- Tab **Sesi & Jam**, **Pengawas**, dan **Jenis Ujian** disembunyikan di balik
  sakelar **Mode sederhana** (matikan sakelar untuk mengatur manual).
- Sesi dan ruang dasar dibuat otomatis bila belum ada (Sesi 1 07.30–09.30,
  Ruang 1 kapasitas 35), jadi tidak perlu membuat sesi lebih dulu.
- Pembagian peserta cukup klik **Bagi Otomatis** di tab Peserta & Ruang;
  rincian per kelas dibuka lewat tombol **Lihat**.
- Semua ujian baru otomatis memakai **jam ke-1**.
## Manajemen Ujian (satu halaman, tiga tab)

Menu **Ujian Sekolah → Manajemen Ujian** (`ujiansekolah`) sekarang satu
halaman dengan tab:

- **Daftar Ujian** (termasuk tombol Buat Ujian/Edit),
- **Pengaturan Ujian** (khusus admin),
- **Hasil Ujian**.

Tiap tab dimuat di dalam halaman (iframe `embed=1`) tanpa sidebar ganda.
Menu "Pengaturan Ujian" dan "Hasil Ujian" di sidebar dihapus karena sudah
menjadi tab. URL lama tetap berfungsi: `ujiansekolah/daftar`,
`ujiansekolah/pengaturan`, `ujiansekolah/hasil`.
## Form cepat Buat Ujian (gaya KARTINI CBT)

Di tab **Daftar Ujian** (Manajemen Ujian) ada kartu **Buat Ujian Baru** yang
ringkas, satu baris isian:

- **Bank Soal** (kelas peserta otomatis mengikuti bank),
- **Jenis**, **Tanggal**, **Durasi (menit)**, **Kode Ujian** (opsional),
- centang **Acak soal / Acak opsi / Tampilkan hasil / Aktif**,
- tombol **Simpan Ujian** (langsung tersimpan, tanpa wizard).

Hanya bank soal yang **belum dipakai** yang muncul. Untuk pengaturan rinci
(jadwal selesai, pengawas, token global) gunakan tombol **Mode lengkap**.
## Halaman "Buat & Kelola Ujian" (gaya KARTINI CBT)

`ujiansekolah` kini berupa satu halaman sederhana:

- Kartu **Buat Ujian Baru**: Judul Ujian, Mata Pelajaran/Bank Soal, Kode Ujian
  (tombol acak), Durasi, Jadwal Mulai, Jadwal Selesai (opsional), Jenis,
  chip **Kelas Ujian** otomatis dari bank soal, serta centang
  Acak Soal / Acak Opsi / Tampilkan Nilai / Aktifkan Ujian.
- Tombol **Terbitkan Ujian** menyimpan langsung (tanpa wizard); tombol
  **Mode lengkap** tetap membuka wizard untuk pengaturan rinci.
- Tabel **Daftar Ujian**: Kode Ujian, Judul & Mapel, Pembuat, Kelas, Soal,
  Durasi, Status, dan aksi Kelola Soal / Edit / Hapus.

Kolom baru: `cbt_jadwal.judul` (opsional). Kode ujian boleh 4–10 karakter
huruf/angka. Tanggal selesai boleh dikosongkan (otomatis sama dengan tanggal
mulai).
## Bank Soal & Media Ujian (editor ringkas)

Menu **Ujian Sekolah → Bank Soal & Media Ujian** (`/banksoal`) kini memakai
editor ringkas gaya KARTINI CBT:

- Pilih bank soal di kanan atas; daftar soal bank tersebut langsung tampil.
- **Input Soal Baru**: teks pertanyaan, opsi A–E (klik huruf untuk menandai
  kunci jawaban), tombol **Simpan Soal**.
- **Lampiran Media**: unggah gambar/audio; otomatis disisipkan ke teks soal
  dan tampil saat ujian.
- Tabel **Daftar Soal**: nomor, ringkasan soal, kunci, tombol Edit/Hapus.
- Soal lama (termasuk hasil impor) tetap bisa dibuka dan diedit karena
  memakai tabel `cbt_soal` yang sama. Jenis soal lain (PG kompleks, essay,
  menjodohkan) masih lewat tautan **Editor lama**.
- Tombol **Kelola Soal** pada halaman Buat & Kelola Ujian mengarah ke editor
  ini.
