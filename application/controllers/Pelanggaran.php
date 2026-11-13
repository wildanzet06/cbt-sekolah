<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Poin Pelanggaran Siswa.
 *
 * Bisa diakses admin dan guru. Guru hanya boleh mengubah/menghapus catatan
 * yang ia buat sendiri (kolom dicatat_oleh), admin boleh mengubah semuanya.
 * Data selalu dipisah menurut tahun pelajaran + semester yang sedang aktif.
 */
class Pelanggaran extends CI_Controller {

    // Saran jenis pelanggaran (input tetap bebas)
    private $jenis_umum = array(
        'Terlambat masuk sekolah',
        'Tidak memakai atribut sekolah lengkap',
        'Tidak memakai seragam sesuai ketentuan',
        'Rambut tidak rapi / melebihi ketentuan',
        'Membuang sampah tidak pada tempatnya',
        'Tidak mengerjakan tugas',
        'Membolos / tidak masuk tanpa keterangan',
        'Mengganggu ketenangan belajar',
        'Berbicara kasar / tidak sopan',
        'Berkelahi',
        'Merusak fasilitas sekolah',
        'Menggunakan telepon genggam saat pembelajaran'
    );

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'text'));
        $this->load->library(array('ion_auth', 'session'));

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            show_error('Akses Ditolak', 403);
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Pelanggaran_model');
    }

    // =====================================================================
    // HELPER PRIVATE
    // =====================================================================

    private function _periode() {
        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            show_error('Tahun pelajaran dan semester aktif belum diset oleh admin.', 400);
        }
        return array($tp, $smt);
    }

    private function _guru_row($id_user, $id_tp, $id_smt) {
        if (!$id_user) {
            return null;
        }
        return $this->dashboard->getDataGuruByUserId($id_user, $id_tp, $id_smt);
    }

    private function _base_data($title) {
        list($tp, $smt) = $this->_periode();
        $user = $this->ion_auth->user()->row();

        $data = array(
            'title'      => $title,
            'judul'      => $title,
            'user'       => $user,
            'setting'    => $this->dashboard->getSetting(),
            'tp'         => $this->dashboard->getTahun(),
            'tp_active'  => $tp,
            'smt'        => $this->dashboard->getSemester(),
            'smt_active' => $smt,
            'is_admin'   => $this->ion_auth->is_admin(),
            'username'   => $user ? $user->username : '',
        );

        if ($data['is_admin']) {
            $data['profile'] = $this->dashboard->getProfileAdmin($user->id);
            $data['guru']    = null;
        } else {
            $guru = $this->_guru_row($user->id, $tp->id_tp, $smt->id_smt);
            if (!$guru) {
                // Tetap tampilkan halaman; sidebar butuh objek guru minimal.
                $guru = (object) array(
                    'id_guru'    => null,
                    'id_jabatan' => null,
                    'wali_kelas' => null,
                    'nama_guru'  => $user->username,
                    'foto'       => null,
                );
            }
            $data['guru']    = $guru;
            $data['profile'] = null;
        }

        return $data;
    }

    private function _render($view, $data) {
        if (!empty($data['is_admin'])) {
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view($view, $data);
            $this->load->view('_templates/dashboard/_footer');
        } else {
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view($view, $data);
            $this->load->view('members/guru/templates/footer');
        }
    }

    private function _harus_post() {
        if ($this->input->method() !== 'post') {
            redirect('pelanggaran');
            exit;
        }
    }

    private function _tanggal_valid($t) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    // Rentang tanggal dari query string (kosong = tanpa batas)
    private function _rentang_get() {
        $dari   = trim((string) $this->input->get('dari'));
        $sampai = trim((string) $this->input->get('sampai'));

        if (!$this->_tanggal_valid($dari))   $dari = '';
        if (!$this->_tanggal_valid($sampai)) $sampai = '';

        if ($dari !== '' && $sampai !== '' && $dari > $sampai) {
            $tmp    = $dari;
            $dari   = $sampai;
            $sampai = $tmp;
        }
        return array($dari, $sampai);
    }

    private function _filter_kelas($id_tp, $id_smt) {
        $id = (int) $this->input->get('kelas');
        if ($id && !$this->Pelanggaran_model->kelas_valid($id, $id_tp, $id_smt)) {
            $id = 0;
        }
        return $id;
    }

    private function _boleh_ubah($row) {
        if (!$row) {
            return false;
        }
        if ($this->ion_auth->is_admin()) {
            return true;
        }
        $user = $this->ion_auth->user()->row();
        return $user
            && $row->dicatat_oleh !== null
            && $row->dicatat_oleh === $user->username;
    }

    private function _kembali($id_kelas = 0) {
        redirect('pelanggaran' . ($id_kelas ? '?kelas=' . (int) $id_kelas : ''));
    }

    /**
     * Validasi input tambah/ubah.
     * Mengembalikan array($data, $error). Salah satu pasti null.
     *
     * @param bool $untuk_ubah true = jangan sertakan kolom kepemilikan
     */
    private function _input_data($untuk_ubah = false) {
        list($tp, $smt) = $this->_periode();

        $id_siswa = (int) $this->input->post('id_siswa');
        $tanggal  = trim((string) $this->input->post('tanggal'));
        $jenis    = trim((string) $this->input->post('jenis_pelanggaran'));
        $poin     = (int) $this->input->post('poin');
        $ket      = trim((string) $this->input->post('keterangan'));

        $siswa = $this->Pelanggaran_model->siswa_by_id($id_siswa);
        if (!$siswa) {
            return array(null, 'Siswa tidak ditemukan.');
        }

        $ks = $this->Pelanggaran_model->kelas_siswa($id_siswa, $tp->id_tp, $smt->id_smt);
        if (!$ks) {
            return array(null, 'Siswa tersebut tidak terdaftar di kelas mana pun pada tahun pelajaran/semester aktif.');
        }

        if (!$this->_tanggal_valid($tanggal)) {
            return array(null, 'Tanggal tidak valid.');
        }
        if ($tanggal > date('Y-m-d')) {
            return array(null, 'Tanggal pelanggaran tidak boleh di masa depan.');
        }
        if ($jenis === '') {
            return array(null, 'Jenis pelanggaran wajib diisi.');
        }
        if (mb_strlen($jenis, 'UTF-8') > 150) {
            $jenis = mb_substr($jenis, 0, 150, 'UTF-8');
        }
        if ($poin < 1 || $poin > 1000) {
            return array(null, 'Poin harus berupa angka 1 sampai 1000.');
        }
        if (mb_strlen($ket, 'UTF-8') > 500) {
            $ket = mb_substr($ket, 0, 500, 'UTF-8');
        }

        $data = array(
            'id_siswa'          => $id_siswa,
            'id_kelas'          => (int) $ks->id_kelas,
            'id_tp'             => (int) $tp->id_tp,
            'id_smt'            => (int) $smt->id_smt,
            'tanggal'           => $tanggal,
            'jenis_pelanggaran' => $jenis,
            'keterangan'        => $ket !== '' ? $ket : null,
            'poin'              => $poin,
        );

        if (!$untuk_ubah) {
            $user  = $this->ion_auth->user()->row();
            $guru  = $this->ion_auth->is_admin()
                ? null
                : $this->_guru_row($user->id, $tp->id_tp, $smt->id_smt);

            $data['id_guru']      = $guru && $guru->id_guru ? (int) $guru->id_guru : null;
            $data['dicatat_oleh'] = $user ? $user->username : null;
        }

        return array($data, null);
    }

    private function _flash_error($pesan) {
        $this->session->set_flashdata('error', $pesan);
    }

    // =====================================================================
    // DAFTAR & INPUT
    // =====================================================================

    public function index() {
        list($tp, $smt) = $this->_periode();
        list($dari, $sampai) = $this->_rentang_get();
        $kelas_id = $this->_filter_kelas($tp->id_tp, $smt->id_smt);

        $data = $this->_base_data('Poin Pelanggaran Siswa');
        $data['daftar_kelas'] = $this->Pelanggaran_model->daftar_kelas($tp->id_tp, $smt->id_smt);
        $data['semua_siswa']  = $this->Pelanggaran_model->semua_siswa($tp->id_tp, $smt->id_smt);
        $data['daftar']       = $this->Pelanggaran_model->daftar($tp->id_tp, $smt->id_smt, $kelas_id, $dari, $sampai);
        $data['ringkasan']    = $this->Pelanggaran_model->total($tp->id_tp, $smt->id_smt, $kelas_id, $dari, $sampai);
        $data['kelas_id']     = $kelas_id;
        $data['dari']         = $dari;
        $data['sampai']       = $sampai;
        $data['jenis_umum']   = $this->jenis_umum;
        $data['tanggal_ini']  = date('Y-m-d');

        // Peta siswa => kelas untuk modal input
        $siswa_map = array();
        foreach ($data['semua_siswa'] as $s) {
            $siswa_map[(int) $s->id_siswa] = array(
                'id_kelas'   => (int) $s->id_kelas,
                'nama_kelas' => (string) $s->nama_kelas,
            );
        }
        $data['siswa_map'] = $siswa_map;

        // Data untuk tombol ubah (disimpan sebagai JSON agar aman dari tanda kutip)
        $data_edit = array();
        foreach ($data['daftar'] as $r) {
            if (!$this->_boleh_ubah($r)) {
                continue;
            }
            $data_edit[(int) $r->id_pelanggaran] = array(
                'id_siswa'    => (int) $r->id_siswa,
                'tanggal'     => (string) $r->tanggal,
                'jenis'       => (string) $r->jenis_pelanggaran,
                'poin'        => (int) $r->poin,
                'keterangan'  => (string) $r->keterangan,
            );
        }
        $data['data_edit'] = $data_edit;

        $this->_render('pelanggaran/index', $data);
    }

    public function simpan() {
        $this->_harus_post();

        list($data, $error) = $this->_input_data(false);
        if ($error !== null) {
            $this->_flash_error($error);
            $this->_kembali();
            return;
        }

        $id = $this->Pelanggaran_model->simpan($data);
        if ($id) {
            $this->session->set_flashdata('success', 'Pelanggaran berhasil dicatat.');
            $this->_kembali($data['id_kelas']);
        } else {
            $this->_flash_error('Pelanggaran gagal disimpan. Coba lagi.');
            $this->_kembali();
        }
    }

    public function ubah($id = 0) {
        $this->_harus_post();

        $row = $this->Pelanggaran_model->get($id);
        if (!$row) {
            $this->_flash_error('Data pelanggaran tidak ditemukan.');
            $this->_kembali();
            return;
        }
        if (!$this->_boleh_ubah($row)) {
            $this->_flash_error('Anda hanya boleh mengubah catatan yang Anda buat sendiri.');
            $this->_kembali((int) $row->id_kelas);
            return;
        }

        list($data, $error) = $this->_input_data(true);
        if ($error !== null) {
            $this->_flash_error($error);
            $this->_kembali((int) $row->id_kelas);
            return;
        }

        if ($this->Pelanggaran_model->ubah($row->id_pelanggaran, $data)) {
            $this->session->set_flashdata('success', 'Catatan pelanggaran berhasil diperbarui.');
            $this->_kembali($data['id_kelas']);
        } else {
            $this->_flash_error('Catatan pelanggaran gagal diperbarui.');
            $this->_kembali((int) $row->id_kelas);
        }
    }

    public function hapus($id = 0) {
        $this->_harus_post();

        $row = $this->Pelanggaran_model->get($id);
        if (!$row) {
            $this->_flash_error('Data pelanggaran tidak ditemukan.');
            $this->_kembali();
            return;
        }
        if (!$this->_boleh_ubah($row)) {
            $this->_flash_error('Anda hanya boleh menghapus catatan yang Anda buat sendiri.');
            $this->_kembali((int) $row->id_kelas);
            return;
        }

        if ($this->Pelanggaran_model->hapus($row->id_pelanggaran)) {
            $this->session->set_flashdata('success', 'Catatan pelanggaran berhasil dihapus.');
        } else {
            $this->_flash_error('Catatan pelanggaran gagal dihapus.');
        }
        $this->_kembali((int) $row->id_kelas);
    }

    // =====================================================================
    // REKAP
    // =====================================================================

    public function rekap() {
        list($tp, $smt) = $this->_periode();
        list($dari, $sampai) = $this->_rentang_get();
        $kelas_id = $this->_filter_kelas($tp->id_tp, $smt->id_smt);

        $data = $this->_base_data('Rekap Poin Pelanggaran');
        $data['daftar_kelas']  = $this->Pelanggaran_model->daftar_kelas($tp->id_tp, $smt->id_smt);
        $data['kelas_id']      = $kelas_id;
        $data['dari']          = $dari;
        $data['sampai']        = $sampai;
        $data['rekap_siswa']   = $this->Pelanggaran_model->rekap_siswa($tp->id_tp, $smt->id_smt, $kelas_id, $dari, $sampai);
        $data['rekap_kelas']   = $this->Pelanggaran_model->rekap_kelas($tp->id_tp, $smt->id_smt, $dari, $sampai, $kelas_id);
        $data['ringkasan']     = $this->Pelanggaran_model->total($tp->id_tp, $smt->id_smt, $kelas_id, $dari, $sampai);

        $this->_render('pelanggaran/rekap', $data);
    }

    /**
     * Riwayat satu siswa. ?format=json dipakai modal pada halaman rekap.
     */
    public function riwayat($id_siswa = 0) {
        list($tp, $smt) = $this->_periode();
        list($dari, $sampai) = $this->_rentang_get();

        $siswa = $this->Pelanggaran_model->siswa_by_id($id_siswa);
        if (!$siswa) {
            show_error('Data siswa tidak ditemukan.', 404);
        }

        $ks       = $this->Pelanggaran_model->kelas_siswa($siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $riwayat  = $this->Pelanggaran_model->riwayat_siswa($siswa->id_siswa, $tp->id_tp, $smt->id_smt, $dari, $sampai);
        $total    = 0;
        foreach ($riwayat as $r) {
            $total += (int) $r->poin;
        }

        if ($this->input->get('format') === 'json' || $this->input->is_ajax_request()) {
            $rows = array();
            foreach ($riwayat as $r) {
                $rows[] = array(
                    'tanggal'           => (string) $r->tanggal,
                    'jenis_pelanggaran' => (string) $r->jenis_pelanggaran,
                    'poin'              => (int) $r->poin,
                    'keterangan'        => (string) $r->keterangan,
                    'dicatat_oleh'      => (string) $r->dicatat_oleh,
                );
            }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'siswa' => array(
                        'id_siswa'   => (int) $siswa->id_siswa,
                        'nis'        => (string) $siswa->nis,
                        'nama'       => (string) $siswa->nama,
                        'nama_kelas' => $ks ? (string) $ks->nama_kelas : '-',
                    ),
                    'jumlah'     => count($rows),
                    'total_poin' => $total,
                    'rows'       => $rows,
                )));
            return;
        }

        $data = $this->_base_data('Riwayat Pelanggaran Siswa');
        $data['siswa']      = $siswa;
        $data['nama_kelas'] = $ks ? $ks->nama_kelas : '-';
        $data['riwayat']    = $riwayat;
        $data['total_poin'] = $total;
        $data['dari']       = $dari;
        $data['sampai']     = $sampai;

        $this->_render('pelanggaran/riwayat', $data);
    }

    // =====================================================================
    // EKSPOR EXCEL (.xlsx)
    // =====================================================================

    public function export() {
        list($tp, $smt) = $this->_periode();
        list($dari, $sampai) = $this->_rentang_get();
        $kelas_id = $this->_filter_kelas($tp->id_tp, $smt->id_smt);

        $jenis = strtolower(trim((string) $this->input->get('jenis')));
        if (!in_array($jenis, array('detail', 'siswa', 'kelas'), true)) {
            $jenis = 'detail';
        }

        $baris = array();

        if ($jenis === 'siswa') {
            $rows = $this->Pelanggaran_model->rekap_siswa($tp->id_tp, $smt->id_smt, $kelas_id, $dari, $sampai);
            $no = 1;
            foreach ($rows as $r) {
                $baris[] = array(
                    $no++,
                    (string) $r->nis,
                    (string) $r->nama,
                    (string) $r->nama_kelas,
                    (int) $r->jml,
                    (int) $r->total_poin,
                    $r->terakhir ? date('d/m/Y', strtotime($r->terakhir)) : '-',
                );
            }
            $headers = array('No', 'NIS', 'Nama Siswa', 'Kelas', 'Jumlah Pelanggaran', 'Total Poin', 'Pelanggaran Terakhir');
            $widths  = array(5, 13, 30, 12, 18, 12, 20);
            $sheet   = 'Rekap per Siswa';
            $nama    = 'rekap_pelanggaran_siswa';

        } elseif ($jenis === 'kelas') {
            $rows = $this->Pelanggaran_model->rekap_kelas($tp->id_tp, $smt->id_smt, $dari, $sampai, $kelas_id);
            $no = 1;
            foreach ($rows as $r) {
                $baris[] = array(
                    $no++,
                    (string) $r->nama_kelas,
                    (int) $r->jumlah_siswa,
                    (int) $r->siswa_terlibat,
                    (int) $r->jumlah_pelanggaran,
                    (int) $r->total_poin,
                    (float) $r->rata_poin,
                );
            }
            $headers = array('No', 'Kelas', 'Jumlah Siswa', 'Siswa Terlibat', 'Jumlah Pelanggaran', 'Total Poin', 'Rata-rata Poin');
            $widths  = array(5, 15, 14, 15, 18, 12, 15);
            $sheet   = 'Rekap per Kelas';
            $nama    = 'rekap_pelanggaran_kelas';

        } else {
            $rows = $this->Pelanggaran_model->daftar($tp->id_tp, $smt->id_smt, $kelas_id, $dari, $sampai);
            $no = 1;
            foreach ($rows as $r) {
                $baris[] = array(
                    $no++,
                    date('d/m/Y', strtotime($r->tanggal)),
                    (string) $r->nis,
                    (string) $r->nama,
                    (string) $r->nama_kelas,
                    (string) $r->jenis_pelanggaran,
                    (int) $r->poin,
                    (string) $r->keterangan,
                    (string) $r->dicatat_oleh,
                );
            }
            $headers = array('No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Jenis Pelanggaran', 'Poin', 'Keterangan', 'Dicatat Oleh');
            $widths  = array(5, 13, 13, 28, 12, 35, 8, 40, 18);
            $sheet   = 'Daftar Pelanggaran';
            $nama    = 'daftar_pelanggaran';
        }

        $periode = preg_replace('/[^A-Za-z0-9]/', '', (string) $tp->tahun);
        $nama_file = $nama . '_' . ($periode !== '' ? $periode . '_' : '') . date('Ymd') . '.xlsx';

        $this->load->library('simple_xlsx');
        $this->simple_xlsx->download($nama_file, $sheet, $headers, $baris, $widths);
    }
}
