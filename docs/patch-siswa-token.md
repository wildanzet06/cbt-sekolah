# Patch token per ujian — `application/controllers/Siswa.php`

PRD acuan: `docs/PRD-UJIAN-SIMPEL.md` bagian 5.5 dan 6.6.

File `Siswa.php` ter-obfuscate (goto + string escape), jadi patch dibuat **sesempit
mungkin: satu statement** pada jalur verifikasi token di `validasiSiswa()`.
Tidak ada baris lain yang diubah.

## Sebelum

```php
pgnsg: $token = $this->cbt->getToken(); goto IGOQK;
```

`getToken()` membaca **satu baris global** `cbt_token`, sehingga dua ujian yang
berjalan bersamaan tidak bisa memakai token berbeda.

## Sesudah

```php
pgnsg: $token = $this->db->select("IFNULL(NULLIF(j.token_ujian, ''), (SELECT token FROM cbt_token LIMIT 1)) AS token", FALSE)->from("cbt_jadwal j")->where("j.id_jadwal", $info->id_jadwal)->get()->row(); if (!$token || $token->token === null) { $token = $this->cbt->getToken(); } goto IGOQK;
```

- `$info` pada titik itu sudah berisi baris `cbt_jadwal` dari `getJadwalById($id_jadwal)`.
- Token ujian dipakai bila `cbt_jadwal.token_ujian` terisi.
- Bila kosong/NULL → **fallback ke token global** (`cbt_token`), sehingga ujian lama
  yang belum punya `token_ujian` tetap berjalan seperti sebelumnya.
- Verifikasi lama tidak diubah: `$token->token == $token_siswa`.

## Kenapa aman

| Alasan | Keterangan |
|---|---|
| Satu titik saja | `getToken()` hanya dipanggil satu kali di `Siswa.php`, tepat di jalur verifikasi token (dicek sebelum patch). |
| Fallback penuh | Ujian lama dengan `token_ujian` NULL memakai token global. |
| Tidak mengubah alur | `$data["token"]`, `token_msg`, dan seluruh percabangan goto tidak disentuh. |
| Halaman Token tetap jalan | `cbttoken` masih mengubah token global (dipakai sebagai fallback). |

## Rollback

Kembalikan satu statement ke versi "Sebelum", atau timpa file dengan backup:

```
docs/backup/Siswa.php.sebelum-patch
```

## Cara menguji

1. Buat 2 ujian pada tanggal yang sama, kelas berbeda, token berbeda (mis. `ABC123`
   dan `XYZ789`) lewat Daftar Ujian → Buat Ujian Baru.
2. Pastikan `SELECT id_jadwal, token_ujian FROM cbt_jadwal` menunjukkan token berbeda.
3. Login siswa → masuk ujian dengan token ujian **yang salah** → harus gagal.
4. Ulangi dengan token yang benar → harus masuk.
5. Uji ujian lama (tanpa `token_ujian`) dengan token global → harus tetap masuk.

Verifikasi SQL (sudah dijalankan pada data uji):

```sql
SELECT j.id_jadwal,
       IFNULL(NULLIF(j.token_ujian,''), (SELECT token FROM cbt_token LIMIT 1)) AS token
FROM cbt_jadwal j ORDER BY j.id_jadwal;
```

Hasil uji: ujian lama → token global (`HILZCX`), ujian baru → token masing-masing.
