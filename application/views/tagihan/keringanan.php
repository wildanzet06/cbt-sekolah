<?php
$e  = $edit; // baris yang sedang diedit, atau null
$rp = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1><i class="fas fa-hand-holding-heart text-primary"></i> Keringanan Siswa</h1>
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
                            <h3 class="card-title"><?= $e ? 'Edit Keringanan' : 'Tambah Keringanan'; ?></h3>
                        </div>
                        <?= form_open('tagihan/keringanan_simpan'); ?>
                        <div class="card-body">
                            <input type="hidden" name="id" value="<?= $e ? (int) $e->id : 0; ?>">

                            <div class="form-group">
                                <label>Siswa</label>
                                <select name="siswa_id" id="siswa_id" class="form-control" required>
                                    <option value="">-- Pilih siswa --</option>
                                    <?php foreach ($siswa as $s): ?>
                                        <option value="<?= (int) $s->id_siswa; ?>"
                                            <?= ($e && $e->siswa_id == $s->id_siswa) ? 'selected' : ''; ?>>
                                            <?= html_escape($s->nama . ' (' . $s->nis . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Berlaku untuk</label>
                                <select name="jenis_id" class="form-control">
                                    <option value="0">Semua jenis tagihan</option>
                                    <?php foreach ($jenis as $j): ?>
                                        <option value="<?= (int) $j->id; ?>"
                                            <?= ($e && $e->jenis_id == $j->id) ? 'selected' : ''; ?>>
                                            <?= html_escape($j->nama); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Jenis potongan</label>
                                    <select name="tipe" class="form-control">
                                        <option value="persen"  <?= (!$e || $e->tipe == 'persen') ? 'selected' : ''; ?>>Persen (%)</option>
                                        <option value="nominal" <?= ($e && $e->tipe == 'nominal') ? 'selected' : ''; ?>>Nominal (Rp)</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Nilai</label>
                                    <input type="number" name="nilai" min="1" class="form-control" required
                                           value="<?= $e ? (int) $e->nilai : ''; ?>">
                                </div>
                            </div>
                            <small class="text-muted d-block mb-3">Persen 100 = <b>gratis</b>.</small>

                            <div class="form-group">
                                <label>Alasan / keterangan</label>
                                <input type="text" name="keterangan" maxlength="255" class="form-control"
                                       placeholder="mis. Tidak mampu (SKTM), yatim"
                                       value="<?= $e ? html_escape($e->keterangan) : ''; ?>">
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="aktif" value="1" class="form-check-input" id="aktif"
                                       <?= (!$e || $e->aktif) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="aktif">Aktif</label>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                            <?php if ($e): ?>
                                <a href="<?= site_url('tagihan/keringanan'); ?>" class="btn btn-default">Batal</a>
                            <?php endif; ?>
                        </div>
                        <?= form_close(); ?>
                    </div>

                    <div class="callout callout-info">
                        <small>
                            Keringanan diterapkan otomatis saat <b>Buat Tagihan</b>. Jika siswa punya
                            beberapa keringanan, yang paling besar yang dipakai.
                            Tagihan yang <b>sudah dibuat</b> tidak berubah; ubah lewat tombol
                            <i>Diskon</i> di Daftar Tagihan.
                        </small>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Berlaku untuk</th>
                                        <th>Potongan</th>
                                        <th>Alasan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftar as $k): ?>
                                        <tr>
                                            <td><?= html_escape($k->nama); ?><br><small class="text-muted"><?= html_escape($k->nis); ?></small></td>
                                            <td><?= $k->jenis_id ? html_escape($k->jenis_nama) : '<i>Semua jenis</i>'; ?></td>
                                            <td>
                                                <?php if ($k->tipe == 'persen'): ?>
                                                    <?= (int) $k->nilai; ?>%
                                                    <?= ((int) $k->nilai >= 100) ? '<span class="badge badge-primary">Gratis</span>' : ''; ?>
                                                <?php else: ?>
                                                    <?= $rp($k->nilai); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= html_escape($k->keterangan); ?></td>
                                            <td><?= $k->aktif ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-light">Nonaktif</span>'; ?></td>
                                            <td>
                                                <a href="<?= site_url('tagihan/keringanan?edit=' . (int) $k->id); ?>" class="btn btn-warning btn-xs">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?= form_open('tagihan/keringanan_hapus/' . (int) $k->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus keringanan ini?')")); ?>
                                                    <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                                                <?= form_close(); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($daftar)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada keringanan.</td></tr>
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

<script>
    // Kotak pencarian pada pilihan siswa (jika plugin select2 tersedia)
    $(function () {
        if ($.fn.select2) {
            $('#siswa_id').select2({theme: 'bootstrap4'});
        }
    });
</script>
