# GarudaCBT
## Last version: 1.5.3  (FIXED)
____

### CHANGELOG:
#### Added
- Support PHP 7.4 ~ 8.3
- Login CBT
- Update foto siswa perkelas
- Auto logout admin setelah ganti password
- Import Guru dengan foto
- Editor Header jawaban essai di ujian siswa
- Equation LaTex

#### Changed
- Font Arabic Calibri
- Remove vendor folder

## Download
Harap download aplikasi dari tombol [<> Code] -> Download zip
----
## Install
* Download Aplikasi dari menu **Code => Download ZIP**
* Extract di folder **htdocs** jika menggunakan XAMPP, atau folder **www** jika mengonakan Laragon
* Lengkapnya sialhkan ke [TUTORIAL INSTALL](https://github.com/garudacbt/cbt/wiki)
----
## Update
* Download Aplikasi dari menu **Code => Download ZIP**
* Extract dan replace semua code di folder aplikasi
* sesuaikan nama database yang digunakan
* backup database untuk berjaga-jaga
* jalankan menu **update** di menu DATABASE => UPDATE
______
### MENU FITUR
1. DATA MASTER
   * Beranda
   * Keuangan
      * Pembayaran SPP
      * Daftar Tagihan
      * Buat Tagihan
      * Jenis Tagihan
      * Keringanan Siswa
      * Setting Nominal SPP
      * Laporan SPP 
   * Data Umum
      * Tahun Pelajaran
      * Jurusan
      * Mata Pelajaran
      * Ekstrakurikuler
      * Siswa
      * Kelas / Rombel
      * Guru
   * Data E-Learning
      * Jadwal Pelajaran
      * Materi
      * Tugas
      * Jadwal Materi/Tugas
   * Data Ujian
      * Jenis Ujian
      * Sesi
      * Ruang
      * Atur Ruang dan Sesi
      * Atur Nomor Peserta
      * Bank Soal
      * Jadwal
      * Alokasi Waktu
      * Token
   * Pengumuman

2. PELAKSANAAN
   * presensi Kelas
      * Isi Presensi
      * Rekap Presensi
   * Pelaksanaan Ujian
      * Cetak
      * Status Siswa
      * Hasil Ujian
      * Analisis Soal
      * Rekap Nilai Ujian

4. RAPOR
   * Setting Rapor
   * Kumpulan Nilai Rapor
   * Buku Induk
   * Alumni

5. PENGATURAN
   * Profile Sekolah
   * User Management
      * Administrator
      * Guru
      * Siswa
   * Database
      * Backup/Restore
      * Update

6. LOGOUT

___________
## version: 1.5.2
___________
#### Added
- Soal gambar menjodohkan
- Soal gambar tanpa text
- Search/paging rekap nilai
- Search kelas/rombel
- Search status ujian
- NIS list siswa di menu edit kelas
- Detect new tab ketika siswa sedang ujian

#### Fixed
- Rekap nilai 0
- Detail soal
- Typo
- Edit soal gambar hang
- Gambar soal di menu koreksi
- Hapus jadwal ujian
- Hapus siswa dari rombel
- Download soal ujian KP
- Edit soal ketika ujian berlangsung
- Mapel SD/MI
- Cetak rapor PTS beda predikat
- Kehadiran rapor di DKN
- Rapor prestasi berbeda dengan arsip
- Rapor kesehatan
- Ttd kepsek rapor akhir
- Mapel rapor berbeda dengan arsip

- Struktur organisasi #admin
- Reset waktu ujian siswa #pengawas
- Search daftar siswa ada siswa lain kelas #wali kelas
- Tugas/Materi guru membaca kelas lain #guru
- Semua guru dianggap pengawas #pengawas
- Soal menjodohkan dianggap terjawab #siswa

______
MIT License

Copyright (c) 2020 GarudaCBT

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
