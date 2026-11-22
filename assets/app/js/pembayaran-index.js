jQuery(function () {
    if (jQuery.fn.DataTable && jQuery('#tableSPP').length) {
        jQuery('#tableSPP').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
                emptyTable: 'Belum ada transaksi'
            }
        });
    }
});

function tolakPembayaran(id) {
    var alasan = prompt('Alasan penolakan (misalnya: bukti transfer tidak sesuai):');
    if (alasan === null) { return; }
    if (alasan.trim() === '') {
        alert('Alasan penolakan harus diisi.');
        return;
    }

    var base = window.base_url || '/';
    var meta = function (name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    };

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = base + 'pembayaran/tolak/' + id;

    var f1 = document.createElement('input');
    f1.type = 'hidden';
    f1.name = 'alasan';
    f1.value = alasan.trim();
    form.appendChild(f1);

    var f2 = document.createElement('input');
    f2.type = 'hidden';
    f2.name = meta('cbt-csrf-name') || 'csrf_token';
    f2.value = meta('cbt-csrf-hash');
    form.appendChild(f2);

    document.body.appendChild(form);
    form.submit();
}