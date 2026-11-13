<?php $filter_q = array('kelas' => '', 'dari' => $dari, 'sampai' => $sampai); ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1><i class="fas fa-history text-primary"></i> Riwayat Pelanggaran</h1>
                </div>
                <div class="col-sm-5 text-right">
                    <a href="<?= site_url('pelanggaran/rekap'); ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Rekap
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <?= html_escape($siswa->nama); ?>
                        <small class="text-muted">NIS <?= html_escape($siswa->nis); ?> &middot; Kelas <?= html_escape($nama_kelas); ?></small>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-danger p-2">Total <?= (int) $total_poin; ?> poin</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="<?= site_url('pelanggaran/riwayat/' . (int) $siswa->id_siswa); ?>"
                          class="form-row align-items-end mb-3">
                        <div class="form-group col-6 col-md-3">
                            <label>Dari tanggal</label>
                            <input type="date" name="dari" class="form-control" value="<?= html_escape($dari); ?>">
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Sampai tanggal</label>
                            <input type="date" name="sampai" class="form-control" value="<?= html_escape($sampai); ?>">
                        </div>
                        <div class="form-group col-12 col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Tampilkan
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width:40px">No</th>
                                    <th>Tanggal</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th class="text-center">Poin</th>
                                    <th>Keterangan</th>
                                    <th>Dicatat Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= date('d/m/Y', strtotime($r->tanggal)); ?></td>
                                        <td><?= html_escape($r->jenis_pelanggaran); ?></td>
                                        <td class="text-center"><span class="badge badge-danger"><?= (int) $r->poin; ?></span></td>
                                        <td><?= $r->keterangan ? nl2br(html_escape($r->keterangan)) : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?= html_escape($r->dicatat_oleh ? $r->dicatat_oleh : '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($riwayat)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada pelanggaran pada rentang tanggal ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
