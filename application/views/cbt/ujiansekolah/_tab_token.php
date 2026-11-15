<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tab "Token" — menggantikan menu Token.
 * PRD 5.3 (tab Token): token per ujian + acak ulang; token global sebagai cadangan.
 */
$hariIni = date('Y-m-d');
$globalToken = $token_global ? $token_global->token : '';
?>
<div class="alert alert-light border">
    <i class="fas fa-info-circle mr-1"></i>
    Setiap ujian punya <b>token sendiri</b>, jadi ujian paralel pada tanggal yang sama
    memakai token berbeda. Token di sini juga yang diverifikasi siswa di halaman ujian.
</div>

<div class="card border mb-3">
    <div class="card-header py-2">
        <b><i class="fas fa-globe mr-1"></i> Token Global (cadangan)</b>
    </div>
    <div class="card-body py-2">
        <p class="text-muted small mb-2">
            Dipakai hanya oleh ujian lama yang belum punya token sendiri. Ujian baru selalu memakai token per ujian.
        </p>
        <div class="form-row">
            <div class="col-sm-3 mb-2">
                <div class="input-group input-group-sm">
                    <input type="text" id="f-token-global" class="form-control text-uppercase"
                           maxlength="6" style="letter-spacing:2px;font-weight:600"
                           value="<?= html_escape($globalToken) ?>" placeholder="6 karakter">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary" id="btn-simpan-global">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<h6 class="mb-2"><i class="fas fa-key mr-1"></i> Token per Ujian</h6>

<?php if (count($ujian) === 0): ?>
    <div class="alert alert-light border mb-0">Belum ada ujian pada semester ini.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0" style="max-width:900px">
            <thead class="bg-light">
            <tr>
                <th>Tanggal</th>
                <th>Ujian</th>
                <th>Status</th>
                <th style="width:250px">Token</th>
                <th style="width:80px"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ujian as $u):
                $tgl = substr((string)$u->tgl_mulai, 0, 10);
                $isHariIni = $tgl === $hariIni;
                $token = (int)$u->token === 1 && !empty($u->token_ujian)
                    ? $u->token_ujian
                    : '';
                ?>
                <tr data-jadwal="<?= (int)$u->id_jadwal ?>">
                    <td class="align-middle">
                        <?= html_escape(date('d/m/Y', strtotime($tgl))) ?>
                        <?php if ($isHariIni): ?>
                            <span class="badge badge-success ml-1">hari ini</span>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <?= html_escape(trim((!empty($u->kode_jenis) ? $u->kode_jenis : 'Ujian') . ' '
                            . (!empty($u->nama_mapel) ? $u->nama_mapel : '-'))) ?>
                        <?php if (!empty($u->label_kelas)): ?>
                            <small class="text-muted d-block">Kelas <?= html_escape($u->label_kelas) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <span class="badge badge-<?= (int)$u->status === 1 ? 'success' : 'secondary' ?>">
                            <?= (int)$u->status === 1 ? 'Aktif' : 'Draf' ?>
                        </span>
                    </td>
                    <td class="align-middle">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control text-uppercase token-input"
                                   maxlength="6" style="letter-spacing:2px;font-weight:600"
                                   value="<?= html_escape($token) ?>" placeholder="6 karakter">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-acak"
                                        data-id="<?= (int)$u->id_jadwal ?>">
                                    <i class="fas fa-sync"></i>
                                </button>
                                <button type="button" class="btn btn-primary btn-simpan-token"
                                        data-id="<?= (int)$u->id_jadwal ?>">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class="align-middle text-center">
                        <?php if ($isHariIni): ?>
                            <span class="text-muted small">aktif</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_UJIAN = '<?= base_url('ujiansekolah/tokenujian') ?>';
        var URL_ACAK = '<?= base_url('ujiansekolah/tokenacak') ?>';
        var URL_GLOBAL = '<?= base_url('ujiansekolah/tokensimpan') ?>';

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        function post(url, data, cb) {
            data[CSRF.name] = CSRF.hash;
            $.ajax({
                type: 'POST', url: url, data: data, dataType: 'json',
                success: cb,
                error: function (xhr) {
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    cb({status: false, msg: msg});
                }
            });
        }

        $('#btn-simpan-global').on('click', function () {
            var $b = $(this).prop('disabled', true);
            post(URL_GLOBAL, {token: $('#f-token-global').val()}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
            });
        });

        $('#pane-token .btn-acak').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('id');
            post(URL_ACAK, {id_jadwal: id}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    $('#pane-token tr[data-jadwal="' + id + '"] .token-input').val(r.token);
                }
            });
        });

        $('#pane-token .btn-simpan-token').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('id');
            var $input = $('#pane-token tr[data-jadwal="' + id + '"] .token-input');
            post(URL_UJIAN, {id_jadwal: id, token: $input.val()}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    $input.val(r.token);
                }
            });
        });
    })();
</script>
