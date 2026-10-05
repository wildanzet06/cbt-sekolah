<?php
$e = $edit; // baris yang sedang diedit, atau null jika menambah baru
$rp = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1><i class="fas fa-tags text-primary"></i> Jenis Tagihan</h1>
                </div>
                <div class="col-sm-4 text-right">
                    <a href="<?= site_url('tagihan'); ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Daftar Tagihan
                    </a>
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

            <div class="row">
                <!-- Form -->
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><?= $e ? 'Edit Jenis Tagihan' : 'Tambah Jenis Tagihan'; ?></h3>
                        </div>
                        <?= form_open('tagihan/jenis_simpan'); ?>
                        <div class="card-body">
                            <input type="hidden" name="id" value="<?= $e ? (int) $e->id : 0; ?>">

                            <div class="form-group">
                                <label>Kode</label>
                                <input type="text" name="kode" maxlength="20" class="form-control"
                                       placeholder="mis. SERAGAM" required
                                       value="<?= $e ? html_escape($e->kode) : ''; ?>">
                                <small class="text-muted">Huruf/angka tanpa spasi, otomatis huruf besar.</small>
                            </div>

                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="nama" maxlength="100" class="form-control"
                                       placeholder="mis. Seragam Sekolah" required
                                       value="<?= $e ? html_escape($e->nama) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Tipe</label>
                                <select name="tipe" class="form-control">
                                    <option value="bulanan" <?= ($e && $e->tipe == 'bulanan') ? 'selected' : ''; ?>>Bulanan (tiap bulan, mis. SPP)</option>
                                    <option value="sekali"  <?= (!$e || $e->tipe == 'sekali') ? 'selected' : ''; ?>>Sekali bayar (mis. uang gedung, seragam)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Nominal bawaan (Rp)</label>
                                <input type="number" name="nominal_default" min="0" class="form-control"
                                       value="<?= $e ? (int) $e->nominal_default : 0; ?>">
                                <small class="text-muted">Bisa diubah lagi saat membuat tagihan.</small>
                            </div>

                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" name="keterangan" maxlength="255" class="form-control"
                                       value="<?= $e ? html_escape($e->keterangan) : ''; ?>">
                            </div>

                            <div class="form-check mb-2">
                                <input type="checkbox" name="boleh_cicil" value="1" class="form-check-input" id="boleh_cicil"
                                       <?= ($e && !empty($e->boleh_cicil)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="boleh_cicil">
                                    Boleh dicicil <small class="text-muted">(hanya untuk tipe "sekali bayar")</small>
                                </label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="aktif" value="1" class="form-check-input" id="aktif"
                                       <?= (!$e || $e->aktif) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="aktif">Aktif (muncul saat membuat tagihan)</label>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                            <?php if ($e): ?>
                                <a href="<?= site_url('tagihan/jenis'); ?>" class="btn btn-default">Batal</a>
                            <?php endif; ?>
                        </div>
                        <?= form_close(); ?>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Tipe</th>
                                        <th>Nominal Bawaan</th>
                                        <th>Cicilan</th>
                                        <th>Status</th>
                                        <th>Dipakai</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftar_jenis as $j): ?>
                                        <tr>
                                            <td><b><?= html_escape($j->kode); ?></b></td>
                                            <td><?= html_escape($j->nama); ?></td>
                                            <td>
                                                <?php if ($j->tipe == 'bulanan'): ?>
                                                    <span class="badge badge-primary">Bulanan</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Sekali bayar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $rp($j->nominal_default); ?></td>
                                            <td><?= !empty($j->boleh_cicil) ? '<span class="badge badge-info">Boleh</span>' : '-'; ?></td>
                                            <td>
                                                <?= $j->aktif ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-light">Nonaktif</span>'; ?>
                                            </td>
                                            <td><?= (int) $j->jml_tagihan; ?> tagihan</td>
                                            <td>
                                                <a href="<?= site_url('tagihan/jenis?edit=' . (int) $j->id); ?>" class="btn btn-warning btn-xs">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <?= form_open('tagihan/jenis_hapus/' . (int) $j->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus jenis tagihan ini? Jika sudah pernah dipakai, hanya dinonaktifkan.')")); ?>
                                                    <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                                                <?= form_close(); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($daftar_jenis)): ?>
                                        <tr><td colspan="8" class="text-center text-muted">Belum ada jenis tagihan.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
