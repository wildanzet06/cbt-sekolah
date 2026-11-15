<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$maks = (int)$u->nilai_maks;
$pre = ((int)$u->aspek === 2) ? 'k' : 'p';
$rapor_info = [
    'PH' => 'Masuk ke kolom ' . strtoupper($pre) . '1-' . strtoupper($pre) . '8 pada Nilai Harian rapor',
    'PTS' => 'Masuk ke Nilai PTS rapor (satu nilai per mapel; mengirim ulang akan menimpa)',
    'PAS' => 'Masuk ke Nilai PAS/Akhir rapor (satu nilai per mapel; mengirim ulang akan menimpa)',
];
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-9">
                    <h1><?= html_escape($u->nama_ujian) ?></h1>
                    <small class="text-muted">
                        <?= html_escape($u->nama_mapel) ?> &middot; Kelas <?= html_escape($u->nama_kelas) ?> &middot;
                        <?= html_escape($u->kode_jenis) ?>
                        <?php if ($u->kode_jenis === 'PH'): ?>(<?= (int)$u->aspek === 2 ? 'Keterampilan' : 'Pengetahuan' ?>)<?php endif; ?>
                        &middot; Nilai maksimal <?= $maks ?>
                    </small>
                </div>
                <div class="col-sm-3 text-right">
                    <a href="<?= base_url('nilaimanual') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title">Daftar siswa</h6>
                    <div class="card-tools">
                        <span class="badge badge-info" id="info-terisi">0 / <?= count($siswa) ?> terisi</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($siswa) === 0): ?>
                        <div class="alert alert-warning mb-0">Belum ada siswa di kelas ini pada tahun pelajaran/semester aktif.</div>
                    <?php else: ?>
                        <p class="text-muted small mb-2">
                            Tekan <kbd>Enter</kbd> atau <kbd>&darr;</kbd> untuk pindah ke siswa berikutnya.
                            Anda juga bisa menyalin satu kolom nilai dari Excel lalu menempelnya (<kbd>Ctrl</kbd>+<kbd>V</kbd>)
                            pada kotak nilai siswa pertama. Pecahan boleh memakai koma atau titik.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="tabel-nilai" style="max-width: 760px">
                                <thead class="bg-light">
                                <tr>
                                    <th style="width:45px" class="text-center">No</th>
                                    <th style="width:140px">NIS</th>
                                    <th>Nama</th>
                                    <th style="width:140px" class="text-center">Nilai (0-<?= $maks ?>)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($siswa as $i => $s):
                                    $v = $nilai[(int)$s->id_siswa] ?? null;
                                    $tampil = ($v === null) ? '' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td><?= html_escape($s->nis) ?></td>
                                        <td><?= html_escape($s->nama) ?></td>
                                        <td class="p-1">
                                            <input type="text" inputmode="decimal" autocomplete="off"
                                                   class="form-control form-control-sm text-center inp-nilai"
                                                   data-id="<?= (int)$s->id_siswa ?>"
                                                   value="<?= html_escape($tampil) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (count($siswa) > 0): ?>
                    <div class="card-footer">
                        <div class="form-inline">
                            <button type="button" class="btn btn-success mr-2" id="btn-simpan">
                                <i class="fas fa-save"></i> Simpan
                            </button>

                            <?php if ($u->kode_jenis === 'PH' && empty($u->slot_rapor)): ?>
                                <label for="slot" class="mr-1 ml-2 small">Kolom rapor:</label>
                                <select id="slot" class="form-control form-control-sm mr-2">
                                    <option value="">Otomatis (kolom kosong pertama)</option>
                                    <?php for ($n = 1; $n <= 8; $n++): ?>
                                        <option value="<?= $pre . $n ?>"><?= strtoupper($pre) . $n ?></option>
                                    <?php endfor; ?>
                                </select>
                            <?php endif; ?>

                            <button type="button" class="btn btn-primary" id="btn-rapor">
                                <i class="fas fa-paper-plane"></i>
                                <?= empty($u->rapor_terkirim) ? 'Simpan &amp; Kirim ke Rapor' : 'Simpan &amp; Kirim Ulang ke Rapor' ?>
                            </button>
                        </div>
                        <div class="small text-muted mt-2">
                            <?= html_escape($rapor_info[$u->kode_jenis] ?? '') ?>.
                            <?php if (!empty($u->rapor_terkirim)): ?>
                                Terakhir dikirim: <?= html_escape(date('d-m-Y H:i', strtotime($u->rapor_terkirim))) ?>
                                <?php if (!empty($u->slot_rapor)): ?>(kolom <?= html_escape(strtoupper($u->slot_rapor)) ?>)<?php endif; ?>.
                            <?php endif; ?>
                        </div>
                        <?php if ($u->kode_jenis === 'PH'): ?>
                            <div class="small text-muted">
                                Deskripsi capaian di rapor baru muncul setelah guru membuka
                                <b>Rapor &gt; Input Nilai &gt; Harian</b> untuk kelas ini lalu menekan Simpan.
                            </div>
                        <?php endif; ?>
                        <div class="small mt-2" id="pesan"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var MAKS = <?= $maks ?>;
        var ID_NM = <?= (int)$u->id_nm ?>;
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_SIMPAN = '<?= base_url('nilaimanual/simpannilai') ?>';
        var URL_RAPOR = '<?= base_url('nilaimanual/kirimrapor') ?>';
        var dirty = false;

        var $inputs = $('.inp-nilai');

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

        function norm(v) { return String(v).trim().replace(',', '.'); }

        function valid(v) {
            v = norm(v);
            if (v === '') { return true; }
            if (isNaN(v)) { return false; }
            var n = parseFloat(v);
            return n >= 0 && n <= MAKS;
        }

        function refreshInfo() {
            var terisi = 0;
            $inputs.each(function () {
                var ok = valid(this.value);
                $(this).toggleClass('is-invalid', !ok);
                if (norm(this.value) !== '' && ok) { terisi++; }
            });
            $('#info-terisi').text(terisi + ' / ' + $inputs.length + ' terisi');
        }

        function pesan(teks, ok) {
            $('#pesan').attr('class', 'small mt-2 ' + (ok ? 'text-success' : 'text-danger')).text(teks);
        }

        function kumpul() {
            var nilai = {};
            $inputs.each(function () { nilai[$(this).data('id')] = norm(this.value); });
            return nilai;
        }

        function simpan(lalu) {
            var salah = $inputs.filter('.is-invalid');
            if (salah.length) {
                pesan('Ada nilai yang tidak valid (di luar 0-' + MAKS + ' atau bukan angka). Perbaiki kotak yang berwarna merah.', false);
                salah.first().focus();
                return;
            }
            $('#btn-simpan, #btn-rapor').prop('disabled', true);
            post(URL_SIMPAN, {id_nm: ID_NM, nilai: kumpul()}, function (r) {
                if (!r.status) {
                    $('#btn-simpan, #btn-rapor').prop('disabled', false);
                    pesan(r.msg, false);
                    return;
                }
                dirty = false;
                if (lalu) {
                    lalu();
                } else {
                    $('#btn-simpan, #btn-rapor').prop('disabled', false);
                    pesan('Nilai tersimpan (' + r.jumlah + ' siswa).', true);
                }
            });
        }

        function kirimRapor() {
            var data = {id_nm: ID_NM, slot: $('#slot').length ? $('#slot').val() : ''};
            post(URL_RAPOR, data, function (r) {
                $('#btn-simpan, #btn-rapor').prop('disabled', false);
                pesan(r.msg, r.status);
                if (r.status) { setTimeout(function () { window.location.reload(); }, 1500); }
            });
        }

        $('#btn-simpan').on('click', function () { simpan(null); });
        $('#btn-rapor').on('click', function () {
            if (!window.confirm('Simpan nilai dan kirim ke rapor?\n\nNilai rapor siswa yang sudah ada pada kolom ini akan diperbarui.')) { return; }
            simpan(kirimRapor);
        });

        $inputs.on('input', function () { dirty = true; refreshInfo(); });

        $inputs.on('keydown', function (e) {
            var i = $inputs.index(this);
            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                e.preventDefault();
                $inputs.eq(Math.min(i + 1, $inputs.length - 1)).focus().select();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                $inputs.eq(Math.max(i - 1, 0)).focus().select();
            }
        });

        // Tempel banyak baris dari Excel mulai dari kotak yang sedang aktif
        $inputs.on('paste', function (e) {
            var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            var lines = text.split(/\r?\n/).filter(function (l, idx, arr) {
                return !(idx === arr.length - 1 && l === '');
            });
            if (lines.length < 2) { return; }          // tempel satu nilai: perilaku biasa
            e.preventDefault();
            var start = $inputs.index(this);
            $.each(lines, function (k, line) {
                var $t = $inputs.eq(start + k);
                if ($t.length) { $t.val(line.split('\t')[0].trim()); }
            });
            dirty = true;
            refreshInfo();
        });

        $(window).on('beforeunload', function () {
            if (dirty) { return 'Ada perubahan yang belum disimpan.'; }
        });

        refreshInfo();
    })();
</script>
