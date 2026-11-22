<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tab "Pengawas" â€” menggantikan menu Pengawas.
 * PRD 5.3 (tab Pengawas): satu pengawas per ruang per tanggal, dipakai bersama
 * semua ujian pada tanggal itu; tombol "Salin ke semua ruang".
 */
?>
<div class="alert alert-light border">
    <i class="fas fa-info-circle mr-1"></i>
    Pengawas dihitung <b>per ruang per tanggal</b>. Satu guru mengawasi ruang yang sama untuk
    semua ujian paralel di tanggal tersebut, jadi orangnya tetap satu.
</div>

<?php if (count($matrix) === 0): ?>
    <div class="alert alert-light border mb-0">
        Belum ada ujian pada semester ini, jadi belum ada tanggal yang perlu dijaga.
    </div>
<?php else: ?>
    <?php foreach ($matrix as $i => $baris):
        $tgl = $baris['tanggal'];
        $pengawas = $baris['pengawas'];
        ?>
        <div class="card border mb-3 pengawas-card" data-tanggal="<?= html_escape($tgl) ?>">
            <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <b><?= html_escape(date('d M Y', strtotime($tgl))) ?></b>
                    <?php foreach ($baris['ujian'] as $u): ?>
                        <span class="badge badge-light border ml-1">
                            <?= html_escape(trim((!empty($u->kode_jenis) ? $u->kode_jenis : 'Ujian') . ' '
                                . (!empty($u->nama_mapel) ? $u->nama_mapel : '-'))) ?>
                            <?php if (!empty($u->label_kelas)): ?>Â· <?= html_escape($u->label_kelas) ?><?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="mt-1 mt-sm-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-salin">
                        <i class="fas fa-clone"></i> Salin ke semua ruang
                    </button>
                    <button type="button" class="btn btn-sm btn-primary btn-simpan-pengawas">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                        <?php foreach ($ruang as $r):
                            $rid = (int)$r->id_ruang;
                            $dipilih = isset($pengawas[$rid]) ? (int)$pengawas[$rid] : 0;
                            ?>
                            <tr>
                                <td class="align-middle" style="width:30%">
                                    <b><?= html_escape($r->nama_ruang) ?></b>
                                    <small class="text-muted">(<?= html_escape($r->kode_ruang) ?>)</small>
                                </td>
                                <td class="align-middle">
                                    <select class="form-control form-control-sm pengawas-select"
                                            data-ruang="<?= $rid ?>">
                                        <option value="">â€” Belum dipilih â€”</option>
                                        <?php foreach ($guru_list as $g): ?>
                                            <option value="<?= (int)$g->id_guru ?>"
                                                <?= $dipilih === (int)$g->id_guru ? 'selected' : '' ?>>
                                                <?= html_escape($g->nama_guru) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div id="ujian-tab-pengawas" data-csrf="<?= html_escape(json_encode($csrf)) ?>" data-url-simpan="<?= html_escape(base_url('ujiansekolah/pengawassimpan')) ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/ujian-tab-pengawas.js"></script>
</script>
