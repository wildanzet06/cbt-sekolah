/**
 * Filter otomatis.
 *
 * Form yang diberi atribut `data-auto-filter` langsung dikirim begitu salah satu
 * pilihan (select / tanggal / teks) diubah — jadi tidak perlu tombol "Tampilkan".
 *
 * Input teks/angka dikirim saat Enter ditekan atau saat fokus keluar,
 * supaya tidak mengirim permintaan di setiap ketukan.
 */
(function () {
    'use strict';

    function kirim(form) {
        if (form.dataset.autoSending === '1') return;
        form.dataset.autoSending = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    function pasang(form) {
        if (form.dataset.autoFilterSiap === '1') return;
        form.dataset.autoFilterSiap = '1';

        var elemen = form.querySelectorAll('select, input[type="date"], input[type="month"], input[type="week"], input[type="number"], input[type="text"]');
        Array.prototype.forEach.call(elemen, function (el) {
            if (el.dataset.autoOff !== undefined) return;

            var perluEnter = (el.type === 'text' || el.type === 'number');

            el.addEventListener('change', function () {
                if (el.type === 'text' || el.type === 'number') {
                    // ketikan: tunggu fokus keluar / Enter, jangan tiap ketukan
                    return;
                }
                kirim(form);
            });

            if (perluEnter) {
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        kirim(form);
                    }
                });
                el.addEventListener('blur', function () {
                    if (el.value !== el.defaultValue) {
                        kirim(form);
                    }
                });
            }
        });
    }

    function mulai() {
        var forms = document.querySelectorAll('form[data-auto-filter]');
        Array.prototype.forEach.call(forms, pasang);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulai);
    } else {
        mulai();
    }
})();
