<?php
/**
 * Created by IntelliJ IDEA.
 * User: multazam
 * Date: 14/07/20
 * Time: 17:46
 */

$dataFileAttach = [];
$att = @unserialize($soal->file ?? '', ['allowed_classes' => ['stdClass']]);
if ($att !== false) {
    $dataFileAttach = unserialize($soal->file ?? '', ['allowed_classes' => ['stdClass']]);
}

$pg_belum_komplit = [];
$pg2_belum_komplit = [];
$jodohkan_belum_komplit = [];
$isian_belum_komplit = [];
$essai_belum_komplit = [];

foreach ($soal_belum_komplit as $key => $value) {
    if ($value->jenis == '1') {
        array_push($pg_belum_komplit, $value->nomor_soal);
    } elseif ($value->jenis == '2') {
        array_push($pg2_belum_komplit, $value->nomor_soal);
    } elseif ($value->jenis == '3') {
        array_push($jodohkan_belum_komplit, $value->nomor_soal);
    } elseif ($value->jenis == '4') {
        array_push($isian_belum_komplit, $value->nomor_soal);
    } else {
        array_push($essai_belum_komplit, $value->nomor_soal);
    }
}

$soal_pg_ada = [];
$soal_pg2_ada = [];
$soal_jodohkan_ada = [];
$soal_isian_ada = [];
$soal_esai_ada = [];

foreach ($soal_ada as $key => $value) {
    if ($value->jenis == '1') {
        array_push($soal_pg_ada, $value->nomor_soal);
    } elseif ($value->jenis == '2') {
        array_push($soal_pg2_ada, $value->nomor_soal);
    } elseif ($value->jenis == '3') {
        array_push($soal_jodohkan_ada, $value->nomor_soal);
    } elseif ($value->jenis == '4') {
        array_push($soal_isian_ada, $value->nomor_soal);
    } else {
        array_push($soal_esai_ada, $value->nomor_soal);
    }
}
?>

<div class="content-wrapper bg-white">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-sm-flex justify-content-between mb-2">
                <h1><?= $subjudul ?></h1>
                <a href="<?= base_url('cbtbanksoal/detail/' . $bank->id_bank) ?>" type="button"
                   class="btn btn-sm btn-danger float-right">
                    <i class="fas fa-arrow-circle-left"></i><span
                            class="d-none d-sm-inline-block ml-1">Kembali</span>
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card my-shadow">
                <div class="card-body">
                    <div class="row">
                        <h6 class="col-md-6"><b>Bank
                                Soal:</b> <?= $bank->bank_kode . ' | PG: ' . $bank->tampil_pg . ', Essai: ' . $bank->tampil_esai ?>
                        </h6>
                    </div>
                </div>
            </div>
            <?= form_open('create', array('id' => 'create')) ?>
            <div class="card my-shadow">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link <?= $tab_active == '1' ? 'active' : '' ?>"
                                                href="<?= base_url('cbtbanksoal/buatsoal/' . $bank->id_bank . '?tab=1') ?>">Pilihan
                                Ganda</a></li>
                        <li class="nav-item"><a class="nav-link <?= $tab_active == '2' ? 'active' : '' ?>"
                                                href="<?= base_url('cbtbanksoal/buatsoal/' . $bank->id_bank . '?tab=2') ?>">Pilihan
                                Ganda Kompleks</a></li>
                        <li class="nav-item"><a class="nav-link <?= $tab_active == '3' ? 'active' : '' ?>"
                                                href="<?= base_url('cbtbanksoal/buatsoal/' . $bank->id_bank . '?tab=3') ?>">Menjodohkan</a>
                        </li>
                        <li class="nav-item"><a class="nav-link <?= $tab_active == '4' ? 'active' : '' ?>"
                                                href="<?= base_url('cbtbanksoal/buatsoal/' . $bank->id_bank . '?tab=4') ?>">Isian
                                Singkat</a></li>
                        <li class="nav-item"><a class="nav-link <?= $tab_active == '5' ? 'active' : '' ?>"
                                                href="<?= base_url('cbtbanksoal/buatsoal/' . $bank->id_bank . '?tab=5') ?>">Essai/Uraian</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <input type="hidden" id="jenis-id" name="jenis" value="1" class="form-control">
                    <input type="hidden" id="bank-id" name="bank_id" value="<?= $bank->id_bank ?>" class="form-control">
                    <input type="hidden" id="soal-id" name="soal_id"
                           value="<?= is_null($soal) ? '0' : $soal->id_soal ?>" class="form-control">
                    <input type="hidden" id="method" name="method" value="<?= is_null($soal) ? 'add' : 'edit' ?>"
                           class="form-control">

                    <div class="tab-content">
                        <div class="tab-pane  <?= $tab_active == '1' ? 'active' : '' ?>" id="ganda">
                            <div class="alert alert-default-success align-content-center" role="alert">
                                Pilihan Ganda, jumlah soal seharusnya: <b><?= $bank->tampil_pg ?></b>
                            </div>
                            <span><b>PG NOMOR: </b></span><br>
                            <?php
                            $no = 1;
                            $tpg = !isset($jml_pg) || $jml_pg == null || $jml_pg->nomor_soal == null ? 0 : $jml_pg->nomor_soal;
                            for ($i = 0; $i < $tpg; $i++) : ?>
                                <a href="javascript:void(0)"
                                   class="mt-1 btn btn-circle-sm <?= !in_array($no, $pg_belum_komplit) ? 'btn-success' : 'btn-outline-danger' ?> <?= is_null($soal) ? ($no == 1 ? 'active' : '') : ($no == $soal->nomor_soal ? 'active' : '') ?>"
                                   onclick="getSoalById(<?= $bank->id_bank ?>, <?= $no ?>, <?= '1' . $no ?>, '1')"
                                   id="btn-<?= '1' . $no ?>"><?= $no ?>
                                </a>
                                <?php
                                $no++;
                            endfor; ?>
                            <a href="javascript:void(0)" class="pt-1 btn btn-oval-sm btn-outline-success"
                               data-mapel="<?= $bank->bank_mapel_id ?>" data-bank="<?= $bank->id_bank ?>"
                               data-nomor="<?= $no ?>" onclick="tambahSoalPg(this)" id="btn-add-new-pg"><i
                                        class="fa fa-plus"></i> Tambah PG</a>
                        </div>
                        <div class="tab-pane <?= $tab_active == '2' ? 'active' : '' ?>" id="kompleks">
                            <div class="alert alert-default-success align-content-center" role="alert">
                                Pilihan Ganda Kompleks, jumlah soal seharusnya: <b><?= $bank->tampil_kompleks ?></b>
                            </div>
                            <span><b>SOAL NOMOR: </b></span><br>
                            <?php

                            $tpg2 = !isset($jml_pg2) || $jml_pg2 == null || $jml_pg2->nomor_soal == null ? 0 : $jml_pg2->nomor_soal;
                            $no2 = 1;
                            for ($i = 0; $i < $tpg2; $i++) : ?>
                                <a href="javascript:void(0)"
                                   class="mt-1 btn btn-circle-sm <?= !in_array($no2, $pg2_belum_komplit) ? 'btn-success' : 'btn-outline-danger' ?> <?= is_null($soal) ? ($no2 == 1 ? 'active' : '') : ($no2 == $soal->nomor_soal ? 'active' : '') ?>"
                                   onclick="getSoalById(<?= $bank->id_bank ?>, <?= $no2 ?>, <?= '2' . $no2 ?>, '2')"
                                   id="btn-<?= '2' . $no2 ?>"><?= $no2 ?>
                                </a>
                                <?php
                                $no2++;
                            endfor; ?>
                            <a href="javascript:void(0)" class="pt-1 btn btn-oval-sm btn-outline-success"
                               data-mapel="<?= $bank->bank_mapel_id ?>" data-bank="<?= $bank->id_bank ?>"
                               data-nomor="<?= $no2 ?>" onclick="tambahSoalPg2(this)" id="btn-add-new-pg"><i
                                        class="fa fa-plus"></i> Tambah PG Kompleks</a>
                        </div>
                        <div class="tab-pane <?= $tab_active == '3' ? 'active' : '' ?>" id="jodoh">
                            <div class="alert alert-default-success align-content-center" role="alert">
                                Soal Menjodohkan, jumlah soal seharusnya: <b><?= $bank->tampil_jodohkan ?></b>
                            </div>
                            <span><b>SOAL NOMOR: </b></span><br>
                            <?php $noj = 1;
                            $tej = !isset($jml_jodohkan) || $jml_jodohkan == null || $jml_jodohkan->nomor_soal == null ? 0 : $jml_jodohkan->nomor_soal;
                            for ($i = 0; $i < $tej; $i++) : ?>
                                <a href="javascript:void(0)"
                                   class="mt-1 btn btn-circle-sm <?= !in_array($noj, $jodohkan_belum_komplit) ? 'btn-primary' : 'btn-outline-danger' ?>"
                                   onclick="getSoalById(<?= $bank->id_bank ?>, <?= $noj ?>, <?= '3' . $noj ?>, '3')"
                                   id="btn-<?= '3' . $noj ?>"><?= $noj ?>
                                </a>
                                <?php $noj++; endfor; ?>
                            <a href="javascript:void(0)" class="pt-1 btn btn-oval-sm btn-outline-primary"
                               data-mapel="<?= $bank->bank_mapel_id ?>" data-bank="<?= $bank->id_bank ?>"
                               data-nomor="<?= $noj ?>" onclick="tambahSoalJodohkan(this)" id="btn-add-new-essai"><i
                                        class="fa fa-plus"></i> Tambah Soal Menjodohkan</a>
                        </div>
                        <div class="tab-pane <?= $tab_active == '4' ? 'active' : '' ?>" id="isian">
                            <div class="alert alert-default-success align-content-center" role="alert">
                                Soal Isian Singkat, jumlah soal seharusnya: <b><?= $bank->tampil_isian ?></b>
                            </div>
                            <span><b>SOAL NOMOR: </b></span><br>
                            <?php $noi = 1;
                            $tei = !isset($jml_isian) || $jml_isian == null || $jml_isian->nomor_soal == null ? 0 : $jml_isian->nomor_soal;
                            for ($i = 0; $i < $tei; $i++) : ?>
                                <a href="javascript:void(0)"
                                   class="mt-1 btn btn-circle-sm <?= !in_array($noi, $isian_belum_komplit) ? 'btn-primary' : 'btn-outline-danger' ?>"
                                   onclick="getSoalById(<?= $bank->id_bank ?>, <?= $noi ?>, <?= '4' . $noi ?>, '4')"
                                   id="btn-<?= '4' . $noi ?>"><?= $noi ?>
                                </a>
                                <?php $noi++; endfor; ?>
                            <a href="javascript:void(0)" class="pt-1 btn btn-oval-sm btn-outline-primary"
                               data-mapel="<?= $bank->bank_mapel_id ?>" data-bank="<?= $bank->id_bank ?>"
                               data-nomor="<?= $noi ?>" onclick="tambahSoalIsian(this)" id="btn-add-new-essai"><i
                                        class="fa fa-plus"></i> Tambah Soal Isian</a>
                        </div>
                        <div class="tab-pane <?= $tab_active == '5' ? 'active' : '' ?>" id="essai">
                            <div class="alert alert-default-success align-content-center" role="alert">
                                Soal Uraian/Essai, jumlah soal seharusnya: <b><?= $bank->tampil_esai ?></b>
                            </div>
                            <span><b>ESSAI NOMOR: </b></span><br>
                            <?php $noe = 1;
                            $tes = !isset($jml_essai) || $jml_essai == null || $jml_essai->nomor_soal == null ? 0 : $jml_essai->nomor_soal;
                            for ($i = 0; $i < $tes; $i++) : ?>
                                <a href="javascript:void(0)"
                                   class="mt-1 btn btn-circle-sm <?= !in_array($noe, $essai_belum_komplit) ? 'btn-primary' : 'btn-outline-danger' ?>"
                                   onclick="getSoalById(<?= $bank->id_bank ?>, <?= $noe ?>, <?= '5' . $noe ?>, '5')"
                                   id="btn-<?= '5' . $noe ?>"><?= $noe ?>
                                </a>
                                <?php $noe++; endfor; ?>
                            <a href="javascript:void(0)" class="pt-1 btn btn-oval-sm btn-outline-primary"
                               data-mapel="<?= $bank->bank_mapel_id ?>" data-bank="<?= $bank->id_bank ?>"
                               data-nomor="<?= $noe ?>" onclick="tambahSoalEssai(this)" id="btn-add-new-essai"><i
                                        class="fa fa-plus"></i> Tambah Essai</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $pg_none = $tab_active == '1' ? '' : 'd-none';
            $pg2_none = $tab_active == '2' ? '' : 'd-none';
            $jodohkan_none = $tab_active == '3' ? '' : 'd-none';
            $isian_none = $tab_active == '4' ? '' : 'd-none';
            $essai_none = $tab_active == '5' ? '' : 'd-none';
            ?>
            <div class="alert alert-default-info" id="empty-soal">
                <span>Belum ada soal</span>
            </div>
            <div class="card my-shadow d-none" id="not-empty-soal">
                <div class="card-header">
                    <div class="card-title">
                        <h6 id="nomor-soal"><b>Soal Nomor: 1</b></h6>
                    </div>
                    <div class="card-tools">
                        <button type="submit" class="btn btn-primary btn-sm mr-1">
                            <i class="fa fa-plus mr-1"></i>Simpan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                <div class="card-body">
                    <?php
                    $__bank_id = (int) $bank->id_bank;
                    $__jenis = (int) ($this->input->get('tab') ?: $this->input->get('jns') ?: 1);
                    if (!$this->db->table_exists('cbt_bacaan')) {
                        $this->load->model('Bacaan_model', 'bacaan_model');
                    }
                    $__bacaan = $this->db->order_by('judul', 'ASC')->get('cbt_bacaan')->result();
                    $__assign = $this->db->select('nomor_soal, id_bacaan')
                        ->where('bank_id', $__bank_id)
                        ->where('jenis', $__jenis)
                        ->where('id_bacaan IS NOT NULL', null, false)
                        ->order_by('nomor_soal', 'ASC')
                        ->get('cbt_soal')->result();
                    $__peta = array();
                    foreach ($__assign as $__a) {
                        $__peta[(int) $__a->id_bacaan][] = (int) $__a->nomor_soal;
                    }
                    ?>
                    <div class="border rounded mb-4" id="panel-bacaan" style="background:#fbfcfd;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2"
                             style="border-bottom:1px solid #e3e6ea;">
                            <span class="font-weight-bold"><i class="fas fa-book-open mr-1"></i>Bacaan / Stimulus</span>
                            <button type="button" class="btn btn-sm btn-light border" id="bac-toggle"
                                    title="Perkecil / perbesar panel">
                                <i class="fas fa-chevron-up" id="bac-toggle-icon"></i>
                            </button>
                        </div>
                        <div id="bac-body" class="p-3">
                        <div class="form-row align-items-center mb-2">
                            <div class="col-auto">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="bac-aktif">
                                    <label class="custom-control-label font-weight-bold" for="bac-aktif">Bacaan stimulus</label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <label class="mb-0" for="bac-dari">Berlaku untuk nomor</label>
                            </div>
                            <div class="col-auto">
                                <input type="number" class="form-control form-control-sm" id="bac-dari"
                                       min="1" value="1" style="width:80px;" disabled>
                            </div>
                            <div class="col-auto">
                                <input type="number" class="form-control form-control-sm" id="bac-sampai"
                                       min="1" value="<?= max(1, (int) $bank->jml_soal) ?>" style="width:80px;" disabled>
                            </div>
                            <div class="col-auto ml-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="bac-terapkan" disabled>
                                    Terapkan
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="bac-lepas">
                                    Lepas bacaan
                                </button>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="font-weight-bold mb-1">Tambah bacaan baru</div>
                                <input type="text" class="form-control form-control-sm mb-2" id="bac-judul"
                                       placeholder="judul">
                                <textarea class="form-control mb-2" id="bac-isi" rows="6"
                                          placeholder="Isi bacaan (boleh HTML sederhana)"></textarea>
                                <button type="button" class="btn btn-primary" id="bac-tambah">
                                    Simpan bacaan
                                </button>
                                <span class="small text-muted ml-2" id="bac-status"></span>
                            </div>
                            <div class="col-md-4">
                                <div class="font-weight-bold mb-1">Bacaan tersimpan</div>
                                <?php if (empty($__bacaan)): ?>
                                    <div class="text-muted small">Belum ada bacaan.</div>
                                <?php else: ?>
                                    <select class="form-control form-control-sm" id="bac-pilih">
                                        <?php foreach ($__bacaan as $__b): ?>
                                            <option value="<?= (int) $__b->id_bacaan ?>"><?= html_escape($__b->judul) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small text-muted mt-1">Centang "Bacaan stimulus" lalu klik Terapkan.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($__peta)): ?>
                            <div class="mt-3 small">
                                <b>Terpasang saat ini:</b>
                                <?php
                                $__judul = array();
                                foreach ($__bacaan as $__b) {
                                    $__judul[(int) $__b->id_bacaan] = $__b->judul;
                                }
                                foreach ($__peta as $__idb => $__nomor):
                                    $__nomor = array_values(array_unique($__nomor));
                                    sort($__nomor);
                                    $__teks = array();
                                    $__mulai = $__nomor[0];
                                    $__akhir = $__nomor[0];
                                    for ($__i = 1; $__i < count($__nomor); $__i++) {
                                        if ($__nomor[$__i] === $__akhir + 1) {
                                            $__akhir = $__nomor[$__i];
                                        } else {
                                            $__teks[] = $__mulai === $__akhir ? (string) $__mulai : $__mulai . '–' . $__akhir;
                                            $__mulai = $__akhir = $__nomor[$__i];
                                        }
                                    }
                                    $__teks[] = $__mulai === $__akhir ? (string) $__mulai : $__mulai . '–' . $__akhir;
                                    ?>
                                    <span class="badge badge-info mr-1 mb-1">
                                        <?= html_escape(isset($__judul[$__idb]) ? $__judul[$__idb] : ('#' . $__idb)) ?>:
                                        nomor <?= html_escape(implode(', ', $__teks)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        </div><!-- /bac-body -->
                    </div>
                    <script>
                        (function () {
                            var BASE = '<?= base_url() ?>';
                            var CSRF = '<?= $this->security->get_csrf_token_name() ?>';
                            var HASH = '<?= $this->security->get_csrf_hash() ?>';
                            var BANK = <?= $__bank_id ?>;
                            var JENIS = <?= $__jenis ?>;

                            function angka(sel) {
                                var v = parseInt($(sel).val(), 10);
                                return isNaN(v) ? 0 : v;
                            }

                            function kirim(url, data, ok) {
                                data[CSRF] = HASH;
                                $.post(BASE + url, data, function (res) {
                                    if (res && res.status) {
                                        $('#bac-status').text(res.msg || 'Berhasil.');
                                        setTimeout(function () { location.reload(); }, 600);
                                    } else {
                                        alert((res && res.msg) || 'Gagal.');
                                    }
                                }, 'json').fail(function () {
                                    alert('Gagal menghubungi server.');
                                });
                            }

                            $('#bac-aktif').on('change', function () {
                                var aktif = this.checked;
                                $('#bac-dari, #bac-sampai, #bac-terapkan').prop('disabled', !aktif);
                            });

                            $('#bac-terapkan').on('click', function () {
                                var idb = $('#bac-pilih').val();
                                if (!idb) { alert('Belum ada bacaan tersimpan.'); return; }
                                var dari = angka('#bac-dari'), sampai = angka('#bac-sampai');
                                if (dari < 1 || sampai < dari) { alert('Nomor tidak valid.'); return; }
                                kirim('bacaan/terapkan', {
                                    id_bank: BANK, jenis: JENIS, id_bacaan: idb, dari: dari, sampai: sampai
                                });
                            });

                            $('#bac-lepas').on('click', function () {
                                var dari = angka('#bac-dari'), sampai = angka('#bac-sampai');
                                if (dari < 1 || sampai < dari) { alert('Nomor tidak valid.'); return; }
                                if (!confirm('Lepas bacaan dari nomor ' + dari + ' - ' + sampai + '?')) { return; }
                                kirim('bacaan/lepas', { id_bank: BANK, jenis: JENIS, dari: dari, sampai: sampai });
                            });

                            $('#bac-tambah').on('click', function () {
                                var judul = $.trim($('#bac-judul').val());
                                if (judul === '') { alert('Judul bacaan wajib diisi.'); return; }

                                var data = { judul: judul, isi: $('#bac-isi').val(), id_bacaan: 0 };
                                data[CSRF] = HASH;
                                $.post(BASE + 'bacaan/simpan', data, function (res) {
                                    if (!res || !res.status) { alert((res && res.msg) || 'Gagal menyimpan.'); return; }
                                    if ($('#bac-aktif').is(':checked')) {
                                        var dari = angka('#bac-dari'), sampai = angka('#bac-sampai');
                                        if (dari < 1 || sampai < dari) { alert('Bacaan tersimpan. Nomor belum valid.'); return; }
                                        kirim('bacaan/terapkan', {
                                            id_bank: BANK, jenis: JENIS, id_bacaan: res.id_bacaan, dari: dari, sampai: sampai
                                        });
                                    } else {
                                        $('#bac-status').text('Bacaan disimpan.');
                                        setTimeout(function () { location.reload(); }, 600);
                                    }
                                }, 'json').fail(function () { alert('Gagal menghubungi server.'); });
                            });
                            var $bacBody = $('#bac-body');
                            var $bacIcon = $('#bac-toggle-icon');
                            try {
                                if (localStorage.getItem('bac_panel_min') === '1') {
                                    $bacBody.hide();
                                    $bacIcon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                                }
                            } catch (e) {}

                            $('#bac-toggle').on('click', function () {
                                $bacBody.slideToggle(150);
                                var kecil = $bacBody.is(':hidden');
                                $bacIcon.toggleClass('fa-chevron-up', !kecil).toggleClass('fa-chevron-down', kecil);
                                try { localStorage.setItem('bac_panel_min', kecil ? '1' : '0'); } catch (e) {}
                            });
                        })();
                    </script>
                    <div class="soal-area mt-4">
                        <div class="row">
                            <div class="col-md-6" id="soal-area">
                                <div class="form-group">
                                    <label>Soal</label>
                                    <textarea class="textsoal" id="textsoal"
                                              data-id="<?= $this->security->get_csrf_hash() ?>"
                                              data-name="<?= $this->security->get_csrf_token_name() ?>" name="soal"
                                              placeholder="Tulis soal disini"
                                              style="width:100%;"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6" id="jawaban-area">
                                <?php
                                $opsis [''] = 'Pilih Jawaban Benar :';
                                $opsis ['a'] = 'A';
                                $opsis ['b'] = 'B';
                                $opsis ['c'] = 'C';

                                if ($bank->opsi == 4) {
                                    $opsis ['d'] = 'D';
                                    $rangeAbjad = range('a', 'd');
                                } elseif ($bank->opsi == 5) {
                                    $opsis ['d'] = 'D';
                                    $opsis ['e'] = 'E';
                                    $rangeAbjad = range('a', 'e');
                                } else {
                                    $rangeAbjad = range('a', 'c');
                                };
                                ?>
                                <div id="root-opsi-pg" class="<?= $pg_none ?>">
                                    <?php
                                    foreach ($rangeAbjad as $abjad) :
                                        ?>
                                        <div class="mb-3 ml-3">
                                            <label>Jawaban <?= strtoupper($abjad ?? '') ?></label>
                                            <textarea class="textjawaban" id="textjawaban_<?= $abjad ?>"
                                                      name="jawaban_<?= $abjad ?>"
                                                      placeholder="Place some text here"
                                                      style="width:100%;"></textarea>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div id="root-opsi-pg2" class="<?= $pg2_none ?>">
                                    <div id="opsi-pg2"></div>
                                    <div class="mb-3 ml-3">
                                        <button id="tambah-jawaban-pg2" type="button" class="btn btn-success">Tambah
                                            Jawaban
                                        </button>
                                    </div>
                                </div>

                                <div class="<?= $jodohkan_none ?> mt-4" id="root-opsi-jodohkan">
                                    <hr>
                                    <p><b>Jawaban</b></p>
                                    <div id="jawaban-jodohkan">
                                    </div>
                                    <div class="alert alert-default-warning">
                                        Maksimal <b>20 KOLOM</b> dan <b>20 BARIS</b>
                                    </div>
                                </div>

                                <div class="mt-1 ml-3" id="jawaban-benar">
                                    <label>Jawaban Benar</label>
                                    <div id="root-jawaban-pg" class="<?= $pg_none ?>">
                                        <?php
                                        echo form_dropdown(
                                            'jawaban_pg',
                                            $opsis,
                                            '',
                                            'id="jawaban" class="select2 form-control" required'
                                        ); ?>
                                    </div>
                                    <div class="<?= $isian_none ?>" id="root-jawaban-isian">
                                        <input type="text" id="jawaban-isian" name="jawaban_isian" class="form-control"
                                               value="" disabled="disabled"/>
                                    </div>
                                    <div class="<?= $essai_none ?>" id="root-jawaban-essai">
										<textarea class="textjawaban-essai" id="jawaban-essai" name="jawaban_essai"
                                                  placeholder="Place some text here" style="width:100%;"
                                                  disabled="disabled">
										</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overlay d-none" id="loading">
                    <div class="spinner-grow"></div>
                </div>
            </div>
            <?= form_close() ?>
        </div>
    </section>
</div>

<?= form_open('create', array('id' => 'addfile')) ?>
<div class="modal fade" id="pickerModal" tabindex="-1" role="dialog" aria-labelledby="pickerModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <h5 id="pickerModalLabel">Pilih File</h5>
                    <br> hanya untuk file audio dan video
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <ul id="media-list" class="clearfix">
                        <li class="myupload">
                                    <span>
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                        <input name="file_uploads" type="file" id="picupload"
                                               class="picupload">
                                    </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="modal fade" id="noteModal" tabindex="-1" role="dialog" aria-labelledby="noteModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <h5 id="noteModalLabel">Edit Field</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <textarea id="edit-dialog" style="width:100%;"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="simpanValue()">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url() ?>/assets/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script src="<?= base_url() ?>/assets/app/js/mediastyler.js"></script>

<script src="<?= base_url() ?>/assets/app/js/linker-list.js"></script>
<script src="<?= base_url() ?>/assets/plugins/element-queries/ElementQueries.js"></script>
<script src="<?= base_url() ?>/assets/plugins/element-queries/ResizeSensor.js"></script>
<script src="<?= base_url() ?>/assets/plugins/katex/katex.min.js"></script>

<script type="text/javascript">
    var nomor_soal = '<?=$p_no?>';
    let soal_ada = '<?=is_null($soal) ? 'n' : 'y'?>';
    let idBank = '<?= $bank->id_bank ?>';
    let idSoal = '<?= is_null($soal) ? 0 : $soal->id_soal ?>';
    let jenis = '<?= $tab_active ?>';
    console.log('jns', jenis);

    var dataFiles = [];
    var arrFileAttach = <?= json_encode($dataFileAttach, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    dataFiles = $.merge(dataFiles, arrFileAttach);

    var adaPg = <?= json_encode($soal_pg_ada, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    var adaPg2 = <?= json_encode($soal_pg2_ada, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    var adaJodohkan = <?= json_encode($soal_jodohkan_ada, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    var adaIsian = <?= json_encode($soal_isian_ada, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    var adaEssai = <?= json_encode($soal_esai_ada, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    console.log('adaPg', adaIsian);

    $(function () {
        bsCustomFileInput.init();
    });
</script>
<script src="<?= base_url() ?>/assets/app/js/cbt/banksoal/soal.js"></script>
