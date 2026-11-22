<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Buku Kas Sekolah (admin): pemasukan lain + pengeluaran, saldo, dan laporan.
 * Pemasukan SPP dihitung otomatis dari pembayaran yang sudah diverifikasi.
 */
class Kas extends CI_Controller {

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
        $this->load->model('Kas_model');
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

    private function _render($view, $data) {
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    private function _harus_post() {
        if ($this->input->method() !== 'post') {
            redirect('kas');
            exit;
        }
    }

    private function _tanggal_valid($t) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    public function index() {
        $bulan = (int) $this->input->get('bulan');
        $tahun = (int) $this->input->get('tahun');
        if ($bulan < 1 || $bulan > 12) $bulan = (int) date('m');
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        $dari   = sprintf('%04d-%02d-01', $tahun, $bulan);
        $sampai = date('Y-m-t', strtotime($dari));

        $data = $this->_data('Buku Kas Sekolah');
        $data['daftar']    = $this->Kas_model->daftar($dari, $sampai);
        $data['dari']      = $dari;
        $data['sampai']    = $sampai;
        $data['bulan']     = $bulan;
        $data['tahun']     = $tahun;
        $data['nama_bulan'] = $this->Kas_model->nama_bulan($bulan);
        $data['daftar_bulan'] = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');

        $spp_bulan = $this->Kas_model->spp_rentang($dari, $sampai);
        $masuk     = $this->Kas_model->total_jenis('masuk', $dari, $sampai);
        $keluar    = $this->Kas_model->total_jenis('keluar', $dari, $sampai);

        $data['spp_bulan']      = (float) $spp_bulan->total;
        $data['spp_jml']        = (int) $spp_bulan->jml;
        $data['masuk_bulan']    = $masuk;
        $data['keluar_bulan']   = $keluar;
        $data['pemasukan_bulan'] = $data['spp_bulan'] + $masuk;
        $data['saldo']          = $this->Kas_model->spp_total()
            + $this->Kas_model->total_jenis('masuk')
            - $this->Kas_model->total_jenis('keluar');

        $this->_render('kas/index', $data);
    }

    public function simpan() {
        $this->_harus_post();

        $tanggal = trim((string) $this->input->post('tanggal'));
        $jenis   = $this->input->post('jenis') === 'masuk' ? 'masuk' : 'keluar';
        $kategori = trim((string) $this->input->post('kategori', true));
        $keterangan = trim((string) $this->input->post('keterangan', true));
        $__nom = trim((string) $this->input->post('nominal'));
        if (strpos($__nom, ',') !== false) {
            $nominal = (float) str_replace(',', '.', str_replace('.', '', $__nom));
        } else {
            $nominal = (float) $__nom;
        }

        if (!$this->_tanggal_valid($tanggal)) {
            $this->session->set_flashdata('error', 'Tanggal tidak valid.');
            redirect('kas');
            return;
        }
        if ($tanggal > date('Y-m-d')) {
            $this->session->set_flashdata('error', 'Tanggal transaksi tidak boleh di masa depan.');
            redirect('kas');
            return;
        }
        if ($kategori === '') {
            $this->session->set_flashdata('error', 'Kategori wajib diisi.');
            redirect('kas');
            return;
        }
        if ($nominal <= 0) {
            $this->session->set_flashdata('error', 'Nominal harus lebih besar dari nol.');
            redirect('kas');
            return;
        }

        $user = $this->ion_auth->user()->row();
        $this->Kas_model->simpan(array(
            'tanggal'     => $tanggal,
            'jenis'       => $jenis,
            'kategori'    => mb_substr($kategori, 0, 60),
            'keterangan'  => mb_substr($keterangan, 0, 255),
            'nominal'     => $nominal,
            'dibuat_oleh' => $user ? $user->username : null
        ));

        $this->session->set_flashdata('success', 'Transaksi kas berhasil disimpan.');
        redirect('kas?bulan=' . (int) date('m', strtotime($tanggal)) . '&tahun=' . (int) date('Y', strtotime($tanggal)));
    }

    public function hapus() {
        $this->_harus_post();
        $id = (int) $this->input->post('id');
        if ($id) {
            $row = $this->db->select('tanggal, jenis, nominal, keterangan')->where('id', $id)->get('cbt_kas')->row();
            if ($row) {
                $this->load->model('Log_model', 'logging');
                $this->logging->saveLog(5, 'menghapus kas id ' . $id . ' nominal ' . $row->nominal . ' tanggal ' . $row->tanggal);
            }
        }
        if ($id && $this->Kas_model->hapus($id)) {
            $this->session->set_flashdata('success', 'Transaksi kas dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Transaksi tidak ditemukan.');
        }
        redirect('kas');
    }

    /** Ekspor CSV transaksi kas (bulan terpilih). */
    public function export() {
        $bulan = (int) $this->input->get('bulan');
        $tahun = (int) $this->input->get('tahun');
        if ($bulan < 1 || $bulan > 12) $bulan = (int) date('m');
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        $dari   = sprintf('%04d-%02d-01', $tahun, $bulan);
        $sampai = date('Y-m-t', strtotime($dari));
        $rows   = $this->Kas_model->daftar($dari, $sampai);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kas-' . $tahun . '-' . sprintf('%02d', $bulan) . '.csv"');
        $out = fopen('php://output', 'w');
        // Cegah CSV formula injection: sel yang diawali = + - @ diberi awalan '
        $aman = function ($v) {
            $v = (string) $v;
            return ($v !== '' && strpos('=+-@', $v[0]) !== false) ? "'" . $v : $v;
        };

        fputcsv($out, array('Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Nominal', 'Dicatat oleh'));
        foreach ($rows as $r) {
            fputcsv($out, array(
                date('d/m/Y', strtotime($r->tanggal)),
                $aman($r->jenis), $aman($r->kategori), $aman($r->keterangan),
                number_format((float) $r->nominal, 2, '.', ''),
                $aman($r->dibuat_oleh)
            ));
        }
        fclose($out);
    }
}
