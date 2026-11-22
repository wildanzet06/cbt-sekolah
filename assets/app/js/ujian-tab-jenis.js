    (function () {
        var __cfg = document.getElementById('ujian-tab-jenis');
    var CSRF = __cfg ? JSON.parse(__cfg.dataset.csrf || '{}') : {};
        var URL_SIMPAN = __cfg ? (__cfg.dataset.urlSimpan || '') : '';
        var URL_HAPUS = __cfg ? (__cfg.dataset.urlHapus || '') : '';

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

        $('#pane-jenis .btn-simpan-jenis').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var $tr = $b.closest('tr');
            post(URL_SIMPAN, {
                id_jenis: $b.data('id'),
                kode_jenis: $tr.find('.jenis-kode').val(),
                nama_jenis: $tr.find('.jenis-nama').val()
            }, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    window.setTimeout(function () { window.location.reload(); }, 700);
                }
            });
        });

        $('#pane-jenis .btn-hapus-jenis').on('click', function () {
            var $b = $(this);
            if (parseInt($b.data('pakai'), 10) > 0) {
                toast(false, 'Jenis ini sudah dipakai ujian dan tidak bisa dihapus.');
                return;
            }
            if (!window.confirm('Hapus jenis "' + $b.data('nama') + '"?')) { return; }
            $b.prop('disabled', true);
            post(URL_HAPUS, {id_jenis: $b.data('id')}, function (r) {
                $b.prop('disabled', false);
                toast(r.status, r.msg);
                if (r.status) {
                    window.setTimeout(function () { window.location.reload(); }, 700);
                }
            });
        });
    })();
