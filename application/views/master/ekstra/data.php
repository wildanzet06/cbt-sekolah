<div class="content-wrapper bg-white pt-4">
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
            <div class="card my-shadow mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-default"><i
                                    class="fa fa-sync"></i> Reload
                        </button>
                        <button type="button" data-toggle="modal" data-target="#createEkstraModal"
                                class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Ekskul
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 table-responsive">
                            <table id="tableEkstra" class="w-100 table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="text-center align-middle p-0">No.</th>
                                    <th>Ekstrakurikuler</th>
                                    <th>Kode</th>
                                    <th class="text-center align-middle p-0" style="width: 100px"><span>Aksi</span></th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <?= form_open('save', array('id' => 'save')) ?>
                            <div class="card border mt-1">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h6>Pemetaan Kelas (opsional)</h6>
                                    </div>
                                    <div class="card-tools">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa fa-save mr-1"></i>Simpan
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info py-2 small">
                                        Siswa sekarang <b>bebas memilih sendiri</b> ekstrakurikuler melalui menu
                                        <b>Ekstrakurikuler</b> di akun siswa. Pemetaan kelas di bawah hanya
                                        dipertahankan sebagai data lama/opsional.
                                    </div>
                                    <?php
                                    if (count($kelas) > 0) :
                                        foreach ($kelas as $key => $kls) :
                                            $ids_selected = [];
                                            if ($ekskul_kelas[$key] != null) {
                                                $ids = unserialize($ekskul_kelas[$key][0]->ekstra ?? '', ['allowed_classes' => ['stdClass']]);
                                                foreach ($ids as $id) {
                                                    array_push($ids_selected, $id['ekstra']);
                                                }
                                            }

                                            ?>
                                            <div class="input-group input-group-sm mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Kelas <?= $kls ?></span>
                                                </div>
                                                <?php
                                                echo form_dropdown(
                                                    'ekskul' . $key . '[]',
                                                    $ekskul,
                                                    $ids_selected,
                                                    'id="ekskul' . $key . '" class="select2 form-control form-control-sm" multiple="multiple" data-placeholder="Pilih Ekskul"'
                                                ); ?>
                                            </div>
                                        <?php endforeach;
                                    else: ?>
                                        <div class="alert alert-default-warning" role="alert">Belum ada data kelas</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?= form_close() ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('create', array('id' => 'create')) ?>
<div class="modal fade" id="createEkstraModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Ekstrakurikuler</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group row">
                    <label class="col-md-3 col-form-label">Ekstrakurikuler*</label>
                    <div class="col-md-9">
                        <input type="text" id="createnama" name="nama_ekstra" class="form-control" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label">Kode*</label>
                    <div class="col-md-9">
                        <input type="text" id="createkode" name="kode_ekstra" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<?= form_open('update', array('id' => 'update')) ?>
<div class="modal fade" id="editEkstraModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Ekstrakurikuler</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group row">
                    <label class="col-md-3 col-form-label">Ekstrakurikuler*</label>
                    <div class="col-md-9">
                        <input type="text" id="namaEdit" class="form-control" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label">Kode*</label>
                    <div class="col-md-9">
                        <input type="text" id="kodeEdit" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="editIdEkstra" class="form-control">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>


<div id="ekstra-data" data-json="<?= html_escape(json_encode($kelas, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)) ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/master-ekstra-data.js"></script>
</script>
