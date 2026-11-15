<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-cog text-primary mr-1"></i> Setting Pembayaran SPP</h1>
                    <small class="text-muted">Nominal SPP, instruksi transfer, dan rekening tujuan pembayaran</small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Nominal & instruksi -->
                <div class="col-12 col-xl-5">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Nominal SPP &amp; Instruksi</h3>
                        </div>
                        <?php echo form_open('pembayaran/update_setting_spp'); ?>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nominal SPP per bulan (Rp)</label>
                                    <input type="number" name="nominal_spp" class="form-control"
                                           value="<?= $setting_spp ? (int) $setting_spp->nominal_spp : 500000; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control"
                                           value="<?= $setting_spp ? html_escape($setting_spp->keterangan) : 'SPP Bulanan Default'; ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label>Instruksi tambahan untuk siswa</label>
                                    <textarea name="instruksi_transfer" class="form-control" rows="3"
                                              placeholder="mis. Cantumkan NIS pada berita transfer dan kirim bukti ke bagian keuangan."><?= $setting_spp && isset($setting_spp->instruksi_transfer) ? html_escape($setting_spp->instruksi_transfer) : ''; ?></textarea>
                                    <small class="form-text text-muted">
                                        Tampil di halaman pembayaran siswa, di bawah daftar rekening.
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                            </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>

                <!-- Rekening tujuan transfer -->
                <div class="col-12 col-xl-7">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Rekening Tujuan Transfer</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-outline-success" id="btn-tambah-rekening">
                                    <i class="fas fa-plus"></i> Tambah Rekening
                                </button>
                            </div>
                        </div>
                        <?php echo form_open('pembayaran/simpan_rekening'); ?>
                            <div class="card-body">
                                <p class="text-muted small">
                                    Rekening di sini yang ditampilkan ke siswa pada halaman pembayaran SPP
                                    (opsi transfer manual). Kosongkan semua bila hanya memakai pembayaran tunai/Midtrans.
                                </p>

                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="max-width:100%">
                                        <thead class="bg-light">
                                        <tr>
                                            <th style="width:22%">Bank</th>
                                            <th style="width:33%">Nomor Rekening</th>
                                            <th>Atas Nama</th>
                                            <th style="width:50px"></th>
                                        </tr>
                                        </thead>
                                        <tbody id="baris-rekening">
                                        <?php
                                        $daftar = (!empty($rekening)) ? $rekening : array();
                                        if (count($daftar) === 0) {
                                            $daftar = array((object) array('bank' => '', 'nomor' => '', 'atas_nama' => ''));
                                        }
                                        foreach ($daftar as $r):
                                            ?>
                                            <tr class="baris">
                                                <td>
                                                    <input type="text" name="bank[]" class="form-control form-control-sm"
                                                           maxlength="50" placeholder="BCA / BRI / Mandiri"
                                                           value="<?= html_escape($r->bank); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="nomor[]" class="form-control form-control-sm"
                                                           maxlength="50" placeholder="1234567890"
                                                           value="<?= html_escape($r->nomor); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="atas_nama[]" class="form-control form-control-sm"
                                                           maxlength="100" placeholder="a.n. Sekolah"
                                                           value="<?= html_escape($r->atas_nama); ?>">
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris"
                                                            title="Hapus baris">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-light border mt-3 mb-0 small">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Contoh tampilan ke siswa:
                                    <b><?= !empty($rekening) ? html_escape($rekening[0]->bank . ' ' . $rekening[0]->nomor . ' a.n. ' . $rekening[0]->atas_nama) : 'BCA 1234567890 a.n. Nama Sekolah'; ?></b>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan Rekening
                                </button>
                            </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var $body = $('#baris-rekening');

        function barisBaru() {
            return '<tr class="baris">' +
                '<td><input type="text" name="bank[]" class="form-control form-control-sm" maxlength="50" placeholder="BCA / BRI / Mandiri"></td>' +
                '<td><input type="text" name="nomor[]" class="form-control form-control-sm" maxlength="50" placeholder="1234567890"></td>' +
                '<td><input type="text" name="atas_nama[]" class="form-control form-control-sm" maxlength="100" placeholder="a.n. Sekolah"></td>' +
                '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris" title="Hapus baris"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
        }

        $('#btn-tambah-rekening').on('click', function () {
            $body.append(barisBaru());
        });

        $body.on('click', '.btn-hapus-baris', function () {
            $(this).closest('tr').remove();
            if ($body.find('tr').length === 0) {
                $body.append(barisBaru());
            }
        });
    })();
</script>
