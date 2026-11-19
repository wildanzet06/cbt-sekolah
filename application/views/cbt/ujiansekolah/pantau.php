<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pantau Ujian — hari-H (PRD 5.4)
 */

$bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
$tgl = substr((string)$u->tgl_mulai, 0, 10);
$t = strtotime($tgl);
$tglLabel = $t ? date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t) : '-';
$judulUjian = trim((!empty($u->kode_jenis) ? $u->kode_jenis : 'Ujian') . ' '
    . (!empty($u->nama_mapel) ? $u->nama_mapel : '(kumpulan soal belum dipilih)'));

$statusTeks = ['belum' => 'Belum mulai', 'sedang' => 'Sedang mengerjakan', 'selesai' => 'Selesai'];

$this->load->view('cbt/ujiansekolah/_style');
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center ujian-hero">
                <div class="col-auto ujian-hero-kembali">
                    <?php $this->load->view('cbt/ujiansekolah/_kembali'); ?>
                </div>
                <div class="col ujian-hero-judul">
                    <h1 class="mb-0"><?= html_escape($judulUjian) ?></h1>
                    <small class="text-muted">
                        <?= html_escape($tglLabel) ?> &middot; <?= (int)$u->durasi_ujian ?> menit &middot; Sesi 1
                        <?php if (!empty($u->label_kelas)): ?>
                            &middot; Kelas <?= html_escape($u->label_kelas) ?>
                        <?php endif; ?>
                        &middot; <?= (int)$u->status === 1 ? 'Dibuka' : 'Ditutup' ?>
                    </small>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (!$kesiapan['siap']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Ujian belum siap: <?= html_escape(implode(', ', array_map(function ($k) {
                        $map = ['soal' => 'soal belum ada', 'peserta' => 'peserta belum dibagi',
                            'ruang' => 'ruang belum dipakai semua', 'pengawas' => 'pengawas belum diatur',
                            'token' => 'token belum diisi'];
                        return $map[$k] ?? $k;
                    }, $kesiapan['kurang']))) ?>.
                    <a href="<?= base_url('ujiansekolah/edit/' . (int)$u->id_jadwal) ?>">Lengkapi persiapan</a>.
                </div>
            <?php endif; ?>

            <div class="card card-default my-shadow mb-4">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-6 col-md-2 mb-2 text-center">
                            <div class="text-muted small">Total peserta</div>
                            <div class="h4 mb-0"><b><?= (int)$ringkas['total'] ?></b></div>
                        </div>
                        <div class="col-6 col-md-2 mb-2 text-center">
                            <div class="text-muted small">Belum mulai</div>
                            <div class="h4 mb-0 text-secondary"><b><?= (int)$ringkas['belum'] ?></b></div>
                        </div>
                        <div class="col-6 col-md-2 mb-2 text-center">
                            <div class="text-muted small">Mengerjakan</div>
                            <div class="h4 mb-0 text-warning"><b><?= (int)$ringkas['sedang'] ?></b></div>
                        </div>
                        <div class="col-6 col-md-2 mb-2 text-center">
                            <div class="text-muted small">Selesai</div>
                            <div class="h4 mb-0 text-success"><b><?= (int)$ringkas['selesai'] ?></b></div>
                        </div>
                        <div class="col-12 col-md-4 text-md-right">
                            <?php if ($kelola): ?>
                                <?php if ((int)$u->status === 1): ?>
                                    <button type="button" class="btn btn-sm btn-danger btn-status"
                                            data-aksi="tutup">
                                        <i class="fas fa-stop"></i> Tutup Ujian
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success btn-status"
                                            data-aksi="buka" <?= $kesiapan['siap'] ? '' : 'disabled' ?>>
                                        <i class="fas fa-play"></i> Buka Ujian
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="<?= base_url('ujiansekolah/pantau/' . (int)$u->id_jadwal) ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-sync"></i> Segarkan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fas fa-users mr-1"></i> Peserta</h6>
                    <div class="card-tools" style="max-width:240px">
                        <input type="search" class="form-control form-control-sm" id="cari-peserta"
                               placeholder="Cari nama / kelas...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($siswa) === 0): ?>
                        <div class="p-4 text-center text-muted">
                            Belum ada peserta. Ujian belum punya kelas, atau
                            <a href="<?= base_url('ujiansekolah/pembagian') ?>">pembagian peserta</a> belum dibuat.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height:60vh;overflow:auto">
                            <table class="table table-sm table-bordered mb-0" id="tabel-peserta">
                                <thead class="bg-light" style="position:sticky;top:0;z-index:2">
                                <tr>
                                    <th style="width:44px">#</th>
                                    <th>Nama</th>
                                    <th style="width:70px">Kelas</th>
                                    <th style="width:80px">Ruang</th>
                                    <th style="width:150px">Status</th>
                                    <th style="width:130px">Mulai</th>
                                    <th style="width:80px">Nilai</th>
                                    <?php if ($kelola): ?>
                                        <th style="width:190px">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $n = 0; foreach ($siswa as $s): $n++;
                                    $st = $s->durasi_status === null ? 0 : (int)$s->durasi_status;
                                    $kelasStatus = $st === 2 ? 'selesai' : ($st === 1 ? 'sedang' : 'belum');
                                    $labelStatus = $st === 2 ? $statusTeks['selesai']
                                        : ($st === 1 ? $statusTeks['sedang'] : $statusTeks['belum']);

                                    $nilai = null;
                                    if ($st === 2) {
                                        $nilai = (float)$s->pg_nilai + (float)$s->essai_nilai
                                            + (float)$s->kompleks_nilai + (float)$s->jodohkan_nilai
                                            + (float)$s->isian_nilai;
                                    }
                                    $cari = strtolower((string)$s->nama . ' ' . (string)$s->nama_kelas
                                        . ' ' . (string)$s->kode_ruang);
                                    ?>
                                    <tr data-cari="<?= html_escape($cari) ?>">
                                        <td><?= $n ?></td>
                                        <td>
                                            <?= html_escape($s->nama) ?>
                                            <small class="text-muted d-block"><?= html_escape($s->nis) ?></small>
                                        </td>
                                        <td><?= html_escape($s->nama_kelas) ?></td>
                                        <td>
                                            <?= $s->kode_ruang ? html_escape($s->kode_ruang) : '—' ?>
                                        </td>
                                        <td>
                                            <span class="status-dot <?= $kelasStatus ?>"></span>
                                            <?= html_escape($labelStatus) ?>
                                            <?php if ($st === 1 && !empty($s->mulai)): ?>
                                                <small class="text-muted d-block">timer: <?= html_escape($s->lama_ujian) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= !empty($s->mulai) ? html_escape(date('d/m H:i', strtotime($s->mulai))) : '—' ?></small>
                                        </td>
                                        <td>
                                            <?= $nilai === null ? '<span class="text-muted">—</span>'
                                                : html_escape(number_format($nilai, 1)) ?>
                                        </td>
                                        <?php if ($kelola): ?>
                                            <td class="text-nowrap">
                                                <?php if ($st !== 1): ?>
                                                    <button type="button" class="btn btn-xs btn-outline-primary btn-reset"
                                                            data-id="<?= (int)$s->id_siswa ?>"
                                                            data-nama="<?= html_escape($s->nama) ?>">
                                                        <i class="fas fa-key"></i> Reset izin
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($st === 1): ?>
                                                    <button type="button" class="btn btn-xs btn-outline-warning btn-waktu"
                                                            data-id="<?= (int)$s->id_siswa ?>"
                                                            data-nama="<?= html_escape($s->nama) ?>">
                                                        <i class="fas fa-clock"></i> + waktu
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-muted small mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                <b>Reset izin</b> mengizinkan siswa login kembali di perangkat lain.
                <b>+ waktu</b> menambah sisa waktu siswa yang sedang mengerjakan.
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_AKSI = '<?= base_url('ujiansekolah/pantauaksi') ?>';
        var ID = <?= (int)$u->id_jadwal ?>;

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        function post(data, cb) {
            data.id_jadwal = ID;
            data[CSRF.name] = CSRF.hash;
            $.ajax({
                type: 'POST', url: URL_AKSI, data: data, dataType: 'json',
                success: cb,
                error: function (xhr) {
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    cb({status: false, msg: msg});
                }
            });
        }

        $('.btn-status').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var aksi = $b.data('aksi');
            if (aksi === 'tutup' && !window.confirm('Tutup ujian ini? Siswa tidak bisa lagi masuk ke ujian.')) {
                $b.prop('disabled', false);
                return;
            }
            post({aksi: aksi}, function (r) {
                toast(r.status, r.msg);
                if (r.status) { window.setTimeout(function () { window.location.reload(); }, 700); }
                else { $b.prop('disabled', false); }
            });
        });

        $('.btn-reset').on('click', function () {
            var $b = $(this);
            post({aksi: 'reset', id_siswa: $b.data('id')}, function (r) {
                toast(r.status, r.msg);
            });
        });

        $('.btn-waktu').on('click', function () {
            var $b = $(this);
            var menit = window.prompt('Tambah waktu untuk ' + $b.data('nama') + ' (menit):', '5');
            if (menit === null) { return; }
            menit = parseInt(menit, 10);
            if (!menit || menit < 1 || menit > 120) {
                toast(false, 'Jumlah menit harus antara 1 dan 120.');
                return;
            }
            post({aksi: 'waktu', id_siswa: $b.data('id'), menit: menit}, function (r) {
                toast(r.status, r.msg);
            });
        });

        $('#cari-peserta').on('input', function () {
            var q = ($(this).val() || '').toLowerCase();
            $('#tabel-peserta tbody tr').each(function () {
                var cocok = q === '' || ($(this).data('cari') || '').indexOf(q) !== -1;
                $(this).toggleClass('d-none', !cocok);
            });
        });
    })();
</script>
