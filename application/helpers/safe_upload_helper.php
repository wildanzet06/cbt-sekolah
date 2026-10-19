<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('safe_delete_uploaded_file')) {
    function safe_delete_uploaded_file($src, $upload_directory)
    {
        $directories = array('materi', 'foto_siswa', 'bank_soal');
        if (!is_string($src) || $src === '' || !in_array($upload_directory, $directories, true)) {
            return false;
        }

        $relative = $src;
        $base = rtrim(base_url(), '/') . '/';
        if (substr($relative, 0, strlen($base)) === $base) {
            $relative = substr($relative, strlen($base));
        } else {
            $relative = ltrim($relative, '/');
        }
        $prefix = 'uploads/' . $upload_directory . '/';
        if (substr($relative, 0, strlen($prefix)) !== $prefix) {
            return false;
        }
        $filename = substr($relative, strlen($prefix));
        if ($filename === '' || basename($filename) !== $filename || strpos($filename, "\0") !== false) {
            return false;
        }

        $upload_root = realpath(FCPATH . 'uploads');
        $root = realpath(FCPATH . $prefix);
        if ($upload_root === false || $root === false || dirname($root) !== $upload_root) {
            return false;
        }
        $candidate = $root . DIRECTORY_SEPARATOR . $filename;
        if (is_link($candidate)) {
            return false;
        }
        $target = realpath($candidate);
        if ($target === false || dirname($target) !== $root || !is_file($target)) {
            return false;
        }
        return @unlink($target);
    }
}
