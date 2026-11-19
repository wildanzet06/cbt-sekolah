<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengaturan Ujian — 1 halaman, 5 tab (PRD 4.3 / 5.3).
 * Semua tab dirender sekaligus; pindah tab tidak memuat ulang halaman
 * dan tidak membuka tab browser.
 */
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
            <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div>
                    <b><i class="fas fa-magic mr-1"></i>Mode sederhana</b>
                    <div class="small text-muted">
                        Cukup atur <b>Peserta &amp; Ruang</b> lalu <b>Token</b>.
                        Sesi, pengawas, dan jenis ujian sudah otomatis (jam ke-1).
                        Matikan sakelar ini bila ingin mengatur manual.
                    </div>
                </div>
                <div class="custom-control custom-switch mt-2 mt-sm-0">
                    <input type="checkbox" class="custom-control-input" id="mode-sederhana" checked>
                    <label class="custom-control-label" for="mode-sederhana">Aktif</label>
                </div>
            </div>
            <div class="card card-default my-shadow mb-4">
                <div class="card-header p-0 pt-2 px-2">
                    <ul class="nav nav-tabs border-bottom-0" id="tab-pengaturan" role="tablist">
                        <?php foreach ($tabs as $kunci => $info): ?>
                            <?php $__lanjut = in_array($kunci, array('sesi', 'pengawas', 'jenis'), true); ?>
                            <li class="nav-item <?= $__lanjut ? 'tab-lanjutan' : '' ?>">
                                <a class="nav-link <?= $tab === $kunci ? 'active' : '' ?>"
                                   id="tab-link-<?= $kunci ?>" data-toggle="tab"
                                   href="#pane-<?= $kunci ?>" role="tab">
                                    <i class="<?= $info[1] ?> mr-1"></i><?= html_escape($info[0]) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="isi-pengaturan">

                        <div class="tab-pane fade <?= $tab === 'peserta' ? 'show active' : '' ?>"
                             id="pane-peserta" role="tabpanel">
                            <?php $this->load->view('cbt/ujiansekolah/_tab_peserta'); ?>
                        </div>

                        <div class="tab-pane fade <?= $tab === 'sesi' ? 'show active' : '' ?>"
                             id="pane-sesi" role="tabpanel">
                            <?php $this->load->view('cbt/ujiansekolah/_tab_sesi'); ?>
                        </div>

                        <div class="tab-pane fade <?= $tab === 'pengawas' ? 'show active' : '' ?>"
                             id="pane-pengawas" role="tabpanel">
                            <?php $this->load->view('cbt/ujiansekolah/_tab_pengawas'); ?>
                        </div>

                        <div class="tab-pane fade <?= $tab === 'jenis' ? 'show active' : '' ?>"
                             id="pane-jenis" role="tabpanel">
                            <?php $this->load->view('cbt/ujiansekolah/_tab_jenis'); ?>
                        </div>

                        <div class="tab-pane fade <?= $tab === 'token' ? 'show active' : '' ?>"
                             id="pane-token" role="tabpanel">
                            <?php $this->load->view('cbt/ujiansekolah/_tab_token'); ?>
                        </div>

                    </div>
                </div>
            </div>

            <p class="text-muted small mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Semua pengaturan teknis ujian ada di halaman ini. Tab berpindah tanpa memuat ulang
                halaman dan tanpa membuka tab browser baru.
            </p>
        </div>
    <script>
    (function () {
        var KUNCI = 'mode_sederhana_pengaturan';
        var $ul = $('#tab-pengaturan');
        var $sw = $('#mode-sederhana');
        if (!$ul.length || !$sw.length) { return; }

        function terapkan(sederhana) {
            $ul.toggleClass('mode-sederhana', sederhana);
            $sw.prop('checked', sederhana);
            if (sederhana && $ul.find('li.tab-lanjutan a.active').length) {
                $ul.find('a[href="#pane-peserta"]').tab('show');
            }
            try { localStorage.setItem(KUNCI, sederhana ? '1' : '0'); } catch (e) {}
        }

        var paksaRinci = <?= in_array($tab, array('sesi', 'pengawas', 'jenis'), true) ? 'true' : 'false' ?>;
        var awal = true;
        try {
            var simpan = localStorage.getItem(KUNCI);
            if (simpan !== null) { awal = simpan === '1'; }
        } catch (e) {}
        if (paksaRinci) { awal = false; }

        $sw.on('change', function () { terapkan(this.checked); });
        terapkan(awal);
    })();
</script>
<style>
    #tab-pengaturan.mode-sederhana .tab-lanjutan { display: none !important; }
</style></section>
</div>
