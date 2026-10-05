<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-file-text"></i> Laporan Pembayaran SPP</h1>
        <div class="pull-right">
            <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </section>

    <section class="content">
        <!-- Filter Form -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Filter Laporan</h3>
            </div>
            <div class="box-body">
                <form method="get" action="<?= site_url('pembayaran/laporan'); ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tahun</label>
                                <input type="number" name="tahun" class="form-control" value="<?= $filter['tahun'] ?: date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
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
                    <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Tampilkan</button>
                    <a href="<?= site_url('pembayaran/laporan'); ?>" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
                    <a href="<?= site_url('pembayaran/export_laporan?' . http_build_query(array_filter($filter))); ?>" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Ekspor Excel</a>
                </form>
            </div>
        </div>

        <!-- Rekap per Bulan -->
        <?php if(!empty($total_by_bulan)): ?>
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Rekap Penerimaan per Bulan (<?= $filter['tahun'] ?: date('Y'); ?>)</h3>
            </div>
            <div class="box-body">
                <table class="table table-bordered">
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
                            <td>Rp <?= number_format($rekap->total, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detail Laporan -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Detail Pembayaran (<?= count($laporan); ?> data)</h3>
            </div>
            <div class="box-body">
                <table id="tableLaporan" class="table table-bordered table-striped">
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
                            <td>Rp <?= number_format($row->jumlah_bayar, 0, ',', '.'); ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $row->metode_pembayaran)); ?></td>
                            <td>
                                <?php if($row->status_pembayaran == 'success'): ?>
                                    <span class="label label-success">Lunas</span>
                                <?php elseif($row->status_pembayaran == 'pending'): ?>
                                    <span class="label label-warning">Pending</span>
                                <?php else: ?>
                                    <span class="label label-danger">Gagal</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($row->tanggal_bayar)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
            'autoWidth': false
        });
    });
</script>