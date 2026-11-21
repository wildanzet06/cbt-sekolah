# PRD — Perbaikan Fungsional dan Data Materi/Tugas Siswa

## 1. Konteks

Melanjutkan `PRD-PERBAIKAN-XSS-DAN-GUARD-LANJUTAN.md` yang sudah di-commit sebagai `7858472`. Pengujian lanjutan menemukan beberapa bug alur materi/tugas siswa yang bukan XSS, melainkan salah validasi kepemilikan dan salah nama tabel. PRD ini menjadi acuan perubahan kelompok berikutnya.

## 2. Temuan yang diperbaiki

| ID | File | Masalah |
|---|---|---|
| S4 | `application/controllers/Siswa.php` → `bukaTugasMateri()` | `id_kjm` dari URL belum diverifikasi milik kelas siswa yang login. `id_kelas` dari sesi bisa NULL/0 sehingga filter di model hilang. |
| F2 | `application/controllers/Siswa.php` → `saveLogMateri()`, `saveLogTugas()`, `saveFileMateriSelesai()`, `saveFileTugasSelesai()` | `id_siswa` diambil dari GET/POST, jadi log bisa ditulis atas nama siswa lain. |
| F1 | `application/controllers/Siswa.php` → `saveFileTugasSelesai()` | Menulis ke tabel `log_tugas` yang tidak ada di database maupun skema; submit tugas pasti gagal. |
| F3 | `application/models/Kelas_model.php` → `getMateriKelasSiswa()` | Bila `id_kelas` 0/NULL, filter kelas hilang; jadwal dengan `id_materi=0` juga tetap diproses sampai view sehingga halaman error. |
| D3 | Database lokal | 8 baris `users_groups` orphan (`user_id` 50–57 tanpa baris `users`). |

## 3. Perubahan yang direncanakan

### 3.1 Validasi kepemilikan di controller siswa

- Menambah dua helper private di `Siswa.php`:
  - `id_siswa_login()`: mengambil `id_siswa` dari username sesi ion_auth, bukan dari input.
  - `validasi_jadwal_siswa()`: memastikan `id_kjm` ada di `kelas_jadwal_materi`, `id_materi > 0`, `jenis` sesuai, tahun/semester aktif, dan siswa memiliki baris `kelas_siswa` untuk kelas itu.
- `bukaTugasMateri()` memakai helper tersebut. Bila tidak valid, halaman menampilkan 404 sebelum view dipanggil. Ini menutup S4 sekaligus mencegah 47 jadwal orphan `id_materi=0` menyebabkan error di view (F3).
- `saveLogMateri()`, `saveLogTugas()`, `saveFileMateriSelesai()`, `saveFileTugasSelesai()` memakai `id_siswa` dari sesi dan memvalidasi `id_kjm`. Permintaan tanpa siswa valid atau jadwal tidak sah mendapat respons `{"status":"error"}`.

### 3.2 Perbaikan tugas selesai

- `saveFileTugasSelesai()` diubah menulis ke `log_materi` dengan `log_desc = 'Menyelesaikan tugas'` dan `finish_time`, mengikuti pola `saveFileMateriSelesai()`. Tidak ada tabel baru dan skema database tidak diubah.
- Teks jawaban siswa tetap disanitasi dengan `soal_html_aman()` sebelum disimpan.

### 3.3 Pengaman model

- `Kelas_model::getMateriKelasSiswa()` mengembalikan `null` bila `id_kelas <= 0`, dan menolak `id_materi = 0`. Filter kelas tidak lagi bisa hilang diam-diam.

### 3.4 Data

- D1 (41 foto siswa hilang), D2 (NISN `4294967295`), dan D4 (`log_ujian`/durasi tidak sinkron) **tidak diubah otomatis** karena memerlukan input/konfirmasi sekolah.
- D3 sudah dihapus setelah user menyetujui. Backup dibuat lebih dulu di `backups/manual-pre-orphan-2026-11-21_113100.sql` (diabaikan Git), lalu 8 baris `users_groups` dengan `user_id` 50–57 dihapus. Sisa orphan = 0.

## 4. Yang tidak diubah

- Modul SPP, tampilan GarudaCBT, sidebar navy Data Siswa/Data Guru, dan struktur database.
- Alur timer ujian: pemeriksaan di `saveJawaban()` sudah valid berdasarkan audit ulang (elapsed dihitung server, jawaban setelah selesai ditolak, batas durasi +120 detik).
- Cara halaman tugas dibuka: controller tetap memakai view materi yang sudah ada agar tampilan tidak berubah.
- `encryption.local.php` tetap tidak masuk repo/ZIP.

## 5. Kriteria uji

- `php -l` lolos untuk `Siswa.php` dan `Kelas_model.php`.
- `id_kjm` siswa lain/orphan ditolak; `id_kjm` valid (`1512026071811`, `1512026111411`) masih bisa dibuka siswa kelas 1.
- GET `savelogmateri`/`savelogtugas` dengan `id_siswa` siswa lain tidak membuat log atas nama siswa itu.
- Submit materi dan tugas menghasilkan baris `log_materi` dengan `id_siswa` sesuai sesi; tidak ada error `log_tugas doesn't exist`.
- Halaman materi/tugas siswa, bank soal, dan koreksi nilai tidak menambah error konsol baru.
- Regresi: `git status` bersih setelah commit dan `encryption.local.php` tetap terabaikan.

## 6. Hasil uji

- `php -l` lolos untuk `Siswa.php` dan `Kelas_model.php`; `git diff --check` hanya memberi peringatan konversi CRLF, bukan error.
- Uji browser dengan akun siswa kelas 1 (`ahmad.afsyad`):
  - `siswa/bukamateri/1512026111411/1` → **200**, halaman materi tampil.
  - `siswa/bukamateri/1512026071311/1` (jadwal `id_materi=0`) → **404**.
  - `siswa/bukamateri/TESTKELAS2/1` (kelas 2) → **404**.
  - `savelogmateri` dan `savelogtugas` dengan `id_siswa=1` palsu → log tetap dibuat untuk **id_siswa=2** (sesi).
  - `id_kjm` tidak sah → `{"status":"error"}`.
  - Submit materi/tugas dengan `id_siswa=1` palsu → baris `log_materi` bertulis `id_siswa=2`; tugas masuk ke `log_materi` dengan `log_desc='Menyelesaikan tugas'`, tidak ada error `log_tugas`.
  - Payload `<script>` dan `onerror` pada jawaban tersaring menjadi `<p>…</p>`.
- Data uji (`TESTTUGAS2`, `TESTKELAS2`, dan dua baris `log_materi`) sudah dihapus; jumlah `log_materi` kembali 1 dan `kelas_jadwal_materi` kembali 49.
- Halaman materi siswa dibuka lagi dengan 0 error konsol; tab browser dikembalikan ke `kelasmateri/add/1` dengan sesi admin.
