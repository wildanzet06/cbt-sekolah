<?php
$pilih_periode = $periode_aktif->id_tp . ':' . $periode_aktif->id_smt;
$ada_poin      = (int) $total_poin > 0;
?>
<div class="content-wrapper">
    <div class="content pt-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">

                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Poin Pelanggaran Saya</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!$siswa): ?>
                                <div class="alert alert-warning mb-0">Data siswa untuk akun ini tidak ditemukan.</div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <b><?= html_escape($siswa->nama); ?></b>
                                    <span class="text-muted">&middot; NIS <?= html_escape($siswa->nis); ?></span>
                                </div>

                                <?php if (!empty($periode)): ?>
                                    <form method="get" action="<?= base_url('akun/pelanggaran'); ?>" class="form-inline mb-3">
                                        <label class="mr-2 mb-1">Periode</label>
                                        <select name="periode" class="form-control mr-2 mb-1">
                                            <?php foreach ($periode as $p): ?>
                                                <?php $nilai = $p->id_tp . ':' . $p->id_smt; ?>
                                                <option value="<?= $nilai; ?>" <?= ($nilai === $pilih_periode) ? 'selected' : ''; ?>>
                                                    <?= html_escape($p->tahun); ?> &middot; Semester <?= html_escape($p->nama_smt); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-warning mb-1">Tampilkan</button>
                                    </form>
                                <?php endif; ?>

                                <div class="rounded bg-<?= $ada_poin ? 'danger' : 'success'; ?> text-white text-center py-3 mb-3">
                                    <div style="font-size:2rem;font-weight:600;line-height:1.1"><?= (int) $total_poin; ?></div>
                                    <small>Total poin &middot; <?= html_escape($periode_aktif->tahun); ?>
                                        Semester <?= html_escape($periode_aktif->nama_smt); ?></small>
                                </div>

                                <?php if (empty($riwayat)): ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Tidak ada catatan pelanggaran pada periode ini.
                                    </div>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($riwayat as $r): ?>
                                            <?php $ts = strtotime($r->tanggal); ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                                <div style="min-width:0">
                                                    <div><b><?= html_escape($r->jenis_pelanggaran); ?></b></div>
                                                    <div class="small text-muted">
                                                        <?= $ts ? date('d/m/Y', $ts) : html_escape($r->tanggal); ?>
                                                        <?php if ($r->nama_kelas): ?>
                                                            &middot; <?= html_escape($r->nama_kelas); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($r->keterangan): ?>
                                                        <div class="small"><?= html_escape($r->keterangan); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge badge-danger ml-2 p-2"><?= (int) $r->poin; ?> poin</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="<?= base_url('dashboard'); ?>" class="btn btn-link btn-block text-muted">Kembali ke Beranda</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
