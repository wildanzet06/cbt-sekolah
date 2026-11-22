    $(function () {
        if ($('#tableRekap').length) {
            $('#tableRekap').DataTable({
                'paging': false, 'searching': true, 'ordering': true, 'info': false, 'autoWidth': false,
                'language': {'emptyTable': 'Tidak ada data.'}
            });
        }
    });
