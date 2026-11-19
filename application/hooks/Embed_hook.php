<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mode "embed" untuk halaman yang dimuat di dalam iframe (mis. tab Hasil
 * Ujian). Menyembunyikan sidebar, navbar, header halaman, dan footer agar
 * tidak muncul sidebar kedua di dalam iframe.
 *
 * Dipakai lewat query string ?embed=1 (lihat cbt/ujiansekolah/hasil.php).
 */
class Embed_hook {

    public function terapkan() {
        $CI =& get_instance();

        // display_override mencetak output sendiri, jadi header dari CI Output
        // tidak dikirim otomatis. Pertahankan Content-Type asli (penting untuk
        // respons AJAX JSON seperti dataguru/getDataKelas).
        $content_type = method_exists($CI->output, 'get_content_type')
            ? $CI->output->get_content_type()
            : 'text/html';
        if ($content_type) {
            @header('Content-Type: ' . $content_type, true);
        }

        // Hook display_override WAJIB mengeluarkan output sendiri. Kalau bukan
        // mode embed, keluarkan apa adanya.
        if ((string) $CI->input->get('embed') !== '1') {
            echo $CI->output->get_output();
            return;
        }

        // Jangan biarkan browser meng-cache halaman embed (isi berubah).
        @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        @header('Pragma: no-cache');

        $html = $CI->output->get_output();
        if ($html === '' || $html === null) {
            return;
        }

        $css = '<style id="embed-style">'
            . '.main-header,.main-sidebar,.main-footer,.content-header,'
            . '.navbar,.breadcrumb{display:none!important}'
            . '.wrapper{display:block!important}'
            . '.content-wrapper{margin-left:0!important;padding-top:0!important;min-height:auto!important;background:#fff!important}'
            . 'body{background:#fff!important}'
            . '.content{padding:0!important}'
            . '.content > .container-fluid{padding:12px!important}'
            . '</style>';

        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $css . '</head>', $html, 1);
        } else {
            $html = $css . $html;
        }

        echo $html;
    }
}
