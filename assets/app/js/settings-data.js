    var __sd = document.getElementById('settings-data');
    var logoKanan = __sd ? (__sd.dataset.logoKanan || '') : '';
    var logoKiri = __sd ? (__sd.dataset.logoLiri || '') : '';
    var tandatangan = __sd ? (__sd.dataset.ttd || '') : '';
    var satuanPend = __sd ? JSON.parse(__sd.dataset.satuan || '[]') : [];

    function submitSetting() {
        $('#savesetting').submit();
    }

    $(document).ready(function () {
        ajaxcsrf();

        var drEvent = $('.dropify').dropify({
            messages: {
                'default': 'Seret logo kesini atau klik',
                'replace': 'Seret atau klik<br>untuk mengganti logo',
                'remove': 'Hapus',
                'error': 'Ooops, ada kesalahan!!.'
            },
            error: {
                'fileSize': 'The file size is too big ({{ value }} max).',
                'minWidth': 'The image width is too small ({{ value }}}px min).',
                'maxWidth': 'The image width is too big ({{ value }}}px max).',
                'minHeight': 'The image height is too small ({{ value }}}px min).',
                'maxHeight': 'The image height is too big ({{ value }}px max).',
                'imageFormat': 'The image format is not allowed ({{ value }} only).'
            }
        });


        drEvent.on('dropify.beforeClear', function (event, element) {
            //return confirm("Hapus logo \"" + element.file.name + "\" ?");
        });

        drEvent.on('dropify.afterClear', function (event, element) {
            deleteImage($(event.currentTarget).data('default-file'));
            if (element.element.id === 'logo-kanan') {
                logoKanan = '';
                $('#prev-logo-kanan').attr('src', '');
            } else if (element.element.id === 'logo-kiri') {
                logoKiri = '';
                $('#prev-logo-kiri').attr('src', '');
            } else if (element.element.id === 'tanda-tangan') {
                tandatangan = '';
                $('#prev-tandatangan').css(
                    {'background': 'url() no-repeat center'},
                    {'font-family': 'Times New Roman'},
                    {'font-size': '10pt'},
                    {'background-size': '100px 60px'}
                );
            }
        });

        drEvent.on('dropify.errors', function (event, element) {
            console.log('Has Errors');
            $.toast({
                heading: "Error",
                text: "file rusak",
                icon: 'warning',
                showHideTransition: 'fade',
                allowToastClose: true,
                hideAfter: 5000,
                position: 'top-right'
            });
        });

        $('#jenjang').change(function () {
            var htmlOptions = '<option value="0" disabled="">Satuan Pendidikan</option>\n';
            var satSelected = satuanPend[$(this).val()];
            for (let i = 0; i < satSelected.length; i++) {
                htmlOptions += '<option value="' + (i + 1) + '">' + satSelected[i] + '</option>';
            }
            $('#satuan-pend').html(htmlOptions);
        });

        $('#savesetting').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var hasInput = true;
            $('.required').each(function () {
                if ($(this).val() === "") {
                    hasInput = false;
                    return false;
                }
            });

            console.log(hasInput);

            if (!hasInput) {
                Swal.fire({
                    title: "ERROR",
                    text: "Isi semua pilihan yang bertanda bintang (*)",
                    icon: "error"
                });
            } else {
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
                    url: base_url + 'settings/savesetting',
                    type: 'POST',
                    data: $(this).serialize() + '&logo_kanan=' + logoKanan + '&logo_kiri=' + logoKiri + '&tanda_tangan=' + tandatangan,
                    success: function (response) {
                        console.log(response);
                        swal.fire({
                            title: "Sukses",
                            html: "Berhasil menyimpan pengaturan",
                            icon: "success",
                            showCancelButton: false,
                            confirmButtonColor: "#3085d6",
                        }).then(result => {
                            if (result.value) {
                                window.location.href = base_url + 'settings';
                            }
                        });
                    },
                    error: function (xhr, error, status) {
                        console.log(xhr.responseText);
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

        $("#logo-kanan").change(function () {
            var input = $(this)[0];
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#prev-logo-kanan').attr('src', e.target.result);
                };
                reader.readAsDataURL(input.files[0]);

                var form = new FormData($('#set-logo-kanan')[0]);
                uploadAttach(base_url + 'settings/uploadfile/logo_kanan', form);
            }
        });

        $("#logo-kiri").change(function () {
            var input = $(this)[0];
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#prev-logo-kiri').attr('src', e.target.result);
                };
                reader.readAsDataURL(input.files[0]);

                var form = new FormData($('#set-logo-kiri')[0]);
                uploadAttach(base_url + 'settings/uploadfile/logo_kiri', form);
            }
        });

        $("#tanda-tangan").change(function () {
            var input = $(this)[0];
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    //style="font-family: 'Times New Roman'; font-size: 10pt;"
                    $('#prev-tandatangan').css({'background': 'url(' + e.target.result + ') no-repeat center'}, {'font-family': 'Times New Roman'}, {'font-size': '10pt'}, {'background-size': '100px 60px'});
                    //$('#prev-tandatangan').attr('src', e.target.result);
                };
                reader.readAsDataURL(input.files[0]);

                var form = new FormData($('#set-tandatangan')[0]);
                uploadAttach(base_url + 'settings/uploadfile/tandatangan', form);
            }
        });

        function uploadAttach(action, data) {
            $.ajax({
                type: "POST",
                enctype: 'multipart/form-data',
                url: action,
                data: data,
                processData: false,
                contentType: false,
                cache: false,
                timeout: 600000,
                success: function (data) {
                    if (data.src.includes('kanan')) {
                        logoKanan = data.src;
                        //console.log('kanan', data.src);
                    } else if (data.src.includes('kiri')) {
                        logoKiri = data.src;
                        //console.log('kiri', data.src);
                    } else if (data.src.includes('tanda')) {
                        tandatangan = data.src;
                        //console.log('tandatangan', data.src);
                    }
                },
                error: function (e) {
                    console.log("error", e.responseText);
                    $.toast({
                        heading: "ERROR!!",
                        text: "file tidak terbaca",
                        icon: 'error',
                        showHideTransition: 'fade',
                        allowToastClose: true,
                        hideAfter: 5000,
                        position: 'top-right'
                    });
                }
            });
        }

        function deleteImage(src) {
            console.log(src);
            $.ajax({
                data: {src: src},
                type: "POST",
                url: base_url + "settings/deletefile",
                cache: false,
                success: function (response) {
                    console.log(response);
                }
            });
        }
    });
