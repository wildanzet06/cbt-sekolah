    (function () {
        var __cfg = document.getElementById('ujian-tab-sesi');
    var CSRF = __cfg ? JSON.parse(__cfg.dataset.csrf || '{}') : {};
        var URL_SESI = __cfg ? (__cfg.dataset.urlSesi || '') : '';
        var URL_JAM = __cfg ? (__cfg.dataset.urlJam || '') : '';

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        function post(url, data, cb) {
            data[CSRF.name] = CSRF.hash;
            $.ajax({
                type: 'POST', url: url, data: data, dataType: 'json',
                success: cb,
                error: function (xhr) {
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    cb({status: false, msg: msg});
                }
            });
        }

        $('#tampil-sesi-lain').on('change', function () {
            $('#sesi-lain').toggleClass('d-none', !$(this).is(':checked'));
        });

        $('#pane-sesi .btn-simpan-sesi').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('sesi');
            var data = {id_sesi: id};
            $('#pane-sesi .sesi-input[data-sesi="' + id + '"]').each(function () {
                data[$(this).data('field')] = $(this).val();
            });
            data.aktif = $('#aktif-' + id).is(':checked') ? 1 : 0;

            post(URL_SESI, data, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    window.setTimeout(function () { window.location.reload(); }, 700);
                }
            });
        });

        $('#btn-simpan-jam').on('click', function () {
            var data = {};
            $('#pane-sesi .jam-input').each(function () {
                data['jam[' + $(this).data('jadwal') + ']'] = $(this).val();
            });
            var $b = $(this).prop('disabled', true);
            post(URL_JAM, data, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
            });
        });
    })();
