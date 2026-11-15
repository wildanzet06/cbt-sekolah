<?php
// Hitung ringkasan
$jumlah_siswa = count($tunggakan);
$total_bulan  = 0;
foreach ($tunggakan as $t) {
    $total_bulan += count($t->belum);
}
$total_rupiah = $total_bulan * $nominal;
$daftar_bulan = $this->Pembayaran_model->daftar_bulan;
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-exclamation-circle text-danger mr-1"></i> Tunggakan SPP</h1>
                    <small class="text-muted">Siswa yang belum lunas sampai bulan yang dipilih</small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran/export_tagihan?status=tunggakan&tahun=' . (int) $tahun); ?>"
                           class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Ekspor Excel
                        </a>
                        <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Filter -->
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="get" action="<?= site_url('tagihan'); ?>" class="filter-keuangan">
                        <input type="hidden" name="status" value="tunggakan">
                        <div class="form-row">
                            <div class="col-6 col-md-3">
                                <div class="form-group mb-2">
                                    <label>Tahun</label>
                                    <input type="number" name="tahun" value="<?= (int) $tahun; ?>" min="2000" max="2100"
                                           class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-group mb-2">
                                    <label>Dicek sampai bulan</label>
                                    <select name="sampai" class="form-control form-control-sm">
                                        <?php foreach ($daftar_bulan as $i => $nama): ?>
                                            <option value="<?= $i + 1; ?>" <?= ($sampai == $i + 1) ? 'selected' : ''; ?>>
                                                <?= $nama; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-3 d-flex align-items-end">
                                <div class="form-group mb-2 w-100">
                                    <button type="submit" class="btn btn-primary btn-sm btn-block">
                                        <i class="fas fa-filter"></i> Tampilkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <small class="text-muted d-block mt-2">
                        Nominal SPP saat ini: <b>Rp <?= number_format($nominal, 0, ',', '.'); ?></b> per bulan.
                        Bulan dihitung dari Januari sampai bulan yang dipilih.
                    </small>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="row">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $jumlah_siswa; ?></h3>
                            <p>Siswa memiliki tunggakan / menunggu</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $total_bulan; ?></h3>
                            <p>Total bulan belum dibayar</p>
                        </div>
                        <div class="icon"><i class="fas fa-calendar-times"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>Rp <?= number_format($total_rupiah, 0, ',', '.'); ?></h3>
                            <p>Perkiraan total tunggakan</p>
                        </div>
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Daftar Siswa Menunggak &mdash; <?= (int) $tahun; ?></h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="tableTunggakan" class="table table-sm table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Belum Dibayar</th>
                                <th>Menunggu Verifikasi</th>
                                <th>Jumlah Bulan</th>
                                <th>Total Tunggakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tunggakan as $row): ?>
                                <?php
                                $kelas   = $this->Pembayaran_model->get_nama_kelas_siswa($row->id_siswa);
                                $n_belum = count($row->belum);
                                $total   = $n_belum * $nominal;
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= html_escape($row->nis); ?></td>
                                    <td><?= html_escape($row->nama); ?></td>
                                    <td><?= html_escape($kelas); ?></td>
                                    <td>
                                        <?php foreach ($row->belum as $b): ?>
                                            <span class="badge badge-danger"><?= $b; ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($row->belum)): ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php foreach ($row->menunggu as $b): ?>
                                            <span class="badge badge-warning"><?= $b; ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($row->menunggu)): ?>-<?php endif; ?>
                                    </td>
                                    <td><?= $n_belum; ?></td>
                                    <td>Rp <?= number_format($total, 0, ',', '.'); ?></td>
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
    $(function () {
        $('#tableTunggakan').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'language': {'emptyTable': 'Tidak ada tunggakan pada periode ini'}
        });
    });
</script>
