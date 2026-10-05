<?php
$psb_nama  = isset($siswa->nama) ? (string) $siswa->nama : '';
$psb_nis   = isset($siswa->nis) ? (string) $siswa->nis : '';
$psb_kelas = isset($siswa->nama_kelas) ? trim((string) $siswa->nama_kelas) : '';
?>
<style>
    /* Hanya untuk layar HP. Gaya dasar ada langsung di elemen (inline) supaya
       tidak bentrok dengan CSS tema lain. */
    @media (max-width: 575.98px) {
        .psb-foto  { width: 72px !important; height: 72px !important; }
        .psb-teks  { margin-left: .8rem !important; }
        .psb-nama  { font-size: 1.05rem !important; }
        .psb-info  { font-size: .82rem !important; }
    }
</style>

<div class="psb-wrap" style="display:flex; align-items:center; padding:.25rem 0 1rem;">
    <img class="avatar psb-foto"
         src="<?= base_url($siswa->foto) ?>"
         alt="Foto <?= html_escape($psb_nama) ?>"
         style="display:block; width:96px; height:96px; flex:0 0 auto; object-fit:cover; border-radius:50%; border:3px solid rgba(255,255,255,.9); background:#fff;">
    <div class="psb-teks" style="margin-left:1rem; min-width:0; color:#fff;">
        <div class="psb-nama" style="color:#fff; font-size:1.3rem; font-weight:600; line-height:1.2; margin:0 0 .35rem; word-break:break-word;">
            <?= html_escape($psb_nama) ?>
        </div>
        <div class="psb-info" style="color:#fff; font-size:.9rem; line-height:1.5;">
            <i class="fas fa-id-card" style="color:#fff; margin-right:.4rem;"></i>NIS <?= html_escape($psb_nis) ?>
        </div>
        <?php if ($psb_kelas !== ''): ?>
            <div class="psb-info" style="color:#fff; font-size:.9rem; line-height:1.5;">
                <i class="fas fa-school" style="color:#fff; margin-right:.4rem;"></i>Kelas <?= html_escape($psb_kelas) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(`.avatar`).each(function () {
        $(this).on("error", function () {
            var src = $(this).attr('src').replace('profiles', 'foto_siswa');
            $(this).attr("src", src);
            $(this).on("error", function () {
                $(this).attr("src", base_url + 'assets/img/siswa.png');
            });

        });
    });
</script>
