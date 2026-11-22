<?php
/**
 * Created by IntelliJ IDEA.
 * User: multazam
 * Date: 07/07/20
 * Time: 17:20
 */
?>

<div class="content-wrapper bg-white pt-4">
    <!--
	<section class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1><?= $judul ?></h1>
				</div>
			</div>
		</div>
	</section>
	-->
    <section class="content">
        <div class="container">
            <div class="row">
                <?php
                $col = '';
                if ($this->ion_auth->is_admin()) :
                    $col = 'col-md-7';
                    ?>
                    <div class="col-12 col-md-5 mb-4">
                        <div class="card my-shadow border">
                            <div class="card-header">
                                Running Text
                            </div>
                            <div class="card-body">
                                <div class="alert alert-default-info align-content-center" role="alert">
                                    RUNNING TEXT akan muncul di bagian bawah layar siswa.
                                </div>

                                <table id="tb-text" class="table mb-2 table-bordered">
                                    <?php
                                    for ($i = 0; $i < 5; $i++) :
                                        $text = isset($running_text[$i]) ? $running_text[$i]->text : '';
                                        ?>
                                        <tr>
                                            <td width="30" class="text-sm text-center"><?= $i + 1 ?></td>
                                            <td class="text-sm editable"><?= $text ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                </table>
                                <?= form_open('', array('id' => 'formrunningtext')) ?>
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-save"></i> Simpan
                                </button>
                                <?= form_close(); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-12 <?= $col ?> mb-4">
                    <div class="card my-shadow border">
                        <?= form_open('', array('id' => 'formpengumuman')) ?>
                        <div class="card-header">
                            Tulis Info/Pengumuman
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-2">
                                    <p>Kepada:</p>
                                </div>
                                <div class="col-10">
                                    <select id="opsi-kepada" name="kepada[]" class="select2 form-control"
                                            multiple="multiple" required>
                                        <option value='guru'>Semua Guru</option>
                                        <option value='siswa'>Semua Siswa</option>
                                        <?php foreach ($kelas as $key => $value) : ?>
                                            <option class="opsi-kelas" value="<?= $key ?>"><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <textarea class="w-100" id="text-pengumuman" name="text" required></textarea>

                            <?php if ($this->ion_auth->is_admin()): ?>
                                <input type="hidden" name="dari" value="0">
                            <?php else: ?>
                                <input type="hidden" name="dari" value="<?= $guru->id_guru ?>">
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-save"></i> Simpan
                            </button>
                        </div>
                        <?= form_close(); ?>
                    </div>
                </div>
                <!--
                <div class="col-md-4">
                    <div class="card my-shadow border">
                        <div class="card-header">
                            <h3 class="card-title">Pengumuman anda</h3>
                        </div>
                        <div class="card-body p-0" style="display: block;">
                            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview">
                                <li class="nav-item">
                                    <a href="<?= base_url('pengumuman') ?>" class="nav-link">
                                        <i class="nav-icon fas fa-users-cog"></i>
                                        <p>Ke Guru & Siswa<span class="right badge badge-danger">10</span></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('pengumuman/kepada/semua_guru') ?>" class="nav-link">
                                        <i class="nav-icon fas fa-user"></i>
                                        <p>Ke Semua Guru<span class="right badge badge-danger">10</span></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('pengumuman/kepada/semua_siswa') ?>" class="nav-link">
                                        <i class="nav-icon fa fa-users"></i>
                                        <p>Ke Semua Siswa<span class="right badge badge-danger">10</span></p>
                                    </a>
                                </li>
                                <li class="nav-item has-treeview">
                                    <a href="#" class="nav-link">
                                        <i class="nav-icon fas fa-building"></i>
                                        <p>Ke Kelas<i class="fas fa-angle-left right"></i></p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <?php foreach ($kelas as $key => $value) : ?>
                                            <li class="nav-item">
                                                <a href="<?= base_url('pengumuman/kepada/' . $key) ?>" class="nav-link">
                                                    <i class="fa fa-users nav-icon"></i>
                                                    <p><?= $value ?><span class="right badge badge-danger">10</span></p>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                -->
                <div class="col-md-12">
                    <div class="card my-shadow">
                        <div class="card-header">
                            <div class="card-title">
                                <?= $subjudul ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            //echo '<pre>';
                            //var_dump($posts);
                            //echo '</pre>';
                            foreach ($pengumumans as $pengumuman) : ?>
                                <div class="card">
                                    <div class="card-body" id="parent<?= $pengumuman->id_post ?>">
                                        <div class="media">
                                            <?php if ($pengumuman->dari == '0') : ?>
                                                <span class="btn-circle btn-success media-left pt-1">A</span>
                                                <div class="media-body ml-3">
                                                    <span class="font-weight-bold">Admin</b></span>
                                                    <br/>
                                                    <span class="text-gray"><?= buat_tanggal(date('D, d M Y H:i', strtotime($pengumuman->tanggal))) ?>
                                            </span>
                                                </div>
                                            <?php else: ?>
                                                <img class="img-circle media-left"
                                                     src="<?= $pengumuman->foto != null ? base_url() . $pengumuman->foto : base_url('assets/img/siswa.png') ?>"
                                                     width="50" height="50"/>
                                                <div class="media-body ml-3">
                                                    <span class="font-weight-bold"><?= $pengumuman->nama_guru ?></span>
                                                    <br/>
                                                    <span class="text-gray"> <?= $pengumuman->tanggal ?> </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2">
                                            <?= $pengumuman->text ?>
                                        </div>
                                        <div class="text-muted">
                                            <button type="button" class="btn btn-default btn-sm mr-2 btn-toggle"
                                                    data-id="<?= $pengumuman->id_post ?>" data-toggle="modal"
                                                    data-target="#komentarModal"><i class="fas fa-reply mr-1"></i> Tulis
                                                komentar
                                            </button>
                                            <button type="button" id="trigger<?= $pengumuman->id_post ?>"
                                                    class="btn btn-default btn-sm mr-2 action-collapse"
                                                    data-toggle="collapse" aria-expanded="true"
                                                    aria-controls="collapse-<?= $pengumuman->id_post ?>"
                                                    href="#collapse-<?= $pengumuman->id_post ?>">
                                                <i class="fa fa-commenting-o mr-1"></i><?= $pengumuman->jml ?> komentar
                                            </button>
                                            <button type="button" class="btn btn-default btn-sm"
                                                    onclick="hapusPost(<?= $pengumuman->id_post ?>)"
                                                    data-id="<?= $pengumuman->id_post ?>">
                                                <i class="fa fa-trash mr-1"></i> Hapus
                                            </button>

                                        </div>
                                    </div>
                                    <div id="collapse-<?= $pengumuman->id_post ?>" class="p-2 collapse toggle-comment"
                                         data-id="<?= $pengumuman->id_post ?>"
                                         data-parent="#parent<?= $pengumuman->id_post ?>">
                                        <hr class="m-0">
                                        <div id="konten<?= $pengumuman->id_post ?>" class="p-4">
                                        </div>
                                        <div id="loading<?= $pengumuman->id_post ?>" class="text-center d-none">
                                            <div class="spinner-grow"></div>
                                        </div>
                                        <?php if ($pengumuman->jml == '0') : ?>
                                            <div class="text-center">Tidak ada komentar</div>
                                        <?php else: ?>
                                            <div id="loadmore<?= $pengumuman->id_post ?>"
                                                 onclick="getComments(<?= $pengumuman->id_post ?>)"
                                                 class="text-center mt-4 loadmore">
                                                <div class="btn btn-default">Muat komentar lainnya ...</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
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

<div id="pengumuman-data" data-guru="<?= html_escape(isset($guru) ? $guru->id_guru : '') ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/pengumuman-data.js"></script>
