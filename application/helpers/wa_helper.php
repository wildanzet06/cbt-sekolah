<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembantu pengingat tagihan lewat WhatsApp (tanpa API, memakai wa.me).
 *
 * Nomor HP siswa di database biasanya ditulis 08xx… atau +62…; WhatsApp
 * membutuhkan format internasional tanpa tanda plus (628xx…).
 */

if (!function_exists('wa_nomor')) {
    function wa_nomor($hp)
    {
        $digit = preg_replace('/\D+/', '', (string) $hp);
        if ($digit === '') {
            return '';
        }
        if (strpos($digit, '0') === 0) {
            $digit = '62' . substr($digit, 1);
        } elseif (strpos($digit, '8') === 0) {
            $digit = '62' . $digit;
        } elseif (strpos($digit, '620') === 0) {
            $digit = '62' . substr($digit, 3);
        }
        if (strlen($digit) < 10) {
            return ''; // terlalu pendek, kemungkinan nomor tidak valid
        }
        return $digit;
    }
}

if (!function_exists('wa_link')) {
    function wa_link($hp, $pesan)
    {
        $nomor = wa_nomor($hp);
        if ($nomor === '') {
            return '';
        }
        return 'https://wa.me/' . $nomor . '?text=' . rawurlencode($pesan);
    }
}

if (!function_exists('wa_pesan_tagihan')) {
    /**
     * Susun teks pengingat tagihan.
     *
     * @param object $tagihan  baris tagihan (nama, nis, jenis_nama, periode_bulan,
     *                         periode_tahun, jatuh_tempo, sisa)
     * @param string $sekolah  nama sekolah
     * @param array  $rekening daftar rekening (objek dengan bank, nomor, atas_nama)
     * @param string $instruksi instruksi tambahan (opsional)
     */
    function wa_pesan_tagihan($tagihan, $sekolah, $rekening = array(), $instruksi = '')
    {
        $rp = function ($n) {
            return 'Rp ' . number_format((float) $n, 0, ',', '.');
        };

        $label = isset($tagihan->jenis_nama) ? $tagihan->jenis_nama : 'Tagihan';
        if (!empty($tagihan->periode_bulan)) {
            $label .= ' ' . $tagihan->periode_bulan;
            if (!empty($tagihan->periode_tahun)) {
                $label .= ' ' . $tagihan->periode_tahun;
            }
        }

        $baris = array();
        $baris[] = '*Pengingat Pembayaran*';
        if ($sekolah !== '') {
            $baris[] = $sekolah;
        }
        $baris[] = '';
        $baris[] = 'Yth. Bapak/Ibu Wali dari:';
        $baris[] = ($tagihan->nama ?? '-') . (isset($tagihan->nis) && $tagihan->nis ? ' (' . $tagihan->nis . ')' : '');
        $baris[] = '';
        $baris[] = 'Tagihan : ' . $label;
        $baris[] = 'Jumlah   : ' . $rp($tagihan->sisa ?? 0);
        if (!empty($tagihan->jatuh_tempo)) {
            $baris[] = 'Jatuh tempo : ' . date('d/m/Y', strtotime($tagihan->jatuh_tempo));
        }
        $baris[] = '';
        $baris[] = 'Pembayaran dapat dilakukan:';
        $baris[] = '1. Tunai ke bagian keuangan sekolah.';

        if (!empty($rekening)) {
            $baris[] = '2. Transfer ke rekening:';
            foreach ($rekening as $r) {
                $baris[] = '   • ' . $r->bank . ' ' . $r->nomor . ' a.n. ' . $r->atas_nama;
            }
        }
        if ($instruksi !== '') {
            $baris[] = '';
            $baris[] = $instruksi;
        }
        $baris[] = '';
        $baris[] = 'Mohon abaikan pesan ini bila sudah membayar. Terima kasih.';

        return implode("\n", $baris);
    }
}
