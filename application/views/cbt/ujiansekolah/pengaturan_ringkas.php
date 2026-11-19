<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="content-wrapper bg-white pt-4">
    <style>
        .set-card { border: 1px solid #e5e9f0; border-radius: 10px; background: #fff; }
        .set-card label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .25rem; }
        .btn-gelap { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-gelap:hover { background: #123862; color: #fff; }
        .set-tabel th { font-size: .75rem; text-transform: uppercase; color: #64748b; }
        .set-tabel td { vertical-align: middle; }
    </style>

    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-2">
                    <h1 class="mb-1"><?= html_escape($judul) ?></h1>
                    <p class="text-muted mb-0">
                        <?= html_escape($subjudul) ?> &middot;
                        Tahun <?= html_escape($tp_active->tahun) ?> &middot;
                        Semester <?= html_escape($smt_active->nama_smt) ?>
                    </p>
                </div>
                <div class="mb-2">
                    <a href="<?= base_url('ujiansekolah') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                    </a>
                    <a href="<?= base_url('ujiansekolah/pengaturan_lama') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-cog mr-1"></i>Pengaturan lanjutan
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="set-card p-3 p-md-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="font-weight-bold mb-0"><i class="fas fa-users mr-1"></i>Peserta &amp; Ruang</h5>
                            <button type="button" class="btn btn-gelap btn-sm" id="btn-bagi">
                                <i class="fas fa-random mr-1"></i>Bagi Otomatis
                            </button>
                        </div>
                        <p class="text-muted small mb-3">
                            Pembagian dipakai semua ujian semester ini. Cukup sekali atur.
                        </p>

                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="h4 mb-0 font-weight-bold"><?= (int) $d['total_siswa'] ?></div>
                                    <div class="small text-muted">Total siswa</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="h4 mb-0 font-weight-bold text-success"><?= (int) $d['terdaftar'] ?></div>
                                    <div class="small text-muted">Sudah dibagi</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="h4 mb-0 font-weight-bold"><?= (int) $kapasitas['kapasitas'] ?></div>
                                    <div class="small text-muted">Kapasitas kursi</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table set-tabel mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th>Ruang</th>
                                    <th>Kode</th>
                                    <th style="width:130px">Kapasitas</th>
                                    <th style="width:80px"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (count($ruang) === 0): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada ruang.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($ruang as $r): ?>
                                    <tr>
                                        <td><?= html_escape($r->nama_ruang) ?></td>
                                        <td><span class="badge badge-light border"><?= html_escape($r->kode_ruang) ?></span></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm input-kapasitas"
                                                   data-ruang="<?= (int) $r->id_ruang ?>" min="1" max="500"
                                                   value="<?= (int) $r->kapasitas ?>">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-simpan-ruang"
                                                    data-ruang="<?= (int) $r->id_ruang ?>">
                                                Simpan
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2" id="pesan-ruang"></div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="set-card p-3 p-md-4 mb-4">
                        <h5 class="font-weight-bold mb-2"><i class="fas fa-key mr-1"></i>Token Global</h5>
                        <p class="text-muted small mb-3">
                            Token cadangan bila ujian tidak punya token sendiri.
                        </p>
                        <div class="form-group mb-2">
                            <label>Token (6 huruf/angka)</label>
                            <input type="text" class="form-control form-control-sm" id="token-global"
                                   maxlength="6" style="text-transform:uppercase"
                                   value="<?= html_escape($token_global ? $token_global->token : '') ?>">
                        </div>
                        <div class="form-row">
                            <div class="col-6">
                                <label>Jarak (menit)</label>
                                <input type="number" class="form-control form-control-sm" id="token-jarak"
                                       value="<?= (int) ($token_global ? $token_global->jarak : 30) ?>">
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="token-auto"
                                           <?= $token_global && (int) $token_global->auto === 1 ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="token-auto">Ganti otomatis</label>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-gelap btn-sm mt-3" id="simpan-token-global">
                            <i class="fas fa-save mr-1"></i>Simpan Token
                        </button>
                        <span class="small text-muted ml-2" id="pesan-token"></span>
                    </div>

                    <div class="set-card p-3 p-md-4">
                        <h5 class="font-weight-bold mb-2"><i class="fas fa-list mr-1"></i>Token per Ujian</h5>
                        <div class="table-responsive" style="max-height:260px;overflow:auto">
                            <table class="table set-tabel mb-0">
                                <thead class="bg-light">
                                <tr><th>Ujian</th><th>Token</th><th></th></tr>
                                </thead>
                                <tbody>
                                <?php if (count($ujian) === 0): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada ujian.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($ujian as $u): ?>
                                    <tr>
                                        <td class="small">
                                            <?= html_escape(!empty($u->judul) ? $u->judul : ($u->kode_jenis . ' ' . $u->nama_mapel)) ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border" id="token-<?= (int) $u->id_jadwal ?>">
                                                <?= html_escape($u->token_ujian ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-acak-token"
                                                    data-id="<?= (int) $u->id_jadwal ?>">
                                                <i class="fas fa-dice"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
    (function () {
        var BASE = '<?= base_url() ?>';
        var CSRF = '<?= $this->security->get_csrf_token_name() ?>';
        var HASH = '<?= $this->security->get_csrf_hash() ?>';

        function post(url, data, done) {
            data[CSRF] = HASH;
            $.post(BASE + url, data, function (res) {
                if (done) { done(res); }
            }, 'json').fail(function () {
                alert('Gagal menghubungi server.');
            });
        }

        $('#btn-bagi').on('click', function () {
            if (!window.confirm('Bagi ulang semua peserta ke ruang? Pembagian lama akan ditimpa.')) { return; }
            $(this).prop('disabled', true);
            post('ujiansekolah/bagiproses', { semua_ruang: 1 }, function (res) {
                alert((res && res.msg) || (res && res.status ? 'Berhasil.' : 'Gagal.'));
                if (res && res.status) { window.location.reload(); }
            });
        });

        $('.btn-simpan-ruang').on('click', function () {
            var id = $(this).data('ruang');
            var kap = $('.input-kapasitas[data-ruang="' + id + '"]').val();
            post('ujiansekolah/simpanruang', { id_ruang: id, kapasitas: kap }, function (res) {
                $('#pesan-ruang').text((res && res.msg) || '');
            });
        });

        $('#simpan-token-global').on('click', function () {
            post('ujiansekolah/tokensimpan', {
                token: $.trim($('#token-global').val()).toUpperCase(),
                auto: $('#token-auto').is(':checked') ? 1 : 0,
                jarak: $('#token-jarak').val()
            }, function (res) {
                $('#pesan-token').text((res && res.msg) || '');
            });
        });

        $('.btn-acak-token').on('click', function () {
            var id = $(this).data('id');
            var data = { id_jadwal: id };
            data[CSRF] = HASH;
            $.post(BASE + 'ujiansekolah/tokenacak', data, function (res) {
                if (res && res.status) {
                    $('#token-' + id).text(res.token || '');
                } else {
                    alert((res && res.msg) || 'Gagal mengacak token.');
                }
            }, 'json');
        });
    })();
</script>
