<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tab "Sesi & Jam" — menggantikan Sesi + Alokasi Waktu.
 * PRD 5.3 (tab Sesi & Jam): Sesi 1 aktif, Sesi 2–3 disembunyikan,
 * urutan jam (jam_ke) hanya bila perlu.
 */
$lain = [];
foreach ($sesi as $s) {
    if ($sesi1 && (int)$s->id_sesi === (int)$sesi1->id_sesi) {
        continue;
    }
    $lain[] = $s;
}
$adaBergiliran = false;
foreach ($ujian as $u) {
    if ((int)$u->jam_ke > 1) {
        $adaBergiliran = true;
        break;
    }
}
?>
<div class="alert alert-light border">
    <i class="fas fa-info-circle mr-1"></i>
    Pola sekolah: ujian <b>serentak</b> pada sesi dengan jam mulai paling awal. Semua ujian baru
    otomatis memakai <b>jam ke-1</b>.
</div>

<h6 class="mb-2"><i class="far fa-clock mr-1"></i> Sesi Utama</h6>
<?php if (!$sesi1): ?>
    <div class="alert alert-warning">Belum ada sesi. Tambahkan lewat halaman
        <a href="<?= base_url('cbtsesi?dari=pengaturan') ?>">Sesi (lama)</a>.
    </div>
<?php else: ?>
    <div class="card border mb-3">
        <div class="card-body py-2">
            <div class="form-row">
                <div class="col-md-3 mb-2">
                    <label class="small mb-1">Nama sesi</label>
                    <input type="text" class="form-control form-control-sm sesi-input"
                           data-sesi="<?= (int)$sesi1->id_sesi ?>" data-field="nama_sesi"
                           value="<?= html_escape($sesi1->nama_sesi) ?>" maxlength="50">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Kode</label>
                    <input type="text" class="form-control form-control-sm sesi-input"
                           data-sesi="<?= (int)$sesi1->id_sesi ?>" data-field="kode_sesi"
                           value="<?= html_escape($sesi1->kode_sesi) ?>" maxlength="10">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Mulai</label>
                    <input type="time" class="form-control form-control-sm sesi-input"
                           data-sesi="<?= (int)$sesi1->id_sesi ?>" data-field="waktu_mulai"
                           value="<?= html_escape(substr((string)$sesi1->waktu_mulai, 0, 5)) ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Akhir</label>
                    <input type="time" class="form-control form-control-sm sesi-input"
                           data-sesi="<?= (int)$sesi1->id_sesi ?>" data-field="waktu_akhir"
                           value="<?= html_escape(substr((string)$sesi1->waktu_akhir, 0, 5)) ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1 d-block">Aktif</label>
                    <div class="custom-control custom-switch mt-1">
                        <input type="checkbox" class="custom-control-input sesi-aktif"
                               id="aktif-<?= (int)$sesi1->id_sesi ?>"
                               data-sesi="<?= (int)$sesi1->id_sesi ?>"
                            <?= (int)$sesi1->aktif === 1 ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="aktif-<?= (int)$sesi1->id_sesi ?>"></label>
                    </div>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-primary btn-simpan-sesi"
                            data-sesi="<?= (int)$sesi1->id_sesi ?>">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (count($lain) > 0): ?>
    <div class="custom-control custom-switch mb-2">
        <input type="checkbox" class="custom-control-input" id="tampil-sesi-lain">
        <label class="custom-control-label" for="tampil-sesi-lain">
            Tampilkan sesi lain (<?= count($lain) ?> sesi)
            <small class="text-muted d-block" style="margin-left:-1.5rem">
                Sesi 2 dan seterusnya disembunyikan karena sekolah memakai pola serentak.
            </small>
        </label>
    </div>
    <div class="d-none" id="sesi-lain">
        <?php foreach ($lain as $s): ?>
            <div class="card border mb-2">
                <div class="card-body py-2">
                    <div class="form-row">
                        <div class="col-md-3 mb-2">
                            <label class="small mb-1">Nama sesi</label>
                            <input type="text" class="form-control form-control-sm sesi-input"
                                   data-sesi="<?= (int)$s->id_sesi ?>" data-field="nama_sesi"
                                   value="<?= html_escape($s->nama_sesi) ?>" maxlength="50">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small mb-1">Kode</label>
                            <input type="text" class="form-control form-control-sm sesi-input"
                                   data-sesi="<?= (int)$s->id_sesi ?>" data-field="kode_sesi"
                                   value="<?= html_escape($s->kode_sesi) ?>" maxlength="10">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small mb-1">Mulai</label>
                            <input type="time" class="form-control form-control-sm sesi-input"
                                   data-sesi="<?= (int)$s->id_sesi ?>" data-field="waktu_mulai"
                                   value="<?= html_escape(substr((string)$s->waktu_mulai, 0, 5)) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small mb-1">Akhir</label>
                            <input type="time" class="form-control form-control-sm sesi-input"
                                   data-sesi="<?= (int)$s->id_sesi ?>" data-field="waktu_akhir"
                                   value="<?= html_escape(substr((string)$s->waktu_akhir, 0, 5)) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small mb-1 d-block">Aktif</label>
                            <div class="custom-control custom-switch mt-1">
                                <input type="checkbox" class="custom-control-input sesi-aktif"
                                       id="aktif-<?= (int)$s->id_sesi ?>"
                                       data-sesi="<?= (int)$s->id_sesi ?>"
                                    <?= (int)$s->aktif === 1 ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="aktif-<?= (int)$s->id_sesi ?>"></label>
                            </div>
                        </div>
                        <div class="col-md-1 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-primary btn-simpan-sesi"
                                    data-sesi="<?= (int)$s->id_sesi ?>">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<hr>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0"><i class="fas fa-sort-numeric-down mr-1"></i> Urutan Jam Ujian</h6>
    <?php if (!$adaBergiliran): ?>
        <span class="badge badge-light border">Semua ujian jam ke-1 (serentak)</span>
    <?php endif; ?>
</div>
<p class="text-muted small">
    Hanya perlu diubah bila sekolah ingin ujian <b>bergiliran</b> dalam satu hari.
    Ujian dengan <b>jam ke-</b> sama akan aktif bersamaan.
</p>

<?php if (count($ujian) === 0): ?>
    <div class="alert alert-light border mb-0">Belum ada ujian pada semester ini.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-2" style="max-width:720px">
            <thead class="bg-light">
            <tr>
                <th>Tanggal</th>
                <th>Ujian</th>
                <th style="width:120px">Jam ke-</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ujian as $u): ?>
                <tr>
                    <td class="align-middle">
                        <?= html_escape(date('d/m/Y', strtotime((string)$u->tgl_mulai))) ?>
                    </td>
                    <td class="align-middle">
                        <?= html_escape(trim((!empty($u->kode_jenis) ? $u->kode_jenis : 'Ujian') . ' '
                            . (!empty($u->nama_mapel) ? $u->nama_mapel : '-'))) ?>
                        <?php if (!empty($u->label_kelas)): ?>
                            <small class="text-muted">· Kelas <?= html_escape($u->label_kelas) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <input type="number" class="form-control form-control-sm jam-input" min="1" max="10"
                               value="<?= max(1, (int)$u->jam_ke) ?>" data-jadwal="<?= (int)$u->id_jadwal ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-primary" id="btn-simpan-jam">
        <i class="fas fa-save"></i> Simpan Urutan Jam
    </button>
<?php endif; ?>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_SESI = '<?= base_url('ujiansekolah/sesisimpan') ?>';
        var URL_JAM = '<?= base_url('ujiansekolah/jamsimpan') ?>';

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

        $('#tampil-sesi-lain').on('change', function () {
            $('#sesi-lain').toggleClass('d-none', !$(this).is(':checked'));
        });

        $('#pane-sesi .btn-simpan-sesi').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('sesi');
            var data = {id_sesi: id};
            $('#pane-sesi .sesi-input[data-sesi="' + id + '"]').each(function () {
                data[$(this).data('field')] = $(this).val();
            });
            data.aktif = $('#aktif-' + id).is(':checked') ? 1 : 0;

            post(URL_SESI, data, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    window.setTimeout(function () { window.location.reload(); }, 700);
                }
            });
        });

        $('#btn-simpan-jam').on('click', function () {
            var data = {};
            $('#pane-sesi .jam-input').each(function () {
                data['jam[' + $(this).data('jadwal') + ']'] = $(this).val();
            });
            var $b = $(this).prop('disabled', true);
            post(URL_JAM, data, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
            });
        });
    })();
</script>
