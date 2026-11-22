    let currentPage = 1;
    let perPage = 10;
    let $pagination, defaultOpts, query, actionBulk;

    $(document).ready(function () {
        ajaxcsrf();
        $pagination = $('#pagination');
        defaultOpts = {
            visiblePages: 5,
            initiateStartPageClick: false,
            onPageClick: function (event, page) {
                console.info(page + ' (from options)');
                currentPage = page;
                loadSiswa();
            }
        };
        $pagination.twbsPagination(defaultOpts);

        $('#users_length').change(function () {
            $('#pager-limit').val($(this).val());
            perPage = $(this).val();
            currentPage = 1;
            loadSiswa();
        });

        $('#users-filter').change(function () {
            currentPage = 1;
            loadSiswa();
        });

        $('#input-search').on('change keyup', function () {
            var val = $(this).val();
            query = val === "" ? null : val;
        });

        var suggestTimer = null;
        function hideSuggest() {
            $('#search-suggest').addClass('d-none').empty();
        }

        $('#input-search').on('input', function () {
            var q = $.trim($(this).val());
            clearTimeout(suggestTimer);
            if (q.length < 2) { hideSuggest(); return; }
            suggestTimer = setTimeout(function () {
                $.ajax({
                    url: base_url + 'datasiswa/list',
                    type: 'POST',
                    dataType: 'json',
                    data: { search: q, page: 1, limit: 8, filter: $('#users-filter').val() },
                    success: function (res) {
                        var $box = $('#search-suggest').empty();
                        if (!res || !res.lists || res.lists.length === 0) { $box.addClass('d-none'); return; }
                        $.each(res.lists, function (i, s) {
                            var $b = $('<button type="button" class="suggest-item">');
                            $('<div>').append($('<b>').text(s.nama || '-')).appendTo($b);
                            $('<div class="suggest-meta">').text('NIS ' + (s.nis || '-') + (s.nama_kelas ? ' \u00b7 ' + s.nama_kelas : '')).appendTo($b);
                            $b.data('nama', s.nama || '');
                            $box.append($b);
                        });
                        $box.removeClass('d-none');
                    },
                    error: function () { hideSuggest(); }
                });
            }, 250);
        });

        $('#input-search').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); hideSuggest(); applySearch(); }
            if (e.key === 'Escape') { hideSuggest(); }
        });

        $('#search-suggest').on('click', '.suggest-item', function () {
            var nama = $(this).data('nama') || '';
            $('#input-search').val(nama);
            query = nama === '' ? null : nama;
            currentPage = 1;
            hideSuggest();
            loadSiswa();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.siswa-search').length) { hideSuggest(); }
        });

        $(".select_all").on("click", function () {
            if (this.checked) {
                $(".check").each(function () {
                    this.checked = true;
                    $(".select_all").prop("checked", true);
                    $('#hapusterpilih').removeAttr('disabled');
                    $('#dropdown-btn').removeAttr('disabled');
                });
            } else {
                $(".check").each(function () {
                    this.checked = false;
                    $(".select_all").prop("checked", false);
                    $('#hapusterpilih').attr('disabled', 'disabled');
                    $('#dropdown-btn').attr('disabled', 'disabled');
                });
            }
        });

        $("#table-siswa tbody").on("click", "tr .check", function () {
            var check = $("#table-siswa tbody tr .check").length;
            var checked = $("#table-siswa tbody tr .check:checked").length;
            if (check === checked) {
                $(".select_all").prop("checked", true);
            } else {
                $(".select_all").prop("checked", false);
            }

            if (checked === 0) {
                $('#hapusterpilih').attr('disabled', 'disabled');
                $('#dropdown-btn').attr('disabled', 'disabled');
            } else {
                $('#hapusterpilih').removeAttr('disabled');
                $('#dropdown-btn').removeAttr('disabled');
            }
        });

        $("#bulk").on("submit", function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //console.log($(this).serialize() + '&aksi=' +actionBulk)
            $.ajax({
                url: $(this).attr("action"),
                data: $(this).serialize() + '&aksi=' +actionBulk,
                type: "POST",
                success: function (respon) {
                    if (respon.status) {
                        $(".select_all").prop("checked", false);
                        $('#hapusterpilih').attr('disabled', 'disabled');
                        $('#dropdown-btn').attr('disabled', 'disabled');
                        console.log('res', respon)
                        swal.fire({
                            title: "Berhasil",
                            text: respon.total + " data berhasil dihapus",
                            icon: "success"
                        });
                        loadSiswa();
                    } else {
                        swal.fire({
                            title: "Gagal",
                            text: "Tidak ada data yang dipilih",
                            icon: "error"
                        });
                    }
                },
                error: function () {
                    swal.fire({
                        title: "Gagal",
                        text: "Ada data yang sedang digunakan",
                        icon: "error"
                    });
                }
            });
        });

        $('#tahunmasuk').datetimepicker({
            icons:
                {
                    next: 'fa fa-angle-right',
                    previous: 'fa fa-angle-left'
                },
            timepicker: false,
            format: 'Y-m-d',
            disabledWeekDays: [0],
            widgetPositioning: {
                horizontal: 'left',
                vertical: 'bottom'
            }
        });

        $('#formsiswa').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log($(this).serialize());
            $.ajax({
                url: base_url + "datasiswa/create",
                data: $(this).serialize(),
                dataType: "JSON",
                type: 'POST',
                success: function (response) {
                    console.log("result", response);
                    $('#createSiswaModal').modal('hide').data('bs.modal', null);
                    $('#createSiswaModal').on('hidden', function () {
                        $(this).data('modal', null);  // destroys modal
                    });

                    if (response.insert) {
                        showSuccessToast(response.text);
                        loadSiswa();
                    } else {
                        showDangerToast(response.text);
                    }
                },
                error: function (xhr, status, error) {
                    $('#createSiswaModal').modal('hide').data('bs.modal', null);
                    $('#createSiswaModal').on('hidden', function () {
                        $(this).data('modal', null);  // destroys modal
                    });
                    showDangerToast("Gagal disimpan");
                    console.log(xhr.responseText);
                }
            })
        });

        $("#dropdown-action a ").click(function () {
            let x = $(this).attr('id');
            //alert(x);
            actionBulk = x;
            if (x === "pindah") {
                bulk_pindah()
            } else if (x === "keluar") {
                bulk_keluar()
            } else if (x === "hapus") {
                bulk_delete()
            }
        });

        loadSiswa();
    });

    function loadSiswa() {
        $(".select_all").prop("checked", false);
        $('#pager-page').val(currentPage);
        $('#loading').removeClass('d-none');
        var cari = query != null ? '&search=' + query : ''
        var filter = '&filter=' + $('#users-filter').val();
        var dataPost = $('#pager').serialize() + cari + filter;
        console.log('post', dataPost);
        $.ajax({
            url: base_url + 'datasiswa/list',
            data: dataPost,
            type: 'POST',
            success: function (data) {
                $('#loading').addClass('d-none');
                $('#input-search').val(data.search);
                if (data.pages > 0) {
                    $pagination.removeClass('d-none');
                    $pagination.twbsPagination('destroy');
                    $pagination.twbsPagination($.extend({}, defaultOpts, {
                        startPage: currentPage,
                        totalPages: data.pages,
                    }));
                } else {
                    $pagination.addClass('d-none');
                }
                previewData(data);
            }, error: function (xhr, status, error) {
                $('#loading').addClass('d-none')
                console.log("error", xhr.responseText);
                swal.fire({
                    title: "ERROR",
                    text: "Ada kesalahan",
                    icon: "error"
                });
            }
        });
    }

    function escHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function previewData(data) {
        console.log(data);
        $('#input-search').val(data.search);
        var html = '';

        if (data.lists.length > 0) {
            $.each(data.lists, function (idx, siswa) {
                const kls = siswa.nama_kelas != null ? '<span class="badge badge-info">'+escHtml(siswa.nama_kelas)+'</span>' : '';
                const status = siswa.aktif == "0" ? '<span class="badge badge-danger">Nonaktif</span>' : '<span class="badge badge-success">Aktif</span>';
                html += '<tr>' +
                    '   <td class="text-center align-middle">' +
                    '       <input name="checked[]" class="check" value="'+siswa.id_siswa+'" type="checkbox">' +
                    '   </td>' +
                    '   <td class="text-center align-middle">'+ Number((perPage * (currentPage - 1)) + (idx + 1)) +'</td>' +
                    '   <td>' +
                    '       <div class="media d-flex h-100">' +
                    '           <img class="avatar img-circle justify-content-center align-self-center"' +
                    '                src="'+escHtml(base_url+siswa.foto)+'" width="50" height="50" alt="User Image"> ' +
                    '           <div class="media-body ml-2 justify-content-center align-self-center">'+escHtml(siswa.nama)+'<br>' + kls +
                    '               <span class="badge badge-info mr-1">'+escHtml(siswa.jenis_kelamin)+'</span>' + status +
                    '           </div>' +
                    '       </div>' +
                    '   </td>' +
                    '   <td class=" align-middle">' +
                    '       <span class="badge badge-light">'+escHtml(siswa.nis)+'</span><br>' +
                    '       <span class="badge badge-light">'+escHtml(siswa.nisn)+'</span>' +
                    '   </td>' +
                    '   <td class=" align-middle">' +
                    '       <div class="text-center">' +
                    '           <a class="btn btn-xs btn-warning" href="'+base_url+'datasiswa/edit/'+siswa.id_siswa+'">' +
                    '               <i class="fa fa-pencil-alt"></i> Edit' +
                    '           </a>' +
                    '       </div>' +
                    '   </td>' +
                    '</tr>';
            });
        } else {
            html += '<tr><td colspan="5" class="text-center align-middle">Tidak ada data siswa</td><tr>';
        }
        $('#table-body').html(html);
        $(`.avatar`).each(function () {
            $(this).on("error", function () {
                var src = $(this).attr('src').replace('profiles', 'foto_siswa');
                $(this).attr("src", src);
                $(this).on("error", function () {
                    $(this).attr("src", base_url + 'assets/img/siswa.png');
                });
            });
        });
    }

    function applySearch() {
        query = $('#input-search').val();
        currentPage = 1;
        loadSiswa();
    }

    function bulk_delete() {
        if ($("#table-siswa tbody tr .check:checked").length == 0) {
            swal.fire({
                title: "Gagal",
                text: "Tidak ada data yang dipilih",
                icon: "error"
            });
        } else {
            swal.fire({
                title: "Anda yakin?",
                text: "Data terpilih akan dihapus!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Hapus!"
            }).then(result => {
                if (result.value) {
                    $("#bulk").submit();
                }
            });
        }
    }

    function bulk_pindah() {
        if ($("#table-siswa tbody tr .check:checked").length == 0) {
            swal.fire({
                title: "Gagal",
                text: "Tidak ada data yang dipilih",
                icon: "error"
            });
        } else {
            swal.fire({
                title: "Anda yakin?",
                text: "Data terpilih akan diset sebagai siswa PINDAH",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "YA!"
            }).then(result => {
                if (result.value) {
                    $("#bulk").submit();
                }
            });
        }
    }

    function bulk_keluar() {
        if ($("#table-siswa tbody tr .check:checked").length == 0) {
            swal.fire({
                title: "Gagal",
                text: "Tidak ada data yang dipilih",
                icon: "error"
            });
        } else {
            swal.fire({
                title: "Anda yakin?",
                text: "Data terpilih akan diset sebagai siswa KELUAR",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "YA!"
            }).then(result => {
                if (result.value) {
                    $("#bulk").submit();
                }
            });
        }
    }

