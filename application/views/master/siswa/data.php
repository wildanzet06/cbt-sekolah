<style>
    .siswa-tools { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: flex-end; }
    .siswa-tools .btn { display: inline-flex; align-items: center; gap: .35rem; border-radius: 6px; }
    .siswa-search { position: relative; max-width: 380px; margin-left: auto; }
    .search-suggest { position: absolute; top: calc(100% + 2px); left: 0; right: 0; z-index: 1050;
        background: #fff; border: 1px solid #dee2e6; border-radius: 6px;
        box-shadow: 0 6px 18px rgba(0,0,0,.12); max-height: 260px; overflow: auto; }
    .search-suggest .suggest-item { display: block; width: 100%; text-align: left; border: 0;
        background: none; padding: .5rem .7rem; border-bottom: 1px solid #f1f3f5; font-size: .85rem; }
    .search-suggest .suggest-item:last-child { border-bottom: 0; }
    .search-suggest .suggest-item:hover, .search-suggest .suggest-item:focus { background: #f1f5f9; outline: 0; }
    .search-suggest .suggest-meta { color: #6c757d; font-size: .75rem; }
    @media (max-width: 575.98px) {
        .siswa-tools { width: 100%; justify-content: stretch; }
        .siswa-tools .btn { flex: 1 1 30%; justify-content: center; font-size: .78rem; padding: .4rem .3rem; }
        .siswa-search { width: 100%; max-width: none; margin-left: 0; }
        .dataTables_filter { text-align: left !important; }
        .dataTables_length { margin-bottom: .5rem; }
    }
</style><div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                    <div class="card-tools siswa-tools">
                        <button type="button" data-toggle="modal" data-target="#createSiswaModal"
                                class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i><span>Tambah Siswa</span>
                        </button>
                        <a href="<?= base_url('datasiswa/add') ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-upload"></i><span>Import</span></a>
                        <a href="<?= base_url('datasiswa/update') ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-database"></i><span>Update Data</span></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <div class="dataTables_length">
                                    <label>Show
                                        <select id="users_length" aria-controls="users" class="custom-select custom-select-sm form-control form-control-sm">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="siswa-search">
                                    <input id="input-search" type="search" class="form-control form-control-sm"
                                           placeholder="Cari nama / NIS..." autocomplete="off" aria-controls="users">
                                    <div id="search-suggest" class="search-suggest d-none"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-2 d-flex flex-row justify-content-between">
                                <!--
                                <button id="hapusterpilih" onclick="bulk_delete()" type="button" class="btn btn-danger" data-toggle="tooltip" title="Hapus Terpilh" disabled="disabled">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                                -->
                                <div class="dropdown">
                                    <button id="dropdown-btn" class="btn btn-danger dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" disabled="disabled">
                                        Aksi
                                    </button>
                                    <div id="dropdown-action" class="dropdown-menu">
                                        <a class="dropdown-item" id="pindah" href="#">Set sebagai PINDAH</a>
                                        <a class="dropdown-item" id="keluar" href="#">Set sebagai KELUAR</a>
                                        <a class="dropdown-item" id="hapus" href="#">HAPUS</a>
                                    </div>
                                </div>
                                <div class="d-flex flex-row align-items-center">
                                    <span>Filter </span>
                                    <select id="users-filter" class="ml-2 form-control form-control-sm">
                                        <option value="1">Aktif</option>
                                        <option value="5">Tanpa Kelas</option>
                                        <option value="3">Pindah</option>
                                        <option value="4">Keluar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <?= form_open('datasiswa/delete', array('id' => 'bulk')); ?>
                                <div class="table-responsive">
                                    <table id="table-siswa" class="w-100 table table-md table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th height="50" class="align-middle text-center p-0">
                                                <input class="select_all" type="checkbox">
                                            </th>
                                            <th class="align-middle text-center p-0">No.</th>
                                            <th class="align-middle">NAMA & KELAS</th>
                                            <th class="align-middle">NIS & NISN</th>
                                            <th class="align-middle text-center p-0">Aksi</th>
                                        </tr>
                                        </thead>
                                        <tbody id="table-body">
                                        </tbody>
                                    </table>
                                </div>
                                <?= form_close() ?>
                            </div>
                            <div class="col-12">
                                <nav aria-label="Page navigation" class="float-right">
                                    <ul class="pagination" id="pagination"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overlay" id="loading">
                    <div class="spinner-grow"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('', array('id' => 'formsiswa')); ?>
<div class="modal fade" id="createSiswaModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Siswa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nama_siswa">Nama Siswa :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input id="nama_siswa" type="text" class="form-control" name="nama_siswa"
                                   placeholder="Nama Siswa" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nis">NIS :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="number" id="nis" class="form-control" name="nis" placeholder="NIS" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nisn">NISN :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="number" id="nisn" class="form-control" name="nisn" placeholder="NISN" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="jenis_kelamin" class="control-label">Jenis Kelamin :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                            </div>
                            <select class="form-control" id="jenis_kelamin" data-placeholder="Jenis Kelamin"
                                    name="jenis_kelamin">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="kelas_awal">Kelas Awal :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                            </div>
                            <?php
                            if ($setting->jenjang == 1) {
                                $opsis ['1'] = '1';
                                $opsis ['2'] = '2';
                                $opsis ['3'] = '3';
                                $opsis ['4'] = '4';
                                $opsis ['5'] = '5';
                                $opsis ['6'] = '6';
                            } elseif ($setting->jenjang == 2) {
                                $opsis ['7'] = '7';
                                $opsis ['8'] = '8';
                                $opsis ['9'] = '9';
                            } else {
                                $opsis ['10'] = '10';
                                $opsis ['11'] = '11';
                                $opsis ['12'] = '12';
                            };
                            ?>
                            <select class="form-control" id="kelas_awal" data-placeholder="Pilih Kelas"
                                    name="kelas_awal">
                                <option value="">Pilih Kelas Awal</option>
                                <?php foreach ($opsis as $kelas) : ?>
                                    <option value="<?= $kelas ?>"><?= $kelas ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="tahun_masuk">Tanggal Diterima :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
								<span class="input-group-text">
									<i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" name="tahun_masuk" id="tahunmasuk" class="form-control"
                                   autocomplete="off" placeholder="Tgl/Tahun Masuk" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="username">Username :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input id="username" type="text" class="form-control" name="username" placeholder="Username"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="password">Password :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input id="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="reset" class="btn bg-warning text-white">
                    <i class="fa fa-sync mr-1"></i> Reset
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<?= form_open('', array('id' => 'pager')); ?>
<input type="hidden" id="pager-page" name="page" value="1">
<input type="hidden" id="pager-limit" name="limit" value="10">
<?= form_close() ?>

<script src="<?=base_url()?>/assets/app/js/jquery.twbsPagination.js" type="text/javascript"></script>
<script src="<?= base_url() ?>assets/app/js/master-siswa-data.js"></script>
</script>
