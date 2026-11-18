<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Buku Kas Sekolah: pencatatan pengeluaran (dan pemasukan lain di luar SPP).
 *
 * Pemasukan SPP tetap dihitung otomatis dari tabel cbt_pembayaran_spp
 * (status success) sehingga tidak perlu dicatat dua kali.
 */
class Kas_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->pastikan();
    }

    public function pastikan() {
        if (!$this->db->table_exists('cbt_kas')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS cbt_kas (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tanggal     DATE NOT NULL,
                jenis       ENUM('masuk','keluar') NOT NULL DEFAULT 'keluar',
                kategori    VARCHAR(60)  NOT NULL,
                keterangan  VARCHAR(255) NULL,
                nominal     DECIMAL(15,2) NOT NULL DEFAULT 0,
                dibuat_oleh VARCHAR(100) NULL,
                created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_tanggal (tanggal),
                KEY idx_jenis (jenis)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** Daftar transaksi kas pada rentang tanggal. */
    public function daftar($dari, $sampai, $jenis = '') {
        $sql = "SELECT * FROM cbt_kas WHERE tanggal >= ? AND tanggal <= ?";
        $bind = array($dari, $sampai);
        if ($jenis === 'masuk' || $jenis === 'keluar') {
            $sql .= " AND jenis = ?";
            $bind[] = $jenis;
        }
        $sql .= " ORDER BY tanggal DESC, id DESC";
        return $this->db->query($sql, $bind)->result();
    }

    public function get($id) {
        return $this->db->get_where('cbt_kas', array('id' => (int) $id))->row();
    }

    public function simpan($data) {
        $this->db->insert('cbt_kas', $data);
        return $this->db->insert_id();
    }

    public function hapus($id) {
        $this->db->where('id', (int) $id)->delete('cbt_kas');
        return $this->db->affected_rows() > 0;
    }

    /** Ringkasan SPP yang sudah dibayar (success) pada rentang tanggal. */
    public function spp_rentang($dari, $sampai) {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(jumlah_bayar),0) AS total, COUNT(*) AS jml
               FROM cbt_pembayaran_spp
              WHERE status_pembayaran = 'success'
                AND DATE(tanggal_bayar) BETWEEN ? AND ?",
            array($dari, $sampai)
        )->row();
        return $row ? $row : (object) array('total' => 0, 'jml' => 0);
    }

    /** Total SPP yang sudah diterima (semua waktu). */
    public function spp_total() {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(jumlah_bayar),0) AS total FROM cbt_pembayaran_spp WHERE status_pembayaran = 'success'"
        )->row();
        return $row ? (float) $row->total : 0.0;
    }

    /** Total kas masuk / keluar. */
    public function total_jenis($jenis, $dari = null, $sampai = null) {
        $sql = "SELECT COALESCE(SUM(nominal),0) AS total FROM cbt_kas WHERE jenis = ?";
        $bind = array($jenis);
        if ($dari !== null && $sampai !== null) {
            $sql .= " AND tanggal BETWEEN ? AND ?";
            $bind[] = $dari;
            $bind[] = $sampai;
        }
        $row = $this->db->query($sql, $bind)->row();
        return $row ? (float) $row->total : 0.0;
    }

    /** Rekap per bulan dalam satu tahun: masuk, keluar, spp. */
    public function rekap_bulanan($tahun) {
        $hasil = array();
        for ($b = 1; $b <= 12; $b++) {
            $dari   = sprintf('%04d-%02d-01', (int) $tahun, $b);
            $sampai = date('Y-m-t', strtotime($dari));
            $spp    = $this->spp_rentang($dari, $sampai);
            $hasil[] = (object) array(
                'bulan' => $b,
                'spp'   => (float) $spp->total,
                'masuk' => $this->total_jenis('masuk', $dari, $sampai),
                'keluar' => $this->total_jenis('keluar', $dari, $sampai)
            );
        }
        return $hasil;
    }

    public function nama_bulan($b) {
        $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        return isset($bulan[(int) $b]) ? $bulan[(int) $b] : '';
    }
}
