    $(document).ready(function () {
        $("#tahun").on("click", ".btn-aktif", function () {
            let id = $(this).data("id");
            var dataTahun = JSON.stringify($('#tahun').tableToJSON());
            var replaced = dataTahun.replace(/Tahun Pelajaran/g, "tp");

            //console.log($('#edittp').serialize() + "&active=" + id + "&tahun=" + replaced);
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
                url: base_url + "datatahun/gantitahun",
                data: $('#edittp').serialize() + "&active=" + id + "&tahun=" + replaced,
                type: "POST",
                success: function (response) {
                    var title = response.status ? "Berhasil" : "Gagal";
                    var type = response.status ? "success" : "error";

                    swal.fire({
                        title: title,
                        text: response.msg,
                        icon: type
                    }).then((result) => {
                        if (result.value) {
                            if (response.status) {
                                window.location.href = base_url + 'datatahun';
                            }
                        }
                    });
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

        $('#createTahunModal').on('show.bs.modal', function (e) {
            var method = $(e.relatedTarget).data('from');
            $(e.currentTarget).find('input[id="method"]').val(method);

            if (method === 'edit') {
                $('#createModalLabel').text('Edit Tahun');
                var id = $(e.relatedTarget).data('id');
                var tahun = $(e.relatedTarget).data('tahun');

                $(e.currentTarget).find('input[id="editIdTahun"]').val(id);
                $(e.currentTarget).find('input[id="createtahun"]').val(tahun);

                var attrId = document.getElementById("editIdTahun");
                attrId.setAttribute("name", "id_tahun");
            } else {
                $('#createModalLabel').text('Tambah Tahun');
                $(e.currentTarget).find('input[id="editIdTahun"]').val('');
                $(e.currentTarget).find('input[id="createtahun"]').val('');
            }
        });

        $("#tahun").on("click", ".btn-hapus", function () {
            let id = $(this).data("id");
            swal.fire({
                title: 'Hapus Tahun',
                text: 'Anda yakin akan menghapus Tahun Pelajaran? tindakan ini akan membuat data yang berhubungan tidak aktif',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Hapus"
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: base_url + "datatahun/hapustahun",
                        data: $('#edittp').serialize() + "&hapus=" + id,
                        type: "POST",
                        success: function (response) {
                            var title = response.status ? "Berhasil" : "Gagal";
                            var type = response.status ? "success" : "error";

                            swal.fire({
                                title: title,
                                text: response.msg,
                                icon: type
                            }).then((result) => {
                                if (result.value) {
                                    if (response.status) {
                                        window.location.href = base_url + 'datatahun';
                                    }
                                }
                            });
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
        });

        $("#semester").on("click", ".btn-aktif", function () {
            let id = $(this).data("id");
            var dataSmt = JSON.stringify($('#semester').tableToJSON());
            //console.log($('#edittp').serialize() + "&active=" + id + "&tahun=" + replaced);
            $.ajax({
                url: base_url + "datatahun/gantisemester",
                data: $('#edittp').serialize() + "&active=" + id + "&semester=" + dataSmt,
                type: "POST",
                success: function (response) {
                    var title = response.status ? "Berhasil" : "Gagal";
                    var type = response.status ? "success" : "error";

                    swal.fire({
                        title: title,
                        text: response.msg,
                        icon: type
                    }).then((result) => {
                        if (result.value) {
                            if (response.status) {
                                window.location.href = base_url + 'datatahun';
                            }
                        }
                    });
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

        $('#create').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var form = new FormData($('#create')[0])
            console.log("data:", Object.fromEntries(form));

            $.ajax({
                url: base_url + "datatahun/add",
                type: "POST",
                data: form,
                processData: false,
                contentType: false,
                cache: false,
                success: function (data) {
                    location.href = base_url + 'datatahun';
                }, error: function (xhr, status, error) {
                    $('#createTahunModal').modal('hide').data('bs.modal', null);
                    $('#createTahunModal').on('hidden', function () {
                        $(this).data('modal', null);  // destroys modal
                    });
                    showDangerToast('Gagal menambah tahun pelajaran');
                }
            });
            return false;
        });

        /*
        $('#hariefektif').submit('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log("data:", $(this).serialize());

            $.ajax({
                url: base_url + "datatahun/savehariefektif",
                type: "POST",
                dataType: "JSON",
                data: $(this).serialize(),
                success: function (data) {
                    if (data.status) {
                        swal.fire({
                            title: "Sukses",
                            text: "Jumlah Hari Efektif berhasi disimpan",
                            icon: "success"
                        }).then((result) => {
                            if (result.value) {
                                if (data.status) {
                                    window.location.href = base_url + 'datatahun';
                                }
                            }
                        });
                    } else {
                        swal.fire({
                            title: "Gagal",
                            text: 'Gagal menyimpan jumlah hari efektif',
                            icon: "error",
                        });
                    }
                }, error: function (xhr, status, error) {
                    const err = JSON.parse(xhr.responseText)
                    swal.fire({
                        title: "Error",
                        text: err.Message,
                        icon: "error"
                    });
                }
            });
        });
         */
    })
