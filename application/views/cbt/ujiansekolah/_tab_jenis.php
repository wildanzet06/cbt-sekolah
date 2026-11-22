<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tab "Jenis Ujian" â€” menggantikan menu Jenis Ujian.
 * PRD 5.3 (tab Jenis Ujian): CRUD 7 jenis (kode + nama) + penanda jenis dipakai.
 */
?>
<div class="alert alert-light border">
    <i class="fas fa-info-circle mr-1"></i>
    Tujuh jenis ujian tetap dipertahankan. Kode dipakai sebagai singkatan pada kartu ujian
    (mis. <b>PTS</b>). Jenis yang sudah dipakai ujian tidak bisa dihapus.
</div>

<div class="table-responsive">
    <table class="table table-sm table-bordered mb-3" style="max-width:820px">
        <thead class="bg-light">
        <tr>
            <th style="width:140px">Kode</th>
            <th>Nama Jenis</th>
            <th style="width:110px">Dipakai</th>
            <th style="width:110px"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($jenis as $j): ?>
            <tr data-jenis="<?= (int)$j->id_jenis ?>">
                <td class="align-middle">
                    <input type="text" class="form-control form-control-sm jenis-kode" maxlength="10"
                           value="<?= html_escape($j->kode_jenis) ?>">
                </td>
                <td class="align-middle">
                    <input type="text" class="form-control form-control-sm jenis-nama" maxlength="50"
                           value="<?= html_escape($j->nama_jenis) ?>">
                </td>
                <td class="align-middle">
                    <?php if ((int)$j->dipakai > 0): ?>
                        <span class="badge badge-info"><?= (int)$j->dipakai ?> ujian</span>
                    <?php else: ?>
                        <span class="badge badge-light border">belum</span>
                    <?php endif; ?>
                </td>
                <td class="align-middle text-nowrap">
                    <button type="button" class="btn btn-sm btn-primary btn-simpan-jenis"
                            data-id="<?= (int)$j->id_jenis ?>">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-jenis"
                            data-id="<?= (int)$j->id_jenis ?>"
                            data-nama="<?= html_escape($j->nama_jenis) ?>"
                            data-pakai="<?= (int)$j->dipakai ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr class="table-light" data-jenis="0">
            <td class="align-middle">
                <input type="text" class="form-control form-control-sm jenis-kode" maxlength="10"
                       placeholder="Kode baru">
            </td>
            <td class="align-middle">
                <input type="text" class="form-control form-control-sm jenis-nama" maxlength="50"
                       placeholder="Nama jenis baru">
            </td>
            <td class="align-middle text-muted small">baru</td>
            <td class="align-middle text-nowrap">
                <button type="button" class="btn btn-sm btn-success btn-simpan-jenis" data-id="0">
                    <i class="fas fa-plus"></i> Tambah
                </button>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div id="ujian-tab-jenis" data-csrf="<?= html_escape(json_encode($csrf)) ?>" data-url-simpan="<?= html_escape(base_url('ujiansekolah/jenissimpan')) ?>" data-url-hapus="<?= html_escape(base_url('ujiansekolah/jenishapus')) ?>" hidden></div>
<script src="<?= base_url() ?>assets/app/js/ujian-tab-jenis.js"></script>
</script>
