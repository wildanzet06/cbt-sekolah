<!DOCTYPE html>
<html>
<head>
    <title>Kwitansi - <?= $pembayaran->nama; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .kwitansi { border: 2px solid #333; padding: 30px; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        table { width: 100%; margin-bottom: 30px; }
        td { padding: 5px; }
        .signature { margin-top: 50px; text-align: right; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px dashed #999; text-align: center; font-size: 11px; color: #666; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">ðŸ–¨ï¸ Print</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px;">âœ• Close</button>
    </div>
    
    <div class="kwitansi">
        <div class="header">
            <h2 style="margin: 0;"><?= $setting->nama_aplikasi ?? 'SEKOLAH'; ?></h2>
            <p style="margin: 5px 0;">KWITANSI PEMBAYARAN</p>
            <p style="margin: 5px 0; font-size: 12px;">No: <?= str_pad($pembayaran->id, 6, '0', STR_PAD_LEFT); ?>/KWT/<?= date('Y', strtotime($pembayaran->tanggal_bayar)); ?></p>
        </div>
        
        <table>
            <tr>
                <td style="width: 150px;"><strong>Telah Terima Dari</strong></td>
                <td>: <?= $pembayaran->nama; ?></td>
            </tr>
            <tr>
                <td><strong>NIS</strong></td>
                <td>: <?= $pembayaran->nis; ?></td>
            </tr>
            <tr>
                <td><strong>Kelas</strong></td>
                <td>: <?= $this->Pembayaran_model->get_nama_kelas_siswa($pembayaran->siswa_id); ?></td>
            </tr>
            <tr>
                <td><strong>Untuk Pembayaran</strong></td>
                <td>: <strong><?= html_escape($pembayaran->untuk); ?></strong></td>
            </tr>
            <tr>
                <td><strong>Jumlah</strong></td>
                <td>: <strong style="font-size: 18px;">Rp <?= number_format($pembayaran->jumlah_bayar, 0, ',', '.'); ?></strong></td>
            </tr>
            <tr>
                <td><strong>Metode Pembayaran</strong></td>
                <td>: <?= ucfirst(str_replace('_', ' ', $pembayaran->metode_pembayaran)); ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal Pembayaran</strong></td>
                <td>: <?= date('d F Y', strtotime($pembayaran->tanggal_bayar)); ?></td>
            </tr>
        </table>
        
        <div class="signature">
            <p><?= date('d F Y'); ?></p>
            <p style="margin-top: 80px;"><strong>_______________________</strong></p>
            <p>Admin Keuangan</p>
        </div>
        
        <div class="footer">
        <div style="margin-top:20px; display:flex; align-items:center; gap:12px; justify-content:center;">
            <div id="qr-kwitansi" style="width:100px;height:100px;"></div>
            <div style="font-size:10px; color:#555; text-align:left; max-width:260px;">
                <strong>Scan untuk verifikasi</strong><br>
                <span style="word-break:break-all;"><?= html_escape($url_verifikasi); ?></span>
            </div>
        </div>
        
            <p>Kwitansi ini dicetak secara otomatis oleh sistem</p>
            <p>Dicetak pada: <?= date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
    
    <script src="<?= base_url() ?>/assets/app/js/qrcode.min.js"></script>
    <script>
        window.onload = function () {
            var el = document.getElementById('qr-kwitansi');
            if (el && window.QRCode) {
                new QRCode(el, {
                    text: <?= json_encode($url_verifikasi, JSON_UNESCAPED_SLASHES); ?>,
                    width: 100,
                    height: 100,
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        };
    </script>
</body>
</html>
