# Ujian Sekolah — catatan implementasi (PRD v5)

Implementasi menu **Ujian Sekolah** sesuai `docs/PRD-UJIAN-SIMPEL.md` **v5**:
satu menu dengan 4 submenu, semua pengaturan teknis di **1 halaman 5 tab**,
semua hasil di **1 halaman 4 tab**, tanpa tab browser baru, dan setiap halaman
punya tombol **Kembali**.

## 1. Struktur menu (PRD 4.1 / 4.2)

```
UJIAN SEKOLAH
├─ Daftar Ujian        /ujiansekolah
├─ Kumpulan Soal       /cbtbanksoal            (halaman lama, diberi tombol Kembali)
├─ Pengaturan Ujian    /ujiansekolah/pengaturan (1 halaman, 5 tab)   [admin]
└─ Hasil Ujian         /ujiansekolah/hasil      (1 halaman, 4 tab)
```

Admin: **4 item** (menggantikan 16). Guru: **3 item** tanpa Pengaturan
(menggantikan 8). Tidak ada route lama yang dihapus — hanya baris menunya.

## 2. File baru

| File | Isi |
|---|---|
| `application/controllers/Ujiansekolah.php` | hub, wizard, pengaturan (5 tab), hasil (4 tab), pantau, aksi & AJAX |
| `application/models/Ujiansekolah_model.php` | migrasi otomatis, kesiapan, CRUD jadwal+pengawas, token, bagi peserta, sesi, jenis, pengawas per tanggal, pantau |
| `views/cbt/ujiansekolah/daftar.php` | Daftar Ujian (hub, chip kesiapan, banner guru, token) |
| `views/cbt/ujiansekolah/tambah.php` | Wizard 3 langkah (`?langkah=n` untuk lompat ke langkah yang kurang) |
| `views/cbt/ujiansekolah/pengaturan.php` | Kerangka 1 halaman + 5 tab (tanpa reload) |
| `views/cbt/ujiansekolah/_tab_peserta.php` | Tab Peserta & Ruang (bagi otomatis, kapasitas, nomor, kartu) |
| `views/cbt/ujiansekolah/_tab_sesi.php` | Tab Sesi & Jam (Sesi 1, sesi lain disembunyikan, urutan jam) |
| `views/cbt/ujiansekolah/_tab_pengawas.php` | Tab Pengawas (per ruang per tanggal, "Salin ke semua ruang") |
| `views/cbt/ujiansekolah/_tab_jenis.php` | Tab Jenis Ujian (CRUD + penanda dipakai) |
| `views/cbt/ujiansekolah/_tab_token.php` | Tab Token (token per ujian, acak ulang, token global) |
| `views/cbt/ujiansekolah/hasil.php` | Kerangka 1 halaman + 4 tab (iframe dimuat saat tab dibuka) |
| `views/cbt/ujiansekolah/pantau.php` | Pantau hari-H |
| `views/cbt/ujiansekolah/_kembali.php` | Tombol Kembali baku halaman baru |
| `views/cbt/ujiansekolah/_kembali_lama.php` | Tombol Kembali untuk halaman lama (`?dari=`) |
| `views/cbt/ujiansekolah/_style.php` | CSS modul |
| `docs/ujiansekolah.sql` | Migrasi manual (juga otomatis) |
| `docs/patch-siswa-token.md` | Dokumentasi patch 1 statement token |
| `docs/backup/` | Backup `Siswa.php` sebelum patch + dump tabel sebelum uji |

## 3. File lama yang disentuh (PRD 6.4 + 4.7)

| File | Perubahan |
|---|---|
| `application/controllers/Siswa.php` | **1 statement** token → baca `cbt_jadwal.token_ujian` + fallback |
| `views/_templates/dashboard/sidebar.php` | 16 baris menu → 4 item Ujian Sekolah |
| `views/members/guru/templates/sidebar.php` | 8 baris → 3 item |
| `views/dashboard.php` | wadah notifikasi AJAX "Ujian baru dari guru" |
| `views/cbt/banksoal/data.php` | tambah tombol Kembali (`?dari=`) |
| `views/cbt/cetak/data.php` | tambah tombol Kembali (`?dari=`) |
| `views/cbt/ruang/data.php` | tambah tombol Kembali (`?dari=`) — PRD 4.7 secara eksplisit meminta ini |
| `controllers/Nilaimanual.php`, `models/Nilaimanual_model.php`, `views/cbt/nilaimanual/data.php` | perbaikan variabel `tp_active`/`smt_active` agar halaman Nilai Manual bersih saat ditampilkan di tab Hasil Ujian |

Tidak ada controller/model obfuscated lain yang diubah.

## 4. Aturan navigasi (PRD 4.7)

- **Tidak ada** `target="_blank"` atau `window.open` di seluruh modul
  (diverifikasi: 0 kemunculan di `views/cbt/ujiansekolah/*`).
- Setiap halaman punya tombol **Kembali** di kiri atas dengan URL tetap:

| Halaman | Kembali ke |
|---|---|
| Daftar Ujian | — |
| Wizard Buat/Edit | Daftar Ujian |
| Pengaturan Ujian (5 tab) | Daftar Ujian |
| Hasil Ujian (4 tab) | Daftar Ujian |
| Pantau Ujian | Daftar Ujian |
| Kumpulan Soal, Cetak, Ruang | `?dari=` (default: Daftar Ujian) |

- Tab pada Pengaturan (5) dan Hasil (4) adalah tab **di dalam halaman**
  (Bootstrap `nav-tabs`), bukan tab browser; tidak ada reload saat berpindah tab.

## 5. Database (aditif, PRD 6.1)

```sql
ALTER TABLE cbt_ruang  ADD COLUMN kapasitas INT NOT NULL DEFAULT 35;
ALTER TABLE cbt_jadwal ADD COLUMN token_ujian VARCHAR(10) NULL,
                       ADD COLUMN created_by  INT NULL;
CREATE TABLE cbt_jadwal_meta (...);   -- pelengkap draf tanpa kumpulan soal + catatan
```

Dijalankan otomatis (idempoten) saat model dimuat, atau manual
`docs/ujiansekolah.sql`.

## 6. Padanan PRD → implementasi

| PRD | Implementasi |
|---|---|
| 4.1/4.2 sidebar 4 & 3 item | `sidebar.php` admin & guru |
| 4.3 Pengaturan 5 tab | `pengaturan.php` + 5 partial tab |
| 4.4 Hasil 4 tab | `hasil.php` (iframe halaman lama, dimuat saat tab dibuka) |
| 4.5 peta 16 menu | Semua fitur tercapai lewat 4 submenu; tidak ada yang hilang |
| 4.7 tanpa tab browser | 0 `target="_blank"`/`window.open`; tombol Kembali di semua halaman |
| 5.1 Daftar Ujian | Kartu + 5 chip + status + filter + pencarian + banner guru + token hari ini; "Lengkapi Persiapan" membuka wizard di langkah yang kurang |
| 5.2 wizard 3 langkah | `tambah.php`, default sesuai PRD, info BERSAMAAN, validasi bentrok kelas |
| 5.3 tab Peserta & Ruang | Bagi Otomatis merata (42 → 9/9/8/8/8), kapasitas 35, nomor peserta, kartu |
| 5.3 tab Sesi & Jam | Sesi 1 editable, sesi lain disembunyikan (toggle), urutan `jam_ke` |
| 5.3 tab Pengawas | Matriks tanggal × ruang, disimpan ke **semua ujian di tanggal itu**, "Salin ke semua ruang" |
| 5.3 tab Jenis Ujian | CRUD kode+nama, penanda dipakai, hapus ditolak bila dipakai |
| 5.3 tab Token | Token per ujian + acak ulang + token global cadangan |
| 5.5 token per ujian | patch 1 statement `Siswa.php` |
| 5.6 Pantau | Buka/Tutup, Reset izin, Tambah waktu |
| 7 pesan validasi | Dipakai apa adanya |
| 8 hak akses | Guru: mapel/kelas `jabatan_guru`, hanya ujiannya; `/pengaturan` → 403 |

## 7. Hasil pengujian (dev lokal)

| Uji | Hasil |
|---|---|
| Render 31 halaman (modul + 16 halaman lama) | **0 notice/error PHP** |
| Tab Pengaturan | 5 nav-tab, 5 pane, tanpa reload |
| Tab Hasil | 4 tab, iframe dimuat saat tab dibuka (lazy) |
| `target="_blank"` / `window.open` di modul | **0** |
| Tombol Kembali | wizard, pengaturan, hasil, pantau, kumpulan soal, cetak, ruang — semua ada |
| `sesisimpan` | simpan OK; jam `25:99` ditolak |
| `jamsimpan` | OK, tidak mengubah kolom lain (status tetap) |
| `pengawassimpan` | menulis ke semua ujian tanggal itu; tanggal tanpa ujian ditolak |
| `jenissimpan` / `jenishapus` | tambah & hapus OK; jenis yang dipakai ditolak dihapus |
| `tokenujian` / `tokenacak` / `tokensimpan` | token 3 karakter ditolak; acak & simpan OK; token global tersimpan |
| Guru | `/pengaturan` → **403**, `/hasil` & `/baru` → 200 |
| Syntax JS inline (9 blok) | `node --check` semua OK |
| `?langkah=2` / `?langkah=3` | wizard langsung membuka langkah tsb |

Data uji (jenis percobaan, pengawas, token percobaan) sudah dikembalikan.
Pembagian peserta (42 siswa / 5 ruang) dipertahankan karena itu keadaan yang
dituju Fase 3; keadaan sebelumnya ada di `docs/backup/`.

## 8. Di luar lingkup (PRD 11)

- Notifikasi WhatsApp/email.
- Menghapus file controller/model/view lama (hanya menu yang dihapus).
- Aplikasi sisi siswa, multi-sesi bergiliran, ujian bergiliran lewat wizard.
- Membangun ulang isi tab Hasil menjadi seragam (PRD 5.4 fase lanjut —
  sekarang memakai halaman lama di dalam tab).
