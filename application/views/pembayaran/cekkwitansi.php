<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($judul); ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px 16px; min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6fa; color: #26303e;
            display: flex; align-items: flex-start; justify-content: center;
        }
        .kartu {
            width: 100%; max-width: 460px; background: #fff; border-radius: 14px;
            box-shadow: 0 10px 30px rgba(16, 42, 67, .12); overflow: hidden;
        }
        .kepala {
            padding: 22px 24px; text-align: center; color: #fff;
        }
        .kepala.sah { background: linear-gradient(135deg, #1e9d5b, #137a45); }
        .kepala.tidak { background: linear-gradient(135deg, #d9534f, #a7332f); }
        .kepala h1 { margin: 0 0 4px; font-size: 19px; }
        .kepala p { margin: 0; font-size: 13px; opacity: .9; }
        .ikon { font-size: 42px; line-height: 1; margin-bottom: 6px; }
        .isi { padding: 22px 24px 26px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 7px 0; vertical-align: top; }
        td.kunci { color: #6b7a8d; width: 42%; }
        td.nilai { font-weight: 600; }
        .total { font-size: 20px; color: #0f5132; }
        .catatan {
            margin-top: 18px; padding: 12px 14px; border-radius: 10px;
            background: #f1f5f9; color: #55657a; font-size: 12px; line-height: 1.5;
        }
        .kode { font-family: Consolas, monospace; font-size: 12px; word-break: break-all; color: #8a97a8; }
        .kaki { text-align: center; padding: 14px; color: #8a97a8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="kartu">
        <div class="kepala <?= $sah ? 'sah' : 'tidak'; ?>">
            <div class="ikon"><?= $sah ? '&#10004;' : '&#10006;'; ?></div>
            <h1><?= $sah ? 'Kwitansi Asli' : 'Kwitansi Tidak Valid'; ?></h1>
            <p><?= $sah ? 'Data pembayaran terverifikasi di sistem sekolah' : 'Kode verifikasi tidak cocok atau data tidak ditemukan'; ?></p>
        </div>

        <div class="isi">
            <?php if ($sah): ?>
                <table>
                    <tr>
                        <td class="kunci">Nama siswa</td>
                        <td class="nilai"><?= html_escape($nama); ?></td>
                    </tr>
                    <tr>
                        <td class="kunci">Nomor kwitansi</td>
                        <td class="nilai"><?= str_pad((int) $bayar->id, 6, '0', STR_PAD_LEFT); ?>/KWT/<?= date('Y', strtotime($bayar->tanggal_bayar)); ?></td>
                    </tr>
                    <tr>
                        <td class="kunci">Untuk pembayaran</td>
                        <td class="nilai"><?= html_escape(isset($bayar->untuk) ? $bayar->untuk : ($bayar->bulan . ' ' . $bayar->tahun)); ?></td>
                    </tr>
                    <tr>
                        <td class="kunci">Metode</td>
                        <td class="nilai"><?= html_escape(ucfirst(str_replace('_', ' ', $bayar->metode_pembayaran))); ?></td>
                    </tr>
                    <tr>
                        <td class="kunci">Tanggal bayar</td>
                        <td class="nilai"><?= date('d/m/Y', strtotime($bayar->tanggal_bayar)); ?></td>
                    </tr>
                    <tr>
                        <td class="kunci">Jumlah</td>
                        <td class="nilai total">Rp <?= number_format((float) $bayar->jumlah_bayar, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td class="kunci">Status</td>
                        <td class="nilai"><?= $bayar->status_pembayaran === 'success' ? 'Terverifikasi (Lunas)' : ucfirst(html_escape($bayar->status_pembayaran)); ?></td>
                    </tr>
                </table>

                <div class="catatan">
                    Kwitansi ini dinyatakan <b>ASLI</b> karena cocok dengan data pembayaran
                    yang tersimpan di sistem <?= html_escape(isset($setting->nama_aplikasi) ? $setting->nama_aplikasi : 'sekolah'); ?>.
                    Nama siswa disamarkan untuk menjaga privasi.
                </div>
            <?php else: ?>
                <div class="catatan" style="background:#fdecea;color:#842029;">
                    Kwitansi dengan kode ini tidak ditemukan atau sudah tidak berlaku.
                    Silakan hubungi bagian keuangan sekolah untuk memastikan.
                </div>
            <?php endif; ?>

            <div class="kode">Kode verifikasi: <?= html_escape($kode !== '' ? $kode : '-'); ?></div>
        </div>

        <div class="kaki">
            <?= html_escape(isset($setting->nama_aplikasi) ? $setting->nama_aplikasi : ''); ?>
            &middot; <?= date('d/m/Y H:i'); ?>
        </div>
    </div>
</body>
</html>
