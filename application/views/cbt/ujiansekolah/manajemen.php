<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Manajemen Ujian â€” satu halaman dengan 3 tab:
 * Daftar Ujian, Pengaturan Ujian, dan Hasil Ujian.
 * Tiap tab dimuat di iframe (embed=1) seperti halaman Hasil Ujian.
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
            <div class="card card-default my-shadow mb-4">
                <div class="card-header p-0 pt-2 px-2">
                    <ul class="nav nav-tabs border-bottom-0" id="tab-manajemen" role="tablist">
                        <?php foreach ($tabs as $kunci => $info): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $tab === $kunci ? 'active' : '' ?>"
                                   id="tab-link-<?= $kunci ?>" data-toggle="tab"
                                   href="#pane-<?= $kunci ?>" role="tab">
                                    <i class="<?= $info[1] ?> mr-1"></i><?= html_escape($info[0]) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="isi-manajemen">
                        <?php foreach ($tabs as $kunci => $info): ?>
                            <div class="tab-pane fade <?= $tab === $kunci ? 'show active' : '' ?>"
                                 id="pane-<?= $kunci ?>" role="tabpanel"
                                 data-src="<?= base_url($info[2] . '?embed=1&_=' . time()) ?>">
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
                                    <iframe class="manajemen-frame" id="frame-<?= $kunci ?>"
                                            title="<?= html_escape($info[0]) ?>"
                                            style="width:100%;height:82vh;border:0;display:block"
                                            loading="lazy"></iframe>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Semua tab berada dalam satu halaman; perpindahan tab tidak membuka tab browser baru.
            </p>
        </div>
    </section>
</div>

<script>
    (function () {
        // Tambahkan embed=1 pada tautan/form di dalam iframe agar tetap
        // tanpa sidebar saat berpindah halaman di dalam tab.
        function rapikan(frame) {
            try {
                var d = frame.contentDocument;
                if (!d) { return; }

                d.querySelectorAll('a[href]').forEach(function (a) {
                    var h = a.getAttribute('href');
                    if (!h || h.charAt(0) === '#' || /^(javascript:|mailto:|tel:)/i.test(h)) { return; }
                    try {
                        var u = new URL(h, frame.src);
                        if (u.origin !== location.origin) { return; }
                        u.searchParams.set('embed', '1');
                        a.setAttribute('href', u.toString());
                    } catch (e) {}
                });

                d.querySelectorAll('form').forEach(function (f) {
                    if (f.querySelector('input[name="embed"]')) { return; }
                    var i = d.createElement('input');
                    i.type = 'hidden';
                    i.name = 'embed';
                    i.value = '1';
                    f.appendChild(i);
                });
            } catch (e) {}
        }

        function muat($pane) {
            var frame = $pane.find('.manajemen-frame')[0];
            if (!frame) { return; }
            if (!frame.getAttribute('src')) {
                frame.onload = function () { rapikan(frame); };
                frame.setAttribute('src', $pane.data('src'));
            } else {
                rapikan(frame);
            }
        }

        muat($('#pane-' + '<?= $tab ?>'));
        $('#tab-manajemen a[data-toggle="tab"]').on('shown.bs.tab', function () {
            muat($($(this).attr('href')));
        });



        $('.btn-muat-ulang').on('click', function () {
            var $pane = $('#' + $(this).data('pane'));
            var frame = $pane.find('.manajemen-frame')[0];
            if (frame) {
                frame.setAttribute('src', $pane.data('src'));
            }
        });
    })();
</script>
