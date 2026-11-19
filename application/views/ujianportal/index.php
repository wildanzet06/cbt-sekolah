<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($judul) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/bootstrap/css/bootstrap.min.css') ?>">
    <style>
        body { background: #f1f5f9; display: flex; align-items: center; min-height: 100vh; }
        .portal { max-width: 440px; width: 100%; margin: auto; }
        .portal .card { border: 0; border-radius: 14px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); }
        .portal h1 { font-size: 1.35rem; font-weight: 700; color: #0b2545; }
        .kode { letter-spacing: .5rem; font-size: 1.4rem; text-transform: uppercase; text-align: center; }
        .btn-masuk { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-masuk:hover { background: #123862; color: #fff; }
    </style>
</head>
<body>
<div class="portal p-3">
    <div class="card">
        <div class="card-body p-4">
            <h1 class="mb-1">Portal Ujian</h1>
            <p class="text-muted small mb-4">Masukkan kode ujian dari pengawas, lalu verifikasi NIS dan nama.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small"><?= html_escape($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('ujianportal/cek') ?>">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <div class="form-group">
                    <label class="small font-weight-bold">Kode Ujian</label>
                    <input type="text" name="kode" class="form-control kode" maxlength="6"
                           placeholder="ABC234" autocomplete="off" required
                           value="<?= html_escape($kode) ?>">
                </div>
                <button type="submit" class="btn btn-masuk btn-block">Masuk Ujian</button>
            </form>

            <div class="text-center small text-muted mt-3">
                <a href="<?= base_url('auth') ?>">Kembali ke halaman login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
