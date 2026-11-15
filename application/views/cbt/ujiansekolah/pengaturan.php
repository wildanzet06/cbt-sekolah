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
            <div class="card card-default my-shadow mb-4">
                <div class="card-header p-0 pt-2 px-2">
                    <ul class="nav nav-tabs border-bottom-0" id="tab-pengaturan" role="tablist">
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
    </section>
</div>
