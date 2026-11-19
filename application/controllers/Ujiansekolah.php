<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ujian Sekolah — "Daftar Ujian" (PRD: docs/PRD-UJIAN-SIMPEL.md)
 *
 * Controller ini adalah file BARU. Semua halaman lama (Jadwal, Bank Soal,
 * Cetak, Token, Status Siswa, Alokasi, Pengawas, Nomor Peserta, Sesi, Ruang)
 * tetap berfungsi dan tidak diubah controller-nya.
 *
 * Pengecualian yang disengaja (lihat PRD 6.6): satu statement token di
 * application/controllers/Siswa.php diubah agar membaca token per ujian.
 */
class Ujiansekolah extends CI_Controller
{
    private $user;
    private $isAdmin;
    private $guru = null;
    private $tp;
    private $smt;
    private $opsi = ['mapel' => [], 'kelas' => [], 'pairs' => []];

    public function __construct()
    {
        parent::__construct();

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            show_error('Hanya Administrator dan Guru yang diberi hak untuk mengakses halaman ini, <a href="'
                . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Ujiansekolah_model', 'us');

        $this->user = $this->ion_auth->user()->row();
        $this->isAdmin = $this->ion_auth->is_admin();
        $this->tp = $this->us->activeTp();
        $this->smt = $this->us->activeSmt();

        if (!$this->tp || !$this->smt) {
            show_error('Tahun pelajaran/semester aktif belum diatur. Atur dulu di menu Data Umum &rsaquo; Tahun Pelajaran.',
                500, 'Tahun pelajaran/semester aktif belum diatur');
        }

        if (!$this->isAdmin) {
            $this->guru = $this->us->guruByUser($this->user->id);
            if (!$this->guru) {
                show_error('Data guru untuk akun ini tidak ditemukan.', 403, 'Akses Terlarang');
            }
        }

        $this->opsi = $this->us->opsi(
            $this->tp->id_tp,
            $this->smt->id_smt,
            $this->isAdmin ? null : $this->guru->id_guru
        );
    }

    /* ==================================================================
     * Helper umum
     * ================================================================ */

    private function json($data, $code = 200)
    {
        $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
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
        // Nama variabel mengikuti konvensi template dashboard:
        // $tp/$smt = daftar (dipakai navbar), $tp_active/$smt_active = yang aktif.
        $data['tp'] = $this->db->get('master_tp')->result();
        $data['smt'] = $this->db->get('master_smt')->result();
        $data['tp_active'] = $this->tp;
        $data['smt_active'] = $this->smt;
        $data['opsi'] = $this->opsi;

        if ($this->isAdmin) {
            $data['profile'] = $this->dashboard->getProfileAdmin($this->user->id);
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view($view);
            $this->load->view('_templates/dashboard/_footer');
        } else {
            $data['guru'] = $this->dashboard->getDataGuruByUserId(
                $this->user->id, $this->tp->id_tp, $this->smt->id_smt
            );
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view($view);
            $this->load->view('members/guru/templates/footer');
        }
    }

    private function idGuruFilter()
    {
        return $this->isAdmin ? null : (int)$this->guru->id_guru;
    }

    /** Guru hanya boleh melihat ujian pada mapel/kelas yang dia ampu. */
    private function bolehLihat($u)
    {
        if ($this->isAdmin) {
            return true;
        }
        if ((int)$u->created_by === (int)$this->guru->id_guru) {
            return true;
        }
        return $this->us->guruBoleh(
            $this->guru->id_guru, $u->id_tp, $u->id_smt,
            $this->us->mapelUjian($u), $this->us->kelasUjian($u)
        );
    }

    /** Guru hanya boleh mengubah/menjalankan ujian buatannya sendiri. */
    private function bolehKelola($u)
    {
        return $this->isAdmin || (int)$u->created_by === (int)$this->guru->id_guru;
    }

    /** Ambil ujian + cek hak akses. Null bila sudah mengirim respons error. */
    private function ujianAkses($id_jadwal, $kelola = false, $asJson = true)
    {
        $u = $this->us->getUjian((int)$id_jadwal, $this->tp->id_tp, $this->smt->id_smt);
        $ok = $u && ($kelola ? $this->bolehKelola($u) : $this->bolehLihat($u));
        if (!$ok) {
            $msg = 'Data tidak ditemukan atau Anda tidak berhak mengaksesnya.';
            if ($asJson) {
                $this->json(['status' => false, 'msg' => $msg], 403);
            } else {
                show_error($msg, 403, 'Akses Terlarang');
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

    /** Nama chip kesiapan dalam bahasa manusia. */
    private function labelKurang(array $kurang)
    {
        $map = [
            'soal'     => 'soal belum ada',
            'peserta'  => 'peserta belum dibagi',
            'ruang'    => 'ruang belum dipakai semua',
            'pengawas' => 'pengawas belum diatur',
            'token'    => 'token belum diisi'
        ];
        $out = [];
        foreach ($kurang as $k) {
            if (isset($map[$k])) {
                $out[] = $map[$k];
            }
        }
        return implode(', ', $out);
    }

    private function sedangBerlangsung($u)
    {
        return (int)$u->status === 1 && substr((string)$u->tgl_mulai, 0, 10) === date('Y-m-d');
    }

    /* ==================================================================
     * Halaman utama — Daftar Ujian
     * ================================================================ */

    public function index()
    {
        $ujian = $this->us->listUjian($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter());

        $hariIni = date('Y-m-d');
        $data = [
            'judul'       => 'Daftar Ujian',
            'subjudul'    => 'Ujian Sekolah',
            'ujian'       => $ujian,
            'token_aktif' => $this->us->tokenAktif($hariIni, $this->tp->id_tp, $this->smt->id_smt),
            'notif'       => $this->isAdmin ? $this->us->notifGuru($this->tp->id_tp, $this->smt->id_smt) : [],
            'hari_ini'    => $hariIni,
            'ada_opsi'    => count($this->opsi['pairs']) > 0
        ];
        $this->render('cbt/ujiansekolah/daftar', $data);
    }

    /* ==================================================================
     * Wizard 3 langkah
     * ================================================================ */

    public function baru()
    {
        $this->wizard(null);
    }

    public function edit($id_jadwal = 0)
    {
        $u = $this->ujianAkses($id_jadwal, true, false);
        if (!$u) {
            return;
        }
        $this->wizard($u);
    }

    private function wizard($u)
    {
        if (count($this->opsi['pairs']) === 0 && !$this->isAdmin) {
            show_error('Anda belum diberi jadwal mengajar (mapel dan kelas) pada tahun pelajaran dan semester aktif, '
                . 'sehingga belum bisa membuat ujian. <a href="' . base_url('ujiansekolah') . '">Kembali ke Daftar Ujian</a>',
                403, 'Belum ada mapel/kelas');
        }

        $jenis = $this->us->jenisList();
        $jenisTerakhir = null;
        if (!$u) {
            $last = $this->us->listUjian($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter());
            if (count($last) > 0) {
                $jenisTerakhir = (int)$last[count($last) - 1]->id_jenis;
            }
        }

        $langkah = (int)$this->input->get('langkah');
        if ($langkah < 1 || $langkah > 3) {
            $langkah = 1;
        }

        $data = [
            'judul'         => $u ? 'Ubah Ujian' : 'Buat Ujian Baru',
            'subjudul'      => $u ? 'Ubah pengaturan ujian' : 'Tiga langkah, selesai',
            'u'             => $u,
            'langkah'       => $langkah,
            'jenis'         => $jenis,
            'jenis_akhir'   => $jenisTerakhir,
            'banks'         => $this->us->bankPilihan($this->tp->id_tp, $this->smt->id_smt, null, $this->idGuruFilter()),
            'guru_list'     => $this->us->guruList(),
            'ruang'         => $this->us->ruangList(),
            'sesi'          => $this->us->sesiSatu(),
            'kapasitas'     => $this->us->kapasitasTotal(),
            'meta'          => $u ? $this->db->get_where('cbt_jadwal_meta', ['id_jadwal' => (int)$u->id_jadwal])->row() : null,
            'pengawas'      => $u ? $this->us->pengawasMap($u->id_jadwal) : [],
            'berlangsung'   => $u ? $this->sedangBerlangsung($u) : false
        ];
        $this->render('cbt/ujiansekolah/tambah', $data);
    }

    /** AJAX: token unik 6 karakter untuk tanggal tertentu. */
    public function acaktoken()
    {
        $tanggal = trim((string)$this->input->post('tanggal', true));
        if (!$this->validTanggal($tanggal)) {
            $tanggal = date('Y-m-d');
        }
        $exclude = (int)$this->input->post('id_jadwal');
        if ($exclude) {
            $u = $this->us->getUjian($exclude, $this->tp->id_tp, $this->smt->id_smt);
            if (!$u || !$this->bolehKelola($u)) {
                $exclude = 0;
            }
        }
        return $this->json([
            'status' => true,
            'token'  => $this->us->tokenUnik($tanggal, $exclude)
        ]);
    }

    /** AJAX: ujian lain pada tanggal yang sama + pengawas yang bisa disalin. */
    public function tanggal()
    {
        $tanggal = trim((string)$this->input->get('tanggal', true));
        if (!$this->validTanggal($tanggal)) {
            return $this->json(['status' => false, 'msg' => 'Tanggal tidak valid.']);
        }
        $exclude = (int)$this->input->get('id_jadwal');
        if ($exclude) {
            $u = $this->us->getUjian($exclude, $this->tp->id_tp, $this->smt->id_smt);
            if (!$u || !$this->bolehKelola($u)) {
                $exclude = 0;
            }
        }

        $lain = [];
        foreach ($this->us->ujianPadaTanggal($tanggal, $exclude, $this->tp->id_tp, $this->smt->id_smt) as $r) {
            if (!$this->isAdmin && !$this->bolehLihat($r)) {
                continue;
            }
            $lain[] = [
                'id_jadwal'   => (int)$r->id_jadwal,
                'judul'       => trim(($r->kode_jenis ? $r->kode_jenis : '') . ' ' . (string)$r->nama_mapel),
                'kelas'       => $r->label_kelas,
                'durasi'      => (int)$r->durasi_ujian,
                'pengawas'    => array_map('intval', array_values($r->pengawas))
            ];
        }

        return $this->json([
            'status'    => true,
            'tanggal'   => $tanggal,
            'lain'      => $lain,
            'pengawas'  => (object)$this->us->pengawasDariTanggal($tanggal, $this->tp->id_tp, $this->smt->id_smt, $exclude),
            'token_lain' => $this->us->tokenDipakai($tanggal, $exclude)
        ]);
    }

    /** AJAX: kumpulan soal untuk satu mapel (kartu pilihan langkah 2). */
    public function kumpulansoal()
    {
        $id_mapel = (int)$this->input->get('mapel');
        if ($id_mapel <= 0) {
            return $this->json(['status' => false, 'msg' => 'Pilih mata pelajaran dulu.']);
        }
        if (!$this->isAdmin && !isset($this->opsi['pairs'][$id_mapel])) {
            return $this->json(['status' => false, 'msg' => 'Anda tidak mengajar mata pelajaran ini.'], 403);
        }
        $banks = [];
        foreach ($this->us->bankPilihan($this->tp->id_tp, $this->smt->id_smt, $id_mapel, $this->idGuruFilter()) as $b) {
            $banks[] = [
                'id_bank'      => (int)$b->id_bank,
                'nama'         => $b->bank_nama !== '' && $b->bank_nama !== null ? $b->bank_nama : $b->bank_kode,
                'kode'         => $b->bank_kode,
                'mapel'        => $b->nama_mapel,
                'kelas'        => $b->label_kelas,
                'jml'          => (int)$b->jml,
                'jml_pg'       => (int)$b->jml_pg,
                'jml_esai'     => (int)$b->jml_esai,
                'kkm'          => (int)$b->kkm,
                'pakai'        => $b->pakai_label,
                'jumlah_siswa' => (int)$b->jumlah_siswa
            ];
        }
        return $this->json(['status' => true, 'banks' => $banks]);
    }

    /* ==================================================================
     * Simpan ujian (POST)
     * ================================================================ */

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }

        $id = (int)$this->input->post('id_jadwal');
        $id_mapel = (int)$this->input->post('id_mapel');
        $id_jenis = (int)$this->input->post('id_jenis');
        $tanggal = trim((string)$this->input->post('tanggal', true));
        $durasi = (int)$this->input->post('durasi_ujian');
        $id_bank = (int)$this->input->post('id_bank');
        $token_ujian = strtoupper(trim((string)$this->input->post('token_ujian', true)));
        $gunakan_token = (int)$this->input->post('gunakan_token') === 1;
        $acak_soal = (int)$this->input->post('acak_soal') === 1 ? 1 : 0;
        $acak_opsi = (int)$this->input->post('acak_opsi') === 1 ? 1 : 0;
        $hasil_tampil = (int)$this->input->post('hasil_tampil') === 1 ? 1 : 0;
        $aktif = (int)$this->input->post('aktif') === 1 ? 1 : 0;
        $catatan = trim((string)$this->input->post('catatan', true));

        /* --- ujian yang sedang berlangsung: opsi inti dikunci --------- */
        $lama = null;
        if ($id) {
            $lama = $this->ujianAkses($id, true);
            if (!$lama) {
                return;
            }
        }
        $terkunci = $lama && $this->sedangBerlangsung($lama);

        /* --- validasi mapel ------------------------------------------ */
        if ($id_mapel <= 0 || !isset($this->opsi['mapel'][$id_mapel])) {
            return $this->json(['status' => false, 'msg' => 'Pilih mata pelajaran dulu.', 'fokus' => 'mapel']);
        }
        if (!$this->isAdmin && !isset($this->opsi['pairs'][$id_mapel])) {
            return $this->json(['status' => false, 'msg' => 'Anda tidak mengajar mata pelajaran ini.'], 403);
        }

        /* --- validasi tanggal & durasi ------------------------------- */
        if (!$this->validTanggal($tanggal)) {
            return $this->json(['status' => false, 'msg' => 'Tanggal ujian belum diisi dengan benar.', 'fokus' => 'tanggal']);
        }
        if ($durasi < Ujiansekolah_model::DURASI_MIN || $durasi > Ujiansekolah_model::DURASI_MAX) {
            return $this->json(['status' => false, 'msg' => 'Durasi sebaiknya ' . Ujiansekolah_model::DURASI_MIN
                . '–' . Ujiansekolah_model::DURASI_MAX . ' menit.', 'fokus' => 'durasi']);
        }

        $jenis = $this->db->get_where('cbt_jenis', ['id_jenis' => $id_jenis])->row();
        if (!$jenis) {
            return $this->json(['status' => false, 'msg' => 'Jenis ujian tidak valid.', 'fokus' => 'jenis']);
        }

        if ($terkunci) {
            // Nilai lama dipertahankan; hanya token, pengawas, dan status yang boleh berubah.
            $id_bank = (int)$lama->id_bank;
            $id_jenis = (int)$lama->id_jenis;
            $tanggal = substr((string)$lama->tgl_mulai, 0, 10);
            $durasi = (int)$lama->durasi_ujian;
            $acak_soal = (int)$lama->acak_soal;
            $acak_opsi = (int)$lama->acak_opsi;
            $hasil_tampil = (int)$lama->hasil_tampil;
        }

        /* --- validasi kumpulan soal ---------------------------------- */
        $kelas = [];
        $bank = null;
        if ($id_bank > 0) {
            $bank = $this->db->get_where('cbt_bank_soal', ['id_bank' => $id_bank])->row();
            if (!$bank) {
                return $this->json(['status' => false, 'msg' => 'Kumpulan soal tidak ditemukan.', 'fokus' => 'bank']);
            }
            if ((int)$bank->bank_mapel_id !== $id_mapel) {
                $namaBank = $this->opsi['mapel'][(int)$bank->bank_mapel_id] ?? 'mapel lain';
                return $this->json([
                    'status' => false,
                    'msg'    => 'Kumpulan soal ini untuk ' . $namaBank . ', bukan '
                        . $this->opsi['mapel'][$id_mapel] . '.',
                    'fokus'  => 'bank'
                ]);
            }
            $kelas = $this->us->kelasUjian((object)[
                'id_bank' => $id_bank, 'bank_kelas' => $bank->bank_kelas, 'meta_kelas' => null
            ]);
            if (count($kelas) === 0) {
                return $this->json([
                    'status' => false,
                    'msg'    => 'Kumpulan soal ini belum punya kelas. Lengkapi dulu di menu Kumpulan Soal.',
                    'fokus'  => 'bank'
                ]);
            }
            if (!$this->isAdmin && !$this->us->guruBoleh($this->guru->id_guru, $this->tp->id_tp,
                    $this->smt->id_smt, $id_mapel, $kelas)) {
                return $this->json(['status' => false, 'msg' => 'Anda tidak mengajar kelas pada kumpulan soal ini.'], 403);
            }

            $pakai = $this->us->jadwalPakaiBank($id_bank, $id);
            if ($pakai) {
                return $this->json([
                    'status' => false,
                    'msg'    => 'Ujian untuk kumpulan soal ini sudah ada di tanggal '
                        . date('d M Y', strtotime($pakai->tgl_mulai))
                        . '. Satu kumpulan soal dipakai untuk satu ujian.',
                    'fokus'  => 'bank'
                ]);
            }
        } elseif ($aktif) {
            return $this->json([
                'status' => false,
                'msg'    => 'Pilih kumpulan soal dulu. Ujian tanpa soal hanya bisa disimpan sebagai draf.',
                'fokus'  => 'bank'
            ]);
        } else {
            // Draf tanpa kumpulan soal: kelas diambil dari input (bila ada).
            $kelasInput = $this->input->post('kelas');
            if (is_array($kelasInput)) {
                foreach ($kelasInput as $k) {
                    if ((int)$k > 0) {
                        $kelas[] = (int)$k;
                    }
                }
            }
            if (count($kelas) === 0) {
                $kelas = isset($this->opsi['pairs'][$id_mapel]) ? $this->opsi['pairs'][$id_mapel] : [];
            }
        }

        /* --- validasi bentrok kelas pada tanggal yang sama ----------- */
        $bentrok = $this->kelasBentrok($tanggal, $kelas, $id);
        if ($bentrok !== null) {
            return $this->json(['status' => false, 'msg' => $bentrok, 'fokus' => 'tanggal']);
        }

        /* --- validasi token ----------------------------------------- */
        if ($gunakan_token) {
            if ($token_ujian === '') {
                return $this->json([
                    'status' => false,
                    'msg'    => 'Token belum diisi. Isi token atau matikan pilihan token.',
                    'fokus'  => 'token'
                ]);
            }
            if (!preg_match('/^[A-Z0-9]{6}$/', $token_ujian)) {
                return $this->json([
                    'status' => false,
                    'msg'    => 'Token harus 6 karakter huruf dan angka.',
                    'fokus'  => 'token'
                ]);
            }
            foreach ($this->us->tokenDipakai($tanggal, $id) as $t) {
                if (strtoupper(trim((string)$t->token_ujian)) === $token_ujian) {
                    return $this->json([
                        'status' => false,
                        'msg'    => 'Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang.',
                        'fokus'  => 'token'
                    ]);
                }
            }
        } else {
            $token_ujian = null;
        }

        /* --- susun data kolom --------------------------------------- */
        $data = [
            'id_tp'        => (int)$this->tp->id_tp,
            'id_smt'       => (int)$this->smt->id_smt,
            'id_bank'      => $id_bank > 0 ? $id_bank : null,
            'id_jenis'     => $id_jenis,
            'tgl_mulai'    => $tanggal,
            'tgl_selesai'  => $tanggal,
            'durasi_ujian' => $durasi,
            'pengawas'     => null,
            'acak_soal'    => $acak_soal,
            'acak_opsi'    => $acak_opsi,
            'hasil_tampil' => $hasil_tampil,
            'token'        => $gunakan_token ? 1 : 0,
            'status'       => $aktif,
            'token_ujian'  => $token_ujian,
            'jam_ke'       => 1,
            'jarak'        => 30
        ];

        if (!$id) {
            $data['ulang'] = 0;
            $data['reset_login'] = 0;
            $data['rekap'] = 0;
            $data['created_by'] = $this->isAdmin ? null : (int)$this->guru->id_guru;
            $data['time_create'] = date('Y-m-d H:i:s');
        } else {
            $data['created_by'] = $lama->created_by !== null ? (int)$lama->created_by : null;
        }

        $this->db->trans_start();

        $id_jadwal = $this->us->simpanUjian($data, $id ?: null);
        if (!$id_jadwal) {
            $this->db->trans_complete();
            return $this->json(['status' => false, 'msg' => 'Gagal menyimpan ujian (kesalahan database).']);
        }

        $this->us->simpanMeta($id_jadwal, $id_mapel, $kelas, $catatan);

        /* --- pengawas ----------------------------------------------- */
        $pengawas = $this->input->post('pengawas');
        $perRuang = [];
        if (is_array($pengawas)) {
            foreach ($pengawas as $id_ruang => $id_guru) {
                if ((int)$id_ruang > 0 && (int)$id_guru > 0) {
                    $perRuang[(int)$id_ruang] = (int)$id_guru;
                }
            }
        }

        if (!$this->isAdmin) {
            // Guru tidak mengatur pengawas: salin/serahkan ke admin (PRD bagian 8).
            if (!$id) {
                $perRuang = $this->us->pengawasDariTanggal(
                    $tanggal, $this->tp->id_tp, $this->smt->id_smt, $id_jadwal
                );
            } else {
                $perRuang = $this->us->pengawasMap($id_jadwal);
            }
        } elseif (count($perRuang) === 0) {
            // Isi otomatis dari ujian lain di tanggal yang sama (bila ada).
            $perRuang = $this->us->pengawasDariTanggal(
                $tanggal, $this->tp->id_tp, $this->smt->id_smt, $id_jadwal
            );
        }

        $sesi = $this->us->sesiSatu();
        $id_sesi = $sesi ? (int)$sesi->id_sesi : 1;
        if (count($perRuang) > 0) {
            $this->us->simpanPengawas($id_jadwal, $this->tp->id_tp, $this->smt->id_smt, $perRuang, $id_sesi);
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return $this->json(['status' => false, 'msg' => 'Gagal menyimpan ujian (kesalahan database).']);
        }

        /* --- catatan hasil simpan ----------------------------------- */
        $u = $this->us->getUjian($id_jadwal, $this->tp->id_tp, $this->smt->id_smt);
        $kes = $this->us->kesiapan($u, $this->tp->id_tp, $this->smt->id_smt);
        $msg = $aktif ? 'Ujian disimpan dan diaktifkan.' : 'Ujian disimpan sebagai draf.';

        if ($id_bank > 0 && (int)$kes['jumlah_soal'] === 0) {
            $msg .= ' Kumpulan soal ini masih kosong. Ujian bisa disimpan, tapi belum bisa dijalankan.';
        }
        if ($aktif && !$kes['siap']) {
            $msg .= ' Catatan: ' . $this->labelKurang($kes['kurang']) . '.';
        }
        if (count($perRuang) === 0 && !in_array('pengawas', $kes['kurang'], true)) {
            $msg .= ' Pengawas belum diatur.';
        }

        return $this->json([
            'status'    => true,
            'id'        => (int)$id_jadwal,
            'msg'       => $msg,
            'siap'      => $kes['siap'],
            'kurang'    => $this->labelKurang($kes['kurang']),
            'redirect'  => base_url('ujiansekolah')
        ]);
    }

    /** Return pesan error bila ada kelas yang bentrok, atau null. */
    private function kelasBentrok($tanggal, array $kelas, $exclude_id = 0)
    {
        if (count($kelas) === 0) {
            return null;
        }
        foreach ($this->us->ujianPadaTanggal($tanggal, $exclude_id, $this->tp->id_tp, $this->smt->id_smt) as $lain) {
            $irisan = array_intersect($kelas, $lain->kelas);
            if (count($irisan) === 0) {
                continue;
            }
            $namaKelas = $this->us->labelKelas(array_values($irisan));
            $namaUjian = trim(($lain->kode_jenis ? $lain->kode_jenis : '') . ' ' . (string)$lain->nama_mapel);
            return 'Kelas ' . $namaKelas . ' sudah punya ujian ' . $namaUjian . ' di tanggal ini pada jam yang sama.';
        }
        return null;
    }

    /* ==================================================================
     * Aksi ujian: buka / tutup / hapus
     * ================================================================ */

    public function aktif()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->ujianAkses((int)$this->input->post('id_jadwal'), true);
        if (!$u) {
            return;
        }
        $aktif = (int)$this->input->post('aktif') === 1;

        if ($aktif) {
            $kes = $this->us->kesiapan($u, $this->tp->id_tp, $this->smt->id_smt);
            if (!$kes['siap']) {
                return $this->json([
                    'status' => false,
                    'msg'    => 'Ujian belum siap: ' . $this->labelKurang($kes['kurang'])
                        . '. Lengkapi persiapan terlebih dahulu.'
                ]);
            }
        } else {
            if (!$this->isAdmin && !$this->bolehKelola($u)) {
                return $this->json(['status' => false, 'msg' => 'Anda tidak berhak menutup ujian ini.'], 403);
            }
        }

        $this->us->setStatus($u->id_jadwal, $aktif);
        return $this->json([
            'status' => true,
            'msg'    => $aktif ? 'Ujian dibuka. Siswa bisa masuk saat Sesi 1 dimulai.' : 'Ujian ditutup.'
        ]);
    }

    public function hapus()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->ujianAkses((int)$this->input->post('id_jadwal'), true);
        if (!$u) {
            return;
        }
        if ($this->us->adaNilai($u->id_jadwal)) {
            return $this->json([
                'status' => false,
                'msg'    => 'Ujian ini sudah memiliki nilai siswa dan tidak bisa dihapus. Tutup saja ujiannya.'
            ]);
        }
        $ok = $this->us->hapusUjian($u->id_jadwal);
        return $this->json([
            'status' => (bool)$ok,
            'msg'    => $ok ? 'Ujian dihapus.' : 'Gagal menghapus ujian.'
        ]);
    }

    /* ==================================================================
     * PENGATURAN UJIAN — 1 halaman, 5 tab (PRD 4.3 / 5.3)
     * ================================================================ */

    private $tabPengaturan = ['peserta', 'sesi', 'pengawas', 'jenis', 'token'];

    public function pengaturan()
    {
        $this->hanyaAdmin();

        $tab = strtolower((string)$this->input->get('tab', true));
        if (!in_array($tab, $this->tabPengaturan, true)) {
            $tab = 'peserta';
        }

        $this->us->pastikanSesiDanRuang();
        $sesi = $this->us->sesiList();
        $sesi1 = count($sesi) > 0 ? $sesi[0] : null;

        $data = [
            'judul'        => 'Pengaturan Ujian',
            'subjudul'     => 'Semua pengaturan teknis ujian dalam satu halaman',
            'tab'          => $tab,
            'tabs'         => [
                'peserta'  => ['Peserta & Ruang', 'fas fa-users'],
                'sesi'     => ['Sesi & Jam', 'far fa-clock'],
                'pengawas' => ['Pengawas', 'fas fa-user-shield'],
                'jenis'    => ['Jenis Ujian', 'fas fa-project-diagram'],
                'token'    => ['Token', 'fas fa-key']
            ],
            'd'            => $this->us->pembagianData($this->tp->id_tp, $this->smt->id_smt),
            'ruang'        => $this->us->ruangList(),
            'kapasitas'    => $this->us->kapasitasTotal(),
            'sesi'         => $sesi,
            'sesi1'        => $sesi1,
            'matrix'       => $this->us->pengawasMatrix($this->tp->id_tp, $this->smt->id_smt),
            'guru_list'    => $this->us->guruList(),
            'jenis'        => $this->us->jenisDenganPemakaian(),
            'ujian'        => $this->us->listUjian($this->tp->id_tp, $this->smt->id_smt, null),
            'token_global' => $this->us->globalToken()
        ];
        $this->render('cbt/ujiansekolah/pengaturan', $data);
    }

    /** Ajakan lama: /pembagian → tab Peserta & Ruang. */
    public function pembagian()
    {
        $this->hanyaAdmin();
        redirect('ujiansekolah/pengaturan?tab=peserta');
    }

    /** Ajakan lama: /ruangsesi → tab Peserta & Ruang. */
    public function ruangsesi()
    {
        $this->hanyaAdmin();
        redirect('ujiansekolah/pengaturan?tab=peserta');
    }

    public function bagiproses()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        // Fitur ini menimpa pembagian semester berjalan; konfirmasi dilakukan di UI.
        $semuaRuang = (int)$this->input->post('semua_ruang') === 0 ? false : true;
        $hasil = $this->us->bagiOtomatis($this->tp->id_tp, $this->smt->id_smt, $semuaRuang);
        $hasil['redirect'] = base_url('ujiansekolah/pengaturan?tab=peserta');
        return $this->json($hasil);
    }

    public function simpanruang()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $id_ruang = (int)$this->input->post('id_ruang');
        $kapasitas = (int)$this->input->post('kapasitas');
        if ($id_ruang <= 0) {
            return $this->json(['status' => false, 'msg' => 'Ruang tidak valid.']);
        }
        if (!$this->db->get_where('cbt_ruang', ['id_ruang' => $id_ruang])->row()) {
            return $this->json(['status' => false, 'msg' => 'Ruang tidak ditemukan.']);
        }
        if (!$this->us->setKapasitas($id_ruang, $kapasitas)) {
            return $this->json(['status' => false, 'msg' => 'Kapasitas harus antara 1 dan 500.']);
        }
        return $this->json(['status' => true, 'msg' => 'Kapasitas ruang disimpan.']);
    }

    /* ---- tab Sesi & Jam --------------------------------------------- */

    public function sesisimpan()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $r = $this->us->simpanSesi(
            (int)$this->input->post('id_sesi'),
            $this->input->post('nama_sesi', true),
            $this->input->post('kode_sesi', true),
            $this->input->post('waktu_mulai', true),
            $this->input->post('waktu_akhir', true),
            (int)$this->input->post('aktif') === 1
        );
        return $this->json(['status' => $r['ok'], 'msg' => $r['msg']]);
    }

    public function jamsimpan()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $jam = $this->input->post('jam');
        if (!is_array($jam)) {
            return $this->json(['status' => false, 'msg' => 'Tidak ada data yang dikirim.']);
        }
        $n = 0;
        foreach ($jam as $id_jadwal => $nilai) {
            $u = $this->us->getUjian((int)$id_jadwal, $this->tp->id_tp, $this->smt->id_smt);
            if (!$u) {
                continue;
            }
            if ($this->us->setJamKe($u->id_jadwal, (int)$nilai)) {
                $n++;
            }
        }
        return $this->json(['status' => true, 'msg' => 'Urutan jam disimpan untuk ' . $n . ' ujian.']);
    }

    /* ---- tab Pengawas ---------------------------------------------- */

    public function pengawassimpan()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $tanggal = trim((string)$this->input->post('tanggal', true));
        if (!$this->validTanggal($tanggal)) {
            return $this->json(['status' => false, 'msg' => 'Tanggal tidak valid.']);
        }
        $input = $this->input->post('pengawas');
        $perRuang = [];
        if (is_array($input)) {
            foreach ($input as $id_ruang => $id_guru) {
                if ((int)$id_ruang > 0 && (int)$id_guru > 0) {
                    $perRuang[(int)$id_ruang] = (int)$id_guru;
                }
            }
        }
        $sesi = $this->us->sesiSatu();
        $id_sesi = $sesi ? (int)$sesi->id_sesi : 1;

        $n = $this->us->simpanPengawasTanggal($tanggal, $this->tp->id_tp, $this->smt->id_smt, $perRuang, $id_sesi);
        if ($n === 0) {
            return $this->json(['status' => false, 'msg' => 'Tidak ada ujian pada tanggal tersebut.']);
        }
        return $this->json([
            'status' => true,
            'msg'    => 'Pengawas disimpan untuk ' . $n . ' ujian pada '
                . date('d M Y', strtotime($tanggal)) . '.'
        ]);
    }

    /* ---- tab Jenis Ujian ------------------------------------------- */

    public function jenissimpan()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $r = $this->us->simpanJenis(
            (int)$this->input->post('id_jenis'),
            $this->input->post('kode_jenis', true),
            $this->input->post('nama_jenis', true)
        );
        return $this->json(['status' => $r['ok'], 'msg' => $r['msg'], 'id' => $r['id'] ?? 0]);
    }

    public function jenishapus()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $r = $this->us->hapusJenis((int)$this->input->post('id_jenis'));
        return $this->json(['status' => $r['ok'], 'msg' => $r['msg']]);
    }

    /* ---- tab Token ------------------------------------------------- */

    public function tokenujian()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->us->getUjian((int)$this->input->post('id_jadwal'), $this->tp->id_tp, $this->smt->id_smt);
        if (!$u) {
            return $this->json(['status' => false, 'msg' => 'Ujian tidak ditemukan.']);
        }
        $r = $this->us->setTokenUjian($u->id_jadwal, $this->input->post('token', true));
        return $this->json(['status' => $r['ok'], 'msg' => $r['msg'], 'token' => $r['token'] ?? '']);
    }

    public function tokenacak()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->us->getUjian((int)$this->input->post('id_jadwal'), $this->tp->id_tp, $this->smt->id_smt);
        if (!$u) {
            return $this->json(['status' => false, 'msg' => 'Ujian tidak ditemukan.']);
        }
        $r = $this->us->acakTokenUjian($u->id_jadwal);
        return $this->json(['status' => $r['ok'], 'msg' => $r['msg'], 'token' => $r['token'] ?? '']);
    }

    public function tokensimpan()
    {
        $this->hanyaAdmin();
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $r = $this->us->simpanTokenGlobal(
            $this->input->post('token', true),
            (int)$this->input->post('auto'),
            (int)$this->input->post('jarak')
        );
        return $this->json(['status' => $r['ok'], 'msg' => $r['msg']]);
    }

    /* ==================================================================
     * HASIL UJIAN — 1 halaman, 4 tab (PRD 4.4 / 5.4)
     * ================================================================ */

    public function hasil()
    {
        $tabs = [
            'nilai'    => ['Nilai Ujian', 'fas fa-file-alt', 'cbtnilai'],
            'analisis' => ['Analisis Soal', 'fas fa-chart-line', 'cbtanalisis'],
            'rekap'    => ['Rekap Nilai', 'fas fa-trophy', 'cbtrekap'],
            'manual'   => ['Nilai Manual', 'fas fa-pen-square', 'nilaimanual']
        ];
        $tab = strtolower((string)$this->input->get('tab', true));
        if (!isset($tabs[$tab])) {
            $tab = 'nilai';
        }
        $data = [
            'judul'    => 'Hasil Ujian',
            'subjudul' => 'Nilai, analisis, rekap, dan nilai manual dalam satu halaman',
            'tab'      => $tab,
            'tabs'     => $tabs,
            'jumlah'   => count($this->us->listUjian($this->tp->id_tp, $this->smt->id_smt, $this->idGuruFilter()))
        ];
        $this->render('cbt/ujiansekolah/hasil', $data);
    }

    /* ==================================================================
     * FASE 4 — Pantau Ujian
     * ================================================================ */

    public function pantau($id_jadwal = 0)
    {
        $u = $this->ujianAkses($id_jadwal, false, false);
        if (!$u) {
            return;
        }
        $p = $this->us->pantauData($u, $this->tp->id_tp, $this->smt->id_smt);
        $u->label_kelas = $this->us->labelKelas($this->us->kelasUjian($u));
        $data = [
            'judul'    => 'Pantau Ujian',
            'subjudul' => 'Pantau pelaksanaan ujian',
            'u'        => $u,
            'siswa'    => $p['siswa'],
            'ringkas'  => $p['ringkas'],
            'kelola'   => $this->bolehKelola($u),
            'kesiapan' => $this->us->kesiapan($u, $this->tp->id_tp, $this->smt->id_smt)
        ];
        $this->render('cbt/ujiansekolah/pantau', $data);
    }

    public function pantauaksi()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['status' => false, 'msg' => 'Metode tidak valid.'], 405);
        }
        $u = $this->ujianAkses((int)$this->input->post('id_jadwal'), true);
        if (!$u) {
            return;
        }
        $aksi = (string)$this->input->post('aksi', true);
        $id_siswa = (int)$this->input->post('id_siswa');

        switch ($aksi) {
            case 'buka':
                $kes = $this->us->kesiapan($u, $this->tp->id_tp, $this->smt->id_smt);
                if (!$kes['siap']) {
                    return $this->json([
                        'status' => false,
                        'msg'    => 'Ujian belum siap: ' . $this->labelKurang($kes['kurang']) . '.'
                    ]);
                }
                $this->us->setStatus($u->id_jadwal, true);
                return $this->json(['status' => true, 'msg' => 'Ujian dibuka.']);

            case 'tutup':
                $this->us->setStatus($u->id_jadwal, false);
                return $this->json(['status' => true, 'msg' => 'Ujian ditutup.']);

            case 'reset':
                if ($id_siswa <= 0) {
                    return $this->json(['status' => false, 'msg' => 'Siswa tidak valid.']);
                }
                $this->us->resetIzin($u->id_jadwal, $id_siswa);
                return $this->json(['status' => true, 'msg' => 'Izin login siswa direset. Siswa bisa masuk kembali.']);

            case 'waktu':
                if ($id_siswa <= 0) {
                    return $this->json(['status' => false, 'msg' => 'Siswa tidak valid.']);
                }
                $menit = (int)$this->input->post('menit');
                if (!$this->us->tambahWaktu($u->id_jadwal, $id_siswa, $menit)) {
                    return $this->json(['status' => false, 'msg' => 'Gagal menambah waktu. Siswa belum memulai ujian.']);
                }
                return $this->json(['status' => true, 'msg' => 'Waktu siswa ditambah ' . $menit . ' menit.']);
        }
        return $this->json(['status' => false, 'msg' => 'Aksi tidak dikenal.']);
    }

    /* ==================================================================
     * Pemberitahuan untuk dashboard admin (dipanggil AJAX)
     * ================================================================ */

    public function notif()
    {
        if (!$this->isAdmin) {
            return $this->json(['status' => false, 'items' => [], 'jumlah' => 0]);
        }
        $items = [];
        foreach ($this->us->notifGuru($this->tp->id_tp, $this->smt->id_smt) as $r) {
            $items[] = [
                'id_jadwal' => (int)$r->id_jadwal,
                'judul'     => trim(($r->kode_jenis ? $r->kode_jenis : '') . ' ' . (string)$r->nama_mapel),
                'kelas'     => $r->label_kelas,
                'tanggal'   => $r->tgl_mulai,
                'oleh'      => $r->pembuat ? $r->pembuat : 'Guru',
                'waktu'     => $r->time_create,
                'url'       => base_url('ujiansekolah/pantau/' . (int)$r->id_jadwal)
            ];
        }
        return $this->json([
            'status' => true,
            'jumlah' => count($items),
            'items'  => array_slice($items, 0, 3),
            'semua'  => base_url('ujiansekolah')
        ]);
    }

    /* ==================================================================
     * Util
     * ================================================================ */

    private function hanyaAdmin()
    {
        if (!$this->isAdmin) {
            show_error('Halaman ini hanya untuk Administrator. <a href="' . base_url('ujiansekolah')
                . '">Kembali ke Daftar Ujian</a>', 403, 'Akses Terlarang');
        }
    }
}
