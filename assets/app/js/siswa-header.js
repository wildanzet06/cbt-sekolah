(function () {
    var cfg = document.getElementById('siswa-header-config');
    if (!cfg || !window.jQuery) { return; }
    var isLogin = localStorage.getItem('garudaCBT.login');
    var isCbtMode = isLogin ? isLogin === '1' : false;
    if (isCbtMode) {
        jQuery('a#url-back').attr('href', window.base_url + 'siswa/cbt');
        if (cfg.dataset.cbt === 'cbt') {
            jQuery('#back').addClass('d-none');
        }
    }
})();