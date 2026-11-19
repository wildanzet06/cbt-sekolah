<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="content-wrapper">
    <section class="content pt-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card my-shadow">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-chess mr-2"></i>Pilih Ekstrakurikuler</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success py-2"><?= html_escape($this->session->flashdata('success')) ?></div>
                            <?php endif; ?>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger py-2"><?= html_escape($this->session->flashdata('error')) ?></div>
                            <?php endif; ?>

                            <p class="text-muted small">
                                Pilih <b>satu</b> ekstrakurikuler yang ingin Anda ikuti. Pilihan ini bebas dan
                                tidak ditentukan oleh kelas. Anda dapat menggantinya kapan saja.
                            </p>

                            <div class="mb-3">
                                <div class="font-weight-bold"><?= html_escape($siswa->nama) ?></div>
                                <div class="small text-muted">
                                    NIS <?= html_escape($siswa->nis) ?> &middot;
                                    TP <?= html_escape($tp_active->tahun) ?> &middot;
                                    Semester <?= html_escape($smt_active->nama_smt) ?>
                                </div>
                            </div>

                            <?php if (count($ekskul) === 0): ?>
                                <div class="alert alert-warning py-2">
                                    Belum ada ekstrakurikuler yang tersedia. Hubungi administrator.
                                </div>
                            <?php else: ?>
                                <?= form_open('siswa/simpanekstra', array('id' => 'form-ekstra')) ?>
                                <div class="form-group">
                                    <label for="id_ekstra">Ekstrakurikuler</label>
                                    <select name="id_ekstra" id="id_ekstra" class="form-control" required>
                                        <option value="">— Pilih ekstrakurikuler —</option>
                                        <?php foreach ($ekskul as $e): ?>
                                            <option value="<?= (int) $e->id_ekstra ?>"
                                                <?= (int) $pilihan === (int) $e->id_ekstra ? 'selected' : '' ?>>
                                                <?= html_escape($e->nama_ekstra) ?>
                                                <?= !empty($e->kode_ekstra) ? ' (' . html_escape($e->kode_ekstra) . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save mr-1"></i>Simpan Pilihan
                                </button>
                                <?= form_close() ?>
                            <?php endif; ?>

                            <div class="mt-3">
                                <a href="<?= base_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Beranda
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
