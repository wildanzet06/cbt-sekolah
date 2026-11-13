<?php
$boleh_tambah = !empty($semua_siswa);
$total = isset($ringkasan) && $ringkasan ? $ringkasan : (object) array('jml' => 0, 'poin' => 0, 'siswa' => 0);
$kelas_label = '';
foreach ($daftar_kelas as $k) {
    if ((int) $k->id_kelas === (int) $kelas_id) {
        $kelas_label = $k->nama_kelas;
        break;
    }
}
$filter_q = array('kelas' => $kelas_id, 'dari' => $dari, 'sampai' => $sampai);
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1><i class="fas fa-exclamation-triangle text-warning"></i> Poin Pelanggaran Siswa</h1>
                    <small class="text-muted">
                        Tahun pelajaran <?= html_escape($tp_active->tahun); ?> &middot;
                        Semester <?= html_escape($smt_active->nama_smt); ?>
                    </small>
                </div>
                <div class="col-sm-5 text-right">
                    <a href="<?= site_url('pelanggaran/rekap' . ($kelas_id ? '?kelas=' . (int) $kelas_id : '')); ?>"
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-chart-bar"></i> Rekap &amp; Ekspor
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" id="btnTambah"
                            data-toggle="modal" data-target="#modalPelanggaran" <?= $boleh_tambah ? '' : 'disabled'; ?>>
                        <i class="fas fa-plus"></i> Tambah Pelanggaran
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle mr-1"></i> <?= html_escape($this->session->flashdata('success')); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-circle mr-1"></i> <?= html_escape($this->session->flashdata('error')); ?>
                </div>
            <?php endif; ?>

            <?php if (!$boleh_tambah): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle mr-1"></i>
                    Belum ada siswa yang terdaftar pada tahun pelajaran dan semester aktif.
                    Lengkapi data kelas/siswa terlebih dahulu.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-warning"><i class="fas fa-list"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Jumlah Pelanggaran</span>
                            <span class="info-box-number"><?= (int) $total->jml; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-danger"><i class="fas fa-star-half-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Poin</span>
                            <span class="info-box-number"><?= (int) $total->poin; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Siswa Terlibat</span>
                            <span class="info-box-number"><?= (int) $total->siswa; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="get" action="<?= site_url('pelanggaran'); ?>" class="form-row align-items-end">
                        <div class="form-group col-12 col-md-4">
                            <label>Kelas</label>
                            <select name="kelas" class="form-control">
                                <option value="">Semua kelas</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= (int) $k->id_kelas; ?>" <?= ((int) $kelas_id === (int) $k->id_kelas) ? 'selected' : ''; ?>>
                                        <?= html_escape($k->nama_kelas); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Dari tanggal <small class="text-muted">(opsional)</small></label>
                            <input type="date" name="dari" class="form-control" value="<?= html_escape($dari); ?>">
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Sampai tanggal <small class="text-muted">(opsional)</small></label>
                            <input type="date" name="sampai" class="form-control" value="<?= html_escape($sampai); ?>">
                        </div>
                        <div class="form-group col-12 col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-clipboard-list"></i>
                        Daftar Pelanggaran<?= $kelas_label !== '' ? ' &middot; Kelas ' . html_escape($kelas_label) : ''; ?>
                    </h3>
                    <a href="<?= site_url('pelanggaran/export?' . http_build_query(array_merge($filter_q, array('jenis' => 'detail')))); ?>"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Ekspor Excel
                    </a>
                </div>
                <div class="card-body table-responsive">
                    <table id="tablePelanggaran" class="table table-bordered table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th style="width:40px">No</th>
                                <th>Tanggal</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis Pelanggaran</th>
                                <th class="text-center">Poin</th>
                                <th>Keterangan</th>
                                <th>Dicatat Oleh</th>
                                <th class="text-center" style="width:110px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($daftar as $r): ?>
                                <?php
                                $boleh = $is_admin || ($r->dicatat_oleh !== null && $r->dicatat_oleh === $username);
                                $poin  = (int) $r->poin;
                                $badge = $poin >= 50 ? 'danger' : ($poin >= 20 ? 'warning' : 'secondary');
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td data-order="<?= html_escape($r->tanggal); ?>"><?= date('d/m/Y', strtotime($r->tanggal)); ?></td>
                                    <td><?= html_escape($r->nis); ?></td>
                                    <td><?= html_escape($r->nama); ?></td>
                                    <td><?= html_escape($r->nama_kelas ? $r->nama_kelas : '-'); ?></td>
                                    <td><?= html_escape($r->jenis_pelanggaran); ?></td>
                                    <td class="text-center"><span class="badge badge-<?= $badge; ?>"><?= $poin; ?></span></td>
                                    <td><?= $r->keterangan ? nl2br(html_escape($r->keterangan)) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?= html_escape($r->dicatat_oleh ? $r->dicatat_oleh : '-'); ?></td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($boleh): ?>
                                            <button type="button" class="btn btn-info btn-xs btn-edit"
                                                    data-id="<?= (int) $r->id_pelanggaran; ?>" title="Ubah">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?= form_open('pelanggaran/hapus/' . (int) $r->id_pelanggaran,
                                                array('class' => 'd-inline',
                                                      'onsubmit' => "return confirm('Hapus catatan pelanggaran ini? Tindakan ini tidak bisa dibatalkan.');")); ?>
                                                <button type="submit" class="btn btn-danger btn-xs" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?= form_close(); ?>
                                        <?php else: ?>
                                            <span class="text-muted" title="Hanya pembuat catatan atau admin yang boleh mengubah">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal tambah/ubah -->
<div class="modal fade" id="modalPelanggaran" tabindex="-1" role="dialog" aria-labelledby="judulModalPelanggaran" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= form_open('pelanggaran/simpan', array('id' => 'formPelanggaran')); ?>
            <input type="hidden" name="id_pelanggaran" id="f_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalPelanggaran">Tambah Pelanggaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Siswa <span class="text-danger">*</span></label>
                    <select name="id_siswa" id="f_siswa" class="form-control" required>
                        <option value="">-- Pilih siswa --</option>
                        <?php foreach ($semua_siswa as $s): ?>
                            <option value="<?= (int) $s->id_siswa; ?>">
                                <?= html_escape($s->nis . ' - ' . $s->nama . ' (' . $s->nama_kelas . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" id="f_kelas" class="form-control" value="-" readonly>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="f_tanggal" class="form-control"
                               max="<?= html_escape($tanggal_ini); ?>" value="<?= html_escape($tanggal_ini); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Poin <span class="text-danger">*</span></label>
                        <input type="number" name="poin" id="f_poin" class="form-control"
                               min="1" max="1000" step="1" placeholder="mis. 10" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Jenis Pelanggaran <span class="text-danger">*</span></label>
                    <input type="text" name="jenis_pelanggaran" id="f_jenis" class="form-control"
                           list="daftarJenisPelanggaran" maxlength="150" placeholder="Tulis jenis pelanggaran" required>
                    <datalist id="daftarJenisPelanggaran">
                        <?php foreach ($jenis_umum as $j): ?>
                            <option value="<?= html_escape($j); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <small class="text-muted">Ketik bebas atau pilih salah satu saran.</small>
                </div>
                <div class="form-group">
                    <label>Keterangan <small class="text-muted">(opsional)</small></label>
                    <textarea name="keterangan" id="f_ket" rows="3" maxlength="500" class="form-control"
                              placeholder="Catatan tambahan, mis. kejadian dan tindakan"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalPelanggaran');
    var form    = document.getElementById('formPelanggaran');
    var select  = document.getElementById('f_siswa');
    var fieldKelas = document.getElementById('f_kelas');
    var dataEdit = <?= json_encode($data_edit, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var siswaMap = <?= json_encode($siswa_map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function perbaruiKelas() {
        var info = siswaMap[select.value];
        fieldKelas.value = info && info.nama_kelas ? info.nama_kelas : '-';
    }
    if (select) {
        select.addEventListener('change', perbaruiKelas);
    }

    if (window.jQuery && jQuery.fn.DataTable && document.getElementById('tablePelanggaran')) {
        jQuery('#tablePelanggaran').DataTable({
            'pageLength': 25,
            'order': [[1, 'desc']],
            'columnDefs': [
                {'targets': 0, 'orderable': false},
                {'targets': 9, 'orderable': false}
            ],
            'language': {
                'search': 'Cari:',
                'lengthMenu': 'Tampilkan _MENU_ data',
                'info': 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                'infoEmpty': 'Tidak ada data',
                'zeroRecords': 'Data tidak ditemukan',
                'paginate': {'previous': 'Sebelumnya', 'next': 'Berikutnya'},
                'emptyTable': 'Belum ada catatan pelanggaran.'
            }
        });
    }

    var btnTambah = document.getElementById('btnTambah');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            form.action = base_url + 'pelanggaran/simpan';
            form.reset();
            document.getElementById('f_id').value = '0';
            select.value = '';
            fieldKelas.value = '-';
            document.getElementById('f_tanggal').value = <?= json_encode($tanggal_ini); ?>;
            document.getElementById('f_poin').value = '';
            document.getElementById('f_jenis').value = '';
            document.getElementById('f_ket').value = '';
            document.getElementById('judulModalPelanggaran').textContent = 'Tambah Pelanggaran';
        });
    }

    var tombolUbah = document.querySelectorAll('.btn-edit');
    for (var i = 0; i < tombolUbah.length; i++) {
        tombolUbah[i].addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            var d = dataEdit[id];
            if (!d) return;

            form.action = base_url + 'pelanggaran/ubah/' + id;
            document.getElementById('f_id').value = id;
            select.value = String(d.id_siswa);
            document.getElementById('f_tanggal').value = d.tanggal;
            document.getElementById('f_jenis').value = d.jenis;
            document.getElementById('f_poin').value = d.poin;
            document.getElementById('f_ket').value = d.keterangan;
            perbaruiKelas();
            document.getElementById('judulModalPelanggaran').textContent = 'Ubah Pelanggaran';

            if (window.jQuery) {
                jQuery(modalEl).modal('show');
            }
        });
    }
});
</script>
