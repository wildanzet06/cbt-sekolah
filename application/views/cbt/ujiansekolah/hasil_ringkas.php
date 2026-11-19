<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="content-wrapper bg-white pt-4">
    <style>
        .hasil-card { border: 1px solid #e5e9f0; border-radius: 10px; background: #fff; }
        .hasil-card label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .25rem; }
        .btn-gelap { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-gelap:hover { background: #123862; color: #fff; }
        .hasil-tabel th { font-size: .75rem; text-transform: uppercase; color: #64748b; }
        .hasil-tabel td { vertical-align: middle; }
    </style>

    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-2">
                    <h1 class="mb-1"><?= html_escape($judul) ?></h1>
                    <p class="text-muted mb-0">
                        <?= html_escape($subjudul) ?> &middot;
                        Tahun <?= html_escape($tp_active->tahun) ?> &middot;
                        Semester <?= html_escape($smt_active->nama_smt) ?>
                    </p>
                </div>
                <div class="mb-2">
                    <a href="<?= base_url('ujiansekolah') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                    </a>
                    <a href="<?= base_url('ujiansekolah/hasil_lama') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chart-line mr-1"></i>Analisis &amp; rekap
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="hasil-card p-3 mb-4">
                <form method="get" action="<?= base_url('ujiansekolah/hasil') ?>" class="form-row align-items-end">
                    <div class="col-md-5 mb-2">
                        <label>Ujian</label>
                        <select class="form-control form-control-sm" name="jadwal" onchange="this.form.submit()">
                            <?php if (count($jadwal_list) === 0): ?>
                                <option value="0">Belum ada ujian</option>
                            <?php endif; ?>
                            <?php foreach ($jadwal_list as $j): ?>
                                <option value="<?= (int) $j->id_jadwal ?>" <?= (int) $j->id_jadwal === (int) $id_jadwal ? 'selected' : '' ?>>
                                    <?= html_escape(!empty($j->judul) ? $j->judul : ($j->kode_jenis . ' ' . $j->nama_mapel)) ?>
                                    — <?= html_escape($j->label_kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Kelas</label>
                        <select class="form-control form-control-sm" name="kelas" onchange="this.form.submit()">
                            <option value="0">Semua kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                                <option value="<?= (int) $k->id_kelas ?>" <?= (int) $k->id_kelas === (int) $id_kelas ? 'selected' : '' ?>>
                                    <?= html_escape($k->nama_kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2 text-right">
                        <button type="button" class="btn btn-gelap btn-sm" id="btn-csv">
                            <i class="fas fa-file-csv mr-1"></i>Unduh CSV
                        </button>
                    </div>
                </form>
            </div>

            <div class="row mb-3">
                <div class="col-4">
                    <div class="hasil-card p-3 text-center">
                        <div class="h4 mb-0 font-weight-bold"><?= count($nilai) ?></div>
                        <div class="small text-muted">Peserta</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="hasil-card p-3 text-center">
                        <div class="h4 mb-0 font-weight-bold"><?= number_format((float) $rata, 1, ',', '.') ?></div>
                        <div class="small text-muted">Rata-rata</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="hasil-card p-3 text-center">
                        <div class="h4 mb-0 font-weight-bold"><?= number_format((float) $maks, 1, ',', '.') ?></div>
                        <div class="small text-muted">Tertinggi</div>
                    </div>
                </div>
            </div>

            <div class="hasil-card mb-4">
                <div class="p-3 border-bottom">
                    <h5 class="font-weight-bold mb-0">
                        <?= $ujian ? html_escape(!empty($ujian->judul) ? $ujian->judul : ($ujian->kode_jenis . ' ' . $ujian->nama_mapel)) : 'Nilai Ujian' ?>
                    </h5>
                    <?php if ($ujian): ?>
                        <div class="small text-muted">
                            <?= html_escape($ujian->label_kelas) ?> &middot;
                            <?= date('d M Y', strtotime((string) $ujian->tgl_mulai)) ?> &middot;
                            <?= (int) $ujian->durasi_ujian ?> menit
                        </div>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table hasil-tabel mb-0" id="tabel-hasil">
                        <thead class="bg-light">
                        <tr>
                            <th class="pl-3" style="width:50px">#</th>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Kelas</th>
                            <th class="text-center">PG</th>
                            <th class="text-center">Esai</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($nilai) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada nilai untuk ujian ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php $no = 1; foreach ($nilai as $n): ?>
                            <tr>
                                <td class="pl-3"><?= $no++ ?></td>
                                <td class="font-weight-bold"><?= html_escape($n->nama ?: '-') ?></td>
                                <td><?= html_escape($n->nis ?: '-') ?></td>
                                <td><?= html_escape($n->nama_kelas ?: '-') ?></td>
                                <td class="text-center"><?= (int) $n->pg_benar ?> benar</td>
                                <td class="text-center"><?= html_escape($n->essai_nilai ?: '0') ?></td>
                                <td class="text-center font-weight-bold"><?= number_format((float) $n->total, 1, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php if ((int) $n->dikoreksi === 1): ?>
                                        <span class="badge badge-success">Dikoreksi</span>
                                    <?php else: ?>
                                        <span class="badge badge-light border">Belum</span>
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

<script>
    (function () {
        $('#btn-csv').on('click', function () {
            var rows = [];
            $('#tabel-hasil tr').each(function () {
                var cols = [];
                $(this).find('th, td').each(function () {
                    cols.push('"' + $(this).text().replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"');
                });
                if (cols.length) { rows.push(cols.join(',')); }
            });
            var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'hasil-ujian.csv';
            a.click();
        });
    })();
</script>
