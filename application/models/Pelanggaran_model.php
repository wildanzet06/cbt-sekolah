<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model poin pelanggaran siswa.
 *
 * Tabel utama: cbt_pelanggaran_siswa (dibuat oleh docs/database_tambahan.sql).
 * Model ini hanya membaca data siswa/kelas yang sudah ada dan tidak mengubahnya.
 */
class Pelanggaran_model extends CI_Model {

    // =====================================================================
    // KELAS & SISWA
    // =====================================================================

    // Kelas yang punya siswa pada tahun pelajaran + semester tertentu
    public function daftar_kelas($id_tp, $id_smt) {
        $sql = "SELECT DISTINCT mk.id_kelas, mk.nama_kelas
                FROM kelas_siswa ks
                JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                WHERE ks.id_tp = ? AND ks.id_smt = ?
                ORDER BY mk.nama_kelas ASC";
        return $this->db->query($sql, array((int) $id_tp, (int) $id_smt))->result();
    }

    public function kelas_valid($id_kelas, $id_tp, $id_smt) {
        $this->db->where('id_kelas', (int) $id_kelas);
        $this->db->where('id_tp', (int) $id_tp);
        $this->db->where('id_smt', (int) $id_smt);
        return $this->db->count_all_results('kelas_siswa') > 0;
    }

    public function nama_kelas($id_kelas) {
        $r = $this->db->get_where('master_kelas', array('id_kelas' => (int) $id_kelas))->row();
        return $r ? $r->nama_kelas : '-';
    }

    // Daftar siswa di sebuah kelas (urut nama)
    public function roster($id_kelas, $id_tp, $id_smt) {
        $sql = "SELECT DISTINCT s.id_siswa, s.nis, s.nama
                FROM kelas_siswa ks
                JOIN master_siswa s ON s.id_siswa = ks.id_siswa
                WHERE ks.id_tp = ? AND ks.id_smt = ? AND ks.id_kelas = ?
                ORDER BY s.nama ASC";
        return $this->db->query($sql, array((int) $id_tp, (int) $id_smt, (int) $id_kelas))->result();
    }

    // Semua siswa pada periode aktif beserta kelasnya (untuk pilihan modal input)
    public function semua_siswa($id_tp, $id_smt) {
        $sql = "SELECT s.id_siswa, s.nis, s.nama, ks.id_kelas, mk.nama_kelas
                FROM kelas_siswa ks
                JOIN master_siswa s ON s.id_siswa = ks.id_siswa
                LEFT JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                WHERE ks.id_tp = ? AND ks.id_smt = ?
                ORDER BY mk.nama_kelas ASC, s.nama ASC";
        return $this->db->query($sql, array((int) $id_tp, (int) $id_smt))->result();
    }

    public function siswa_by_id($id) {
        return $this->db->select('id_siswa, nis, nama, username')
                        ->get_where('master_siswa', array('id_siswa' => (int) $id))->row();
    }

    // Kelas siswa pada periode tertentu (snapshot kelas saat dicatat)
    public function kelas_siswa($id_siswa, $id_tp, $id_smt) {
        $sql = "SELECT ks.id_kelas, mk.nama_kelas
                FROM kelas_siswa ks
                LEFT JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                WHERE ks.id_siswa = ? AND ks.id_tp = ? AND ks.id_smt = ?
                LIMIT 1";
        return $this->db->query($sql, array((int) $id_siswa, (int) $id_tp, (int) $id_smt))->row();
    }

    // =====================================================================
    // DATA PELANGGARAN
    // =====================================================================

    public function get($id) {
        $sql = "SELECT p.*, s.nis, s.nama, mk.nama_kelas
                FROM cbt_pelanggaran_siswa p
                JOIN master_siswa s ON s.id_siswa = p.id_siswa
                LEFT JOIN master_kelas mk ON mk.id_kelas = p.id_kelas
                WHERE p.id_pelanggaran = ?";
        return $this->db->query($sql, array((int) $id))->row();
    }

    /**
     * Daftar catatan pelanggaran pada satu periode.
     * $id_kelas = 0 berarti semua kelas. $dari/$sampai kosong berarti tanpa batas.
     */
    public function daftar($id_tp, $id_smt, $id_kelas = 0, $dari = '', $sampai = '') {
        $sql = "SELECT p.*, s.nis, s.nama, mk.nama_kelas
                FROM cbt_pelanggaran_siswa p
                JOIN master_siswa s ON s.id_siswa = p.id_siswa
                LEFT JOIN master_kelas mk ON mk.id_kelas = p.id_kelas
                WHERE p.id_tp = ? AND p.id_smt = ?";
        $params = array((int) $id_tp, (int) $id_smt);

        if ($id_kelas) {
            $sql .= " AND p.id_kelas = ?";
            $params[] = (int) $id_kelas;
        }
        if ($dari !== '') {
            $sql .= " AND p.tanggal >= ?";
            $params[] = $dari;
        }
        if ($sampai !== '') {
            $sql .= " AND p.tanggal <= ?";
            $params[] = $sampai;
        }

        $sql .= " ORDER BY p.tanggal DESC, p.id_pelanggaran DESC";
        return $this->db->query($sql, $params)->result();
    }

    public function simpan($data) {
        $this->db->insert('cbt_pelanggaran_siswa', $data);
        return $this->db->insert_id();
    }

    public function ubah($id, $data) {
        $this->db->where('id_pelanggaran', (int) $id);
        return $this->db->update('cbt_pelanggaran_siswa', $data);
    }

    public function hapus($id) {
        $this->db->where('id_pelanggaran', (int) $id);
        return $this->db->delete('cbt_pelanggaran_siswa');
    }

    // Riwayat seorang siswa pada periode tertentu
    public function riwayat_siswa($id_siswa, $id_tp, $id_smt, $dari = '', $sampai = '') {
        $sql = "SELECT p.*, mk.nama_kelas
                FROM cbt_pelanggaran_siswa p
                LEFT JOIN master_kelas mk ON mk.id_kelas = p.id_kelas
                WHERE p.id_siswa = ? AND p.id_tp = ? AND p.id_smt = ?";
        $params = array((int) $id_siswa, (int) $id_tp, (int) $id_smt);

        if ($dari !== '') {
            $sql .= " AND p.tanggal >= ?";
            $params[] = $dari;
        }
        if ($sampai !== '') {
            $sql .= " AND p.tanggal <= ?";
            $params[] = $sampai;
        }

        $sql .= " ORDER BY p.tanggal DESC, p.id_pelanggaran DESC";
        return $this->db->query($sql, $params)->result();
    }

    // =====================================================================
    // REKAP
    // =====================================================================

    /**
     * Rekap per siswa. Semua siswa pada periode ikut tampil, termasuk yang
     * belum pernah mendapat poin (total 0), agar mudah dipantau.
     */
    public function rekap_siswa($id_tp, $id_smt, $id_kelas = 0, $dari = '', $sampai = '') {
        $params = array((int) $id_tp, (int) $id_smt);

        $join_tanggal = '';
        if ($dari !== '') {
            $join_tanggal .= " AND p.tanggal >= ?";
            $params[] = $dari;
        }
        if ($sampai !== '') {
            $join_tanggal .= " AND p.tanggal <= ?";
            $params[] = $sampai;
        }

        $params[] = (int) $id_tp;
        $params[] = (int) $id_smt;

        $where_kelas = '';
        if ($id_kelas) {
            $where_kelas = " AND ks.id_kelas = ?";
            $params[] = (int) $id_kelas;
        }

        $sql = "SELECT s.id_siswa, s.nis, s.nama, mk.id_kelas, mk.nama_kelas,
                       COUNT(p.id_pelanggaran) AS jml,
                       COALESCE(SUM(p.poin), 0) AS total_poin,
                       MAX(p.tanggal) AS terakhir
                FROM kelas_siswa ks
                JOIN master_siswa s ON s.id_siswa = ks.id_siswa
                LEFT JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                LEFT JOIN cbt_pelanggaran_siswa p
                       ON p.id_siswa = s.id_siswa AND p.id_tp = ? AND p.id_smt = ?" . $join_tanggal . "
                WHERE ks.id_tp = ? AND ks.id_smt = ?" . $where_kelas . "
                GROUP BY s.id_siswa, s.nis, s.nama, mk.id_kelas, mk.nama_kelas
                ORDER BY total_poin DESC, s.nama ASC";

        return $this->db->query($sql, $params)->result();
    }

    // Rekap per kelas (total poin seluruh siswa di kelas itu)
    public function rekap_kelas($id_tp, $id_smt, $dari = '', $sampai = '') {
        $params = array();

        $join_tanggal = '';
        if ($dari !== '') {
            $join_tanggal .= " AND p.tanggal >= ?";
            $params[] = $dari;
        }
        if ($sampai !== '') {
            $join_tanggal .= " AND p.tanggal <= ?";
            $params[] = $sampai;
        }

        $params[] = (int) $id_tp;
        $params[] = (int) $id_smt;

        $sql = "SELECT mk.id_kelas, mk.nama_kelas,
                       COUNT(DISTINCT ks.id_siswa) AS jumlah_siswa,
                       COUNT(DISTINCT CASE WHEN p.id_pelanggaran IS NOT NULL THEN ks.id_siswa END) AS siswa_terlibat,
                       COUNT(p.id_pelanggaran) AS jumlah_pelanggaran,
                       COALESCE(SUM(p.poin), 0) AS total_poin
                FROM kelas_siswa ks
                JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                LEFT JOIN cbt_pelanggaran_siswa p
                       ON p.id_siswa = ks.id_siswa AND p.id_tp = ks.id_tp AND p.id_smt = ks.id_smt" . $join_tanggal . "
                WHERE ks.id_tp = ? AND ks.id_smt = ?
                GROUP BY mk.id_kelas, mk.nama_kelas
                ORDER BY total_poin DESC, mk.nama_kelas ASC";

        $rows = $this->db->query($sql, $params)->result();
        foreach ($rows as $r) {
            $r->jumlah_siswa      = (int) $r->jumlah_siswa;
            $r->siswa_terlibat    = (int) $r->siswa_terlibat;
            $r->jumlah_pelanggaran = (int) $r->jumlah_pelanggaran;
            $r->total_poin        = (int) $r->total_poin;
            $r->rata_poin         = $r->jumlah_siswa > 0 ? round($r->total_poin / $r->jumlah_siswa, 1) : 0;
        }
        return $rows;
    }

    // Ringkasan untuk kartu di halaman daftar/rekap
    public function total($id_tp, $id_smt, $id_kelas = 0, $dari = '', $sampai = '') {
        $sql = "SELECT COUNT(*) AS jml,
                       COALESCE(SUM(p.poin), 0) AS poin,
                       COUNT(DISTINCT p.id_siswa) AS siswa
                FROM cbt_pelanggaran_siswa p
                WHERE p.id_tp = ? AND p.id_smt = ?";
        $params = array((int) $id_tp, (int) $id_smt);

        if ($id_kelas) {
            $sql .= " AND p.id_kelas = ?";
            $params[] = (int) $id_kelas;
        }
        if ($dari !== '') {
            $sql .= " AND p.tanggal >= ?";
            $params[] = $dari;
        }
        if ($sampai !== '') {
            $sql .= " AND p.tanggal <= ?";
            $params[] = $sampai;
        }

        $r = $this->db->query($sql, $params)->row();
        if ($r) {
            $r->jml   = (int) $r->jml;
            $r->poin  = (int) $r->poin;
            $r->siswa = (int) $r->siswa;
        }
        return $r;
    }
}
