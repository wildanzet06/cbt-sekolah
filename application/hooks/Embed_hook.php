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

        // Hook display_override WAJIB mengeluarkan output sendiri. Kalau bukan
        // mode embed, keluarkan apa adanya.
        if ((string) $CI->input->get('embed') !== '1') {
            echo $CI->output->get_output();
            return;
        }

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
