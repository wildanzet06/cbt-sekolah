<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Akun extends CI_Controller {

    const MIN_PASSWORD   = 8;   // panjang minimal password baru
    const MAKS_PASSWORD  = 64;  // panjang maksimal password baru
    const MAKS_GAGAL     = 5;   // salah memasukkan password lama sebanyak ini -> dikunci sementara
    const MENIT_KUNCI    = 10;  // lama penguncian (menit)

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('ion_auth', 'session'));

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }

        $this->load->model('Dashboard_model', 'dashboard');
    }

    // Data yang dibutuhkan header/footer template siswa
    private function _data($judul) {
        $data['judul']        = $judul;
        $data['title']        = $judul;
        $data['setting']      = $this->dashboard->getSetting();
        $data['tp_active']    = $this->dashboard->getTahunActive();
        $data['smt_active']   = $this->dashboard->getSemesterActive();
        $data['running_text'] = array(); // footer siswa memerlukan variabel ini
        $data['min_password'] = self::MIN_PASSWORD;
        $data['maks_password'] = self::MAKS_PASSWORD;
        return $data;
    }

    // Halaman form ganti password
    public function password() {
        $data = $this->_data('Ganti Password');

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('akun/password', $data);
        $this->load->view('members/siswa/templates/footer');
    }

    // Proses ganti password
    public function password_simpan() {
        if ($this->input->method() !== 'post') {
            redirect('akun/password');
            return;
        }

        // Dikunci sementara jika terlalu sering salah password lama
        $kunci = (int) $this->session->userdata('pw_kunci_sampai');
        if ($kunci > time()) {
            $sisa = (int) ceil(($kunci - time()) / 60);
            $this->session->set_flashdata('error',
                'Terlalu banyak percobaan yang salah. Coba lagi dalam ' . $sisa . ' menit.');
            redirect('akun/password');
            return;
        }

        // Password sengaja tidak di-trim atau difilter
        $lama   = (string) $this->input->post('password_lama');
        $baru   = (string) $this->input->post('password_baru');
        $konfir = (string) $this->input->post('password_konfirmasi');

        if ($lama === '' || $baru === '' || $konfir === '') {
            $this->session->set_flashdata('error', 'Semua kolom wajib diisi.');
            redirect('akun/password');
            return;
        }
        if ($baru !== $konfir) {
            $this->session->set_flashdata('error', 'Konfirmasi password baru tidak sama.');
            redirect('akun/password');
            return;
        }
        if (mb_strlen($baru) < self::MIN_PASSWORD) {
            $this->session->set_flashdata('error', 'Password baru minimal ' . self::MIN_PASSWORD . ' karakter.');
            redirect('akun/password');
            return;
        }
        if (mb_strlen($baru) > self::MAKS_PASSWORD) {
            $this->session->set_flashdata('error', 'Password baru maksimal ' . self::MAKS_PASSWORD . ' karakter.');
            redirect('akun/password');
            return;
        }
        if ($baru === $lama) {
            $this->session->set_flashdata('error', 'Password baru harus berbeda dari password lama.');
            redirect('akun/password');
            return;
        }

        // Identitas login sesuai pengaturan Ion Auth (biasanya username)
        $user  = $this->ion_auth->user()->row();
        $kolom = $this->config->item('identity', 'ion_auth');
        $identity = ($kolom && isset($user->$kolom)) ? $user->$kolom : $user->username;

        $berhasil = $this->ion_auth->change_password($identity, $lama, $baru);

        if ($berhasil) {
            $this->_catat_password_diganti($user->username);
            $this->session->unset_userdata(array('pw_gagal', 'pw_kunci_sampai'));
            $this->session->set_flashdata('success', 'Password berhasil diubah.');
        } else {
            $gagal = (int) $this->session->userdata('pw_gagal') + 1;
            if ($gagal >= self::MAKS_GAGAL) {
                $this->session->set_userdata('pw_kunci_sampai', time() + self::MENIT_KUNCI * 60);
                $this->session->set_userdata('pw_gagal', 0);
            } else {
                $this->session->set_userdata('pw_gagal', $gagal);
            }
            $this->session->set_flashdata('error', 'Gagal mengubah password. Pastikan password lama benar.');
        }

        redirect('akun/password');
    }

    // Mencatat bahwa pengguna sudah mengganti password sendiri.
    // Yang disimpan HANYA nama pengguna + waktu, bukan password.
    private function _catat_password_diganti($username) {
        if (!$this->db->table_exists('cbt_password_diganti')) return;
        $this->db->query(
            "INSERT INTO cbt_password_diganti (username, diganti_pada) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE diganti_pada = VALUES(diganti_pada)",
            array((string) $username, time())
        );
    }

    // Dipakai halaman User Management (admin): dari daftar username yang dikirim,
    // mana saja yang passwordnya sudah diganti sendiri oleh pengguna.
    // Catatan: jika akun dibuat ulang setelah itu (Nonaktifkan lalu Aktifkan),
    // akun kembali memakai password awal, jadi penanda otomatis tidak berlaku lagi.
    public function status_password() {
        $kosong = array('diganti' => array());

        if (!$this->ion_auth->is_admin() || $this->input->method() !== 'post') {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode($kosong));
            return;
        }

        $username = $this->input->post('usernames');
        $username = is_array($username) ? array_map('strval', $username) : array();
        $username = array_slice(array_values(array_unique($username)), 0, 200);

        $diganti = array();
        if (!empty($username) && $this->db->table_exists('cbt_password_diganti')) {
            $this->db->select('username, diganti_pada');
            $this->db->where_in('username', $username);
            $catatan = $this->db->get('cbt_password_diganti')->result();

            // Waktu akun dibuat (dibaca terpisah supaya tidak ada gabungan antar tabel)
            $dibuat = array();
            if (!empty($catatan) && $this->db->field_exists('created_on', 'users')) {
                $nama = array();
                foreach ($catatan as $c) $nama[] = $c->username;
                $this->db->select('username, created_on');
                $this->db->where_in('username', $nama);
                foreach ($this->db->get('users')->result() as $u) {
                    $dibuat[$u->username] = (int) $u->created_on;
                }
            }

            foreach ($catatan as $c) {
                // Berlaku hanya jika penggantian terjadi sesudah akun dibuat
                if (!isset($dibuat[$c->username]) || (int) $c->diganti_pada >= $dibuat[$c->username]) {
                    $diganti[] = $c->username;
                }
            }
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(array('diganti' => $diganti)));
    }

    // Halaman siswa: melihat kehadirannya sendiri (hadir/izin/sakit/alpa)
    public function kehadiran() {
        $this->load->model('Presensi_model');

        $user  = $this->ion_auth->user()->row();
        $siswa = $this->Presensi_model->siswa_by_username($user->username);

        $bulan = (int) ($this->input->get('bulan') ?: date('n'));
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = max(1, min(12, $bulan));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        $dari   = sprintf('%04d-%02d-01', $tahun, $bulan);
        $sampai = date('Y-m-t', strtotime($dari));

        $data = $this->_data('Kehadiran Saya');
        $data['siswa']        = $siswa;
        $data['bulan']        = $bulan;
        $data['tahun']        = $tahun;
        $data['daftar_bulan'] = $this->Presensi_model->daftar_bulan;
        $data['status_ref']   = $this->Presensi_model->status;
        $data['riwayat']      = $siswa ? $this->Presensi_model->riwayat_siswa($siswa->id_siswa, $dari, $sampai) : array();
        $data['hitung']       = $this->Presensi_model->hitung($data['riwayat']);

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('akun/kehadiran', $data);
        $this->load->view('members/siswa/templates/footer');
    }

    // Halaman siswa/orang tua: melihat poin pelanggaran sendiri
    public function pelanggaran() {
        $this->load->model('Pelanggaran_model');

        $user  = $this->ion_auth->user()->row();
        $siswa = $this->Pelanggaran_model->siswa_by_username($user->username);

        $data = $this->_data('Poin Pelanggaran Saya');
        $periode = $siswa ? $this->Pelanggaran_model->periode_siswa($siswa->id_siswa) : array();

        // Periode aktif menjadi pilihan awal (bisa kosong pada instalasi baru)
        $tp_aktif  = isset($data['tp_active']) ? $data['tp_active'] : null;
        $smt_aktif = isset($data['smt_active']) ? $data['smt_active'] : null;
        $id_tp  = $tp_aktif ? (int) $tp_aktif->id_tp : 0;
        $id_smt = $smt_aktif ? (int) $smt_aktif->id_smt : 0;

        // Pilihan periode dari siswa hanya diterima jika memang ada catatannya
        $pilih = explode(':', (string) $this->input->get('periode'));
        if (count($pilih) === 2) {
            $pilih_tp  = (int) $pilih[0];
            $pilih_smt = (int) $pilih[1];
            foreach ($periode as $p) {
                if ((int) $p->id_tp === $pilih_tp && (int) $p->id_smt === $pilih_smt) {
                    $id_tp  = $pilih_tp;
                    $id_smt = $pilih_smt;
                    break;
                }
            }
        }

        $periode_aktif = null;
        foreach ($periode as $p) {
            if ((int) $p->id_tp === $id_tp && (int) $p->id_smt === $id_smt) {
                $periode_aktif = $p;
                break;
            }
        }
        if (!$periode_aktif) {
            $periode_aktif = (object) array(
                'id_tp'    => $id_tp,
                'id_smt'   => $id_smt,
                'tahun'    => $tp_aktif ? $tp_aktif->tahun : '-',
                'nama_smt' => $smt_aktif ? $smt_aktif->nama_smt : '-',
            );
        }

        $riwayat = $siswa
            ? $this->Pelanggaran_model->riwayat_siswa($siswa->id_siswa, $id_tp, $id_smt)
            : array();

        $total = 0;
        foreach ($riwayat as $r) {
            $total += (int) $r->poin;
        }

        $data['siswa']         = $siswa;
        $data['periode']       = $periode;
        $data['periode_aktif'] = $periode_aktif;
        $data['riwayat']       = $riwayat;
        $data['total_poin']    = $total;

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('akun/pelanggaran', $data);
        $this->load->view('members/siswa/templates/footer');
    }
}
