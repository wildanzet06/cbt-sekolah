<?php
$tgl_label = date('d/m/Y', strtotime($tanggal));
?>
<style>
    /* Tombol status H / I / S / A (tanpa JavaScript tambahan) */
    .pil-status { display: inline-block; margin: 0 .12rem; position: relative; }
    .pil-status input { position: absolute; opacity: 0; pointer-events: none; }
    .pil-status span {
        display: inline-block; min-width: 2.5rem; padding: .4rem .55rem; text-align: center;
        border: 1px solid #ced4da; border-radius: .35rem; cursor: pointer;
        font-weight: 600; color: #6c757d; background: #fff; user-select: none;
    }
    .pil-status input:checked + span.s-H { background: #28a745; border-color: #28a745; color: #fff; }
    .pil-status input:checked + span.s-I { background: #17a2b8; border-color: #17a2b8; color: #fff; }
    .pil-status input:checked + span.s-S { background: #ffc107; border-color: #ffc107; color: #212529; }
    .pil-status input:checked + span.s-A { background: #dc3545; border-color: #dc3545; color: #fff; }
    .pil-status input:focus + span { box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25); }
    .tabel-presensi td { vertical-align: middle; }
    .bar-simpan { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #dee2e6; padding: .6rem 1rem; z-index: 5; }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1><i class="fas fa-user-check text-primary"></i> Presensi Kelas</h1>
                </div>
                <div class="col-sm-5 text-right">
                    <a href="<?= site_url('presensi/rekap'); ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-chart-bar"></i> Rekap Presensi
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <!-- Pilih sesi pelajaran -->
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="get" action="<?= site_url('presensi'); ?>" class="form-row align-items-end">
                        <div class="form-group col-6 col-md-3">
                            <label>Kelas</label>
                            <select name="kelas" class="form-control" required>
                                <option value="">-- Pilih kelas --</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= (int) $k->id_kelas; ?>" <?= ($kelas_id == $k->id_kelas) ? 'selected' : ''; ?>>
                                        <?= html_escape($k->nama_kelas); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= html_escape($tanggal); ?>"
                                   max="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group col-6 col-md-2">
                            <label>Jam ke-</label>
                            <select name="jam" class="form-control">
                                <?php for ($j = 1; $j <= $maks_jam; $j++): ?>
                                    <option value="<?= $j; ?>" <?= ($jam == $j) ? 'selected' : ''; ?>><?= $j; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($sesi_hari)): ?>
                        <div class="mt-1">
                            <small class="text-muted">Sesi yang sudah tercatat pada tanggal ini:</small>
                            <?php foreach ($sesi_hari as $sh): ?>
                                <a href="<?= site_url('presensi?' . http_build_query(array('kelas' => $kelas_id, 'tanggal' => $tanggal, 'jam' => $sh->jam_ke))); ?>"
                                   class="badge badge-<?= ($sh->jam_ke == $jam) ? 'primary' : 'secondary'; ?> p-2 ml-1">
                                    Jam <?= (int) $sh->jam_ke; ?><?= $sh->mapel_nama ? ' &middot; ' . html_escape($sh->mapel_nama) : ''; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($kelas_id && !empty($roster)): ?>
            <?= form_open('presensi/simpan', array('id' => 'formPresensi')); ?>
                <input type="hidden" name="kelas" value="<?= (int) $kelas_id; ?>">
                <input type="hidden" name="tanggal" value="<?= html_escape($tanggal); ?>">
                <input type="hidden" name="jam" value="<?= (int) $jam; ?>">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Kelas <b><?= html_escape($nama_kelas); ?></b> &middot; <?= $tgl_label; ?> &middot; Jam ke-<?= (int) $jam; ?>
                            <?php if ($sesi): ?><span class="badge badge-success ml-2">sudah pernah disimpan</span><?php endif; ?>
                        </h3>
                    </div>

                    <div class="card-body pb-0">
                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label>Mata pelajaran <small class="text-muted">(opsional)</small></label>
                                <?php if ($mapel_tersedia): ?>
                                    <select name="mapel_id" class="form-control">
                                        <option value="0">-- Tanpa mata pelajaran --</option>
                                        <?php foreach ($daftar_mapel as $m): ?>
                                            <option value="<?= (int) $m->id_mapel; ?>"
                                                <?= ($sesi && $sesi->mapel_id == $m->id_mapel) ? 'selected' : ''; ?>>
                                                <?= html_escape($m->nama_mapel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" name="mapel_nama" maxlength="100" class="form-control"
                                           placeholder="mis. Matematika"
                                           value="<?= $sesi ? html_escape($sesi->mapel_nama) : ''; ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-7 d-flex align-items-end flex-wrap">
                                <button type="button" class="btn btn-success mr-2 mb-2" id="btnHadirSemua">
                                    <i class="fas fa-check-double"></i> Hadir semua
                                </button>
                                <span class="mb-2 ml-md-2" id="ringkasan"></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body table-responsive pt-0">
                        <table class="table table-bordered table-striped tabel-presensi">
                            <thead>
                                <tr>
                                    <th style="width:40px">No</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center">Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($roster as $s): ?>
                                    <?php
                                    $cur = isset($tersimpan[$s->id_siswa]) ? $tersimpan[$s->id_siswa]->status : 'H';
                                    $ketv = isset($tersimpan[$s->id_siswa]) ? $tersimpan[$s->id_siswa]->keterangan : '';
                                    ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <?= html_escape($s->nama); ?><br>
                                            <small class="text-muted"><?= html_escape($s->nis); ?></small>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <?php foreach ($status_ref as $kode => $nama): ?>
                                                <label class="pil-status" title="<?= $nama; ?>">
                                                    <input type="radio" name="status[<?= (int) $s->id_siswa; ?>]"
                                                           value="<?= $kode; ?>" <?= ($cur === $kode) ? 'checked' : ''; ?>>
                                                    <span class="s-<?= $kode; ?>"><?= $kode; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="ket[<?= (int) $s->id_siswa; ?>]" maxlength="255"
                                                   class="form-control form-control-sm" placeholder="opsional"
                                                   value="<?= html_escape($ketv); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <small class="text-muted">H = Hadir, I = Izin, S = Sakit, A = Alpa. Yang tidak diubah dianggap Hadir.</small>
                    </div>

                    <div class="bar-simpan d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($sesi): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnHapus">
                                    <i class="fas fa-trash"></i> Hapus sesi ini
                                </button>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Presensi
                        </button>
                    </div>
                </div>
            <?= form_close(); ?>

            <?php if ($sesi): ?>
                <?= form_open('presensi/hapus_sesi/' . (int) $sesi->id, array('id' => 'formHapus', 'style' => 'display:none')); ?>
                <?= form_close(); ?>
            <?php endif; ?>

            <?php elseif ($kelas_id): ?>
                <div class="alert alert-warning">Kelas ini belum memiliki siswa pada tahun pelajaran dan semester aktif.</div>
            <?php else: ?>
                <div class="callout callout-info">
                    Pilih <b>kelas</b>, <b>tanggal</b>, dan <b>jam pelajaran</b>, lalu klik <i>Tampilkan</i> untuk mulai memanggil nama siswa.
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
(function () {
    var form = document.getElementById('formPresensi');
    if (!form) return;

    var ringkasan = document.getElementById('ringkasan');
    var label = {H: 'Hadir', I: 'Izin', S: 'Sakit', A: 'Alpa'};

    function hitung() {
        var n = {H: 0, I: 0, S: 0, A: 0};
        var terpilih = form.querySelectorAll('input[type=radio]:checked');
        for (var i = 0; i < terpilih.length; i++) n[terpilih[i].value]++;
        var teks = [];
        for (var k in label) teks.push(label[k] + ' <b>' + n[k] + '</b>');
        ringkasan.innerHTML = teks.join(' &nbsp;&bull;&nbsp; ');
    }

    form.addEventListener('change', hitung);
    hitung();

    document.getElementById('btnHadirSemua').addEventListener('click', function () {
        var h = form.querySelectorAll('input[type=radio][value=H]');
        for (var i = 0; i < h.length; i++) h[i].checked = true;
        hitung();
    });

    var hapus = document.getElementById('btnHapus');
    if (hapus) {
        hapus.addEventListener('click', function () {
            if (confirm('Hapus seluruh presensi untuk sesi ini? Tindakan ini tidak bisa dibatalkan.')) {
                document.getElementById('formHapus').submit();
            }
        });
    }
})();
</script>
