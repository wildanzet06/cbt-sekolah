# PRD — Aktivasi Akun Siswa Aman

## 1. Masalah

Menambahkan murid di **Data Siswa** belum membuat murid bisa login. Murid harus diaktifkan melalui **User Management → Data User Siswa**.

Ada dua kelemahan pada alur aktivasi di `application/controllers/Usersiswa.php`:

1. Tombol **Aktifkan** tetap memanggil registrasi akun walaupun `master_siswa.password` kosong atau `master_siswa.username` kosong.
2. Fungsi `registerSiswa()` mengembalikan array `['status' => true/false, 'id' => ...]`, tetapi `aktifkan()` menaruh array itu langsung ke `status`. Karena array selalu dianggap benar oleh JavaScript, kegagalan registrasi tetap ditampilkan sebagai **Sukses**.
3. `aktifkanSemua()` menambah jumlah “siswa diaktifkan” tanpa mengecek hasil aktivasi, sehingga jumlahnya bisa berlebih.

## 2. Tujuan

Mencegah akun siswa aktif dengan password login kosong, dan memastikan pesan sukses/gagal sesuai hasil registrasi yang sebenarnya.

## 3. Perubahan

### 3.1 `Usersiswa.php` — `private function aktifkan($siswa)`

Sebelum registrasi dijalankan:

- Jika `username` kosong → kembalikan `status: false` dengan pesan agar username diisi dulu di Data Siswa.
- Jika `password` kosong → kembalikan `status: false` dengan pesan agar password diisi dulu di Data Siswa, menyertakan nama siswa.

Registrasi hanya berjalan jika keduanya terisi.

### 3.2 `Usersiswa.php` — hasil registrasi

`status` diambil dari `$reg['status']`, bukan dari array `$reg` secara keseluruhan. Pesan sukses/gagal mengikuti nilai tersebut.

### 3.3 `Usersiswa.php` — `aktifkanSemua()`

Jumlah yang dihitung hanya siswa yang benar-benar berhasil diaktifkan.

## 4. Yang tidak diubah

- Tidak ada perubahan struktur database.
- Tidak ada fitur yang dihapus, termasuk SPP.
- Tidak mengubah tampilan sidebar atau halaman lain.
- Akun siswa yang sudah ada tidak disentuh.
- Password yang sudah ada tidak ditimpa.

## 5. Risiko dan penanganan

| Risiko | Penanganan |
| --- | --- |
| Admin bingung mengapa tombol Aktifkan gagal | Pesan kesalahan menyebut langsung bahwa username/password Data Siswa harus diisi |
| Aktivasi massal berhenti di tengah | Setiap siswa diproses sendiri; yang gagal dilewati, yang berhasil tetap dihitung |
| Registrasi gagal tetapi tetap dilaporkan sukses | `status` kini berasal dari `$reg['status']` |

## 6. Kriteria uji

- Siswa dengan password kosong ditolak saat diaktifkan dan memunculkan pesan yang jelas.
- Siswa dengan username kosong ditolak saat diaktifkan.
- Siswa dengan username dan password terisi tetap bisa diaktifkan seperti biasa.
- Aktivasi massal hanya menghitung akun yang benar-benar berhasil.
- `php -l` lolos.
- Uji mandiri `tools/test-aktivasi-siswa.php` lolos.
- Tidak ada perubahan pada fitur lain.

## 7. Hasil uji

Perintah: `php tools/test-aktivasi-siswa.php`

```
password kosong    status=OK pesan=OK
username kosong    status=OK pesan=OK
data lengkap       status=OK pesan=OK
aktivasi massal     OK -> {"status":true,"jumlah":1,"msg":"1 siswa diaktifkan."}

SEMUA UJI LOLOS
```

## 8. Status

- [x] PRD ini.
- [x] Perubahan kode.
- [x] Uji otomatis fungsi.
- [x] Commit dan ZIP final diperbarui.
