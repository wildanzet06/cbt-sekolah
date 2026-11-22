<?php
/**
 * Pemasang cepat CBT Sekolah (baris perintah).
 *
 * Pemakaian (dari folder aplikasi):
 *   php tools/pasang.php
 *   php tools/pasang.php --db=cbt --user=root --pass= --admin=admin --sandi=<pilihan-anda>
 *   php tools/pasang.php --pulihkan=backups/manual-2026-11-18_120809.sql
 *
 * Yang dilakukan:
 *   1. membuat database (bila belum ada),
 *   2. mengimpor assets/app/db/master.sql (instalasi baru) ATAU file .sql
 *      cadangan (mode --pulihkan),
 *   3. mengimpor docs/database_gabungan.sql (tabel tambahan),
 *   4. menulis application/config/database.php,
 *   5. membuat akun administrator (instalasi baru),
 *   6. menyiapkan folder uploads/backups/logs/cache.
 *
 * Aman dijalankan berulang: bila database sudah berisi akun, langkah impor
 * dan pembuatan admin dilewati (kecuali memakai --paksa).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya untuk baris perintah.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
mysqli_report(MYSQLI_REPORT_OFF); // pakai kode error, bukan exception

// ---------------------------------------------------------------------
// Argumen
// ---------------------------------------------------------------------
$opt = array(
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'pass' => '',
    'db'   => 'cbt',
    'admin' => 'admin',
    'sandi' => bin2hex(random_bytes(6)), // dibuat acak; tampilkan sekali saat instalasi
    'nama'  => 'Administrator Sekolah',
    'email' => 'admin@sekolah.local',
    'pulihkan' => ''
);
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z]+)=(.*)$/i', $arg, $m)) {
        $k = strtolower($m[1]);
        if (array_key_exists($k, $opt)) {
            $opt[$k] = $m[2];
        }
    }
}

$akar = dirname(__DIR__);
$config_path = $akar . '/application/config/database.php';

echo "== Pemasang CBT Sekolah ==\n";
echo "Folder : $akar\n";
echo "Database: {$opt['user']}@{$opt['host']}:{$opt['port']} / {$opt['db']}\n\n";

// ---------------------------------------------------------------------
// 1. Cek lingkungan
// ---------------------------------------------------------------------
$perlu = array('mysqli', 'mbstring', 'json', 'gd', 'zip', 'fileinfo');
$kurang = array();
foreach ($perlu as $ext) {
    if (!extension_loaded($ext)) {
        $kurang[] = $ext;
    }
}
if ($kurang) {
    fwrite(STDERR, "Ekstensi PHP belum aktif: " . implode(', ', $kurang) . "\n");
    fwrite(STDERR, "Aktifkan di php.ini lalu jalankan ulang.\n");
    exit(1);
}
if (!is_file($akar . '/assets/app/db/master.sql')) {
    fwrite(STDERR, "Berkas assets/app/db/master.sql tidak ditemukan.\n");
    exit(1);
}

// ---------------------------------------------------------------------
// 2. Koneksi & database
// ---------------------------------------------------------------------
$mysqli = @new mysqli($opt['host'], $opt['user'], $opt['pass'], '', (int) $opt['port']);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Gagal terhubung ke MySQL: " . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');
$db_aman = str_replace('`', '', $opt['db']);
$mysqli->query("CREATE DATABASE IF NOT EXISTS `$db_aman` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
if (!$mysqli->select_db($db_aman)) {
    fwrite(STDERR, "Tidak bisa memilih database $db_aman: " . $mysqli->error . "\n");
    exit(1);
}
echo "[1/7] Database siap.\n";

// ---------------------------------------------------------------------
// 3. Impor skema / cadangan
// ---------------------------------------------------------------------
function impor_sql(mysqli $db, $path)
{
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        return array(false, 'berkas kosong');
    }
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $sql = preg_replace('/^(?:(?:WARNING|mysqldump|mariadb-dump)[^\r\n]*\r?\n)+/i', '', $sql);
    if (!@$db->multi_query($sql)) {
        $err = $db->error;
        while ($db->more_results() && @$db->next_result()) {
            if ($r = @$db->store_result()) { $r->free(); }
        }
        return array(false, $err);
    }
    do {
        if ($res = @$db->store_result()) {
            $res->free();
        }
    } while (@$db->more_results() && @$db->next_result());
    if ($db->errno) {
        return array(false, $db->error);
    }
    return array(true, '');
}

$sudah_terpasang = false;
$cek = $mysqli->query("SHOW TABLES LIKE 'users'");
if ($cek && $cek->num_rows > 0) {
    $r = $mysqli->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc();
    $sudah_terpasang = ((int) $r['c'] > 0);
}

if ($sudah_terpasang && $opt['pulihkan'] === '') {
    echo "[2/7] Database sudah berisi akun — impor skema dilewati.\n";
} elseif ($opt['pulihkan'] !== '') {
    $path = $opt['pulihkan'];
    if (!is_file($path)) {
        $path = $akar . '/' . ltrim($opt['pulihkan'], '/');
    }
    if (!is_file($path)) {
        fwrite(STDERR, "Berkas cadangan tidak ditemukan: {$opt['pulihkan']}\n");
        exit(1);
    }
    echo "[2/7] Memulihkan dari " . basename($path) . " ... ";
    list($ok, $err) = impor_sql($mysqli, $path);
    if (!$ok) {
        fwrite(STDERR, "GAGAL: $err\n");
        exit(1);
    }
    echo "selesai.\n";
} else {
    echo "[2/7] Mengimpor skema dasar (master.sql) ... ";
    list($ok, $err) = impor_sql($mysqli, $akar . '/assets/app/db/master.sql');
    if (!$ok) {
        fwrite(STDERR, "GAGAL: $err\n");
        exit(1);
    }
    echo "selesai.\n";
}

// ---------------------------------------------------------------------
// 4. Tabel tambahan
// ---------------------------------------------------------------------
$tambahan = $akar . '/docs/database_gabungan.sql';
if (is_file($tambahan)) {
    echo "[3/7] Mengimpor tabel tambahan ... ";
    list($ok, $err) = impor_sql($mysqli, $tambahan);
    if (!$ok) {
        echo "sebagian gagal ($err) — aman, aplikasi membuatnya otomatis.\n";
    } else {
        echo "selesai.\n";
    }
} else {
    echo "[3/7] docs/database_gabungan.sql tidak ada — dilewati.\n";
}

// ---------------------------------------------------------------------
// 5. Tulis konfigurasi database
// ---------------------------------------------------------------------
if (is_file($config_path)) {
    @copy($config_path, $config_path . '.bak-' . date('Ymd-His'));
}
$tpl = <<<'PHPTPL'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'      => '',
    'hostname' => '{HOST}',
    'username' => '{USER}',
    'password' => '{PASS}',
    'database' => '{DB}',
    'port'     => {PORT},
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_general_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
PHPTPL;
$isi = str_replace(
    array('{HOST}', '{USER}', '{PASS}', '{DB}', '{PORT}'),
    array(addslashes($opt['host']), addslashes($opt['user']), addslashes($opt['pass']), addslashes($opt['db']), (int) $opt['port']),
    $tpl
);
file_put_contents($config_path, $isi . "\n");
@chmod($config_path, 0640);
echo "[4/7] application/config/database.php ditulis.\n";

// ---------------------------------------------------------------------
// 5b. Data awal (sekolah, tahun pelajaran, semester)
// ---------------------------------------------------------------------
function seed_awal(mysqli $db)
{
    $hasil = array();

    // Profil sekolah
    $q = $db->query("SELECT COUNT(*) AS c FROM setting");
    if ($q && (int) $q->fetch_assoc()['c'] === 0) {
        $tahun = (int) date('Y');
        $bulan = (int) date('n');
        $ta = $bulan >= 7 ? $tahun . '/' . ($tahun + 1) : ($tahun - 1) . '/' . $tahun;

        $stmt = $db->prepare(
            "INSERT INTO setting (id_setting, sekolah, jenjang, satuan_pendidikan, alamat, desa, kota, kecamatan, provinsi, kepsek, nama_aplikasi)
             VALUES (1, ?, 2, '2', '', '', '', '', '', '', 'CBT Sekolah')"
        );
        $nama = 'Sekolah';
        $stmt->bind_param('s', $nama);
        $stmt->execute();
        $stmt->close();
        $hasil[] = 'profil sekolah';
    }

    // Tahun pelajaran aktif
    $q = $db->query("SELECT COUNT(*) AS c FROM master_tp");
    $jml_tp = $q ? (int) $q->fetch_assoc()['c'] : 0;
    if ($jml_tp === 0) {
        $tahun = (int) date('Y');
        $bulan = (int) date('n');
        $ta = $bulan >= 7 ? $tahun . '/' . ($tahun + 1) : ($tahun - 1) . '/' . $tahun;
        $stmt = $db->prepare("INSERT INTO master_tp (tahun, active) VALUES (?, 1)");
        $stmt->bind_param('s', $ta);
        $stmt->execute();
        $stmt->close();
        $hasil[] = "tahun pelajaran $ta";
    } else {
        $q = $db->query("SELECT COUNT(*) AS c FROM master_tp WHERE active = 1");
        if ($q && (int) $q->fetch_assoc()['c'] === 0) {
            $db->query("UPDATE master_tp SET active = 1 ORDER BY tahun DESC LIMIT 1");
            $hasil[] = 'tahun pelajaran aktif';
        }
    }

    // Pastikan ada semester aktif
    $q = $db->query("SELECT COUNT(*) AS c FROM master_smt");
    if ($q && (int) $q->fetch_assoc()['c'] > 0) {
        $q = $db->query("SELECT COUNT(*) AS c FROM master_smt WHERE active = 1");
        if ($q && (int) $q->fetch_assoc()['c'] === 0) {
            $db->query("UPDATE master_smt SET active = 1 WHERE smt = 'Ganjil' LIMIT 1");
            $hasil[] = 'semester aktif';
        }
    }

    // Semester
    $q = $db->query("SELECT COUNT(*) AS c FROM master_smt");
    if ($q && (int) $q->fetch_assoc()['c'] === 0) {
        $db->query("INSERT INTO master_smt (smt, nama_smt, active) VALUES ('Ganjil', 'I (satu)', 1), ('Genap', 'II (dua)', 0)");
        $hasil[] = 'semester Ganjil/Genap';
    }

    return $hasil;
}

$seed = seed_awal($mysqli);
echo "[5/7] Data awal: " . ($seed ? implode(', ', $seed) : 'sudah ada') . ".\n";
// ---------------------------------------------------------------------
// 6. Akun administrator (hanya instalasi baru)
// ---------------------------------------------------------------------
if ($sudah_terpasang) {
    echo "[6/7] Akun administrator tidak diubah (sudah ada).\n";
} else {
    $r = $mysqli->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc();
    if ((int) $r['c'] > 0) {
        echo "[6/7] Sudah ada akun — pembuatan admin dilewati.\n";
    } else {
        $sandi = password_hash($opt['sandi'], PASSWORD_BCRYPT);
        $nama  = trim($opt['nama']);
        $bagian = explode(' ', $nama);
        $depan = $bagian[0];
        $belakang = count($bagian) > 1 ? implode(' ', array_slice($bagian, 1)) : '';
        $stmt = $mysqli->prepare(
            "INSERT INTO users (ip_address, username, password, email, created_on, last_login, active, first_name, last_name, company, phone)
             VALUES ('127.0.0.1', ?, ?, ?, ?, NULL, 1, ?, ?, '', '')"
        );
        $waktu = time();
        $stmt->bind_param('sssiss', $opt['admin'], $sandi, $opt['email'], $waktu, $depan, $belakang);
        if (!$stmt->execute()) {
            fwrite(STDERR, "Gagal membuat admin: " . $stmt->error . "\n");
            exit(1);
        }
        $id_admin = $stmt->insert_id;
        $stmt->close();
        $mysqli->query("INSERT INTO users_groups (user_id, group_id) VALUES (" . (int) $id_admin . ", 1)");
        echo "[6/7] Akun administrator dibuat: {$opt['admin']} / {$opt['sandi']}\n";
        echo "       (segera ganti password setelah login)\n";
    }
}

// ---------------------------------------------------------------------
// 7. Folder & ringkasan
// ---------------------------------------------------------------------
foreach (array('uploads', 'backups', 'application/logs', 'application/cache') as $folder) {
    $p = $akar . '/' . $folder;
    if (!is_dir($p)) {
        @mkdir($p, 0775, true);
    }
}
echo "[7/7] Folder uploads/backups/logs/cache siap.\n\n";

echo "SELESAI.\n";
echo "Buka: http://localhost/cbt-sekolah/auth\n";
if (!$sudah_terpasang) {
    echo "Login: {$opt['admin']} / {$opt['sandi']}\n";
}
echo "\nJangan lupa di application/config/config.php:\n";
echo "  - ganti encryption_key dengan: " . bin2hex(random_bytes(16)) . "\n";
echo "  - sesuaikan date_default_timezone_set()\n";
