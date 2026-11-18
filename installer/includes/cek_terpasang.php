<?php
/**
 * Penjaga installer: menolak akses bila aplikasi SUDAH terpasang.
 *
 * Tanpa penjaga ini, siapa pun yang bisa menjangkau folder installer dapat
 * mengirim POST dan menimpa application/config/database.php (termasuk dari
 * halaman web lain lewat CSRF di komputer yang sama).
 *
 * Installer hanya boleh dipakai saat database belum punya tabel `users`.
 */

if (!function_exists('installer_sudah_terpasang')) {
    function installer_sudah_terpasang()
    {
        // Penanda dibuat setelah instalasi selesai. Bila ada, installer
        // langsung ditutup tanpa perlu menghubungi database sama sekali.
        if (is_file(__DIR__ . '/../TERPASANG')) {
            return true;
        }

        $cfg = dirname(__DIR__, 2) . '/application/config/database.php';
        if (!is_file($cfg)) {
            return false; // belum ada konfigurasi: anggap instalasi baru
        }

        $db = array();
        // database.php memakai defined('BASEPATH') OR exit(...), jadi definisikan dulu.
        if (!defined('BASEPATH')) {
            define('BASEPATH', dirname($cfg) . '/');
        }
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'production');
        }
        include $cfg;
        if (empty($db['default'])) {
            return false;
        }
        $d = $db['default'];

        $host = isset($d['hostname']) ? $d['hostname'] : 'localhost';
        $user = isset($d['username']) ? $d['username'] : '';
        $pass = isset($d['password']) ? $d['password'] : '';
        $name = isset($d['database']) ? $d['database'] : '';
        $port = isset($d['port']) && $d['port'] ? (int) $d['port'] : 3306;
        if ($name === '') {
            return false;
        }

        try {
            $mysqli = @new mysqli($host, $user, $pass, $name, $port);
            if ($mysqli->connect_errno) {
                // 1049 = database belum ada -> instalasi baru, izinkan.
                // Error lain (server mati / akses ditolak) -> tidak bisa
                // memverifikasi, jadi installer ditutup demi keamanan.
                return $mysqli->connect_errno !== 1049;
            }
        } catch (Throwable $e) {
            return true;
        }
        // Dianggap "sudah terpasang" hanya bila sudah ada akun (bukan sekadar
        // tabel users dibuat oleh master.sql saat instalasi baru).
        $q = @$mysqli->query("SELECT COUNT(*) AS c FROM users");
        if (!$q) {
            // Tabel users belum ada -> skema belum dipasang, izinkan installer.
            $mysqli->close();
            return false;
        }
        $row = $q->fetch_assoc();
        $ada = $row && (int) $row['c'] > 0;
        $mysqli->close();
        return $ada;
    }
}

if (installer_sudah_terpasang()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Installer dinonaktifkan</title></head><body '
        . 'style="font-family:sans-serif;padding:40px;max-width:640px;margin:auto">'
        . '<h2>Installer dinonaktifkan</h2>'
        . '<p>Aplikasi ini <b>sudah terpasang</b>, jadi installer tidak boleh dijalankan lagi.</p>'
        . '<p>Untuk memasang ulang, kosongkan dulu database-nya (atau hapus tabel <code>users</code>), '
        . 'lalu buka kembali folder installer.</p>'
        . '<p><a href="../auth">Kembali ke halaman login</a></p></body></html>';
    exit;
}
