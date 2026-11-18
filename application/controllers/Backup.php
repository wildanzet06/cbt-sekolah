<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman Backup Otomatis (admin).
 *
 * - Melihat daftar backup (otomatis & manual)
 * - Membuat backup sekarang
 * - Memulihkan (restore) database dari file backup
 * - Menghapus file backup
 */
class Backup extends CI_Controller {

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
        $this->load->model('Backup_model', 'backup');
    }

    private function _harus_post() {
        if ($this->input->method() !== 'post') {
            redirect('backup');
            exit;
        }
    }

    private function _render($view, $data) {
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
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
        $data = $this->_data('Backup Database');
        $data['daftar']       = $this->backup->daftar();
        $data['terakhir_auto'] = $this->backup->terakhir(true);
        $data['simpan_hari']  = Backup_model::SIMPAN_HARI;
        $data['direktori']    = $this->backup->direktori();
        $data['bisa_pulih']   = ($this->db->conn_id instanceof mysqli);

        $this->_render('backup/index', $data);
    }

    public function buat() {
        $this->_harus_post();

        $hasil = $this->backup->buat('manual');
        if ($hasil['ok']) {
            $this->session->set_flashdata('success', 'Backup berhasil dibuat: ' . $hasil['file']);
        } else {
            $this->session->set_flashdata('error', isset($hasil['pesan']) ? $hasil['pesan'] : 'Backup gagal.');
        }
        redirect('backup');
    }

    public function pulihkan() {
        $this->_harus_post();

        $nama  = (string) $this->input->post('nama');
        $hasil = $this->backup->pulihkan($nama);

        $this->session->set_flashdata($hasil['ok'] ? 'success' : 'error', $hasil['pesan']);
        redirect('backup');
    }

    public function hapus() {
        $this->_harus_post();

        $nama = (string) $this->input->post('nama');
        if ($this->backup->hapus($nama)) {
            $this->session->set_flashdata('success', 'File backup dihapus.');
        } else {
            $this->session->set_flashdata('error', 'File backup tidak bisa dihapus.');
        }
        redirect('backup');
    }
}
