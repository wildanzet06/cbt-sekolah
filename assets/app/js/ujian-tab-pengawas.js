    (function () {
        var __cfg = document.getElementById('ujian-tab-pengawas');
    var CSRF = __cfg ? JSON.parse(__cfg.dataset.csrf || '{}') : {};
        var URL_SIMPAN = __cfg ? (__cfg.dataset.urlSimpan || '') : '';

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        $('#pane-pengawas .btn-salin').on('click', function () {
            var $card = $(this).closest('.pengawas-card');
            var $first = $card.find('.pengawas-select').first();
            var nilai = $first.val();
            if (!nilai) {
                toast(false, 'Pilih pengawas pada ruang pertama dulu.');
                return;
            }
            $card.find('.pengawas-select').val(nilai);
        });

        $('#pane-pengawas .btn-simpan-pengawas').on('click', function () {
            var $card = $(this).closest('.pengawas-card');
            var data = {tanggal: $card.data('tanggal')};
            $card.find('.pengawas-select').each(function () {
                if ($(this).val()) {
                    data['pengawas[' + $(this).data('ruang') + ']'] = $(this).val();
                }
            });
            data[CSRF.name] = CSRF.hash;

            var $b = $(this).prop('disabled', true);
            $.ajax({
                type: 'POST', url: URL_SIMPAN, data: data, dataType: 'json',
                success: function (r) {
                    $b.prop('disabled', false);
                    toast(r.status, r.msg);
                },
                error: function (xhr) {
                    $b.prop('disabled', false);
                    var msg = 'Terjadi kesalahan pada server.';
                    try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                    toast(false, msg);
                }
            });
        });
    })();
