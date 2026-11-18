<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-chart-pie text-primary mr-1"></i> Statistik Sekolah</h1>
                    <small class="text-muted">Keuangan, kehadiran, dan pelanggaran tahun <?= (int) $tahun; ?></small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <a href="<?= site_url('statistik?' . http_build_query(array('tahun' => $tahun - 1))); ?>" class="btn btn-default">
                            <i class="fas fa-chevron-left"></i> <?= (int) $tahun - 1; ?>
                        </a>
                        <a href="<?= site_url('statistik?' . http_build_query(array('tahun' => $tahun + 1))); ?>" class="btn btn-default">
                            <?= (int) $tahun + 1; ?> <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="<?= site_url('dashboard'); ?>" class="btn btn-primary">
                            <i class="fas fa-house-user"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h4 class="mb-0">Rp <?= number_format($spp_total, 0, ',', '.'); ?></h4>
                            <p>SPP diterima <?= (int) $tahun; ?></p>
                        </div>
                        <div class="icon"><i class="fas fa-receipt"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h4 class="mb-0">Rp <?= number_format($tunggakan_total, 0, ',', '.'); ?></h4>
                            <p>Total tunggakan</p>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h4 class="mb-0"><?= $persen_hadir; ?>%</h4>
                            <p>Kehadiran <?= (int) $tahun; ?></p>
                        </div>
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h4 class="mb-0"><?= (int) $total_pelanggaran; ?></h4>
                            <p>Catatan pelanggaran</p>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title mb-0">Penerimaan SPP per Bulan (<?= (int) $tahun; ?>)</h3></div>
                        <div class="card-body">
                            <canvas id="chartSpp" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card card-outline card-danger">
                        <div class="card-header"><h3 class="card-title mb-0">Tunggakan per Kelas</h3></div>
                        <div class="card-body">
                            <?php if (empty($kelas_label)): ?>
                                <p class="text-muted mb-0">Belum ada data tagihan.</p>
                            <?php else: ?>
                                <canvas id="chartTunggakan" height="220"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-7">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title mb-0">Kehadiran per Bulan</h3></div>
                        <div class="card-body">
                            <canvas id="chartHadir" height="140"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="card card-outline card-warning">
                        <div class="card-header"><h3 class="card-title mb-0">Jenis Pelanggaran Terbanyak</h3></div>
                        <div class="card-body">
                            <?php if (empty($pel_label)): ?>
                                <p class="text-muted mb-0">Belum ada pelanggaran pada periode aktif.</p>
                            <?php else: ?>
                                <canvas id="chartPelanggaran" height="200"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="<?= base_url() ?>/assets/plugins/chart.js/Chart.bundle.min.js"></script>
<script>
(function () {
    if (!window.Chart) return;
    var namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var rupiah = function (v) {
        return 'Rp ' + Number(v).toLocaleString('id-ID');
    };

    var spp = <?= json_encode(array_map('floatval', $spp_bulanan)); ?>;
    var elSpp = document.getElementById('chartSpp');
    if (elSpp) {
        new Chart(elSpp, {
            type: 'line',
            data: {
                labels: namaBulan,
                datasets: [{
                    label: 'Penerimaan SPP',
                    data: spp,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,.12)',
                    fill: true,
                    tension: .3,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: {
                    callbacks: {
                        label: function (item, data) { return rupiah(data.datasets[item.datasetIndex].data[item.index]); }
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (v) { return v >= 1000000 ? (v / 1000000) + ' jt' : (v / 1000) + ' rb'; }
                        }
                    }]
                }
            }
        });
    }

    var elTunggakan = document.getElementById('chartTunggakan');
    if (elTunggakan) {
        new Chart(elTunggakan, {
            type: 'bar',
            data: {
                labels: <?= json_encode($kelas_label); ?>,
                datasets: [{
                    label: 'Sisa tagihan',
                    data: <?= json_encode(array_map('floatval', $kelas_sisa)); ?>,
                    backgroundColor: 'rgba(220,53,69,.75)',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: {
                    callbacks: {
                        label: function (item, data) { return rupiah(data.datasets[item.datasetIndex].data[item.index]); }
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (v) { return v >= 1000000 ? (v / 1000000) + ' jt' : (v / 1000) + ' rb'; }
                        }
                    }]
                }
            }
        });
    }

    var elHadir = document.getElementById('chartHadir');
    if (elHadir) {
        new Chart(elHadir, {
            type: 'line',
            data: {
                labels: namaBulan,
                datasets: [
                    { label: 'Hadir', data: <?= json_encode($hadir); ?>, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.12)', fill: true, tension: .3, pointRadius: 2 },
                    { label: 'Izin', data: <?= json_encode($izin); ?>, borderColor: '#17a2b8', backgroundColor: 'transparent', tension: .3, pointRadius: 2 },
                    { label: 'Sakit', data: <?= json_encode($sakit); ?>, borderColor: '#ffc107', backgroundColor: 'transparent', tension: .3, pointRadius: 2 },
                    { label: 'Alpa', data: <?= json_encode($alpa); ?>, borderColor: '#dc3545', backgroundColor: 'transparent', tension: .3, pointRadius: 2 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
            }
        });
    }

    var elPel = document.getElementById('chartPelanggaran');
    if (elPel) {
        new Chart(elPel, {
            type: 'horizontalBar',
            data: {
                labels: <?= json_encode($pel_label); ?>,
                datasets: [{
                    label: 'Jumlah kejadian',
                    data: <?= json_encode($pel_jml); ?>,
                    backgroundColor: 'rgba(255,193,7,.8)',
                    borderColor: '#e0a800',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: { xAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }] }
            }
        });
    }
})();
</script>
