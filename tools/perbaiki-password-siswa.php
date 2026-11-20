<?php
/**
 * Perbaiki password login siswa yang kosong.
 *
 * Password login disimpan di users.password (hash), sedangkan halaman Data Siswa
 * menampilkan master_siswa.password. Skrip ini menyamakan keduanya untuk akun
 * yang belum punya hash password yang valid.
 *
 * Pemakaian:
 *   php tools/perbaiki-password-siswa.php            # simulasi, tidak menulis
 *   php tools/perbaiki-password-siswa.php --apply    # benar-benar memperbaiki
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Skrip ini hanya boleh dijalankan dari command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
$dbFile = $root . '/application/config/database.php';

if (!is_file($dbFile)) {
    fwrite(STDERR, "File konfigurasi database tidak ditemukan: {$dbFile}\n");
    exit(1);
}

if (!defined('BASEPATH')) {
    define('BASEPATH', $root . '/system/');
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}

$db = array();
include $dbFile;

if (!isset($db['default']) || !is_array($db['default'])) {
    fwrite(STDERR, "Konfigurasi database default tidak ditemukan.\n");
    exit(1);
}

$cfg = $db['default'];
$apply = in_array('--apply', $argv, true);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(
        (string) $cfg['hostname'],
        (string) $cfg['username'],
        (string) $cfg['password'],
        (string) $cfg['database']
    );
    $mysqli->set_charset('utf8mb4');

    $sql = "SELECT DISTINCT
                s.id_siswa,
                s.username,
                s.password AS password_data_siswa,
                u.id AS user_id,
                u.password AS password_login
            FROM master_siswa s
            JOIN users u ON u.username = s.username
            JOIN users_groups ug ON ug.user_id = u.id
            JOIN `groups` g ON g.id = ug.group_id
            WHERE g.name = 'siswa'
            ORDER BY s.id_siswa";

    $result = $mysqli->query($sql);

    $perbaiki = array();
    $sudah_benar = array();
    $password_kosong = array();

    while ($row = $result->fetch_assoc()) {
        $username = (string) $row['username'];
        $plain = trim((string) $row['password_data_siswa']);
        $hash = (string) $row['password_login'];

        if ($hash !== '' && password_verify($plain, $hash)) {
            $sudah_benar[] = $username;
            continue;
        }

        if ($plain === '' || strlen($plain) > 4096 || strpos($plain, "\0") !== false) {
            $password_kosong[] = $username;
            continue;
        }

        $perbaiki[] = array(
            'user_id' => (int) $row['user_id'],
            'username' => $username,
            'plain' => $plain,
        );
    }
    $result->free();

    echo "Mode: " . ($apply ? 'APPLY (menulis database)' : 'SIMULASI (tidak menulis database)') . "\n";
    echo "Akun siswa sudah benar      : " . count($sudah_benar) . "\n";
    echo "Akun akan diperbaiki        : " . count($perbaiki) . "\n";
    echo "Password Data Siswa kosong  : " . count($password_kosong) . "\n";

    if (!empty($password_kosong)) {
        echo "\nUsername dengan password Data Siswa kosong (tidak diubah):\n";
        foreach (array_slice($password_kosong, 0, 20) as $username) {
            echo "  - {$username}\n";
        }
        if (count($password_kosong) > 20) {
            echo "  ... dan " . (count($password_kosong) - 20) . " akun lainnya\n";
        }
    }

    if (!$apply) {
        echo "\nIni masih simulasi. Jalankan dengan --apply setelah backup database dibuat.\n";
        exit(0);
    }

    if (empty($perbaiki)) {
        echo "\nTidak ada akun yang perlu diperbaiki.\n";
        exit(0);
    }

    $transactionStarted = true;
    $mysqli->begin_transaction();
    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");

    foreach ($perbaiki as $item) {
        $hashBaru = password_hash($item['plain'], PASSWORD_BCRYPT);
        if ($hashBaru === false) {
            throw new RuntimeException('Gagal membuat hash password untuk ' . $item['username']);
        }
        $stmt->bind_param('si', $hashBaru, $item['user_id']);
        $stmt->execute();
    }
    $stmt->close();
    $mysqli->commit();
    $transactionStarted = false;

    echo "\nSelesai. " . count($perbaiki) . " akun siswa diperbaiki.\n";

    $cek = $mysqli->query("SELECT COUNT(*) AS kosong FROM users u
        JOIN users_groups ug ON ug.user_id = u.id
        JOIN `groups` g ON g.id = ug.group_id
        WHERE g.name = 'siswa' AND (u.password IS NULL OR u.password = '')");
    $sisa = (int) $cek->fetch_assoc()['kosong'];
    $cek->free();

    echo "Sisa password login siswa yang kosong: {$sisa}\n";
    exit($sisa === 0 ? 0 : 2);
} catch (Throwable $e) {
    if (isset($mysqli, $transactionStarted) && $transactionStarted) {
        $mysqli->rollback();
    }
    fwrite(STDERR, "GAGAL: " . $e->getMessage() . "\n");
    exit(1);
}
