jQuery(function () {
    jQuery('.avatar').each(function () {
        jQuery(this).on('error', function () {
            var src = jQuery(this).attr('src').replace('profiles', 'foto_siswa');
            jQuery(this).attr('src', src);
            jQuery(this).on('error', function () {
                jQuery(this).attr('src', window.base_url + 'assets/img/siswa.png');
            });
        });
    });
});