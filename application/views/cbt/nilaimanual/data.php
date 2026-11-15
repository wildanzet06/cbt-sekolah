<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1><?= html_escape($judul) ?></h1>
                    <small class="text-muted">
                        Tahun pelajaran <?= html_escape($tp_active->tahun) ?> &middot; Semester <?= html_escape($smt_active->nama_smt) ?>
                    </small>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><?= html_escape($subjudul) ?></h6>
                    <div class="card-tools">
                        <a href="<?= base_url('nilaimanual/rekap') ?>" class="btn btn-sm btn-outline-secondary mr-1">
                            <i class="fas fa-table"></i> Rekap
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-baru">
                            <i class="fas fa-plus"></i> Buat Ujian Offline
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($opsi['pairs']) === 0): ?>
                        <div class="alert alert-warning mb-0">
                            Belum ada mapel/kelas yang bisa dipilih. Pastikan Anda sudah diberi jadwal mengajar
                            (mapel dan kelas) pada tahun pelajaran dan semester aktif.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm" id="tabel-ujian">
                                <thead class="bg-light">
                                <tr>
                                    <th style="width:40px" class="text-center">#</th>
                                    <th>Tanggal</th>
                                    <th>Nama Ujian</th>
                                    <th>Mapel</th>
                                    <th>Kelas</th>
                                    <th>Jenis</th>
                                    <th class="text-center">Terisi</th>
                                    <th>Rapor</th>
                                    <th class="text-center" style="width:190px">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (count($ujian) === 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Belum ada ujian offline. Klik <b>Buat Ujian Offline</b> untuk memulai.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($ujian as $i => $u): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td><?= $u->tanggal ? html_escape(date('d-m-Y', strtotime($u->tanggal))) : '-' ?></td>
                                        <td><?= html_escape($u->nama_ujian) ?></td>
                                        <td><?= html_escape($u->nama_mapel) ?></td>
                                        <td><?= html_escape($u->nama_kelas) ?></td>
                                        <td>
                                            <?= html_escape($u->kode_jenis) ?>
                                            <?php if ($u->kode_jenis === 'PH'): ?>
                                                <small class="text-muted">(<?= (int)$u->aspek === 2 ? 'Keterampilan' : 'Pengetahuan' ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= (int)$u->terisi ?> / <?= (int)$u->total_siswa ?></td>
                                        <td>
                                            <?php if (!empty($u->rapor_terkirim)): ?>
                                                <span class="badge badge-success">Terkirim</span>
                                                <?php if (!empty($u->slot_rapor)): ?>
                                                    <small>kolom <?= html_escape(strtoupper($u->slot_rapor)) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('nilaimanual/isi/' . (int)$u->id_nm) ?>"
                                               class="btn btn-xs btn-success"><i class="fas fa-edit"></i> Isi Nilai</a>
                                            <button type="button" class="btn btn-xs btn-warning btn-edit"
                                                    data-id="<?= (int)$u->id_nm ?>"
                                                    data-nama="<?= html_escape($u->nama_ujian) ?>"
                                                    data-tanggal="<?= html_escape($u->tanggal) ?>"
                                                    data-maks="<?= (int)$u->nilai_maks ?>">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger btn-hapus"
                                                    data-id="<?= (int)$u->id_nm ?>"
                                                    data-nama="<?= html_escape($u->nama_ujian) ?>"
                                                    data-rapor="<?= !empty($u->rapor_terkirim) ? 1 : 0 ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal buat/ubah ujian -->
<div class="modal fade" id="modal-ujian" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-judul">Buat Ujian Offline</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="f-id" value="0">

                <div class="form-group baru-only">
                    <label for="f-mapel">Mata pelajaran</label>
                    <select id="f-mapel" class="form-control"></select>
                </div>
                <div class="form-group baru-only">
                    <label for="f-kelas">Kelas</label>
                    <select id="f-kelas" class="form-control"></select>
                </div>
                <div class="form-row baru-only">
                    <div class="form-group col-md-7">
                        <label for="f-jenis">Jenis</label>
                        <select id="f-jenis" class="form-control">
                            <?php foreach ($jenis as $kode => $nama): ?>
                                <option value="<?= html_escape($kode) ?>"><?= html_escape($kode . ' - ' . $nama) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-5" id="grp-aspek">
                        <label for="f-aspek">Aspek rapor</label>
                        <select id="f-aspek" class="form-control">
                            <option value="1">Pengetahuan</option>
                            <option value="2">Keterampilan</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="f-nama">Nama ujian</label>
                    <input type="text" id="f-nama" class="form-control" maxlength="100" placeholder="mis. Ulangan Harian 1 - Bab Pecahan">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="f-tanggal">Tanggal</label>
                        <input type="date" id="f-tanggal" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="f-maks">Nilai maksimal</label>
                        <input type="number" id="f-maks" class="form-control" min="1" max="1000" value="100">
                        <small class="text-muted">Nilai akan dikonversi ke skala 100 saat masuk rapor.</small>
                    </div>
                </div>
                <div class="text-danger small d-none" id="f-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var OPSI = <?= json_encode($opsi, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_SIMPAN = '<?= base_url('nilaimanual/simpan') ?>';
        var URL_HAPUS = '<?= base_url('nilaimanual/hapus') ?>';
        var URL_ISI = '<?= base_url('nilaimanual/isi/') ?>';

        function post(url, data, cb) {
            data = data || {};
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

        function fillMapel() {
            var $m = $('#f-mapel').empty();
            $.each(OPSI.mapel, function (id, nama) {
                $m.append($('<option>').val(id).text(nama));
            });
            fillKelas();
        }

        function fillKelas() {
            var $k = $('#f-kelas').empty();
            var list = OPSI.pairs[$('#f-mapel').val()] || [];
            $.each(list, function (i, id) {
                $k.append($('<option>').val(id).text(OPSI.kelas[id]));
            });
        }

        function toggleAspek() {
            $('#grp-aspek').toggle($('#f-jenis').val() === 'PH');
        }

        function bukaModal(edit, d) {
            $('#f-error').addClass('d-none').text('');
            if (edit) {
                $('#modal-judul').text('Ubah Ujian Offline');
                $('.baru-only').hide();
                $('#f-id').val(d.id);
                $('#f-nama').val(d.nama);
                $('#f-tanggal').val(d.tanggal);
                $('#f-maks').val(d.maks);
            } else {
                $('#modal-judul').text('Buat Ujian Offline');
                $('.baru-only').show();
                $('#f-id').val(0);
                $('#f-nama').val('');
                $('#f-tanggal').val(new Date().toISOString().slice(0, 10));
                $('#f-maks').val(100);
                fillMapel();
                toggleAspek();
            }
            $('#modal-ujian').modal('show');
        }

        $('#btn-baru').on('click', function () { bukaModal(false); });
        $('#f-mapel').on('change', fillKelas);
        $('#f-jenis').on('change', toggleAspek);

        $('.btn-edit').on('click', function () {
            var $b = $(this);
            bukaModal(true, {id: $b.data('id'), nama: $b.data('nama'), tanggal: $b.data('tanggal'), maks: $b.data('maks')});
        });

        $('#btn-simpan').on('click', function () {
            var id = parseInt($('#f-id').val(), 10) || 0;
            var data = {
                id_nm: id,
                nama_ujian: $('#f-nama').val(),
                tanggal: $('#f-tanggal').val(),
                nilai_maks: $('#f-maks').val()
            };
            if (!id) {
                data.id_mapel = $('#f-mapel').val();
                data.id_kelas = $('#f-kelas').val();
                data.kode_jenis = $('#f-jenis').val();
                data.aspek = $('#f-aspek').val();
            }
            var $btn = $(this).prop('disabled', true);
            post(URL_SIMPAN, data, function (r) {
                $btn.prop('disabled', false);
                if (!r.status) {
                    $('#f-error').removeClass('d-none').text(r.msg);
                    return;
                }
                if (!id) {
                    window.location.href = URL_ISI + r.id;   // langsung ke halaman isi nilai
                } else {
                    window.location.reload();
                }
            });
        });

        $('.btn-hapus').on('click', function () {
            var $b = $(this);
            var id = $b.data('id');
            var teks = 'Hapus ujian "' + $b.data('nama') + '" beserta semua nilainya?';
            if (parseInt($b.data('rapor'), 10) === 1) {
                teks += '\n\nNilai yang sudah terkirim ke rapor TIDAK ikut terhapus.';
            }
            if (!window.confirm(teks)) { return; }
            post(URL_HAPUS, {id_nm: id}, function (r) {
                if (r.status) { window.location.reload(); } else { window.alert(r.msg); }
            });
        });
    })();
</script>
