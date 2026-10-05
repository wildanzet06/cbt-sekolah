<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1><i class="fas fa-chart-bar text-primary"></i> Rekap Presensi</h1>
                </div>
                <div class="col-sm-5 text-right">
                    <?php if ($kelas_id && !empty($rekap)): ?>
                        <a href="<?= site_url('presensi/export_rekap?' . http_build_query(array('kelas' => $kelas_id, 'bulan' => $bulan, 'tahun' => $tahun))); ?>"
                           class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
                    <?php endif; ?>
                    <a href="<?= site_url('presensi'); ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Isi Presensi
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="get" action="<?= site_url('presensi/rekap'); ?>" class="form-row align-items-end">
                        <div class="form-group col-6 col-md-3">
                            <label>Kelas</label>
                            <select name="kelas" class="form-control" required>
                                <option value="">-- Pilih kelas --</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= (int) $k->id_kelas; ?>" <?= ($kelas_id == $k->id_kelas) ? 'selected' : ''; ?>>
                                        <?= html_escape($k->nama_kelas); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Bulan</label>
                            <select name="bulan" class="form-control">
                                <?php foreach ($daftar_bulan as $i => $nm): ?>
                                    <option value="<?= $i + 1; ?>" <?= ($bulan == $i + 1) ? 'selected' : ''; ?>><?= $nm; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-2">
                            <label>Tahun</label>
                            <input type="number" name="tahun" class="form-control" min="2000" max="2100" value="<?= (int) $tahun; ?>">
                        </div>
                        <div class="form-group col-6 col-md-2">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter"></i> Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($kelas_id): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Kelas <b><?= html_escape($nama_kelas); ?></b> &middot; <?= $daftar_bulan[$bulan - 1] . ' ' . (int) $tahun; ?>
                        &middot; <?= (int) $jml_sesi; ?> sesi tercatat
                    </h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="tableRekap" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Izin</th>
                                <th class="text-center">Sakit</th>
                                <th class="text-center">Alpa</th>
                                <th class="text-center">% Hadir</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($rekap as $r): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= html_escape($r->nis); ?></td>
                                    <td><?= html_escape($r->nama); ?></td>
                                    <td class="text-center"><?= $r->h; ?></td>
                                    <td class="text-center"><?= $r->i; ?></td>
                                    <td class="text-center"><?= $r->s_; ?></td>
                                    <td class="text-center"><?= $r->a > 0 ? '<span class="badge badge-danger">' . $r->a . '</span>' : 0; ?></td>
                                    <td class="text-center">
                                        <?php if ($r->persen === null): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <span class="badge badge-<?= $r->persen >= 90 ? 'success' : ($r->persen >= 75 ? 'warning' : 'danger'); ?>">
                                                <?= $r->persen; ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('presensi/detail/' . (int) $r->id_siswa . '?' . http_build_query(array('bulan' => $bulan, 'tahun' => $tahun, 'kelas' => $kelas_id))); ?>"
                                           class="btn btn-info btn-xs"><i class="fas fa-list"></i> Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="callout callout-info">Pilih kelas dan bulan, lalu klik <i>Tampilkan</i>.</div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
    $(function () {
        if ($('#tableRekap').length) {
            $('#tableRekap').DataTable({
                'paging': false, 'searching': true, 'ordering': true, 'info': false, 'autoWidth': false,
                'language': {'emptyTable': 'Tidak ada data.'}
            });
        }
    });
</script>
