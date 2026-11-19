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
<a href="<?= base_url($__tujuan[$__dari][1]) ?>" class="kembali-ringkas">
    <i class="fas fa-arrow-left"></i><span>Kembali</span>
</a>
<style>
    .kembali-ringkas{display:inline-flex;align-items:center;gap:.4rem;padding:.15rem 0;border:0;background:transparent;color:#2563eb;font-size:.85rem;font-weight:500;line-height:1.2;text-decoration:none;white-space:nowrap}
    .kembali-ringkas:hover{color:#1d4ed8;text-decoration:underline}
</style>
