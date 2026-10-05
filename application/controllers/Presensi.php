<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Presensi extends CI_Controller {

    const MAKS_JAM = 15; // jumlah jam pelajaran per hari yang tersedia di pilihan

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'text'));
        $this->load->library(array('ion_auth', 'session'));

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin()) {
            show_error('Akses Ditolak', 403);
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Presensi_model');
    }

    // =====================================================================
    // HELPER PRIVATE
    // =====================================================================
    private function _admin_data($title) {
        $user = $this->ion_auth->user()->row();

        $data['title']      = $title;
        $data['judul']      = $title;
        $data['user']       = $user;
        $data['setting']    = $this->dashboard->getSetting();
        $data['profile']    = $this->dashboard->getProfileAdmin($user->id);
        $data['tp']         = $this->dashboard->getTahun();
        $data['tp_active']  = $this->dashboard->getTahunActive();
        $data['smt']        = $this->dashboard->getSemester();
        $data['smt_active'] = $this->dashboard->getSemesterActive();

        return $data;
    }

    private function _render_admin($view, $data) {
        // Sidebar & navbar sudah di-include di dalam _header.php
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    private function _harus_post($kembali = 'presensi') {
        if ($this->input->method() !== 'post') {
            redirect($kembali);
            exit;
        }
    }

    private function _tanggal_valid($t) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    // Rentang tanggal awal-akhir dari bulan & tahun
    private function _rentang($bulan, $tahun) {
        $bulan = max(1, min(12, (int) $bulan));
        $tahun = (int) $tahun;
        $dari   = sprintf('%04d-%02d-01', $tahun, $bulan);
        $sampai = date('Y-m-t', strtotime($dari));
        return array($dari, $sampai, $bulan, $tahun);
    }

    // =====================================================================
    // ISI PRESENSI
    // =====================================================================
    public function index() {
        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $kelas_id = (int) $this->input->get('kelas');
        $tanggal  = (string) $this->input->get('tanggal');
        $jam      = (int) $this->input->get('jam');
        if (!$this->_tanggal_valid($tanggal)) $tanggal = date('Y-m-d');
        if ($jam < 1 || $jam > self::MAKS_JAM) $jam = 1;

        $data = $this->_admin_data('Presensi Kelas');
        $data['daftar_kelas']  = $this->Presensi_model->daftar_kelas($tp->id_tp, $smt->id_smt);
        $data['status_ref']    = $this->Presensi_model->status;
        $data['maks_jam']      = self::MAKS_JAM;
        $data['mapel_tersedia'] = $this->Presensi_model->mapel_tersedia();
        $data['daftar_mapel']  = $this->Presensi_model->daftar_mapel();
        $data['kelas_id']      = $kelas_id;
        $data['tanggal']       = $tanggal;
        $data['jam']           = $jam;
        $data['roster']        = array();
        $data['sesi']          = null;
        $data['tersimpan']     = array();
        $data['sesi_hari']     = array();
        $data['nama_kelas']    = '';

        if ($kelas_id && $this->Presensi_model->kelas_valid($kelas_id, $tp->id_tp, $smt->id_smt)) {
            $data['roster']     = $this->Presensi_model->roster($kelas_id, $tp->id_tp, $smt->id_smt);
            $data['sesi']       = $this->Presensi_model->get_sesi($kelas_id, $tanggal, $jam);
            $data['tersimpan']  = $data['sesi'] ? $this->Presensi_model->status_sesi($data['sesi']->id) : array();
            $data['sesi_hari']  = $this->Presensi_model->sesi_hari($kelas_id, $tanggal);
            $data['nama_kelas'] = $this->Presensi_model->nama_kelas($kelas_id);
        } elseif ($kelas_id) {
            $this->session->set_flashdata('error', 'Kelas tidak ditemukan pada tahun pelajaran/semester aktif.');
            $data['kelas_id'] = 0;
        }

        $this->_render_admin('presensi/index', $data);
    }

    public function simpan() {
        $this->_harus_post();

        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $kelas_id = (int) $this->input->post('kelas');
        $tanggal  = (string) $this->input->post('tanggal');
        $jam      = (int) $this->input->post('jam');
        $mapel_id = (int) $this->input->post('mapel_id');
        $mapel_tx = trim((string) $this->input->post('mapel_nama'));

        $balik = 'presensi?' . http_build_query(array('kelas' => $kelas_id, 'tanggal' => $tanggal, 'jam' => $jam));

        if (!$this->_tanggal_valid($tanggal)) {
            $this->session->set_flashdata('error', 'Tanggal tidak valid.');
            redirect('presensi');
            return;
        }
        if ($tanggal > date('Y-m-d')) {
            $this->session->set_flashdata('error', 'Tanggal presensi tidak boleh di masa depan.');
            redirect($balik);
            return;
        }
        if ($jam < 1 || $jam > self::MAKS_JAM) {
            $this->session->set_flashdata('error', 'Jam pelajaran tidak valid.');
            redirect($balik);
            return;
        }
        if (!$this->Presensi_model->kelas_valid($kelas_id, $tp->id_tp, $smt->id_smt)) {
            $this->session->set_flashdata('error', 'Kelas tidak valid.');
            redirect('presensi');
            return;
        }

        // Nama mata pelajaran: dari pilihan (jika ada), kalau tidak dari ketikan
        $mapel_nama = '';
        if ($mapel_id) {
            $nm = $this->Presensi_model->nama_mapel($mapel_id);
            if ($nm !== null) {
                $mapel_nama = $nm;
            } else {
                $mapel_id = 0;
            }
        }
        if ($mapel_nama === '') {
            $mapel_nama = mb_substr($mapel_tx, 0, 100);
        }

        // Hanya siswa yang memang ada di kelas itu yang diterima
        $roster = $this->Presensi_model->roster($kelas_id, $tp->id_tp, $smt->id_smt);
        $status = (array) $this->input->post('status');
        $ket    = (array) $this->input->post('ket');
        $valid  = array_keys($this->Presensi_model->status);

        $data = array();
        foreach ($roster as $s) {
            $st = isset($status[$s->id_siswa]) ? (string) $status[$s->id_siswa] : '';
            if (!in_array($st, $valid, true)) {
                $st = 'H'; // tidak dipilih = dianggap hadir
            }
            $k = isset($ket[$s->id_siswa]) ? trim((string) $ket[$s->id_siswa]) : '';
            $data[$s->id_siswa] = array('status' => $st, 'ket' => mb_substr($k, 0, 255));
        }
        if (empty($data)) {
            $this->session->set_flashdata('error', 'Kelas ini belum memiliki siswa.');
            redirect($balik);
            return;
        }

        $user = $this->ion_auth->user()->row();
        $id = $this->Presensi_model->simpan(
            $tp->id_tp, $smt->id_smt, $kelas_id, $tanggal, $jam,
            $mapel_id, $mapel_nama, $user ? $user->username : null, $data
        );

        if ($id) {
            $this->session->set_flashdata('success', 'Presensi tersimpan (' . count($data) . ' siswa).');
        } else {
            $this->session->set_flashdata('error', 'Presensi gagal disimpan. Coba lagi.');
        }
        redirect($balik);
    }

    public function hapus_sesi($id) {
        $this->_harus_post();

        $sesi = $this->Presensi_model->get_sesi_by_id($id);
        if ($sesi) {
            $this->Presensi_model->hapus_sesi($sesi->id);
            $this->session->set_flashdata('success', 'Presensi sesi tersebut dihapus.');
            redirect('presensi?' . http_build_query(array(
                'kelas' => $sesi->id_kelas, 'tanggal' => $sesi->tanggal, 'jam' => $sesi->jam_ke
            )));
            return;
        }
        $this->session->set_flashdata('error', 'Data tidak ditemukan.');
        redirect('presensi');
    }

    // =====================================================================
    // REKAP
    // =====================================================================
    public function rekap() {
        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $kelas_id = (int) $this->input->get('kelas');
        $bulan    = $this->input->get('bulan') ?: date('n');
        $tahun    = $this->input->get('tahun') ?: date('Y');
        list($dari, $sampai, $bulan, $tahun) = $this->_rentang($bulan, $tahun);

        $data = $this->_admin_data('Rekap Presensi');
        $data['daftar_kelas'] = $this->Presensi_model->daftar_kelas($tp->id_tp, $smt->id_smt);
        $data['daftar_bulan'] = $this->Presensi_model->daftar_bulan;
        $data['kelas_id'] = $kelas_id;
        $data['bulan']    = $bulan;
        $data['tahun']    = $tahun;
        $data['rekap']    = array();
        $data['jml_sesi'] = 0;
        $data['nama_kelas'] = '';

        if ($kelas_id && $this->Presensi_model->kelas_valid($kelas_id, $tp->id_tp, $smt->id_smt)) {
            $data['rekap']      = $this->Presensi_model->rekap($kelas_id, $tp->id_tp, $smt->id_smt, $dari, $sampai);
            $data['jml_sesi']   = $this->Presensi_model->jumlah_sesi($kelas_id, $dari, $sampai);
            $data['nama_kelas'] = $this->Presensi_model->nama_kelas($kelas_id);
        }

        $this->_render_admin('presensi/rekap', $data);
    }

    public function detail($siswa_id = 0) {
        $siswa = $this->Presensi_model->siswa_by_id($siswa_id);
        if (!$siswa) {
            show_error('Data siswa tidak ditemukan.', 404);
        }

        $bulan = $this->input->get('bulan') ?: date('n');
        $tahun = $this->input->get('tahun') ?: date('Y');
        list($dari, $sampai, $bulan, $tahun) = $this->_rentang($bulan, $tahun);

        $riwayat = $this->Presensi_model->riwayat_siswa($siswa->id_siswa, $dari, $sampai);

        $data = $this->_admin_data('Detail Presensi');
        $data['siswa']        = $siswa;
        $data['riwayat']      = $riwayat;
        $data['hitung']       = $this->Presensi_model->hitung($riwayat);
        $data['status_ref']   = $this->Presensi_model->status;
        $data['daftar_bulan'] = $this->Presensi_model->daftar_bulan;
        $data['bulan']        = $bulan;
        $data['tahun']        = $tahun;
        $data['kelas_id']     = (int) $this->input->get('kelas');

        $this->_render_admin('presensi/detail', $data);
    }

    // =====================================================================
    // EKSPOR EXCEL (CSV)
    // =====================================================================
    public function export_rekap() {
        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $kelas_id = (int) $this->input->get('kelas');
        list($dari, $sampai, $bulan, $tahun) = $this->_rentang($this->input->get('bulan') ?: date('n'), $this->input->get('tahun') ?: date('Y'));

        if (!$this->Presensi_model->kelas_valid($kelas_id, $tp->id_tp, $smt->id_smt)) {
            show_error('Kelas tidak valid.', 400);
        }

        $rows = $this->Presensi_model->rekap($kelas_id, $tp->id_tp, $smt->id_smt, $dari, $sampai);
        $baris = array();
        $no = 1;
        foreach ($rows as $r) {
            $baris[] = array(
                $no++, $r->nis, $r->nama, $r->h, $r->i, $r->s_, $r->a, $r->total,
                $r->persen === null ? '' : $r->persen . '%'
            );
        }

        $nama_file = 'presensi_' . preg_replace('/[^A-Za-z0-9_-]/', '', $this->Presensi_model->nama_kelas($kelas_id))
                   . '_' . $tahun . '-' . sprintf('%02d', $bulan) . '.csv';

        $this->_kirim_csv($nama_file,
            array('No', 'NIS', 'Nama Siswa', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Total Sesi', '% Hadir'),
            $baris);
    }

    private function _kirim_csv($nama_file, $header, $baris) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo "sep=;\r\n";

        $out = fopen('php://output', 'w');
        fputcsv($out, $header, ';');
        foreach ($baris as $b) {
            fputcsv($out, array_map(array($this, '_csv_aman'), $b), ';');
        }
        fclose($out);
        exit;
    }

    // Mencegah "formula injection" pada sel yang diawali = + - @
    private function _csv_aman($nilai) {
        if (is_string($nilai) && $nilai !== '' && strpos('=+-@', $nilai[0]) !== false) {
            return "'" . $nilai;
        }
        return $nilai;
    }
}
