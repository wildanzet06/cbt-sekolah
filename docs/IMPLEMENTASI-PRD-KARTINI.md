# Status Implementasi PRD KARTINI CBT

Dokumen acuan: `docs/PRD-KARTINI-UJIAN.md`
Terakhir diperbarui: 19 November 2026

## Ringkasan commit

| Fase | Isi | Commit | Status |
|---|---|---|---|
| 1 | Daftar Ujian satu form + tabel, Kelola Soal inline (iframe), Pantau, Cetak Absen, Berita Acara, Edit, Hapus, tanpa Cetak Kartu | `f31ef00` | Selesai |
| 2a | Skema aditif (`tipe_soal`, `opsi_json`, `bobot`, `media_audio`, `opsi_alias_json`), isian bobot + unggah audio di editor, audio tampil di halaman ujian | `565f96c` | Selesai |
| 3 | Portal kode ujian `/ujianportal`: kode 6 karakter, verifikasi NIS + nama, login otomatis ke `siswa/cbt`; link di halaman login | `254a958` | Selesai |
| 4 | Tab **Koreksi Essay** + **Ekspor CSV** pada halaman Hasil (total 6 tab) | `6e8a9d2` | Selesai |
| 5 | Polling 10 detik + alarm audio + toast di halaman Pantau | `6e8a9d2` | Selesai |
| 2b | Tipe **Benar/Salah**, pemakaian **bobot per soal** dalam penilaian, penyamaan 5 jenis KARTINI | — | Belum |

## Rincian fase 2a

- Kolom baru: `cbt_soal.tipe_soal` (default `pg`), `cbt_soal.opsi_json`,
  `cbt_soal.bobot`, `cbt_soal.media_audio`, `cbt_soal_siswa.opsi_alias_json`.
- Editor soal lama (`cbtbanksoal/buatsoal`) menampilkan **Bobot Soal** dan
  **Audio Soal**; tombol Unggah memakai `cbtbanksoal/upload_image`.
- `Siswa::loadNomorSoal` menambahkan pemutar `<audio>` ke teks soal bila
  `media_audio` terisi.

## Rincian fase 3

- Alur: `/ujianportal` → kode → verifikasi NIS + nama → sesi login diisi
  (`identity`, `user_id`, `email`) → redirect `siswa/cbt`.
- Syarat kode berlaku: `cbt_jadwal.status = 1`, `tgl_mulai <= hari ini <=
  tgl_selesai`, TP/SMT aktif, dan kelas peserta berasal dari `bank_kelas`.
- Akun harus aktif dan tergabung di grup **siswa** (group_id 3).

## Keterbatasan yang masih terbuka (fase 2b)

1. **Benar/Salah belum tersedia sebagai tipe soal.** Editor yang ada baru
   mendukung PG, PG Kompleks, Menjodohkan, Isian, dan Essay. Tipe
   Benar/Salah memerlukan jalur baru di `loadNomorSoal`, `ujian.php`, dan
   `olahNilai`.
2. **Bobot per soal belum dipakai dalam penilaian.** Nilai masih memakai
   bobot per jenis dari pengaturan ujian (`tampil_pg`, `bobot_pg`, dst.);
   kolom `cbt_soal.bobot` baru disimpan, belum dihitung di `olahNilai`.
3. Portal memakai NIS + nama sebagai verifikasi (sesuai keputusan PRD #3),
   belum ada pembatasan percobaan (rate limit) dan belum mencatat log
   percobaan gagal.

## Catatan teknis

- Server lokal berjalan pada PHP 7.4/Apache (Laragon); lint dijalankan
  dengan PHP 8.3. Kode baru memakai sintaks yang kompatibel dengan 7.4.
- Migrasi aditif ada di `docs/database_gabungan.sql` dan aman diulang.
- Data lama tidak diubah/dihapus; kolom baru memiliki nilai default.
