<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar Ujian — hub (PRD 5.1)
 * Satu halaman untuk melihat, membuat, dan menjalankan ujian.
 */

$bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];

$fmtTgl = function ($tgl) use ($bulan) {
    $t = strtotime((string)$tgl);
    if (!$t) {
        return '-';
    }
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
};

$judulUjian = function ($u) {
    $kode = !empty($u->kode_jenis) ? $u->kode_jenis : 'Ujian';
    $mapel = !empty($u->nama_mapel) ? $u->nama_mapel : '(kumpulan soal belum dipilih)';
    return trim($kode . ' ' . $mapel);
};

$badge = [
    'draf'        => ['secondary', 'far fa-file-alt'],
    'belum_siap'  => ['warning', 'fas fa-exclamation-triangle'],
    'siap'        => ['info', 'fas fa-check-circle'],
    'berlangsung' => ['success', 'fas fa-play-circle'],
    'selesai'     => ['primary', 'fas fa-flag-checkered'],
    'ditutup'     => ['dark', 'fas fa-lock']
];

// Kelompokkan jumlah ujian per tanggal untuk label "bersamaan".
$perTanggal = [];
foreach ($ujian as $u) {
    $t = substr((string)$u->tgl_mulai, 0, 10);
    $perTanggal[$t] = isset($perTanggal[$t]) ? $perTanggal[$t] + 1 : 1;
}
$this->load->view('cbt/ujiansekolah/_style');
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1 class="mb-0"><?= html_escape($judul) ?></h1>
                    <small class="text-muted">
                        <?= html_escape($subjudul) ?> &middot;
                        Tahun <?= html_escape($tp_active->tahun) ?> &middot;
                        Semester <?= html_escape($smt_active->nama_smt) ?>
                    </small>
                </div>
                <div class="col-sm-4 text-right">
                    <?php if ($ada_opsi): ?>
                        <a href="<?= base_url('ujiansekolah/baru') ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Buat Ujian Baru
                        </a>
                    <?php endif; ?>
                    <?php if ($is_admin): ?>
                        <a href="<?= base_url('ujiansekolah/pengaturan') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-cog"></i> Pengaturan
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('ujiansekolah/hasil') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chart-bar"></i> Hasil
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (!$ada_opsi): ?>
                <div class="alert alert-warning">
                    Anda belum diberi jadwal mengajar (mapel dan kelas) pada tahun pelajaran dan semester aktif,
                    sehingga belum bisa membuat ujian. Hubungi administrator.
                </div>
            <?php endif; ?>

            <?php if ($is_admin && count($notif) > 0): ?>
                <div class="alert alert-info d-flex align-items-center justify-content-between" id="banner-notif">
                    <div>
                        <i class="fas fa-bell mr-1"></i>
                        <b><?= count($notif) ?></b> ujian baru dibuat guru minggu ini:
                        <?php
                        $nama = [];
                        foreach (array_slice($notif, 0, 3) as $n) {
                            $nama[] = $judulUjian($n) . ' (' . html_escape($n->pembuat ?: 'Guru') . ')';
                        }
                        echo html_escape(implode(', ', $nama));
                        if (count($notif) > 3) {
                            echo ' dan ' . (count($notif) - 3) . ' lainnya';
                        }
                        ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-lihat-notif">Lihat</button>
                </div>
            <?php endif; ?>

            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="filter-ujian">
                        <li class="nav-item"><a class="nav-link active" href="#" data-filter="semua">Semua</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-filter="hari-ini">Hari Ini</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-filter="akan-datang">Akan Datang</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-filter="selesai">Selesai</a></li>
                    </ul>
                    <div class="card-tools" style="max-width:260px">
                        <div class="input-group input-group-sm">
                            <input type="search" class="form-control" id="cari-ujian"
                                   placeholder="Cari mapel, kelas, token...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">

                    <?php if (count($ujian) === 0): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="far fa-calendar-alt fa-3x mb-3"></i>
                            <p class="mb-3">Belum ada ujian pada tahun pelajaran dan semester ini.</p>
                            <?php if ($ada_opsi): ?>
                                <a href="<?= base_url('ujiansekolah/baru') ?>" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Buat Ujian Baru
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>

                        <div id="daftar-ujian">
                            <?php foreach ($ujian as $u):
                                $tgl = substr((string)$u->tgl_mulai, 0, 10);
                                $k = $u->kesiapan;
                                $st = $u->status_kode;
                                $bg = isset($badge[$st]) ? $badge[$st] : ['secondary', 'far fa-circle'];
                                $isHariIni = ($tgl === $hari_ini);
                                $isAkan = ($tgl > $hari_ini);
                                $isSelesai = ($tgl < $hari_ini || in_array($st, ['selesai', 'ditutup'], true));
                                $bersamaan = isset($perTanggal[$tgl]) && $perTanggal[$tgl] > 1;
                                $judul = $judulUjian($u);
                                $kelasLabel = !empty($u->label_kelas) ? $u->label_kelas : null;
                                $oleh = $u->pembuat ? $u->pembuat : 'Admin';

                                // "Lengkapi Persiapan" membuka wizard di langkah yang kurang (PRD 5.1).
                                $langkahKurang = 3;
                                foreach (['soal'] as $chipKurang) {
                                    if (empty($k['chip'][$chipKurang])) {
                                        $langkahKurang = 2;
                                    }
                                }
                                ?>
                                <div class="ujian-item border-bottom px-3 py-3"
                                     data-status="<?= html_escape($st) ?>"
                                     data-tgl="<?= html_escape($tgl) ?>"
                                     data-hari-ini="<?= $isHariIni ? 1 : 0 ?>"
                                     data-akan="<?= $isAkan ? 1 : 0 ?>"
                                     data-selesai="<?= $isSelesai ? 1 : 0 ?>"
                                     data-kelola="<?= ($u->milik_saya || $is_admin) ? 1 : 0 ?>"
                                     data-cari="<?= html_escape(strtolower($judul . ' ' . $kelasLabel . ' '
                                         . (string)$u->token_ujian . ' ' . $oleh . ' ' . $tgl)) ?>">

                                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                                        <div class="pr-2">
                                            <h6 class="mb-1">
                                                <i class="fas fa-caret-right text-muted mr-1"></i>
                                                <b><?= html_escape($judul) ?></b>
                                                <?php if ($kelasLabel): ?>
                                                    <span class="text-muted font-weight-normal">&mdash; Kelas <?= html_escape($kelasLabel) ?></span>
                                                <?php endif; ?>
                                                <?php if ($bersamaan): ?>
                                                    <span class="badge badge-light border ml-1" data-toggle="tooltip"
                                                          title="Ada ujian lain pada tanggal yang sama. Ujian berjalan bersamaan mulai Sesi 1.">
                                                        bersamaan
                                                    </span>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="text-muted small">
                                                <?= html_escape($fmtTgl($tgl)) ?> &middot;
                                                <?= (int)$u->durasi_ujian ?> menit &middot;
                                                Sesi 1 &middot;
                                                oleh <?= html_escape($oleh) ?>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-<?= $bg[0] ?> px-2 py-1">
                                                <i class="<?= $bg[1] ?> mr-1"></i><?= html_escape($u->status_label) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ujian-chips mt-2">
                                        <?php
                                        $jmlDeklarasi = !empty($u->jml_soal) ? max((int)$u->jml_soal, (int)$k['jumlah_soal']) : 0;
                                        $chips = [
                                            ['soal', 'Soal ' . (int)$k['jumlah_soal']
                                                . ($jmlDeklarasi ? '/' . $jmlDeklarasi : '')],
                                            ['peserta', 'Peserta ' . (int)$k['terdaftar'] . '/' . (int)$k['total_siswa']],
                                            ['ruang', 'Ruang ' . (int)$k['ruang_pakai'] . '/' . (int)$k['ruang_total']],
                                            ['pengawas', 'Pengawas ' . (int)$k['ruang_pengawas'] . '/'
                                                . max(1, (int)$k['ruang_pakai'])],
                                            ['token', 'Token ' . (!empty($u->token_ujian) ? $u->token_ujian : '—')]
                                        ];
                                        foreach ($chips as $c):
                                            $ok = !empty($k['chip'][$c[0]]);
                                            ?>
                                            <span class="chip <?= $ok ? 'chip-ok' : 'chip-no' ?>">
                                                <i class="fas <?= $ok ? 'fa-check' : 'fa-times' ?>"></i>
                                                <?= html_escape($c[1]) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="ujian-aksi mt-2">
                                        <?php if (in_array($st, ['draf', 'belum_siap'], true)): ?>
                                            <?php if ((int)$u->status === 1): ?>
                                                <button type="button" class="btn btn-sm btn-danger btn-aktif"
                                                        data-id="<?= (int)$u->id_jadwal ?>" data-aktif="0">
                                                    <i class="fas fa-stop"></i> Tutup
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= base_url('ujiansekolah/edit/' . (int)$u->id_jadwal
                                                . '?langkah=' . (int)$langkahKurang) ?>"
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-tools"></i> Lengkapi Persiapan
                                            </a>
                                            <?php if ($k['siap']): ?>
                                                <button type="button" class="btn btn-sm btn-success btn-aktif"
                                                        data-id="<?= (int)$u->id_jadwal ?>" data-aktif="1">
                                                    <i class="fas fa-play"></i> Buka Ujian
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($st === 'berlangsung'): ?>
                                            <a href="<?= base_url('ujiansekolah/pantau/' . (int)$u->id_jadwal) ?>"
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-desktop"></i> Pantau
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-aktif"
                                                    data-id="<?= (int)$u->id_jadwal ?>" data-aktif="0">
                                                <i class="fas fa-stop"></i> Tutup
                                            </button>
                                        <?php elseif ($st === 'selesai'): ?>
                                            <a href="<?= base_url('ujiansekolah/hasil?tab=nilai') ?>"
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-file-alt"></i> Hasil
                                            </a>
                                            <a href="<?= base_url('ujiansekolah/pantau/' . (int)$u->id_jadwal) ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-desktop"></i> Pantau
                                            </a>
                                        <?php elseif ($st === 'siap'): ?>
                                            <button type="button" class="btn btn-sm btn-success btn-aktif"
                                                    data-id="<?= (int)$u->id_jadwal ?>" data-aktif="1">
                                                <i class="fas fa-play"></i> Buka Ujian
                                            </button>
                                            <a href="<?= base_url('ujiansekolah/pantau/' . (int)$u->id_jadwal) ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-desktop"></i> Pantau
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-success btn-aktif"
                                                    data-id="<?= (int)$u->id_jadwal ?>" data-aktif="1">
                                                <i class="fas fa-play"></i> Buka Ujian
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($u->milik_saya || $is_admin): ?>
                                            <a href="<?= base_url('ujiansekolah/edit/' . (int)$u->id_jadwal) ?>"
                                               class="btn btn-sm btn-outline-secondary" data-toggle="tooltip"
                                               title="Ubah pengaturan ujian ini">
                                                <i class="fas fa-cog"></i> Pengaturan
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('cbtcetak?dari=daftar') ?>"
                                           class="btn btn-sm btn-outline-secondary"
                                           data-toggle="tooltip" title="Cetak kartu & daftar hadir">
                                            <i class="fas fa-print"></i> Kartu
                                        </a>
                                        <?php if ($u->milik_saya || $is_admin): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus float-right"
                                                    data-id="<?= (int)$u->id_jadwal ?>"
                                                    data-judul="<?= html_escape($judul) ?>"
                                                    data-toggle="tooltip" title="Hapus ujian">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="kosong-filter" class="p-4 text-center text-muted d-none">
                            Tidak ada ujian pada filter ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_admin): ?>
                <div class="card card-default my-shadow mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="fas fa-key mr-1"></i> Token aktif hari ini</h6>
                        <div class="card-tools">
                            <a href="<?= base_url('ujiansekolah/pengaturan?tab=token') ?>"
                               class="btn btn-sm btn-outline-secondary">
                                Atur Token
                            </a>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <?php if (count($token_aktif) === 0): ?>
                            <span class="text-muted">Belum ada token aktif hari ini.</span>
                        <?php else: ?>
                            <?php $i = 0; foreach ($token_aktif as $t): $i++; ?>
                                <?= $i > 1 ? ' &middot; ' : '' ?>
                                <span class="badge badge-primary px-2 py-1" style="font-size:.95rem;letter-spacing:1px">
                                    <?= html_escape($t->token_ujian) ?>
                                </span>
                                <span class="text-muted small">
                                    (<?= html_escape($t->nama_mapel ?: ($t->kode_jenis ?: 'Ujian')) ?>)
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var URL_AKTIF = '<?= base_url('ujiansekolah/aktif') ?>';
        var URL_HAPUS = '<?= base_url('ujiansekolah/hapus') ?>';

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        function post(url, data, cb) {
            data = data || {};
            data[CSRF.name] = CSRF.hash;
            $.ajax({
                type: 'POST', url: url, data: data, dataType: 'json',
                success: function (r) { cb(r); },
                error: function (xhr) {
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    cb({status: false, msg: msg});
                }
            });
        }

        // ---- filter & pencarian -------------------------------------
        var filterAktif = 'semua';

        function terapkanFilter() {
            var q = ($('#cari-ujian').val() || '').toLowerCase();
            var tampil = 0;
            $('.ujian-item').each(function () {
                var $e = $(this);
                var cocokFilter = true;
                if (filterAktif === 'hari-ini') { cocokFilter = $e.data('hari-ini') === 1; }
                else if (filterAktif === 'akan-datang') { cocokFilter = $e.data('akan') === 1; }
                else if (filterAktif === 'selesai') { cocokFilter = $e.data('selesai') === 1; }

                var cocokCari = q === '' || ($e.data('cari') || '').indexOf(q) !== -1;
                var tampilItem = cocokFilter && cocokCari;
                $e.toggleClass('d-none', !tampilItem);
                if (tampilItem) { tampil++; }
            });
            $('#kosong-filter').toggleClass('d-none', tampil !== 0);
        }

        $('#filter-ujian .nav-link').on('click', function (e) {
            e.preventDefault();
            $('#filter-ujian .nav-link').removeClass('active');
            $(this).addClass('active');
            filterAktif = $(this).data('filter');
            terapkanFilter();
        });

        $('#cari-ujian').on('input', terapkanFilter);
        terapkanFilter();

        // ---- banner guru --------------------------------------------
        $('#btn-lihat-notif').on('click', function () {
            $('#banner-notif .fa-bell').removeClass('fa-bell').addClass('fa-bell-slash');
            window.scrollTo(0, 0);
            $('#cari-ujian').val('').trigger('input');
            $(this).prop('disabled', true).text('Ditandai');
        });

        // ---- buka / tutup -------------------------------------------
        $('.btn-aktif').on('click', function () {
            var $b = $(this);
            var aktif = parseInt($b.data('aktif'), 10) === 1;
            if (!aktif && !window.confirm('Tutup ujian ini? Siswa tidak bisa lagi masuk ke ujian.')) { return; }
            $b.prop('disabled', true);
            post(URL_AKTIF, {id_jadwal: $b.data('id'), aktif: aktif ? 1 : 0}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) { window.setTimeout(function () { window.location.reload(); }, 700); }
            });
        });

        // ---- hapus ---------------------------------------------------
        $('.btn-hapus').on('click', function () {
            var $b = $(this);
            if (!window.confirm('Hapus ujian "' + $b.data('judul') + '"?\n\n'
                + 'Data peserta, soal siswa, dan nilai ujian ini ikut terhapus. '
                + 'Ujian yang sudah bernilai tidak bisa dihapus.')) { return; }
            $b.prop('disabled', true);
            post(URL_HAPUS, {id_jadwal: $b.data('id')}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) { window.setTimeout(function () { window.location.reload(); }, 700); }
            });
        });

        $('[data-toggle="tooltip"]').tooltip();
    })();
</script>
