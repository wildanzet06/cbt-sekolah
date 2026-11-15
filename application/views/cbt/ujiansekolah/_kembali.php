<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tombol Kembali baku modul Ujian Sekolah (PRD 4.7).
 * Variabel opsional: $kembali_url, $kembali_label
 */
$__url = isset($kembali_url) ? $kembali_url : base_url('ujiansekolah');
$__label = isset($kembali_label) ? $kembali_label : 'Kembali';
?>
<a href="<?= $__url ?>" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left mr-1"></i><?= html_escape($__label) ?>
</a>
