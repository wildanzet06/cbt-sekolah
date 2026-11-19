<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Membersihkan HTML bacaan/stimulus agar tidak bisa dipakai menyisipkan
 * script (stored XSS) ke halaman ujian siswa.
 */
if (!function_exists('bacaan_isi_aman')) {
    function bacaan_isi_aman($html)
    {
        $html = (string) $html;

        // Buang blok berbahaya beserta isinya.
        $html = preg_replace('#<(script|style|iframe|object|embed|form|textarea|button|select|link|meta|base)\b[^>]*>.*?</\1>#is', '', $html);
        // Buang tag berbahaya yang berdiri sendiri.
        $html = preg_replace('#</?(script|style|iframe|object|embed|form|textarea|button|select|link|meta|base)\b[^>]*>#is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Hanya izinkan tag yang aman untuk tampilan bacaan.
        $izin = '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote>'
            . '<h1><h2><h3><h4><h5><h6><table><thead><tbody><tfoot><tr><th><td>'
            . '<hr><span><div><sup><sub><small><code><pre>';
        $html = strip_tags($html, $izin);

        // Buang atribut yang bisa menjalankan script (on*, style, src, href, dll).
        $html = preg_replace('/\s(on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+))/i', '', $html);
        $html = preg_replace('/\s(style|src|href|formaction|xlink:href|background|poster|data)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/data\s*:\s*text\/html/i', '', $html);

        return $html;
    }
}

/**
 * Membersihkan HTML teks soal/opsi sebelum ditampilkan.
 *
 * Berbeda dengan bacaan, soal boleh berisi gambar. Karena itu gambar tetap
 * diizinkan tetapi hanya dengan src http(s), data:image/..., atau path uploads.
 */
if (!function_exists('soal_html_aman')) {
    function soal_html_aman($html)
    {
        $html = (string) $html;

        $html = preg_replace('#<(script|style|iframe|object|embed|form|textarea|button|select|link|meta|base|svg|math)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#</?(script|style|iframe|object|embed|form|textarea|button|select|link|meta|base|svg|math)\b[^>]*>#is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        $izin = '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote>'
            . '<h1><h2><h3><h4><h5><h6><table><thead><tbody><tfoot><tr><th><td>'
            . '<hr><span><div><sup><sub><small><code><pre><img>';
        $html = strip_tags($html, $izin);

        $html = preg_replace('/\s(on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+))/i', '', $html);
        $html = preg_replace('/\s(style|formaction|xlink:href|background|poster|data)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/data\s*:\s*text\/html/i', '', $html);

        $html = preg_replace_callback('/<img\b[^>]*>/i', function ($m) {
            $tag = $m[0];
            if (!preg_match('/\bsrc\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $src)) {
                return '';
            }
            $url = '';
            if (isset($src[2]) && $src[2] !== '') { $url = $src[2]; }
            elseif (isset($src[3]) && $src[3] !== '') { $url = $src[3]; }
            elseif (isset($src[4])) { $url = $src[4]; }
            $url = trim($url);

            $boleh = preg_match('#^https?://#i', $url)
                || preg_match('#^data:image/(png|jpe?g|gif|webp|bmp);base64,#i', $url)
                || preg_match('#^(/?|\./)uploads/#i', $url);
            if (!$boleh) { return ''; }

            $alt = preg_match('/\balt\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', $tag, $a)
                ? ' alt=' . $a[1]
                : '';
            return '<img src="' . html_escape($url) . '"' . $alt . '>';
        }, $html);

        return $html;
    }
}