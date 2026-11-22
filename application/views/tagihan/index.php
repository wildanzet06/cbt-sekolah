<?php
$rp = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };

$tot_nominal = 0; $tot_terbayar = 0; $tot_sisa = 0;
foreach ($tagihan as $t) {
    $tot_nominal  += $t->nominal_bersih;
    $tot_terbayar += $t->terbayar;
    $tot_sisa     += $t->sisa;
}
$qs_filter = http_build_query(array_filter($filter));
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-5 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-file-invoice-dollar text-primary mr-1"></i> Daftar Tagihan</h1>
                    <small class="text-muted">Tagihan siswa per jenis, bulan, dan status</small>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran/export_tagihan?' . $qs_filter); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Ekspor Excel
                        </a>
                        <a href="<?= site_url('tagihan/buat'); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Buat Tagihan
                        </a>
                        <a href="<?= site_url('tagihan/jenis'); ?>" class="btn btn-secondary">
                            <i class="fas fa-tags"></i> Jenis
                        </a>
                        <a href="<?= site_url('tagihan/keringanan'); ?>" class="btn btn-secondary">
                            <i class="fas fa-hand-holding-heart"></i> Keringanan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="get" action="<?= site_url('tagihan'); ?>" class="filter-keuangan" data-auto-filter>
                        <div class="form-row">
                            <div class="col-6 col-md-3">
                                <div class="form-group mb-2">
                                    <label>Jenis</label>
                                    <select name="jenis_id" class="form-control form-control-sm">
                                        <option value="">Semua jenis</option>
                                        <?php foreach ($jenis as $j): ?>
                                            <option value="<?= (int) $j->id; ?>" <?= ($filter['jenis_id'] == $j->id) ? 'selected' : ''; ?>>
                                                <?= html_escape($j->nama); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="form-group mb-2">
                                    <label>Bulan</label>
                                    <select name="bulan" class="form-control form-control-sm">
                                        <option value="">Semua bulan</option>
                                        <?php foreach ($daftar_bulan as $b): ?>
                                            <option value="<?= $b; ?>" <?= ($filter['bulan'] == $b) ? 'selected' : ''; ?>><?= $b; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-6 col-md-2">
                                <div class="form-group mb-2">
                                    <label>Tahun</label>
                                    <input type="number" name="tahun" placeholder="Tahun" min="2000" max="2100"
                                           value="<?= html_escape($filter['tahun']); ?>"
                                           class="form-control form-control-sm">
                                </div>
                            </div>

                            <div class="col-6 col-md-2">
                                <div class="form-group mb-2">
                                    <label>Status</label>
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="">Semua status</option>
                                        <option value="tunggakan" <?= ($filter['status'] == 'tunggakan') ? 'selected' : ''; ?>>Belum lunas (tunggakan)</option>
                                        <option value="belum"     <?= ($filter['status'] == 'belum') ? 'selected' : ''; ?>>Belum dibayar</option>
                                        <option value="cicilan"   <?= ($filter['status'] == 'cicilan') ? 'selected' : ''; ?>>Cicilan</option>
                                        <option value="lunas"     <?= ($filter['status'] == 'lunas') ? 'selected' : ''; ?>>Lunas</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-2">
                                <div class="form-group mb-2">
                                    <label class="d-none d-md-block">&nbsp;</label>
                                    <div class="d-flex">
                                        <noscript>
                                            <button type="submit" class="btn btn-primary btn-sm mr-2 flex-fill">
                                                <i class="fas fa-filter"></i> Tampilkan
                                            </button>
                                        </noscript>
                                        <a href="<?= site_url('tagihan'); ?>" class="btn btn-default btn-sm flex-fill">Reset</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="row">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="small-box bg-info">
                        <div class="inner"><h3><?= $rp($tot_nominal); ?></h3><p>Total tagihan setelah diskon (<?= count($tagihan); ?> data)</p></div>
                        <div class="icon"><i class="fas fa-file-invoice"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="small-box bg-success">
                        <div class="inner"><h3><?= $rp($tot_terbayar); ?></h3><p>Sudah dibayar</p></div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="small-box bg-danger">
                        <div class="inner"><h3><?= $rp($tot_sisa); ?></h3><p>Sisa belum dibayar</p></div>
                        <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card card-outline card-primary">
                <div class="card-body table-responsive">
                    <table id="tableTagihan" class="table table-sm table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Jenis</th>
                                <th>Periode</th>
                                <th>Tagihan</th>
                                <th>Terbayar</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th>Jatuh Tempo</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tagihan as $t): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= html_escape($t->nis); ?></td>
                                    <td><?= html_escape($t->nama); ?></td>
                                    <td>
                                        <?= html_escape($t->jenis_nama); ?>
                                        <?php if ($t->boleh_cicil): ?><span class="badge badge-light">cicilan</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($t->periode_bulan) {
                                            echo html_escape($t->periode_bulan . ' ' . $t->periode_tahun);
                                        } elseif ($t->keterangan) {
                                            echo html_escape($t->keterangan);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?= $rp($t->nominal_bersih); ?>
                                        <?php if ((int) $t->diskon > 0): ?>
                                            <br><small class="text-muted">
                                                <s><?= $rp($t->nominal); ?></s> &minus; diskon <?= $rp($t->diskon); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $rp($t->terbayar); ?></td>
                                    <td><?= $rp($t->sisa); ?></td>
                                    <td>
                                        <?php if ($t->gratis): ?>
                                            <span class="badge badge-primary">Gratis</span>
                                        <?php elseif ($t->status == 'lunas'): ?>
                                            <span class="badge badge-success">Lunas</span>
                                        <?php elseif ($t->status == 'cicilan'): ?>
                                            <span class="badge badge-info">Cicilan</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $t->jatuh_tempo ? date('d/m/Y', strtotime($t->jatuh_tempo)) : '-'; ?></td>
                                    <td class="text-nowrap">
                                        <?php if ($t->status != 'lunas' && !$t->gratis): ?>
                                            <a href="<?= site_url('pembayaran/tunai?tagihan=' . (int) $t->id); ?>"
                                               class="btn btn-success btn-xs" title="Catat pembayaran tunai">
                                                <i class="fas fa-money-bill-wave"></i> Tunai
                                            </a>
                                        <?php endif; ?>
                                            <?php
                                            // Tombol pengingat WhatsApp (wa.me) untuk tagihan yang belum lunas.
                                            $wa_hp   = isset($t->hp) ? wa_nomor($t->hp) : '';
                                            $wa_link = '';
                                            if ($wa_hp !== '') {
                                                $wa_pesan = wa_pesan_tagihan(
                                                    $t,
                                                    isset($setting->nama_aplikasi) ? $setting->nama_aplikasi : '',
                                                    isset($rekening) ? $rekening : array(),
                                                    isset($instruksi_wa) ? $instruksi_wa : ''
                                                );
                                                $wa_link = 'https://wa.me/' . $wa_hp . '?text=' . rawurlencode($wa_pesan);
                                            }
                                            ?>
                                            <?php if ($wa_link !== '' && $t->status != 'lunas' && !$t->gratis): ?>
                                                <a href="<?= $wa_link; ?>" target="_blank" rel="noopener"
                                                   class="btn btn-outline-success btn-xs" title="Kirim pengingat WhatsApp">
                                                    <i class="fab fa-whatsapp"></i> WA
                                                </a>
                                            <?php endif; ?>
                                        <?php if ($t->status != 'lunas' || $t->gratis): ?>
                                            <button type="button" class="btn btn-warning btn-xs"
                                                    onclick="ubahDiskon(<?= (int) $t->id; ?>, <?= (int) $t->nominal; ?>, <?= (int) $t->diskon; ?>, '<?= html_escape(addslashes($t->nama)); ?>')">
                                                <i class="fas fa-percent"></i> Diskon
                                            </button>
                                        <?php endif; ?>
                                        <?php if ((int) $t->jml_pembayaran === 0): ?>
                                            <?= form_open('tagihan/hapus/' . (int) $t->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus tagihan ini?')")); ?>
                                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                                            <?= form_close(); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<div id="tagihan-data" data-qs="<?= html_escape($qs_filter) ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/tagihan-index.js"></script>
</script>
