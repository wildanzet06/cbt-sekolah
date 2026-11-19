<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

// Penjaga akses: siswa tidak boleh membuka halaman admin/guru
$hook['post_controller_constructor'][] = array(
    'class'    => 'Akses_hook',
    'function' => 'periksa',
    'filename' => 'Akses_hook.php',
    'filepath' => 'hooks'
);
// Mode embed: sembunyikan sidebar/header saat halaman dimuat dalam iframe
$hook['display_override'][] = array(
    'class'    => 'Embed_hook',
    'function' => 'terapkan',
    'filename' => 'Embed_hook.php',
    'filepath' => 'hooks'
);
