(function () {
    var cfg = document.getElementById('user-config');
    window.user_id = cfg ? (cfg.dataset.id || '') : '';
})();