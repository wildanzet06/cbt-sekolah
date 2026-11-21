# PRD — Perbaikan Keamanan Kritis (Tindak Lanjut Audit 21 Nov 2026)

## 1. Konteks

Audit menyeluruh read-only menghasilkan 52 temuan. Dua temuan awalnya diberi tingkat KRITIS. Setelah kode sumber dibaca lebih teliti, satu di antaranya (**eskalasi admin di `Auth::edit_user()`**) ternyata **false positive**: controller sudah memiliki pengecekan `if (!$this->ion_auth->is_admin()) goto ruPX9;` sebelum `remove_from_group()` / `add_to_group()`, sehingga pengguna non-admin tidak dapat mengubah grup.

Yang benar-benar kritis tinggal **satu**:

- `application/controllers/Userguru.php:7` → `editLogin()`.

## 2. Temuan yang diperbaiki

`Userguru::editLogin()` saat ini:

1. Mengambil `id_guru` langsung dari POST tanpa memastikan itu milik akun yang sedang login (guru dapat mengubah kredensial guru lain).
2. Query memakai *condition string* (`"id_guru=\"".$id_guru."\""` dan `"email=\"".$email."\""`) yang tidak di-escape oleh Query Builder CI3 → SQL injection.
3. Field `old` hanya divalidasi `required`, tidak diverifikasi terhadap kata sandi sebenarnya.
4. `delete_user()` dijalankan sebelum `register()`, dan hasil `register()` diabaikan → akun bisa hilang jika registrasi gagal.
5. Sesi tidak diregenerasi setelah kredensial berubah.

Hook `Akses_hook.php` tidak memasukkan `editlogin` ke `$aksi_admin` karena guru memang perlu mengubah kata sandinya sendiri lewat `members/guru/profile.php`. Karena itu perbaikannya dilakukan **di dalam controller**, bukan dengan memblokir method secara global.

## 3. Tujuan

- Guru hanya dapat mengubah username/password akunnya sendiri; admin tetap dapat mengubah akun guru mana pun.
- Kata sandi lama harus terverifikasi sebelum perubahan diterima.
- Tidak ada lagi SQL injection pada method ini.
- Jika registrasi akun baru gagal, data lama tidak hilang.
- Respons JSON (`status`, `text`, `errors`) tetap sama supaya JavaScript existing tidak perlu diubah.

## 4. Ruang lingkup

**Diubah:**
- `application/controllers/Userguru.php` — hanya fungsi `editLogin()`.
- Dokumen PRD ini.

**Tidak diubah:**
- Fitur SPP/pembayaran, tampilan GarudaCBT, sidebar navy Data Siswa/Data Guru.
- Struktur database, view, hook, helper, dan method lain di `Userguru.php`.
- File `application/config/encryption.local.php` (tetap di-ignore git).

Catatan: file controller ini awalnya ter-obfuscasi satu baris. Hasil dekode akan ditulis kembali sebagai PHP normal agar mudah diaudit; perilaku method lain tetap dipertahankan persis.

## 5. Rencana commit

| Commit | Isi |
|---|---|
| PRD | Dokumen ini |
| Commit 1 | Perbaikan `Userguru::editLogin()` |

Perbaikan lain (TINGGI/SEDANG/RENDAH) akan masuk commit terpisah setelah PRD masing-masing disetujui.

## 6. Pengujian

- `php -l application/controllers/Userguru.php` harus lolos.
- `git diff` hanya boleh menunjukkan perubahan pada `editLogin()`.
- Uji runtime:
  - Login sebagai guru, kirim POST `editlogin` dengan `id_guru` milik guru lain → harus ditolak tanpa mengubah data.
  - Kirim `old` salah → harus ditolak.
  - Kirim `username` yang sudah dipakai guru lain → harus ditolak.
  - Alur normal guru mengubah password sendiri masih berfungsi (dicek sampai halaman logout).
- Pastikan tidak ada error baru di konsol browser.

## 7. Rollback

Jika ada regresi, kembalikan file ke `HEAD~1` untuk commit kode:

```bash
git checkout HEAD~1 -- application/controllers/Userguru.php
```

Dokumen PRD tidak perlu dihapus.
