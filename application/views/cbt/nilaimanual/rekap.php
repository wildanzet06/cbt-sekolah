<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('nm_fmt')) {
    function nm_fmt($v)
    {
        return ($v === null || $v === '') ? '' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
    }
}
?>
<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8"><h1><?= html_escape($judul) ?></h1></div>
                <div class="col-sm-4 text-right">
                    <a href="<?= base_url('nilaimanual') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header"><h6 class="card-title"><?= html_escape($subjudul) ?></h6></div>
                <div class="card-body">
                    <form method="get" action="<?= base_url('nilaimanual/rekap') ?>" class="form-inline mb-3" data-auto-filter>
                        <label class="mr-2" for="mapel">Mapel</label>
                        <select name="mapel" id="mapel" class="form-control form-control-sm mr-3">
                            <?php foreach ($opsi['mapel'] as $id => $nama): ?>
                                <option value="<?= (int)$id ?>" <?= (int)$id === (int)$mapel_sel ? 'selected' : '' ?>>
                                    <?= html_escape($nama) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label class="mr-2" for="kelas">Kelas</label>
                        <select name="kelas" id="kelas" class="form-control form-control-sm mr-3"></select>
                        <noscript><button type="submit" class="btn btn-sm btn-primary">Tampilkan</button></noscript>
                    </form>

                    <?php if ($rekap === null): ?>
                        <div class="text-muted">Pilih mapel dan kelas — rekap langsung tampil.</div>
                    <?php elseif (count($rekap['ujian']) === 0): ?>
                        <div class="alert alert-info mb-0">Belum ada ujian offline untuk mapel dan kelas ini.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="bg-light">
                                <tr>
                                    <th class="text-center" style="width:45px">No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <?php foreach ($rekap['ujian'] as $u): ?>
                                        <th class="text-center">
                                            <?= html_escape($u->nama_ujian) ?><br>
                                            <small class="text-muted">
                                                <?= html_escape($u->kode_jenis) ?> &middot; maks <?= (int)$u->nilai_maks ?>
                                                <?= $u->tanggal ? ' &middot; ' . html_escape(date('d-m-Y', strtotime($u->tanggal))) : '' ?>
                                            </small>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="text-center">Rata-rata<br><small class="text-muted">(skala 100)</small></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rekap['siswa'] as $i => $s):
                                    $sid = (int)$s->id_siswa;
                                    $total = 0;
                                    $n = 0;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td><?= html_escape($s->nis) ?></td>
                                        <td><?= html_escape($s->nama) ?></td>
                                        <?php foreach ($rekap['ujian'] as $u):
                                            $v = $rekap['nilai'][(int)$u->id_nm][$sid] ?? null;
                                            if ($v !== null) {
                                                $maks = max(1, (int)$u->nilai_maks);
                                                $total += ((float)$v / $maks) * 100;
                                                $n++;
                                            }
                                            ?>
                                            <td class="text-center"><?= html_escape(nm_fmt($v)) ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-center font-weight-bold">
                                            <?= $n > 0 ? html_escape(nm_fmt(round($total / $n, 1))) : '' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        var OPSI = <?= json_encode($opsi, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var KELAS_SEL = <?= (int)$kelas_sel ?>;

        function fillKelas() {
            var $k = $('#kelas').empty();
            var list = OPSI.pairs[$('#mapel').val()] || [];
            $.each(list, function (i, id) {
                var $o = $('<option>').val(id).text(OPSI.kelas[id]);
                if (id === KELAS_SEL) { $o.prop('selected', true); }
                $k.append($o);
            });
        }

        $('#mapel').on('change', function () { KELAS_SEL = 0; fillKelas(); });
        fillKelas();
    })();
</script>
