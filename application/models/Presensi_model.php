<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Presensi_model extends CI_Model {

    // Kode status => nama
    public $status = array('H' => 'Hadir', 'I' => 'Izin', 'S' => 'Sakit', 'A' => 'Alpa');

    public $daftar_bulan = array(
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );

    // =====================================================================
    // KELAS & SISWA (membaca data yang sudah ada, tidak mengubahnya)
    // =====================================================================

    // Kelas yang punya siswa pada tahun pelajaran + semester aktif
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

    public function siswa_by_id($id) {
        return $this->db->select('id_siswa, nis, nama, username')
                        ->get_where('master_siswa', array('id_siswa' => (int) $id))->row();
    }

    public function siswa_by_username($username) {
        return $this->db->select('id_siswa, nis, nama, username')
                        ->get_where('master_siswa', array('username' => (string) $username))->row();
    }

    // =====================================================================
    // MATA PELAJARAN (opsional: dipakai jika tabel master_mapel dikenali)
    // =====================================================================

    public function mapel_tersedia() {
        return $this->db->table_exists('master_mapel')
            && $this->db->field_exists('id_mapel', 'master_mapel')
            && $this->db->field_exists('nama_mapel', 'master_mapel');
    }

    public function daftar_mapel() {
        if (!$this->mapel_tersedia()) return array();
        return $this->db->select('id_mapel, nama_mapel')->order_by('nama_mapel', 'ASC')->get('master_mapel')->result();
    }

    public function nama_mapel($id) {
        if (!$this->mapel_tersedia()) return null;
        $r = $this->db->select('nama_mapel')->get_where('master_mapel', array('id_mapel' => (int) $id))->row();
        return $r ? $r->nama_mapel : null;
    }

    // =====================================================================
    // SESI & PRESENSI
    // =====================================================================

    public function get_sesi($id_kelas, $tanggal, $jam_ke) {
        return $this->db->get_where('cbt_presensi_sesi', array(
            'id_kelas' => (int) $id_kelas,
            'tanggal'  => $tanggal,
            'jam_ke'   => (int) $jam_ke
        ))->row();
    }

    public function get_sesi_by_id($id) {
        return $this->db->get_where('cbt_presensi_sesi', array('id' => (int) $id))->row();
    }

    // Sesi lain pada kelas + tanggal yang sama (untuk pindah cepat antar jam)
    public function sesi_hari($id_kelas, $tanggal) {
        $this->db->where('id_kelas', (int) $id_kelas);
        $this->db->where('tanggal', $tanggal);
        $this->db->order_by('jam_ke', 'ASC');
        return $this->db->get('cbt_presensi_sesi')->result();
    }

    // Peta [siswa_id] => baris presensi untuk satu sesi
    public function status_sesi($sesi_id) {
        $peta = array();
        foreach ($this->db->get_where('cbt_presensi', array('sesi_id' => (int) $sesi_id))->result() as $r) {
            $peta[$r->siswa_id] = $r;
        }
        return $peta;
    }

    /**
     * Simpan presensi satu sesi (membuat sesi baru atau memperbarui yang ada).
     * $data: array(siswa_id => array('status' => 'H|I|S|A', 'ket' => '...'))
     * Mengembalikan id sesi.
     */
    public function simpan($id_tp, $id_smt, $id_kelas, $tanggal, $jam_ke, $mapel_id, $mapel_nama, $oleh, $data) {
        $this->db->trans_start();

        $sesi = $this->get_sesi($id_kelas, $tanggal, $jam_ke);
        if ($sesi) {
            $sesi_id = (int) $sesi->id;
            $this->db->where('id', $sesi_id);
            $this->db->update('cbt_presensi_sesi', array(
                'mapel_id'    => $mapel_id ? $mapel_id : null,
                'mapel_nama'  => ($mapel_nama !== '') ? $mapel_nama : null,
                'dibuat_oleh' => $oleh
            ));
        } else {
            $this->db->insert('cbt_presensi_sesi', array(
                'id_tp'       => (int) $id_tp,
                'id_smt'      => (int) $id_smt,
                'id_kelas'    => (int) $id_kelas,
                'tanggal'     => $tanggal,
                'jam_ke'      => (int) $jam_ke,
                'mapel_id'    => $mapel_id ? $mapel_id : null,
                'mapel_nama'  => ($mapel_nama !== '') ? $mapel_nama : null,
                'dibuat_oleh' => $oleh
            ));
            $sesi_id = (int) $this->db->insert_id();
        }

        $sql = "INSERT INTO cbt_presensi (sesi_id, siswa_id, status, keterangan)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)";
        foreach ($data as $siswa_id => $d) {
            $this->db->query($sql, array(
                $sesi_id,
                (int) $siswa_id,
                $d['status'],
                ($d['ket'] !== '') ? $d['ket'] : null
            ));
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $sesi_id : 0;
    }

    public function hapus_sesi($id) {
        $id = (int) $id;
        $this->db->trans_start();
        $this->db->where('sesi_id', $id);
        $this->db->delete('cbt_presensi');
        $this->db->where('id', $id);
        $this->db->delete('cbt_presensi_sesi');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // =====================================================================
    // REKAP & RIWAYAT
    // =====================================================================

    // Rekap per siswa untuk satu kelas pada rentang tanggal
    public function rekap($id_kelas, $id_tp, $id_smt, $dari, $sampai) {
        $sql = "SELECT s.id_siswa, s.nis, s.nama,
                       COALESCE(SUM(p.status = 'H'), 0) AS h,
                       COALESCE(SUM(p.status = 'I'), 0) AS i,
                       COALESCE(SUM(p.status = 'S'), 0) AS s_,
                       COALESCE(SUM(p.status = 'A'), 0) AS a
                FROM kelas_siswa ks
                JOIN master_siswa s ON s.id_siswa = ks.id_siswa
                LEFT JOIN cbt_presensi_sesi ps
                       ON ps.id_kelas = ks.id_kelas AND ps.tanggal BETWEEN ? AND ?
                LEFT JOIN cbt_presensi p
                       ON p.sesi_id = ps.id AND p.siswa_id = s.id_siswa
                WHERE ks.id_tp = ? AND ks.id_smt = ? AND ks.id_kelas = ?
                GROUP BY s.id_siswa, s.nis, s.nama
                ORDER BY s.nama ASC";
        $rows = $this->db->query($sql, array($dari, $sampai, (int) $id_tp, (int) $id_smt, (int) $id_kelas))->result();

        foreach ($rows as $r) {
            $r->h = (int) $r->h; $r->i = (int) $r->i; $r->s_ = (int) $r->s_; $r->a = (int) $r->a;
            $r->total = $r->h + $r->i + $r->s_ + $r->a;
            $r->persen = $r->total > 0 ? round($r->h / $r->total * 100) : null;
        }
        return $rows;
    }

    // Jumlah sesi pelajaran yang sudah dicatat untuk satu kelas pada rentang tanggal
    public function jumlah_sesi($id_kelas, $dari, $sampai) {
        $this->db->where('id_kelas', (int) $id_kelas);
        $this->db->where('tanggal >=', $dari);
        $this->db->where('tanggal <=', $sampai);
        return $this->db->count_all_results('cbt_presensi_sesi');
    }

    // Riwayat presensi satu siswa
    public function riwayat_siswa($siswa_id, $dari, $sampai) {
        $sql = "SELECT ps.tanggal, ps.jam_ke, ps.mapel_nama, p.status, p.keterangan
                FROM cbt_presensi p
                JOIN cbt_presensi_sesi ps ON ps.id = p.sesi_id
                WHERE p.siswa_id = ? AND ps.tanggal BETWEEN ? AND ?
                ORDER BY ps.tanggal DESC, ps.jam_ke ASC";
        return $this->db->query($sql, array((int) $siswa_id, $dari, $sampai))->result();
    }

    // Hitungan H/I/S/A dari hasil riwayat_siswa()
    public function hitung($riwayat) {
        $n = array('H' => 0, 'I' => 0, 'S' => 0, 'A' => 0);
        foreach ($riwayat as $r) {
            if (isset($n[$r->status])) $n[$r->status]++;
        }
        $total = array_sum($n);
        $n['total']  = $total;
        $n['persen'] = $total > 0 ? round($n['H'] / $total * 100) : null;
        return $n;
    }
}
