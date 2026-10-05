<?php
$warna = array('H' => 'success', 'I' => 'info', 'S' => 'warning', 'A' => 'danger');
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1><i class="fas fa-user-clock text-primary"></i> Detail Presensi</h1>
                </div>
                <div class="col-sm-4 text-right">
                    <a href="<?= site_url('presensi/rekap?' . http_build_query(array('kelas' => $kelas_id, 'bulan' => $bulan, 'tahun' => $tahun))); ?>"
                       class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Rekap</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary">
                <div class="card-body">
                    <h5 class="mb-1"><?= html_escape($siswa->nama); ?></h5>
                    <div class="text-muted mb-3">NIS <?= html_escape($siswa->nis); ?></div>

                    <form method="get" action="<?= site_url('presensi/detail/' . (int) $siswa->id_siswa); ?>" class="form-inline">
                        <input type="hidden" name="kelas" value="<?= (int) $kelas_id; ?>">
                        <select name="bulan" class="form-control form-control-sm mr-2">
                            <?php foreach ($daftar_bulan as $i => $nm): ?>
                                <option value="<?= $i + 1; ?>" <?= ($bulan == $i + 1) ? 'selected' : ''; ?>><?= $nm; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="tahun" class="form-control form-control-sm mr-2" style="width:90px"
                               min="2000" max="2100" value="<?= (int) $tahun; ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <?php foreach ($status_ref as $kode => $nama): ?>
                    <div class="col-6 col-md-3">
                        <div class="small-box bg-<?= $warna[$kode]; ?>">
                            <div class="inner"><h3><?= (int) $hitung[$kode]; ?></h3><p><?= $nama; ?></p></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr><th>Tanggal</th><th>Jam ke-</th><th>Mata Pelajaran</th><th>Status</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $r): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($r->tanggal)); ?></td>
                                    <td><?= (int) $r->jam_ke; ?></td>
                                    <td><?= html_escape($r->mapel_nama); ?></td>
                                    <td><span class="badge badge-<?= $warna[$r->status]; ?>"><?= $status_ref[$r->status]; ?></span></td>
                                    <td><?= html_escape($r->keterangan); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($riwayat)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Belum ada catatan presensi pada bulan ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>
