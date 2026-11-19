<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar Ujian ala KARTINI: satu form "Buat Ujian Baru" + tabel.
 * Tanpa wizard, tanpa tab browser baru.
 */
$this->load->view('cbt/ujiansekolah/_style');
$badge = [
    'draf' => ['secondary', 'Draf'], 'belum_siap' => ['warning', 'Belum siap'],
    'siap' => ['info', 'Siap'], 'berlangsung' => ['success', 'Berlangsung'],
    'selesai' => ['primary', 'Selesai'], 'ditutup' => ['dark', 'Ditutup'],
];
$__edit = isset($edit) ? $edit : null;
$__judul = $__edit && !empty($__edit->judul) ? $__edit->judul : '';
$__bank = $__edit ? (int) $__edit->id_bank : (count($banks) > 0 ? (int) $banks[0]->id_bank : 0);
$__jenis = $__edit ? (int) $__edit->id_jenis : (count($jenis_ujian) > 0 ? (int) $jenis_ujian[0]->id_jenis : 0);
$__tgl = $__edit ? substr((string) $__edit->tgl_mulai, 0, 10) : $hari_ini;
$__durasi = $__edit ? (int) $__edit->durasi_ujian : 60;
$__token = $__edit ? (string) $__edit->token_ujian : '';
$__acak1 = !$__edit || (int) $__edit->acak_soal === 1;
$__acak2 = !$__edit || (int) $__edit->acak_opsi === 1;
$__hasil = !$__edit || (int) $__edit->hasil_tampil === 1;
$__aktif = !$__edit || (int) $__edit->status === 1;
?>
<div class="content-wrapper bg-white pt-4">
    <style>
        .kj-card { border: 1px solid #e5e9f0; border-radius: 10px; background: #fff; }
        .kj-card label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .25rem; }
        .kj-chip { display: inline-block; background: #0b2545; color: #fff; border-radius: 6px;
            padding: .28rem .65rem; margin: 0 .3rem .3rem 0; font-size: .8rem; }
        .btn-terbit { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-terbit:hover { background: #123862; color: #fff; }
        .kj-tabel th { font-size: .73rem; text-transform: uppercase; color: #64748b; white-space: nowrap; }
        .kj-tabel td { vertical-align: middle; }
        .kj-aksi .btn { padding: .18rem .45rem; font-size: .72rem; }
    </style>

    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-2">
                    <h1 class="mb-1">Buat &amp; Kelola Ujian</h1>
                    <p class="text-muted mb-0">Satu halaman untuk membuat ujian, mengatur kode, dan mengelola soal.</p>
                </div>
                <div class="mb-2">
                    <a href="<?= base_url('ujiansekolah/pengaturan') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-cog mr-1"></i>Pengaturan lengkap
                    </a>
                    <a href="<?= base_url('ujiansekolah/hasil') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chart-bar mr-1"></i>Hasil
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (!empty($is_admin) && count($notif) > 0): ?>
                <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between">
                    <span><i class="fas fa-bell mr-1"></i><?= count($notif) ?> ujian baru dibuat guru minggu ini.</span>
                </div>
            <?php endif; ?>

            <?php if (!$ada_opsi): ?>
                <div class="alert alert-warning">
                    Anda belum diberi jadwal mengajar (mapel dan kelas) pada tahun pelajaran dan semester aktif.
                </div>
            <?php elseif (empty($banks) && !$__edit): ?>
                <div class="alert alert-light border">
                    <i class="fas fa-info-circle mr-1"></i>Semua kumpulan soal sudah dipakai ujian. Buat bank baru di
                    <a href="<?= base_url('cbtbanksoal') ?>">Kumpulan Soal</a>.
                </div>
            <?php else: ?>
                <div class="kj-card p-3 p-md-4 mb-4">
                    <h5 class="font-weight-bold mb-3">
                        <?= $__edit ? 'Edit Ujian' : 'Buat Ujian Baru' ?>
                    </h5>

                    <div class="form-row">
                        <div class="col-md-5 mb-2">
                            <label for="kj-judul">Judul Ujian</label>
                            <input type="text" class="form-control form-control-sm" id="kj-judul" maxlength="200"
                                   placeholder="Contoh: Penilaian Harian Bab 3"
                                   value="<?= html_escape($__judul) ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="kj-bank">Mata Pelajaran / Bank Soal</label>
                            <select class="form-control form-control-sm" id="kj-bank">
                                <?php
                                $__ada_opsi = false;
                                foreach ($banks as $b):
                                    if ((int) $b->id_bank === (int) $__bank) { $__ada_opsi = true; }
                                    ?>
                                    <option value="<?= (int) $b->id_bank ?>"
                                            data-mapel="<?= (int) $b->bank_mapel_id ?>"
                                            <?= (int) $b->id_bank === (int) $__bank ? 'selected' : '' ?>>
                                        <?= html_escape($b->nama_mapel . ' — ' . $b->bank_kode . ' (' . $b->label_kelas . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($__edit && !$__ada_opsi && isset($banks_info[(int) $__bank])): ?>
                                    <option value="<?= (int) $__bank ?>" data-mapel="<?= (int) $banks_info[(int) $__bank]['mapel'] ?>" selected>
                                        <?= html_escape($banks_info[(int) $__bank]['nama'] . ' — ' . $banks_info[(int) $__bank]['kode'] . ' (' . $banks_info[(int) $__bank]['kelas'] . ')') ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="kj-token">Kode Ujian (Token Siswa)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="kj-token" maxlength="6"
                                       style="text-transform:uppercase" placeholder="ABC234"
                                       value="<?= html_escape($__token) ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="kj-acak" title="Acak kode">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-2 mb-2">
                            <label for="kj-durasi">Durasi (Menit)</label>
                            <input type="number" class="form-control form-control-sm" id="kj-durasi"
                                   min="5" max="300" value="<?= (int) $__durasi ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="kj-tanggal">Tanggal Ujian</label>
                            <input type="date" class="form-control form-control-sm" id="kj-tanggal"
                                   value="<?= html_escape($__tgl) ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="kj-jenis">Kategori Ujian</label>
                            <select class="form-control form-control-sm" id="kj-jenis">
                                <?php foreach ($jenis_ujian as $j): ?>
                                    <option value="<?= (int) $j->id_jenis ?>" <?= (int) $j->id_jenis === (int) $__jenis ? 'selected' : '' ?>>
                                        <?= html_escape($j->nama_jenis) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label>Kelas Ujian</label>
                        <div id="kj-kelas"><span class="text-muted small">—</span></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center">
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="kj-acak-soal" <?= $__acak1 ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="kj-acak-soal">Acak Soal</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="kj-acak-opsi" <?= $__acak2 ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="kj-acak-opsi">Acak Opsi Jawaban</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="kj-hasil" <?= $__hasil ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="kj-hasil">Tampilkan Nilai</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="kj-aktif" <?= $__aktif ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="kj-aktif">Aktifkan Ujian</label>
                        </div>
                        <div class="ml-auto mt-2 mt-sm-0">
                            <button type="button" class="btn btn-terbit" id="kj-simpan">
                                <i class="fas fa-plus mr-1"></i><?= $__edit ? 'Simpan Perubahan' : 'Terbitkan Ujian' ?>
                            </button>
                            <?php if ($__edit): ?>
                                <a href="<?= base_url('ujiansekolah') ?>" class="btn btn-link btn-sm">Batal</a>
                            <?php endif; ?>
                            <span class="small text-muted ml-2" id="kj-pesan"></span>
                        </div>
                    </div>
                    <input type="hidden" id="kj-id" value="<?= $__edit ? (int) $__edit->id_jadwal : 0 ?>">
                </div>
            <?php endif; ?>

            <div class="kj-card mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold mb-0">Daftar Ujian</h5>
                    <span class="small text-muted"><?= count($ujian) ?> ujian</span>
                </div>
                <div class="table-responsive">
                    <table class="table kj-tabel mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="pl-3">Kode</th>
                            <th>Judul &amp; Mapel</th>
                            <th>Pembuat</th>
                            <th>Kelas</th>
                            <th class="text-center">Soal</th>
                            <th class="text-center">Durasi</th>
                            <th class="text-center">Status</th>
                            <th class="text-right pr-3">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($ujian) === 0): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada ujian.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ujian as $u):
                            $st = isset($badge[$u->status_kode]) ? $badge[$u->status_kode] : ['secondary', 'Draf'];
                            $judulU = !empty($u->judul) ? $u->judul : trim((string) $u->kode_jenis . ' ' . (string) $u->nama_mapel);
                            ?>
                            <tr>
                                <td class="pl-3 font-weight-bold"><?= html_escape($u->token_ujian ?: '—') ?></td>
                                <td>
                                    <div class="font-weight-bold"><?= html_escape($judulU ?: 'Ujian') ?></div>
                                    <div class="small text-muted"><?= html_escape($u->nama_mapel ?: '-') ?></div>
                                </td>
                                <td class="small"><?= html_escape($u->pembuat ?: 'Admin') ?></td>
                                <td class="small"><?= html_escape($u->label_kelas ?: '—') ?></td>
                                <td class="text-center"><?= (int) $u->kesiapan['jumlah_soal'] ?></td>
                                <td class="text-center"><?= (int) $u->durasi_ujian ?> mnt</td>
                                <td class="text-center"><span class="badge badge-<?= $st[0] ?>"><?= html_escape($st[1]) ?></span></td>
                                <td class="text-right pr-3 kj-aksi text-nowrap">
                                    <?php if (!empty($u->id_bank)): ?>
                                        <a href="<?= base_url('ujiansekolah?kelola=' . (int) $u->id_jadwal) ?>"
                                           class="btn btn-sm btn-dark"><i class="fas fa-book-open"></i> Kelola Soal</a>
                                    <?php endif; ?>
                                    <a href="<?= base_url('ujiansekolah/pantau/' . (int) $u->id_jadwal) ?>"
                                       class="btn btn-sm btn-outline-primary">Pantau</a>
                                    <a href="<?= base_url('cbtcetak/absenPeserta?jadwal=' . (int) $u->id_jadwal) ?>"
                                       class="btn btn-sm btn-outline-secondary" target="_self">Cetak Absen</a>
                                    <a href="<?= base_url('cbtcetak/beritaAcara?jadwal=' . (int) $u->id_jadwal) ?>"
                                       class="btn btn-sm btn-outline-secondary" target="_self">Berita Acara</a>
                                    <a href="<?= base_url('ujiansekolah?edit=' . (int) $u->id_jadwal) ?>"
                                       class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-ujian"
                                            data-id="<?= (int) $u->id_jadwal ?>"
                                            data-judul="<?= html_escape($judulU) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($kelola_bank)): ?>
                <div class="kj-card mb-4" id="panel-kelola">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="font-weight-bold mb-0"><i class="fas fa-book-open mr-1"></i>Kelola Soal</h5>
                        <a href="<?= base_url('ujiansekolah') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i>Tutup
                        </a>
                    </div>
                    <iframe src="<?= base_url('cbtbanksoal/buatsoal/' . (int) $kelola_bank . '?tab=1&embed=1') ?>"
                            title="Kelola Soal" style="width:100%;height:78vh;border:0;display:block"
                            loading="lazy"></iframe>
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
        var INFO = <?= json_encode($banks_info) ?>;

        function kelas() {
            var info = INFO[$('#kj-bank').val()] || null;
            if (!info || !info.kelas) {
                $('#kj-kelas').html('<span class="text-muted small">—</span>');
                return;
            }
            var html = '';
            String(info.kelas).split(',').forEach(function (k) {
                k = $.trim(k);
                if (k) { html += '<span class="kj-chip">' + $('<div>').text(k).html() + '</span>'; }
            });
            $('#kj-kelas').html(html || '<span class="text-muted small">—</span>');
        }

        $('#kj-bank').on('change', kelas);
        kelas();

        $('#kj-acak').on('click', function () {
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            var out = '';
            for (var i = 0; i < 6; i++) { out += chars.charAt(Math.floor(Math.random() * chars.length)); }
            $('#kj-token').val(out);
        });

        $('#kj-simpan').on('click', function () {
            var idBank = $('#kj-bank').val();
            var info = INFO[idBank] || { mapel: 0 };
            var token = $.trim($('#kj-token').val()).toUpperCase();

            var data = {
                id_jadwal: $('#kj-id').val(),
                judul: $.trim($('#kj-judul').val()),
                id_bank: idBank,
                id_mapel: info.mapel,
                id_jenis: $('#kj-jenis').val(),
                tanggal: $('#kj-tanggal').val(),
                durasi_ujian: $('#kj-durasi').val(),
                token_ujian: token,
                gunakan_token: token ? 1 : 0,
                acak_soal: $('#kj-acak-soal').is(':checked') ? 1 : 0,
                acak_opsi: $('#kj-acak-opsi').is(':checked') ? 1 : 0,
                hasil_tampil: $('#kj-hasil').is(':checked') ? 1 : 0,
                aktif: $('#kj-aktif').is(':checked') ? 1 : 0,
                catatan: ''
            };
            data[CSRF] = HASH;

            $('#kj-simpan').prop('disabled', true);
            $('#kj-pesan').text('Menyimpan...');

            $.post(BASE + 'ujiansekolah/simpan', data, function (res) {
                if (res && res.status) {
                    $('#kj-pesan').text('Tersimpan.');
                    window.location.href = BASE + 'ujiansekolah';
                } else {
                    $('#kj-pesan').text((res && res.msg) || 'Gagal menyimpan.');
                    $('#kj-simpan').prop('disabled', false);
                }
            }, 'json').fail(function () {
                $('#kj-pesan').text('Gagal menghubungi server.');
                $('#kj-simpan').prop('disabled', false);
            });
        });

        $('.btn-hapus-ujian').on('click', function () {
            var $b = $(this);
            if (!window.confirm('Hapus ujian "' + $b.data('judul') + '"?')) { return; }
            var data = { id_jadwal: $b.data('id') };
            data[CSRF] = HASH;
            $b.prop('disabled', true);
            $.post(BASE + 'ujiansekolah/hapus', data, function (res) {
                if (res && res.status) {
                    window.location.href = BASE + 'ujiansekolah';
                } else {
                    alert((res && res.msg) || 'Gagal menghapus.');
                    $b.prop('disabled', false);
                }
            }, 'json');
        });
    })();
</script>
