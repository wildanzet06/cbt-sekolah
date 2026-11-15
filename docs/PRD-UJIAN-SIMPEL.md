# PRD — Menu "Ujian Sekolah" yang Simpel (CBT)

Status: **Draf v3 — final untuk ditinjau**
Tanggal: 15 November 2026
Lingkup: menu **Data Ujian** + **Pelaksanaan Ujian** (admin) dan **Ulangan / Ujian** (guru)
File terkait: `docs/PRD-UJIAN-SIMPEL.md`

---

## 0. Keputusan yang sudah disepakati

| # | Pertanyaan | Keputusan | Dampak |
|---|---|---|---|
| 1 | Kapasitas per ruang | **35 siswa/ruang** | 5 ruang × 35 = **175 kursi** |
| 2 | Ruang yang dipakai | **Semua 5 ruang** | Peserta disebar merata ke seluruh ruang |
| 3 | Guru boleh buat ujian | **Boleh** | Wizard tersedia untuk guru, dibatasi mapel/kelas yang dia ampu |
| 4 | Perlu persetujuan admin | **Tidak** — cukup pemberitahuan | Notifikasi muncul di **dashboard admin** |
| 5 | Jenis ujian | **Tetap 7** (PH, PTS, PAS, PAT, UMBK, TO, Simulasi) | Tidak ada perubahan data master |
| 6 | Nama menu | Header **"UJIAN SEKOLAH"**, item **"Daftar Ujian"** | Struktur sidebar final |
| 7 | Dua ujian di tanggal sama | **Bersamaan** (paralel, beda kelas) | Tidak perlu kolom jam; token harus benar-benar per ujian |
| 8 | Token | **Beda tiap ujian**, 6 karakter **huruf + angka** | Perlu kolom `token_ujian` + patch 1 baris di sisi siswa |

### Asumsi yang dipakai

- Satu kelas **tidak** mengerjakan dua ujian pada saat bersamaan. Jika terjadi, sistem menolak.
- Ujian paralel memakai **ruang yang sama**; satu ruang berisi siswa dari kelas berbeda.
- Pengawas dihitung **per ruang per tanggal**, bukan per ujian (lihat 5.3).
- Jam mulai mengikuti **Sesi 1**; ujian hanya dibatasi tanggal, bukan jam.

---

## 1. Ringkasan

Saat ini untuk menyiapkan **satu** ujian, admin harus melewati **10 menu** di *Data Ujian* dan **6 menu** di *Pelaksanaan Ujian* (total 16). Guru melihat 8 menu. Menu disusun mengikuti nama tabel database, bukan mengikuti pekerjaan yang harus diselesaikan, sehingga pengguna awam tidak tahu harus mulai dari mana dan urutan yang benar.

Usulan: **satukan persiapan ujian dalam satu halaman "Daftar Ujian"** dengan wizard 3 langkah, kelompokkan ulang sidebar menjadi 2 grup, dan sisakan halaman lama sebagai "pengaturan lanjutan".

| Sekarang | Sesudah |
|---|---|
| 10 menu persiapan + 6 menu pelaksanaan | 1 halaman Daftar Ujian + 1 grup Pengaturan (5 item) |
| 15–20 field di form jadwal | 3 langkah, 6–8 isian, sisanya default |
| Tidak ada penanda "belum lengkap" | Checklist kesiapan otomatis per ujian |
| Token satu untuk semua ujian | Token **per ujian**, aktif bersamaan tanpa saling menimpa |
| Istilah teknis (Bank Soal, Alokasi Waktu, Atur Ruang/Sesi) | Istilah tugas (Kumpulan Soal, Urutan Jam, Pembagian Peserta) |

---

## 2. Temuan kondisi sekarang

### 2.1 Daftar menu (dari `sidebar.php`)

**Admin — grup "Data Ujian" (10 item)**
`Jenis Ujian` · `Sesi` · `Ruang` · `Atur Ruang/Sesi` · `Nomor Peserta` · `Bank Soal` · `Jadwal` · `Alokasi Waktu` · `Pengawas` · `Token`

**Admin — grup "Pelaksanaan Ujian" (6 item)**
`Cetak` · `Status Siswa` · `Hasil Ujian` · `Analisis Soal` · `Rekap Nilai` · `Nilai Manual`

**Guru — grup "Ulangan / Ujian" (8 item)**
`Bank Soal` · `Jadwal` · `Cetak` · `Status Siswa` · `Hasil Ujian` · `Analisis Soal` · `Rekap Nilai` · `Nilai Manual`

### 2.2 Penyebab terasa ribet

1. **Menu = tabel database.** `Sesi`, `Ruang`, `Atur Ruang/Sesi`, `Nomor Peserta` sebenarnya satu pekerjaan ("siapkan peserta"), tapi dipecah 4 menu.
2. **Urutan wajib tidak terlihat.** Tidak ada penanda langkah 1..5 atau status "belum lengkap". Kalau satu langkah terlewat, siswa gagal ujian tanpa pesan jelas.
3. **Istilah teknis.** *Bank Soal*, *Alokasi Waktu* (isinya hanya "jam ke"), *Atur Ruang/Sesi*, *Expired*, *Reset Izin*.
4. **Pengaturan langka dicampur dengan yang berubah tiap ujian.** `Alokasi Waktu` (opsional) sejajar dengan `Jadwal` (wajib).
5. **Hasil tersebar di 5 menu.** Padahal pertanyaannya cuma satu: "ujiannya bagaimana?".

### 2.3 Batasan teknis (penting untuk desain)

| Temuan | Dampak desain |
|---|---|
| `cbt_token` hanya **1 baris global**. `cbt_jadwal.token` hanya **flag 0/1** "gunakan token". | Token per ujian butuh kolom baru. Verifikasi token siswa ada di `Siswa.php` dan hanya 1 tempat → patch 1 baris (lihat 5.5). |
| `cbt_sesi_siswa` menyimpan pembagian siswa → ruang/sesi **per tahun pelajaran + semester**, bukan per ujian. | Pembagian peserta cukup **sekali per semester**. Wizard tidak perlu membagi peserta. |
| `cbt_jadwal.tgl_mulai/tgl_selesai` bertipe **varchar tanggal** (tanpa jam). | Semua ujian pada tanggal yang sama otomatis **bersamaan** mulai Sesi 1. Tidak ada isian jam. |
| `cbt_sesi_siswa` berisi 3 sesi; pola sekolah = **serentak 1 sesi**. | Sesi 2 & 3 disembunyikan. Sistem memakai **Sesi 1** saja. |
| `cbt_jadwal.pengawas` (longtext) tidak dipakai; pengawas ada di `cbt_pengawas` per jadwal+ruang+sesi+guru. | Wizard menulis ke `cbt_pengawas`. Ujian paralel berbagi ruang → pengawas disalin (lihat 5.3). |
| Semua controller/model CBT lama **ter-obfuscate** (goto). | Fitur baru di **file baru**. Hanya 1 baris di `Siswa.php` yang disentuh, untuk token (wajib). |
| `cbt_ruang` **tidak punya kolom kapasitas**. | Tambah kolom `kapasitas` default **35**. |
| `cbt_bank_soal.bank_kelas` berupa **array PHP terserialisasi**. | Kelas peserta diturunkan dari kumpulan soal. |
| `cbt_jadwal` tidak punya `id_kelas`/`id_mapel`/`created_by`. | Mapel & kelas ikut kumpulan soal; pembuat ujian perlu kolom `created_by`. |

### 2.4 Data saat ini (kalibrasi)

`master_kelas` 2 (kelas 7, 8) · `master_siswa` 42 · `master_mapel` 42 · `master_guru` 1 · `cbt_bank_soal` 1 · `cbt_soal` 11 · `cbt_jenis` 7 · `cbt_sesi` 3 · `cbt_ruang` 5 · `cbt_jadwal` 1 · `cbt_sesi_siswa` 24 · `cbt_nomor_peserta` 24 · `cbt_pengawas` 0 · `cbt_token` 1

Kapasitas baru: **5 × 35 = 175 kursi** untuk **42 siswa** → semua ruang terpakai, isi ± 8–9 siswa/ruang.

---

## 3. Prinsip desain

1. **Satu halaman, satu pekerjaan.** "Daftar Ujian" = melihat, membuat, dan menjalankan ujian.
2. **Tiga langkah untuk ujian baru.** Mapel & tanggal → soal → pengawas & opsi.
3. **Default yang wajar, bukan pertanyaan.** Acak soal ON, acak jawaban ON, tampilkan hasil ON, token ON (acak otomatis), durasi 60 menit, Sesi 1, semua ruang.
4. **Yang wajib kelihatan, yang langka disembunyikan.** Sesi/ruang/jam/jenis → *Pengaturan Ujian*.
5. **Jangan hapus, jangan pindahkan data.** Semua tabel & halaman lama tetap berfungsi.
6. **Bahasa manusia.** Tiap halaman punya 1 baris penjelas: "Halaman ini untuk …, dilakukan setelah …".

---

## 4. Arsitektur informasi baru

### 4.1 Sidebar admin

```
UJIAN SEKOLAH
├─ Daftar Ujian           (baru)   ← hub: daftar ujian + checklist + token
├─ Kumpulan Soal          cbtbanksoal (label baru)
└─ Hasil                  (halaman ringkas / submenu)
   ├─ Nilai Ujian         cbtnilai
   ├─ Analisis Soal       cbtanalisis
   ├─ Rekap Nilai         cbtrekap
   └─ Nilai Manual        nilaimanual

PENGATURAN UJIAN  (dilipat, jarang dipakai)
├─ Pembagian Peserta      cbtsesisiswa  (gabungan Sesi/Ruang/Atur Ruang/Sesi)
├─ Ruang & Sesi           (baru: gabungan cbtruang + cbtsesi)
├─ Pengawas               cbtpengawas
├─ Jenis Ujian            cbtjenis
└─ Urutan Jam Ujian       cbtalokasi
```

Item yang **hilang dari sidebar** (tetap ada sebagai halaman/aksi):
- `Nomor Peserta` → aksi **"Kartu Peserta"** (opsional)
- `Token` → **isian token di wizard** + panel "Token aktif" di Daftar Ujian
- `Cetak` → aksi **"Cetak Kartu & Absen"**
- `Status Siswa` → tab **"Pantau"** (Fase 4)

Hasil: **2 grup, 4 item terlihat** menggantikan **16 item berderet**.

### 4.2 Sidebar guru

```
UJIAN SEKOLAH
├─ Daftar Ujian
├─ Kumpulan Soal
└─ Hasil
   ├─ Nilai Ujian
   ├─ Analisis Soal
   ├─ Rekap Nilai
   └─ Nilai Manual
```

Guru **tidak** melihat Pengaturan Ujian (ruang/sesi/pengawas diatur admin). Dari 8 item → 3 item.

### 4.3 Perubahan label

| Sekarang | Menjadi | Alasan |
|---|---|---|
| Bank Soal | **Kumpulan Soal** | "Bank" terasa teknis |
| Atur Ruang/Sesi | **Pembagian Peserta** | Menyebut hasil, bukan tabel |
| Alokasi Waktu | **Urutan Jam Ujian** | Isinya hanya urutan jam |
| Nomor Peserta | **Kartu Peserta** | Yang dicetak memang kartu |
| Status Siswa | **Pantau Ujian** | Menyebut tujuan |
| Cetak | **Cetak Kartu & Absen** | Menyebut isi |
| Jadwal | **Daftar Ujian** (halaman baru) | Satu tempat untuk semuanya |
| Jenis Ujian, Pengawas | tetap | sudah dipahami |

---

## 5. Alur layar

### 5.1 Halaman "Daftar Ujian" (hub)

```
┌──────────────────────────────────────────────────────────────┐
│ Daftar Ujian                             [+ Buat Ujian Baru] │
│ Ujian Sekolah · Tahun 2026/2027 · Semester 1                 │
├──────────────────────────────────────────────────────────────┤
│ ⓘ 2 ujian baru dibuat guru minggu ini          [Lihat]       │
├──────────────────────────────────────────────────────────────┤
│ [Semua] [Hari Ini] [Akan Datang] [Selesai]      🔍 cari...   │
├──────────────────────────────────────────────────────────────┤
│ ▸ PTS Matematika — Kelas 7, 8            ● Berlangsung       │
│   20 Nov 2026 · 60 menit · Sesi 1 · oleh Admin               │
│   ✔ Soal 40/40   ✔ Peserta 42/42   ✔ Ruang 5                 │
│   ✔ Pengawas 5   ✔ Token 8K3M9Q                              │
│   [Pantau] [Tutup] [Pengaturan] [Kartu] [Hasil]              │
├──────────────────────────────────────────────────────────────┤
│ ▸ PAS Bahasa Indonesia — Kelas 7          ⚠ Belum siap       │
│   20 Nov 2026 · 60 menit · Sesi 1 · oleh Bu Sari             │
│   ✔ Soal 30/30   ✘ Peserta belum dibagi  ✘ Pengawas kosong   │
│   [Lengkapi Persiapan]                                       │
├──────────────────────────────────────────────────────────────┤
│ Token aktif hari ini: 8K3M9Q (Matematika) · QP7Z2L (B. Ind)  │
└──────────────────────────────────────────────────────────────┘
```

**Kartu ujian**
- Judul: jenis + mapel; kelas dari `bank_kelas`; jumlah siswa.
- Tanggal, durasi, sesi, **pembuat** (Admin / nama guru).
- 5 chip kesiapan: Soal, Peserta, Ruang, Pengawas, Token.
- Status: `Draf` / `Siap` / `Berlangsung` / `Selesai` / `Ditutup`.
- Aksi kontekstual: belum siap → **Lengkapi Persiapan**; siap → **Buka Ujian**; berlangsung → **Pantau** + **Tutup**.
- Ujian pada tanggal yang sama diberi label **"bersamaan"** agar tidak dikira salah input.

**Aturan kesiapan (dihitung, tidak disimpan)**

| Chip | Sumber data | Siap jika |
|---|---|---|
| Soal | `cbt_soal` where `id_bank` | jumlah soal ≥ 1 |
| Peserta | `master_siswa` kelas `bank_kelas` vs `cbt_sesi_siswa` (tp/smt aktif) | semua siswa punya baris ruang/sesi |
| Ruang | `cbt_sesi_siswa.ruang_id` distinct | semua 5 ruang terpakai |
| Pengawas | `cbt_pengawas` where `id_jadwal` | ≥ 1 guru per ruang |
| Token | `cbt_jadwal.token` + `token_ujian` | flag ON dan token 6 karakter terisi |

**Pemberitahuan ujian buatan guru (keputusan #4)**
- Banner di atas daftar: "N ujian baru dibuat guru" (7 hari terakhir, `created_by` terisi).
- Label "oleh <nama guru>" di tiap kartu.
- **Muncul juga di dashboard admin** sebagai kartu "Ujian baru dari guru" (maks. 3 item + tautan "Lihat semua").
- Tidak ada tombol setujui/tolak. Admin bisa langsung mengubah/menutup bila perlu.
- Belum ada notifikasi WhatsApp/email (di luar lingkup).

**Cara menampilkan notifikasi di dashboard tanpa menyentuh controller lama:** potongan HTML di `views/dashboard.php` memanggil AJAX ke `ujiansekolah/notif`, lalu merender kartu. Controller `Dashboard.php` yang ter-obfuscate tidak diubah.

---

### 5.2 Wizard "Buat Ujian Baru" — 3 langkah

**Langkah 1 — Ujian apa & kapan**

| Isian | Wajib | Default | Catatan |
|---|---|---|---|
| Mata Pelajaran | ✔ | — | searchable (select2); guru: hanya mapel di `jabatan_guru` |
| Jenis Ujian | ✔ | jenis terakhir (fallback PH) | 7 jenis tetap |
| Tanggal | ✔ | hari ini | datepicker |
| Durasi (menit) | ✔ | 60 | 15–240 |
| Kelas | otomatis | dari kumpulan soal | info, bukan isian |

**Ujian paralel (keputusan #7).** Setelah tanggal dipilih, sistem menampilkan ujian lain di tanggal itu:

```
20 Nov 2026 sudah ada:
  • PTS Matematika · Kelas 7, 8 · 60 menit

Ujian ini akan berjalan BERSAMAAN pada tanggal tersebut (mulai Sesi 1).
```

- Semua ujian pada tanggal sama memakai `jam_ke = 1` → aktif bersamaan saat Sesi 1 dimulai.
- Tidak ada isian jam. Tidak ada penomoran urut.
- **Validasi bentrok:** jika kelas pada kumpulan soal sudah punya ujian di tanggal yang sama → tolak: "Kelas 7 sudah punya ujian Matematika di tanggal ini pada jam yang sama."
- Jika sekolah ingin bergiliran (bukan bersamaan), atur lewat **Urutan Jam Ujian** (halaman lama, Fase lanjutan).

**Langkah 2 — Pilih soal**

Kartu pilihan (bukan dropdown), difilter mapel:

```
┌────────────────────────────────────────────┐
│ ● Matematika Kelas 7 — 40 soal             │
│   PG 35 · Esai 5 · KKM 75                  │
│   Dipakai di: PTS Matematika (20 Nov)      │
└────────────────────────────────────────────┘

   Belum punya kumpulan soal?  + Buat Kumpulan Soal
```

- Boleh lanjut tanpa soal → **Draf**, chip Soal ✘.
- Setelah memilih: "Peserta: 42 siswa · Kelas 7, 8".

**Langkah 3 — Pengawas, token & opsi**

| Isian | Default | Catatan |
|---|---|---|
| Pengawas per ruang | 1 guru/ruang | otomatis terisi dari ujian lain di tanggal sama; toggle **"Guru yang sama untuk semua ruang"** (ON) |
| Token ujian | 6 karakter huruf+angka, diacak | tombol **Acak ulang**; dicek unik pada tanggal yang sama |
| Acak soal | ON | |
| Acak jawaban | ON | |
| Tampilkan hasil ke siswa | ON | |
| Gunakan token | ON | |
| Aktifkan sekarang | OFF | OFF → Draf; ON → tampil sesuai tanggal |

**Kenapa pengawas per ruang, bukan per ujian.** Karena ujian paralel berbagi ruang, satu ruang bisa berisi siswa kelas 7 (Matematika) dan kelas 8 (Bahasa Indonesia) sekaligus. Wizard menyalin pengawas ruang yang sama dari ujian lain di tanggal itu, sehingga **orangnya tetap satu**, hanya tercatat di masing-masing ujian. Cetak daftar hadir per ujian tetap benar.

Ringkasan bahasa manusia:

```
PTS Matematika · Kelas 7, 8 · 20 Nov 2026 · BERSAMAAN · 60 menit
Soal 40 · Peserta 42 · Ruang 5 · Pengawas 5 · Token 8K3M9Q
[ Simpan sebagai Draf ]            [ Simpan & Aktifkan ]
```

**Hasil simpan:** 1 baris `cbt_jadwal` (termasuk `token_ujian`, `created_by`, `jam_ke = 1`) + N baris `cbt_pengawas` (satu per ruang × Sesi 1).

**Edit:** wizard yang sama (`ujiansekolah/edit/{id}`), nilai terisi. Opsi terkunci saat ujian berlangsung ditampilkan disabled + penjelasan.

---

### 5.3 Halaman "Pembagian Peserta" (Pengaturan Ujian)

Menggantikan `Atur Ruang/Sesi` + `Nomor Peserta`.

```
Pembagian Peserta — Semester 1 2026/2027
Pola: serentak · Sesi 1 · semua ruang dipakai
Kapasitas: 35 siswa/ruang · total 175 kursi · siswa 42

┌ Kelas 7 ─────────────────────────────────────────────┐
│ Ruang 1 (LAB-KOM)   9 siswa   [Lihat] [Pindah]        │
│ Ruang 2 (R2)        9 siswa   [Lihat] [Pindah]        │
│ Ruang 3 (R3)        8 siswa   [Lihat] [Pindah]        │
│ Ruang 4 (R4)        8 siswa   [Lihat] [Pindah]        │
│ Ruang 5 (R5)        8 siswa   [Lihat] [Pindah]        │
│                                        [Bagi Otomatis]│
└───────────────────────────────────────────────────────┘
┌ Kelas 8 ─────────────────────────────────────────────┐
│ ... (pola sama, 9/9/8/8/8)                            │
└───────────────────────────────────────────────────────┘

Nomor peserta: 7-001 … 7-042            [Cetak Kartu] (opsional)
```

**Aturan "Bagi Otomatis" (keputusan #1 & #2)**
- Semua ruang **wajib terpakai** (checkbox "Gunakan semua ruang", default ON).
- Siswa dibagi **merata**: 42 siswa / 5 ruang → 9, 9, 8, 8, 8.
- Kapasitas **35/ruang** hanya batas aman. Jika total siswa > 175 → tolak: "Kapasitas ruang kurang (175 kursi untuk 180 siswa). Tambah ruang atau naikkan kapasitas."
- Jika "Gunakan semua ruang" dimatikan → diisi berurutan sampai 35/ruang.
- Menulis `cbt_sesi_siswa` (ruang_id, sesi_id = Sesi 1), `cbt_kelas_ruang`, lalu `cbt_nomor_peserta` berurutan.
- Dijalankan **sekali per semester**; semua ujian memakai pembagian yang ada.
- Konfirmasi sebelum menimpa: "Pembagian peserta akan diganti. Lanjut?"

---

### 5.4 "Pantau Ujian" (Fase 4)

Tabel hari-H: siswa belum mulai / sedang mengerjakan / selesai; tombol **Buka/Tutup**, **Reset login**, **Tambah waktu**. Menggantikan `Status Siswa`.

---

### 5.5 Token per ujian untuk ujian paralel (keputusan #8)

**Masalah:** verifikasi token siswa ada di `application/controllers/Siswa.php` (obfuscated) dan membaca **satu** baris `cbt_token`. Karena ujian berjalan **bersamaan**, tidak bisa memakai trik "satu token aktif bergiliran" — dua ujian paralel butuh dua token yang sah pada saat yang sama.

**Solusi:** token disimpan per ujian, dan **hanya 1 baris** di `Siswa.php` diubah agar membacanya.

1. Tambah kolom `cbt_jadwal.token_ujian VARCHAR(10)`.
2. Wizard mengisi token 6 karakter huruf+angka (bisa diacak ulang).
3. Di `Siswa.php`, ganti satu statement:

   ```php
   // SEBELUM
   $token = $this->cbt->getToken();

   // SESUDAH
   $token = $this->db
       ->select("IFNULL(NULLIF(j.token_ujian,''), (SELECT token FROM cbt_token LIMIT 1)) AS token", FALSE)
       ->from('cbt_jadwal j')
       ->where('j.id_jadwal', $info->id_jadwal)
       ->get()->row();
   if (!$token) {
       $token = $this->cbt->getToken();
   }
   ```

   Verifikasi lama tetap dipakai: `$token->token == $token_siswa`.

**Kenapa aman:**
- `getToken()` hanya muncul **1 kali** di `Siswa.php` (sudah dicek), tepat di jalur verifikasi token.
- `$info` pada titik itu adalah baris `cbt_jadwal` dari `getJadwalById($id_jadwal)`.
- Ujian lama dengan `token_ujian` NULL otomatis **fallback ke token global** → tidak ada ujian yang rusak.
- Halaman admin Token (`cbttoken`) tetap berfungsi untuk mengubah token global.

**Yang perlu disiapkan:** backup file, diff sebelum-sesudah, dan uji end-to-end (2 ujian paralel, token berbeda, tukar token → harus gagal).

**Validasi:** token harus **unik di antara ujian pada tanggal yang sama**. Jika bentrok: "Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang."

---

### 5.6 "Kartu Peserta" (opsional)

Tombol di Daftar Ujian & Pembagian Peserta → membuka `cbtcetak` yang sudah ada, dengan filter kelas + ujian. Tidak wajib dipakai.

---

## 6. Pemetaan data

### 6.1 Kolom baru (aditif, nullable/default — aman untuk kode lama)

```sql
ALTER TABLE cbt_ruang
  ADD COLUMN kapasitas INT NOT NULL DEFAULT 35;

ALTER TABLE cbt_jadwal
  ADD COLUMN token_ujian VARCHAR(10) NULL,
  ADD COLUMN created_by  INT NULL;   -- id_guru; NULL = dibuat admin
```

Ketiga kolom hanya dibaca/ditulis oleh modul baru (kecuali `token_ujian` dibaca 1 baris patch di `Siswa.php`). Halaman lama mengabaikannya.

### 6.2 Wizard → `cbt_jadwal`

| Field wizard | Kolom | Nilai |
|---|---|---|
| tp/smt aktif | `id_tp`, `id_smt` | dari setting aktif |
| kumpulan soal | `id_bank` | pilihan langkah 2 |
| jenis | `id_jenis` | pilihan langkah 1 |
| tanggal | `tgl_mulai`, `tgl_selesai` | `Y-m-d`, keduanya sama |
| durasi | `durasi_ujian` | 15–240 |
| — | `pengawas` | `NULL` (legacy) |
| acak soal | `acak_soal` | 1/0 |
| acak jawaban | `acak_opsi` | 1/0 |
| tampilkan hasil | `hasil_tampil` | 1/0 |
| gunakan token | `token` | 1/0 |
| token ujian | `token_ujian` | 6 karakter huruf+angka |
| dibuat oleh | `created_by` | id_guru (NULL jika admin) |
| aktifkan | `status` | 1/0 |
| — | `ulang`, `reset_login`, `rekap` | 0 |
| — | `jam_ke` | **1** (ujian paralel) |
| — | `jarak` | 30 (tidak ditanyakan) |
| — | `time_create` | NOW() |

### 6.3 Wizard → `cbt_pengawas`

Satu baris per ruang: `id_jadwal`, `id_tp`, `id_smt`, `id_ruang`, `id_sesi` (Sesi 1), `id_guru`.

### 6.4 Pembagian otomatis

| Tabel | Isi |
|---|---|
| `cbt_sesi_siswa` | `siswa_id`, `kelas_id`, `ruang_id`, `sesi_id`, `tp_id`, `smt_id` |
| `cbt_kelas_ruang` | `id_kelas`, `id_ruang`, `id_sesi`, `id_tp`, `id_smt`, `set_siswa` |
| `cbt_nomor_peserta` | `id_siswa`, `id_tp`, `id_smt`, `nomor_peserta` |

### 6.5 Data yang dibaca

| Kebutuhan | Sumber |
|---|---|
| daftar ujian + chip | `cbt_jadwal` ⟕ `cbt_bank_soal` ⟕ `cbt_jenis` |
| kelas & jumlah siswa | `cbt_bank_soal.bank_kelas` (unserialize) ⟕ `master_siswa` |
| pengawas | `cbt_pengawas` ⟕ `master_guru` |
| token | `cbt_jadwal.token_ujian` (fallback `cbt_token`) |
| ruang & kapasitas | `cbt_ruang`, `cbt_sesi_siswa` |
| mapel/kelas guru | `jabatan_guru.mapel_kelas` |
| pembuat ujian | `cbt_jadwal.created_by` ⟕ `master_guru` |

### 6.6 Perubahan pada file lama (satu-satunya)

| File | Perubahan | Alasan |
|---|---|---|
| `application/controllers/Siswa.php` | 1 statement token → baca `cbt_jadwal.token_ujian` (dengan fallback) | Verifikasi token per ujian; tidak ada cara lain tanpa mengubah file ini |

Semua file lain **tidak disentuh**. Halaman lama (Jadwal, Bank Soal, Cetak, dll.) tetap memakai kode aslinya.

---

## 7. Validasi & pesan error

| Kondisi | Pesan | Perilaku |
|---|---|---|
| Mapel belum dipilih | "Pilih mata pelajaran dulu." | fokus field |
| Tanggal kosong/format salah | "Tanggal ujian belum diisi dengan benar." | fokus |
| Durasi < 15 atau > 240 | "Durasi sebaiknya 15–240 menit." | fokus |
| Kumpulan soal belum dipilih | "Pilih kumpulan soal. Bisa juga disimpan dulu sebagai draf." | tombol Simpan Draf aktif |
| Kumpulan soal tanpa soal | "Kumpulan soal ini masih kosong. Ujian bisa disimpan, tapi belum bisa dijalankan." | boleh simpan |
| Mapel bank ≠ mapel terpilih | "Kumpulan soal ini untuk Matematika, bukan IPA." | bank disembunyikan |
| Jadwal ganda (bank + tanggal + tp/smt) | "Ujian untuk kumpulan soal ini sudah ada di tanggal tersebut." | tolak simpan |
| Kelas bentrok pada tanggal sama | "Kelas 7 sudah punya ujian Matematika di tanggal ini pada jam yang sama." | tolak simpan |
| Token kosong saat opsi token ON | "Token belum diisi. Isi token atau matikan pilihan token." | tolak simpan |
| Token bukan 6 huruf/angka | "Token harus 6 karakter huruf dan angka." | fokus |
| Token sudah dipakai ujian lain di tanggal sama | "Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang." | tolak simpan |
| Token ganda saat simpan | "Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang." | tolak simpan |
| Pengawas kosong | "Pengawas belum diatur — ujian tetap bisa disimpan sebagai draf." | peringatan |
| Guru pilih mapel di luar jabatan | "Anda tidak mengajar mata pelajaran ini." | filter + 403 |
| TP/SMT aktif belum diset | "Tahun pelajaran/semester aktif belum diatur." | halaman info + link |
| Ubah opsi saat ujian berlangsung | "Beberapa pengaturan tidak bisa diubah karena ujian sedang berlangsung." | field disabled |
| Hapus ujian bernilai | "Ujian ini sudah memiliki nilai siswa dan tidak bisa dihapus. Tutup saja ujiannya." | tolak hapus |
| Bagi peserta menimpa data | "Pembagian peserta akan diganti. Lanjut?" | konfirmasi |
| Siswa > kapasitas total | "Kapasitas ruang kurang (175 kursi untuk 180 siswa). Tambah ruang atau naikkan kapasitas." | tolak |
| Buka ujian yang belum siap | "Ujian belum siap: pengawas belum diatur." | tolak buka + arahkan lengkapi |

Prinsip: **satu pesan per masalah**, di dekat field, tanpa istilah database.

---

## 8. Hak akses

| Aksi | Admin | Guru |
|---|---|---|
| Lihat Daftar Ujian | semua ujian | hanya mapel/kelas di `jabatan_guru` |
| Buat ujian | ✔ | ✔ (mapel & kelasnya, divalidasi di server) |
| Ubah / hapus ujian | ✔ | ✔ (ujiannya sendiri) |
| Aktifkan / tutup ujian | ✔ | ✔ (ujiannya sendiri) |
| Token per ujian | ✔ | ✔ (ujiannya sendiri) |
| Token global (fallback) | ✔ | lihat |
| Pembagian peserta, ruang & sesi | ✔ | ✘ |
| Pengawas | ✔ | ✘ (diatur admin / disalin otomatis) |
| Kumpulan Soal | semua | miliknya |
| Hasil, analisis, rekap, nilai manual | semua | mapel/kelasnya |

Ujian buatan guru **langsung bisa aktif** tanpa persetujuan admin; admin hanya menerima pemberitahuan (banner + kartu dashboard).

---

## 9. Rencana bertahap

| Fase | Isi | Perkiraan | Risiko |
|---|---|---|---|
| **0** | Backup + migrasi 3 kolom aditif | ½ hari | Sangat rendah |
| **1** | Sidebar 2 grup, label baru, halaman **Daftar Ujian** (baca saja) + chip kesiapan + panel token + banner guru + notifikasi dashboard | 1–2 hari | Rendah |
| **2** | Wizard 3 langkah + token per ujian (termasuk patch 1 baris `Siswa.php`) + `created_by` | 3 hari | **Sedang–tinggi** (patch file obfuscated) |
| **3** | **Pembagian peserta otomatis** (5 ruang, 35/ruang, Sesi 1) + nomor peserta + kartu opsional | 2–3 hari | Sedang (menimpa data semester) |
| **4** | **Pantau Ujian** hari-H + cetak terpadu | 2 hari | Sedang |

Fase 0–2 sudah menghilangkan sebagian besar keluhan. Fase 3–4 opsional.

---

## 10. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Patch 1 baris di `Siswa.php` (obfuscated) | Backup file, diff, uji 2 ujian paralel + token salah/benar. Rollback = kembalikan 1 statement. |
| Ujian paralel berbagi ruang → siswa beda ujian dalam satu ruang | Pengawas disalin per ruang, label "bersamaan" di kartu, cetak absen per ujian. |
| Token per ujian bentrok | Validasi unik per tanggal + tombol acak ulang. |
| Controller/model lama ter-obfuscate | Fitur baru di file baru (`Ujiansekolah.php`, `Ujiansekolah_model.php`, `views/cbt/ujiansekolah/*`). |
| Notifikasi dashboard butuh data dari controller obfuscated | AJAX ke endpoint baru; `Dashboard.php` tidak diubah. |
| Pembagian peserta menimpa data semester | Konfirmasi + backup tabel sebelum eksekusi. |
| Siswa > 175 | Validasi kapasitas + saran tambah ruang. |
| Guru membuat ujian untuk kelas yang tidak diajar | Filter `jabatan_guru` di server, bukan hanya tampilan. |
| Double submit wizard | CSRF + cek jadwal ganda (bank + tanggal + tp + smt). |
| Sidebar berubah, menu lama "hilang" | Semua tautan lama tetap ada di Pengaturan / aksi halaman. Tidak ada route dihapus. |

---

## 11. Di luar lingkup

- Notifikasi WhatsApp/email (belum disetujui, provider belum dipilih). Pemberitahuan = banner + kartu dashboard.
- Mengubah halaman lama (Bank Soal, Jadwal, Cetak, Hasil) selain label sidebar.
- Aplikasi siswa / tampilan peserta ujian.
- Multi-sesi bergiliran (pola sekolah = serentak Sesi 1).
- Ujian bergiliran dalam satu hari (bisa lewat Urutan Jam Ujian, tanpa wizard).

---

## 12. Pertanyaan terbuka (sisa)

1. **Pengawas**: cukup **1 guru per ruang per tanggal** dan otomatis dipakai bersama oleh ujian paralel? (desain sekarang)
2. **Token**: 6 karakter huruf+angka (mis. `8K3M9Q`) — atau mau 4/8 karakter?
3. **Notifikasi dashboard**: cukup tampil untuk ujian 7 hari terakhir, atau sampai admin menekan "Tandai sudah dibaca"?

---

## 13. Definisi selesai (Fase 0–2)

- Admin dapat membuat satu ujian lengkap tanpa membuka lebih dari 2 halaman (Daftar Ujian + Kumpulan Soal).
- Jumlah klik dari sidebar ke ujian aktif ≤ 3.
- Setiap ujian menampilkan status kesiapan yang bisa dijelaskan tanpa manual.
- Dua ujian paralel dengan token berbeda: masing-masing hanya menerima tokennya sendiri.
- Guru hanya melihat & membuat ujian untuk mapel/kelas yang dia ampu.
- Notifikasi ujian buatan guru muncul di Daftar Ujian **dan** dashboard admin.
- Semua menu lama masih dapat diakses.
- Tidak ada error PHP/DB pada alur buat → aktifkan → siswa mengerjakan → nilai masuk.
