var table;

$(document).ready(function() {
	ajaxcsrf();

	table = $("#users").DataTable({
		initComplete: function() {
			var api = this.api();
			$("#users_filter input")
				.off(".DT")
				.on("keyup.DT", function(e) {
					api.search(this.value).draw();
				});
		},
		oLanguage: {
			sProcessing: "loading..."
		},
		processing: true,
		serverSide: true,
		ajax: {
			url: base_url + "userguru/data",
			type: "POST"
		},
		columns: [
			{
				data: "id_guru",
				className: "text-center",
				orderable: false,
				searchable: false
			},
			{ data: "nama_guru" },
			{ data: "username" },
			{ data: "level"},
			{ data: "password" },
			{ data: "reset" },
			{ data: "aktif" }
		],
		columnDefs: [
			{
				searchable: false,
				orderable: false,
				className: "text-center",
				targets: 4,
				render: function(data, type, row, meta) {
					return `<div class="td-password" data-username="${row.username}">
								<button type="button" class="btn btn-lihat-password btn-default btn-xs"
									data-id="${row.id_guru}" data-toggle="tooltip" title="Lihat password awal"><i class="fa fa-eye m-1"></i></button>
								<span class="pw-awal d-none"></span>
							</div>`;
				}
			},
			{
				searchable: false,
				className: "text-center",
				targets: 5,
				render: function(data, type, row, meta) {
					return `<button type="button" class="btn btn-reset btn-default btn-xs ${row.reset == 0 ? 'btn-disabled' : ''}"
 								data-username="${row.username}" data-nama="${row.nama_guru}" data-toggle="tooltip" title="Reset Login"
								 ${row.reset == 0 ? 'disabled' : ''}><i class="fa fa-sync m-1"></i></button>`;
				}
			},
			{
				searchable: false,
				className: "text-center",
				targets: 6,
				render: function(data, type, row, meta) {
					let btn;
					if (row.aktif > 0) {
						btn = `<button type="button" class="btn btn-nonaktif btn-danger btn-xs" data-id="${row.id}" data-nama="${row.nama_guru}" data-toggle="tooltip" title="Nonaktifkan">
								<i class="fa fa-ban m-1"></i>
							</button>`;
					} else {
						btn = `<button type="button" class="btn btn-aktif btn-success btn-xs" data-id="${row.id_guru}" data-toggle="tooltip" title="Aktifkan">
								<i class="fa fa-user-plus m-1"></i>
							</button>`;
					}
					return btn;
				}
			}
		],
		//order: [[4, "desc"]],
		rowId: function(a) {
			return a;
		},
		rowCallback: function(row, data, iDisplayIndex) {
			var info = this.fnPagingInfo();
			var page = info.iPage;
			var length = info.iLength;
			var index = page * length + (iDisplayIndex + 1);
			$("td:eq(0)", row).html(index);
		},
		//createdRow: function( row, data, dataIndex ) {
		//	console.log(data);
		//}
	});

	table
		.buttons()
		.container()
		.appendTo("#users_wrapper .col-md-6:eq(0)");

	// Password tidak dikirim bersama daftar. Admin memintanya satu per satu.
	$("#users").on("click", ".btn-lihat-password", function() {
		let tombol = $(this);
		let sel = tombol.closest(".td-password");
		$.ajax({
			url: base_url + "akun/lihat_password",
			type: "POST",
			dataType: "json",
			data: {id_guru: tombol.attr("data-id")},
			success: function(res) {
				sel.find(".pw-awal").text(res && res.password ? res.password : "-").removeClass("d-none");
				tombol.remove();
			},
			error: function() {
				swal.fire({title: "Gagal", text: "Password tidak dapat ditampilkan.", icon: "error"});
			}
		});
	});

	$("#users").on("click", ".btn-aktif", function() {
		let id = $(this).data("id");
		$.ajax({
			url: base_url + "userguru/activate/" + id,
			type: "GET",
			success: function (response) {
				console.log("pass", response.pass);
				if (response.msg) {
					if (response.status) {
						swal.fire({
							title: "Sukses",
							text: response.msg,
							icon: "success"
						});
						reload_ajax();
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
		let id = $(this).data("id");
		let nama = $(this).data("nama");
		$.ajax({
			url: base_url + "userguru/deactivate/" + id,
			type: "GET",
			success: function (response) {
				if (response.msg) {
					if (response.status) {
						swal.fire({
							title: "Sukses",
							text: nama + ' ' + response.msg,
							icon: "success"
						});
						reload_ajax();
					} else {
						swal.fire({
							title: "Error",
							text: response.msg,
							icon: "error"
						});
					}
				}
			}
		});
	});

	$("#users").on("click", ".btn-reset", function() {
		let username = $(this).data("username");
		let nama = $(this).data("nama");
		$.ajax({
			url: base_url + "userguru/reset_login?username="+ username,
			type: "GET",
			success: function (response) {
				if (response.msg) {
					if (response.status) {
						swal.fire({
							title: "Sukses",
							html: "<b>"+nama+ response.msg+"<b>",
							icon: "success"
						});
						reload_ajax();
					} else {
						swal.fire({
							title: "Error",
							html: "<b>"+nama + response.msg+"<b>",
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
    	console.log('click');
        let action = $(this).data("action");
        let uri = action === 'aktifkan' ? base_url + "userguru/aktifkansemua" : base_url + "userguru/nonaktifkansemua";
        swal.fire({
            title: action === 'aktifkan' ? "Aktifan Semua Guru" : "Nonaktifkan Semua Guru",
            text: "",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Lanjutkan"
        }).then(result => {
            if (result.value) {
                $('#loading').removeClass('d-none');
                $.ajax({
                    url: uri,
                    type: "GET",
                    success: function (response) {
                        $('#loading').addClass('d-none');
                        console.log("result", response.jumlah);
                        swal.fire({
                            title: response.status ? "Sukses" : "Gagal",
                            text: response.msg,
                            icon: response.status ? "success" : "error"
                        }).then(result => {
                            reload_ajax();
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
});

function editLogin(id) {
	console.log("id", id);
	window.location.href = base_url + "userguru/edit/"+id;
}
