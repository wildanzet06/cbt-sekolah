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

    /** Terapkan satu bacaan ke rentang nomor soal pada satu bank soal. */
    public function terapkan() {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
        }

        $id_bank  = (int) $this->input->post('id_bank', true);
        $id_bacaan = (int) $this->input->post('id_bacaan', true);
        $jenis    = (int) $this->input->post('jenis', true);
        $dari     = (int) $this->input->post('dari', true);
        $sampai   = (int) $this->input->post('sampai', true);

        if (!$this->bank_boleh($id_bank)) {
            $this->output_json(array('status' => false, 'msg' => 'Anda tidak berhak mengubah bank soal ini.'));
            return;
        }
        if ($dari < 1 || $sampai < $dari || $sampai > 500) {
            $this->output_json(array('status' => false, 'msg' => 'Rentang nomor tidak valid.'));
            return;
        }
        if (!$this->bacaan_model->get($id_bacaan)) {
            $this->output_json(array('status' => false, 'msg' => 'Bacaan tidak ditemukan.'));
            return;
        }

        $this->db->where('bank_id', $id_bank)
            ->where('nomor_soal >=', $dari)
            ->where('nomor_soal <=', $sampai);
        if ($jenis > 0) {
            $this->db->where('jenis', $jenis);
        }
        $this->db->update('cbt_soal', array('id_bacaan' => $id_bacaan));
        $jumlah = (int) $this->db->affected_rows();

        $this->output_json(array(
            'status' => true,
            'msg'    => 'Bacaan diterapkan ke ' . $jumlah . ' soal (nomor ' . $dari . '–' . $sampai . ').',
            'jumlah' => $jumlah
        ));
    }

    /** Lepas bacaan dari rentang nomor soal. */
    public function lepas() {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
        }

        $id_bank = (int) $this->input->post('id_bank', true);
        $jenis   = (int) $this->input->post('jenis', true);
        $dari    = (int) $this->input->post('dari', true);
        $sampai  = (int) $this->input->post('sampai', true);

        if (!$this->bank_boleh($id_bank)) {
            $this->output_json(array('status' => false, 'msg' => 'Anda tidak berhak mengubah bank soal ini.'));
            return;
        }
        if ($dari < 1 || $sampai < $dari || $sampai > 500) {
            $this->output_json(array('status' => false, 'msg' => 'Rentang nomor tidak valid.'));
            return;
        }

        $this->db->where('bank_id', $id_bank)
            ->where('nomor_soal >=', $dari)
            ->where('nomor_soal <=', $sampai);
        if ($jenis > 0) {
            $this->db->where('jenis', $jenis);
        }
        $this->db->update('cbt_soal', array('id_bacaan' => null));

        $this->output_json(array('status' => true, 'msg' => 'Bacaan dilepas dari nomor ' . $dari . '–' . $sampai . '.'));
    }

    /** Guru hanya boleh mengubah bank soalnya sendiri; admin bebas. */
    private function bank_boleh($id_bank) {
        if ($this->ion_auth->is_admin()) {
            return true;
        }
        $user = $this->ion_auth->user()->row();
        if (!$user) {
            return false;
        }
        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        if (!$guru) {
            return false;
        }
        $bank = $this->db->select('bank_guru_id')->where('id_bank', (int) $id_bank)->get('cbt_bank_soal')->row();
        return $bank && (int) $bank->bank_guru_id === (int) $guru->id_guru;
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
