# PRD — Perbaikan XSS Tersimpan dan Guard Mutasi Lanjutan

## 1. Konteks

Melanjutkan `PRD-PERBAIKAN-KEAMANAN-TINGGI.md`. Kelompok sebelumnya sudah aman dan di-commit sebagai `1290cf1`. Audit lanjutan masih melihat dua celah yang belum tertutup di repo kerja:

1. Konten soal/opsi dan isi materi masih dicetak apa adanya di beberapa halaman admin/guru, sementara helper `soal_html_aman()` sudah tersedia dan dipakai di halaman analisis.
2. Daftar `Akses_hook::$metode_mutasi` belum mencakup beberapa method yang mengubah data (copy, hapus materi/tugas, backup/restore, reset nomor, log materi/tugas, dll), sehingga akses lewat tautan GET biasa belum ditolak untuk method-method itu.

## 2. Temuan yang diperbaiki

| No | File | Masalah |
|---|---|---|
| 1 | `application/views/cbt/banksoal/detail.php`, `detail-table.php`, `cbt/status/detail.php`, `cbt/nilai/detail.php` | Teks soal dan opsi dari database belum melewati `soal_html_aman()`. |
| 2 | `application/controllers/Kelasmateri.php`, `application/views/members/siswa/materi/view.php`, `members/siswa/tugas/view.php`, `application/views/kelas/tugas/add.php`, `application/helpers/bacaan_helper.php` | Isi materi/tugas (HTML dari editor) belum disanitasi saat disimpan/ditampilkan; atribut gambar yang aman juga belum dipertahankan oleh sanitizer. |
| 3 | `application/views/kelas/materi/add.php` | Isi materi lama disisipkan ke string JavaScript backtick secara mentah; dapat merusak JS bila mengandung backtick. Teks editor dan jawaban siswa di textarea juga belum di-escape. |
| 4 | `application/hooks/Akses_hook.php` | Beberapa method mutasi belum masuk `$metode_mutasi` (copy, hapus materi/tugas/soal, delete-all, backup/restore, reset nomor, log materi/tugas, kalkulasi, dll). |

## 3. Tujuan

- Script/event handler tidak bisa ikut tersimpan lewat soal, opsi, atau materi.
- Gambar dan format sederhana yang sudah dipakai guru tetap tampil.
- Aksi mutasi lewat tautan/GET biasa ditolak, sementara alur AJAX yang sudah ada tetap berjalan.
- Tampilan GarudaCBT, SPP, sidebar, dan struktur database tidak berubah.

## 4. Ruang lingkup

**Diubah:** helper sanitizer yang sudah ada dipakai ulang (atribut gambar aman tetap dipertahankan); 4 view soal/nilai; 2 view materi/tugas siswa; 2 view editor materi/tugas; `Kelasmateri::saveMateri()`; daftar mutasi `Akses_hook`.

**Tidak diubah:** SPP/pembayaran, Midtrans, skema database, tampilan utama, dan alur CBT yang sudah berjalan.

## 5. Pengujian

- `php -l` untuk semua file PHP yang berubah.
- Uji helper: `<script>alert(1)</script><img src=x onerror=alert(1)><p>Aman</p>` → script/onerror hilang, `<p>Aman</p>` tetap.
- Uji simpan materi dengan payload di atas → tidak ada script tersimpan; gambar `data:image/png` valid tetap ditulis sebagai `.png`.
- Uji halaman bank soal/detail nilai → tidak ada error konsol baru; format soal tetap terbaca.
- Uji GET biasa ke method mutasi yang baru ditambahkan → 403; AJAX GET yang dipakai fitur lama tetap berjalan.

## 6. Rollback

Kembalikan file terkait ke commit sebelum kelompok ini bila ada regresi.
