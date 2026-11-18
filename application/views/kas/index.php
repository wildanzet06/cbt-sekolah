<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-cash-register text-success mr-1"></i> Buku Kas Sekolah</h1>
                    <small class="text-muted">Pemasukan SPP otomatis + pengeluaran &amp; pemasukan lain</small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <a href="<?= site_url('kas/export?' . http_build_query(array('bulan' => $bulan, 'tahun' => $tahun))); ?>"
                           class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Ekspor CSV
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
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= html_escape($this->session->flashdata('success')); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= html_escape($this->session->flashdata('error')); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h4 class="mb-0">Rp <?= number_format($spp_bulan, 0, ',', '.'); ?></h4>
                            <p>SPP diterima (<?= (int) $spp_jml; ?> pembayaran)</p>
                        </div>
                        <div class="icon"><i class="fas fa-receipt"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h4 class="mb-0">Rp <?= number_format($masuk_bulan, 0, ',', '.'); ?></h4>
                            <p>Pemasukan lain</p>
                        </div>
                        <div class="icon"><i class="fas fa-plus-circle"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h4 class="mb-0">Rp <?= number_format($keluar_bulan, 0, ',', '.'); ?></h4>
                            <p>Pengeluaran</p>
                        </div>
                        <div class="icon"><i class="fas fa-minus-circle"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h4 class="mb-0">Rp <?= number_format($saldo, 0, ',', '.'); ?></h4>
                            <p>Saldo kas (total)</p>
                        </div>
                        <div class="icon"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title mb-0">Catat Transaksi</h3></div>
                        <?= form_open('kas/simpan'); ?>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control"
                                           value="<?= date('Y-m-d'); ?>" max="<?= date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Jenis</label>
                                    <select name="jenis" class="form-control">
                                        <option value="keluar">Pengeluaran</option>
                                        <option value="masuk">Pemasukan lain</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <input type="text" name="kategori" class="form-control" maxlength="60"
                                           list="kategori-kas" placeholder="mis. ATK, Listrik, Honor" required>
                                    <datalist id="kategori-kas">
                                        <option value="ATK / Alat Tulis"></option>
                                        <option value="Listrik &amp; Air"></option>
                                        <option value="Internet"></option>
                                        <option value="Honor / Gaji"></option>
                                        <option value="Kebersihan"></option>
                                        <option value="Perbaikan Sarana"></option>
                                        <option value="Kegiatan Sekolah"></option>
                                        <option value="Konsumsi"></option>
                                        <option value="Sumbangan / Donasi"></option>
                                        <option value="Lain-lain"></option>
                                    </datalist>
                                </div>
                                <div class="form-group">
                                    <label>Nominal (Rp)</label>
                                    <input type="number" name="nominal" class="form-control" min="1" step="1"
                                           placeholder="mis. 150000" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Keterangan <small class="text-muted">(opsional)</small></label>
                                    <textarea name="keterangan" rows="2" maxlength="255" class="form-control"
                                              placeholder="Catatan tambahan"></textarea>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </div>
                        <?= form_close(); ?>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-body py-2">
                            <form method="get" action="<?= site_url('kas'); ?>" class="form-row align-items-end" data-auto-filter>
                                <div class="form-group col-6 col-md-4 mb-1">
                                    <label>Bulan</label>
                                    <select name="bulan" class="form-control">
                                        <?php foreach ($daftar_bulan as $i => $nm): ?>
                                            <option value="<?= $i; ?>" <?= ((int) $bulan === $i) ? 'selected' : ''; ?>><?= $nm; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-6 col-md-3 mb-1">
                                    <label>Tahun</label>
                                    <input type="number" name="tahun" class="form-control" min="2000" max="2100"
                                           value="<?= (int) $tahun; ?>">
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Transaksi <?= html_escape($nama_bulan); ?> <?= (int) $tahun; ?></h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kategori</th>
                                            <th>Keterangan</th>
                                            <th class="text-right">Nominal</th>
                                            <th class="text-center">Oleh</th>
                                            <th class="text-center" style="width:56px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($daftar)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi pada bulan ini.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($daftar as $r): ?>
                                            <tr>
                                                <td class="text-nowrap"><?= date('d/m/Y', strtotime($r->tanggal)); ?></td>
                                                <td>
                                                    <?php if ($r->jenis === 'masuk'): ?>
                                                        <span class="badge badge-success mr-1">Masuk</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger mr-1">Keluar</span>
                                                    <?php endif; ?>
                                                    <?= html_escape($r->kategori); ?>
                                                </td>
                                                <td><?= html_escape($r->keterangan ? $r->keterangan : '-'); ?></td>
                                                <td class="text-right text-nowrap">
                                                    <?= $r->jenis === 'masuk' ? '+' : '&minus;'; ?>
                                                    Rp <?= number_format((float) $r->nominal, 0, ',', '.'); ?>
                                                </td>
                                                <td class="text-center small"><?= html_escape($r->dibuat_oleh ? $r->dibuat_oleh : '-'); ?></td>
                                                <td class="text-center">
                                                    <?= form_open('kas/hapus', array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus transaksi ini?')")); ?>
                                                        <input type="hidden" name="id" value="<?= (int) $r->id; ?>">
                                                        <button type="submit" class="btn btn-xs btn-danger" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?= form_close(); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <th colspan="3" class="text-right">Total bulan ini (masuk &minus; keluar)</th>
                                            <th class="text-right">
                                                Rp <?= number_format($pemasukan_bulan - $keluar_bulan, 0, ',', '.'); ?>
                                            </th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer small text-muted">
                            Pemasukan SPP bulan ini (Rp <?= number_format($spp_bulan, 0, ',', '.'); ?>)
                            ikut dihitung pada total di atas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
