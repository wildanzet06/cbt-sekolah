<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tombol Kembali untuk halaman LAMA yang dibuka dari modul Ujian Sekolah.
 * Tujuan mengikuti parameter `?dari=` (PRD 4.7), default ke Daftar Ujian.
 */
$__dari = strtolower(trim((string)$this->input->get('dari')));
$__tujuan = [
    'daftar'     => ['Daftar Ujian', 'ujiansekolah'],
    'tambah'     => ['Buat Ujian', 'ujiansekolah/baru'],
    'pengaturan' => ['Pengaturan Ujian', 'ujiansekolah/pengaturan'],
    'hasil'      => ['Hasil Ujian', 'ujiansekolah/hasil'],
    'cetak'      => ['Cetak', 'cbtcetak']
];
if (!isset($__tujuan[$__dari])) {
    $__dari = 'daftar';
}
?>
<a href="<?= base_url($__tujuan[$__dari][1]) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left mr-1"></i>Kembali ke <?= html_escape($__tujuan[$__dari][0]) ?>
</a>
