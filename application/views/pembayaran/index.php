<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-6 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fa fa-money text-primary mr-1"></i> Manajemen Pembayaran SPP</h1>
                    <small class="text-muted">Verifikasi pembayaran transfer dan pantau penerimaan SPP</small>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran/laporan'); ?>" class="btn btn-info">
                            <i class="fa fa-file-text"></i> Laporan
                        </a>
                        <a href="<?= site_url('pembayaran/tunggakan'); ?>" class="btn btn-danger">
                            <i class="fa fa-exclamation-circle"></i> Tunggakan
                        </a>
                        <a href="<?= site_url('pembayaran/export_laporan'); ?>" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> Ekspor Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <?php if($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>

            <!-- Info Boxes -->
            <div class="row">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pending Verifikasi</span>
                            <span class="info-box-number"><?= $total_pending; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Sudah Lunas</span>
                            <span class="info-box-number"><?= $total_success; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fa fa-money"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Penerimaan</span>
                            <span class="info-box-number">Rp <?= number_format($total_revenue, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Daftar Transaksi SPP</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="tableSPP" class="table table-sm table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Untuk</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach($pembayaran as $row): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $row->nis; ?></td>
                                <td><?= $row->nama; ?></td>
                                <td><?= $this->Pembayaran_model->get_nama_kelas_siswa($row->siswa_id); ?></td>
                                <td><?= html_escape($row->untuk); ?></td>
                                <td class="text-nowrap">Rp <?= number_format($row->jumlah_bayar, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if($row->metode_pembayaran == 'transfer_manual'): ?>
                                        <span class="badge badge-secondary">Transfer Manual</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Midtrans</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row->status_pembayaran == 'pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php elseif($row->status_pembayaran == 'success'): ?>
                                        <span class="badge badge-success" title="<?= !empty($row->diverifikasi_oleh) ? 'Diverifikasi oleh ' . html_escape($row->diverifikasi_oleh) : ''; ?>">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" title="<?= !empty($row->catatan_admin) ? 'Alasan: ' . html_escape($row->catatan_admin) : ''; ?>">Gagal</span>
                                        <?php if(!empty($row->catatan_admin)): ?><br><small class="text-muted"><?= html_escape($row->catatan_admin); ?></small><?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($row->tanggal_bayar)); ?></td>
                                <td class="text-nowrap">
                                    <?php if($row->status_pembayaran == 'pending' && $row->metode_pembayaran == 'transfer_manual'): ?>
                                        <a href="<?= site_url('pembayaran/verifikasi/'.$row->id); ?>" class="btn btn-sm btn-success mb-1" onclick="return confirm('Yakin ingin memverifikasi pembayaran ini?')">
                                            <i class="fa fa-check"></i> Verifikasi
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger mb-1" onclick="tolakPembayaran(<?= (int) $row->id; ?>)">
                                            <i class="fa fa-times"></i> Tolak
                                        </button>
                                    <?php endif; ?>

                                    <?php if($row->status_pembayaran == 'success'): ?>
                                        <a href="<?= site_url('pembayaran/kwitansi/'.$row->id); ?>" class="btn btn-sm btn-info mb-1">
                                            <i class="fa fa-print"></i> Kwitansi
                                        </a>
                                    <?php endif; ?>

                                    <?php if(!empty($row->bukti_transfer)): ?>
                                        <a href="<?= base_url('uploads/bukti_transfer/'.$row->bukti_transfer); ?>" class="btn btn-sm btn-primary mb-1">
                                            <i class="fa fa-image"></i> Bukti
                                        </a>
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
    $(function () {
        $('#tableSPP').DataTable({
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
                'emptyTable': 'Belum ada transaksi'
            }
        });
    });
</script>

<script>
    // Tombol "Tolak": minta alasan lalu kirim lewat form POST
    function tolakPembayaran(id) {
        var alasan = prompt('Alasan penolakan (misalnya: bukti transfer tidak sesuai):');
        if (alasan === null) return; // dibatalkan
        if (alasan.trim() === '') {
            alert('Alasan penolakan harus diisi.');
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= site_url('pembayaran/tolak/'); ?>' + id;

        var f1 = document.createElement('input');
        f1.type = 'hidden'; f1.name = 'alasan'; f1.value = alasan.trim();
        form.appendChild(f1);

        var f2 = document.createElement('input');
        f2.type = 'hidden';
        f2.name = '<?= $this->security->get_csrf_token_name(); ?>';
        f2.value = '<?= $this->security->get_csrf_hash(); ?>';
        form.appendChild(f2);

        document.body.appendChild(form);
        form.submit();
    }
</script>
