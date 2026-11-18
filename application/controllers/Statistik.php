<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Statistik keuangan & kehadiran (admin) — grafik dengan Chart.js.
 */
class Statistik extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'text'));
        $this->load->library(array('ion_auth', 'session'));

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin()) {
            show_error('Akses Ditolak', 403, 'Akses Ditolak');
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Pembayaran_model');
    }

    private function _data($title) {
        $user = $this->ion_auth->user()->row();
        return array(
            'title'      => $title,
            'judul'      => $title,
            'user'       => $user,
            'setting'    => $this->dashboard->getSetting(),
            'profile'    => $this->dashboard->getProfileAdmin($user->id),
            'tp'         => $this->dashboard->getTahun(),
            'tp_active'  => $this->dashboard->getTahunActive(),
            'smt'        => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive()
        );
    }

    public function index() {
        $tahun = (int) $this->input->get('tahun');
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $data = $this->_data('Statistik Keuangan & Kehadiran');
        $data['tahun'] = $tahun;

        // 1. Penerimaan SPP per bulan
        $rows = $this->db->query(
            "SELECT MONTH(tanggal_bayar) AS m, COALESCE(SUM(jumlah_bayar),0) AS total
               FROM cbt_pembayaran_spp
              WHERE status_pembayaran = 'success' AND YEAR(tanggal_bayar) = ?
              GROUP BY MONTH(tanggal_bayar)",
            array($tahun)
        )->result();
        $spp = array_fill(1, 12, 0);
        foreach ($rows as $r) {
            $spp[(int) $r->m] = (float) $r->total;
        }
        $data['spp_bulanan'] = array_values($spp);
        $data['spp_total']   = array_sum($spp);

        // 2. Tunggakan per kelas (sisa tagihan)
        $data['kelas_label'] = array();
        $data['kelas_sisa']  = array();
        $rows = $this->db->query(
            "SELECT mk.nama_kelas, COALESCE(SUM(GREATEST(t.nominal + t.diskon - COALESCE(paid.total,0), 0)),0) AS sisa
               FROM cbt_tagihan t
               JOIN kelas_siswa ks ON ks.id_siswa = t.siswa_id AND ks.id_tp = ? AND ks.id_smt = ?
               JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
               LEFT JOIN (SELECT tagihan_id, SUM(jumlah_bayar) AS total
                            FROM cbt_pembayaran_spp WHERE status_pembayaran = 'success'
                           GROUP BY tagihan_id) paid ON paid.tagihan_id = t.id
              GROUP BY mk.id_kelas, mk.nama_kelas
              ORDER BY sisa DESC",
            array($tp->id_tp, $smt->id_smt)
        )->result();
        foreach ($rows as $r) {
            $data['kelas_label'][] = $r->nama_kelas;
            $data['kelas_sisa'][]  = (float) $r->sisa;
        }

        // 3. Kehadiran per bulan (H/I/S/A)
        $rows = $this->db->query(
            "SELECT MONTH(s.tanggal) AS m, p.status, COUNT(*) AS jml
               FROM cbt_presensi p
               JOIN cbt_presensi_sesi s ON s.id = p.sesi_id
              WHERE YEAR(s.tanggal) = ?
              GROUP BY MONTH(s.tanggal), p.status",
            array($tahun)
        )->result();
        $hadir = array_fill(1, 12, 0);
        $izin  = array_fill(1, 12, 0);
        $sakit = array_fill(1, 12, 0);
        $alpa  = array_fill(1, 12, 0);
        foreach ($rows as $r) {
            $m = (int) $r->m;
            switch ($r->status) {
                case 'H': $hadir[$m] = (int) $r->jml; break;
                case 'I': $izin[$m]  = (int) $r->jml; break;
                case 'S': $sakit[$m] = (int) $r->jml; break;
                case 'A': $alpa[$m]  = (int) $r->jml; break;
            }
        }
        $data['hadir'] = array_values($hadir);
        $data['izin']  = array_values($izin);
        $data['sakit'] = array_values($sakit);
        $data['alpa']  = array_values($alpa);

        // 4. Pelanggaran per jenis (8 teratas)
        $rows = $this->db->query(
            "SELECT jenis_pelanggaran, COUNT(*) AS jml, COALESCE(SUM(poin),0) AS poin
               FROM cbt_pelanggaran_siswa
              WHERE id_tp = ? AND id_smt = ?
              GROUP BY jenis_pelanggaran
              ORDER BY jml DESC, poin DESC
              LIMIT 8",
            array($tp->id_tp, $smt->id_smt)
        )->result();
        $data['pel_label'] = array();
        $data['pel_jml']   = array();
        foreach ($rows as $r) {
            $data['pel_label'][] = $r->jenis_pelanggaran;
            $data['pel_jml'][]   = (int) $r->jml;
        }

        // Ringkasan tambahan
        $data['total_pelanggaran'] = (int) $this->db->query(
            "SELECT COUNT(*) AS c FROM cbt_pelanggaran_siswa WHERE id_tp = ? AND id_smt = ?",
            array($tp->id_tp, $smt->id_smt)
        )->row()->c;

        $total_hadir = array_sum($data['hadir']);
        $total_semua = $total_hadir + array_sum($data['izin']) + array_sum($data['sakit']) + array_sum($data['alpa']);
        $data['persen_hadir'] = $total_semua > 0 ? round($total_hadir / $total_semua * 100, 1) : 0;

        $data['tunggakan_total'] = array_sum($data['kelas_sisa']);

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('statistik/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }
}
