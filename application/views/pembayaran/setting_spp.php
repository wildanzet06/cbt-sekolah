<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-cog"></i> Pengaturan Nominal SPP</h1>
    </section>
    <section class="content">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?= $this->session->flashdata('success'); ?>
            </div>
        <?php endif; ?>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Form Setting SPP Bulanan</h3>
            </div>
            <?php echo form_open('pembayaran/update_setting_spp'); ?>
                <div class="box-body">
                    <div class="form-group">
                        <label>Nominal SPP (Rp)</label>
                        <input type="number" name="nominal_spp" class="form-control" value="<?= $setting->nominal_spp ?? 500000; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="<?= $setting->keterangan ?? 'SPP Bulanan Default'; ?>">
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </section>
</div>