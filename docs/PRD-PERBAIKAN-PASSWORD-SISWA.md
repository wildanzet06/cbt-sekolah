# PRD — Perbaikan Password Login Siswa

## 1. Masalah

Login siswa gagal meskipun username dan password yang terlihat di halaman Data Siswa sudah sama persis.

Pemeriksaan database lokal menemukan:

- 42 akun dengan grup `siswa`.
- 41 akun memiliki `users.password` kosong.
- 1 akun (`sinabil`) memiliki hash password yang valid dan bisa login.

## 2. Penyebab

Aplikasi menyimpan dua nilai password yang berbeda:

| Tempat | Fungsi | Kondisi saat ini |
| --- | --- | --- |
| `master_siswa.password` | Ditampilkan di halaman Data Siswa | Sudah terisi |
| `users.password` | Dipakai ion_auth saat login | 41 akun kosong |

Fungsi `aktifkan()` di `application/controllers/Usersiswa.php` mengambil password dari `master_siswa.password`, lalu mendaftarkan akun ke ion_auth. Jika password kosong atau belum tersinkron saat tombol Aktifkan ditekan, akun `users` tetap dibuat dengan password login kosong.

Jadi yang diketik siswa sebenarnya benar, tetapi “kunci login”-nya belum pernah diisi.

## 3. Tujuan

Mengisi `users.password` untuk akun siswa yang kosong menggunakan password yang sudah ada di `master_siswa.password`, tanpa mengubah password yang tertera di Data Siswa dan tanpa mengubah struktur database.

## 4. Prinsip

- Data lama tetap dipakai.
- Tidak mengubah skema tabel.
- Tidak mengubah username atau grup.
- Akun yang sudah punya password login tidak disentuh, apa pun isinya.
- Ada backup sebelum perubahan.
- Ada mode uji (`dry-run`) sebelum perubahan sungguhan.

## 5. Perubahan yang dibuat

### 5.1 Skrip perbaikan

File baru: `tools/perbaiki-password-siswa.php`

Skrip akan:

1. Membaca koneksi dari `application/config/database.php`.
2. Mengambil siswa yang punya akun grup `siswa`.
3. Melewati akun yang sudah punya password login, termasuk bila berbeda dengan Data Siswa.
4. Melewati baris yang password Data Siswanya kosong.
5. Mengisi `users.password` yang masih kosong dengan hash bcrypt dari `master_siswa.password`.
6. Tidak menampilkan password asli di keluaran skrip.
7. Default hanya simulasi; perubahan sungguhan butuh `--apply`.

### 5.2 Backup

Sebelum `--apply`, database `cbt` dicadangkan ke folder `backups/` dengan nama bertanda waktu. File backup tidak ikut repository.

## 6. Risiko dan penanganan

| Risiko | Penanganan |
| --- | --- |
| Salah mengubah akun yang sudah benar | Skrip hanya mengisi `users.password` yang kosong |
| Password Data Siswa kosong | Baris dilewati dan dilaporkan |
| Perubahan massal salah | Dry-run dulu, lalu backup, lalu apply |
| Siswa sudah mengganti password sendiri | Hash yang sudah ada tidak pernah ditimpa; catatan `cbt_password_diganti` tetap dipakai aplikasi seperti semula |

Catatan: karena pembuatan ulang akun bisa mengembalikan password awal, perbaikan ini tidak menghapus catatan `cbt_password_diganti`.

## 7. Hasil uji

- Dry-run awal melaporkan 41 akun akan diperbaiki dan 1 akun dilewati.
- Backup dibuat: `backups/manual-password-siswa-20261120_123826.sql` (268 KB, 91 tabel).
- Setelah apply, 41 akun diperbaiki dan sisa password login siswa yang kosong = 0.
- Dry-run ulang melaporkan 42 akun sudah punya password login; tidak ada yang perlu diubah.
- Akun `ahmad.afsyad`, yang sebelumnya kosong, berhasil login dengan password di Data Siswa dan sampai ke halaman **Beranda**.
- Halaman login tidak ada error konsol. Dashboard menampilkan dua 404 hanya untuk foto siswa `20260001.jpg` yang belum diunggah; tidak terkait password.

## 8. Status

- [x] Diagnosis awal.
- [x] PRD ini.
- [x] Skrip perbaikan.
- [x] Backup database.
- [x] Dry-run.
- [x] Apply.
- [x] Verifikasi login.
