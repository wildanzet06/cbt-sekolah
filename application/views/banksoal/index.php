<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="content-wrapper bg-white pt-4">
    <style>
        .bs-card { border: 1px solid #e5e9f0; border-radius: 10px; background: #fff; }
        .bs-card label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .25rem; }
        .bs-opt { display: flex; align-items: flex-start; gap: .5rem; margin-bottom: .5rem; }
        .bs-abjad { width: 38px; height: 38px; flex: 0 0 38px; border: 1px solid #cbd5e1; border-radius: 6px;
            background: #f8fafc; font-weight: 700; color: #334155; cursor: pointer; }
        .bs-abjad.benar { background: #16a34a; border-color: #16a34a; color: #fff; }
        .btn-simpan-soal { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-simpan-soal:hover { background: #123862; color: #fff; }
        .bs-tabel th { font-size: .75rem; text-transform: uppercase; color: #64748b; }
        .bs-tabel td { vertical-align: middle; }
        .bs-preview img, .bs-preview audio { max-width: 100%; margin-top: .5rem; }
        .bs-bank { max-width: 420px; }
    </style>

    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-2">
                    <h1 class="mb-1"><?= html_escape($judul) ?></h1>
                    <p class="text-muted mb-0"><?= html_escape($subjudul) ?></p>
                </div>
                <div class="mb-2 bs-bank">
                    <?php if (!empty($banks)): ?>
                        <select class="form-control form-control-sm" id="pilih-bank">
                            <?php foreach ($banks as $b): ?>
                                <option value="<?= (int) $b->id_bank ?>" <?= (int) $b->id_bank === (int) $bank_id ? 'selected' : '' ?>>
                                    [<?= html_escape($b->bank_kode) ?>] <?= html_escape($b->nama_mapel) ?> —
                                    <?= html_escape($b->label_kelas) ?> (<?= (int) $b->jml ?> soal)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <span class="text-muted small">Belum ada bank soal.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if ($bank): ?>
                <div class="bs-card p-3 p-md-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="font-weight-bold mb-0">
                            <?= $soal_edit ? 'Edit Soal' : 'Input Soal Baru' ?>
                        </h5>
                        <span class="badge badge-light border">Pilihan Ganda</span>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <label for="bs-soal">Teks Pertanyaan</label>
                            <textarea class="form-control" id="bs-soal" rows="5"
                                      placeholder="Tuliskan isi soal secara jelas..."><?= $soal_edit ? html_escape($soal_edit->soal) : '' ?></textarea>

                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <label class="mb-0">Pilihan Jawaban &amp; Kunci Jawaban</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="bs-tambah-e">
                                    + Tambah Opsi E
                                </button>
                            </div>

                            <?php
                            $opsi_edit = array();
                            if ($soal_edit) {
                                foreach (array('a', 'b', 'c', 'd', 'e') as $ab) {
                                    $field = 'opsi_' . $ab;
                                    $opsi_edit[$ab] = (string) $soal_edit->$field;
                                }
                            }
                            $kunci_edit = $soal_edit ? strtolower((string) $soal_edit->jawaban) : '';
                            ?>
                            <div class="mt-2" id="bs-opsi-wrap">
                                <?php foreach (array('a', 'b', 'c', 'd') as $ab): ?>
                                    <div class="bs-opt" data-abjad="<?= $ab ?>">
                                        <button type="button" class="bs-abjad <?= $kunci_edit === $ab ? 'benar' : '' ?>"
                                                data-abjad="<?= $ab ?>"><?= strtoupper($ab) ?></button>
                                        <input type="text" class="form-control form-control-sm bs-opsi"
                                               data-abjad="<?= $ab ?>" placeholder="Isi pilihan <?= strtoupper($ab) ?>"
                                               value="<?= html_escape(isset($opsi_edit[$ab]) ? $opsi_edit[$ab] : '') ?>">
                                    </div>
                                <?php endforeach; ?>
                                <div class="bs-opt <?= !empty($opsi_edit['e']) || $kunci_edit === 'e' ? '' : 'd-none' ?>"
                                     data-abjad="e" id="bs-opt-e">
                                    <button type="button" class="bs-abjad <?= $kunci_edit === 'e' ? 'benar' : '' ?>"
                                            data-abjad="e">E</button>
                                    <input type="text" class="form-control form-control-sm bs-opsi"
                                           data-abjad="e" placeholder="Isi pilihan E"
                                           value="<?= html_escape(isset($opsi_edit['e']) ? $opsi_edit['e'] : '') ?>">
                                </div>
                            </div>
                            <small class="text-muted">Klik huruf A–E untuk menandai jawaban benar (hijau).</small>

                            <input type="hidden" id="bs-jawaban" value="<?= html_escape($kunci_edit) ?>">
                            <input type="hidden" id="bs-id" value="<?= $soal_edit ? (int) $soal_edit->id_soal : 0 ?>">
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <label class="d-block mb-2">Lampiran Media</label>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-block mb-2" id="bs-pilih-gambar">
                                    <i class="fas fa-image mr-1"></i>Pilih Gambar (JPG/PNG)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-block mb-2" id="bs-pilih-audio">
                                    <i class="fas fa-music mr-1"></i>Pilih Audio (MP3/WAV/OGG)
                                </button>
                                <input type="file" id="bs-file" accept="image/*,audio/*" class="d-none">
                                <div class="small text-muted" id="bs-media-status"></div>
                                <div class="bs-preview" id="bs-preview"></div>

                                <button type="button" class="btn btn-simpan-soal btn-block mt-3" id="bs-simpan">
                                    <i class="fas fa-save mr-1"></i>Simpan Soal
                                </button>
                                <a href="<?= base_url('banksoal?bank=' . (int) $bank_id) ?>"
                                   class="btn btn-link btn-sm btn-block <?= $soal_edit ? '' : 'd-none' ?>" id="bs-batal">
                                    Batal edit
                                </a>
                            </div>
                            <div class="mt-2 small text-muted" id="bs-pesan"></div>
                        </div>
                    </div>
                </div>

                <div class="bs-card mb-4">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="font-weight-bold mb-0">Daftar Soal (<?= count($soal_list) ?> Soal)</h5>

                        <a class="btn btn-sm btn-outline-secondary"
                           href="<?= base_url('cbtbanksoal/buatsoal/' . (int) $bank_id . '?tab=2') ?>">
                            Editor lama (PG kompleks/essay)
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table bs-tabel mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th class="pl-3" style="width:60px">No</th>
                                <th>Soal</th>
                                <th style="width:90px" class="text-center">Kunci</th>
                                <th style="width:120px" class="text-right pr-3">Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($soal_list) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada soal pada bank ini. Tambahkan soal pertama di atas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($soal_list as $s):
                                $ringkas = trim(preg_replace('/\s+/', ' ', strip_tags((string) $s->soal)));
                                ?>
                                <tr>
                                    <td class="pl-3 font-weight-bold"><?= (int) $s->nomor_soal ?></td>
                                    <td>
                                        <?= html_escape(mb_substr($ringkas, 0, 150)) ?><?= mb_strlen($ringkas) > 150 ? '…' : '' ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?= html_escape(strtoupper((string) $s->jawaban)) ?></span>
                                    </td>
                                    <td class="text-right pr-3 text-nowrap">
                                        <?php if ((int) $s->jenis === 1): ?>
                                            <a class="btn btn-sm btn-outline-secondary"
                                               href="<?= base_url('banksoal?bank=' . (int) $bank_id . '&edit=' . (int) $s->id_soal) ?>">
                                                Edit
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-light border">jenis lain</span>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-soal"
                                                data-id="<?= (int) $s->id_soal ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-light border">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pilih bank soal terlebih dahulu, atau buat bank soal di
                    <a href="<?= base_url('cbtbanksoal/addBank') ?>">Buat Bank Soal</a>.
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
    (function () {
        var BASE = '<?= base_url() ?>';
        var CSRF = '<?= $this->security->get_csrf_token_name() ?>';
        var HASH = '<?= $this->security->get_csrf_hash() ?>';
        var BANK = <?= (int) $bank_id ?>;

        $('#pilih-bank').on('change', function () {
            window.location.href = BASE + 'banksoal?bank=' + this.value;
        });

        $(document).on('click', '.bs-abjad', function () {
            var abjad = $(this).data('abjad');
            $('.bs-abjad').removeClass('benar');
            $(this).addClass('benar');
            $('#bs-jawaban').val(abjad);
        });

        $('#bs-tambah-e').on('click', function () {
            $('#bs-opt-e').removeClass('d-none');
            $(this).addClass('d-none');
        });

        var fileTarget = '';
        $('#bs-pilih-gambar').on('click', function () { fileTarget = 'image'; $('#bs-file').click(); });
        $('#bs-pilih-audio').on('click', function () { fileTarget = 'audio'; $('#bs-file').click(); });

        $('#bs-file').on('change', function () {
            var f = this.files && this.files[0];
            if (!f) { return; }
            var fd = new FormData();
            fd.append('file', f);
            fd.append(CSRF, HASH);
            $('#bs-media-status').text('Mengunggah...');

            $.ajax({
                url: BASE + 'banksoal/upload',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (!res || !res.status) {
                        $('#bs-media-status').text((res && res.msg) || 'Gagal mengunggah.');
                        return;
                    }
                    var tag = res.tipe === 'image'
                        ? '<img src="' + res.url + '" style="max-width:100%">'
                        : '<audio controls src="' + res.url + '"></audio>';
                    $('#bs-soal').val($.trim($('#bs-soal').val()) + '\n' + tag);
                    $('#bs-preview').html(tag);
                    $('#bs-media-status').text('Media ditambahkan ke soal.');
                },
                error: function () {
                    $('#bs-media-status').text('Gagal mengunggah.');
                }
            });
        });

        $('#bs-simpan').on('click', function () {
            var data = {
                bank_id: BANK,
                id_soal: $('#bs-id').val(),
                soal: $('#bs-soal').val(),
                jawaban: $('#bs-jawaban').val()
            };
            $('.bs-opsi').each(function () {
                data['opsi_' + $(this).data('abjad')] = $(this).val();
            });
            data[CSRF] = HASH;

            $('#bs-simpan').prop('disabled', true);
            $('#bs-pesan').text('Menyimpan...');

            $.post(BASE + 'banksoal/simpan', data, function (res) {
                if (res && res.status) {
                    window.location.href = BASE + 'banksoal?bank=' + BANK;
                } else {
                    $('#bs-pesan').text((res && res.msg) || 'Gagal menyimpan.');
                    $('#bs-simpan').prop('disabled', false);
                }
            }, 'json').fail(function () {
                $('#bs-pesan').text('Gagal menghubungi server.');
                $('#bs-simpan').prop('disabled', false);
            });
        });

        $('.btn-hapus-soal').on('click', function () {
            if (!window.confirm('Hapus soal ini?')) { return; }
            var data = { id_soal: $(this).data('id') };
            data[CSRF] = HASH;
            var $b = $(this);
            $b.prop('disabled', true);
            $.post(BASE + 'banksoal/hapus', data, function (res) {
                if (res && res.status) {
                    window.location.href = BASE + 'banksoal?bank=' + BANK;
                } else {
                    alert((res && res.msg) || 'Gagal menghapus.');
                    $b.prop('disabled', false);
                }
            }, 'json');
        });
    })();
</script>
