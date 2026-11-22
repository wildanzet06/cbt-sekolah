    var __bs = document.getElementById('banksoal-data');
var idFilter = __bs ? (__bs.dataset.idFilter || '') : '';
    var idMapel = __bs ? (__bs.dataset.idMapel || '') : '';
    var idLevel = __bs ? (__bs.dataset.idLevel || '') : '';
    var idGuru = __bs ? (__bs.dataset.idGuru || '') : '';
    var mode = __bs ? (__bs.dataset.mode || '') : '';

    $(document).ready(function () {
        ajaxcsrf();

        var count = $('#konten .check-bank').length;
        var selectedF = idFilter == '' ? 'selected' : '';
        var selectedM = idMapel == '' ? 'selected' : '';
        var selectedL = idLevel == '' ? 'selected' : '';

        var opsiFilter = $('#filter')
        var opsiGuru = $('#guru')
        var opsiMapel = $('#mapel')
        var opsiLevel = $('#level')

        opsiFilter.select2();
        opsiGuru.select2();
        opsiMapel.select2();
        opsiLevel.select2();

        opsiFilter.prepend("<option value='' " + selectedF + " disabled='disabled'>Filter berdasarkan:</option>");
        opsiMapel.prepend("<option value='' " + selectedM + " disabled='disabled'>Pilih mapel:</option>");
        opsiLevel.prepend("<option value='' " + selectedL + " disabled='disabled'>Pilih level:</option>");

        function onChangeFilter(type) {
            if (type == '1') {
                $('#select-guru').removeClass('d-none');
                $('#select-mapel').addClass('d-none');
                $('#select-level').addClass('d-none');
            } else if (type == '2') {
                $('#select-guru').addClass('d-none');
                $('#select-mapel').removeClass('d-none');
                $('#select-level').addClass('d-none');
            } else if (type == '3') {
                $('#select-guru').addClass('d-none');
                $('#select-mapel').addClass('d-none');
                $('#select-level').removeClass('d-none');
            } else {
                $('#select-guru').addClass('d-none');
                $('#select-mapel').addClass('d-none');
                $('#select-level').addClass('d-none');
            }
        }

        opsiFilter.on('change', function () {
            var type = $(this).val();
            console.log(type);
            if (type == '0' && idFilter != '0') {
                window.location.href = base_url + 'cbtbanksoal?type=0&mode=' + mode;
            } else {
                onChangeFilter(type);
            }
        });

        $('.sel').on('change', function () {
            var id = $(this).val();
            window.location.href = base_url + 'cbtbanksoal?id=' + id + '&type=' + opsiFilter.val() + '&mode=' + mode;
        });
        /*
		$('#guru').on('change', function () {
			idGuru = $(this).val();
			window.location.href = base_url + 'cbtbanksoal?id=' + idGuru;
		});
		*/

        onChangeFilter(opsiFilter.val());

        var unchecked = [];
        var checked = [];

        function findUnchecked() {
            unchecked = [];
            checked = [];

            $("#konten .check-bank:not(:checked)").each(function () {
                unchecked.push($(this).val());
            });
            $("#konten .check-bank:checked").each(function () {
                checked.push($(this).val());
            });
            var countChecked = $("#konten .check-bank:checked").length;
            $("#check-all").prop("checked", countChecked == count);

            $("#submit-hapus").attr('disabled', countChecked == 0);
        }

        $("#konten").on("change", ".check-bank", function () {
            findUnchecked();
        });

        $("#check-all").on("click", function () {
            if (count > 0) {
                if (this.checked) {
                    $(".check-bank").each(function () {
                        this.checked = true;
                        $("#check-all").prop("checked", true);
                    });
                } else {
                    $(".check-bank").each(function () {
                        this.checked = false;
                        $("#check-all").prop("checked", false);
                    });
                }
                findUnchecked()
            }
        });

        $('#hapus_semua').on('submit', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();

            var dataPost = $(this).serialize() + "&ids=" + JSON.stringify(checked);
            console.log(dataPost);
            if (checked.length == 0) {
                swal.fire({
                    title: "",
                    text: "Pilih Bank Soal yang akan dihapus",
                    icon: "warning"
                });
                return;
            }

            swal.fire({
                title: "Anda yakin?",
                text: "Semua Bank Soal yang saat ini ditampilkan akan dihapus!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Hapus!"
            }).then(result => {
                if (result.value) {
                    swal.fire({
                        text: "Silahkan tunggu....",
                        button: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false,
                        allowEscapeKey: false,
                        allowOutsideClick: false,
                        onOpen: () => {
                            swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: base_url + 'cbtbanksoal/deleteallbank',
                        type: "POST",
                        data: dataPost,
                        success: function (respon) {
                            if (respon.status) {
                                swal.fire({
                                    title: "Berhasil",
                                    text: "Semua Bank soal berhasil dihapus",
                                    icon: "success"
                                }).then(result => {
                                    if (result.value) {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                swal.fire({
                                    title: "Gagal",
                                    text: "Tidak bisa menghapus, " + respon.message,
                                    icon: "error"
                                });
                            }
                        },
                        error: function () {
                            const err = JSON.parse(xhr.responseText)
                            swal.fire({
                                title: "Error",
                                text: err.Message,
                                icon: "error"
                            });
                        }
                    });
                }
            });
        })
    });

    function filterBy() {
        console.log('find', $('#filter-by').val());
        $('.card').show();
        var filter = $('#filter-by').val(); // get the value of the input, which we filter on
        $('#konten').find(".card-body:not(:contains(" + filter + "))").parent().parent().css('display', 'none');
    }

    function getSoal(e) {
        var id = $(e).data('id');
        var level = $(e).data('level');
        var mapel = $(e).data('mapel');
        var jmlEssai = $(e).data('essai');
        $.ajax({
            url: base_url + 'cbtbanksoal/getsoalsiswa/' + id,
            type: "GET",
            success: function (respon) {
                console.log(respon);
                if (respon.soal.length > 0) {
                    var pg1 = '';
                    var pg2 = '';
                    var jodoh = '';
                    var isian = '';
                    var essais = '';

                    var jwbnPgSiswa = '<tr>' +
                        '    <td colspan="3" class="pt-2">Jawaban Pilihan Ganda</td>' +
                        '</tr>';
                    var jwbnPg2Siswa = '<tr>' +
                        '    <td colspan="3" class="pt-2">Jawaban Pilihan Ganda Kompleks</td>' +
                        '</tr>';
                    var jwbnJodohkanSiswa = '<tr>' +
                        '    <td colspan="3">Jawaban Menjodohkan</td>' +
                        '</tr>';
                    var jwbnIsianSiswa = '<tr>' +
                        '    <td colspan="3">Jawaban Isian Singkat</td>' +
                        '</tr>';
                    var jwbnEssaiSiswa = '<tr>' +
                        '    <td colspan="3">Jawaban Uraian</td>' +
                        '</tr>';

                    $.each(respon.soal, function (i, v) {
                        if (v.jenis == '1') {
                            jwbnPgSiswa += '<tr>' +
                                '<td class="align-top" style="width: 30px"><b>' + v.nomor_soal + '.</b><td>' +
                                '<td>' + v.jawaban + '<td>' +
                                '</tr>';

                            pg1 += '<li style="margin-bottom: 12px">' +
                                v.soal +
                                '<ol type="a">' +
                                '<li>' + v.opsi_a + '</li>' +
                                '<li>' + v.opsi_b + '</li>' +
                                '<li>' + v.opsi_c + '</li>' +
                                '<li>' + v.opsi_d + '</li>';
                            if ((__bs ? (__bs.dataset.jenjang || '') : '') === '3') {
                                pg1 += '<li>' + v.opsi_e + '</li>';
                            }
                            pg1 += '</ol>' + '</li>';

                        } else if (v.jenis == '2') {
                            jwbnPg2Siswa += '<tr>' +
                                '<td class="align-top" style="width: 30px"><b>' + v.nomor_soal + '.</b><td>' +
                                '<td>' + v.jawaban + '<td>' +
                                '</tr>';

                            pg2 += '<li style="padding-bottom: 12px">' + v.soal +
                                '<ol type="a">';
                            $.each(v.opsi_a, function (abjad, opsi) {
                                pg2 += '<li>' + opsi + '</li>';
                            });
                            pg2 += '</ol>' + '</li>';
                        } else if (v.jenis == '3') {
                            var jawaban = '<table class="mb-3" style="width:100%; border: 1px solid black; border-collapse: collapse; border-spacing: 0;">';
                            $.each(v.jawaban.jawaban, function (i, kolom) {
                                var bgkol = i === 0 ? 'lightgrey' : 'white';
                                jawaban += '<tr style="background: ' + bgkol + '">';
                                $.each(kolom, function (row, td) {
                                    var val = td === "1" ? "ÃƒÂ¢Ã…â€œÃ¢â‚¬Â" : (td === "0" || td === "#" ? "" : td);
                                    var bg = row === 0 ? ' background: lightgrey;' : '';
                                    jawaban += '<td style="border: 1px solid black;border-collapse: collapse; text-align: center;' + bg + '">' + val + '</td>';
                                });
                                jawaban += '</tr>';
                            });
                            jawaban += '</table>';
                            jwbnJodohkanSiswa += '<tr>' +
                                '<td class="align-top" style="width: 30px"><b>' + v.nomor_soal + '.</b><td>' +
                                '<td>'
                                + jawaban +
                                '<td>' +
                                '</tr>';

                            jodoh += '<li style="padding-bottom: 12px">' + v.soal +
                                '<table style="width:100%; border: 1px solid black; border-collapse: collapse; border-spacing: 0;">';
                            $.each(v.jawaban.jawaban, function (i, kolom) {
                                var bgkol = i === 0 ? 'lightgrey' : 'white';
                                jodoh += '<tr style="background: ' + bgkol + '">';
                                $.each(kolom, function (row, td) {
                                    var val = td === "0" || td === "1" || td === "#" ? "" : td;
                                    var bg = row === 0 ? ' background: lightgrey;' : '';
                                    jodoh += '<td style="border: 1px solid black;border-collapse: collapse; text-align: center;' + bg + '">' + val + '</td>';
                                });
                                jodoh += '</tr>';
                            });
                            jodoh += '</table></li>';
                        } else if (v.jenis == '4') {
                            jwbnIsianSiswa += '<tr>' +
                                '<td class="align-top" style="width: 30px"><b>' + v.nomor_soal + '.</b><td>' +
                                '<td>' + v.jawaban + '<td>' +
                                '</tr>';
                            isian += '<li style="padding-bottom: 12px; display: flex; align-items: baseline;">' +
                                '<span style="margin-right: 12px; padding: 0;">' + v.soal + '</span>' +
                                '<span style="flex: 1; overflow: hidden; margin: 0; padding: 0;">.............' +
                                '......................................</span></li>';
                        } else if (v.jenis == '5') {
                            jwbnEssaiSiswa += '<tr>' +
                                '<td class="align-top" style="width: 30px"><b>' + v.nomor_soal + '.</b><td>' +
                                '<td>' + v.jawaban + '<td>' +
                                '</tr>';
                            essais += '<li style="padding-bottom: 24px;"><section style="display: flex; align-items: baseline; margin-bottom: 8px">' +
                                '<span style="margin-right: 12px; padding: 0;">' + v.soal + '</span>' +
                                '<span style="flex: 1; overflow: hidden;">.............' +
                                '.............................................................................' +
                                '.............................................................................' +
                                '.............................................................................</span></section>';
                            for (let j = 0; j < 5; j++) {
                                essais += '<section style="display: flex; align-items: baseline; margin-bottom: 8px">' +
                                    '<span style="flex: 1; overflow: hidden;">.............' +
                                    '.............................................................................' +
                                    '.............................................................................' +
                                    '.............................................................................' +
                                    '.......................' +
                                    '</span></section>';
                            }
                            essais += '</li>';
                        } else {
                            //
                        }
                    });

                    $('#list-pg').html(pg1);
                    $('#list-pg2').html(pg2);
                    $('#list-jodohkan').html(jodoh);
                    $('#list-isian').html(isian);
                    $('#list-essai').html(essais);

                    $('#jawaban-soal-siswa').html(jwbnPgSiswa + jwbnPg2Siswa + jwbnJodohkanSiswa + jwbnIsianSiswa + jwbnEssaiSiswa);

                    $('#list-essai p').css({padding: 0, margin: 0});

                    $('#for-siswa img').each(function () {
                        var curSrc = $(this).attr('src');
                        if (curSrc.indexOf("http") === -1 && curSrc.indexOf("data:image") === -1) {
                            $(this).attr('src', base_url + curSrc);
                        } else if (curSrc.indexOf(base_url) === -1) {
                            var pathUpload = 'uploads';
                            var forReplace = curSrc.split(pathUpload);
                            $(this).attr('src', base_url + pathUpload + forReplace[1]);
                        }
                        /*
                        var curSrc = $(this).attr('src');
                        if (curSrc.indexOf("http") === -1 && curSrc.indexOf("data:image") === -1) {
                            $(this).attr('src', base_url + curSrc);
                        }
                        */
                    });

                    setTimeout(function () {
                        $("#for-siswa").wordExport(`Soal ${mapel} Kls ${level}`);
                    }, 500);

                } else {
                    swal.fire({
                        title: "Gagal",
                        text: "Tidak bisa mendownload soal",
                        icon: "error"
                    });
                }
            },
            error: function () {
                const err = JSON.parse(xhr.responseText)
                swal.fire({
                    title: "Error",
                    text: err.Message,
                    icon: "error"
                });
            }
        });
    }

    function exportWord(mapel, level) {
        var contentDocument = $('#for-siswa').convertToHtmlFile('ekspor', '');
        var content = '<!DOCTYPE html>' + contentDocument.documentElement.outerHTML;
        var converted = htmlDocx.asBlob(
            content, {
                orientation: 'portrait',
                size: 'A4',
                margins: {
                    top: 700,
                    bottom: 700,
                    left: 700,
                    right: 700
                }
            });

        saveAs(converted, 'Soal ' + mapel + ' Kls ' + level + '.docx');
    }

    function importSoal(btn) {
        var idBank = $(btn).data('id');
        var total = $(btn).data('total');
        if (total > 0) {
            swal.fire({
                title: "Import Soal?",
                html: "Soal sudah ada, mengimport soal baru akan menghapus soal terdahulu.<br>Lanjutkan import soal?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Lanjut"
            }).then(result => {
                if (result.value) {
                    window.location.href = base_url + 'cbtbanksoal/importsoal/' + idBank;
                }
            });
        } else {
            window.location.href = base_url + 'cbtbanksoal/importsoal/' + idBank;
        }
    }

    function hapus(id) {
        swal.fire({
            title: "Anda yakin?",
            text: "Bank Soal akan dihapus!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Hapus!"
        }).then(result => {
            if (result.value) {
                swal.fire({
                    text: "Silahkan tunggu....",
                    button: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                    allowEscapeKey: false,
                    allowOutsideClick: false,
                    onOpen: () => {
                        swal.showLoading();
                    }
                });
                $.ajax({
                    url: base_url + 'cbtbanksoal/deleteBank?id_bank=' + id,
                    //data: {id_bank: id},
                    type: "GET",
                    success: function (respon) {
                        if (respon.status) {
                            swal.fire({
                                title: "Berhasil",
                                text: "Bank soal berhasil dihapus",
                                icon: "success"
                            }).then(result => {
                                if (result.value) {
                                    window.location.reload();
                                    //window.location.href = base_url + 'cbtbanksoal?id=' + idGuru;
                                }
                            });
                        } else {
                            swal.fire({
                                title: "Gagal",
                                text: "Tidak bisa menghapus, " + respon.message,
                                icon: "error"
                            });
                        }
                    },
                    error: function () {
                        const err = JSON.parse(xhr.responseText)
                        swal.fire({
                            title: "Error",
                            text: err.Message,
                            icon: "error"
                        });
                    }
                });
            }
        });
    }

    function copy(id) {
        swal.fire({
            title: "Copi Bank Soal?",
            text: " Bank Soal ini akan dicopy",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Copy"
        }).then(result => {
            if (result.value) {
                swal.fire({
                    text: "Silahkan tunggu....",
                    button: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                    allowEscapeKey: false,
                    allowOutsideClick: false,
                    onOpen: () => {
                        swal.showLoading();
                    }
                });
                $.ajax({
                    url: base_url + 'cbtbanksoal/copybanksoal/' + id,
                    type: "GET",
                    success: function (respon) {
                        if (respon) {
                            swal.fire({
                                title: "Berhasil",
                                text: "Bank Soal berhasil dicopy",
                                icon: "success"
                            }).then(result => {
                                if (result.value) {
                                    window.location.reload();
                                }
                            })
                        } else {
                            swal.fire({
                                title: "Gagal",
                                text: "Tidak bisa mengcopy materi",
                                icon: "error"
                            });
                        }
                    },
                    error: function () {
                        const err = JSON.parse(xhr.responseText)
                        swal.fire({
                            title: "Error",
                            text: err.Message,
                            icon: "error"
                        });
                    }
                });
            }
        });
    }

