function getToken(func) {
    if (!window.base_url) { return; }
    jQuery.ajax({
        url: window.base_url + 'dashboard/checktokenjadwal',
        type: 'GET',
        success: function (response) {
            window.globalToken = response.token;
            window.adaJadwalUjian = response.ada_jadwal;
            if (typeof window.reloadCOunt === 'undefined') { window.reloadCOunt = 0; }
            if (window.reloadCOunt > 0 && typeof showSuccessToast === 'function') {
                showSuccessToast('Token: <b>' + window.globalToken.token + '</b>');
            }
            window.reloadCOunt++;
            if (func && typeof func === 'function') { func(response); }
        },
        error: function (xhr) { console.log(xhr); }
    });
}