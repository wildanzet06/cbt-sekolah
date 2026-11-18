<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-file-alt text-warning mr-1"></i> Surat Peringatan Siswa</h1>
                    <small class="text-muted">
                        Siswa otomatis masuk SP1/SP2/SP3 bila poin pelanggaran mencapai ambang batas
                    </small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <a href="<?= site_url('pelanggaran'); ?>" class="btn btn-default">
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
                <div class="col-12 col-lg-7">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Filter</h3></div>
                        <div class="card-body py-2">
                            <form method="get" action="<?= site_url('pelanggaran/surat'); ?>" class="form-row align-items-end" data-auto-filter>
                                <div class="form-group col-12 col-md-6 mb-1">
                                    <label>Kelas</label>
                                    <select name="kelas" class="form-control">
                                        <option value="0">Semua kelas</option>
                                        <?php foreach ($daftar_kelas as $k): ?>
                                            <option value="<?= (int) $k->id_kelas; ?>" <?= ((int) $kelas_id === (int) $k->id_kelas) ? 'selected' : ''; ?>>
                                                <?= html_escape($k->nama_kelas); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Ambang Poin</h3></div>
                        <?php if ($is_admin): ?>
                            <?= form_open('pelanggaran/simpan_setting_sp'); ?>
                                <div class="card-body py-2">
                                    <div class="form-row">
                                        <div class="form-group col-4">
                                            <label class="small mb-0">SP 1</label>
                                            <input type="number" name="sp1" min="1" max="1000" class="form-control form-control-sm"
                                                   value="<?= (int) $setting_sp->sp1; ?>" required>
                                        </div>
                                        <div class="form-group col-4">
                                            <label class="small mb-0">SP 2</label>
                                            <input type="number" name="sp2" min="1" max="1000" class="form-control form-control-sm"
                                                   value="<?= (int) $setting_sp->sp2; ?>" required>
                                        </div>
                                        <div class="form-group col-4">
                                            <label class="small mb-0">SP 3</label>
                                            <input type="number" name="sp3" min="1" max="1000" class="form-control form-control-sm"
                                                   value="<?= (int) $setting_sp->sp3; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer py-2">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="fas fa-save"></i> Simpan Ambang
                                    </button>
                                </div>
                            <?= form_close(); ?>
                        <?php else: ?>
                            <div class="card-body py-2">
                                <p class="mb-0">
                                    SP 1: <b><?= (int) $setting_sp->sp1; ?> poin</b> &middot;
                                    SP 2: <b><?= (int) $setting_sp->sp2; ?> poin</b> &middot;
                                    SP 3: <b><?= (int) $setting_sp->sp3; ?> poin</b>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Daftar Siswa</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th class="text-center">Pelanggaran</th>
                                    <th class="text-center">Total Poin</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada siswa yang memiliki poin pelanggaran.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= html_escape($r->nis); ?></td>
                                        <td><?= html_escape($r->nama); ?></td>
                                        <td><?= html_escape($r->nama_kelas ? $r->nama_kelas : '-'); ?></td>
                                        <td class="text-center"><?= (int) $r->jml; ?></td>
                                        <td class="text-center"><b><?= (int) $r->total_poin; ?></b></td>
                                        <td class="text-center">
                                            <?php if ((int) $r->level === 3): ?>
                                                <span class="badge badge-danger">SP 3</span>
                                            <?php elseif ((int) $r->level === 2): ?>
                                                <span class="badge badge-warning">SP 2</span>
                                            <?php elseif ((int) $r->level === 1): ?>
                                                <span class="badge badge-info">SP 1</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Belum SP</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <?php if ((int) $r->level > 0): ?>
                                                <a href="<?= site_url('pelanggaran/cetak_sp/' . (int) $r->id_siswa); ?>"
                                                   target="_blank" rel="noopener" class="btn btn-xs btn-primary">
                                                    <i class="fas fa-print"></i> Cetak SP
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= site_url('pelanggaran/riwayat/' . (int) $r->id_siswa); ?>"
                                                   class="btn btn-xs btn-outline-secondary">
                                                    <i class="fas fa-history"></i> Riwayat
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    Poin dihitung dari pelanggaran pada tahun pelajaran dan semester aktif.
                </div>
            </div>
        </div>
    </section>
</div>
