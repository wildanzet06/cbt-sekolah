(function () {
    if (!window.Chart) return;
    var namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var rupiah = function (v) {
        return 'Rp ' + Number(v).toLocaleString('id-ID');
    };

    var __st = document.getElementById('statistik-data');
    var STAT = __st ? JSON.parse(__st.dataset.json || '{}') : {};
    var spp = STAT.spp || [];
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
                labels: STAT.kelas_label || [],
                datasets: [{
                    label: 'Sisa tagihan',
                    data: STAT.kelas_sisa || [],
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
                    { label: 'Hadir', data: STAT.hadir || [], borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.12)', fill: true, tension: .3, pointRadius: 2 },
                    { label: 'Izin', data: STAT.izin || [], borderColor: '#17a2b8', backgroundColor: 'transparent', tension: .3, pointRadius: 2 },
                    { label: 'Sakit', data: STAT.sakit || [], borderColor: '#ffc107', backgroundColor: 'transparent', tension: .3, pointRadius: 2 },
                    { label: 'Alpa', data: STAT.alpa || [], borderColor: '#dc3545', backgroundColor: 'transparent', tension: .3, pointRadius: 2 }
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
                labels: STAT.pel_label || [],
                datasets: [{
                    label: 'Jumlah kejadian',
                    data: STAT.pel_jml || [],
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
