<?php
$rp = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-money"></i> Pembayaran Sekolah</h1>
    </section>
    <section class="content">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?= $this->session->flashdata('success'); ?>
            </div>
        <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?= $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-7">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Tagihan Saya</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-borderless">
                            <tr><td width="100"><strong>Nama</strong></td><td>: <?= html_escape($siswa->nama); ?></td></tr>
                            <tr><td><strong>NIS</strong></td><td>: <?= html_escape($siswa->nis); ?></td></tr>
                        </table>
                        <hr>

                        <?php if (empty($tagihan)): ?>
                            <p class="text-center text-muted">Belum ada tagihan untuk Anda.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th>Tagihan</th>
                                        <th>Jumlah</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($tagihan as $t): ?>
                                    <?php
                                    $label = $t->jenis_nama;
                                    if (!empty($t->periode_bulan)) {
                                        $label .= ' ' . $t->periode_bulan . ' ' . $t->periode_tahun;
                                    } elseif (!empty($t->keterangan)) {
                                        $label .= ' - ' . $t->keterangan;
                                    }
                                    $maks = $t->sisa - $t->pending_manual;
                                    $min  = min($min_cicilan, max($maks, 0));
                                    ?>
                                    <tr>
                                        <td>
                                            <?= html_escape($label); ?>
                                            <?php if ($t->jatuh_tempo): ?>
                                                <br><small class="text-muted">Jatuh tempo <?= date('d/m/Y', strtotime($t->jatuh_tempo)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $rp($t->nominal_bersih); ?>
                                            <?php if ((int) $t->diskon > 0): ?>
                                                <br><small class="text-muted">keringanan <?= $rp($t->diskon); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $rp($t->sisa); ?></td>
                                        <td>
                                            <?php if ($t->gratis): ?>
                                                <span class="label label-primary">Gratis</span>
                                            <?php elseif ($t->status == 'lunas'): ?>
                                                <span class="label label-success">Lunas</span>
                                            <?php elseif ($t->status == 'cicilan'): ?>
                                                <span class="label label-info">Cicilan</span>
                                            <?php else: ?>
                                                <span class="label label-danger">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($t->status == 'lunas'): ?>
                                                &nbsp;
                                            <?php elseif ($maks <= 0 || (!$t->boleh_cicil && $t->pending_manual > 0)): ?>
                                                <small class="text-muted">menunggu verifikasi</small>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-success btn-xs btn-bayar"
                                                        data-id="<?= (int) $t->id; ?>"
                                                        data-label="<?= html_escape($label); ?>"
                                                        data-sisa="<?= (int) $t->sisa; ?>"
                                                        data-maks="<?= (int) $maks; ?>"
                                                        data-min="<?= (int) $min; ?>"
                                                        data-cicil="<?= $t->boleh_cicil ? 1 : 0; ?>">
                                                    <i class="fa fa-credit-card"></i> Bayar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Riwayat Pembayaran</h3>
                    </div>
                    <div class="box-body">
                        <?php if(empty($riwayat)): ?>
                            <p class="text-center text-muted">Belum ada riwayat.</p>
                        <?php else: ?>
                            <table class="table table-condensed table-bordered">
                                <thead>
                                    <tr><th>Untuk</th><th>Jumlah</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($riwayat as $r): ?>
                                    <tr>
                                        <td><?= html_escape($r->untuk); ?></td>
                                        <td><?= $rp($r->jumlah_bayar); ?></td>
                                        <td>
                                            <?php if($r->status_pembayaran == 'success'): ?>
                                                <span class="label label-success">Lunas</span>
                                            <?php elseif($r->status_pembayaran == 'pending'): ?>
                                                <span class="label label-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="label label-danger">Gagal</span>
                                                <?php if (!empty($r->catatan_admin)): ?>
                                                    <br><small class="text-muted"><?= html_escape($r->catatan_admin); ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Jendela pembayaran -->
<div class="modal fade" id="modalBayar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open_multipart('pembayaran/submit_pembayaran_manual', array('id' => 'formBayar')); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Bayar: <span id="f_label"></span></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="tagihan_id" id="f_tagihan">

                <div class="form-group">
                    <label>Jumlah yang dibayar (Rp)</label>
                    <input type="number" name="jumlah_bayar" id="f_jumlah" class="form-control" min="1">
                    <p class="help-block" id="f_info"></p>
                </div>

                <div class="form-group">
                    <label>Metode pembayaran</label>
                    <div class="radio"><label><input type="radio" name="metode" value="manual" checked> Transfer manual (upload bukti)</label></div>
                    <div class="radio"><label><input type="radio" name="metode" value="midtrans"> Bayar online (Midtrans)</label></div>
                </div>

                <div id="blok_manual">
                    <p class="text-muted">Transfer ke BCA 1234567890 a.n Sekolah, lalu upload bukti.</p>
                    <div class="form-group">
                        <label>Upload bukti (JPG/PNG/PDF, maks 2MB)</label>
                        <input type="file" name="bukti_transfer" id="f_bukti" class="form-control" accept="image/*,.pdf">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Lanjutkan</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
$(function () {
    var urlManual   = '<?= site_url('pembayaran/submit_pembayaran_manual'); ?>';
    var urlMidtrans = '<?= site_url('pembayaran/bayar_midtrans'); ?>';

    function rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

    $('.btn-bayar').on('click', function () {
        var b = $(this);
        var maks = parseInt(b.data('maks'), 10);
        var min  = parseInt(b.data('min'), 10);
        var cicil = parseInt(b.data('cicil'), 10) === 1;

        $('#f_tagihan').val(b.data('id'));
        $('#f_label').text(b.data('label'));
        $('#f_jumlah').val(maks);

        if (cicil) {
            $('#f_jumlah').prop('readonly', false).attr({min: min, max: maks});
            $('#f_info').text('Tagihan ini boleh dicicil: isi antara ' + rupiah(min) + ' dan ' + rupiah(maks) + '.');
        } else {
            $('#f_jumlah').prop('readonly', true);
            $('#f_info').text('Tagihan ini dibayar penuh sebesar ' + rupiah(maks) + '.');
        }

        $('input[name=metode][value=manual]').prop('checked', true).trigger('change');
        $('#modalBayar').modal('show');
    });

    // Ganti metode: ubah tujuan form & tampilan unggah bukti
    $('input[name=metode]').on('change', function () {
        var manual = $('input[name=metode]:checked').val() === 'manual';
        $('#formBayar').attr('action', manual ? urlManual : urlMidtrans);
        $('#blok_manual').toggle(manual);
        $('#f_bukti').prop('required', manual);
    }).trigger('change');
});
</script>
