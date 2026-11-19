<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bank Soal & Media Ujian â€” editor soal ringkas gaya KARTINI CBT.
 * Fokus Pilihan Ganda (jenis 1) dengan lampiran gambar/audio.
 * Soal lama tetap bisa dibuka/diedit karena memakai tabel cbt_soal yang sama.
 */
class Banksoal extends CI_Controller {

    private $tp;
    private $smt;
    private $guru;
    private $is_admin;

    public function __construct() {
        parent::__construct();

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group(array(2, 'guru'))) {
            show_error('Hanya guru dan administrator yang dapat membuka halaman ini.', 403, 'Akses Ditolak');
        }

        $this->is_admin = $this->ion_auth->is_admin();
        $this->load->model('Dashboard_model', 'dash');
        $this->load->model('Ujiansekolah_model', 'us');

        $this->tp  = $this->dash->getTahunActive();
        $this->smt = $this->dash->getSemesterActive();

        if (!$this->is_admin) {
            $user = $this->ion_auth->user()->row();
            $this->guru = $this->dash->getDataGuruByUserId($user->id, $this->tp->id_tp, $this->smt->id_smt);
        }
    }

    public function index() {
        $bank_id = (int) $this->input->get('bank');
        $edit_id = (int) $this->input->get('edit');

        $banks = $this->us->bankPilihan(
            $this->tp->id_tp,
            $this->smt->id_smt,
            null,
            $this->is_admin ? null : ($this->guru ? $this->guru->id_guru : -1)
        );

        if ($bank_id <= 0 && count($banks) > 0) {
            $bank_id = (int) $banks[0]->id_bank;
        }
        if (!$this->bank_boleh($bank_id)) {
            $bank_id = 0;
        }

        $bank = $bank_id > 0 ? $this->db->get_where('cbt_bank_soal', array('id_bank' => $bank_id))->row() : null;
        $soal_list = array();
        $soal_edit = null;

        if ($bank) {
            $soal_list = $this->db->select('id_soal, nomor_soal, jenis, soal, jawaban, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e')
                ->where('bank_id', $bank_id)
                ->order_by('nomor_soal', 'ASC')
                ->get('cbt_soal')->result();

            if ($edit_id > 0) {
                $soal_edit = $this->db->get_where('cbt_soal', array('id_soal' => $edit_id, 'bank_id' => $bank_id))->row();
            }
        }

        $data = array(
            'judul'      => 'Bank Soal & Media Ujian',
            'subjudul'   => 'Kelola soal pilihan ganda, gambar, dan audio dalam satu halaman',
            'banks'      => $banks,
            'bank'       => $bank,
            'bank_id'    => $bank_id,
            'soal_list'  => $soal_list,
            'soal_edit'  => $soal_edit,
            'setting'    => $this->dash->getSetting(),
            'user'       => $this->ion_auth->user()->row(),
            'running_text' => $this->dash->getRunningText(),
            'tp_active'  => $this->tp,
            'smt_active' => $this->smt,
            'is_admin'   => $this->is_admin,
        );

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('banksoal/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function simpan() {
        if ($this->input->method() !== 'post') {
            return $this->json(array('status' => false, 'msg' => 'Metode tidak valid.'), 405);
        }

        $bank_id  = (int) $this->input->post('bank_id');
        $id_soal  = (int) $this->input->post('id_soal');
        $soal     = (string) $this->input->post('soal', false);
        $jawaban  = strtolower(trim((string) $this->input->post('jawaban', true)));

        if (!$this->bank_boleh($bank_id)) {
            return $this->json(array('status' => false, 'msg' => 'Anda tidak berhak mengubah bank soal ini.'), 403);
        }
        if (trim(strip_tags($soal)) === '' && trim($soal) === '') {
            return $this->json(array('status' => false, 'msg' => 'Teks pertanyaan belum diisi.'));
        }

        $opsi = array();
        foreach (array('a', 'b', 'c', 'd', 'e') as $abjad) {
            $opsi[$abjad] = (string) $this->input->post('opsi_' . $abjad, false);
        }
        if ($jawaban === '' || !isset($opsi[$jawaban]) || trim(strip_tags($opsi[$jawaban])) === '') {
            return $this->json(array('status' => false, 'msg' => 'Tandai salah satu opsi sebagai jawaban benar.'));
        }

        $bank = $this->db->get_where('cbt_bank_soal', array('id_bank' => $bank_id))->row();
        if (!$bank) {
            return $this->json(array('status' => false, 'msg' => 'Bank soal tidak ditemukan.'));
        }

        $data = array(
            'bank_id'    => $bank_id,
            'mapel_id'   => (int) $bank->bank_mapel_id,
            'jenis'      => 1,
            'soal'       => $soal,
            'opsi_a'     => $opsi['a'],
            'opsi_b'     => $opsi['b'],
            'opsi_c'     => $opsi['c'],
            'opsi_d'     => $opsi['d'],
            'opsi_e'     => $opsi['e'],
            'jawaban'    => $jawaban,
            'updated_on' => time(),
        );

        if ($id_soal > 0) {
            $cek = $this->db->get_where('cbt_soal', array('id_soal' => $id_soal, 'bank_id' => $bank_id))->row();
            if (!$cek) {
                return $this->json(array('status' => false, 'msg' => 'Soal tidak ditemukan.'));
            }
            $this->db->where('id_soal', $id_soal)->update('cbt_soal', $data);
            $id_soal = (int) $id_soal;
        } else {
            $max = $this->db->select_max('nomor_soal', 'n')->get_where('cbt_soal', array('bank_id' => $bank_id))->row();
            $data['nomor_soal'] = ((int) ($max ? $max->n : 0)) + 1;
            $data['created_on'] = time();
            $data['tampilkan']  = 1;
            $this->db->insert('cbt_soal', $data);
            $id_soal = (int) $this->db->insert_id();
        }

        return $this->json(array('status' => true, 'msg' => 'Soal tersimpan.', 'id_soal' => $id_soal));
    }

    public function hapus() {
        if ($this->input->method() !== 'post') {
            return $this->json(array('status' => false, 'msg' => 'Metode tidak valid.'), 405);
        }
        $id_soal = (int) $this->input->post('id_soal');
        $row = $this->db->select('s.id_soal, s.bank_id')->from('cbt_soal s')
            ->where('s.id_soal', $id_soal)->get()->row();

        if (!$row || !$this->bank_boleh((int) $row->bank_id)) {
            return $this->json(array('status' => false, 'msg' => 'Soal tidak ditemukan.'), 404);
        }

        $this->db->where('id_soal', $id_soal)->delete('cbt_soal');
        return $this->json(array('status' => true, 'msg' => 'Soal dihapus.'));
    }

    /** Upload gambar/audio soal, kembalikan URL untuk disisipkan ke teks soal. */
    public function upload() {
        if ($this->input->method() !== 'post') {
            return $this->json(array('status' => false, 'msg' => 'Metode tidak valid.'), 405);
        }
        if (!$this->is_admin && !$this->guru) {
            return $this->json(array('status' => false, 'msg' => 'Tidak berhak.'), 403);
        }

        $path = FCPATH . 'uploads/bank_soal/';
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        $config = array(
            'upload_path'   => $path,
            'allowed_types' => 'jpg|jpeg|png|gif|webp|mp3|ogg|wav|m4a|mp4|webm',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        );
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            return $this->json(array('status' => false, 'msg' => strip_tags($this->upload->display_errors())));
        }

        $up = $this->upload->data();
        return $this->json(array(
            'status' => true,
            'url'    => base_url('uploads/bank_soal/' . $up['file_name']),
            'tipe'   => strpos((string) $up['file_type'], 'image') === 0 ? 'image' : 'audio',
        ));
    }

    private function bank_boleh($bank_id) {
        $bank_id = (int) $bank_id;
        if ($bank_id <= 0) {
            return false;
        }
        $bank = $this->db->select('bank_guru_id')->where('id_bank', $bank_id)->get('cbt_bank_soal')->row();
        if (!$bank) {
            return false;
        }
        if ($this->is_admin) {
            return true;
        }
        return $this->guru && (int) $bank->bank_guru_id === (int) $this->guru->id_guru;
    }

    private function json($data, $code = 200) {
        $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
