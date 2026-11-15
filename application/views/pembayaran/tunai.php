<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-7 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-money-bill-wave text-success mr-1"></i> Pembayaran Tunai</h1>
                    <small class="text-muted">Catat siswa yang membayar SPP secara tunai (cash) di sekolah</small>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="page-aksi">
                        <a href="<?= site_url('pembayaran'); ?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="<?= site_url('tagihan'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-file-invoice-dollar"></i> Daftar Tagihan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-primary mr-1"></i>
                Pembayaran tunai langsung tercatat <b>Lunas</b> (atau <b>Cicilan</b> bila jumlahnya sebagian)
                pada tagihan siswa, dan langsung masuk ke laporan penerimaan SPP.
            </div>

            <div class="alert alert-success d-none" id="kotak-sukses">
                <div id="teks-sukses"></div>
                <div class="mt-2">
                    <a href="#" class="btn btn-sm btn-primary" id="btn-cetak">
                        <i class="fas fa-print"></i> Cetak Kwitansi
                    </a>
                    <button type="button" class="btn btn-sm btn-default" id="btn-lagi">
                        <i class="fas fa-plus"></i> Input Pembayaran Lagi
                    </button>
                </div>
            </div>

            <div class="alert alert-danger d-none" id="kotak-galat"></div>

            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">Form Pembayaran Tunai</h3>
                </div>
                <div class="card-body">

                    <!-- 1. Siswa -->
                    <div class="form-group">
                        <label for="f-siswa">1. Siswa <span class="text-danger">*</span></label>
                        <select id="f-siswa" class="form-control" style="width:100%"></select>
                        <small class="form-text text-muted">Ketik nama atau NIS siswa.</small>
                    </div>

                    <div id="info-siswa" class="alert alert-light border d-none py-2">
                        <b id="info-nama"></b>
                        <span class="text-muted" id="info-kelas"></span>
                        <span class="text-muted" id="info-nis"></span>
                    </div>

                    <!-- 2. Tagihan -->
                    <div id="blok-tagihan" class="d-none">
                        <label class="d-block">2. Tagihan yang dibayar <span class="text-danger">*</span></label>
                        <div id="daftar-tagihan"></div>

                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" class="custom-control-input" id="f-tanpa-tagihan">
                            <label class="custom-control-label" for="f-tanpa-tagihan">
                                Siswa tidak punya tagihan / catat tanpa tagihan
                                <small class="text-muted d-block" style="margin-left:-1.5rem">
                                    Catatan tanpa tagihan tidak mengubah status tagihan mana pun.
                                </small>
                            </label>
                        </div>

                        <div class="form-row mt-2 d-none" id="blok-periode">
                            <div class="col-6 col-md-4 mb-2">
                                <label class="small mb-1">Bulan</label>
                                <select id="f-bulan" class="form-control form-control-sm">
                                    <?php foreach ($bulan_list as $i => $b): ?>
                                        <option value="<?= html_escape($b); ?>"><?= html_escape($b); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-4 mb-2">
                                <label class="small mb-1">Tahun</label>
                                <input type="number" id="f-tahun" class="form-control form-control-sm"
                                       min="2000" max="2100" value="<?= (int) date('Y'); ?>">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- 3. Nominal -->
                    <div class="form-row">
                        <div class="col-12 col-md-4 mb-2">
                            <label for="f-jumlah">3. Jumlah dibayar (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="f-jumlah" class="form-control" min="1" step="1000" placeholder="0">
                            <small class="form-text text-muted" id="info-sisa"></small>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <label for="f-tanggal">Tanggal bayar</label>
                            <input type="date" id="f-tanggal" class="form-control" value="<?= date('Y-m-d'); ?>">
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <label for="f-catatan">Catatan (opsional)</label>
                            <input type="text" id="f-catatan" class="form-control" maxlength="250"
                                   placeholder="mis. dibayar orang tua, No. kwitansi manual">
                        </div>
                    </div>

                    <div class="callout callout-success mb-0" id="ringkas">
                        <small class="text-muted">Pilih siswa dan tagihan untuk melihat ringkasan.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="page-aksi">
                        <button type="button" class="btn btn-success" id="btn-simpan">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-simpan-cetak">
                            <i class="fas fa-print"></i> Simpan &amp; Cetak
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
    (function () {
        var CSRF = <?= json_encode($csrf) ?>;
        var TAGIHAN_AWAL = <?= json_encode($tagihan_awal, JSON_UNESCAPED_UNICODE) ?>;
        var URL_CARI = '<?= site_url('pembayaran/cari_siswa') ?>';
        var URL_TAGIHAN = '<?= site_url('pembayaran/tagihan_siswa_json') ?>';
        var URL_SIMPAN = '<?= site_url('pembayaran/simpan_tunai') ?>';

        var siswaAktif = null;
        var tagihanAktif = null;

        function rupiah(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        }

        function toast(ok, msg) {
            if (ok && typeof showSuccessToast === 'function') { showSuccessToast(msg); return; }
            if (!ok && typeof showDangerToast === 'function') { showDangerToast(msg); return; }
            window.alert(msg);
        }

        function galat(msg) {
            $('#kotak-galat').removeClass('d-none').text(msg);
            $('html, body').animate({scrollTop: 0}, 150);
        }

        function bersihkanGalat() {
            $('#kotak-galat').addClass('d-none').text('');
        }

        function pilihSiswa(data) {
            siswaAktif = data;
            $('#f-tanpa-tagihan').prop('checked', false);
            $('#blok-tagihan').removeClass('d-none');
            $('#blok-periode').addClass('d-none');
            $('#info-siswa').removeClass('d-none');
            $('#info-nama').text(data.nama);
            $('#info-kelas').text(' · Kelas ' + data.nama_kelas);
            $('#info-nis').text(' · NIS ' + data.nis);
        }

        function renderTagihan(list) {
            var $w = $('#daftar-tagihan').empty();
            tagihanAktif = null;

            if (!list || list.length === 0) {
                $w.html('<div class="alert alert-warning py-2 mb-2">Siswa ini tidak punya tagihan yang belum lunas. ' +
                    'Centang "catat tanpa tagihan" bila tetap ingin mencatat pembayaran, ' +
                    'atau <a href="<?= site_url('tagihan/buat'); ?>">buat tagihan dulu</a>.</div>');
                $('#f-jumlah').val('');
                $('#info-sisa').text('');
                ringkas();
                return;
            }

            $.each(list, function (i, t) {
                var $item = $('<label class="d-block border rounded px-3 py-2 mb-2" style="cursor:pointer"></label>');
                var $radio = $('<input type="radio" name="tagihan" class="mr-2">').val(t.id);
                if (i === 0) { $radio.prop('checked', true); tagihanAktif = t; }
                $item.append($radio)
                     .append($('<b>').text(t.label))
                     .append($('<span class="text-muted ml-2">').text('sisa ' + rupiah(t.sisa)));
                $w.append($item);
            });

            $w.find('input[name=tagihan]').on('change', function () {
                tagihanAktif = null;
                var id = parseInt($(this).val(), 10);
                $.each(list, function (i, t) { if (t.id === id) { tagihanAktif = t; } });
                if (tagihanAktif) { $('#f-jumlah').val(tagihanAktif.sisa); }
                ringkas();
            });

            if (tagihanAktif) { $('#f-jumlah').val(tagihanAktif.sisa); }
            ringkas();
        }

        function tanpaTagihan() {
            return $('#f-tanpa-tagihan').is(':checked');
        }

        function ringkas() {
            var $r = $('#ringkas');
            if (!siswaAktif) {
                $r.html('<small class="text-muted">Pilih siswa dan tagihan untuk melihat ringkasan.</small>');
                return;
            }
            var jumlah = parseFloat($('#f-jumlah').val()) || 0;
            var label = tanpaTagihan()
                ? ('SPP ' + $('#f-bulan').val() + ' ' + $('#f-tahun').val())
                : (tagihanAktif ? tagihanAktif.label : '—');

            $r.html(
                '<div><b>' + siswaAktif.nama + '</b> · Kelas ' + siswaAktif.nama_kelas + ' · NIS ' + siswaAktif.nis + '</div>' +
                '<div class="text-muted">Untuk: ' + label + '</div>' +
                '<div class="mt-1">Jumlah: <b>' + rupiah(jumlah) + '</b>' +
                (!tanpaTagihan() && tagihanAktif ? ' <span class="text-muted">(sisa ' + rupiah(tagihanAktif.sisa) + ')</span>' : '') +
                '</div>'
            );
        }

        $(function () {
            $('#f-siswa').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Cari nama atau NIS siswa…',
                minimumInputLength: 2,
                ajax: {
                    url: URL_CARI,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return {q: params.term}; },
                    processResults: function (r) {
                        var items = [];
                        if (r && r.status && r.items) {
                            $.each(r.items, function (i, s) {
                                items.push({id: s.id_siswa, text: s.teks, data: s});
                            });
                        }
                        return {results: items};
                    }
                }
            });

            $('#f-siswa').on('select2:select', function (e) {
                var s = e.params.data.data;
                pilihSiswa(s);
                bersihkanGalat();
                $.getJSON(URL_TAGIHAN + '/' + s.id_siswa, function (r) {
                    if (r && r.status) {
                        pilihSiswa(r.siswa);
                        renderTagihan(r.tagihan);
                    } else {
                        galat((r && r.msg) ? r.msg : 'Gagal memuat tagihan siswa.');
                    }
                });
            });

            $('#f-tanpa-tagihan').on('change', function () {
                var on = $(this).is(':checked');
                $('#blok-periode').toggleClass('d-none', !on);
                if (on) {
                    $('#daftar-tagihan input[type=radio]').prop('checked', false);
                    tagihanAktif = null;
                    $('#f-jumlah').val('');
                    $('#info-sisa').text('');
                }
                ringkas();
            });

            $('#f-jumlah').on('input', function () {
                if (!tanpaTagihan() && tagihanAktif) {
                    var maks = parseInt(tagihanAktif.sisa, 10);
                    var v = parseInt($(this).val(), 10) || 0;
                    $('#info-sisa').text(v > maks ? ('Melebihi sisa tagihan (' + rupiah(maks) + ')') : ('Sisa tagihan ' + rupiah(maks)));
                }
                ringkas();
            });

            $('#f-bulan, #f-tahun').on('change', ringkas);

            function kirim(cetak) {
                if (!siswaAktif) { galat('Pilih siswa dulu.'); return; }
                if (!tanpaTagihan() && !tagihanAktif) { galat('Pilih tagihan yang dibayar, atau centang "catat tanpa tagihan".'); return; }

                var jumlah = parseFloat($('#f-jumlah').val()) || 0;
                if (jumlah <= 0) { galat('Jumlah pembayaran harus lebih dari nol.'); return; }
                if (!tanpaTagihan() && tagihanAktif && jumlah > parseInt(tagihanAktif.sisa, 10)) {
                    galat('Jumlah melebihi sisa tagihan (' + rupiah(tagihanAktif.sisa) + ').');
                    return;
                }

                bersihkanGalat();
                var data = {
                    siswa_id: siswaAktif.id_siswa,
                    tagihan_id: tanpaTagihan() ? 0 : tagihanAktif.id,
                    jumlah_bayar: jumlah,
                    tanggal: $('#f-tanggal').val(),
                    catatan: $('#f-catatan').val(),
                    bulan: $('#f-bulan').val(),
                    tahun: $('#f-tahun').val(),
                    cetak: cetak ? 1 : 0
                };
                data[CSRF.name] = CSRF.hash;

                var $btn = cetak ? $('#btn-simpan-cetak') : $('#btn-simpan');
                $btn.prop('disabled', true);

                $.ajax({
                    type: 'POST', url: URL_SIMPAN, data: data, dataType: 'json',
                    success: function (r) {
                        $btn.prop('disabled', false);
                        if (!r || !r.status) {
                            galat((r && r.msg) ? r.msg : 'Gagal menyimpan pembayaran.');
                            return;
                        }
                        if (r.cetak) {
                            window.location.href = r.kwitansi;
                            return;
                        }
                        toast(true, r.msg);
                        $('#teks-sukses').text(r.msg);
                        $('#btn-cetak').attr('href', r.kwitansi);
                        $('#kotak-sukses').removeClass('d-none');
                        $('html, body').animate({scrollTop: 0}, 150);
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        var msg = 'Terjadi kesalahan pada server.';
                        try { msg = JSON.parse(xhr.responseText).msg || msg; } catch (e) {}
                        galat(msg);
                    }
                });
            }

            $('#btn-simpan').on('click', function () { kirim(false); });
            $('#btn-simpan-cetak').on('click', function () { kirim(true); });

            $('#btn-lagi').on('click', function () {
                $('#kotak-sukses').addClass('d-none');
                $('#f-siswa').val(null).trigger('change');
                $('#f-siswa').empty();
                siswaAktif = null;
                tagihanAktif = null;
                $('#blok-tagihan').addClass('d-none');
                $('#info-siswa').addClass('d-none');
                $('#f-jumlah, #f-catatan').val('');
                $('#f-tanpa-tagihan').prop('checked', false);
                $('#daftar-tagihan').empty();
                $('#info-sisa').text('');
                ringkas();
            });

            // Prefill bila datang dari halaman tagihan (?tagihan=ID)
            if (TAGIHAN_AWAL) {
                var opsi = new Option(TAGIHAN_AWAL.siswa.nama + ' — ' + TAGIHAN_AWAL.siswa.nis, TAGIHAN_AWAL.siswa.id_siswa, true, true);
                $('#f-siswa').append(opsi).trigger('change');
                pilihSiswa(TAGIHAN_AWAL.siswa);
                $.getJSON(URL_TAGIHAN + '/' + TAGIHAN_AWAL.siswa.id_siswa, function (r) {
                    if (r && r.status) {
                        pilihSiswa(r.siswa);
                        renderTagihan(r.tagihan);
                        $('#daftar-tagihan input[type=radio]').each(function () {
                            if (parseInt($(this).val(), 10) === TAGIHAN_AWAL.id) {
                                $(this).prop('checked', true).trigger('change');
                            }
                        });
                    }
                });
            }

            ringkas();
        });
    })();
</script>
