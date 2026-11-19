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
        .cek { width: 54px; height: 54px; border-radius: 50%; background: #dcfce7; color: #16a34a;
            display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 1rem; }
    </style>
</head>
<body>
<div class="portal p-3">
    <div class="card">
        <div class="card-body p-4 text-center">
            <div class="cek">&#10004;</div>
            <h1 class="mb-2">Verifikasi Berhasil</h1>
            <p class="text-muted small mb-3">Data Anda cocok dengan peserta ujian ini.</p>

            <div class="text-left border rounded p-3 mb-3 small">
                <div><b><?= html_escape($siswa->nama) ?></b></div>
                <div class="text-muted">NIS <?= html_escape($siswa->nis) ?> &middot; <?= html_escape($siswa->nama_kelas ?: '-') ?></div>
                <hr class="my-2">
                <div><b><?= html_escape(!empty($ujian->judul) ? $ujian->judul : ($ujian->kode_jenis . ' ' . $ujian->nama_mapel)) ?></b></div>
                <div class="text-muted">
                    <?= date('d M Y', strtotime((string) $ujian->tgl_mulai)) ?> &middot;
                    <?= (int) $ujian->durasi_ujian ?> menit
                </div>
            </div>

            <?php if ($user): ?>
                <p class="small mb-2">Username login Anda: <b><?= html_escape($user->username) ?></b></p>
                <a href="<?= base_url('auth') ?>" class="btn btn-masuk btn-block">Lanjut ke Halaman Login</a>
            <?php else: ?>
                <div class="alert alert-warning py-2 small">
                    Akun login siswa belum tersedia. Hubungi administrator.
                </div>
                <a href="<?= base_url('ujianportal') ?>" class="btn btn-outline-secondary btn-block">Kembali</a>
            <?php endif; ?>

            <div class="small mt-3">
                <a href="<?= base_url('ujianportal') ?>">Ganti kode ujian</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
