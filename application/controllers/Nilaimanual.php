<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Input Nilai Manual (ujian kertas / offline) untuk admin dan guru.
 * File ini baru; tidak mengubah controller GarudaCBT yang sudah ada.
 */
class Nilaimanual extends CI_Controller
{
    private $user;
    private $isAdmin;
    private $guru;      // baris master_guru (null untuk admin)
    private $tp;
    private $smt;

    const JENIS = ['PH' => 'Penilaian Harian', 'PTS' => 'Penilaian Tengah Semester', 'PAS' => 'Penilaian Akhir Semester'];

    public function __construct()
    {
        parent::__construct();

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            show_error('Hanya Administrator dan Guru yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Nilaimanual_model', 'nm');

        $this->user = $this->ion_auth->user()->row();
        $this->isAdmin = $this->ion_auth->is_admin();
        $this->tp = $this->nm->activeTp();
        $this->smt = $this->nm->activeSmt();

        if (!$this->tp || !$this->smt) {
            show_error('Tahun pelajaran atau semester aktif belum diatur.', 500, 'Data belum lengkap');
        }

        $this->guru = null;
        if (!$this->isAdmin) {
            $this->guru = $this->nm->guruByUser($this->user->id);
            if (!$this->guru) {
                show_error('Data guru untuk akun ini tidak ditemukan.', 403, 'Akses Terlarang');
            }
        }
    }

    /* ---------------------------------------------------------------
     * Helper
     * ------------------------------------------------------------- */

    private function json($data, $code = 200)
    {
        $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function idGuruFilter()
    {
        return $this->isAdmin ? null : (int)$this->guru->id_guru;
    }

    private function csrf()
    {
        return [
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash()
        ];
    }

    private function render($view, array $data)
    {
        $data['user'] = $this->user;
        $data['setting'] = $this->dashboard->getSetting();
        $data['csrf'] = $this->csrf();
        $data['is_admin'] = $this->isAdmin;

        // Variabel yang dibutuhkan template dashboard (navbar & header).
        $data['tp'] = $this->nm->allTp();
        $data['smt'] = $this->nm->allSmt();
        $data['tp_active'] = $this->tp;
        $data['smt_active'] = $this->smt;

        if ($this->isAdmin) {
            $data['profile'] = $this->dashboard->getProfileAdmin($this->user->id);
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view($view);
            $this->load->view('_templates/dashboard/_footer');
        } else {
            $data['guru'] = $this->dashboard->getDataGuruByUserId($this->user->id, $this->tp->id_tp, $this->smt->id_smt);
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view($view);
            $this->load->view('members/guru/templates/footer');
        }
    }

    /** Ambil ujian + cek hak akses. Return objek atau null (sudah mengirim respons error). */
    private function ujianAkses($id_nm, $asJson = true)
    {
        $u = $this->nm->getUjian((int)$id_nm);
        $ok = $u && (int)$u->id_tp === (int)$this->tp->id_tp && (int)$u->id_smt === (int)$this->smt->id_smt;
        if ($ok && !$this->isAdmin) {
            $ok = (int)$u->id_guru === (int)$this->guru->id_guru
                && $this->nm->guruBolehAkses($this->guru->id_guru, $u->id_tp, $u->id_smt, $u->id_mapel, $u->id_kelas);
        }
        if (!$ok) {
            if ($asJson) {
                $this->json(['status' => false, 'msg' => 'Data tidak ditemukan atau Anda tidak berhak mengaksesnya.'], 403);
            } else {
                show_error('Data tidak ditemukan atau Anda tidak berhak mengaksesnya.', 403, 'Akses Terlarang');
            }
            return null;
        }
        return $u;
    }

    private function validTanggal($s)
    {
        $d = DateTime::createFromFormat('Y-m-d', (string)$s);
        return $d && $d->format('Y-m-d') === $s;
    }

    /* ---------------------------------------------------------------
     * Halaman
     * ------------------------------------------------------------- */

    public function index()
    {
        $opsi = $this->nm->opsi($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter());

        $data = [
            'judul' => 'Nilai Manual',
            'subjudul' => 'Input nilai ujian kertas (offline)',
            'ujian' => $this->nm->listUjian($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter()),
            'opsi' => $opsi,
            'jenis' => self::JENIS,
            'tp' => $this->tp,
            'smt' => $this->smt
        ];
        $this->render('cbt/nilaimanual/data', $data);
    }

    public function isi($id_nm = 0)
    {
        $u = $this->ujianAkses($id_nm, false);
        if (!$u) {
            return;
        }
        $data = [
            'judul' => 'Input Nilai Manual',
            'subjudul' => $u->nama_ujian,
            'u' => $u,
            'jenis' => self::JENIS,
            'siswa' => $this->nm->siswaKelas($u->id_kelas, $u->id_tp, $u->id_smt),
            'nilai' => $this->nm->nilaiMap($u->id_nm)
        ];
        $this->render('cbt/nilaimanual/isi', $data);
    }

    public function rekap()
    {
        $opsi = $this->nm->opsi($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter());
        $kelas = (int)$this->input->get('kelas');
        $mapel = (int)$this->input->get('mapel');

        $rekap = null;
        if ($kelas && $mapel) {
            $boleh = isset($opsi['pairs'][$mapel]) && in_array($kelas, $opsi['pairs'][$mapel], true);
            if ($boleh) {
                $rekap = $this->nm->rekap($this->tp->id_tp, $this->smt->id_smt, $kelas, $mapel);
            }
        }

        $data = [
            'judul' => 'Rekap Nilai Manual',
            'subjudul' => 'Rekap nilai ujian kertas per kelas dan mapel',
            'opsi' => $opsi,
            'kelas_sel' => $kelas,
            'mapel_sel' => $mapel,
            'rekap' => $rekap,
            'jenis' => self::JENIS
        ];
        $this->render('cbt/nilaimanual/rekap', $data);
    }

    /* ---------------------------------------------------------------
     * Aksi (AJAX / POST)
     * ------------------------------------------------------------- */

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }

        $id_nm = (int)$this->input->post('id_nm');
        $nama = trim((string)$this->input->post('nama_ujian', true));
        $tanggal = trim((string)$this->input->post('tanggal', true));
        $maks = (int)$this->input->post('nilai_maks');

        if ($nama === '' || mb_strlen($nama) > 100) {
            return $this->json(['status' => false, 'msg' => 'Nama ujian wajib diisi (maksimal 100 karakter).']);
        }
        if (!$this->validTanggal($tanggal)) {
            return $this->json(['status' => false, 'msg' => 'Tanggal tidak valid.']);
        }
        if ($maks < 1 || $maks > 1000) {
            return $this->json(['status' => false, 'msg' => 'Nilai maksimal harus antara 1 dan 1000.']);
        }

        // Ubah ujian yang sudah ada: hanya nama, tanggal, nilai maksimal.
        if ($id_nm > 0) {
            $u = $this->ujianAkses($id_nm);
            if (!$u) {
                return;
            }
            $this->nm->updateUjian($id_nm, ['nama_ujian' => $nama, 'tanggal' => $tanggal, 'nilai_maks' => $maks]);
            $msg = 'Perubahan disimpan.';
            if (!empty($u->rapor_terkirim) && (int)$u->nilai_maks !== $maks) {
                $msg .= ' Nilai maksimal berubah, kirim ulang ke rapor agar nilai rapor ikut diperbarui.';
            }
            return $this->json(['status' => true, 'id' => $id_nm, 'msg' => $msg]);
        }

        // Ujian baru
        $id_mapel = (int)$this->input->post('id_mapel');
        $id_kelas = (int)$this->input->post('id_kelas');
        $kode = (string)$this->input->post('kode_jenis', true);
        $aspek = (int)$this->input->post('aspek') === 2 ? 2 : 1;

        if (!isset(self::JENIS[$kode])) {
            return $this->json(['status' => false, 'msg' => 'Jenis ujian tidak valid.']);
        }
        if ($kode !== 'PH') {
            $aspek = 1;
        }

        $opsi = $this->nm->opsi($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter());
        if (!isset($opsi['pairs'][$id_mapel]) || !in_array($id_kelas, $opsi['pairs'][$id_mapel], true)) {
            return $this->json(['status' => false, 'msg' => 'Mapel atau kelas tidak valid untuk Anda.'], 403);
        }

        $new = $this->nm->createUjian([
            'id_tp' => $this->tp->id_tp,
            'id_smt' => $this->smt->id_smt,
            'id_mapel' => $id_mapel,
            'id_kelas' => $id_kelas,
            'kode_jenis' => $kode,
            'aspek' => $aspek,
            'nama_ujian' => $nama,
            'tanggal' => $tanggal,
            'nilai_maks' => $maks,
            'id_guru' => $this->isAdmin ? null : (int)$this->guru->id_guru
        ]);
        return $this->json(['status' => true, 'id' => $new, 'msg' => 'Ujian offline dibuat.']);
    }

    public function hapus()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->ujianAkses($this->input->post('id_nm'));
        if (!$u) {
            return;
        }
        $ok = $this->nm->deleteUjian($u->id_nm);
        return $this->json([
            'status' => (bool)$ok,
            'msg' => $ok ? 'Ujian dihapus.' : 'Gagal menghapus.'
        ]);
    }

    public function simpannilai()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->ujianAkses($this->input->post('id_nm'));
        if (!$u) {
            return;
        }

        $nilai = $this->input->post('nilai');
        if (!is_array($nilai)) {
            return $this->json(['status' => false, 'msg' => 'Tidak ada nilai yang dikirim.']);
        }

        $bersih = [];
        $salah = [];
        foreach ($nilai as $id_siswa => $v) {
            $v = trim(str_replace(',', '.', (string)$v));
            if ($v === '') {
                $bersih[(int)$id_siswa] = '';
                continue;
            }
            if (!is_numeric($v) || (float)$v < 0 || (float)$v > (float)$u->nilai_maks) {
                $salah[] = (int)$id_siswa;
                continue;
            }
            $bersih[(int)$id_siswa] = $v;
        }
        if (count($salah) > 0) {
            return $this->json([
                'status' => false,
                'msg' => 'Ada nilai di luar rentang 0 - ' . $u->nilai_maks . ' atau bukan angka.',
                'salah' => $salah
            ]);
        }

        $saved = $this->nm->simpanNilai($u, $bersih);
        if ($saved < 0) {
            return $this->json(['status' => false, 'msg' => 'Gagal menyimpan nilai.']);
        }
        return $this->json(['status' => true, 'msg' => 'Nilai tersimpan.', 'jumlah' => $saved]);
    }

    public function kirimrapor()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->ujianAkses($this->input->post('id_nm'));
        if (!$u) {
            return;
        }
        $slot = $this->input->post('slot', true);
        $slot = ($slot === '' || $slot === null) ? null : strtolower($slot);

        $r = $this->nm->kirimRapor($u, $slot);
        return $this->json([
            'status' => $r['ok'],
            'msg' => $r['ok']
                ? 'Nilai ' . $r['jumlah'] . ' siswa berhasil dikirim ke rapor' . ($r['slot'] ? ' (kolom ' . strtoupper($r['slot']) . ')' : '') . '.'
                : $r['msg'],
            'slot' => $r['slot']
        ]);
    }
}
