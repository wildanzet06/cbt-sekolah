# PRD — Menu "Ujian Sekolah" (bangun ulang)

Status: **Draf v5 — struktur menu baru + aturan navigasi tanpa tab browser**
Tanggal: 15 November 2026
Lingkup: menu **Data Ujian** + **Pelaksanaan Ujian** (admin) dan **Ulangan / Ujian** (guru)
File: `docs/PRD-UJIAN-SIMPEL.md`

Riwayat: v3 = 2 grup menu · v4 = 1 menu 4 submenu · **v5 = aturan "tanpa tab browser baru, cukup tombol Kembali"** (dikonfirmasi: yang dimaksud tab browser, bukan tab internal)

---

## 0. Keputusan

### 0.1 Keputusan sebelumnya (tetap berlaku)

| # | Hal | Keputusan |
|---|---|---|
| 1 | Kapasitas ruang | **35 siswa/ruang** → 5 ruang × 35 = 175 kursi |
| 2 | Ruang dipakai | **Semua 5 ruang**, peserta disebar merata |
| 3 | Guru buat ujian | **Boleh**, dibatasi mapel/kelas di `jabatan_guru` |
| 4 | Persetujuan admin | **Tidak perlu** — cukup pemberitahuan (dashboard + banner) |
| 5 | Jenis ujian | **Tetap 7** |
| 6 | Nama menu | **"Ujian Sekolah"** |
| 7 | Dua ujian di tanggal sama | **Bersamaan** (paralel, beda kelas) |
| 8 | Token | **Beda tiap ujian**, 6 karakter huruf + angka |

### 0.2 Keputusan baru (v4)

| Hal | Keputusan |
|---|---|
| Struktur menu | **Satu menu "Ujian Sekolah"** dengan **4 submenu**: Daftar Ujian · Kumpulan Soal · Pengaturan Ujian · Hasil Ujian |
| Menu lama | **Dihapus dari sidebar dan dibangun ulang** dalam 4 submenu tersebut |
| Pengaturan | Semua pengaturan teknis (sesi, ruang, peserta, pengawas, jenis, token, urutan jam) **disatukan dalam satu halaman bertab** "Pengaturan Ujian" |
| Hasil | Nilai, analisis, rekap, nilai manual **disatukan dalam satu halaman bertab** "Hasil Ujian" |
| Navigasi | **Tidak ada tab browser baru.** Semua halaman dibuka di tab yang sama, dengan tombol **Kembali** ke halaman induk |

> Catatan nama: kamu menulis "ujian siswa". Di PRD ini saya pakai **"Ujian Sekolah"** sesuai keputusan #6. Kalau memang mau labelnya "Ujian Siswa", tinggal ganti teks — strukturnya sama.

### 0.3 Asumsi

- Satu kelas **tidak** mengerjakan dua ujian pada saat bersamaan.
- Ujian paralel memakai ruang yang sama; satu ruang bisa berisi siswa dari kelas berbeda.
- Pengawas dihitung **per ruang per tanggal**, dipakai bersama ujian paralel.
- Jam mulai mengikuti **Sesi 1**; ujian dibatasi tanggal, bukan jam.

---

## 1. Ringkasan

Saat ini admin menghadapi **16 menu** untuk urusan ujian (10 di *Data Ujian* + 6 di *Pelaksanaan Ujian*). Guru melihat 8 menu. Strukturnya mengikuti nama tabel database, bukan alur kerja, sehingga pengguna awam tidak tahu harus mulai dari mana.

v4 mengubah pendekatan: **bukan menata ulang menu lama, tapi menghapus dan membangun ulang** menjadi satu menu dengan 4 submenu. Pengaturan dan hasil yang tadinya 12 menu terpisah menjadi **2 halaman bertab**.

| Sekarang | Sesudah |
|---|---|
| 16 item menu berderet di 2 grup | **1 menu, 4 submenu** |
| 12 menu pengaturan & hasil terpisah | 2 halaman (Pengaturan Ujian, Hasil Ujian) dengan tab |
| 15–20 field form jadwal | Wizard 3 langkah, 6–8 isian |
| Tidak tahu apa yang kurang | Checklist kesiapan otomatis |
| Token 1 untuk semua ujian | Token per ujian, aman untuk ujian paralel |

---

## 2. Kondisi sekarang

### 2.1 Menu berjalan

**Admin — "Data Ujian" (10):** `Jenis Ujian` · `Sesi` · `Ruang` · `Atur Ruang/Sesi` · `Nomor Peserta` · `Bank Soal` · `Jadwal` · `Alokasi Waktu` · `Pengawas` · `Token`

**Admin — "Pelaksanaan Ujian" (6):** `Cetak` · `Status Siswa` · `Hasil Ujian` · `Analisis Soal` · `Rekap Nilai` · `Nilai Manual`

**Guru — "Ulangan / Ujian" (8):** `Bank Soal` · `Jadwal` · `Cetak` · `Status Siswa` · `Hasil Ujian` · `Analisis Soal` · `Rekap Nilai` · `Nilai Manual`

### 2.2 Masalah

1. **Menu = tabel database.** Sesi, Ruang, Atur Ruang/Sesi, Nomor Peserta = satu pekerjaan, dipecah 4 menu.
2. **Urutan tidak terlihat.** Tidak ada penanda langkah atau status "belum lengkap".
3. **Istilah teknis.** *Bank Soal*, *Alokasi Waktu* (isinya "jam ke"), *Atur Ruang/Sesi*, *Expired*, *Reset Izin*.
4. **Pengaturan langka sejajar dengan yang wajib.** Alokasi Waktu (opsional) sama menonjolnya dengan Jadwal.
5. **Hasil tersebar di 5 menu** padahal pertanyaannya satu: "ujiannya bagaimana?".

### 2.3 Batasan teknis

| Temuan | Dampak |
|---|---|
| `cbt_token` hanya 1 baris global; `cbt_jadwal.token` cuma flag 0/1 | Token per ujian butuh kolom baru + patch 1 baris di `Siswa.php` |
| `cbt_sesi_siswa` = pembagian siswa per **tp/smt**, bukan per ujian | Bagi peserta cukup sekali per semester |
| `tgl_mulai/tgl_selesai` varchar **tanggal saja** | Ujian di tanggal sama otomatis bersamaan (Sesi 1) |
| 3 sesi tersimpan, pola sekolah 1 sesi | Sesi 2 & 3 disembunyikan |
| `cbt_jadwal.pengawas` legacy; pengawas di `cbt_pengawas` | Wizard menulis ke `cbt_pengawas` |
| Controller/model CBT lama **obfuscated** | Fitur baru di file baru; hanya 1 baris `Siswa.php` disentuh |
| `cbt_ruang` tanpa kapasitas | Tambah kolom `kapasitas` default 35 |
| `bank_kelas` = array PHP terserialisasi | Kelas peserta dari kumpulan soal |
| `cbt_jadwal` tanpa `id_kelas`/`id_mapel`/`created_by` | Mapel/kelas ikut bank; perlu `created_by` |

### 2.4 Data saat ini

2 kelas (7, 8) · 42 siswa · 42 mapel · 1 guru · 1 bank soal · 11 soal · 7 jenis · 3 sesi · 5 ruang · 1 jadwal · 24 baris `cbt_sesi_siswa` · 24 nomor peserta · 0 pengawas · 1 token.

Kapasitas: **175 kursi** untuk **42 siswa** → 5 ruang terpakai, ± 8–9 siswa/ruang.

---

## 3. Prinsip

1. **Satu menu untuk semua urusan ujian.**
2. **Empat submenu = empat pertanyaan pengguna:** ujian apa saja? soalnya mana? pengaturannya di mana? hasilnya bagaimana?
3. **Pengaturan teknis tidak memenuhi sidebar.** Semua masuk tab.
4. **Default wajar.** Acak soal ON, acak jawaban ON, tampilkan hasil ON, token ON otomatis, 60 menit, Sesi 1, semua ruang.
5. **Bangun ulang tanpa memutus siswa.** Halaman lama tetap hidup sampai penggantinya terbukti jalan.
6. **Bahasa manusia.** Tiap halaman punya 1 baris penjelas.

---

## 4. Struktur menu baru

### 4.1 Admin

```
UJIAN SEKOLAH
├─ Daftar Ujian        ujiansekolah            ← hub + wizard + token + pantau
├─ Kumpulan Soal       cbtbanksoal             ← label baru, halaman lama
├─ Pengaturan Ujian    ujiansekolah/pengaturan ← 1 halaman, 5 tab
└─ Hasil Ujian         ujiansekolah/hasil      ← 1 halaman, 4 tab
```

**4 item** menggantikan **16 item**.

### 4.2 Guru

```
UJIAN SEKOLAH
├─ Daftar Ujian
├─ Kumpulan Soal
└─ Hasil Ujian
```

Guru tidak melihat **Pengaturan Ujian** (diatur admin). **3 item** menggantikan **8 item**.

### 4.3 Isi "Pengaturan Ujian" (5 tab)

| Tab | Menggantikan menu | Isi |
|---|---|---|
| **Peserta & Ruang** | Sesi · Ruang · Atur Ruang/Sesi · Nomor Peserta | Kapasitas & daftar ruang, pembagian peserta, **Bagi Otomatis**, nomor peserta, kartu (opsional) |
| **Sesi & Jam** | Sesi · Alokasi Waktu | Sesi 1 aktif + waktu mulai/akhir; Sesi 2–3 disembunyikan; urutan jam (`jam_ke`) hanya bila perlu |
| **Pengawas** | Pengawas | 1 guru per ruang per tanggal; disalin otomatis ke ujian paralel |
| **Jenis Ujian** | Jenis Ujian | CRUD 7 jenis |
| **Token** | Token | Token per ujian hari ini, acak ulang, token global cadangan |

Kelima tab ada dalam **satu halaman** (pindah tab tanpa memuat ulang halaman penuh) dan **tidak** membuka tab browser.

### 4.4 Isi "Hasil Ujian" (4 tab)

| Tab | Menggantikan |
|---|---|
| **Nilai Ujian** | Hasil Ujian |
| **Analisis Soal** | Analisis Soal |
| **Rekap Nilai** | Rekap Nilai |
| **Nilai Manual** | Nilai Manual |

Keempat tab juga berada dalam satu halaman, bukan tab browser.

### 4.5 Peta lengkap 16 menu lama → baru

| Menu lama | Pindah ke |
|---|---|
| Jenis Ujian | Pengaturan Ujian › **Jenis Ujian** |
| Sesi | Pengaturan Ujian › **Sesi & Jam** |
| Ruang | Pengaturan Ujian › **Peserta & Ruang** |
| Atur Ruang/Sesi | Pengaturan Ujian › **Peserta & Ruang** (Bagi Otomatis) |
| Nomor Peserta | Pengaturan Ujian › **Peserta & Ruang** (Nomor & Kartu) |
| Alokasi Waktu | Pengaturan Ujian › **Sesi & Jam** (Urutan) |
| Pengawas | Pengaturan Ujian › **Pengawas** |
| Token | Pengaturan Ujian › **Token** + isian token di wizard |
| Bank Soal | **Kumpulan Soal** |
| Jadwal | **Daftar Ujian** |
| Cetak | Daftar Ujian › aksi **Cetak Kartu & Absen** |
| Status Siswa | Daftar Ujian › aksi **Pantau** |
| Hasil Ujian | Hasil Ujian › **Nilai Ujian** |
| Analisis Soal | Hasil Ujian › **Analisis Soal** |
| Rekap Nilai | Hasil Ujian › **Rekap Nilai** |
| Nilai Manual | Hasil Ujian › **Nilai Manual** |

Tidak ada fitur yang hilang.

### 4.6 Strategi "hapus dan bangun ulang"

Yang **dihapus**: seluruh baris menu lama di kedua sidebar (16 baris admin, 8 baris guru), diganti 4/3 baris baru.

Yang **dibangun baru**:
- `application/controllers/Ujiansekolah.php`
- `application/models/Ujiansekolah_model.php`
- `application/views/cbt/ujiansekolah/{daftar,tambah,pengaturan,hasil}.php` + partial tab
- `docs/ujiansekolah.sql` (migrasi kolom)

Yang **dipakai ulang sementara** (sampai tab penggantinya jadi):
- `cbtbanksoal` (Kumpulan Soal)
- halaman lama di balik tab Pengaturan Ujian & Hasil Ujian

Yang **tidak dihapus**: file controller/model/view lama. Menu-nya hilang, filenya tetap ada. Alasannya: banyak tautan silang (cetak, hasil, siswa) yang masih memanggilnya. Penghapusan file dilakukan hanya setelah semua tab baru terbukti dan tidak ada referensi — di luar lingkup PRD ini.

### 4.7 Aturan navigasi

**Tidak ada tab browser baru.** Semua perpindahan halaman terjadi di tab yang sama.

| Dilarang | Diganti dengan |
|---|---|
| `target="_blank"` | link biasa + tombol **Kembali** di halaman tujuan |
| `window.open(...)` | navigasi biasa di tab yang sama |
| Tombol "buka di jendela baru" | tombol **Kembali** |

**Setiap halaman punya tombol Kembali** di kiri atas (ikon panah + "Kembali"), menuju halaman induknya dengan URL tetap — bukan mengandalkan riwayat browser. Tombol back browser tetap berfungsi, tapi tidak dijadikan satu-satunya jalan pulang.

| Halaman | Kembali ke |
|---|---|
| Daftar Ujian | — (halaman utama menu) |
| Wizard Buat / Edit Ujian | Daftar Ujian |
| Pengaturan Ujian | Daftar Ujian |
| › tab Peserta & Ruang, Sesi & Jam, Pengawas, Jenis, Token | Pengaturan Ujian |
| Hasil Ujian | Daftar Ujian |
| › tab Nilai, Analisis, Rekap, Nilai Manual | Hasil Ujian |
| Pantau Ujian | Daftar Ujian |
| Cetak Kartu & Absen | Daftar Ujian |
| Kumpulan Soal | Daftar Ujian |
| Halaman lama yang dibuka dari tab | tab asal (lewat parameter `?dari=`) |

**Catatan tentang "tab" di dalam halaman:** tab pada Pengaturan Ujian (5 tab) dan Hasil Ujian (4 tab) adalah tab **di dalam halaman**, bukan tab browser — **tetap dipakai**, karena tidak membuka jendela baru. Yang dilarang hanya tab browser.

**Contoh penerapan pada tombol yang ada sekarang** (belum diubah, masih `target="_blank"`):

| File | Baris | Tautan | Perbaikan |
|---|---|---|---|
| `views/cbt/ujiansekolah/pantau.php` | 38 | `cbtstatus` | tab yang sama + Kembali |
| `views/cbt/ujiansekolah/pembagian.php` | 103 | `cbtnomorpeserta` | tab yang sama + Kembali |
| `views/cbt/ujiansekolah/ruangsesi.php` | 41 | `cbtruang` | tab yang sama; halaman `cbtruang` **belum punya** Kembali → tambahkan |
| `views/cbt/ujiansekolah/ruangsesi.php` | 99 | `cbtsesi` | tab yang sama + Kembali |
| `views/cbt/ujiansekolah/wizard.php` | 192 | `cbtbanksoal` | tab yang sama + Kembali |

Halaman lama yang belum punya tombol Kembali: `cbtruang`. Sebelum dipakai dalam alur baru, tambahkan tombol Kembali ke `Daftar Ujian` (atau ke tab asal) di halaman itu.

---

## 5. Layar

### 5.1 Daftar Ujian

```
┌──────────────────────────────────────────────────────────────┐
│ Daftar Ujian                             [+ Buat Ujian Baru] │
│ Ujian Sekolah · 2026/2027 · Semester 1                       │
├──────────────────────────────────────────────────────────────┤
│ ⓘ 2 ujian baru dibuat guru                    [Lihat]        │
├──────────────────────────────────────────────────────────────┤
│ [Semua] [Hari Ini] [Akan Datang] [Selesai]      🔍 cari...   │
├──────────────────────────────────────────────────────────────┤
│ ▸ PTS Matematika — Kelas 7, 8            ● Berlangsung       │
│   20 Nov 2026 · BERSAMAAN · 60 menit · Sesi 1 · oleh Admin   │
│   ✔ Soal 40/40  ✔ Peserta 42/42  ✔ Ruang 5                   │
│   ✔ Pengawas 5  ✔ Token 8K3M9Q                               │
│   [Pantau] [Tutup] [Pengaturan] [Kartu] [Hasil]              │
├──────────────────────────────────────────────────────────────┤
│ ▸ PAS Bahasa Indonesia — Kelas 7          ⚠ Belum siap       │
│   20 Nov 2026 · BERSAMAAN · 60 menit · Sesi 1 · oleh Bu Sari │
│   ✔ Soal 30/30  ✘ Peserta belum dibagi  ✘ Pengawas kosong    │
│   [Lengkapi Persiapan]                                       │
├──────────────────────────────────────────────────────────────┤
│ Token hari ini: 8K3M9Q (Matematika) · QP7Z2L (B. Indonesia)  │
└──────────────────────────────────────────────────────────────┘
```

- Kartu menampilkan jenis+mapel, kelas, jumlah siswa, tanggal, label **BERSAMAAN** bila ada ujian lain di tanggal itu, pembuat.
- 5 chip kesiapan: Soal, Peserta, Ruang, Pengawas, Token.
- Status: Draf / Siap / Berlangsung / Selesai / Ditutup.
- Aksi menyesuaikan status; "Lengkapi Persiapan" membuka wizard di langkah yang kurang.
- Banner "N ujian baru dibuat guru" (7 hari, dari `created_by`).
- Notifikasi yang sama muncul di **dashboard admin** (kartu "Ujian baru dari guru", maks. 3 + "Lihat semua") lewat AJAX, tanpa mengubah `Dashboard.php`.
- Empty state: "Belum ada ujian tahun ini" + tombol besar.

**Aturan kesiapan** (dihitung, tidak disimpan):

| Chip | Syarat |
|---|---|
| Soal | jumlah `cbt_soal` untuk `id_bank` ≥ 1 |
| Peserta | semua siswa di kelas `bank_kelas` punya baris `cbt_sesi_siswa` (tp/smt aktif) |
| Ruang | 5 `ruang_id` terpakai |
| Pengawas | ≥ 1 guru per ruang untuk `id_jadwal` |
| Token | flag `token` ON dan `token_ujian` 6 karakter |

### 5.2 Wizard Buat Ujian (3 langkah)

**Langkah 1 — Ujian apa & kapan**

| Isian | Wajib | Default |
|---|---|---|
| Mata Pelajaran | ✔ | — (guru: hanya mapelnya) |
| Jenis Ujian | ✔ | jenis terakhir / PH |
| Tanggal | ✔ | hari ini |
| Durasi (menit) | ✔ | 60 |
| Kelas | otomatis dari kumpulan soal | info |

Setelah tanggal dipilih, tampil ujian lain di tanggal itu + keterangan "akan berjalan BERSAMAAN (mulai Sesi 1)". Semua ujian tanggal sama memakai `jam_ke = 1`. **Bentrok kelas** ditolak.

**Langkah 2 — Pilih soal**

Kartu pilihan difilter mapel, menampilkan jumlah soal, KKM, kelas. Boleh kosong → Draf.

**Langkah 3 — Pengawas, token & opsi**

| Isian | Default |
|---|---|
| Pengawas per ruang | 1 guru/ruang, otomatis disalin dari ujian lain di tanggal itu |
| Token ujian | 6 karakter huruf+angka, diacak, unik per tanggal |
| Acak soal / jawaban | ON / ON |
| Tampilkan hasil | ON |
| Gunakan token | ON |
| Aktifkan sekarang | OFF |

Ringkasan + `[Simpan sebagai Draf]` `[Simpan & Aktifkan]`.

Hasil simpan: 1 baris `cbt_jadwal` + N baris `cbt_pengawas`.

### 5.3 Pengaturan Ujian (1 halaman, 5 tab)

**Tab Peserta & Ruang**
```
Semester 1 2026/2027 · Sesi 1 · semua ruang dipakai
Kapasitas 35/ruang · 175 kursi · 42 siswa

Kelas 7
  Ruang 1 (LAB-KOM)  9   [Lihat] [Pindah]
  Ruang 2 (R2)       9
  Ruang 3 (R3)       8
  Ruang 4 (R4)       8
  Ruang 5 (R5)       8
                              [Bagi Otomatis]
Nomor peserta 7-001…7-042     [Cetak Kartu] (opsional)
```
- Bagi Otomatis: sebar merata ke **semua ruang**; kapasitas 35 sebagai batas aman; tolak jika siswa > 175.
- Pilihan "isi penuh dulu" tersedia bila "Gunakan semua ruang" dimatikan.
- Menulis `cbt_sesi_siswa`, `cbt_kelas_ruang`, `cbt_nomor_peserta`.
- Sekali per semester; konfirmasi sebelum menimpa.

**Tab Sesi & Jam**
- Sesi 1: nama, waktu mulai, waktu akhir, aktif.
- Sesi 2 & 3 disembunyikan (toggle "tampilkan sesi lain" untuk admin).
- Urutan jam (`jam_ke`) hanya muncul bila ada ujian bergiliran; default semua `1`.

**Tab Pengawas**
- Tabel: tanggal → ruang 1..5 → guru.
- Satu pengawas per ruang per tanggal, otomatis dipakai semua ujian di tanggal itu.
- Tombol "Salin ke semua ruang".

**Tab Jenis Ujian**
- CRUD 7 jenis (kode + nama), penanda jenis yang dipakai.

**Tab Token**
- Daftar ujian hari ini + token masing-masing + tombol acak ulang.
- Token global (`cbt_token`) sebagai cadangan untuk ujian lama.

### 5.4 Hasil Ujian (1 halaman, 4 tab)

Tab **Nilai Ujian**, **Analisis Soal**, **Rekap Nilai**, **Nilai Manual**.
Fase awal: tiap tab menampilkan halaman lama di dalam area konten (tetap satu menu). Fase lanjut: dibangun ulang agar seragam.

### 5.5 Token per ujian (wajib untuk ujian paralel)

Verifikasi token siswa ada di `Siswa.php` dan hanya membaca `cbt_token` global. Karena ujian berjalan bersamaan, token harus dibaca per ujian.

- Kolom baru `cbt_jadwal.token_ujian`.
- **Satu statement** di `Siswa.php` diganti:

```php
// SEBELUM
$token = $this->cbt->getToken();

// SESUDAH
$token = $this->db
    ->select("IFNULL(NULLIF(j.token_ujian,''), (SELECT token FROM cbt_token LIMIT 1)) AS token", FALSE)
    ->from('cbt_jadwal j')
    ->where('j.id_jadwal', $info->id_jadwal)
    ->get()->row();
if (!$token) { $token = $this->cbt->getToken(); }
```

Verifikasi lama tetap: `$token->token == $token_siswa`.

Aman karena: `getToken()` hanya 1 kali di `Siswa.php`; `$info` = baris jadwal; ujian lama (NULL) fallback ke token global; halaman Token admin tetap jalan.

### 5.6 Pantau (aksi di Daftar Ujian)

Fase 4: tabel siswa belum mulai / mengerjakan / selesai, tombol Buka/Tutup, Reset login, Tambah waktu.

---

## 6. Data

### 6.1 Kolom baru (aditif)

```sql
ALTER TABLE cbt_ruang
  ADD COLUMN kapasitas INT NOT NULL DEFAULT 35;

ALTER TABLE cbt_jadwal
  ADD COLUMN token_ujian VARCHAR(10) NULL,
  ADD COLUMN created_by  INT NULL;   -- id_guru; NULL = admin
```

### 6.2 Wizard → `cbt_jadwal`

`id_tp`, `id_smt`, `id_bank`, `id_jenis`, `tgl_mulai`=`tgl_selesai` (`Y-m-d`), `durasi_ujian`, `acak_soal`, `acak_opsi`, `hasil_tampil`, `token` (flag), `token_ujian`, `created_by`, `status`, `ulang=0`, `reset_login=0`, `rekap=0`, `jam_ke=1`, `jarak=30`, `time_create=NOW()`, `pengawas=NULL`.

### 6.3 Lainnya

- `cbt_pengawas`: satu baris per ruang × Sesi 1.
- `cbt_sesi_siswa`, `cbt_kelas_ruang`, `cbt_nomor_peserta`: hasil Bagi Otomatis.

### 6.4 File lama yang disentuh

| File | Perubahan |
|---|---|
| `application/controllers/Siswa.php` | 1 statement token (5.5) |
| `application/views/_templates/dashboard/sidebar.php` | ganti 16 baris menu → 4 |
| `application/views/members/guru/templates/sidebar.php` | ganti 8 baris → 3 |
| `application/views/dashboard.php` | tambah wadah notifikasi AJAX |

Tidak ada file obfuscated lain yang diubah.

---

## 7. Validasi & pesan

| Kondisi | Pesan |
|---|---|
| Mapel kosong | "Pilih mata pelajaran dulu." |
| Tanggal salah | "Tanggal ujian belum diisi dengan benar." |
| Durasi di luar 15–240 | "Durasi sebaiknya 15–240 menit." |
| Kumpulan soal kosong | "Pilih kumpulan soal. Bisa juga disimpan dulu sebagai draf." |
| Bank tanpa soal | "Kumpulan soal ini masih kosong. Ujian bisa disimpan, tapi belum bisa dijalankan." |
| Mapel bank ≠ mapel | "Kumpulan soal ini untuk Matematika, bukan IPA." |
| Jadwal ganda | "Ujian untuk kumpulan soal ini sudah ada di tanggal tersebut." |
| Kelas bentrok | "Kelas 7 sudah punya ujian Matematika di tanggal ini pada jam yang sama." |
| Token kosong | "Token belum diisi. Isi token atau matikan pilihan token." |
| Token bukan 6 huruf/angka | "Token harus 6 karakter huruf dan angka." |
| Token kembar di tanggal sama | "Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang." |
| Pengawas kosong | "Pengawas belum diatur — ujian tetap bisa disimpan sebagai draf." |
| Guru di luar jabatan | "Anda tidak mengajar mata pelajaran ini." |
| TP/SMT belum diset | "Tahun pelajaran/semester aktif belum diatur." |
| Ubah saat berlangsung | "Beberapa pengaturan tidak bisa diubah karena ujian sedang berlangsung." |
| Hapus ujian bernilai | "Ujian ini sudah memiliki nilai siswa dan tidak bisa dihapus. Tutup saja ujiannya." |
| Timpa pembagian | "Pembagian peserta akan diganti. Lanjut?" |
| Siswa > kapasitas | "Kapasitas ruang kurang (175 kursi untuk 180 siswa). Tambah ruang atau naikkan kapasitas." |
| Buka belum siap | "Ujian belum siap: pengawas belum diatur." |

---

## 8. Hak akses

| Aksi | Admin | Guru |
|---|---|---|
| Daftar Ujian | semua | mapel/kelasnya saja |
| Buat/ubah/hapus ujian | ✔ | ✔ (miliknya) |
| Aktifkan/tutup | ✔ | ✔ (miliknya) |
| Token per ujian | ✔ | ✔ (miliknya) |
| Kumpulan Soal | semua | miliknya |
| Pengaturan Ujian | ✔ | ✘ |
| Hasil Ujian | semua | mapel/kelasnya |

---

## 9. Fase

| Fase | Isi | Estimasi | Risiko |
|---|---|---|---|
| **0** | Backup + migrasi 3 kolom | ½ hari | Sangat rendah |
| **1** | **Bangun ulang sidebar**: hapus 16/8 baris lama, pasang menu Ujian Sekolah 4 submenu; halaman Daftar Ujian (baca saja) + chip + banner + notif dashboard; halaman Pengaturan & Hasil bertab yang menampilkan halaman lama; **hapus semua `target="_blank"` + pasang tombol Kembali** | 2–3 hari | Rendah–sedang (banyak tautan) |
| **2** | Wizard 3 langkah + token per ujian (patch 1 baris) + `created_by` | 3 hari | Sedang–tinggi (file obfuscated) |
| **3** | Bangun ulang tab **Peserta & Ruang** (Bagi Otomatis), **Sesi & Jam**, **Pengawas**, **Jenis**, **Token** | 3–4 hari | Sedang (menimpa data semester) |
| **4** | Bangun ulang tab **Hasil**, **Pantau** hari-H, cetak terpadu | 3 hari | Sedang |

Fase 1–2 sudah menghilangkan keluhan utama. Fase 3–4 menyelesaikan "bangun ulang" sepenuhnya.

---

## 10. Risiko

| Risiko | Mitigasi |
|---|---|
| Menghapus menu lama memutus tautan | Halaman lama tetap ada; menu saja yang hilang. Uji setiap tautan cetak/hasil setelah Fase 1. |
| Patch `Siswa.php` (obfuscated) | Backup, diff, uji 2 ujian paralel + token salah/benar. Rollback = 1 statement. |
| Ujian paralel berbagi ruang | Pengawas per ruang, label BERSAMAAN, absen per ujian. |
| Token kembar | Validasi unik per tanggal + acak ulang. |
| Bagi peserta menimpa data | Konfirmasi + backup tabel. |
| Notifikasi dashboard | AJAX ke endpoint baru; `Dashboard.php` tidak diubah. |
| Guru buat ujian kelas lain | Filter `jabatan_guru` di server. |
| Double submit | CSRF + cek jadwal ganda. |
| Tab menampilkan halaman lama (iframe) terasa janggal | Hanya sementara di Fase 1; diganti di Fase 3–4. |
| Tautan membuka tab browser baru | Semua `target="_blank"`/`window.open` dihapus; diganti navigasi tab yang sama + tombol Kembali di halaman tujuan. |
| Halaman lama tanpa tombol Kembali (mis. `cbtruang`) | Tambahkan tombol Kembali sebelum dipakai di alur baru. |

---

## 11. Di luar lingkup

- Notifikasi WhatsApp/email.
- Menghapus file controller/model/view lama (hanya menu yang dihapus).
- Aplikasi sisi siswa.
- Multi-sesi bergiliran.
- Ujian bergiliran dalam satu hari lewat wizard.

---

## 12. Pertanyaan

1. **Nama menu** jadi "Ujian Sekolah" (sesuai keputusan #6) — atau memang "Ujian Siswa"?
2. **Pengaturan Ujian** cukup 1 guru per ruang per tanggal, dipakai bersama ujian paralel?
3. **Token** 6 karakter huruf+angka — atau 4/8?
4. **Notifikasi dashboard** 7 hari terakhir, atau tetap sampai ditandai dibaca?
5. **Tab Hasil** di Fase 1 boleh menampilkan halaman lama dulu (biar cepat jadi), atau harus langsung dibangun ulang semua?

---

## 13. Definisi selesai (Fase 0–2)

- Sidebar hanya punya **4 submenu** (admin) / **3** (guru) di bawah Ujian Sekolah.
- **Tidak ada satu pun** tautan `target="_blank"` atau `window.open` di modul Ujian Sekolah.
- Setiap halaman (wizard, pengaturan, hasil, pantau, cetak, kumpulan soal) punya tombol **Kembali** ke halaman induknya.
- Tidak ada fitur lama yang hilang; semua tercapai lewat 4 submenu.
- Ujian baru selesai dibuat dalam **3 langkah**.
- Dua ujian paralel dengan token berbeda, masing-masing hanya menerima tokennya sendiri.
- Guru hanya melihat/membuat ujian mapel & kelasnya.
- Notifikasi ujian buatan guru muncul di Daftar Ujian dan dashboard admin.
- Alur buat → aktifkan → siswa mengerjakan → nilai masuk berjalan tanpa error.
