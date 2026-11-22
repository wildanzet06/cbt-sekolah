(function () {
    var meta = function (name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    };
    window.base_url = meta('cbt-base-url') || (function () {
        var s = document.currentScript;
        return s && s.src ? s.src.replace(/assets\/app\/js\/app-config\.js.*$/, '') : '/';
    })();
    window.tp_active = meta('cbt-tp');
    window.smt_active = meta('cbt-smt');
    window.id_tp_active = meta('cbt-id-tp');
    window.id_smt_active = meta('cbt-id-smt');
    window.globalToken = undefined;
    window.adaJadwalUjian = undefined;
})();