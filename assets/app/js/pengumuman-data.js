    var guru = '<?=isset($guru) ? $guru->id_guru : ""?>';

    function createTime(d) {
        var date = new Date(d);

        var jam = date.getHours();
        var menit = date.getMinutes();
        var sJam;
        var sMenit;

        if (jam < 10) sJam = '0' + jam;
        else sJam = '' + jam;

        if (menit < 10) sMenit = '0' + menit;
        else sMenit = '' + menit;

        var hari = daysdifference(d);
        var time;

        if (hari === 0) {
            time = sJam + ':' + sMenit;
        } else if (hari === 1) {
            time = 'kemarin ' + sJam + ':' + sMenit;
        } else {
            time = jQuery.timeago(d) + ', ' + sJam + ':' + sMenit;
        }
        return time;
    }

    function daysdifference(last) {
        var startDay = new Date(last);
        var endDay = new Date();

        var millisBetween = startDay.getTime() - endDay.getTime();
        var days = millisBetween / (1000 * 3600 * 24);

        return Math.round(Math.abs(days));
    }

    function addComments(id, comments, append) {
        var comm = '';
        $.each(comments, function (i, v) {
            var dari = v.dari_group === '1' ? 'Admin' : (v.dari_group === '2' ? v.nama_guru : v.nama_siswa);
            var foto = v.dari_group === '2' ? (v.foto != null ? base_url + v.foto : base_url + 'assets/img/siswa.png') :
                (v.foto_siswa != null ? base_url + v.foto_siswa : base_url + 'assets/img/siswa.png');
            var avatar = v.dari == '0' ? '<div class="btn-circle-sm btn-success media-left pt-1" style="width: 43px; height: 40px">A</div>' : '<img class="img-circle border" src="' + foto + '" alt="Img" width="40px" height="40px">';
            comm += '<div class="media mt-1" id="parent-reply' + v.id_comment + '">'
                + avatar +
                '    <div class="w-100 ml-2">' +
                '        <div class="media-body border pl-3 bg-light" style="border-radius: 20px">' +
                '            <span class="text-xs text-muted"><b>' + dari + '</b></span>' +
                '            <div class="comment-text pb-1">' + v.text + '</div>' +
                '        </div>' +
                '        <div class="ml-2">' +
                '            <span class="btn-sm mr-2 text-muted">' + createTime(v.tanggal) + '</span>' +
                '            <span id="trigger-reply' + v.id_comment + '" class="btn btn-sm mr-2 text-muted action-collapse" data-toggle="collapse" aria-expanded="true"' +
                '                              aria-controls="collapse-reply' + v.id_comment + '"' +
                '                              href="#collapse-reply' + v.id_comment + '"><b>' + v.jml + ' balasan</b></span>' +
                '            <span class="btn btn-sm mr-2 text-muted btn-toggle-reply"' +
                '                  data-id="' + v.id_comment + '" data-toggle="modal" data-target="#balasanModal">' +
                '                <i class="fas fa-reply"></i> <b>Balas</b></span>' +
                '<span class="btn btn-sm text-muted" onclick="hapusKomentar(' + v.id_comment + ')" data-id="' + v.id_comment + '"><i class="fa fa-trash mr-1"></i> Hapus</span>' +
                '</div>' +
                '<div id="collapse-reply' + v.id_comment + '" class="p-2 collapse toggle-reply" data-id="' + v.id_comment + '" data-parent="#parent-reply' + v.id_comment + '">';
            if (v.jml != '0') {
                comm += '<div id="konten-reply' + v.id_comment + '"></div>' +
                    '<div id="loadmore-reply' + v.id_comment + '" onclick="getReplies(' + v.id_comment + ')" class="text-center mb-3 loadmore-reply">' +
                    '       <div class="btn btn-default">Muat balasan lainnya ...</div>' +
                    '</div>';
            }
            comm += '    <div id="loading-reply' + v.id_comment + '" class="text-center d-none">' +
                '        <div class="spinner-grow"></div>' +
                '    </div>' +
                '</div>' +
                '    </div>' +
                '</div>';
        });

        if (append) {
            $(`#konten${id}`).append(comm);
        } else {
            $(`#konten${id}`).prepend(comm);
        }

        $('.toggle-reply').on('shown.bs.collapse', function (e) {
            var konten = $(this);
            var id = konten.data('id');
            var list = $(this).find('.media').length;
            if (list === 0) $(`#loadmore-reply${id}`).click();
        });
    }

    function addReplies(id, replies, append) {
        console.log('replies', replies);
        var repl = '';
        $.each(replies, function (i, v) {
            var sudahAda = $(`.media${v.id_reply}`).length;
            if (!sudahAda) {
                var dari = v.dari_group == '1' ? 'Admin' : (v.dari_group == '2' ? v.nama_guru : v.nama_siswa);
                var foto = v.dari_group == '2' ? (v.foto != null ? base_url + v.foto : base_url + 'assets/img/siswa.png') :
                    (v.foto_siswa != null ? base_url + v.foto_siswa : base_url + 'assets/img/siswa.png');
                var avatar = v.dari == '0' ? '<div class="btn-circle-sm btn-success media-left pt-1 mr-2" style="width: 37px">A</div>' : '<img class="img-circle mr-2 border" src="' + foto + '" alt="Img" width="35px" height="35px">';
                repl +=
                    '<div class="media mt-1 media' + v.id_reply + '">'
                    + avatar +
                    '    <div class="w-100">' +
                    '        <div class="media-body border pl-3" style="border-radius: 17px; background-color: #dee2e6">' +
                    '            <span class="text-xs text-muted"><b>' + dari + '</b></span>' +
                    '            <div class="comment-text">' + v.text +
                    '            </div>' +
                    '        </div>' +
                    '        <div class="ml-2">' +
                    '            <small class="btn-sm mr-2 text-muted">' + createTime(v.tanggal) + '</small>' +
                    '            <span class="btn btn-sm text-muted" onclick="hapusReply(' + v.id_reply + ')" data-id="' + v.id_reply + '">' +
                    '                <i class="fa fa-trash mr-1"></i> Hapus' +
                    '            </span>' +
                    '        </div>' +
                    '    </div>' +
                    '</div>';
            }
        });

        if (append) {
            $(`#konten-reply${id}`).append(repl);
        } else {
            $(`#konten-reply${id}`).prepend(repl);
        }
        console.log('added', 'reply' + id);
    }

    function getComments(id) {
        //console.log("url", base_url + "pengumuman/getcomment/" + id + "/" + page);
        $(`#loading${id}`).removeClass('d-none');
        $(`#loadmore${id}`).addClass('d-none');
        var $count = $(`#loadmore${id}`), page = $count.data('count');
        if (!page) page = 0;

        setTimeout(function () {
            $.ajax({
                url: base_url + "pengumuman/getcomment/" + id + "/" + page,
                type: "GET",
                success: function (response) {
                    //console.log('page', page);
                    console.log("result", response);
                    page += 1;
                    currentPage = page;
                    $count.data('count', page);

                    if (response.length === 5) {
                        $(`#loadmore${id}`).removeClass('d-none');
                    }
                    $(`#loading${id}`).addClass('d-none');
                    addComments(id, response, true)
                }, error: function (xhr, status, error) {
                    console.log("error", xhr.responseText);
                }
            });
        }, 500);
    }

    function getReplies(id) {
        $(`#loading-reply${id}`).removeClass('d-none');
        $(`#loadmore-reply${id}`).addClass('d-none');
        var $count = $(`#loadmore-reply${id}`), page = $count.data('count');
        if (!page) page = 0;

        setTimeout(function () {
            $.ajax({
                url: base_url + "pengumuman/getreplies/" + id + "/" + page,
                type: "GET",
                success: function (response) {
                    //console.log('page', page);
                    console.log("result", response);
                    page += 1;
                    currentPage = page;
                    $count.data('count', page);

                    //n >= start && n <= end
                    if (response.length === 5) {
                        $(`#loadmore-reply${id}`).removeClass('d-none');
                    }
                    $(`#loading-reply${id}`).addClass('d-none');
                    addReplies(id, response, true)
                }, error: function (xhr, status, error) {
                    console.log("error", xhr.responseText);
                }
            });
        }, 500);
    }

    function hapusPost(idPost) {
        swal.fire({
            title: "Hapus Pengumuman",
            text: "Pengumuman ini akan dihapus",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "HAPUS"
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
                    url: base_url + "pengumuman/hapuspost/" + idPost,
                    type: "GET",
                    success: function (data) {
                        console.log("result", data);
                        if (data) {
                            swal.fire({
                                title: "Sukses",
                                text: "Pengumuman dihapus",
                                icon: "success",
                                showCancelButton: false,
                            }).then(result => {
                                if (result.value) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            swal.fire({
                                title: "ERROR",
                                text: "Pengumuman tidak dihapus",
                                icon: "error",
                                showCancelButton: false,
                            });
                        }
                    }, error: function (xhr, status, error) {
                        console.log("error", xhr.responseText);
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

    function hapusKomentar(idKomentar) {
        swal.fire({
            title: "Hapus Komentar",
            text: "Komentar ini akan dihapus",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "HAPUS"
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
                    url: base_url + "pengumuman/hapuskomentar/" + idKomentar,
                    type: "GET",
                    success: function (data) {
                        console.log("result", data);
                        if (data) {
                            swal.fire({
                                title: "Sukses",
                                text: "Komentar dihapus",
                                icon: "success",
                                showCancelButton: false,
                            }).then(result => {
                                if (result.value) {
                                }
                            });
                        } else {
                            swal.fire({
                                title: "ERROR",
                                text: "Komentar tidak dihapus",
                                icon: "error",
                                showCancelButton: false,
                            });
                        }
                    }, error: function (xhr, status, error) {
                        console.log("error", xhr.responseText);
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

    function hapusReply(idReply) {
        swal.fire({
            title: "Hapus Balasan",
            text: "Balasan ini akan dihapus",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "HAPUS"
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
                    url: base_url + "pengumuman/hapusbalasan/" + idReply,
                    type: "GET",
                    success: function (data) {
                        console.log("result", data);
                        if (data) {
                            swal.fire({
                                title: "Sukses",
                                text: "Balasan dihapus",
                                icon: "success",
                                showCancelButton: false,
                            }).then(result => {
                                if (result.value) {
                                }
                            });
                        } else {
                            swal.fire({
                                title: "ERROR",
                                text: "Balasan tidak dihapus",
                                icon: "error",
                                showCancelButton: false,
                            });
                        }
                    }, error: function (xhr, status, error) {
                        console.log("error", xhr.responseText);
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

    /*
    function loadListText() {
        $.ajax({
            url: base_url + "pengumuman/getrunningtext",
            type: "GET",
            success: function (response) {
                console.log('page', response);
                createListText(response);
            }, error: function (xhr, status, error) {
                console.log("error", xhr.responseText);
            }
        });
    }
    */

    function createListText(data) {
        var html = '<table class="table w-100">';
        if (data.running_text.length > 0) {
            $.each(data.running_text, function (i, v) {
                html += '<tr> ' +
                    '<td>' + v.text + '</td>' +
                    '<td>' +
                    '<button class="btn btn-default btn-circle-micro" data-id="' + v.id_text + '" onclick="edit(this)"><i class="fa fa-pencil"></i></button>' +
                    '<button class="btn btn-default btn-circle-micro" data-id="' + v.id_text + '" onclick="hapusText(this)"><i class="fa fa-trash"></i></button>' +
                    '</td> ' +
                    '</tr>';
            });
        }
        html += '</table>';
        $('#list-text').html(html);
    }

    function hapusText(e) {
        var id = $(e).data('id');
        console.log(id);

        $.ajax({
            url: base_url + "pengumuman/hapusrunningtext/" + id,
            type: "GET",
            success: function (response) {
                console.log('page', response);
                if (!response) {
                    showDangerToast('Tidak bisa menghapus')
                } else {
                    loadListText()
                }
            }, error: function (xhr, status, error) {
                console.log("error", xhr.responseText);
                showDangerToast('Tidak bisa menghapus')
            }
        });
    }

    $(document).ready(function () {
        $('.editable').attr('contentEditable', true);
        console.log('guru', guru);
        $('.select2').select2();

        $('#text-pengumuman').summernote({
            placeholder: 'Tulis Pengumuman',
            tabsize: 2,
            minHeight: 100,
        });

        $('.toggle-comment').on('shown.bs.collapse', function (e) {
            var konten = $(this);
            var id = konten.data('id');
            var list = $(this).find('.media').length;
            if (list === 0) $(`#loadmore${id}`).click();
        });

        $('#formpengumuman').submit('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log("data:", $(this).serialize());

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
                url: base_url + "pengumuman/save",
                type: "POST",
                dataType: "JSON",
                data: $(this).serialize(),
                success: function (data) {
                    console.log("result", data);
                    if (data) {
                        swal.fire({
                            title: "Sukses",
                            text: "Pengumuman berhasil disimpan",
                            icon: "success",
                            showCancelButton: false,
                        }).then(result => {
                            if (result.value) {
                                window.location.href = base_url + 'pengumuman';
                            }
                        });
                    } else {
                        swal.fire({
                            title: "ERROR",
                            text: "Pengumuman Tidak Tersimpan",
                            icon: "error",
                            showCancelButton: false,
                        });
                    }
                }, error: function (xhr, status, error) {
                    console.log("error", xhr.responseText);
                    const err = JSON.parse(xhr.responseText)
                    swal.fire({
                        title: "Error",
                        text: err.Message,
                        icon: "error"
                    });
                }
            });
        });

        $('#komentarModal').on('show.bs.modal', function (e) {
            var id = $(e.relatedTarget).data('id');
            $("#id-post").val(id);

            var isVisible = $(`#collapse-${id}`).hasClass('show');
            if (!isVisible) {
                $(`#trigger${id}`).click();
            }
        });

        $('#balasanModal').on('show.bs.modal', function (e) {
            var id = $(e.relatedTarget).data('id');
            $("#id-comment").val(id);

            var isVisible = $(`#collapse-reply${id}`).hasClass('show');
            if (!isVisible) {
                $(`#trigger-reply${id}`).click();
            }
        });

        $('#komentar').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            console.log("data", $(this).serialize());
            var id = $(this).find('input[name=id_post]').val();

            $.ajax({
                url: base_url + "pengumuman/savekomentar",
                data: $(this).serialize(),
                method: 'POST',
                dataType: "JSON",
                success: function (response) {
                    console.log("result", response);
                    $('#komentarModal').modal('hide').data('bs.modal', null);
                    $('#komentarModal').on('hidden', function () {
                        $(this).data('modal', null);
                    });
                    addComments(id, response, false)
                    //window.location.href = base_url + 'pengumuman';
                },
                error: function (xhr, status, error) {
                    $('#komentarModal').modal('hide').data('bs.modal', null);
                    $('#komentarModal').on('hidden', function () {
                        $(this).data('modal', null);
                    });
                    showDangerToast('Error, komentar tidak terkirim');
                }
            });
        });

        $('#balasan').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            console.log("data", $(this).serialize());
            var id = $(this).find('input[name=id_comment]').val();

            $.ajax({
                url: base_url + "pengumuman/savebalasan",
                data: $(this).serialize(),
                method: 'POST',
                dataType: "JSON",
                success: function (response) {
                    console.log("result", response);
                    $('#balasanModal').modal('hide').data('bs.modal', null);
                    $('#balasanModal').on('hidden', function () {
                        $(this).data('modal', null);
                    });
                    //window.location.href = base_url + 'pengumuman';
                    addReplies(id, response, false)
                },
                error: function (xhr, status, error) {
                    $('#balasanModal').modal('hide').data('bs.modal', null);
                    $('#balasanModal').on('hidden', function () {
                        $(this).data('modal', null);
                    });
                    showDangerToast('Error, balasan tidak terkirim');
                }
            });
        });

        $('#formrunningtext').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var no = 1;
            var jsonObj = [];
            const $rows = $('#tb-text').find('tr');
            $rows.each((i, row) => {
                var id_text = no;
                const text = $(row).find('.editable').text();

                let item = {};
                item ["id_text"] = no;
                item ["text"] = text;
                jsonObj.push(item);
                no++;
            });

            var dataPost = $(this).serialize() + "&text=" + JSON.stringify(jsonObj);
            console.log(dataPost);

            $.ajax({
                url: base_url + "pengumuman/saverunningtext",
                type: "POST",
                data: dataPost,
                success: function (response) {
                    console.log('updates', response);
                    if (response.status[0]) {
                        window.location.reload();
                        //loadListText();
                        //$('#textarea-running').val('');
                    } else {
                        showDangerToast('Tidak bisa menyimpan')
                    }
                }, error: function (xhr, status, error) {
                    console.log("error", xhr.responseText);
                    showDangerToast('Error, Tidak bisa menyimpan')
                }
            });
        });

    })
