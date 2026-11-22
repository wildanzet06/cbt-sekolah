(function () {
    var script = document.currentScript;
    var base_url = script && script.src
        ? script.src.replace(/assets\/app\/js\/auth-login\.js.*$/, '')
        : '/';

    var img = ["wall1.jpg", "wall2.png", "wall3.jpg"];

    if (window.jQuery && jQuery.backstretch) {
        jQuery.backstretch([
            base_url + 'assets/img/' + img[0],
            base_url + 'assets/img/' + img[1],
            base_url + 'assets/img/' + img[2]
        ], {
            fade: 1000,
            duration: 10000
        });
    }

    jQuery(document).ready(function () {
        if (jQuery('#myCarousel').length) {
            jQuery('#myCarousel').carousel({ interval: 1000 * 2, pause: 'none' });
        }

        jQuery('form#login input').on('change', function () {
            jQuery(this).parent().removeClass('has-error');
            jQuery(this).next().next().text('');
        });

        jQuery('form#login').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var infobox = jQuery('#infoMessage');
            infobox.addClass('info-box align-items-center justify-content-center bg-gradient-info').text('Checking...');

            var btnsubmit = jQuery('#submit');
            btnsubmit.attr('disabled', 'disabled').val('Wait...');

            var arrForm = jQuery(this).serializeArray();
            var cbtOnly = arrForm.find(function (obj) { return obj.name === 'cbt-only'; });
            localStorage.setItem('garudaCBT.login', cbtOnly !== undefined ? '1' : '0');

            jQuery.ajax({
                url: jQuery(this).attr('action'),
                type: 'POST',
                data: jQuery(this).serialize(),
                success: function (data) {
                    infobox.removeAttr('class').text('');
                    btnsubmit.removeAttr('disabled').val('Login');
                    if (data.status) {
                        infobox.addClass('info-box align-items-center justify-content-center bg-gradient-success').text('Login Sukses');
                        var isLogin = localStorage.getItem('garudaCBT.login');
                        var isCbtMode = isLogin ? isLogin === '1' : false;
                        var go = base_url + data.url;
                        if (isCbtMode && data.role === 'siswa') {
                            go = base_url + 'siswa/cbt';
                        }
                        window.location.href = go;
                    } else {
                        if (data.invalid) {
                            jQuery.each(data.invalid, function (key, val) {
                                jQuery('[name="' + key + '"').parent().addClass('has-error');
                                jQuery('[name="' + key + '"').next().next().text(val);
                                if (val === '') {
                                    jQuery('[name="' + key + '"').parent().removeClass('has-error');
                                    jQuery('[name="' + key + '"').next().next().text('');
                                }
                            });
                        }
                        if (data.failed) {
                            infobox.addClass('info-box align-items-center justify-content-center bg-gradient-danger').text(data.failed);
                        }
                    }
                }
            });
        });

        jQuery('#toggle-password').on('click', function () {
            var type = jQuery('#password').attr('type') === 'password' ? 'text' : 'password';
            jQuery('#password').attr('type', type);
            jQuery(this).toggleClass('fa-eye-slash fa-eye');
        });
    });
})();
