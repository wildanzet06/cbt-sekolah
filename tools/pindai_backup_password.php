<?php
/**
 * Pindai backup SQL lama untuk mencari bukti password plaintext
 * pada master_siswa / master_guru (C-5).
 *
 * Jalankan dari CLI:
 *   php tools/pindai_backup_password.php [folder_backup]
 *
 * Tidak mengubah/menghapus file. Hasil berupa daftar file + baris INSERT
 * yang masih berpotensi memuat password plaintext.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$dir = isset($argv[1]) ? $argv[1] : __DIR__ . '/../backups';
$dir = rtrim($dir, '/\\');
if (!is_dir($dir)) {
    fwrite(STDERR, "Folder backup tidak ditemukan: $dir\n");
    exit(1);
}

$tabel = array('master_siswa', 'master_guru');
$files = glob($dir . '/*.{sql,txt,gz}', GLOB_BRACE);
$total = 0;
$bermasalah = 0;

echo "Memindai: $dir\n";
echo str_repeat('=', 60) . "\n";

foreach ($files as $file) {
    $nama = basename($file);
    $isi = @file_get_contents($file);
    if ($isi === false) {
        continue;
    }
    if (substr($nama, -3) === '.gz') {
        $unzip = @gzdecode($isi);
        if ($unzip !== false) {
            $isi = $unzip;
        }
    }

    $total++;
    $temuan = array();
    foreach ($tabel as $t) {
        $ada_insert = preg_match_all('/INSERT\s+INTO\s+`?' . preg_quote($t, '/') . '`?\s*(?:\([^)]*\))?\s*VALUES/is', $isi, $m);
        $ada_tabel = preg_match('/CREATE\s+TABLE\s+`?' . preg_quote($t, '/') . '`?/is', $isi);
        if ($ada_insert && $ada_tabel) {
            // Kolom password berada di posisi tertentu; nilai tidak diberi nama kolom.
            // Anggap seluruh baris berpotensi memuat password plaintext.
            $temuan[$t] = (int) $ada_insert;
        }
    }

    if ($temuan) {
        $bermasalah++;
        echo "[PERLU TINDAKAN] $nama\n";
        foreach ($temuan as $t => $n) {
            echo "  - $t: $n blok INSERT berpotensi memuat password plaintext\n";
        }
    } else {
        echo "[aman] $nama\n";
    }
}

echo str_repeat('=', 60) . "\n";
echo "Total file diperiksa: $total\n";
echo "File memuat password plaintext: $bermasalah\n";
echo "\nTindakan C-5:\n";
echo "1. Pindahkan backup lama ke penyimpanan terenkripsi atau musnahkan.\n";
echo "2. Anggap semua password yang pernah tersimpan plaintext sudah bocor.\n";
echo "3. Reset password akun terkait lewat menu Usersiswa (wajib ganti saat login).\n";
exit($bermasalah > 0 ? 2 : 0);
