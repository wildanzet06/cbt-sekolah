<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-6 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fa fa-file-text text-primary mr-1"></i> Laporan Pembayaran SPP</h1>
                    <small class="text-muted">Rekap penerimaan dan detail transaksi SPP</small>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                        <a href="<?= site_url('pembayaran/export_laporan?' . http_build_query(array_filter($filter))); ?>" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> Ekspor Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter Form -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Filter Laporan</h3>
                </div>
                <div class="card-body">
                    <form method="get" action="<?= site_url('pembayaran/laporan'); ?>" class="filter-keuangan" data-auto-filter>
                        <div class="form-row">
                            <div class="col-12 col-md-4">
                                <div class="form-group mb-0">
                                    <label>Bulan</label>
                                    <select name="bulan" class="form-control">
                                        <option value="">-- Semua Bulan --</option>
                                        <?php
                                        $bulan_arr = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                        foreach($bulan_arr as $b): ?>
                                            <option value="<?= $b; ?>" <?= $filter['bulan'] == $b ? 'selected' : ''; ?>><?= $b; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group mb-0">
                                    <label>Tahun</label>
                                    <input type="number" name="tahun" class="form-control" value="<?= $filter['tahun'] ?: date('Y'); ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group mb-0">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">-- Semua Status --</option>
                                        <option value="success" <?= $filter['status'] == 'success' ? 'selected' : ''; ?>>Lunas</option>
                                        <option value="pending" <?= $filter['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?= $filter['status'] == 'failed' ? 'selected' : ''; ?>>Gagal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="page-aksi">
                                <noscript><button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Tampilkan</button></noscript>
                                <a href="<?= site_url('pembayaran/laporan'); ?>" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rekap per Bulan -->
            <?php if(!empty($total_by_bulan)): ?>
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">Rekap Penerimaan per Bulan (<?= $filter['tahun'] ?: date('Y'); ?>)</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Jumlah Transaksi</th>
                                <th>Total Penerimaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($total_by_bulan as $rekap): ?>
                            <tr>
                                <td><?= $rekap->bulan; ?></td>
                                <td><?= $rekap->jumlah; ?> transaksi</td>
                                <td class="text-nowrap">Rp <?= number_format($rekap->total, 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detail Laporan -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detail Pembayaran (<?= count($laporan); ?> data)</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="tableLaporan" class="table table-sm table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Untuk</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach($laporan as $row): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $row->nis; ?></td>
                                <td><?= $row->nama; ?></td>
                                <td><?= $this->Pembayaran_model->get_nama_kelas_siswa($row->siswa_id); ?></td>
                                <td><?= html_escape($row->untuk); ?></td>
                                <td class="text-nowrap">Rp <?= number_format($row->jumlah_bayar, 0, ',', '.'); ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $row->metode_pembayaran)); ?></td>
                                <td>
                                    <?php if($row->status_pembayaran == 'success'): ?>
                                        <span class="badge badge-success">Lunas</span>
                                    <?php elseif($row->status_pembayaran == 'pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Gagal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($row->tanggal_bayar)); ?></td>
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
        $('#tableLaporan').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'language': {
                'search': 'Cari:',
                'lengthMenu': 'Tampilkan _MENU_ data',
                'info': 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                'paginate': {'previous': 'Sebelumnya', 'next': 'Berikutnya'},
                'emptyTable': 'Tidak ada data pada filter ini'
            }
        });
    });
</script>
