<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-6 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fa fa-print text-primary mr-1"></i> Kwitansi Pembayaran</h1>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                        <a href="<?= site_url('pembayaran/cetak_kwitansi/'.$pembayaran->id); ?>" class="btn btn-primary" target="_blank">
                            <i class="fa fa-print"></i> Cetak
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body" id="kwitansi-content">
                <div style="border: 2px solid #333; padding: 30px; max-width: 800px; margin: 0 auto;">
                    <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px;">
                        <h2 style="margin: 0;"><?= $setting->nama_aplikasi ?? 'SEKOLAH'; ?></h2>
                        <p style="margin: 5px 0;">KWITANSI PEMBAYARAN</p>
                        <p style="margin: 5px 0; font-size: 12px;">No: <?= str_pad($pembayaran->id, 6, '0', STR_PAD_LEFT); ?>/KWT/<?= date('Y', strtotime($pembayaran->tanggal_bayar)); ?></p>
                    </div>
                    
                    <table style="width: 100%; margin-bottom: 30px;">
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
                    
                    <div style="margin-top: 50px; text-align: right;">
                        <p><?= date('d F Y'); ?></p>
                        <p style="margin-top: 80px;"><strong>_______________________</strong></p>
                        <p>Admin Keuangan</p>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; align-items: center; gap: 14px; justify-content: center;">
                        <div id="qr-kwitansi" style="width:110px;height:110px;"></div>
                        <div style="font-size: 11px; color:#555; text-align:left; max-width:280px;">
                            <strong>Scan untuk verifikasi</strong><br>
                            Keaslian kwitansi ini dapat dicek dengan memindai QR di samping,
                            atau membuka tautan berikut:
                            <div style="word-break: break-all; color:#0a58ca;"><?= html_escape($url_verifikasi); ?></div>
                        </div>
                    </div>
                     
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #999; text-align: center; font-size: 11px; color: #666;">
                        <p>Kwitansi ini dicetak secara otomatis oleh sistem</p>
                        <p>Dicetak pada: <?= date('d/m/Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>
</div>

<script src="<?= base_url() ?>/assets/app/js/qrcode.min.js"></script>
<script>
(function () {
    var el = document.getElementById('qr-kwitansi');
    if (el && window.QRCode) {
        new QRCode(el, {
            text: <?= json_encode($url_verifikasi, JSON_UNESCAPED_SLASHES); ?>,
            width: 110,
            height: 110,
            correctLevel: QRCode.CorrectLevel.M
        });
    }
})();
</script>