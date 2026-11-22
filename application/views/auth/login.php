<div class="container pt-4">
    <div class="info-box bg-transparent shadow-none">
        <?php
        $logo_app = $setting->logo_kanan == null ? base_url() . 'assets/img/favicon.png' : base_url() . $setting->logo_kanan;
        ?>
        <img src="<?= $logo_app ?>" width="60" height="60">
        <div class="info-box-content ml-2" style="text-shadow: 1px 1px 2px #000000">
            <h5 class="info-box-text text-white text-wrap"><b><?= $setting->nama_aplikasi ?></b></h5>
            <span class="info-box-text text-white"><?= $setting->alamat ?></span>
        </div>
    </div>
    <div class="container-fluid h-100">
        <div class="row h-100 justify-content-center">
            <div class="login-box">
                <div class="login-logo text-white" style="text-shadow: 1px 1px 2px #000000">
                    <b>S</b>elamat <b>D</b>atang
                </div>

                <div class="card form-signin">
                    <div class="card-body">
                        <p class="login-box-msg">L O G I N</p>
                        <div id="infoMessage"><?php echo $message; ?></div>

                        <?= form_open("auth/cek_login", array('id' => 'login')); ?>
                        <div class="input-group mb-3 has-feedback">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <span class="fas fa-user"></span>
                                </div>
                            </div>
                            <?= form_input($identity, '', 'required'); ?>
                            <div class="help-block"></div>
                        </div>
                        <div class="input-group mb-3 has-feedback">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                            <?= form_input($password, '', 'required'); ?>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span id="toggle-password" class="fas fa-eye-slash" style="cursor: pointer"></span>
                                </div>
                            </div>
                            <div class="help-block"></div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="icheck-cyan">
                                    <input type='checkbox' id="cbt-only" name='cbt-only'
                                           value='1'/>
                                    <label for="cbt-only">Login CBT</label>
                                </div>
                            </div>
                            <!-- /.col -->
                            <div class="col-4">
                                <?= form_submit('submit', lang('login_submit_btn'), array('id' => 'submit', 'class' => 'btn btn-primary btn-block btn-flat')); ?>
                            </div>
                            <!-- /.col -->
                        </div>
                        <?= form_close(); ?>
                        <div class="text-center small text-muted mt-3">
                            Lupa password? Hubungi administrator atau wali kelas untuk reset.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="<?= base_url() ?>/assets/app/js/jquery.backstretch.js"></script>
<script src="<?= base_url() ?>assets/app/js/auth-login.js"></script>
