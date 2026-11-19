<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Portal kode ujian (PRD KARTINI fase 3, tahap verifikasi).
 * Siswa memasukkan kode ujian 6 karakter, lalu memverifikasi NIS + nama.
 * Tahap ini belum melakukan login otomatis; setelah verifikasi siswa
 * diarahkan ke halaman login dengan username terisi.
 */
class Ujianportal extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Dashboard_model', 'dash');
        $this->load->helper('form');
    }

    public function index() {
        $data = array(
            'judul'  => 'Portal Kode Ujian',
            'error'  => $this->session->flashdata('portal_error'),
            'kode'   => '',
        );
        $this->load->view('ujianportal/index', $data);
    }

    public function cek() {
        if ($this->input->method() !== 'post') {
            redirect('ujianportal');
        }

        $kode = strtoupper(trim((string) $this->input->post('kode', true)));
        if (!preg_match('/^[A-Z0-9]{6}$/', $kode)) {
            $this->session->set_flashdata('portal_error', 'Kode ujian harus 6 karakter huruf/angka.');
            redirect('ujianportal');
        }

        $ujian = $this->cari_ujian($kode);
        if (!$ujian) {
            $this->session->set_flashdata('portal_error', 'Kode ujian tidak ditemukan, belum aktif, atau sudah lewat tanggalnya.');
            redirect('ujianportal');
        }

        $siswa = $this->siswa_ujian($ujian);
        $data = array(
            'judul'  => 'Verifikasi Peserta',
            'error'  => $this->session->flashdata('portal_error'),
            'kode'   => $kode,
            'ujian'  => $ujian,
            'siswa'  => $siswa,
            'jumlah' => count($siswa),
        );
        $this->load->view('ujianportal/verifikasi', $data);
    }

    public function verifikasi() {
        if ($this->input->method() !== 'post') {
            redirect('ujianportal');
        }

        $kode = strtoupper(trim((string) $this->input->post('kode', true)));
        $nis  = trim((string) $this->input->post('nis', true));
        $nama = trim((string) $this->input->post('nama', true));

        $ujian = $this->cari_ujian($kode);
        if (!$ujian) {
            $this->session->set_flashdata('portal_error', 'Kode ujian tidak valid.');
            redirect('ujianportal');
        }

        $cocok = null;
        foreach ($this->siswa_ujian($ujian) as $s) {
            if (strcasecmp(trim((string) $s->nis), $nis) === 0
                && strcasecmp(trim((string) $s->nama), $nama) === 0) {
                $cocok = $s;
                break;
            }
        }

        if (!$cocok) {
            $this->session->set_flashdata('portal_error', 'NIS dan nama tidak cocok dengan peserta ujian ini.');
            redirect('ujianportal');
        }

        $user = $this->db->where('username', $cocok->username)->get('users')->row();

        // Login otomatis hanya untuk akun siswa aktif yang terdaftar di grup siswa.
        if ($user && (int) $user->active === 1) {
            $grup = $this->db->where('user_id', $user->id)->where('group_id', 3)->count_all_results('users_groups');
            if ($grup > 0) {
                $this->session->set_userdata(array(
                    'identity'        => $user->username,
                    'username'        => $user->username,
                    'email'           => $user->email,
                    'user_id'         => $user->id,
                    'old_last_login'  => $user->last_login,
                    'last_check'      => time(),
                ));
                $this->session->set_flashdata('portal_sukses', 'Verifikasi berhasil. Silakan mulai ujian.');
                redirect('siswa/cbt');
            }
        }
        $data = array(
            'judul'  => 'Verifikasi Berhasil',
            'kode'   => $kode,
            'ujian'  => $ujian,
            'siswa'  => $cocok,
            'user'   => $user,
        );
        $this->load->view('ujianportal/berhasil', $data);
    }

    private function cari_ujian($kode) {
        $tp  = $this->dash->getTahunActive();
        $smt = $this->dash->getSemesterActive();
        if (!$tp || !$smt) {
            return null;
        }

        $this->db->select('j.*, b.bank_kelas, b.bank_kode, b.bank_nama, b.bank_mapel_id,
            m.nama_mapel, jn.nama_jenis, jn.kode_jenis', false)
            ->from('cbt_jadwal j')
            ->join('cbt_bank_soal b', 'b.id_bank = j.id_bank', 'left')
            ->join('master_mapel m', 'm.id_mapel = b.bank_mapel_id', 'left')
            ->join('cbt_jenis jn', 'jn.id_jenis = j.id_jenis', 'left')
            ->where('j.id_tp', $tp->id_tp)
            ->where('j.id_smt', $smt->id_smt)
            ->where('j.status', 1)
            ->where('UPPER(j.token_ujian) =', $kode)
            ->limit(1);
        $u = $this->db->get()->row();

        if (!$u) {
            return null;
        }
        $tgl = date('Y-m-d');
        if (!empty($u->tgl_mulai) && substr((string) $u->tgl_mulai, 0, 10) > $tgl) {
            return null;
        }
        if (!empty($u->tgl_selesai) && substr((string) $u->tgl_selesai, 0, 10) < $tgl) {
            return null;
        }
        return $u;
    }

    private function siswa_ujian($ujian) {
        $tp  = $this->dash->getTahunActive();
        $smt = $this->dash->getSemesterActive();

        $kelas = array();
        if (!empty($ujian->bank_kelas)) {
            $list = @unserialize($ujian->bank_kelas, array('allowed_classes' => array('stdClass')));
            if (is_array($list)) {
                foreach ($list as $k) {
                    $k = (array) $k;
                    if (isset($k['kelas_id']) && $k['kelas_id'] !== null && $k['kelas_id'] !== '') {
                        $kelas[] = (int) $k['kelas_id'];
                    }
                }
            }
        }
        if (count($kelas) === 0) {
            return array();
        }

        $this->db->select('s.id_siswa, s.nis, s.nama, s.username, k.nama_kelas', false)
            ->from('kelas_siswa ks')
            ->join('master_siswa s', 's.id_siswa = ks.id_siswa')
            ->join('master_kelas k', 'k.id_kelas = ks.id_kelas', 'left')
            ->where('ks.id_tp', $tp->id_tp)
            ->where('ks.id_smt', $smt->id_smt)
            ->where_in('ks.id_kelas', $kelas)
            ->order_by('s.nama', 'ASC');
        return $this->db->get()->result();
    }
}
