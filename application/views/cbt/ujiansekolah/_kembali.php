<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tombol Kembali baku modul Ujian Sekolah.
 * Variabel opsional: $kembali_url, $kembali_label
 */
$__url = isset($kembali_url) ? $kembali_url : base_url('ujiansekolah');
$__label = isset($kembali_label) ? $kembali_label : 'Kembali';
?>
<style>
    .kembali-ringkas {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .15rem 0;
        border: 0;
        background: transparent;
        color: #2563eb;
        font-size: .85rem;
        font-weight: 500;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
    }
    .kembali-ringkas:hover { color: #1d4ed8; text-decoration: underline; }
    .kembali-ringkas i { font-size: .8rem; }

    @media (max-width: 575.98px) {
        .ujian-hero { display: block !important; }
        .ujian-hero .ujian-hero-kembali {
            width: 100%;
            max-width: 100%;
            padding: 0 15px;
            margin-bottom: .35rem;
        }
        .ujian-hero .ujian-hero-judul {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
        }
        .ujian-hero .ujian-hero-judul h1 { font-size: 1.6rem; }
    }
</style>
<a href="<?= $__url ?>" class="kembali-ringkas">
    <i class="fas fa-arrow-left"></i><span><?= html_escape($__label) ?></span>
</a>
