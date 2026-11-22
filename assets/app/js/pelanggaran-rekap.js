document.addEventListener('DOMContentLoaded', function () {
    var __pr = document.getElementById('pelanggaran-rekap-data');
var riwayatQuery = __pr ? (__pr.dataset.q || '') : '';

    if (window.jQuery && jQuery.fn.DataTable) {
        var opsi = {
            'pageLength': 50,
            'order': [],
            'columnDefs': [{'targets': 0, 'orderable': false}],
            'language': {
                'search': 'Cari:',
                'lengthMenu': 'Tampilkan _MENU_ data',
                'info': 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                'infoEmpty': 'Tidak ada data',
                'zeroRecords': 'Data tidak ditemukan',
                'paginate': {'previous': 'Sebelumnya', 'next': 'Berikutnya'},
                'emptyTable': 'Belum ada data.'
            }
        };

        if (document.getElementById('tableRekapSiswa')) {
            var opsiSiswa = JSON.parse(JSON.stringify(opsi));
            opsiSiswa.order = [[5, 'desc']];
            opsiSiswa.columnDefs = [
                {'targets': 0, 'orderable': false},
                {'targets': 7, 'orderable': false}
            ];
            jQuery('#tableRekapSiswa').DataTable(opsiSiswa);
        }
        if (document.getElementById('tableRekapKelas')) {
            var opsiKelas = JSON.parse(JSON.stringify(opsi));
            opsiKelas.order = [[5, 'desc']];
            opsiKelas.columnDefs = [
                {'targets': 0, 'orderable': false},
                {'targets': 7, 'orderable': false}
            ];
            jQuery('#tableRekapKelas').DataTable(opsiKelas);
        }
    }

    function esc(nilai) {
        return String(nilai === null || nilai === undefined ? '' : nilai)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function tanggalTampil(t) {
        if (!t) return '-';
        var bagian = String(t).split('-');
        return bagian.length === 3 ? bagian[2] + '/' + bagian[1] + '/' + bagian[0] : t;
    }

    var tombol = document.querySelectorAll('.btn-riwayat');
    for (var i = 0; i < tombol.length; i++) {
        tombol[i].addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            var tbody = document.getElementById('isiRiwayat');
            var info = document.getElementById('infoRiwayat');

            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>';
            info.innerHTML = '';
            document.getElementById('judulModalRiwayat').textContent = 'Riwayat Pelanggaran';

            if (window.jQuery) {
                jQuery('#modalRiwayat').modal('show');
            }

            var url = base_url + 'pelanggaran/riwayat/' + encodeURIComponent(id) + '?' + riwayatQuery;
            fetch(url, {
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) throw new Error('Gagal memuat data');
                return res.json();
            }).then(function (data) {
                document.getElementById('judulModalRiwayat').textContent = 'Riwayat: ' + data.siswa.nama;
                info.innerHTML = '<div class="callout callout-info mb-0 py-2">'
                    + '<b>' + esc(data.siswa.nama) + '</b> &middot; NIS ' + esc(data.siswa.nis)
                    + ' &middot; Kelas ' + esc(data.siswa.nama_kelas)
                    + '<span class="float-right">' + data.jumlah + ' catatan &middot; <b>' + data.total_poin + ' poin</b></span>'
                    + '</div>';

                if (!data.rows || !data.rows.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Tidak ada pelanggaran pada rentang tanggal ini.</td></tr>';
                    return;
                }

                var html = '';
                for (var j = 0; j < data.rows.length; j++) {
                    var r = data.rows[j];
                    html += '<tr>'
                        + '<td>' + (j + 1) + '</td>'
                        + '<td>' + esc(tanggalTampil(r.tanggal)) + '</td>'
                        + '<td>' + esc(r.jenis_pelanggaran) + '</td>'
                        + '<td class="text-center"><span class="badge badge-danger">' + esc(r.poin) + '</span></td>'
                        + '<td>' + (r.keterangan ? esc(r.keterangan) : '<span class="text-muted">-</span>') + '</td>'
                        + '<td>' + esc(r.dicatat_oleh || '-') + '</td>'
                        + '</tr>';
                }
                tbody.innerHTML = html;
            }).catch(function () {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Data riwayat gagal dimuat. Coba lagi.</td></tr>';
            });
        });
    }
});
