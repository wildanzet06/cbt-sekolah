document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalPelanggaran');
    var form    = document.getElementById('formPelanggaran');
    var select  = document.getElementById('f_siswa');
    var fieldKelas = document.getElementById('f_kelas');
    var __pl = document.getElementById('pelanggaran-data');
var dataEdit = __pl ? JSON.parse(__pl.dataset.edit || '[]') : [];
    var siswaMap = __pl ? JSON.parse(__pl.dataset.siswa || '{}') : {};

    // Pencarian siswa: ketik nama atau NIS (selain menggulir daftar).
    if (window.jQuery && jQuery.fn.select2 && select) {
        jQuery(select).select2({
            dropdownParent: jQuery('#modalPelanggaran'),
            placeholder: 'Ketik nama atau NIS siswa...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () { return 'Siswa tidak ditemukan'; },
                searching: function () { return 'Mencari...'; }
            }
        });
        // select2 memicu event lewat jQuery, jadi pasang juga di sini
        jQuery(select).on('change', perbaruiKelas);
    }

    // Segarkan tampilan select2 setelah nilai diubah lewat kode.
    function sinkronSelect() {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(select).trigger('change.select2');
        }
        perbaruiKelas();
    }

    // Jenis pelanggaran: bisa dipilih dari daftar ATAU diketik sendiri (tag).
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#f_jenis').select2({
            dropdownParent: jQuery('#modalPelanggaran'),
            placeholder: 'Ketik atau pilih jenis pelanggaran',
            tags: true,
            allowClear: true,
            width: '100%',
            maximumInputLength: 150,
            createTag: function (params) {
                var term = jQuery.trim(params.term);
                if (term === '') return null;
                return { id: term, text: term, newTag: true };
            },
            language: {
                noResults: function () { return 'Tekan Enter untuk memakai teks ini'; },
                searching: function () { return 'Mencari...'; }
            }
        });
    }

    // Setel nilai jenis pelanggaran (mendukung teks baru di luar daftar).
    function setJenis(nilai) {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#f_jenis').val(nilai === '' ? null : nilai).trigger('change');
        } else {
            document.getElementById('f_jenis').value = nilai;
        }
    }

    function perbaruiKelas() {
        var info = siswaMap[select.value];
        fieldKelas.value = info && info.nama_kelas ? info.nama_kelas : '-';
    }
    if (select) {
        select.addEventListener('change', perbaruiKelas);
    }

    if (window.jQuery && jQuery.fn.DataTable && document.getElementById('tablePelanggaran')) {
        jQuery('#tablePelanggaran').DataTable({
            'pageLength': 25,
            'order': [[1, 'desc']],
            'columnDefs': [
                {'targets': 0, 'orderable': false},
                {'targets': 9, 'orderable': false}
            ],
            'language': {
                'search': 'Cari:',
                'lengthMenu': 'Tampilkan _MENU_ data',
                'info': 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                'infoEmpty': 'Tidak ada data',
                'zeroRecords': 'Data tidak ditemukan',
                'paginate': {'previous': 'Sebelumnya', 'next': 'Berikutnya'},
                'emptyTable': 'Belum ada catatan pelanggaran.'
            }
        });
    }

    var btnTambah = document.getElementById('btnTambah');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            form.action = base_url + 'pelanggaran/simpan';
            form.reset();
            document.getElementById('f_id').value = '0';
            select.value = '';
            sinkronSelect();
            fieldKelas.value = '-';
            document.getElementById('f_tanggal').value = __pl ? (__pl.dataset.tanggal || '') : '';
            document.getElementById('f_poin').value = '';
            setJenis('');
            document.getElementById('f_ket').value = '';
            document.getElementById('judulModalPelanggaran').textContent = 'Tambah Pelanggaran';
        });
    }

    var tombolUbah = document.querySelectorAll('.btn-edit');
    for (var i = 0; i < tombolUbah.length; i++) {
        tombolUbah[i].addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            var d = dataEdit[id];
            if (!d) return;

            form.action = base_url + 'pelanggaran/ubah/' + id;
            document.getElementById('f_id').value = id;
            select.value = String(d.id_siswa);
            sinkronSelect();
            document.getElementById('f_tanggal').value = d.tanggal;
            setJenis(d.jenis);
            document.getElementById('f_poin').value = d.poin;
            document.getElementById('f_ket').value = d.keterangan;
            perbaruiKelas();
            document.getElementById('judulModalPelanggaran').textContent = 'Ubah Pelanggaran';

            if (window.jQuery) {
                jQuery(modalEl).modal('show');
            }
        });
    }
});
