<?php
/**
 * E-1: tambah unique key pada kombinasi alami tabel rapor/CBT
 * yang sudah aman (tanpa duplikat).
 *
 * CLI:
 *   php tools/migrasi_kunci_unik_rapor.php            # dry-run
 *   php tools/migrasi_kunci_unik_rapor.php --apply    # tambah index
 *   php tools/migrasi_kunci_unik_rapor.php --apply host user pass db
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$args = array_slice($argv, 1);
$apply = false;
if (isset($args[0]) && $args[0] === '--apply') {
    $apply = true;
    array_shift($args);
}
$host = $args[0] ?? 'localhost';
$user = $args[1] ?? 'root';
$pass = $args[2] ?? '';
$db   = $args[3] ?? 'cbt';

$mysqli = @new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Koneksi gagal: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$target = array(
    array('tabel' => 'cbt_nilai', 'nama' => 'uk_nilai_siswa_jadwal', 'kolom' => array('id_siswa', 'id_jadwal')),
    array('tabel' => 'cbt_durasi_siswa', 'nama' => 'uk_durasi_siswa_jadwal', 'kolom' => array('id_siswa', 'id_jadwal')),
    array('tabel' => 'cbt_soal_siswa', 'nama' => 'uk_soal_siswa_jadwal_soal', 'kolom' => array('id_siswa', 'id_jadwal', 'id_soal')),
    array('tabel' => 'rapor_naik', 'nama' => 'uk_naik_siswa_tp_smt', 'kolom' => array('id_siswa', 'id_tp', 'id_smt')),
    array('tabel' => 'rapor_data_sikap', 'nama' => 'uk_sikap_natural', 'kolom' => array('id_kelas', 'jenis', 'kode', 'id_tp', 'id_smt')),
    array('tabel' => 'rapor_data_catatan', 'nama' => 'uk_catatan_natural', 'kolom' => array('id_kelas', 'jenis', 'kode', 'id_tp', 'id_smt')),
);

function tabel_ada($mysqli, $db, $tabel)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=? AND table_name=?");
    $stmt->bind_param('ss', $db, $tabel);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r && (int) $r['c'] > 0;
}

function index_ada($mysqli, $db, $tabel, $nama)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) c FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=?");
    $stmt->bind_param('sss', $db, $tabel, $nama);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r && (int) $r['c'] > 0;
}

$siap = 0;
$bermasalah = 0;
echo "Migrasi unique key rapor/CBT — database $db" . ($apply ? ' (APPLY)' : ' (DRY-RUN)') . "\n";
echo str_repeat('=', 64) . "\n";

foreach ($target as $t) {
    $tabel = $t['tabel'];
    if (!tabel_ada($mysqli, $db, $tabel)) {
        echo "[lewati] $tabel (tabel tidak ada)\n";
        continue;
    }
    $kol = '`' . implode('`,`', $t['kolom']) . '`';
    $q = $mysqli->query("SELECT COUNT(*) c FROM (SELECT 1 FROM `$tabel` GROUP BY $kol HAVING COUNT(*)>1) x");
    $dup = $q ? (int) $q->fetch_assoc()['c'] : -1;
    if ($dup !== 0) {
        $bermasalah++;
        echo "[TOLAK] $tabel: $dup kombinasi duplikat — bersihkan dulu\n";
        continue;
    }
    if (index_ada($mysqli, $db, $tabel, $t['nama'])) {
        echo "[ada] $tabel.{$t['nama']}\n";
        continue;
    }
    if (!$apply) {
        $siap++;
        echo "[siap] $tabel -> {$t['nama']}\n";
        continue;
    }
    $sql = "ALTER TABLE `$tabel` ADD UNIQUE KEY `{$t['nama']}` ($kol)";
    if ($mysqli->query($sql)) {
        $siap++;
        echo "[OK] $tabel.{$t['nama']}\n";
    } else {
        $bermasalah++;
        fwrite(STDERR, "[GAGAL] $tabel: {$mysqli->error}\n");
    }
}

echo str_repeat('=', 64) . "\n";
echo ($apply ? "Index dibuat: $siap" : "Siap dibuat: $siap") . "\n";
echo "Bermasalah/ditolak: $bermasalah\n";
if (!$apply && $siap > 0) {
    echo "Jalankan ulang dengan --apply setelah backup.\n";
}
$mysqli->close();
exit($bermasalah > 0 ? 2 : 0);
