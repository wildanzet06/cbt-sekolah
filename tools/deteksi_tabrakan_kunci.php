<?php
/**
 * Deteksi tabrakan kunci gabungan (PRD E-1/E-2).
 * CLI: php tools/deteksi_tabrakan_kunci.php [host] [user] [pass] [db]
 * Tidak mengubah data; keluar kode 2 bila ada temuan.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$host = isset($argv[1]) ? $argv[1] : 'localhost';
$user = isset($argv[2]) ? $argv[2] : 'root';
$pass = isset($argv[3]) ? $argv[3] : '';
$db   = isset($argv[4]) ? $argv[4] : 'cbt';

$mysqli = @new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Koneksi gagal: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$cek = array(
    array('tabel' => 'cbt_nilai', 'kunci' => 'id_nilai', 'alami' => array('id_siswa', 'id_jadwal'), 'formula' => "CONCAT(id_siswa,'0',id_jadwal)"),
    array('tabel' => 'cbt_durasi_siswa', 'kunci' => 'id_durasi', 'alami' => array('id_siswa', 'id_jadwal'), 'formula' => "CONCAT(id_siswa,'0',id_jadwal)"),
    array('tabel' => 'cbt_soal_siswa', 'kunci' => 'id_soal_siswa', 'alami' => array('id_siswa', 'id_jadwal', 'id_soal'), 'formula' => null),
    array('tabel' => 'rapor_naik', 'kunci' => 'id_naik', 'alami' => array('id_siswa', 'id_tp', 'id_smt'), 'formula' => null),
    array('tabel' => 'rapor_data_sikap', 'kunci' => 'id_sikap', 'alami' => array('id_kelas', 'jenis', 'kode', 'id_tp', 'id_smt'), 'formula' => null),
    array('tabel' => 'rapor_data_catatan', 'kunci' => 'id_catatan', 'alami' => array('id_kelas', 'jenis', 'kode', 'id_tp', 'id_smt'), 'formula' => null),
    array('tabel' => 'kelas_jadwal_materi', 'kunci' => 'id_kjm', 'alami' => array('id_kelas', 'id_tp', 'id_smt', 'jadwal_materi', 'jam_ke', 'jenis'), 'formula' => null),
);

function kolom_ada($mysqli, $db, $tabel, $kolom)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?");
    $stmt->bind_param('sss', $db, $tabel, $kolom);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && (int) $row['c'] > 0;
}

function tabel_ada($mysqli, $db, $tabel)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=? AND table_name=?");
    $stmt->bind_param('ss', $db, $tabel);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && (int) $row['c'] > 0;
}

$temuan = 0;
echo "Deteksi tabrakan kunci — database $db\n";
echo str_repeat('=', 64) . "\n";

foreach ($cek as $c) {
    $t = $c['tabel'];
    if (!tabel_ada($mysqli, $db, $t)) {
        echo "[lewati] $t (tabel tidak ada)\n";
        continue;
    }
    $ada = true;
    foreach (array_merge(array($c['kunci']), $c['alami']) as $k) {
        if (!kolom_ada($mysqli, $db, $t, $k)) {
            $ada = false;
            break;
        }
    }
    if (!$ada) {
        echo "[lewati] $t (kolom tidak lengkap)\n";
        continue;
    }

    $status = array();

    $k = $mysqli->real_escape_string($c['kunci']);
    $q = $mysqli->query("SELECT `$k` AS k, COUNT(*) AS n FROM `$t` GROUP BY `$k` HAVING n > 1");
    if ($q && $q->num_rows > 0) {
        $status[] = 'kunci duplikat: ' . $q->num_rows;
    }

    $kol = implode(',', array_map(function ($x) use ($mysqli) { return '`' . $mysqli->real_escape_string($x) . '`'; }, $c['alami']));
    $q2 = $mysqli->query("SELECT $kol, COUNT(*) AS n FROM `$t` GROUP BY $kol HAVING n > 1");
    if ($q2 && $q2->num_rows > 0) {
        $status[] = 'kombinasi alami duplikat: ' . $q2->num_rows;
    }

    if (!empty($c['formula'])) {
        $q3 = $mysqli->query("SELECT COUNT(*) AS n FROM `$t` WHERE `$k` <> " . $c['formula']);
        $r3 = $q3 ? $q3->fetch_assoc() : null;
        if ($r3 && (int) $r3['n'] > 0) {
            $status[] = 'kunci tidak sesuai formula: ' . (int) $r3['n'];
        }
    }

    if ($status) {
        $temuan++;
        echo "[TEMUAN] $t => " . implode('; ', $status) . "\n";
    } else {
        echo "[aman] $t\n";
    }
}

echo str_repeat('=', 64) . "\n";
echo "Tabel bermasalah: $temuan\n";
echo "Jika ada temuan: backup, hitung ulang kunci, lalu migrasi ke kunci surrogate/unique.\n";
$mysqli->close();
exit($temuan > 0 ? 2 : 0);
