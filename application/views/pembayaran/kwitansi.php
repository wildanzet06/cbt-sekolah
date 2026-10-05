<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-print"></i> Kwitansi Pembayaran</h1>
        <div class="pull-right">
            <a href="<?= site_url('pembayaran/cetak_kwitansi/'.$pembayaran->id); ?>" class="btn btn-primary" target="_blank">
                <i class="fa fa-print"></i> Cetak
            </a>
            <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-body" id="kwitansi-content">
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
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #999; text-align: center; font-size: 11px; color: #666;">
                        <p>Kwitansi ini dicetak secara otomatis oleh sistem</p>
                        <p>Dicetak pada: <?= date('d/m/Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>