<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Buat & Kelola Ujian â€” tampilan ringkas gaya KARTINI CBT:
 * satu kartu formulir di atas, tabel daftar ujian di bawah.
 */
$bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
$fmtTgl = function ($tgl) use ($bulan) {
    $t = strtotime((string) $tgl);
    return $t ? date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t) : '-';
};
$badge = [
    'draf' => ['secondary', 'Draf'], 'belum_siap' => ['warning', 'Belum siap'],
    'siap' => ['info', 'Siap'], 'berlangsung' => ['success', 'Berlangsung'],
    'selesai' => ['primary', 'Selesai'], 'ditutup' => ['dark', 'Ditutup'],
];
?>
<div class="content-wrapper bg-white pt-4">
    <style>
        .kartu-ujian { border: 1px solid #e5e9f0; border-radius: 10px; background: #fff; }
        .kartu-ujian .form-control { border-radius: 6px; }
        .kartu-ujian label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .25rem; }
        .label-kecil { font-size: .75rem; color: #94a3b8; }
        .chip-kelas { display: inline-block; background: #0b2545; color: #fff; border-radius: 6px;
            padding: .3rem .7rem; margin: 0 .3rem .3rem 0; font-size: .8rem; }
        .btn-terbit { background: #0b2545; color: #fff; border-radius: 8px; font-weight: 600; }
        .btn-terbit:hover { background: #123862; color: #fff; }
        .tabel-ujian th { font-size: .75rem; text-transform: uppercase; letter-spacing: .02em; color: #64748b; }
        .tabel-ujian td { vertical-align: middle; }
        .btn-kelola { background: #0b2545; color: #fff; border-radius: 6px; font-size: .8rem; }
        .btn-kelola:hover { background: #123862; color: #fff; }
    </style>

    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-2">
                    <h1 class="mb-1">Buat &amp; Kelola Ujian</h1>
                    <p class="text-muted mb-0">
                        Buat ujian, atur kode ujian, dan pantau kesiapan dalam satu halaman.
                    </p>
                </div>
                <div class="mb-2">
                    <?php if ($is_admin): ?>
                        <a href="<?= base_url('ujiansekolah/pengaturan') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-cog mr-1"></i>Pengaturan
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('ujiansekolah/hasil') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chart-bar mr-1"></i>Hasil Ujian
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (!$ada_opsi): ?>
                <div class="alert alert-warning">
                    Anda belum diberi jadwal mengajar (mapel dan kelas) pada tahun pelajaran dan semester
                    aktif, sehingga belum bisa membuat ujian. Hubungi administrator.
                </div>
            <?php elseif (empty($banks)): ?>
                <div class="alert alert-light border">
                    <i class="fas fa-info-circle mr-1"></i>
                    Semua kumpulan soal sudah dipakai ujian. Buat kumpulan soal baru di
                    <a href="<?= base_url('cbtbanksoal') ?>">Kumpulan Soal</a>.
                </div>
            <?php else: ?>
                <div class="kartu-ujian p-3 p-md-4 mb-4">
                    <h5 class="font-weight-bold mb-3">Buat Ujian Baru</h5>

                    <div class="form-row">
                        <div class="col-md-5 mb-2">
                            <label for="f-judul">Judul Ujian</label>
                            <input type="text" class="form-control form-control-sm" id="f-judul"
                                   maxlength="200" placeholder="Contoh: Penilaian Harian Bab 3">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="f-bank">Mata Pelajaran / Bank Soal</label>
                            <select class="form-control form-control-sm" id="f-bank">
                                <?php foreach ($banks as $b): ?>
                                    <option value="<?= (int) $b->id_bank ?>"
                                            data-mapel="<?= (int) $b->bank_mapel_id ?>">
                                        <?= html_escape($b->nama_mapel . ' — ' . $b->bank_kode . ' (' . $b->label_kelas . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="f-token">Kode Ujian (Token Siswa)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="f-token" maxlength="10"
                                       placeholder="MTK9A26" style="text-transform:uppercase">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="f-acak"
                                            title="Acak kode">
                                        <i class="fas fa-dice"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-2">
                            <label for="f-durasi">Durasi (Menit)</label>
                            <input type="number" class="form-control form-control-sm" id="f-durasi"
                                   value="60" min="5" max="300">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="f-mulai">Jadwal Mulai</label>
                            <input type="date" class="form-control form-control-sm" id="f-mulai"
                                   value="<?= html_escape($hari_ini) ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="f-selesai">Jadwal Selesai <span class="label-kecil">(opsional)</span></label>
                            <input type="date" class="form-control form-control-sm" id="f-selesai">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="f-jenis">Jenis Ujian</label>
                            <select class="form-control form-control-sm" id="f-jenis">
                                <?php foreach ($jenis_ujian as $j): ?>
                                    <option value="<?= (int) $j->id_jenis ?>"><?= html_escape($j->nama_jenis) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label>Kelas Ujian</label>
                        <div id="f-kelas"><span class="text-muted small">—</span></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center">
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="f-acak-soal" checked>
                            <label class="custom-control-label" for="f-acak-soal">Acak Soal</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="f-acak-opsi" checked>
                            <label class="custom-control-label" for="f-acak-opsi">Acak Opsi Jawaban</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="f-hasil" checked>
                            <label class="custom-control-label" for="f-hasil">Tampilkan Nilai</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="f-aktif" checked>
                            <label class="custom-control-label" for="f-aktif">Aktifkan Ujian</label>
                        </div>
                        <div class="ml-auto mt-2 mt-sm-0">
                            <button type="button" class="btn btn-terbit" id="f-simpan">
                                <i class="fas fa-plus mr-1"></i>Terbitkan Ujian
                            </button>
                            <span class="small text-muted ml-2" id="f-pesan"></span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Peserta, ruang, dan sesi diatur otomatis. Untuk pengaturan rinci gunakan tombol
                        <a href="<?= base_url('ujiansekolah/baru') ?>">Mode lengkap</a>.
                    </small>
                </div>
            <?php endif; ?>

            <div class="kartu-ujian mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold mb-0">Daftar Ujian</h5>
                    <span class="label-kecil"><?= count($ujian) ?> ujian</span>
                </div>
                <div class="table-responsive">
                    <table class="table tabel-ujian mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="pl-3">Kode Ujian</th>
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
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada ujian. Buat ujian baru menggunakan formulir di atas.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($ujian as $u):
                            $st = $u->status_kode;
                            $bg = isset($badge[$st]) ? $badge[$st] : ['secondary', 'Draf'];
                            $judulUjian = !empty($u->judul)
                                ? $u->judul
                                : trim((string) $u->kode_jenis . ' ' . (string) $u->nama_mapel);
                            ?>
                            <tr>
                                <td class="pl-3 font-weight-bold">
                                    <?= html_escape($u->token_ujian ?: '—') ?>
                                </td>
                                <td>
                                    <div class="font-weight-bold"><?= html_escape($judulUjian ?: 'Ujian') ?></div>
                                    <div class="label-kecil"><?= html_escape($u->nama_mapel ?: 'Mapel belum dipilih') ?></div>
                                </td>
                                <td><?= html_escape($u->pembuat ?: 'Admin') ?></td>
                                <td><?= html_escape($u->label_kelas ?: '—') ?></td>
                                <td class="text-center">
                                    <?= (int) $u->kesiapan['jumlah_soal'] ?> soal
                                </td>
                                <td class="text-center"><?= (int) $u->durasi_ujian ?> mnt</td>
                                <td class="text-center">
                                    <span class="badge badge-<?= $bg[0] ?>"><?= html_escape($bg[1]) ?></span>
                                </td>
                                <td class="text-right pr-3 text-nowrap">
                                    <?php if (!empty($u->id_bank)): ?>
                                        <a href="<?= base_url('cbtbanksoal/detail/' . (int) $u->id_bank) ?>"
                                           class="btn btn-sm btn-kelola">
                                            <i class="fas fa-book-open mr-1"></i>Kelola Soal
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= base_url('ujiansekolah/edit/' . (int) $u->id_jadwal) ?>"
                                       class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus"
                                            data-id="<?= (int) $u->id_jadwal ?>"
                                            data-judul="<?= html_escape($judulUjian) ?>"
                                            title="Hapus ujian">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-muted small mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Kode ujian dipakai siswa saat mulai ujian. Jadwal selesai boleh dikosongkan.
            </p>
        </div>
    </section>
</div>

<script>
    (function () {
        var BANKS = <?= json_encode($banks_info) ?>;
        var BASE = '<?= base_url() ?>';
        var CSRF = '<?= $this->security->get_csrf_token_name() ?>';
        var HASH = '<?= $this->security->get_csrf_hash() ?>';

        function kelasTerpilih() {
            var id = $('#f-bank').val();
            var info = BANKS[id] || null;
            if (!info || !info.kelas) {
                $('#f-kelas').html('<span class="text-muted small">—</span>');
                return;
            }
            var chips = String(info.kelas).split(',');
            var html = '';
            chips.forEach(function (k) {
                k = $.trim(k);
                if (k) { html += '<span class="chip-kelas">' + $('<div>').text(k).html() + '</span>'; }
            });
            $('#f-kelas').html(html || '<span class="text-muted small">—</span>');
        }

        $('#f-bank').on('change', kelasTerpilih);
        kelasTerpilih();

        $('#f-acak').on('click', function () {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var out = '';
            for (var i = 0; i < 6; i++) {
                out += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            $('#f-token').val(out);
        });

        $('#f-simpan').on('click', function () {
            var idBank = $('#f-bank').val();
            var info = BANKS[idBank] || { mapel: 0 };
            var token = $.trim($('#f-token').val()).toUpperCase();

            var data = {
                id_jadwal: 0,
                judul: $.trim($('#f-judul').val()),
                id_bank: idBank,
                id_mapel: info.mapel,
                id_jenis: $('#f-jenis').val(),
                tanggal: $('#f-mulai').val(),
                tanggal_selesai: $('#f-selesai').val(),
                durasi_ujian: $('#f-durasi').val(),
                token_ujian: token,
                gunakan_token: token ? 1 : 0,
                acak_soal: $('#f-acak-soal').is(':checked') ? 1 : 0,
                acak_opsi: $('#f-acak-opsi').is(':checked') ? 1 : 0,
                hasil_tampil: $('#f-hasil').is(':checked') ? 1 : 0,
                aktif: $('#f-aktif').is(':checked') ? 1 : 0,
                catatan: ''
            };
            data[CSRF] = HASH;

            $('#f-simpan').prop('disabled', true);
            $('#f-pesan').text('Menyimpan...');

            $.post(BASE + 'ujiansekolah/simpan', data, function (res) {
                if (res && res.status) {
                    $('#f-pesan').text('Ujian diterbitkan.');
                    window.setTimeout(function () { window.location.reload(); }, 700);
                } else {
                    $('#f-pesan').text((res && res.msg) || 'Gagal menyimpan.');
                    $('#f-simpan').prop('disabled', false);
                }
            }, 'json').fail(function () {
                $('#f-pesan').text('Gagal menghubungi server.');
                $('#f-simpan').prop('disabled', false);
            });
        });

        $('.btn-hapus').on('click', function () {
            var $b = $(this);
            if (!window.confirm('Hapus ujian "' + $b.data('judul') + '"?\n\n'
                + 'Data peserta, soal siswa, dan nilai ujian ini ikut terhapus.')) { return; }
            var data = { id_jadwal: $b.data('id') };
            data[CSRF] = HASH;
            $b.prop('disabled', true);
            $.post(BASE + 'ujiansekolah/hapus', data, function (res) {
                if (res && res.status) {
                    window.location.reload();
                } else {
                    alert((res && res.msg) || 'Gagal menghapus.');
                    $b.prop('disabled', false);
                }
            }, 'json');
        });
    })();
</script>
