<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengelola bacaan/stimulus untuk soal (reading passage).
 * Bisa diakses admin dan guru.
 */
class Bacaan extends CI_Controller {

    public function __construct() {
        parent::__construct();

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group(array(2, 'guru'))) {
            show_error('Hanya guru dan administrator yang dapat membuka halaman ini.', 403, 'Akses Ditolak');
        }

        $this->load->model('Bacaan_model', 'bacaan_model');
        $this->load->model('Dashboard_model', 'dashboard');
    }

    public function index() {
        $user = $this->ion_auth->user()->row();

        $data = array(
            'user'     => $user,
            'judul'    => 'Bacaan Soal',
            'subjudul' => 'Bacaan / Stimulus',
            'setting'  => $this->dashboard->getSetting(),
            'bacaan'   => $this->bacaan_model->semua(),
        );

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('bacaan/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function get($id = 0) {
        $row = $this->bacaan_model->get($id);
        if (!$row) {
            $this->output_json(array('status' => false, 'msg' => 'Bacaan tidak ditemukan.'));
            return;
        }
        $this->output_json(array('status' => true, 'data' => $row));
    }

    public function simpan() {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
        }

        $judul = trim((string) $this->input->post('judul', true));
        $isi   = (string) $this->input->post('isi', false);
        $id    = (int) $this->input->post('id_bacaan', true);

        if ($judul === '') {
            $this->output_json(array('status' => false, 'msg' => 'Judul bacaan wajib diisi.'));
            return;
        }

        $id = $this->bacaan_model->simpan($judul, $isi, $id);
        $this->output_json(array('status' => true, 'msg' => 'Bacaan disimpan.', 'id_bacaan' => $id));
    }

    public function hapus($id = 0) {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
        }
        $this->bacaan_model->hapus($id);
        $this->output_json(array('status' => true, 'msg' => 'Bacaan dihapus.'));
    }

    private function output_json($data) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
