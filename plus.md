# V 1.6.0
### DONATE VERSION
#### Database
- versi db terakhir: `20250119000000`
- perubahan type id key menjadi INT AI di tabel: 
    * `kelas_jadwal_kbm`
    * `kelas_jadwal_mapel`
    * `kelas_jadwal_materi`
    * `log_materi`
    * `cbt_pengawas`
- penambahan field tabel:
    * field `kbm_jam_selesai` & `libur` di tabel `kelas_jadwal_kbm`
    * field `dari` & `sampai` di tabel `kelas_jadwal_mapel`
- penambahan tabel:
    * `cbt_siswa` pengganti `cbt_soal_siswa`
    * `cbt_rekap_jadwal` pengganti `cbt_rekap`
    * `cbt_rekap_siswa` pengganti `cbt_rekap_nilai`
    * `ci_session` jika menggunakan sess_driver `database`
- tabel yang tidak digunakan lagi:
    * `api_setting` dari awal memang tidak digunakan
    * `api_token` dari awal memang tidak digunakan
    * `cbt_soal_siswa` diganti `cbt_siswa`
    * `cbt_durasi_siswa` digabung ke `cbt_siswa`
    * `cbt_nilai` digabung ke `cbt_siswa`
    * `cbt_rekap` diganti `cbt_rekap_jadwal`
    * `cbt_rekap_nilai` diganti `cbt_rekap_siswa`
- auto migrasi jika menggunakan db dari versi sebelumnya

#### Data Umum
- hapus plugin `dropipy.js` di menu import siswa/guru
- simpan kelas/rombel baru tanpa siswa
- auto aktif setelah import file excel data siswa/guru
- updated jquery 3.7.1

#### E-Learning 
- jadwal elearning flexible
- hari libur elearning flexible
- fixing beberapa bug di menu nilai dan kehadiran

#### CBT
- status siswa per tanggal di akun admin
- Analisis harus setelah Rekap dan menu Analisis pindah ke menu REKAP
- REKAP bisa dilakukan sebelum KOREKSI
- KOREKSI bisa dilakukan dari menu REKAP
- penambahan hasil analisis Indeks Kesukaran Distraktor dan RELIABILITAS
