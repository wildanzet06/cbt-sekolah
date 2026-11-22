(function () {
    var form = document.getElementById('formPresensi');
    if (!form) return;

    var ringkasan = document.getElementById('ringkasan');
    var label = {H: 'Hadir', I: 'Izin', S: 'Sakit', A: 'Alpa'};

    function hitung() {
        var n = {H: 0, I: 0, S: 0, A: 0};
        var terpilih = form.querySelectorAll('input[type=radio]:checked');
        for (var i = 0; i < terpilih.length; i++) n[terpilih[i].value]++;
        var teks = [];
        for (var k in label) teks.push(label[k] + ' <b>' + n[k] + '</b>');
        ringkasan.innerHTML = teks.join(' &nbsp;&bull;&nbsp; ');
    }

    form.addEventListener('change', hitung);
    hitung();

    document.getElementById('btnHadirSemua').addEventListener('click', function () {
        var h = form.querySelectorAll('input[type=radio][value=H]');
        for (var i = 0; i < h.length; i++) h[i].checked = true;
        hitung();
    });

    var hapus = document.getElementById('btnHapus');
    if (hapus) {
        hapus.addEventListener('click', function () {
            if (confirm('Hapus seluruh presensi untuk sesi ini? Tindakan ini tidak bisa dibatalkan.')) {
                document.getElementById('formHapus').submit();
            }
        });
    }
})();
