<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hasil Ujian — 1 halaman, 4 tab (PRD 4.4 / 5.4).
 * Fase awal: tiap tab menampilkan halaman lama di dalam area konten
 * (iframe dimuat saat tab pertama kali dibuka). Tidak membuka tab browser.
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
                        &middot; <?= (int)$jumlah ?> ujian
                    </small>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header p-0 pt-2 px-2">
                    <ul class="nav nav-tabs border-bottom-0" id="tab-hasil" role="tablist">
                        <?php foreach ($tabs as $kunci => $info): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $tab === $kunci ? 'active' : '' ?>"
                                   id="tab-link-<?= $kunci ?>" data-toggle="tab"
                                   href="#pane-<?= $kunci ?>" role="tab"
                                   data-src="<?= base_url($info[2] . '?dari=hasil&embed=1') ?>">
                                    <i class="<?= $info[1] ?> mr-1"></i><?= html_escape($info[0]) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="isi-hasil">
                        <?php foreach ($tabs as $kunci => $info): ?>
                            <div class="tab-pane fade <?= $tab === $kunci ? 'show active' : '' ?>"
                                 id="pane-<?= $kunci ?>" role="tabpanel"
                                 data-src="<?= base_url($info[2] . '?dari=hasil&embed=1') ?>">
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <?= html_escape($info[0]) ?> ditampilkan di dalam halaman ini.
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-muat-ulang"
                                            data-pane="pane-<?= $kunci ?>">
                                        <i class="fas fa-sync"></i> Muat ulang
                                    </button>
                                </div>
                                <div class="p-0">
                                    <iframe class="hasil-frame" id="frame-<?= $kunci ?>" title="<?= html_escape($info[0]) ?>"
                                            style="width:100%;height:78vh;border:0;display:block"
                                            loading="lazy"></iframe>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Keempat tab berada dalam satu halaman; perpindahan tab tidak membuka tab browser baru.
            </p>
        </div>
    </section>
</div>

<script>
    (function () {
        function muat($pane) {
            var $frame = $pane.find('.hasil-frame');
            if (!$frame.length) { return; }
            if (!$frame.attr('src')) {
                $frame.attr('src', $pane.data('src'));
            }
        }

        // Muat tab yang aktif saat halaman dibuka, sisanya saat tab diklik.
        muat($('#pane-' + '<?= $tab ?>'));
        $('#tab-hasil a[data-toggle="tab"]').on('shown.bs.tab', function () {
            muat($($(this).attr('href')));
        });

        $('.btn-muat-ulang').on('click', function () {
            var $frame = $('#' + $(this).data('pane')).find('.hasil-frame');
            var src = $frame.attr('src');
            $frame.attr('src', src ? src : $('#pane-' + '<?= $tab ?>').data('src'));
        });
    })();
</script>
