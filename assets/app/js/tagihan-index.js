var __tg = document.getElementById('tagihan-data');
function meta(name) {
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
}
    $(function () {
        $('#tableTagihan').DataTable({
            'paging': true, 'lengthChange': true, 'searching': true,
            'ordering': true, 'info': true, 'autoWidth': false,
            'language': {'emptyTable': 'Belum ada tagihan. Klik "Buat Tagihan" untuk membuatnya.'}
        });
    });

    // Ubah diskon satu tagihan: isi sama dengan nominal tagihan = gratis
    function ubahDiskon(id, nominal, diskon, nama) {
        var teks = 'Diskon untuk ' + nama + ' (Rp)\n' +
                   'Nominal tagihan: Rp ' + nominal.toLocaleString('id-ID') + '\n' +
                   'Isi ' + nominal + ' untuk GRATIS, atau 0 untuk menghapus diskon.';
        var nilai = prompt(teks, diskon);
        if (nilai === null) return;
        nilai = String(nilai).replace(/\D/g, '');
        if (nilai === '') { alert('Isi angka diskon.'); return; }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = (window.base_url || '/') + 'tagihan/ubah_diskon/' + id;

        function tambah(nama, isi) {
            var el = document.createElement('input');
            el.type = 'hidden'; el.name = nama; el.value = isi;
            form.appendChild(el);
        }
        tambah('diskon', nilai);
        tambah('qs', __tg ? (__tg.dataset.qs || '') : '');
        tambah(meta('cbt-csrf-name') || 'csrf_token', meta('cbt-csrf-hash'));

        document.body.appendChild(form);
        form.submit();
    }
