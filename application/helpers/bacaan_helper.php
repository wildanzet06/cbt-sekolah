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