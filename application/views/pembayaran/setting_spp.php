<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-6 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fa fa-cog text-primary mr-1"></i> Pengaturan Nominal SPP</h1>
                    <small class="text-muted">Nominal SPP bulanan yang dipakai pembuatan tagihan</small>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Kembali
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

            <div class="card card-outline card-primary" style="max-width:640px">
                <div class="card-header">
                    <h3 class="card-title mb-0">Form Setting SPP Bulanan</h3>
                </div>
                <?php echo form_open('pembayaran/update_setting_spp'); ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nominal SPP (Rp)</label>
                            <input type="number" name="nominal_spp" class="form-control" value="<?= $setting_spp ? (int) $setting_spp->nominal_spp : 500000; ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" value="<?= $setting_spp ? html_escape($setting_spp->keterangan) : 'SPP Bulanan Default'; ?>">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </section>
</div>
