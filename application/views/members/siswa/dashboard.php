<style>
    /* ---- Menu utama: ikon dan teks proporsional di HP ---- */
    .menu-utama figure { margin-bottom: 0; }
    .menu-utama a { display: block; padding: .35rem .15rem; border-radius: .5rem; }
    .menu-utama a:active { background: rgba(0, 0, 0, .06); }
    .menu-utama img { width: 60px; height: 60px; object-fit: contain; }
    .menu-utama figcaption { font-size: .8rem; line-height: 1.15; margin-top: .3rem; color: #343a40; }
    @media (min-width: 768px) {
        .menu-utama img { width: 80px; height: 80px; }
        .menu-utama figcaption { font-size: .95rem; }
    }
    /* ---- Kartu dan tabel jadwal ---- */
    .tabel-jadwal td { padding: .45rem .5rem; vertical-align: middle; }
    @media (max-width: 575.98px) {
        .content-wrapper .card-title { font-size: 1rem; }
        .konten-pengumuman .card-body { padding: .75rem; }
    }
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <!-- Main content -->
    <div class="sticky">
    </div>
    <section class="content overlap p-2 p-md-4">
        <div class="container">
            <?php $this->load->view('members/siswa/templates/top'); ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-blue">
                        <div class="card-header">
                            <div class="card-title text-white">
                                MENU UTAMA
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            // Menu tambahan: siswa/orang tua dapat memantau poin pelanggaran sendiri
                            $menu[] = (object) array(
                                'title' => 'Poin Pelanggaran',
                                'icon'  => 'ic_document.png',
                                'link'  => 'akun/pelanggaran',
                            );
                            $menu[] = (object) array(
                                'title' => 'Ekstrakurikuler',
                                'icon'  => 'ic_document.png',
                                'link'  => 'siswa/ekstra',
                            );
                            ?>
                            <div class="row menu-utama">
                                <?php foreach ($menu as $m): ?>
                                    <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                        <a href="<?= base_url($m->link) ?>">
                                            <figure class="text-center">
                                                <img class="img-fluid"
                                                     src="<?= base_url() ?>/assets/app/img/<?= $m->icon ?>" width="80"
                                                     height="80"/>
                                                <figcaption><?= $m->title ?></figcaption>
                                            </figure>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-7 order-2 order-md-1">
                    <div class="card card-success">
                        <div class="card-header">
                            <div class="card-title text-white">
                                INFO/PENGUMUMAN
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="konten-pengumuman">
                                <div id="pengumuman">
                                </div>
                                <p id="loading-post" class="text-center d-none">
                                    <br/><i class="fa fa-spin fa-circle-o-notch"></i> Loading....
                                </p>
                                <div id="loadmore-post"
                                     onclick="getPosts()"
                                     class="text-center mt-4 loadmore d-none">
                                    <div class="btn btn-default">Muat Timeline lainnya ...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5 order-1 order-md-2">
                    <div class="card card-red">
                        <div class="card-header">
                            <div class="card-title text-white">
                                JADWAL HARI INI
                            </div>
                            <div class="card-tools">
                                <button type="button" onclick="loadJadwal()" class="btn btn-sm">
                                    <i class="fa fa-sync text-white"></i> <span
                                            class="d-none d-sm-inline-block ml-1 text-white">Reload</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($kbms != null) :
                                if (count($jadwals) > 0):
                                    $no = 1;
                                    $arrIst = [];
                                    if (isset($kbms->istirahat)) {
                                        foreach ($kbms->istirahat as $istirahat) {
                                            array_push($arrIst, $istirahat['ist']);
                                            $arrDur[$istirahat['ist']] = $istirahat['dur'];
                                        }
                                    }
                                    $active = $no == 1 ? 'active' : '';
                                    ?>

                                    <div class="table-responsive">
                                        <table class="w-100 table table-sm tabel-jadwal">
                                            <tbody>
                                            <?php
                                            $jamMulai = new DateTime($kbms->kbm_jam_mulai);
                                            $jamSampai = new DateTime($kbms->kbm_jam_mulai);
                                            for ($i = 0; $i < $kbms->kbm_jml_mapel_hari; $i++) :
                                                $jamke = $i + 1;
                                                if (in_array($jamke, $arrIst)) :
                                                    $jamSampai->add(new DateInterval('PT' . $arrDur[$jamke] . 'M'));
                                                    ?>
                                                    <tr class="jam" data-jamke="<?= $jamke ?>">
                                                        <td class="align-middle text-nowrap">
                                                            <?= $jamMulai->format('H:i') ?>
                                                            - <?= $jamSampai->format('H:i') ?>
                                                        </td>
                                                        <td class="align-middle">ISTIRAHAT</td>
                                                    </tr>
                                                    <?php
                                                    $jamMulai->add(new DateInterval('PT' . $arrDur[$jamke] . 'M'));
                                                else :
                                                    $jamSampai->add(new DateInterval('PT' . $kbms->kbm_jam_pel . 'M'));
                                                    ?>
                                                    <tr class="jam" data-jamke="<?= $jamke ?>">
                                                        <td class="align-middle text-nowrap">
                                                            <?= $jamMulai->format('H:i') ?>
                                                            - <?= $jamSampai->format('H:i') ?>
                                                        </td>
                                                        <td class="align-middle">
                                                            <?= $jadwals[$jamke]->kode != null ? $jadwals[$jamke]->kode : '--' ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $jamMulai->add(new DateInterval('PT' . $kbms->kbm_jam_pel . 'M'));
                                                endif; endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p>
                                        Tidak ada jadwal hari ini
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>
                                    Jadwal untuk kelas <?= $siswa->nama_kelas ?> belum dibuat
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="pengumumanModal" tabindex="-1" role="dialog" aria-labelledby="previewLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="user-block">
                    <img id="foto" class="img-circle" src="<?= base_url() ?>/assets/img/user.jpg" alt="User Image">
                    <span id="username" class="username">test</span>
                    <span id="tgl" class="description">aja</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div id="isi-pengumuman"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="komentarModal" tabindex="-1" role="dialog" aria-labelledby="komentarLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="komentarLabel">Tulis Komentar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img class="img-fluid img-circle img-sm" src="<?= base_url('assets/img/siswa.png') ?>" alt="Alt Text">
                <div class="img-push">
                    <?= form_open('create', array('id' => 'komentar')) ?>
                    <input type="hidden" id="id-post" name="id_post" value="">
                    <div class="input-group">
                        <input type="text" name="text" placeholder="Tulis komentar ..."
                               class="form-control form-control-sm" required>
                        <span class="input-group-append">
                                <button type="submit" class="btn btn-success btn-sm">Komentari</button>
                            </span>
                    </div>
                    <?= form_close() ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="balasanModal" tabindex="-1" role="dialog" aria-labelledby="balasanLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="balasanLabel">Tulis Balasan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img class="img-fluid img-circle img-sm" src="<?= base_url('assets/img/siswa.png') ?>" alt="Alt Text">
                <div class="img-push">
                    <?= form_open('create', array('id' => 'balasan')) ?>
                    <input type="hidden" id="id-comment" name="id_comment" value="">
                    <div class="input-group">
                        <input type="text" name="text" placeholder="Tulis balasan ...."
                               class="form-control form-control-sm" required>
                        <span class="input-group-append">
                                <button type="submit" class="btn btn-success btn-sm">Balas</button>
                            </span>
                    </div>
                    <?= form_close() ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<div id="dashboard-config" data-kelas="<?= html_escape($siswa->id_kelas) ?>" data-kode="<?= html_escape($siswa->kode_kelas) ?>" data-siswa="<?= html_escape($siswa->id_siswa) ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/siswa-dashboard.js"></script>
</script>
