<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ruang & Sesi — gabungan ringkas cbt_ruang + cbt_sesi (PRD 4.1)
 */
$this->load->view('cbt/ujiansekolah/_style');
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1 class="mb-0"><?= html_escape($judul) ?></h1>
                    <small class="text-muted">
                        Kapasitas total <?= (int)$kapasitas['kapasitas'] ?> kursi dari
                        <?= (int)$kapasitas['ruang'] ?> ruang
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
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                Kapasitas ruang adalah <b>batas aman</b>, dipakai saat membagi peserta otomatis.
                Pola sekolah: semua ujian serentak pada Sesi 1.
            </div>

            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fas fa-door-open mr-1"></i> Ruang Ujian</h6>
                    <div class="card-tools">
                        <a href="<?= base_url('cbtruang') ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                            Halaman Ruang (lama)
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Nama Ruang</th>
                                <th style="width:120px">Kode</th>
                                <th style="width:220px">Kapasitas (siswa)</th>
                                <th style="width:100px">Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($ruang) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada ruang. Tambahkan pada halaman Ruang (lama).
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($ruang as $i => $r): ?>
                                <tr>
                                    <td class="align-middle"><?= $i + 1 ?></td>
                                    <td class="align-middle"><?= html_escape($r->nama_ruang) ?></td>
                                    <td class="align-middle"><?= html_escape($r->kode_ruang) ?></td>
                                    <td class="align-middle">
                                        <div class="input-group input-group-sm" style="max-width:160px">
                                            <input type="number" class="form-control kapasitas-input"
                                                   min="1" max="500" value="<?= (int)$r->kapasitas ?>"
                                                   data-id="<?= (int)$r->id_ruang ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text">siswa</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <button type="button" class="btn btn-sm btn-primary btn-simpan-kapasitas"
                                                data-id="<?= (int)$r->id_ruang ?>">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="far fa-clock mr-1"></i> Sesi Ujian</h6>
                    <div class="card-tools">
                        <a href="<?= base_url('cbtsesi') ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                            Halaman Sesi (lama)
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th>Nama Sesi</th>
                                <th>Kode</th>
                                <th>Mulai</th>
                                <th>Akhir</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($sesi as $s): ?>
                                <tr>
                                    <td><?= html_escape($s->nama_sesi) ?></td>
                                    <td><?= html_escape($s->kode_sesi) ?></td>
                                    <td><?= html_escape(substr((string)$s->waktu_mulai, 0, 5)) ?></td>
                                    <td><?= html_escape(substr((string)$s->waktu_akhir, 0, 5)) ?></td>
                                    <td>
                                        <?php if ((int)$s->aktif === 1): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-light border">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2 text-muted small">
                        Ujian sekolah memakai <b>sesi dengan jam mulai paling awal</b> (Sesi 1).
                        Sesi lain tidak dipakai wizard, tetapi tetap bisa dipakai lewat halaman lama.
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_SIMPAN = '<?= base_url('ujiansekolah/simpanruang') ?>';

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        $('.btn-simpan-kapasitas').on('click', function () {
            var $b = $(this);
            var id = $b.data('id');
            var $input = $('.kapasitas-input[data-id="' + id + '"]');
            var data = {id_ruang: id, kapasitas: $input.val()};
            data[CSRF.name] = CSRF.hash;
            $b.prop('disabled', true);
            $.ajax({
                type: 'POST', url: URL_SIMPAN, data: data, dataType: 'json',
                success: function (r) {
                    $b.prop('disabled', false);
                    toast(r.status, r.msg);
                    if (r.status) { window.setTimeout(function () { window.location.reload(); }, 700); }
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
