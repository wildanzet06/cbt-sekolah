    $('.thumb-lg').css({'width': '6rem', 'height': '6rem'});

    function hapus(idGuru) {
        swal.fire({
            title: "Anda yakin?",
            text: "Data guru akan dihapus!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Hapus!"
        }).then(result => {
            if (result.value) {
                $.ajax({
                    url: base_url + 'dataguru/deleteguru',
                    data: $('#hapus-guru').serialize() + '&id_guru=' + idGuru,
                    type: "POST",
                    success: function (respon) {
                        console.log(respon);
                        if (respon.status) {
                            swal.fire({
                                title: "Berhasil",
                                text: "Data guru berhasil dihapus",
                                icon: "success"
                            }).then(result => {
                                if (result.value) {
                                    window.location.reload(true);
                                }
                            });
                        } else {
                            if (respon.count > 0) {
                                window.location.href = base_url + 'dataguru/detail/' + idGuru
                            } else {
                                swal.fire({
                                    title: "Gagal",
                                    html: respon.message,
                                    icon: "error"
                                });
                            }
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
            }
        });
    }

    $(document).ready(function () {
        $("#list-guru").DataTable({
            paging: false,
            ordering: false,
            info: false,
        });
        /*
        $(`img.avatar`).each(function () {
            $(this).on("error", function () {
                console.log('foto', $(this).attr('src'));
                $(this).attr("src", base_url + '/assets/img/siswa.png'); // default foto
            });
        });
         */
    });
