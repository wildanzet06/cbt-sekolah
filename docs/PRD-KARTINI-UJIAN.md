# PRD — Pengelolaan Ujian Bergaya KARTINI-CBT

Status: **Final v2 — keputusan lengkap, implementasi belum dimulai**
Tanggal: 19 November 2026
Sumber: https://github.com/ilgasunanda11-cmyk/KARTINI-CBT (dibaca 19 November 2026)
Dokumen terkait: `docs/CATATAN-UJIAN-SEKOLAH.md` (catatan modul Ujian Sekolah yang sudah ada)

> `docs/PRD-UJIAN-SIMPEL.md` (v5) tidak lagi ditemukan di workspace saat dokumen ini disusun. Keputusan v5 yang masih berlaku — struktur 1 menu/4 submenu, tanpa tab browser baru, token 6 karakter, 7 kategori ujian — dituliskan ulang di bagian 0 agar PRD ini berdiri sendiri.

---

## 0. Keputusan final

| # | Hal | Keputusan |
|---|---|---|
| 1 | Sifat dokumen | **PRD final. Tidak ada kode yang diubah.** |
| 2 | Stack & database | **Tetap CodeIgniter 3 + MySQL.** Alur dan tampilan KARTINI ditiru, bukan stack-nya (4.1 A). |
| 3 | Siswa masuk ujian | **Portal kode ujian tetap ada, tapi tetap verifikasi nama/NIS** (4.2 C). |
| 4 | Ruang & pembagian peserta | **Tetap dipakai, disembunyikan di pengaturan lanjutan** (4.3 C). |
| 5 | Jadwal | **Tanggal saja + Sesi 1.** Ujian di tanggal sama berjalan bersamaan (4.4 A). |
| 6 | Pengawas | **Tetap di tab Pengawas, dan monitoring ditambahkan** (4.5 A). |
| 7 | Cetak | **Cetak absen + berita acara tetap. Cetak kartu peserta dihapus** (4.6 — keputusan final). |
| 8 | Form & menu | **Form ala KARTINI + tombol "Pengaturan lengkap"**, bukan wizard (4.7 C). |
| 9 | Hasil ujian | **4 tab dipertahankan + Koreksi Essay + Ekspor CSV. Nilai Manual tetap ada** (4.8 A + catatan lampiran). |
| 10 | Monitoring | **Polling AJAX + alarm audio** (4.9 A). WebSocket tidak dipakai. |
| 11 | Kategori ujian | **7 tetap**: PH, PTS, PAS, PAT, UMBK, TO, SIML. |
| 12 | Jenis soal | **5 jenis KARTINI ditambahkan**: PG, PG Kompleks, Benar/Salah, Menjodohkan, Essay. |
| 13 | Token/kode ujian | **6 karakter huruf+angka, beda tiap ujian**, memakai `cbt_jadwal.token_ujian`. |
| 14 | Navigasi | **Tanpa tab browser baru.** Semua halaman dibuka di tab yang sama dengan tombol Kembali. |
| 15 | Lisensi | Repo KARTINI tanpa lisensi → **meniru alur & tampilan, tidak menyalin source code**. |

---

## 1. Ringkasan KARTINI-CBT

### 1.1 Fakta teknis

| Aspek | KARTINI-CBT |
|---|---|
| Frontend | React + TypeScript + Tailwind CSS + Vite |
| Backend | Node.js 22 + Express + REST + JWT |
| Database | SQLite (`data/`, file `db.ts`) |
| Real-time | WebSocket (`/ws`) untuk monitoring lintas-tab |
| File utama | `src/components/ExamAndQuestionManager.tsx` (≈56 KB), `server.ts` (≈77 KB), `StudentExamFlow.tsx` (≈72 KB) |
| Mode tambahan | GitHub Pages statis via `staticCbtEngine.ts` + `localStorage`/`BroadcastChannel` |
| Akun contoh | admin / admin123 · guru.mtk, guru.ipa / guru123 |
| Kode ujian contoh | `MTK9A26`, `IPA8A26` |

### 1.2 Alur pengelolaan ujian (yang ditiru)

Semua ada di **satu halaman**, tanpa wizard dan tanpa banyak menu:

1. **Form "Buat Ujian Baru"** di atas, tabel daftar ujian di bawah.
2. Isian form:

| Isian | Keterangan |
|---|---|
| Judul Ujian | teks bebas, contoh "Penilaian Harian Bab 3" |
| Mata Pelajaran | pilih dari daftar |
| **Kode Ujian (Token Siswa)** | 6 karakter, tombol acak memakai alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (tanpa I, O, 0, 1) |
| Durasi (menit) | angka, default 60 |
| Jadwal Mulai / Selesai | opsional; di aplikasi kita tetap **tanggal saja** (keputusan #5) |
| Kelas | pilih chip; admin = semua kelas, guru = hanya kelas yang diizinkan admin |
| Acak Soal | checkbox, default ON |
| Acak Opsi Jawaban | checkbox, default ON |
| Tampilkan Nilai | checkbox, default ON |
| Aktifkan Ujian | checkbox, default ON |
| Tombol | **Terbitkan Ujian** / **Simpan Perubahan** |

3. Tabel ujian: Kode · Judul & Mapel · Pembuat · Kelas · Jumlah Soal · Durasi · Status · Aksi.
4. Aksi **"Kelola Soal"** langsung membuka editor soal **di halaman yang sama** — tidak membuka tab browser baru.
5. Editor soal menyatu untuk **5 jenis soal**:

| Kode | Jenis |
|---|---|
| `pg` | Pilihan Ganda (1 kunci) |
| `pg_kompleks` | Pilihan Ganda Kompleks (>1 kunci) |
| `benar_salah` | Benar / Salah (pernyataan berulang) |
| `menjodohkan` | Menjodohkan (kiri–kanan) |
| `essay` | Essay + kunci/pedoman penilaian |

6. Tiap soal punya **bobot** (`weight`) dan bisa dilampiri **gambar** atau **audio**.
7. Guru bisa membuat dan mengaktifkan ujiannya sendiri tanpa persetujuan admin.

### 1.3 Alur siswa di KARTINI

- Halaman depan tanpa login: siswa memasukkan kode ujian.
- Saat ujian: monitoring real-time, alarm anti-cheating, timer.
- Hasil: ekspor CSV, koreksi essay oleh guru.

### 1.4 Yang tidak ada di KARTINI

Ruang, kapasitas ruang, pembagian peserta ke ruang, sesi, pengawas, cetak absen/berita acara, kategori ujian (PH/PTS/PAS/…), dan nilai manual. Sesuai keputusan #3, #4, #6, #7, #9, **semua itu tetap ada di aplikasi kita** kecuali kartu peserta.

---

## 2. Kondisi aplikasi kita saat ini

| Aspek | Kondisi |
|---|---|
| Stack | CodeIgniter 3 (PHP), MySQL, Bootstrap, jQuery |
| Modul ujian | `application/controllers/Ujiansekolah.php` + model + views di `application/views/cbt/ujiansekolah/` (`daftar.php`, `tambah.php`, `pengaturan.php`, `hasil.php`, `pantau.php`, `_tab_*.php`, `_kembali.php`) |
| Sidebar admin | **UJIAN SEKOLAH** → Daftar Ujian · Kumpulan Soal · Pengaturan Ujian · Hasil Ujian |
| Sidebar guru | **UJIAN SEKOLAH** → Daftar Ujian · Kumpulan Soal · Hasil Ujian |
| Navigasi | Tidak ada `target="_blank"` di modul ujian; halaman memakai `_kembali.php` |
| Form ujian | Masih wizard/langkah terpisah — **belum** form tunggal ala KARTINI |
| Kategori ujian | 7 baris di `cbt_jenis`: PH, PTS, PAS, PAT, UMBK, TO, SIML |
| Soal | `cbt_soal`: `opsi_a`…`opsi_e`, `jawaban`, `file`, `file1`, `tipe_file`; `jenis` = 1 PG, 2 Essay |
| Bank soal | `cbt_bank_soal` sudah punya `jml_esai`, `jml_kompleks`, `tampil_esai`, `tampil_kompleks`, `bobot_pg`, `bobot_esai` |
| Jawaban siswa | `cbt_soal_siswa`: `jawaban_siswa`, `jawaban_benar`, `point_essai`, `nilai_koreksi`, `nilai_otomatis` |
| Token | `cbt_token` global 1 baris; `cbt_jadwal.token` flag; `cbt_jadwal.token_ujian` sudah ada |
| Siswa | Login + verifikasi token di `Siswa.php` |
| Ruang | 5 ruang, `cbt_ruang.kapasitas` = 35 |

**Artinya:** fondasi Essay dan PG Kompleks sebagian sudah ada di database. Yang benar-benar baru dari KARTINI: **Benar/Salah**, **Menjodohkan**, **editor soal menyatu**, **upload gambar/audio**, dan **bobot per soal**.

---

## 3. Perbandingan dan keputusan

| Aspek | Aplikasi kita | KARTINI | Keputusan final |
|---|---|---|---|
| Stack | PHP / CodeIgniter | React + Node | Tetap PHP/CodeIgniter |
| Database | MySQL | SQLite | Tetap MySQL |
| Struktur | 4 submenu (admin), 3 (guru) | 1 halaman | 4/3 submenu tetap; isi Daftar Ujian ala KARTINI |
| Buat ujian | Wizard/langkah | 1 form langsung | Form KARTINI + "Pengaturan lengkap" |
| Kode ujian | 6 char per ujian | 6 char | Sama — `token_ujian` |
| Jadwal | Tanggal, Sesi 1 | Datetime opsional | Tanggal + Sesi 1 |
| Kelas | dari bank soal | dipilih di form | Default dari bank, bisa diubah; guru dibatasi izin |
| Kategori ujian | 7 | tidak ada | 7 tetap |
| Jenis soal | PG + Essay (sebagian) | 5 jenis | 5 jenis ditambahkan |
| Media soal | kolom `file` | gambar + audio | Tambah upload audio |
| Bobot soal | `point_soal` di jawaban siswa | `weight` di soal | Tambah `bobot` di `cbt_soal` |
| Ruang & peserta | 5 ruang, 35/kursi | tidak ada | Tetap, di pengaturan lanjutan |
| Pengawas | 1 guru/ruang/tanggal | tidak ada | Tetap + monitoring |
| Cetak | kartu + absen + berita acara | tidak ada | **Absen + berita acara tetap; kartu dihapus** |
| Siswa masuk | login + token | tanpa login | Portal kode + verifikasi nama/NIS |
| Monitoring | `cbtstatus` | WebSocket + alarm | Polling + alarm |
| Hasil | 4 tab | CSV + koreksi essay | 4 tab + Koreksi Essay + CSV; Nilai Manual tetap |

---

## 4. Rincian keputusan

### 4.1 Stack dan database — A

Tetap **CodeIgniter 3 + MySQL**. Modul keuangan, presensi, rapor, dan nilai tidak perlu disentuh. Tidak ada server Node yang dijalankan. Konsekuensi: monitoring memakai polling, bukan WebSocket.

### 4.2 Login siswa — C

Disediakan **halaman portal kode ujian** (tanpa login) seperti KARTINI, tetapi siswa tetap **memilih/mengisi nama atau NIS** yang diverifikasi terhadap kelas yang diizinkan ujian. Identitas siswa tetap tercatat di `cbt_soal_siswa`. Dibutuhkan pemetaan kode ujian → `id_jadwal` dan penyesuaian 1 titik di `Siswa.php`.

### 4.3 Ruang, kapasitas, pembagian peserta — C

Ruang (5 × 35), pembagian merata 9/9/8/8/8, dan nomor peserta **tetap dipakai**, tetapi **tidak ditampilkan di form ujian**. Semuanya masuk **Pengaturan lanjutan** / tab **Peserta & Ruang**. Form utama sengaja dibuat sesederhana KARTINI.

### 4.4 Sesi dan jadwal — A

Tanggal saja, `jam_ke = 1`, Sesi 1. Ujian dengan tanggal sama berjalan bersamaan. Tidak ada isian datetime mulai/selesai di form utama. Konsekuensi: tidak ada ujian bergiliran dalam satu hari melalui form ini.

### 4.5 Pengawas — A

Pengawas tetap di tab **Pengawas** (1 guru per ruang per tanggal, dipakai bersama ujian paralel) untuk kebutuhan absen dan berita acara. Panel monitoring KARTINI ditambahkan di halaman **Pantau**, bukan menggantikan pengawas.

### 4.6 Cetak — keputusan final

- **Dipertahankan:** cetak **absen** dan **berita acara** (termasuk daftar pengawas). Keduanya tetap jadi aksi di baris tabel Daftar Ujian, bukan menu tersendiri.
- **Dihapus:** cetak **kartu peserta**. Tombol/link kartu peserta tidak lagi ditawarkan di modul ujian.
- File lama cetak kartu boleh tetap ada di repo (tidak dipanggil dari alur baru), agar tidak memutus tautan dari modul lain.

### 4.7 Struktur menu dan form — C

4 submenu tetap. Submenu **Daftar Ujian** berisi form tunggal ala KARTINI + tabel. Tombol **"Pengaturan lengkap"** menuju submenu **Pengaturan Ujian** di tab browser yang sama, dengan tombol Kembali. Wizard 3 langkah tidak dipakai.

### 4.8 Hasil ujian — A

Enam tab: **Nilai Ujian · Analisis Soal · Rekap Nilai · Nilai Manual · Koreksi Essay · Ekspor CSV**.
Nilai Manual dipertahankan sesuai catatan lampiran. Koreksi essay memakai kolom yang sudah ada (`point_essai`, `nilai_koreksi`, `nilai_otomatis`).

### 4.9 Monitoring dan anti-cheating — A

Halaman **Pantau** tetap memakai polling AJAX, lalu ditambah **alarm audio** seperti KARTINI. Tidak memasang WebSocket karena stack tetap PHP/MySQL. Perlu perhatian izin audio pada browser.

---

## 5. Desain usulan

### 5.1 Daftar Ujian

```
┌──────────────────────────────────────────────────────────────────────┐
│ Buat & Kelola Ujian                          [Pengaturan lengkap →]   │
│ Satu halaman untuk membuat ujian, mengatur kode, dan mengelola soal. │
├──────────────────────────────────────────────────────────────────────┤
│ Judul Ujian            Mapel        Kode Ujian (Token)   [acak]      │
│ [__________________]   [Mapel ▾]    [ABC234]  [🔑]                   │
│ Durasi    Tanggal Ujian              Kategori Ujian                  │
│ [60____]  [20 Nov 2026]              [PTS ▾]                         │
│ Kelas:  [7] [8]   (admin: semua · guru: sesuai izin)                 │
│ ☑ Acak Soal  ☑ Acak Opsi  ☑ Tampilkan Nilai  ☑ Aktifkan Ujian        │
│                                            [ Terbitkan Ujian ]       │
├──────────────────────────────────────────────────────────────────────┤
│ Kode   Judul & Mapel   Pembuat  Kelas  Soal  Durasi  Status   Aksi   │
│ ABC234 PTS Matematika  Admin    7, 8   40    60      Aktif   [Kelola │
│                                                              Soal]  │
│                                            [Pantau] [Cetak Absen]    │
│                                            [Edit] [Hapus]            │
└──────────────────────────────────────────────────────────────────────┘
```

- Tombol **"Kelola Soal"** memuat editor soal di area yang sama (AJAX), **tanpa tab browser baru**.
- Tombol **"Pengaturan lengkap"**, **Pantau**, dan **Cetak Absen** dibuka di tab yang sama; tiap halaman tujuan punya tombol Kembali.
- **Tidak ada** tombol Cetak Kartu.
- Banner notifikasi ujian buatan guru tetap muncul di atas.

### 5.2 Pemetaan form ujian → database

| Form KARTINI | Kolom kita |
|---|---|
| Judul Ujian | `cbt_jadwal` + `cbt_jadwal_meta.judul` |
| Mata Pelajaran | `id_bank` → `cbt_bank_soal.bank_mapel_id` |
| Kategori Ujian (tambahan kita) | `id_jenis` (PH…SIML) |
| Kode Ujian (Token Siswa) | `cbt_jadwal.token_ujian` (6 karakter) + flag `token = 1` |
| Durasi | `cbt_jadwal.durasi_ujian` |
| Tanggal | `tgl_mulai` = `tgl_selesai`, `jam_ke = 1` |
| Kelas | `bank_kelas` / filter `jabatan_guru` |
| Acak Soal | `acak_soal` |
| Acak Opsi | `acak_opsi` |
| Tampilkan Nilai | `hasil_tampil` |
| Aktifkan Ujian | `status` |
| Pembuat | `created_by` |

### 5.3 Kelola Soal — 5 jenis

Editor satu form dengan pilihan jenis di atas:

| Jenis | Input | Kunci | Penilaian |
|---|---|---|---|
| Pilihan Ganda | `opsi_a`…`opsi_e` | `jawaban` = 1 huruf | otomatis |
| PG Kompleks | `opsi_a`…`opsi_e`, banyak kunci | `jawaban` = `["A","C"]` | otomatis (semua kunci benar) |
| Benar/Salah | `opsi_json` = daftar pernyataan | `jawaban` = `{"1":"Benar","2":"Salah"}` | otomatis |
| Menjodohkan | `opsi_json` = pasangan kiri–kanan | `jawaban` = `{"1":"Jawaban A"}` | otomatis |
| Essay | teks pertanyaan | `jawaban` = pedoman penilaian | manual via `nilai_koreksi` |

- Gambar memakai kolom `file` yang sudah ada; audio memakai kolom baru `media_audio`.
- Bobot memakai kolom baru `bobot`; `cbt_soal_siswa.point_soal` diisi dari `bobot`.
- Kategori ujian (7) dipilih di form ujian, terpisah dari jenis soal.

### 5.4 Pengaturan Ujian

Lima tab dipertahankan: **Peserta & Ruang · Sesi & Jam · Pengawas · Jenis Ujian · Token**.
Tab Peserta & Ruang berisi ruang, kapasitas, pembagian peserta, dan nomor peserta — sesuai keputusan #4 (disembunyikan dari form utama, bukan dihapus).

### 5.5 Hasil Ujian

Enam tab: **Nilai Ujian · Analisis Soal · Rekap Nilai · Nilai Manual · Koreksi Essay · Ekspor CSV**.

---

## 6. Perubahan data (aditif)

```sql
-- 1. Jenis soal baru. Sudah ada: 1 = PG, 2 = Essay di kolom `jenis`.
ALTER TABLE cbt_soal
  ADD COLUMN tipe_soal VARCHAR(20) NOT NULL DEFAULT 'pg';
--    Nilai: pg | pg_kompleks | benar_salah | menjodohkan | essay

-- 2. Opsi untuk Benar/Salah dan Menjodohkan (JSON).
ALTER TABLE cbt_soal
  ADD COLUMN opsi_json LONGTEXT NULL DEFAULT NULL;

-- 3. Bobot soal (KARTINI: weight).
ALTER TABLE cbt_soal
  ADD COLUMN bobot DECIMAL(5,2) NOT NULL DEFAULT 1.00;

-- 4. Lampiran audio (gambar memakai kolom `file` yang sudah ada).
ALTER TABLE cbt_soal
  ADD COLUMN media_audio VARCHAR(255) NULL DEFAULT NULL;

-- 5. Alias opsi untuk pengacakan jenis soal baru.
ALTER TABLE cbt_soal_siswa
  ADD COLUMN opsi_alias_json LONGTEXT NULL DEFAULT NULL;
```

Catatan:
- `jawaban_siswa` (longtext) cukup untuk jawaban teks/JSON kelima jenis soal.
- `point_essai`, `nilai_koreksi`, `nilai_otomatis` sudah ada — koreksi essay tidak butuh tabel baru.
- `cbt_jadwal.token_ujian` sudah ada — kode KARTINI tidak butuh kolom baru.
- Belum ada perubahan schema yang dijalankan. `docs/database_gabungan.sql` **belum** memuat usulan di atas sampai implementasi dimulai.

---

## 7. Fase implementasi

| Fase | Isi | Catatan |
|---|---|---|
| **1** | Form ujian + tabel ala KARTINI di Daftar Ujian; tombol Kelola Soal di halaman yang sama; aksi Pantau & Cetak Absen; hapus tombol Cetak Kartu; tanpa tab browser baru | Fitur ruang/pengawas tetap lewat tombol Pengaturan lengkap |
| **2** | Editor 5 jenis soal + upload gambar/audio + bobot | Migrasi aditif bagian 6 |
| **3** | Kode 6 karakter per ujian + portal siswa dengan verifikasi nama/NIS | Patch 1 titik di `Siswa.php`; backup dulu |
| **4** | Koreksi Essay + Ekspor CSV; Nilai Manual tetap | Memakai kolom yang sudah ada |
| **5** | Monitoring polling + alarm audio di halaman Pantau | Perlu izin audio browser |

---

## 8. Risiko dan mitigasi

| Risiko | Mitigasi |
|---|---|
| Timpa stack ke React/Node | Tidak dilakukan — keputusan #2 tetap PHP/MySQL |
| Tab browser baru muncul lagi | Kelola Soal/Pengaturan/Pantau/Cetak memakai AJAX atau navigasi tab yang sama + tombol Kembali |
| 7 kategori tertimpa 5 jenis soal | Dua konsep dibedakan: kategori di form ujian, jenis di editor soal |
| Jawaban lama rusak | Kolom baru aditif; `jenis = 1` tetap PG, `jenis = 2` tetap Essay |
| Tautan Cetak Kartu masih tersisa | Hapus dari view alur baru; file lama boleh tetap di repo bila dipanggil modul lain |
| Portal kode melemahkan identitas siswa | Tetap verifikasi nama/NIS (keputusan #3) + pembatasan kelas |
| Kode ujian kembar | Validasi unik per tanggal |
| Alarm tidak berbunyi | Minta izin audio browser; tampilkan petunjuk bila diblokir |
| Pelanggaran lisensi | Meniru alur/UI, tidak menyalin file source KARTINI |

---

## 9. Definisi selesai

- Form **Daftar Ujian** terlihat dan berperilaku seperti KARTINI (satu form + tabel), tanpa tab browser baru.
- Tombol **Kelola Soal** membuka editor di halaman yang sama.
- **7 kategori ujian** tetap tersedia; **5 jenis soal** bisa dibuat, dikerjakan, dan dinilai.
- Kode ujian **6 karakter** unik per ujian; portal kode ujian berjalan dengan verifikasi nama/NIS.
- Ruang, kapasitas, pembagian peserta, dan pengawas tetap berfungsi lewat Pengaturan lengkap.
- **Cetak absen dan berita acara tetap ada; cetak kartu peserta tidak lagi ditawarkan.**
- **Nilai Manual tetap ada**, plus Koreksi Essay dan Ekspor CSV.
- Monitoring **polling + alarm** berjalan di halaman Pantau.
- Setiap halaman punya tombol **Kembali**; tidak ada `target="_blank"` di modul ujian.
- Tidak ada source code KARTINI yang disalin ke repo ini.
