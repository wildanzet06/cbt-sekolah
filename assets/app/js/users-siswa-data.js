    let currentPage = 1;
    let perPage = 10;
    let $pagination, defaultOpts, query;

    $(document).ready(function() {
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

        $('#input-search').on('change keyup', function () {
            var val = $(this).val();
            query = val === "" ? null : val;
            $('#btn-clear').attr('disabled', query == null)
            $('#btn-search').attr('disabled', query == null)
        });

        $('#btn-clear').on('click', function () {
            query = null;
            currentPage = 1;
            $(this).attr('disabled', query == null);
            $('#btn-search').attr('disabled', query == null);
            loadSiswa();
        });

        $("#users").on("click", ".btn-aktif", function() {
            let id = $(this).data("id");
            $('#loading').removeClass('d-none');
            $.ajax({
                url: base_url + "usersiswa/activate/" + id,
                type: "GET",
                success: function (response) {
                    console.log("pass", response.pass);
                    $('#loading').addClass('d-none');
                    if (response.msg) {
                        if (response.status) {
                            swal.fire({
                                title: "Sukses",
                                text: response.msg,
                                icon: "success"
                            });
                            loadSiswa();
                        } else {
                            swal.fire({
                                title: "Error",
                                text: response.msg,
                                icon: "error"
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr);
                    Swal.fire({
                        title: "Gagal",
                        html: xhr.responseText,
                        icon: "error"
                    });
                }
            });
        });

        $("#users").on("click", ".btn-nonaktif", function() {
            let username = $(this).data("username");
            let nama = $(this).data("nama").replace("'", "");
            $('#loading').removeClass('d-none');
            $.ajax({
                url: base_url + "usersiswa/deactivate/" + username +"/"+nama,
                type: "GET",
                success: function (response) {
                    $('#loading').addClass('d-none');
                    if (response.msg) {
                        if (response.status) {
                            swal.fire({
                                title: "Sukses",
                                text: response.msg,
                                icon: "success"
                            });
                            loadSiswa();
                        } else {
                            swal.fire({
                                title: "Error",
                                text: response.msg,
                                icon: "error"
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr);
                    Swal.fire({
                        title: "Gagal",
                        html: xhr.responseText,
                        icon: "error"
                    });
                }
            });
        });

        $("#users").on("click", ".btn-reset", function() {
            let username = $(this).data("username");
            let nama = encodeURIComponent($(this).data("nama"));
            $('#loading').removeClass('d-none');
            $.ajax({
                url: base_url + "usersiswa/reset_login/" + username +"/"+nama,
                type: "GET",
                success: function (response) {
                    $('#loading').addClass('d-none');
                    if (response.msg) {
                        if (response.status) {
                            swal.fire({
                                title: "Sukses",
                                html: "<b>"+decodeURIComponent(response.msg)+"<b>",
                                icon: "success"
                            });
                            loadSiswa();
                        } else {
                            swal.fire({
                                title: "Error",
                                html: "<b>"+decodeURIComponent(response.msg)+"<b>",
                                icon: "error"
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr);
                    Swal.fire({
                        title: "Gagal",
                        html: xhr.responseText,
                        icon: "error"
                    });
                }
            });
        });

        $(".btn-action").on("click", function() {
            let action = $(this).data("action");
            let uri = action === 'aktifkan' ? base_url + "usersiswa/aktifkansemua" : base_url + "usersiswa/nonaktifkansemua";

            swal.fire({
                title: action === 'aktifkan' ? "Aktifan semua siswa" : "Nonaktifkan semua siswa",
                text: "",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Lanjutkan"
            }).then(result => {
                if (result.value) {
                    $('#loading').removeClass('d-none');
                    swal.fire({
                        title: action === 'aktifkan' ? "Mengaktifkan semua siswa" : "Menonaktifkan semua siswa",
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
                        url: uri,
                        type: "GET",
                        success: function (response) {
                            $('#loading').addClass('d-none');
                            console.log("result", response);
                            swal.fire({
                                title: response.status ? "Sukses" : "Gagal",
                                text: response.msg,
                                icon: response.status ? "success" : "error"
                            }).then(result => {
                                loadSiswa();
                            });
                        },
                        error: function(xhr, status, error) {
                            console.log(xhr);
                            Swal.fire({
                                title: "Gagal",
                                html: xhr.responseText,
                                icon: "error"
                            });
                        }
                    });
                }
            });
        });

        loadSiswa();

    });

    function loadSiswa() {
        $('#pager-page').val(currentPage);
        $('#loading').removeClass('d-none');
        var cari = query != null ? '&search=' + query : ''
        var dataPost = $('#pager').serialize() + cari;
        console.log('post', dataPost);
        $.ajax({
            url: base_url + 'usersiswa/list',
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

    function previewData(data) {
        console.log(data);
        $('#input-search').val(data.search);
        var html = '';
        if (data.lists.length > 0) {
            $.each(data.lists, function (idx, siswa) {
                const kls = siswa.nama_kelas != null ? siswa.nama_kelas : '';
                html += `<tr>
                <td class="text-center align-middle">
                    <input type="hidden" name="ids[]" value="${siswa.id_siswa}">
                    ${Number((perPage * (currentPage - 1)) + (idx + 1))}</td>
            <td class="align-middle">${siswa.nis}</td>
            <td>
                <div class="media d-flex h-100">
                    <img class="avatar img-circle justify-content-center align-self-center" src="${base_url+siswa.foto}" width="30" height="30" alt="User Image">
                        <div class="media-body ml-2 justify-content-center align-self-center">${siswa.nama}</div>
                </div>
            </td>
            <td class="align-middle">${kls}</td>
            <td class="align-middle">${siswa.username}</td>
            <td class="align-middle td-password" data-id="${siswa.id_siswa}" data-username="${siswa.username}">
                <button type="button" class="btn btn-default btn-xs btn-lihat-password" data-id="${siswa.id_siswa}" data-toggle="tooltip" title="Lihat password awal"><i class="fa fa-eye"></i></button>
                <span class="pw-awal d-none"></span>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-default btn-xs btn-reset" ${siswa.reset == '0' ? 'disabled' : ''} data-username="${siswa.username}" data-nama="${siswa.nama}" data-toggle="tooltip" title="Reset Login"> <i class="fa fa-sync text-xs mr-1 ml-1"></i></button>
            </td>
            <td class="text-center align-middle p-1">`;
                if (siswa.aktif == "0") {
                    html += `<span class="badge badge-danger">Nonaktif</span><br><button type="button" class="btn btn-aktif btn-success btn-xs" data-id="${siswa.id_siswa}" data-toggle="tooltip" title="Aktifkan"> <i class="fa fa-user-plus text-xs mr-1 ml-1"></i> </button>`;
                } else {
                    html += `<span class="badge badge-success">Aktif</span><br><button type="button" class="btn btn-nonaktif btn-danger btn-xs" data-username="${siswa.username}" data-nama="${siswa.nama}" data-toggle="tooltip" title="Nonaktifkan"> <i class="fa fa-ban text-xs mr-1 ml-1"></i></button>`;
                }
            html +=`</td></tr>`;
            });
        } else {
            html += '<tr><td colspan="7" class="text-center align-middle">Tidak ada data siswa</td><tr>';
        }

        $('#table-body').html(html);
        tandaiPasswordDiganti();
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

    // Tandai siswa yang sudah mengganti password sendiri: password awal tidak lagi
    // berlaku, jadi diganti tulisan "Sudah diganti siswa" (bukan angka lama).
    function tandaiPasswordDiganti() {
        var sel = $('#table-body .td-password');
        var nama = sel.map(function () { return $(this).attr('data-username'); }).get();
        if (nama.length === 0) return;

        function tampilkanSemua() { /* tombol tetap tersedia bila pengecekan gagal */ }

        $.ajax({
            url: base_url + 'akun/status_password',
            type: 'POST',
            dataType: 'json',
            data: {usernames: nama},
            success: function (res) {
                var diganti = {};
                $.each((res && res.diganti) ? res.diganti : [], function (i, u) { diganti[String(u)] = true; });
                sel.each(function () {
                    var u = String($(this).attr('data-username'));
                    if (diganti[u]) {
                        $(this).html('<span class="badge badge-info" title="Password awal sudah tidak berlaku. Untuk mengembalikan, Nonaktifkan lalu Aktifkan akun.">Sudah diganti siswa</span>');
                    }
                });
            },
            error: tampilkanSemua
        });
    }

    // Password tidak dikirim bersama daftar. Admin memintanya satu per satu.
    $(document).on('click', '.btn-lihat-password', function () {
        var tombol = $(this);
        var sel = tombol.closest('.td-password');
        $.ajax({
            url: base_url + 'akun/lihat_password',
            type: 'POST',
            dataType: 'json',
            data: {id_siswa: tombol.attr('data-id')},
            success: function (res) {
                sel.find('.pw-awal').text(res && res.password ? res.password : '-').removeClass('d-none');
                tombol.remove();
            },
            error: function () {
                swal.fire({title: 'Gagal', text: 'Password tidak dapat ditampilkan.', icon: 'error'});
            }
        });
    });

    function applySearch() {
        query = $('#input-search').val();
        currentPage = 1;
        loadSiswa();
    }


