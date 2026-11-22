    (function () {
        var jenis = document.getElementById('jenis_id');
        var grup  = document.getElementById('grup_periode');
        var nom   = document.getElementById('nominal');
        var form  = document.getElementById('formBuat');
        if (!jenis) return;

        function sesuaikan() {
            var opt = jenis.options[jenis.selectedIndex];
            grup.style.display = (opt.getAttribute('data-tipe') === 'bulanan') ? '' : 'none';
            nom.value = opt.getAttribute('data-nominal') || '';
        }
        jenis.addEventListener('change', sesuaikan);
        sesuaikan();

        form.addEventListener('submit', function (e) {
            var opt  = jenis.options[jenis.selectedIndex];
            var teks = opt.text.replace(/\s+/g, ' ').trim();
            if (!confirm('Buat tagihan "' + teks + '" sekarang?')) {
                e.preventDefault();
            }
        });
    })();
