<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wizard "Buat Ujian Baru" — 3 langkah (PRD 5.2)
 */

$bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];

$edit = !empty($u);
$id_jadwal = $edit ? (int)$u->id_jadwal : 0;

$nilai = [
    'id_mapel'      => $edit ? (int)$u->meta_mapel : 0,
    'id_jenis'      => $edit ? (int)$u->id_jenis : 0,
    'tanggal'       => $edit ? substr((string)$u->tgl_mulai, 0, 10) : date('Y-m-d'),
    'durasi'        => $edit ? (int)$u->durasi_ujian : 60,
    'id_bank'       => $edit ? (int)$u->id_bank : 0,
    'token_ujian'   => $edit ? (string)$u->token_ujian : '',
    'gunakan_token' => $edit ? (int)$u->token : 1,
    'acak_soal'     => $edit ? (int)$u->acak_soal : 1,
    'acak_opsi'     => $edit ? (int)$u->acak_opsi : 1,
    'hasil_tampil'  => $edit ? (int)$u->hasil_tampil : 1,
    'status'        => $edit ? (int)$u->status : 0,
    'catatan'       => $edit && $meta ? (string)$meta->catatan : ''
];

// Mapel ujian: untuk guru hanya mapel yang dia ampu.
$mapelTerpilih = 0;
if ($edit) {
    $mapelTerpilih = $u->bank_mapel_id ? (int)$u->bank_mapel_id : (int)$u->meta_mapel;
}
if ($mapelTerpilih === 0 && count($opsi['mapel']) === 1) {
    $kunci = array_keys($opsi['mapel']);
    $mapelTerpilih = (int)$kunci[0];
}

// Jenis default: jenis terakhir, fallback PH, fallback jenis pertama.
$jenisTerpilih = $nilai['id_jenis'];
if ($jenisTerpilih === 0) {
    if ($jenis_akhir) {
        $jenisTerpilih = (int)$jenis_akhir;
    } else {
        foreach ($jenis as $j) {
            if ($j->kode_jenis === 'PH') {
                $jenisTerpilih = (int)$j->id_jenis;
                break;
            }
        }
        if ($jenisTerpilih === 0 && count($jenis) > 0) {
            $jenisTerpilih = (int)$jenis[0]->id_jenis;
        }
    }
}

$banksJs = [];
foreach ($banks as $b) {
    $banksJs[] = [
        'id_bank'      => (int)$b->id_bank,
        'mapel_id'     => (int)$b->bank_mapel_id,
        'nama'         => ($b->bank_nama !== '' && $b->bank_nama !== null) ? $b->bank_nama : $b->bank_kode,
        'kode'         => $b->bank_kode,
        'mapel'        => $b->nama_mapel,
        'kelas'        => $b->label_kelas,
        'jml'          => (int)$b->jml,
        'jml_pg'       => (int)$b->jml_pg,
        'jml_esai'     => (int)$b->jml_esai,
        'kkm'          => (int)$b->kkm,
        'pakai'        => $b->pakai_label,
        'jumlah_siswa' => (int)$b->jumlah_siswa
    ];
}

$jenisJs = [];
foreach ($jenis as $j) {
    $jenisJs[] = ['id' => (int)$j->id_jenis, 'kode' => $j->kode_jenis, 'nama' => $j->nama_jenis];
}

$this->load->view('cbt/ujiansekolah/_style');
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-auto">
                    <?php $this->load->view('cbt/ujiansekolah/_kembali'); ?>
                </div>
                <div class="col">
                    <h1 class="mb-0"><?= html_escape($judul) ?></h1>
                    <small class="text-muted">
                        <?= html_escape($subjudul) ?> &middot;
                        Tahun <?= html_escape($tp_active->tahun) ?> &middot;
                        Semester <?= html_escape($smt_active->nama_smt) ?>
                    </small>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if ($berlangsung): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-lock mr-1"></i>
                    Ujian ini <b>sedang berlangsung</b>. Beberapa pengaturan (mapel, tanggal, durasi, tipe soal)
                    tidak bisa diubah. Yang masih bisa diubah: token dan pengawas.
                </div>
            <?php endif; ?>

            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <ul class="wizard-steps mb-0">
                        <li class="step aktif" data-step="1"><span class="num">1</span> Ujian apa &amp; kapan</li>
                        <li class="step" data-step="2"><span class="num">2</span> Pilih soal</li>
                        <li class="step" data-step="3"><span class="num">3</span> Pengawas, token &amp; opsi</li>
                    </ul>
                </div>
                <div class="card-body">

                    <div class="alert alert-danger d-none" id="form-error"></div>

                    <!-- ================= LANGKAH 1 ================= -->
                    <div class="wizard-pane aktif" id="pane-1">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="f-mapel">Mata Pelajaran <span class="text-danger">*</span></label>
                                    <select id="f-mapel" class="form-control select2" style="width:100%"
                                        <?= $berlangsung ? 'disabled' : '' ?>>
                                        <option value="">— Pilih mata pelajaran —</option>
                                        <?php foreach ($opsi['mapel'] as $idm => $nm): ?>
                                            <option value="<?= (int)$idm ?>" <?= $mapelTerpilih === (int)$idm ? 'selected' : '' ?>>
                                                <?= html_escape($nm) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="f-jenis">Jenis Ujian <span class="text-danger">*</span></label>
                                    <select id="f-jenis" class="form-control" <?= $berlangsung ? 'disabled' : '' ?>>
                                        <?php foreach ($jenis as $j): ?>
                                            <option value="<?= (int)$j->id_jenis ?>"
                                                <?= $jenisTerpilih === (int)$j->id_jenis ? 'selected' : '' ?>>
                                                <?= html_escape($j->nama_jenis) ?> (<?= html_escape($j->kode_jenis) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="f-tanggal">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" id="f-tanggal" class="form-control"
                                           value="<?= html_escape($nilai['tanggal']) ?>" <?= $berlangsung ? 'disabled' : '' ?>>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="f-durasi">Durasi (menit) <span class="text-danger">*</span></label>
                                    <input type="number" id="f-durasi" class="form-control" min="15" max="240"
                                           value="<?= (int)$nilai['durasi'] ?>" <?= $berlangsung ? 'disabled' : '' ?>>
                                    <small class="text-muted">15–240 menit.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kelas peserta</label>
                                    <div class="form-control bg-light" id="info-kelas" style="min-height:38px">
                                        <span class="text-muted">Mengikuti kumpulan soal</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border d-none" id="info-paralel"></div>
                    </div>

                    <!-- ================= LANGKAH 2 ================= -->
                    <div class="wizard-pane" id="pane-2">
                        <p class="text-muted mb-2">
                            Pilih kumpulan soal untuk mata pelajaran ini. Ujian boleh disimpan walau soalnya masih kosong.
                        </p>
                        <div id="daftar-bank"></div>
                        <div class="mt-2">
                            <a href="<?= base_url('cbtbanksoal?dari=tambah') ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Buat Kumpulan Soal
                            </a>
                        </div>
                        <div class="alert alert-light border mt-3 d-none" id="info-peserta"></div>
                    </div>

                    <!-- ================= LANGKAH 3 ================= -->
                    <div class="wizard-pane" id="pane-3">
                        <?php if ($is_admin): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Pengawas per ruang</h6>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="f-sama-guru" checked>
                                    <label class="custom-control-label" for="f-sama-guru">
                                        Guru yang sama untuk semua ruang
                                    </label>
                                </div>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-light">
                                    <tr>
                                        <th style="width:40%">Ruang</th>
                                        <th>Pengawas</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($ruang as $r): ?>
                                        <tr>
                                            <td class="align-middle">
                                                <b><?= html_escape($r->nama_ruang) ?></b>
                                                <small class="text-muted">(<?= html_escape($r->kode_ruang) ?>)</small>
                                                <small class="text-muted d-block">
                                                    kapasitas <?= (int)$r->kapasitas ?> siswa
                                                </small>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm pengawas-select"
                                                        data-ruang="<?= (int)$r->id_ruang ?>">
                                                    <option value="">— Belum dipilih —</option>
                                                    <?php foreach ($guru_list as $g): ?>
                                                        <option value="<?= (int)$g->id_guru ?>"
                                                            <?= isset($pengawas[(int)$r->id_ruang])
                                                                && (int)$pengawas[(int)$r->id_ruang] === (int)$g->id_guru
                                                                ? 'selected' : '' ?>>
                                                            <?= html_escape($g->nama_guru) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted small">
                                Ujian lain di tanggal yang sama memakai ruang yang sama, jadi pengawasnya disalin
                                otomatis agar orangnya tetap satu.
                            </p>
                        <?php else: ?>
                            <div class="alert alert-light border">
                                <i class="fas fa-info-circle mr-1"></i>
                                Pengawas diatur oleh admin. Sistem menyalin pengawas ruang yang sama dari ujian lain
                                pada tanggal yang sama.
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="f-token">Token ujian</label>
                                    <div class="input-group">
                                        <input type="text" id="f-token" class="form-control text-uppercase"
                                               maxlength="6" style="letter-spacing:3px;font-weight:600"
                                               value="<?= html_escape($nilai['token_ujian']) ?>"
                                               placeholder="6 karakter">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btn-acak">
                                                <i class="fas fa-sync"></i> Acak ulang
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">6 karakter huruf dan angka, berbeda untuk tiap ujian.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="d-block">Opsi ujian</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="f-gunakan-token"
                                        <?= $nilai['gunakan_token'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="f-gunakan-token">Gunakan token</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="f-acak-soal"
                                        <?= $nilai['acak_soal'] ? 'checked' : '' ?> <?= $berlangsung ? 'disabled' : '' ?>>
                                    <label class="custom-control-label" for="f-acak-soal">Acak soal</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="f-acak-opsi"
                                        <?= $nilai['acak_opsi'] ? 'checked' : '' ?> <?= $berlangsung ? 'disabled' : '' ?>>
                                    <label class="custom-control-label" for="f-acak-opsi">Acak jawaban</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="f-hasil-tampil"
                                        <?= $nilai['hasil_tampil'] ? 'checked' : '' ?> <?= $berlangsung ? 'disabled' : '' ?>>
                                    <label class="custom-control-label" for="f-hasil-tampil">Tampilkan hasil ke siswa</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="f-aktif"
                                        <?= $nilai['status'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="f-aktif">
                                        Aktifkan sekarang
                                        <small class="text-muted d-block" style="margin-left:-1.5rem">
                                            Jika tidak dicentang, ujian disimpan sebagai draf.
                                        </small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="f-catatan">Catatan (opsional)</label>
                            <input type="text" id="f-catatan" class="form-control" maxlength="250"
                                   value="<?= html_escape($nilai['catatan']) ?>"
                                   placeholder="mis. ujian susulan, hanya kelas 7A">
                        </div>

                        <div class="ringkas-ujian" id="ringkas-ujian">
                            <div class="baris"><b>Ringkasan belum lengkap.</b></div>
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary d-none" id="btn-kembali">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <div class="ml-auto">
                        <button type="button" class="btn btn-primary" id="btn-lanjut">
                            Lanjut <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success d-none" id="btn-draf">
                            <i class="fas fa-save"></i> Simpan sebagai Draf
                        </button>
                        <button type="button" class="btn btn-success d-none" id="btn-simpan">
                            <i class="fas fa-save"></i> Simpan &amp; Aktifkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var ID_JADWAL = <?= $id_jadwal ?>;
        var EDIT = <?= $edit ? 'true' : 'false' ?>;
        var BERLANGSUNG = <?= $berlangsung ? 'true' : 'false' ?>;
        var IS_ADMIN = <?= $is_admin ? 'true' : 'false' ?>;
        var MAPEL = <?= json_encode($opsi['mapel'], JSON_UNESCAPED_UNICODE) ?>;
        var PAIRS = <?= json_encode($opsi['pairs']) ?>;
        var KELAS = <?= json_encode($opsi['kelas'], JSON_UNESCAPED_UNICODE) ?>;
        var BANKS = <?= json_encode($banksJs, JSON_UNESCAPED_UNICODE) ?>;
        var JENIS = <?= json_encode($jenisJs, JSON_UNESCAPED_UNICODE) ?>;
        var BANK_TERPILIH = <?= (int)$nilai['id_bank'] ?>;
        var TOKEN_AWAL = <?= json_encode($nilai['token_ujian']) ?>;
        var LANGKAH_AWAL = <?= (int)$langkah ?>;

        var URL_SIMPAN = '<?= base_url('ujiansekolah/simpan') ?>';
        var URL_TANGGAL = '<?= base_url('ujiansekolah/tanggal') ?>';
        var URL_ACAK = '<?= base_url('ujiansekolah/acaktoken') ?>';
        var URL_SOAL = '<?= base_url('ujiansekolah/kumpulansoal') ?>';
        var URL_DAFTAR = '<?= base_url('ujiansekolah') ?>';

        var step = 1;
        var paralelLain = [];
        var banksTampil = [];

        function esc(s) {
            return $('<div>').text(s === null || s === undefined ? '' : s).html();
        }

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        function galat(msg) {
            $('#form-error').removeClass('d-none').text(msg);
            $('html, body').animate({scrollTop: 0}, 200);
        }

        function bersihkanGalat() {
            $('#form-error').addClass('d-none').text('');
        }

        function tampilStep(n) {
            step = n;
            $('.wizard-pane').removeClass('aktif');
            $('#pane-' + n).addClass('aktif');
            $('.wizard-steps .step').each(function () {
                var s = parseInt($(this).data('step'), 10);
                $(this).toggleClass('aktif', s === n).toggleClass('selesai', s < n);
            });
            $('#btn-kembali').toggleClass('d-none', n === 1);
            $('#btn-lanjut').toggleClass('d-none', n === 3);
            $('#btn-draf').toggleClass('d-none', n !== 3);
            $('#btn-simpan').toggleClass('d-none', n !== 3);
            if (n === 3) {
                ringkas();
            }
            $('html, body').animate({scrollTop: 0}, 150);
        }

        /* ---------- langkah 1 ---------- */

        function infoKelas() {
            var idMapel = parseInt($('#f-mapel').val(), 10) || 0;
            var bank = $.grep(BANKS, function (b) { return b.id_bank === BANK_TERPILIH; })[0];
            var teks = 'Mengikuti kumpulan soal';
            if (bank) {
                teks = bank.kelas ? ('Kelas ' + bank.kelas + ' · ' + bank.jumlah_siswa + ' siswa') : '—';
            } else if (idMapel && PAIRS[idMapel]) {
                var nama = $.map(PAIRS[idMapel], function (k) { return KELAS[k]; });
                teks = nama.length ? ('Kelas ' + nama.join(', ') + ' (perkiraan)') : '—';
            }
            $('#info-kelas').text(teks);
        }

        function muatParalel() {
            var tgl = $('#f-tanggal').val();
            if (!tgl) { return; }
            $.getJSON(URL_TANGGAL, {tanggal: tgl, id_jadwal: ID_JADWAL}, function (r) {
                if (!r.status) { return; }
                paralelLain = r.lain || [];
                renderParalel(tgl);
                isiPengawasDefault(r.pengawas || {});
            });
        }

        function renderParalel(tgl) {
            var $box = $('#info-paralel');
            if (paralelLain.length === 0) {
                $box.addClass('d-none').empty();
                return;
            }
            var html = '<b>' + esc(tgl) + ' sudah ada:</b><ul class="mb-2 pl-4">';
            $.each(paralelLain, function (i, u) {
                html += '<li>' + esc(u.judul) + (u.kelas ? ' · Kelas ' + esc(u.kelas) : '')
                    + ' · ' + u.durasi + ' menit</li>';
            });
            html += '</ul>Ujian ini akan berjalan <b>BERSAMAAN</b> pada tanggal tersebut (mulai Sesi 1).';
            $box.removeClass('d-none').html(html);
        }

        function isiPengawasDefault(map) {
            if (!IS_ADMIN) { return; }
            $('.pengawas-select').each(function () {
                var $s = $(this);
                if ($s.val()) { return; }
                var id = map[$s.data('ruang')];
                if (id) { $s.val(String(id)); }
            });
        }

        $('#f-tanggal').on('change', muatParalel);
        $('#f-mapel').on('change', function () {
            BANK_TERPILIH = 0;
            infoKelas();
            $('#info-peserta').addClass('d-none');
            renderBank();
        });
        $('#f-jenis').on('change', function () { if (step === 3) { ringkas(); } });

        /* ---------- langkah 2 ---------- */

        function renderBank() {
            var idMapel = parseInt($('#f-mapel').val(), 10) || 0;
            var $w = $('#daftar-bank').empty();
            if (!idMapel) {
                $w.html('<div class="alert alert-warning mb-0">Pilih mata pelajaran dulu pada langkah 1.</div>');
                banksTampil = [];
                return;
            }
            banksTampil = $.grep(BANKS, function (b) { return b.mapel_id === idMapel; });

            if (banksTampil.length === 0) {
                $w.html('<div class="alert alert-light border mb-0">Belum ada kumpulan soal untuk '
                    + esc(MAPEL[idMapel]) + '. Silakan buat kumpulan soal terlebih dahulu, '
                    + 'atau simpan ujian ini sebagai draf.</div>');
                return;
            }

            $.each(banksTampil, function (i, b) {
                var dipakaiLain = b.pakai && b.id_bank !== BANK_TERPILIH;
                var $k = $('<div class="kartu-bank"></div>')
                    .attr('data-id', b.id_bank)
                    .toggleClass('dipilih', b.id_bank === BANK_TERPILIH)
                    .toggleClass('opsi-lock', !!dipakaiLain);
                var html = '<div class="d-flex justify-content-between">'
                    + '<div><span class="judul">' + esc(b.nama || b.kode) + '</span>'
                    + '<span class="badge badge-light border ml-1">' + b.jml + ' soal</span></div>'
                    + (b.jml === 0 ? '<span class="badge badge-warning">kosong</span>' : '')
                    + '</div>'
                    + '<div class="meta">' + esc(b.mapel) + (b.kelas ? ' · Kelas ' + esc(b.kelas) : '')
                    + ' · PG ' + b.jml_pg + ' · Esai ' + b.jml_esai + ' · KKM ' + b.kkm + '</div>'
                    + '<div class="meta">Peserta: ' + b.jumlah_siswa + ' siswa</div>'
                    + (b.pakai ? '<div class="meta text-warning"><i class="fas fa-exclamation-triangle"></i> '
                        + 'Dipakai di: ' + esc(b.pakai) + '</div>' : '');

                if (dipakaiLain) {
                    html += '<div class="meta text-danger">Kumpulan soal ini sudah dipakai ujian lain. '
                        + 'Satu kumpulan soal hanya untuk satu ujian.</div>';
                }
                $k.html(html);

                if (!dipakaiLain) {
                    $k.on('click', function () {
                        BANK_TERPILIH = b.id_bank;
                        $('#daftar-bank .kartu-bank').removeClass('dipilih');
                        $(this).addClass('dipilih');
                        infoKelas();
                        infoPeserta(b);
                    });
                }
                $w.append($k);
            });

            var terpilih = $.grep(banksTampil, function (b) { return b.id_bank === BANK_TERPILIH; })[0];
            if (terpilih) { infoPeserta(terpilih); }
        }

        function infoPeserta(b) {
            if (!b) { return; }
            $('#info-peserta').removeClass('d-none').html('Peserta: <b>' + b.jumlah_siswa + ' siswa</b>'
                + (b.kelas ? ' · Kelas ' + esc(b.kelas) : ''));
        }

        /* ---------- langkah 3 ---------- */

        function acakToken(otomatis) {
            var tgl = $('#f-tanggal').val();
            $.post(URL_ACAK, (function () {
                var d = {tanggal: tgl, id_jadwal: ID_JADWAL};
                d[CSRF.name] = CSRF.hash;
                return d;
            })(), function (r) {
                if (r.status) { $('#f-token').val(r.token); }
            }, 'json').fail(function () {
                if (!otomatis) { toast(false, 'Gagal mengacak token.'); }
            });
        }

        $('#btn-acak').on('click', function () { acakToken(false); });

        $('#f-sama-guru').on('change', function () {
            if (!$(this).is(':checked')) { return; }
            var $pertama = $('.pengawas-select').first();
            if ($pertama.val()) {
                $('.pengawas-select').val($pertama.val());
            }
        });

        $('.pengawas-select').on('change', function () {
            if (!$('#f-sama-guru').is(':checked')) { return; }
            $('.pengawas-select').val($(this).val());
        });

        function namaMapel() {
            var id = parseInt($('#f-mapel').val(), 10) || 0;
            return MAPEL[id] || '(mata pelajaran belum dipilih)';
        }

        function kodeJenis() {
            var id = parseInt($('#f-jenis').val(), 10) || 0;
            for (var i = 0; i < JENIS.length; i++) {
                if (JENIS[i].id === id) { return JENIS[i].kode; }
            }
            return 'Ujian';
        }

        function ringkas() {
            var tgl = $('#f-tanggal').val();
            var durasi = parseInt($('#f-durasi').val(), 10) || 0;
            var bank = $.grep(BANKS, function (b) { return b.id_bank === BANK_TERPILIH; })[0];
            var token = $('#f-token').val();
            var pakaiToken = $('#f-gunakan-token').is(':checked');

            var judul = kodeJenis() + ' ' + namaMapel();
            if (bank && bank.kelas) { judul += ' · Kelas ' + bank.kelas; }
            judul += ' · ' + tgl + (paralelLain.length ? ' · BERSAMAAN' : '') + ' · ' + durasi + ' menit';

            var pengawas = 0;
            $('.pengawas-select').each(function () { if ($(this).val()) { pengawas++; } });

            var baris2 = 'Soal ' + (bank ? bank.jml : 0)
                + ' · Peserta ' + (bank ? bank.jumlah_siswa : 0)
                + ' · Ruang ' + $('.pengawas-select').length
                + ' · Pengawas ' + pengawas
                + ' · Token ' + (pakaiToken ? (token || '—') : 'tidak dipakai');

            $('#ringkas-ujian').html('<div class="baris"><b>' + esc(judul) + '</b></div>'
                + '<div class="baris text-muted">' + esc(baris2) + '</div>');
        }

        /* ---------- navigasi ---------- */

        function validasiStep1() {
            var idMapel = parseInt($('#f-mapel').val(), 10) || 0;
            if (!idMapel) { galat('Pilih mata pelajaran dulu.'); return false; }
            var tgl = $('#f-tanggal').val();
            if (!tgl) { galat('Tanggal ujian belum diisi dengan benar.'); return false; }
            var durasi = parseInt($('#f-durasi').val(), 10) || 0;
            if (durasi < 15 || durasi > 240) { galat('Durasi sebaiknya 15–240 menit.'); return false; }
            bersihkanGalat();
            return true;
        }

        $('#btn-lanjut').on('click', function () {
            if (step === 1) {
                if (!validasiStep1()) { return; }
                renderBank();
                tampilStep(2);
            } else if (step === 2) {
                tampilStep(3);
            }
        });

        $('#btn-kembali').on('click', function () {
            if (step > 1) { tampilStep(step - 1); }
        });

        /* ---------- simpan ---------- */

        function kumpulkan() {
            var pengawas = {};
            $('.pengawas-select').each(function () {
                var v = $(this).val();
                if (v) { pengawas[$(this).data('ruang')] = v; }
            });
            var data = {
                id_jadwal: ID_JADWAL,
                id_mapel: parseInt($('#f-mapel').val(), 10) || 0,
                id_jenis: parseInt($('#f-jenis').val(), 10) || 0,
                tanggal: $('#f-tanggal').val(),
                durasi_ujian: parseInt($('#f-durasi').val(), 10) || 0,
                id_bank: BANK_TERPILIH || 0,
                token_ujian: ($('#f-token').val() || '').toUpperCase(),
                gunakan_token: $('#f-gunakan-token').is(':checked') ? 1 : 0,
                acak_soal: $('#f-acak-soal').is(':checked') ? 1 : 0,
                acak_opsi: $('#f-acak-opsi').is(':checked') ? 1 : 0,
                hasil_tampil: $('#f-hasil-tampil').is(':checked') ? 1 : 0,
                catatan: $('#f-catatan').val()
            };
            $.each(pengawas, function (k, v) { data['pengawas[' + k + ']'] = v; });
            return data;
        }

        function kirim(aktif) {
            if (!validasiStep1()) { tampilStep(1); return; }
            if (aktif) {
                if (!BANK_TERPILIH) {
                    galat('Pilih kumpulan soal dulu. Ujian tanpa soal hanya bisa disimpan sebagai draf.');
                    tampilStep(2);
                    return;
                }
                if ($('#f-gunakan-token').is(':checked')) {
                    var t = ($('#f-token').val() || '').toUpperCase();
                    if (!/^[A-Z0-9]{6}$/.test(t)) {
                        galat('Token harus 6 karakter huruf dan angka.');
                        tampilStep(3);
                        return;
                    }
                }
            }
            bersihkanGalat();

            var data = kumpulkan();
            data.aktif = aktif ? 1 : 0;
            data[CSRF.name] = CSRF.hash;

            var $btn = aktif ? $('#btn-simpan') : $('#btn-draf');
            $btn.prop('disabled', true);
            $.ajax({
                type: 'POST', url: URL_SIMPAN, data: data, dataType: 'json',
                success: function (r) {
                    $btn.prop('disabled', false);
                    if (!r.status) {
                        galat(r.msg);
                        return;
                    }
                    toast(true, r.msg);
                    window.setTimeout(function () {
                        window.location.href = r.redirect || URL_DAFTAR;
                    }, 800);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    galat(msg);
                }
            });
        }

        $('#btn-draf').on('click', function () { kirim(false); });
        $('#btn-simpan').on('click', function () { kirim(true); });

        /* ---------- init ---------- */

        $(function () {
            if ($.fn.select2) {
                $('#f-mapel').select2({theme: 'bootstrap4', placeholder: '— Pilih mata pelajaran —'});
            }
            infoKelas();
            renderBank();
            muatParalel();
            if (!$('#f-token').val()) {
                acakToken(true);
            }
            if (EDIT && BANK_TERPILIH) {
                var b = $.grep(BANKS, function (x) { return x.id_bank === BANK_TERPILIH; })[0];
                if (b) { infoPeserta(b); }
            }
            // "Lengkapi Persiapan" bisa membuka wizard langsung di langkah yang kurang.
            if (LANGKAH_AWAL > 1) {
                tampilStep(LANGKAH_AWAL);
            }
        });
    })();
</script>
