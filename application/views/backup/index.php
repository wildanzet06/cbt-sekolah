<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-database text-primary mr-1"></i> Backup Database</h1>
                    <small class="text-muted">Salinan otomatis setiap hari, bisa dipulihkan kapan saja</small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <?= form_open('backup/buat', array('class' => 'd-inline')); ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download"></i> Backup Sekarang
                            </button>
                        <?= form_close(); ?>
                        <a href="<?= site_url('dashboard'); ?>" class="btn btn-default">
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
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h4 class="mb-0"><?= $terakhir_auto ? date('d/m/Y H:i', $terakhir_auto->waktu) : 'Belum ada'; ?></h4>
                            <p>Backup otomatis terakhir</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h4 class="mb-0"><?= count($daftar); ?> file</h4>
                            <p>Total backup tersimpan</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-archive"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h4 class="mb-0"><?= (int) $simpan_hari; ?> hari</h4>
                            <p>Backup otomatis disimpan</p>
                        </div>
                        <div class="icon"><i class="fas fa-history"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h4 class="mb-0"><?= $bisa_pulih ? 'Aktif' : 'Tidak aktif'; ?></h4>
                            <p>Fitur pulihkan (restore)</p>
                        </div>
                        <div class="icon"><i class="fas fa-undo-alt"></i></div>
                    </div>
                </div>
            </div>

            <div class="callout callout-info">
                <h6 class="mb-1"><i class="fas fa-info-circle"></i> Cara kerja</h6>
                <ul class="mb-0 pl-3">
                    <li>Backup otomatis dibuat <b>sekali sehari</b> saat admin membuka halaman (tanpa perlu klik).</li>
                    <li>Backup otomatis disimpan <b><?= (int) $simpan_hari; ?> hari</b> terakhir; file lama dihapus otomatis.</li>
                    <li>Sebelum memulihkan, sistem membuat <b>backup pengaman</b> (<code>pre-restore-…</code>) lebih dulu.</li>
                    <li>File backup tersimpan di folder <code>backups/</code> dan tidak bisa diakses lewat browser.</li>
                </ul>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Daftar Backup</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nama file</th>
                                    <th>Waktu</th>
                                    <th>Ukuran</th>
                                    <th>Jenis</th>
                                    <th class="text-center" style="width:210px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($daftar)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada file backup.</td></tr>
                            <?php else: ?>
                                <?php foreach ($daftar as $f): ?>
                                    <tr>
                                        <td class="text-monospace small"><?= html_escape($f->nama); ?></td>
                                        <td><?= date('d/m/Y H:i', $f->waktu); ?></td>
                                        <td><?= $this->backup->ukuran($f->ukuran); ?></td>
                                        <td>
                                            <?php if ($f->auto): ?>
                                                <span class="badge badge-info">Otomatis</span>
                                            <?php elseif (strpos($f->nama, 'pre-restore-') === 0): ?>
                                                <span class="badge badge-warning">Pengaman</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Manual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <?php if ($f->aman && $bisa_pulih): ?>
                                                <?= form_open('backup/pulihkan', array(
                                                    'class' => 'd-inline',
                                                    'onsubmit' => "return confirm('Pulihkan database dari file ini? Data saat ini akan DITIMPA (backup pengaman tetap dibuat otomatis).')"
                                                )); ?>
                                                    <input type="hidden" name="nama" value="<?= html_escape($f->nama); ?>">
                                                    <button type="submit" class="btn btn-xs btn-warning">
                                                        <i class="fas fa-undo-alt"></i> Pulihkan
                                                    </button>
                                                <?= form_close(); ?>
                                            <?php endif; ?>
                                            <?= form_open('backup/hapus', array(
                                                'class' => 'd-inline',
                                                'onsubmit' => "return confirm('Hapus file backup ini?')"
                                            )); ?>
                                                <input type="hidden" name="nama" value="<?= html_escape($f->nama); ?>">
                                                <button type="submit" class="btn btn-xs btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?= form_close(); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    Folder: <?= html_escape($direktori); ?>
                </div>
            </div>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Fitur <b>Pulihkan</b> menimpa seluruh data dengan isi file backup.
                Gunakan hanya bila data benar-benar bermasalah. File <code>.zip</code> lama dari menu
                Database tidak bisa dipulihkan dari sini (ekstrak dulu menjadi <code>.sql</code>).
            </div>
        </div>
    </section>
</div>
