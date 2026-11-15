<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembagian Peserta — menggantikan Atur Ruang/Sesi + Nomor Peserta (PRD 5.3)
 */

$kelas = $d['kelas'];
$ruang = $d['ruang'];
$sesi = $d['sesi'];
$totalSiswa = (int)$d['total_siswa'];
$terdaftar = (int)$d['terdaftar'];
$kapasitas = $d['kapasitas'];
$kurang = $totalSiswa > (int)$kapasitas['kapasitas'];

$this->load->view('cbt/ujiansekolah/_style');
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1 class="mb-0"><?= html_escape($judul) ?></h1>
                    <small class="text-muted">
                        Semester <?= html_escape($smt_active->nama_smt) ?> <?= html_escape($tp_active->tahun) ?> &middot;
                        Pola: serentak &middot; <?= $sesi ? html_escape($sesi->nama_sesi) : 'Sesi belum diatur' ?>
                        &middot; semua ruang dipakai
                    </small>
                </div>
                <div class="col-sm-4 text-right">
                    <a href="<?= base_url('ujiansekolah') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Daftar Ujian
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-default my-shadow mb-4">
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col-6 col-md-3 mb-2">
                            <div class="text-muted small">Siswa</div>
                            <div class="h5 mb-0"><b><?= $totalSiswa ?></b></div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="text-muted small">Sudah dibagi</div>
                            <div class="h5 mb-0 text-<?= $terdaftar >= $totalSiswa && $totalSiswa > 0 ? 'success' : 'warning' ?>">
                                <b><?= $terdaftar ?></b> / <?= $totalSiswa ?>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="text-muted small">Ruang</div>
                            <div class="h5 mb-0"><b><?= (int)$kapasitas['ruang'] ?></b>
                                <small class="text-muted">ruang</small></div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="text-muted small">Kapasitas</div>
                            <div class="h5 mb-0 text-<?= $kurang ? 'danger' : 'success' ?>">
                                <b><?= (int)$kapasitas['kapasitas'] ?></b> kursi
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($kurang): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Kapasitas ruang kurang (<?= (int)$kapasitas['kapasitas'] ?> kursi untuk <?= $totalSiswa ?> siswa).
                    Tambah ruang atau naikkan kapasitas di <a href="<?= base_url('ujiansekolah/ruangsesi') ?>">Ruang &amp; Sesi</a>.
                </div>
            <?php endif; ?>

            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <i class="fas fa-info-circle mr-1"></i>
                    Pembagian peserta dipakai <b>semua ujian</b> pada semester ini. Cukup diatur sekali per semester.
                </div>
                <div class="mt-2 mt-sm-0">
                    <div class="custom-control custom-switch d-inline-block mr-3">
                        <input type="checkbox" class="custom-control-input" id="f-semua-ruang" checked>
                        <label class="custom-control-label" for="f-semua-ruang">Gunakan semua ruang</label>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-bagi">
                        <i class="fas fa-random"></i> Bagi Otomatis
                    </button>
                </div>
            </div>

            <?php foreach ($kelas as $id_kelas => $pk): ?>
                <div class="card card-default my-shadow mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            Kelas <?= html_escape($pk['kelas']->nama_kelas) ?>
                            <span class="badge badge-light border ml-1"><?= (int)$pk['total'] ?> siswa</span>
                        </h6>
                        <div class="card-tools">
                            <a href="<?= base_url('cbtsesisiswa?kls=' . (int)$id_kelas) ?>"
                               class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fas fa-people-arrows"></i> Pindah
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-lihat"
                                    data-kelas="<?= (int)$id_kelas ?>">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <?php if ((int)$pk['total'] === 0): ?>
                            <div class="text-muted">Belum ada siswa di kelas ini.</div>
                        <?php else: ?>
                            <?php foreach ($ruang as $r):
                                $rid = (int)$r->id_ruang;
                                $isi = isset($pk['ruang'][$rid]) ? (int)$pk['ruang'][$rid] : 0;
                                $maks = max(1, (int)$r->kapasitas);
                                $persen = min(100, (int)round($isi / $maks * 100));
                                ?>
                                <div class="ruang-baris">
                                    <div class="flex-fill pr-3">
                                        <span class="kode"><?= html_escape($r->kode_ruang) ?></span>
                                        <span><?= html_escape($r->nama_ruang) ?></span>
                                        <div class="kapasitas-bar mt-1" style="max-width:260px">
                                            <span style="width:<?= $persen ?>%"></span>
                                        </div>
                                    </div>
                                    <div class="text-nowrap">
                                        <span class="badge <?= $isi > 0 ? 'badge-success' : 'badge-light border' ?>">
                                            <?= $isi ?> siswa
                                        </span>
                                        <small class="text-muted ml-1">/ <?= $maks ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-body border-top pt-2 pb-2 d-none" id="siswa-<?= (int)$id_kelas ?>">
                        <?php if (count($pk['siswa']) > 0): ?>
                            <div class="table-responsive" style="max-height:320px;overflow:auto">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="bg-light">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Nama</th>
                                        <th>NIS</th>
                                        <th>Ruang</th>
                                        <th>Nomor Peserta</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $n = 0; foreach ($pk['siswa'] as $s): $n++;
                                        $rid = (int)$s->ruang_id;
                                        $namaRuang = '—';
                                        foreach ($ruang as $r) {
                                            if ((int)$r->id_ruang === $rid) {
                                                $namaRuang = $r->kode_ruang;
                                                break;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $n ?></td>
                                            <td><?= html_escape($s->nama) ?></td>
                                            <td><?= html_escape($s->nis) ?></td>
                                            <td><?= html_escape($namaRuang) ?></td>
                                            <td>
                                                <?= !empty($s->nomor_peserta)
                                                    ? html_escape($s->nomor_peserta)
                                                    : '<span class="text-muted">—</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="card card-default my-shadow mb-4">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <b>Nomor peserta</b>
                        <div class="text-muted small">
                            <?= $terdaftar > 0
                                ? 'Sudah dibuat untuk ' . $terdaftar . ' siswa (format 2627.07.001).'
                                : 'Belum ada nomor peserta. Nomor dibuat otomatis saat Bagi Otomatis.' ?>
                        </div>
                    </div>
                    <div>
                        <a href="<?= base_url('cbtnomorpeserta') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-id-card"></i> Kartu Peserta
                        </a>
                        <a href="<?= base_url('cbtcetak/kartupeserta') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-print"></i> Cetak Kartu
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_BAGI = '<?= base_url('ujiansekolah/bagiproses') ?>';

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        $('.btn-lihat').on('click', function () {
            $('#siswa-' + $(this).data('kelas')).toggleClass('d-none');
        });

        $('#btn-bagi').on('click', function () {
            if (!window.confirm('Pembagian peserta akan diganti. Lanjut?')) { return; }
            var data = {semua_ruang: $('#f-semua-ruang').is(':checked') ? 1 : 0};
            data[CSRF.name] = CSRF.hash;
            var $b = $(this).prop('disabled', true);
            $.ajax({
                type: 'POST', url: URL_BAGI, data: data, dataType: 'json',
                success: function (r) {
                    $b.prop('disabled', false);
                    toast(r.status, r.msg);
                    if (r.status) {
                        window.setTimeout(function () { window.location.reload(); }, 900);
                    }
                },
                error: function (xhr) {
                    $b.prop('disabled', false);
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    toast(false, msg);
                }
            });
        });
    })();
</script>
