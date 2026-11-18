<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Backup & restore database.
 *
 * - Backup memakai mysqldump (kalau tersedia). Bila tidak ditemukan,
 *   fallback ke CI dbutil (zip).
 * - Restore memakai mysqli::multi_query sehingga file .sql hasil dump
 *   (termasuk yang dibuat mysqldump) bisa dijalankan sekaligus.
 * - Backup otomatis dibuat sekali sehari (dipanggil dari Akses_hook saat
 *   admin membuka halaman), disimpan 14 hari terakhir.
 */
class Backup_model extends CI_Model {

    const SIMPAN_HARI = 14;

    public function __construct() {
        parent::__construct();
        $this->load->dbutil();
    }

    public function direktori() {
        $dir = FCPATH . 'backups' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /** Daftar file backup (terbaru di atas). */
    public function daftar() {
        $dir  = $this->direktori();
        $list = array();
        foreach ((array) glob($dir . '*.sql') as $path) {
            if (!is_file($path)) continue;
            $nama = basename($path);
            $list[] = (object) array(
                'nama'  => $nama,
                'ukuran' => filesize($path),
                'waktu' => filemtime($path),
                'auto'  => (strpos($nama, 'auto-') === 0),
                'aman'  => (bool) preg_match('/^(auto|manual|pre-restore)-[A-Za-z0-9._-]+\.sql$/', $nama)
            );
        }
        // zip lama (dari menu Database sebelumnya) tetap ditampilkan
        foreach ((array) glob($dir . '*.zip') as $path) {
            if (!is_file($path)) continue;
            $list[] = (object) array(
                'nama' => basename($path), 'ukuran' => filesize($path),
                'waktu' => filemtime($path), 'auto' => false, 'aman' => false
            );
        }
        usort($list, function ($a, $b) { return $b->waktu - $a->waktu; });
        return $list;
    }

    public function terakhir($auto = null) {
        foreach ($this->daftar() as $f) {
            if ($auto === null || $f->auto === $auto) return $f;
        }
        return null;
    }

    /** Cari executable mysqldump. */
    private function mysqldump() {
        $pola = array(
            'C:/laragon/bin/mysql/mysql-*/bin/mysqldump.exe',
            'C:/laragon/bin/mysql/mariadb-*/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/wamp64/bin/mysql/*/bin/mysqldump.exe'
        );
        foreach ($pola as $p) {
            foreach ((array) glob($p) as $bin) {
                if (is_file($bin)) return $bin;
            }
        }
        // PATH
        $cek = trim((string) @shell_exec('where mysqldump 2>NUL'));
        if ($cek !== '') {
            $baris = preg_split('/\r?\n/', $cek);
            if (!empty($baris[0]) && is_file(trim($baris[0]))) return trim($baris[0]);
        }
        return null;
    }

    /**
     * Buat backup baru.
     * @return array (ok => bool, file|pesan)
     */
    public function buat($jenis = 'auto') {
        $dir  = $this->direktori();
        $nama = $jenis . '-' . date('Y-m-d_His') . '.sql';
        $path = $dir . $nama;

        $db  = $this->db;
        $bin = $this->mysqldump();

        if ($bin) {
            $pass = (string) $db->password;
            $cmd  = escapeshellarg($bin)
                . ' -h' . escapeshellarg((string) $db->hostname)
                . ' -P' . (int) ($db->port ? $db->port : 3306)
                . ' -u' . escapeshellarg((string) $db->username)
                . ($pass !== '' ? ' -p' . escapeshellarg($pass) : '')
                . ' --single-transaction --skip-lock-tables --routines --events'
                . ' ' . escapeshellarg((string) $db->database)
                . ' > ' . escapeshellarg($path)
                . (PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null');

            @exec($cmd, $keluaran, $kode);
            if ($kode === 0 && is_file($path) && filesize($path) > 1024) {
                $this->pangkas();
                return array('ok' => true, 'file' => $nama);
            }
            @unlink($path);
            $pesan = trim(implode(' ', (array) $keluaran));
            log_message('error', 'Backup mysqldump gagal: ' . $pesan);
        }

        // Fallback: CI dbutil (format zip)
        $prefs = array(
            'tables'     => $db->list_tables(),
            'ignore'     => array(),
            'format'     => 'txt',
            'filename'   => $nama,
            'add_drop'   => true,
            'add_insert' => true,
            'newline'    => "\n"
        );
        $isi = $this->dbutil->backup($prefs);
        if ($isi && strlen($isi) > 1024) {
            if (@file_put_contents($path, $isi)) {
                $this->pangkas();
                return array('ok' => true, 'file' => $nama);
            }
        }
        return array('ok' => false, 'pesan' => 'Backup gagal dibuat (mysqldump & dbutil tidak berhasil).');
    }

    /** Backup otomatis sekali sehari. */
    public function buat_otomatis() {
        $hari_ini = $this->direktori() . 'auto-' . date('Y-m-d') . '_';
        foreach ((array) glob($hari_ini . '*.sql') as $ada) {
            if (filesize($ada) > 1024) return true; // sudah ada hari ini
        }
        $hasil = $this->buat('auto');
        if (!$hasil['ok']) {
            log_message('error', 'Backup otomatis gagal: ' . (isset($hasil['pesan']) ? $hasil['pesan'] : '-'));
            return false;
        }
        return true;
    }

    /** Hapus backup otomatis yang lebih tua dari SIMPAN_HARI hari. */
    public function pangkas() {
        $batas = time() - (self::SIMPAN_HARI * 86400);
        foreach ((array) glob($this->direktori() . 'auto-*.sql') as $f) {
            if (is_file($f) && filemtime($f) < $batas) {
                @unlink($f);
            }
        }
    }

    /** Nama file aman (tanpa path). */
    public function nama_aman($nama) {
        $nama = basename((string) $nama);
        return (bool) preg_match('/^(auto|manual|pre-restore)-[A-Za-z0-9._-]+\.sql$/', $nama);
    }

    /**
     * Pulihkan database dari file backup.
     * @return array (ok => bool, pesan)
     */
    public function pulihkan($nama) {
        $nama = basename((string) $nama);
        if (!$this->nama_aman($nama)) {
            return array('ok' => false, 'pesan' => 'Nama file backup tidak valid.');
        }
        $path = $this->direktori() . $nama;
        if (!is_file($path)) {
            return array('ok' => false, 'pesan' => 'File backup tidak ditemukan.');
        }

        // Amankan data saat ini dulu
        $this->buat('pre-restore');

        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            return array('ok' => false, 'pesan' => 'File backup kosong / tidak bisa dibaca.');
        }
        // Buang BOM dan baris peringatan yang kadang ikut tertulis di awal file
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
        $sql = preg_replace('/^(?:(?:WARNING|mysqldump|mariadb-dump)[^\r\n]*\r?\n)+/i', '', $sql);

        $conn = $this->db->conn_id;
        if (!($conn instanceof mysqli)) {
            return array('ok' => false, 'pesan' => 'Restore hanya didukung untuk driver mysqli.');
        }

        if (!@$conn->multi_query($sql)) {
            return array('ok' => false, 'pesan' => 'Restore gagal: ' . $conn->error);
        }
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        if ($conn->errno) {
            return array('ok' => false, 'pesan' => 'Restore berhenti di tengah jalan: ' . $conn->error);
        }
        return array('ok' => true, 'pesan' => 'Database berhasil dipulihkan dari ' . $nama . '.');
    }

    public function hapus($nama) {
        $nama = basename((string) $nama);
        if (!$this->nama_aman($nama)) {
            return false;
        }
        $path = $this->direktori() . $nama;
        return is_file($path) ? @unlink($path) : false;
    }

    public function ukuran($bytes) {
        $bytes = (float) $bytes;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        if ($bytes >= 1024)    return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        return (int) $bytes . ' B';
    }
}
