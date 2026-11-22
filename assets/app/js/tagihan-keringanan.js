    // Kotak pencarian pada pilihan siswa (jika plugin select2 tersedia)
    $(function () {
        if ($.fn.select2) {
            $('#siswa_id').select2({theme: 'bootstrap4'});
        }
    });
