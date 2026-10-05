<div class="content-wrapper">
    <div class="content pt-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success'); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-circle mr-1"></i> <?= $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-key mr-2"></i>Ganti Password</h3>
                        </div>

                        <?= form_open('akun/password_simpan', array('id' => 'formPassword', 'autocomplete' => 'off')); ?>
                        <div class="card-body">

                            <div class="form-group">
                                <label for="password_lama">Password lama</label>
                                <div class="input-group">
                                    <input type="password" name="password_lama" id="password_lama"
                                           class="form-control form-control-lg" autocomplete="current-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-lihat" data-target="password_lama" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password_baru">Password baru</label>
                                <div class="input-group">
                                    <input type="password" name="password_baru" id="password_baru"
                                           class="form-control form-control-lg" autocomplete="new-password"
                                           minlength="<?= (int) $min_password; ?>" maxlength="<?= (int) $maks_password; ?>" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-lihat" data-target="password_baru" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted" id="infoPanjang">
                                    Minimal <?= (int) $min_password; ?> karakter.
                                </small>
                            </div>

                            <div class="form-group mb-0">
                                <label for="password_konfirmasi">Ulangi password baru</label>
                                <div class="input-group">
                                    <input type="password" name="password_konfirmasi" id="password_konfirmasi"
                                           class="form-control form-control-lg" autocomplete="new-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-lihat" data-target="password_konfirmasi" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text" id="infoSama"></small>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-save mr-1"></i> Simpan Password
                            </button>
                            <a href="<?= base_url('dashboard'); ?>" class="btn btn-link btn-block text-muted">Kembali ke Beranda</a>
                        </div>
                        <?= form_close(); ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var minPanjang = <?= (int) $min_password; ?>;
    var baru   = document.getElementById('password_baru');
    var konfir = document.getElementById('password_konfirmasi');
    var infoP  = document.getElementById('infoPanjang');
    var infoS  = document.getElementById('infoSama');
    var form   = document.getElementById('formPassword');

    // Tombol mata: lihat / sembunyikan isi kolom
    var tombol = document.querySelectorAll('.btn-lihat');
    for (var i = 0; i < tombol.length; i++) {
        tombol[i].addEventListener('click', function () {
            var el  = document.getElementById(this.getAttribute('data-target'));
            var ikon = this.querySelector('i');
            var tampil = el.type === 'password';
            el.type = tampil ? 'text' : 'password';
            ikon.className = tampil ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    }

    function cekPanjang() {
        var ok = baru.value.length >= minPanjang;
        infoP.className = 'form-text ' + (baru.value === '' ? 'text-muted' : (ok ? 'text-success' : 'text-danger'));
        infoP.textContent = ok && baru.value !== ''
            ? 'Panjang password sudah cukup.'
            : 'Minimal ' + minPanjang + ' karakter (sekarang ' + baru.value.length + ').';
    }
    function cekSama() {
        if (konfir.value === '') { infoS.textContent = ''; return; }
        var sama = baru.value === konfir.value;
        infoS.className = 'form-text ' + (sama ? 'text-success' : 'text-danger');
        infoS.textContent = sama ? 'Password sama.' : 'Password belum sama.';
    }
    baru.addEventListener('input', function () { cekPanjang(); cekSama(); });
    konfir.addEventListener('input', cekSama);

    form.addEventListener('submit', function (e) {
        if (baru.value.length < minPanjang) {
            e.preventDefault();
            alert('Password baru minimal ' + minPanjang + ' karakter.');
        } else if (baru.value !== konfir.value) {
            e.preventDefault();
            alert('Konfirmasi password baru belum sama.');
        }
    });
})();
</script>
