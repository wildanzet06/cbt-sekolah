    (function () {
        var __cfg = document.getElementById('ujian-tab-peserta');
    var CSRF = __cfg ? JSON.parse(__cfg.dataset.csrf || '{}') : {};
        var URL_BAGI = __cfg ? (__cfg.dataset.urlBagi || '') : '';
        var URL_RUANG = __cfg ? (__cfg.dataset.urlRuang || '') : '';

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

        $('#pane-peserta .btn-lihat').on('click', function () {
            $('#siswa-' + $(this).data('kelas')).toggleClass('d-none');
        });

        $('#btn-bagi').on('click', function () {
            if (!window.confirm('Pembagian peserta akan diganti. Lanjut?')) { return; }
            var $b = $(this).prop('disabled', true);
            post(URL_BAGI, {semua_ruang: $('#f-semua-ruang').is(':checked') ? 1 : 0}, function (r) {
                $b.prop('disabled', false);
                toast(r.status !== undefined ? r.status : r.ok, r.msg);
                if (r.ok || r.status) {
                    window.setTimeout(function () {
                        window.location.href = r.redirect || window.location.href;
                    }, 900);
                }
            });
        });

        $('#pane-peserta .btn-simpan-kapasitas').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var id = $b.data('id');
            var $input = $('#pane-peserta .kapasitas-input[data-id="' + id + '"]');
            post(URL_RUANG, {id_ruang: id, kapasitas: $input.val()}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    window.setTimeout(function () { window.location.reload(); }, 700);
                }
            });
        });
    })();
