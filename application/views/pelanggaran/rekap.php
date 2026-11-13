<?php
$total = isset($ringkasan) && $ringkasan ? $ringkasan : (object) array('jml' => 0, 'poin' => 0, 'siswa' => 0);
$filter_q = array('kelas' => $kelas_id, 'dari' => $dari, 'sampai' => $sampai);
$riwayat_q = http_build_query(array('format' => 'json', 'dari' => $dari, 'sampai' => $sampai));
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1><i class="fas fa-chart-bar text-primary"></i> Rekap Poin Pelanggaran</h1>
                    <small class="text-muted">
                        Tahun pelajaran <?= html_escape($tp_active->tahun); ?> &middot;
                        Semester <?= html_escape($smt_active->nama_smt); ?>
                    </small>
                </div>
                <div class="col-sm-5 text-right">
                    <a href="<?= site_url('pelanggaran' . ($kelas_id ? '?kelas=' . (int) $kelas_id : '')); ?>"
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Daftar Pelanggaran
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-4">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-warning"><i class="fas fa-list"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Jumlah Pelanggaran</span>
                            <span class="info-box-number"><?= (int) $total->jml; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-danger"><i class="fas fa-star-half-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Poin</span>
                            <span class="info-box-number"><?= (int) $total->poin; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Siswa Terlibat</span>
                            <span class="info-box-number"><?= (int) $total->siswa; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="get" action="<?= site_url('pelanggaran/rekap'); ?>" class="form-row align-items-end">
                        <div class="form-group col-12 col-md-4">
                            <label>Kelas</label>
                            <select name="kelas" class="form-control">
                                <option value="">Semua kelas</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= (int) $k->id_kelas; ?>" <?= ((int) $kelas_id === (int) $k->id_kelas) ? 'selected' : ''; ?>>
                                        <?= html_escape($k->nama_kelas); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Dari tanggal <small class="text-muted">(opsional)</small></label>
                            <input type="date" name="dari" class="form-control" value="<?= html_escape($dari); ?>">
                        </div>
                        <div class="form-group col-6 col-md-3">
                            <label>Sampai tanggal <small class="text-muted">(opsional)</small></label>
                            <input type="date" name="sampai" class="form-control" value="<?= html_escape($sampai); ?>">
                        </div>
                        <div class="form-group col-12 col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="tabRekap" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-siswa-link" data-toggle="tab" href="#tab-siswa" role="tab">
                                <i class="fas fa-user-graduate"></i> Rekap per Siswa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-kelas-link" data-toggle="tab" href="#tab-kelas" role="tab">
                                <i class="fas fa-school"></i> Rekap per Kelas
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="tabRekapContent">

                        <div class="tab-pane fade show active" id="tab-siswa" role="tabpanel">
                            <div class="d-flex justify-content-end mb-2">
                                <a href="<?= site_url('pelanggaran/export?' . http_build_query(array_merge($filter_q, array('jenis' => 'siswa')))); ?>"
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel"></i> Ekspor Rekap Siswa
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table id="tableRekapSiswa" class="table table-bordered table-striped table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">No</th>
                                            <th>NIS</th>
                                            <th>Nama Siswa</th>
                                            <th>Kelas</th>
                                            <th class="text-center">Jumlah Pelanggaran</th>
                                            <th class="text-center">Total Poin</th>
                                            <th>Pelanggaran Terakhir</th>
                                            <th class="text-center" style="width:90px">Riwayat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($rekap_siswa as $r): ?>
                                            <?php
                                            $poin = (int) $r->total_poin;
                                            $badge = $poin >= 50 ? 'danger' : ($poin >= 20 ? 'warning' : ($poin > 0 ? 'info' : 'secondary'));
                                            ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><?= html_escape($r->nis); ?></td>
                                                <td><?= html_escape($r->nama); ?></td>
                                                <td><?= html_escape($r->nama_kelas ? $r->nama_kelas : '-'); ?></td>
                                                <td class="text-center"><?= (int) $r->jml; ?></td>
                                                <td class="text-center"><span class="badge badge-<?= $badge; ?>"><?= $poin; ?></span></td>
                                                <td><?= $r->terakhir ? date('d/m/Y', strtotime($r->terakhir)) : '<span class="text-muted">-</span>'; ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-info btn-xs btn-riwayat"
                                                            data-id="<?= (int) $r->id_siswa; ?>" title="Lihat riwayat">
                                                        <i class="fas fa-list"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-kelas" role="tabpanel">
                            <div class="d-flex justify-content-end mb-2">
                                <a href="<?= site_url('pelanggaran/export?' . http_build_query(array_merge($filter_q, array('jenis' => 'kelas')))); ?>"
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel"></i> Ekspor Rekap Kelas
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table id="tableRekapKelas" class="table table-bordered table-striped table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">No</th>
                                            <th>Kelas</th>
                                            <th class="text-center">Jumlah Siswa</th>
                                            <th class="text-center">Siswa Terlibat</th>
                                            <th class="text-center">Jumlah Pelanggaran</th>
                                            <th class="text-center">Total Poin</th>
                                            <th class="text-center">Rata-rata Poin/Siswa</th>
                                            <th class="text-center" style="width:100px">Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($rekap_kelas as $r): ?>
                                            <?php
                                            $poin = (int) $r->total_poin;
                                            $badge = $poin >= 50 ? 'danger' : ($poin >= 20 ? 'warning' : ($poin > 0 ? 'info' : 'secondary'));
                                            ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><?= html_escape($r->nama_kelas); ?></td>
                                                <td class="text-center"><?= (int) $r->jumlah_siswa; ?></td>
                                                <td class="text-center"><?= (int) $r->siswa_terlibat; ?></td>
                                                <td class="text-center"><?= (int) $r->jumlah_pelanggaran; ?></td>
                                                <td class="text-center"><span class="badge badge-<?= $badge; ?>"><?= $poin; ?></span></td>
                                                <td class="text-center"><?= html_escape($r->rata_poin); ?></td>
                                                <td class="text-center">
                                                    <a href="<?= site_url('pelanggaran?' . http_build_query(array('kelas' => (int) $r->id_kelas, 'dari' => $dari, 'sampai' => $sampai))); ?>"
                                                       class="btn btn-secondary btn-xs" title="Lihat daftar pelanggaran kelas ini">
                                                        <i class="fas fa-search"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal riwayat siswa -->
<div class="modal fade" id="modalRiwayat" tabindex="-1" role="dialog" aria-labelledby="judulModalRiwayat" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalRiwayat">Riwayat Pelanggaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="infoRiwayat" class="mb-2"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">No</th>
                                <th>Tanggal</th>
                                <th>Jenis Pelanggaran</th>
                                <th class="text-center">Poin</th>
                                <th>Keterangan</th>
                                <th>Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody id="isiRiwayat">
                            <tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var riwayatQuery = <?= json_encode($riwayat_q); ?>;

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
</script>
