    (function () {
        var __cfg = document.getElementById('ujian-tab-token');
    var CSRF = __cfg ? JSON.parse(__cfg.dataset.csrf || '{}') : {};
        var URL_UJIAN = __cfg ? (__cfg.dataset.urlUjian || '') : '';
        var URL_ACAK = __cfg ? (__cfg.dataset.urlAcak || '') : '';
        var URL_GLOBAL = __cfg ? (__cfg.dataset.urlGlobal || '') : '';

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

        $('#btn-simpan-global').on('click', function () {
            var $b = $(this).prop('disabled', true);
            post(URL_GLOBAL, {token: $('#f-token-global').val()}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
            });
        });

        $('#pane-token .btn-acak').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('id');
            post(URL_ACAK, {id_jadwal: id}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    $('#pane-token tr[data-jadwal="' + id + '"] .token-input').val(r.token);
                }
            });
        });

        $('#pane-token .btn-simpan-token').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('id');
            var $input = $('#pane-token tr[data-jadwal="' + id + '"] .token-input');
            post(URL_UJIAN, {id_jadwal: id, token: $input.val()}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    $input.val(r.token);
                }
            });
        });
    })();
