<?php
$warna = array('H' => 'success', 'I' => 'info', 'S' => 'warning', 'A' => 'danger');
$hari  = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
?>
<div class="content-wrapper">
    <div class="content pt-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">

                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Kehadiran Saya</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!$siswa): ?>
                                <div class="alert alert-warning mb-0">Data siswa untuk akun ini tidak ditemukan.</div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <b><?= html_escape($siswa->nama); ?></b>
                                    <span class="text-muted">&middot; NIS <?= html_escape($siswa->nis); ?></span>
                                </div>

                                <form method="get" action="<?= base_url('akun/kehadiran'); ?>" class="form-inline mb-3">
                                    <select name="bulan" class="form-control mr-2 mb-1">
                                        <?php foreach ($daftar_bulan as $i => $nm): ?>
                                            <option value="<?= $i + 1; ?>" <?= ($bulan == $i + 1) ? 'selected' : ''; ?>><?= $nm; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="tahun" class="form-control mr-2 mb-1" style="width:95px"
                                           min="2000" max="2100" value="<?= (int) $tahun; ?>">
                                    <button type="submit" class="btn btn-success mb-1">Tampilkan</button>
                                </form>

                                <div class="row text-center mb-2">
                                    <?php foreach ($status_ref as $kode => $nama): ?>
                                        <div class="col-3 px-1">
                                            <div class="rounded bg-<?= $warna[$kode]; ?> text-<?= $kode === 'S' ? 'dark' : 'white'; ?> py-2">
                                                <div style="font-size:1.4rem;font-weight:600;line-height:1.1"><?= (int) $hitung[$kode]; ?></div>
                                                <small><?= $nama; ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($hitung['persen'] !== null): ?>
                                    <p class="text-center text-muted mb-3">
                                        Kehadiran bulan ini: <b><?= (int) $hitung['persen']; ?>%</b>
                                        dari <?= (int) $hitung['total']; ?> pertemuan tercatat
                                    </p>
                                <?php endif; ?>

                                <?php if (empty($riwayat)): ?>
                                    <p class="text-center text-muted">Belum ada catatan kehadiran pada bulan ini.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($riwayat as $r): ?>
                                            <?php $ts = strtotime($r->tanggal); ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                                <div style="min-width:0">
                                                    <div><b><?= $hari[(int) date('w', $ts)] . ', ' . date('d/m/Y', $ts); ?></b>
                                                        <span class="text-muted">&middot; Jam ke-<?= (int) $r->jam_ke; ?></span></div>
                                                    <?php if ($r->mapel_nama): ?>
                                                        <div class="small text-muted text-truncate"><?= html_escape($r->mapel_nama); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($r->keterangan): ?>
                                                        <div class="small"><?= html_escape($r->keterangan); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge badge-<?= $warna[$r->status]; ?> ml-2 p-2"><?= $status_ref[$r->status]; ?></span>
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
