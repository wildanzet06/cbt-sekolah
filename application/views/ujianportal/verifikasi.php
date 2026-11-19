<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($judul) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/bootstrap/css/bootstrap.min.css') ?>">
    <style>
        body { background: #f1f5f9; min-height: 100vh; }
        .portal { max-width: 520px; margin: 40px auto; }
        .portal .card { border: 0; border-radius: 14px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); }
        .portal h1 { font-size: 1.25rem; font-weight: 700; color: #0b2545; }
        .btn-masuk { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-masuk:hover { background: #123862; color: #fff; }
        .info-ujian { background: #eef2f8; border-radius: 10px; padding: .75rem 1rem; font-size: .9rem; }
    </style>
</head>
<body>
<div class="portal p-3">
    <div class="card">
        <div class="card-body p-4">
            <h1 class="mb-3">Verifikasi Peserta</h1>

            <div class="info-ujian mb-3">
                <div><b><?= html_escape(!empty($ujian->judul) ? $ujian->judul : ($ujian->kode_jenis . ' ' . $ujian->nama_mapel)) ?></b></div>
                <div class="text-muted small">
                    <?= html_escape($ujian->nama_mapel ?: '-') ?> &middot;
                    <?= date('d M Y', strtotime((string) $ujian->tgl_mulai)) ?> &middot;
                    <?= (int) $ujian->durasi_ujian ?> menit &middot;
                    <?= (int) $jumlah ?> peserta terdaftar
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small"><?= html_escape($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('ujianportal/verifikasi') ?>">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="kode" value="<?= html_escape($kode) ?>">
                <div class="form-group">
                    <label class="small font-weight-bold">NIS</label>
                    <input type="text" name="nis" class="form-control" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" autocomplete="off" required>
                </div>
                <button type="submit" class="btn btn-masuk btn-block">Verifikasi</button>
            </form>

            <div class="text-center small mt-3">
                <a href="<?= base_url('ujianportal') ?>">Ganti kode ujian</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
