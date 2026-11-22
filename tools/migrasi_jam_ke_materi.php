<?php
/**
 * Migrasi E-1: tambah kolom jam_ke pada kelas_jadwal_materi dan isi dari id_kjm.
 * Format id_kjm lama: id_kelas . id_tp . id_smt . Ymd . jam_ke . jenis
 * (tanpa pemisah; prefix id_kelas/id_tp/id_smt diambil dari kolom).
 *
 * CLI: php tools/migrasi_jam_ke_materi.php [host] [user] [pass] [db]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$host = $argv[1] ?? 'localhost';
$user = $argv[2] ?? 'root';
$pass = $argv[3] ?? '';
$db   = $argv[4] ?? 'cbt';

$mysqli = @new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Koneksi gagal: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

function kolom_ada($mysqli, $db, $tabel, $kolom)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?");
    $stmt->bind_param('sss', $db, $tabel, $kolom);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r && (int) $r['c'] > 0;
}

if (!kolom_ada($mysqli, $db, 'kelas_jadwal_materi', 'jam_ke')) {
    if (!$mysqli->query("ALTER TABLE kelas_jadwal_materi ADD COLUMN jam_ke TINYINT NULL AFTER jadwal_materi")) {
        fwrite(STDERR, "Gagal menambah kolom: {$mysqli->error}\n");
        exit(1);
    }
    echo "Kolom jam_ke ditambahkan.\n";
} else {
    echo "Kolom jam_ke sudah ada.\n";
}

$res = $mysqli->query("SELECT id_kjm, id_kelas, id_tp, id_smt, jadwal_materi, jenis, jam_ke FROM kelas_jadwal_materi");
$ok = 0;
$gagal = 0;
$update = $mysqli->prepare("UPDATE kelas_jadwal_materi SET jam_ke=? WHERE id_kjm=?");

while ($row = $res->fetch_assoc()) {
    if ($row['jam_ke'] !== null) {
        $ok++;
        continue;
    }
    $prefix = (string) $row['id_kelas'] . (string) $row['id_tp'] . (string) $row['id_smt'];
    $sisa = (string) $row['id_kjm'];
    if (strpos($sisa, $prefix) === 0) {
        $sisa = substr($sisa, strlen($prefix));
    }
    $panjang = strlen($sisa);
    if ($panjang < 10) {
        $gagal++;
        fwrite(STDERR, "Lewati (format pendek): {$row['id_kjm']}\n");
        continue;
    }
    $tanggal = substr($sisa, 0, 8);
    $jenis = substr($sisa, -1);
    $jam = substr($sisa, 8, $panjang - 9);
    if (!ctype_digit($jam) || $jenis !== (string) $row['jenis']
        || $tanggal !== date('Ymd', strtotime((string) $row['jadwal_materi']))) {
        $gagal++;
        fwrite(STDERR, "Perlu dicek manual: {$row['id_kjm']} (prefix=$prefix sisa=$sisa)\n");
        continue;
    }
    $jamInt = (int) $jam;
    $update->bind_param('is', $jamInt, $row['id_kjm']);
    if ($update->execute()) {
        $ok++;
    } else {
        $gagal++;
    }
}
$update->close();

echo "Terisi/valid: $ok\n";
echo "Gagal/perlu manual: $gagal\n";

$dup = $mysqli->query("SELECT COUNT(*) c FROM (SELECT 1 FROM kelas_jadwal_materi GROUP BY id_kelas,id_tp,id_smt,jadwal_materi,jam_ke,jenis HAVING COUNT(*)>1) x");
$cdup = $dup ? (int) $dup->fetch_assoc()['c'] : -1;
echo "Kombinasi duplikat setelah backfill: $cdup\n";

if ($gagal === 0 && $cdup === 0 && !kolom_ada($mysqli, $db, 'kelas_jadwal_materi', 'uniq_jam_ke')) {
    // Cek index unique berdasarkan nama.
    $idx = $mysqli->query("SHOW INDEX FROM kelas_jadwal_materi WHERE Key_name='uk_jadwal_materi'");
    if ($idx && $idx->num_rows === 0) {
        if ($mysqli->query("ALTER TABLE kelas_jadwal_materi ADD UNIQUE KEY uk_jadwal_materi (id_kelas,id_tp,id_smt,jadwal_materi,jam_ke,jenis)")) {
            echo "Unique key uk_jadwal_materi ditambahkan.\n";
        } else {
            fwrite(STDERR, "Gagal menambah unique key: {$mysqli->error}\n");
        }
    }
}

$mysqli->close();
exit($gagal > 0 ? 2 : 0);
