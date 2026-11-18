<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= html_escape($judul) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>">Home</a></li>
                        <li class="breadcrumb-item active"><?= html_escape($judul) ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Bacaan</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-tambah">
                            <i class="fas fa-plus"></i> Tambah Bacaan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Bacaan di sini bisa dipakai untuk <b>beberapa soal sekaligus</b>. Pilih bacaan
                        saat membuat soal di menu <b>Kumpulan Soal</b>, lalu bacaan akan tampil di atas
                        soal saat ujian.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabel-bacaan">
                            <thead>
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th>Judul</th>
                                    <th>Ringkasan Isi</th>
                                    <th style="width:150px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($bacaan as $b): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= html_escape($b->judul) ?></td>
                                        <td><?= html_escape(mb_substr(trim(strip_tags($b->isi)), 0, 120)) ?><?= mb_strlen(trim(strip_tags($b->isi))) > 120 ? '…' : '' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm btn-edit"
                                                    data-id="<?= (int) $b->id_bacaan ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-hapus"
                                                    data-id="<?= (int) $b->id_bacaan ?>"
                                                    data-judul="<?= html_escape($b->judul) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($bacaan)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">Belum ada bacaan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modal-bacaan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-judul">Tambah Bacaan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="id_bacaan" value="0">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" class="form-control" id="judul" placeholder="Contoh: Text 1: My Trip to the Beach">
                </div>
                <div class="form-group">
                    <label>Isi Bacaan</label>
                    <textarea class="form-control" id="isi" rows="12"
                              placeholder="Tulis bacaan di sini. Boleh memakai HTML sederhana seperti <p>, <strong>, <em>."></textarea>
                    <small class="text-muted">Boleh HTML sederhana: &lt;p&gt;, &lt;strong&gt;, &lt;br&gt;, &lt;table&gt;.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var BASE = '<?= base_url() ?>';
    var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
    var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';

    $(function () {
        function bukaModal(data) {
            $('#id_bacaan').val(data ? data.id_bacaan : 0);
            $('#judul').val(data ? data.judul : '');
            $('#isi').val(data ? data.isi : '');
            $('#modal-judul').text(data ? 'Edit Bacaan' : 'Tambah Bacaan');
            $('#modal-bacaan').modal('show');
        }

        $('#btn-tambah').on('click', function () { bukaModal(null); });

        $('.btn-edit').on('click', function () {
            var id = $(this).data('id');
            $.getJSON(BASE + 'bacaan/get/' + id, function (res) {
                if (res.status) { bukaModal(res.data); }
                else { alert(res.msg); }
            });
        });

        $('#btn-simpan').on('click', function () {
            var judul = $.trim($('#judul').val());
            if (judul === '') { alert('Judul bacaan wajib diisi.'); return; }

            var data = {};
            data['id_bacaan'] = $('#id_bacaan').val();
            data['judul'] = judul;
            data['isi'] = $('#isi').val();
            data[CSRF_NAME] = CSRF_HASH;

            $.post(BASE + 'bacaan/simpan', data, function (res) {
                if (res.status) { location.reload(); }
                else { alert(res.msg || 'Gagal menyimpan.'); }
            }, 'json');
        });

        $('.btn-hapus').on('click', function () {
            var id = $(this).data('id');
            var judul = $(this).data('judul');
            if (!confirm('Hapus bacaan "' + judul + '"? Soal yang memakainya tidak terhapus, hanya kehilangan bacaannya.')) { return; }

            var data = {};
            data[CSRF_NAME] = CSRF_HASH;
            $.post(BASE + 'bacaan/hapus/' + id, data, function (res) {
                if (res.status) { location.reload(); }
                else { alert(res.msg || 'Gagal menghapus.'); }
            }, 'json');
        });
    });
</script>
