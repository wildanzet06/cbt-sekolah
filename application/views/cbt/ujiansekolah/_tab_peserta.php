<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tab "Peserta & Ruang" â€” menggantikan Sesi/Ruang/Atur Ruang-Sesi/Nomor Peserta.
 * PRD 5.3 (tab Peserta & Ruang).
 */

$kelas = $d['kelas'];
$ruangList = $ruang;
$totalSiswa = (int)$d['total_siswa'];
$terdaftar = (int)$d['terdaftar'];
$kap = $kapasitas;
$kurang = $totalSiswa > (int)$kap['kapasitas'];
$sesiNama = !empty($d['sesi']) ? $d['sesi']->nama_sesi : 'Sesi belum diatur';
?>
<div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between">
    <div>
        <b>Semester <?= html_escape($smt_active->nama_smt) ?> <?= html_escape($tp_active->tahun) ?></b>
        &middot; <?= html_escape($sesiNama) ?> &middot; semua ruang dipakai
        <div class="text-muted small">
            Kapasitas <?= (int)$kap['kapasitas'] ?> kursi dari <?= (int)$kap['ruang'] ?> ruang
            &middot; <?= $totalSiswa ?> siswa
        </div>
    </div>
    <div class="mt-2 mt-sm-0 text-right">
        <div class="text-muted small">Sudah dibagi</div>
        <div class="h5 mb-0 text-<?= ($terdaftar >= $totalSiswa && $totalSiswa > 0) ? 'success' : 'warning' ?>">
            <b><?= $terdaftar ?></b> / <?= $totalSiswa ?>
        </div>
    </div>
</div>

<?php if ($kurang): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Kapasitas ruang kurang (<?= (int)$kap['kapasitas'] ?> kursi untuk <?= $totalSiswa ?> siswa).
        Tambah ruang atau naikkan kapasitas pada tabel di bawah.
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div class="text-muted small">
        Pembagian dipakai <b>semua ujian</b> pada semester ini â€” cukup diatur sekali.
    </div>
    <div>
        <div class="custom-control custom-switch d-inline-block mr-3">
            <input type="checkbox" class="custom-control-input" id="f-semua-ruang" checked>
            <label class="custom-control-label" for="f-semua-ruang">Gunakan semua ruang</label>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btn-bagi">
            <i class="fas fa-random"></i> Bagi Otomatis
        </button>
    </div>
</div>

<?php foreach ($kelas as $id_kelas => $pk): ?>
    <div class="card card-default my-shadow mb-3">
        <div class="card-header py-2">
            <h6 class="card-title mb-0">
                Kelas <?= html_escape($pk['kelas']->nama_kelas) ?>
                <span class="badge badge-light border ml-1"><?= (int)$pk['total'] ?> siswa</span>
            </h6>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-outline-primary btn-lihat"
                        data-kelas="<?= (int)$id_kelas ?>">
                    <i class="fas fa-eye"></i> Lihat
                </button>
            </div>
        </div>
        <div class="card-body py-2">
            <?php if ((int)$pk['total'] === 0): ?>
                <div class="text-muted">Belum ada siswa di kelas ini.</div>
            <?php else: ?>
                <?php foreach ($ruangList as $r):
                    $rid = (int)$r->id_ruang;
                    $isi = isset($pk['ruang'][$rid]) ? (int)$pk['ruang'][$rid] : 0;
                    $maks = max(1, (int)$r->kapasitas);
                    $persen = min(100, (int)round($isi / $maks * 100));
                    ?>
                    <div class="ruang-baris">
                        <div class="flex-fill pr-3">
                            <span class="kode"><?= html_escape($r->kode_ruang) ?></span>
                            <span><?= html_escape($r->nama_ruang) ?></span>
                            <div class="kapasitas-bar mt-1" style="max-width:260px">
                                <span style="width:<?= $persen ?>%"></span>
                            </div>
                        </div>
                        <div class="text-nowrap">
                            <span class="badge <?= $isi > 0 ? 'badge-success' : 'badge-light border' ?>">
                                <?= $isi ?> siswa
                            </span>
                            <small class="text-muted ml-1">/ <?= $maks ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="card-body border-top pt-2 pb-2 d-none" id="siswa-<?= (int)$id_kelas ?>">
            <?php if (count($pk['siswa']) > 0): ?>
                <div class="table-responsive" style="max-height:300px;overflow:auto">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Ruang</th>
                            <th>Nomor Peserta</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $n = 0; foreach ($pk['siswa'] as $s): $n++;
                            $rid = (int)$s->ruang_id;
                            $namaRuang = 'â€”';
                            foreach ($ruangList as $r) {
                                if ((int)$r->id_ruang === $rid) {
                                    $namaRuang = $r->kode_ruang;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td><?= $n ?></td>
                                <td><?= html_escape($s->nama) ?></td>
                                <td><?= html_escape($s->nis) ?></td>
                                <td><?= html_escape($namaRuang) ?></td>
                                <td><?= !empty($s->nomor_peserta)
                                    ? html_escape($s->nomor_peserta)
                                    : '<span class="text-muted">â€”</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<div class="card card-default my-shadow mb-3">
    <div class="card-header py-2">
        <h6 class="card-title mb-0"><i class="fas fa-door-open mr-1"></i> Kapasitas Ruang</h6>
        <div class="card-tools">
            <a href="<?= base_url('cbtruang?dari=pengaturan') ?>" class="btn btn-sm btn-outline-secondary">
                Kelola ruang
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="bg-light">
                <tr>
                    <th style="width:50px">#</th>
                    <th>Nama Ruang</th>
                    <th style="width:110px">Kode</th>
                    <th>Terisi</th>
                    <th style="width:200px">Kapasitas</th>
                    <th style="width:80px"></th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($ruangList) === 0): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada ruang.</td></tr>
                <?php endif; ?>
                <?php foreach ($ruangList as $i => $r): $rid = (int)$r->id_ruang; ?>
                    <tr>
                        <td class="align-middle"><?= $i + 1 ?></td>
                        <td class="align-middle"><?= html_escape($r->nama_ruang) ?></td>
                        <td class="align-middle"><?= html_escape($r->kode_ruang) ?></td>
                        <td class="align-middle">
                            <?= isset($d['per_ruang'][$rid]) ? (int)$d['per_ruang'][$rid] : 0 ?> siswa
                        </td>
                        <td class="align-middle">
                            <div class="input-group input-group-sm" style="max-width:150px">
                                <input type="number" class="form-control kapasitas-input" min="1" max="500"
                                       value="<?= (int)$r->kapasitas ?>" data-id="<?= $rid ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text">siswa</span>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <button type="button" class="btn btn-sm btn-primary btn-simpan-kapasitas"
                                    data-id="<?= $rid ?>">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-default my-shadow mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <b>Nomor peserta</b>
            <div class="text-muted small">
                <?= $terdaftar > 0
                    ? 'Sudah dibuat untuk ' . $terdaftar . ' siswa (format 2627.07.001).'
                    : 'Belum ada nomor peserta. Nomor dibuat otomatis saat Bagi Otomatis.' ?>
            </div>
        </div>
        <div>
            <a href="<?= base_url('cbtnomorpeserta?dari=pengaturan') ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-id-card"></i> Nomor &amp; Kartu Peserta
            </a>
            <a href="<?= base_url('cbtcetak?dari=pengaturan') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-print"></i> Cetak Kartu &amp; Absen
            </a>
        </div>
    </div>
</div>

<div id="ujian-tab-peserta" data-csrf="<?= html_escape(json_encode($csrf)) ?>" data-url-bagi="<?= html_escape(base_url('ujiansekolah/bagiproses')) ?>" data-url-ruang="<?= html_escape(base_url('ujiansekolah/simpanruang')) ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/ujian-tab-peserta.js"></script>
</script>
