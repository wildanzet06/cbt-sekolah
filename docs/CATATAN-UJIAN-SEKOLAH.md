# Ujian Sekolah — catatan implementasi

Implementasi menu **Ujian Sekolah** sesuai `docs/PRD-UJIAN-SIMPEL.md`
(Fase 0–4). Semua fitur baru berada di **file baru**; satu-satunya file lama
yang diubah isinya adalah `application/controllers/Siswa.php` (1 statement,
lihat `docs/patch-siswa-token.md`).

## 1. File baru

| File | Isi |
|---|---|
| `application/controllers/Ujiansekolah.php` | Hub Daftar Ujian, wizard 3 langkah, aksi buka/tutup/hapus, token, pembagian peserta, ruang & sesi, pantau, notifikasi dashboard |
| `application/models/Ujiansekolah_model.php` | Migrasi otomatis, kesiapan (5 chip), status kartu, CRUD jadwal + pengawas, token unik, pembagian peserta otomatis, pantau |
| `application/views/cbt/ujiansekolah/data.php` | Halaman **Daftar Ujian** (hub) |
| `application/views/cbt/ujiansekolah/wizard.php` | Wizard **Buat/Ubah Ujian** 3 langkah |
| `application/views/cbt/ujiansekolah/pembagian.php` | **Pembagian Peserta** |
| `application/views/cbt/ujiansekolah/pantau.php` | **Pantau Ujian** (hari-H) |
| `application/views/cbt/ujiansekolah/ruangsesi.php` | **Ruang & Sesi** (kapasitas ruang) |
| `application/views/cbt/ujiansekolah/_style.php` | CSS khusus modul (di-include tiap view) |
| `docs/migrasi-ujian-sekolah.sql` | Migrasi manual (juga dijalankan otomatis oleh model) |
| `docs/patch-siswa-token.md` | Dokumentasi patch 1 statement token |
| `docs/backup/` | Backup `Siswa.php` sebelum patch + dump tabel sebelum uji pembagian peserta |

## 2. File lama yang disentuh (minimal)

| File | Perubahan |
|---|---|
| `application/controllers/Siswa.php` | **1 statement** token → baca `cbt_jadwal.token_ujian` dengan fallback |
| `application/views/_templates/dashboard/sidebar.php` | Grup **UJIAN SEKOLAH** + **PENGATURAN UJIAN** (menggantikan 10 + 6 item) |
| `application/views/members/guru/templates/sidebar.php` | Grup **UJIAN SEKOLAH** (3 item menggantikan 8) |
| `application/views/dashboard.php` | Kartu **"Ujian baru dari guru"** (AJAX ke endpoint baru; `Dashboard.php` tidak diubah) |

Tidak ada route yang dihapus. Semua halaman lama tetap bisa dibuka; tautan yang
hilang dari sidebar disediakan di dropdown **⋮** pada halaman Daftar Ujian
(Jadwal lama, Token Global, Nomor Peserta, Cetak Kartu & Absen, Status Siswa lama).

## 3. Perubahan database (aditif)

```sql
ALTER TABLE cbt_ruang  ADD COLUMN kapasitas INT NOT NULL DEFAULT 35;
ALTER TABLE cbt_jadwal ADD COLUMN token_ujian VARCHAR(10) NULL,
                       ADD COLUMN created_by  INT NULL;
CREATE TABLE cbt_jadwal_meta (...);   -- catatan + mapel/kelas draf tanpa kumpulan soal
```

Dijalankan otomatis (idempoten, cek `field_exists`) saat model pertama dimuat,
atau manual lewat `docs/migrasi-ujian-sekolah.sql`.

`cbt_jadwal_meta` dipakai **hanya** sebagai pelengkap: ujian tanpa kumpulan soal
(draf) tetap butuh mapel/kelas, dan catatan bebas. Ujian yang punya `id_bank`
tetap menurunkan mapel/kelas dari `cbt_bank_soal` (single source of truth).

## 4. Padanan PRD → implementasi

| PRD | Implementasi |
|---|---|
| §4.1 sidebar admin 2 grup | `sidebar.php` — UJIAN SEKOLAH (3 item) + PENGATURAN UJIAN (5 item) |
| §4.2 sidebar guru 3 item | `members/guru/templates/sidebar.php` |
| §4.3 label baru | Kumpulan Soal, Pembagian Peserta, Urutan Jam Ujian, Kartu Peserta, Pantau Ujian, Cetak Kartu & Absen |
| §5.1 hub | Kartu ujian + 5 chip kesiapan + status Draf/Belum siap/Siap/Berlangsung/Selesai/Ditutup, filter Semua/Hari Ini/Akan Datang/Selesai + pencarian, banner guru, panel token aktif |
| §5.2 wizard | 3 langkah, default (acak soal ON, acak jawaban ON, hasil ON, token ON + acak, 60 menit, Sesi 1), info ujian paralel + label "bersamaan", validasi bentrok kelas, pengawas per ruang + toggle guru sama, ringkasan bahasa manusia |
| §5.3 pembagian peserta | Semua ruang dipakai, dibagi merata (42 siswa → 9/9/8/8/8), validasi kapasitas 175, tulis `cbt_sesi_siswa` + `cbt_kelas_ruang` + `cbt_nomor_peserta`, konfirmasi sebelum menimpa |
| §5.4 pantau | Tabel belum/sedang/selesai, Buka/Tutup, Reset izin, Tambah waktu |
| §5.5 token per ujian | Kolom `token_ujian`, validasi unik per tanggal, patch 1 statement `Siswa.php` |
| §5.6 kartu peserta | Tautan ke `cbtcetak` / `cbtnomorpeserta` |
| §6 pemetaan data | `cbt_jadwal` (+`jam_ke=1`, `jarak=30`), `cbt_pengawas` per ruang × Sesi 1 |
| §7 pesan validasi | Semua pesan pada tabel §7 dipakai apa adanya (satu pesan per masalah, tanpa istilah database) |
| §8 hak akses | Guru: hanya mapel/kelas `jabatan_guru`, hanya ujiannya sendiri untuk ubah/jalankan; pembagian peserta & pengawas khusus admin |
| §9 fase 0–4 | Semua dikerjakan |

## 5. Catatan keputusan teknis

1. **Status "Ditutup" tanpa kolom baru.** Dihitung dari `status` + tanggal:
   aktif & tanggal lampau → *Selesai*; nonaktif & tanggal lampau → *Ditutup*.
2. **Pembagian peserta diselang-seling per kelas** agar tiap ruang berisi siswa
   dari kelas berbeda (asumsi PRD: ujian paralel berbagi ruang) sekaligus tiap
   kelas tersebar ke semua ruang. Total per ruang tetap merata 9/9/8/8/8.
3. **`cbt_kelas_ruang`** memakai id `kelas+tp+smt+ruang` karena satu kelas kini
   bisa tersebar ke beberapa ruang (skema lama hanya muat satu ruang per kelas).
   Baris lama tetap kompatibel; halaman lama tidak membacanya untuk tampilan.
4. **Ujian yang sedang berlangsung**: server mengunci mapel, tanggal, durasi,
   jenis, dan opsi acak; token/pengawas/status masih boleh diubah.
5. **Guru tidak mengatur pengawas** (§8). Pengawas disalin otomatis dari ujian
   lain pada tanggal yang sama; bila belum ada, admin melengkapinya.
6. **Satu kumpulan soal = satu ujian** mengikuti constraint `UNIQUE(id_bank)`
   yang sudah ada di skema. Pesan errornya menjelaskan hal ini.

## 6. Hasil pengujian (dev server lokal)

| Uji | Hasil |
|---|---|
| Migrasi otomatis saat model dimuat | kolom `kapasitas`, `token_ujian`, `created_by`, tabel `cbt_jadwal_meta` terbentuk |
| Hub, wizard, pembagian, ruang & sesi, pantau, edit | HTTP 200, **0 notice/error PHP** |
| Bagi peserta otomatis | 42 siswa / 5 ruang → **9, 9, 8, 8, 8**; 42 nomor peserta `2627.07.001`–`2627.08.018`; tiap kelas tersebar ke 5 ruang |
| Dua ujian paralel (kelas 7 & 8, tanggal sama) | tersimpan; keduanya berlabel "bersamaan"; token berbeda (`XYZ789` / `ABC123`) |
| Token ganda pada tanggal sama | ditolak: "Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang." |
| Kelas bentrok pada tanggal sama | ditolak: "Kelas 7 sudah punya ujian … di tanggal ini pada jam yang sama." |
| Kumpulan soal dipakai dua ujian | ditolak |
| Durasi 300 menit / token 3 karakter | ditolak dengan pesan §7 |
| Buka ujian belum siap | ditolak: "Ujian belum siap: soal belum ada …" |
| Hapus ujian yang punya nilai | ditolak: "… Tutup saja ujiannya." |
| Guru lihat daftar | hanya ujian kelas/mapel yang dia ampu (ujian kelas 8 tidak tampil) |
| Guru buka Pembagian Peserta | HTTP 403 |
| Guru pakai kumpulan soal kelas lain | HTTP 403 |
| Guru buat ujian (bank miliknya) | tersimpan, `created_by` terisi, muncul di notifikasi admin + dashboard |
| Token SQL: ujian lama vs baru | lama → token global `HILZCX`; baru → token sendiri |
| Halaman lama (jadwal, token, bank soal, cetak, status, sesi siswa, nomor peserta, pengawas, alokasi, ruang, sesi, nilai) | HTTP 200, 0 error |

Data uji (ujian & kumpulan soal percobaan) sudah dihapus kembali.
Pembagian peserta hasil uji **dipertahankan** (42 siswa, 5 ruang) karena itu
memang keadaan yang dituju Fase 3. Keadaan sebelum uji tersimpan di
`docs/backup/sebelum-pembagian-peserta.sql`.

## 7. Yang belum dikerjakan (di luar lingkup PRD §11)

- Notifikasi WhatsApp/email.
- Perubahan halaman lama selain label sidebar.
- Aplikasi siswa / tampilan peserta ujian.
- Multi-sesi bergiliran (pola sekolah: serentak Sesi 1).
