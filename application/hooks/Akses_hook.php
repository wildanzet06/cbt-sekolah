<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penjaga akses tambahan.
 *
 * Dijalankan otomatis SETELAH controller dibuat dan SEBELUM method-nya jalan
 * (hook "post_controller_constructor"). Tugasnya:
 *   1. Melarang akun SISWA membuka halaman admin/guru lewat alamat langsung.
 *   2. Membatasi aksi sensitif di User Management hanya untuk ADMIN.
 *
 * Jika ada halaman sah yang ikut terblokir, lihat application/logs/ :
 * setiap penolakan dicatat dengan teks "AKSES DITOLAK".
 */
class Akses_hook {

    // Controller yang TIDAK boleh dibuka siswa (nama sesuai alamat, huruf kecil)
    private $dilarang_siswa = array(
        // Data umum
        'datatahun', 'datamapel', 'datajurusan', 'datasiswa', 'datakelas',
        'dataekstra', 'dataguru', 'dataalumni',
        // E-learning (sisi admin/guru)
        'kelasjadwal', 'kelasmateri', 'kelasmaterijadwal', 'kelasstatus',
        'kelasabsensiharian', 'kelasabsensibulanan', 'kelasnilai',
        // Ujian (sisi admin/guru)
        'cbtjenis', 'cbtsesi', 'cbtruang', 'cbtsesisiswa', 'cbtnomorpeserta',
        'cbtbanksoal', 'cbtjadwal', 'cbtalokasi', 'cbtpengawas', 'cbttoken',
        'cbtcetak', 'cbtstatus', 'cbtnilai', 'cbtanalisis', 'cbtrekap',
        // Rapor
        'rapor', 'bukurapor',
        // Pengaturan dan akun
        'settings', 'useradmin', 'userguru', 'usersiswa', 'dbmanager', 'dbclear',
        // Keuangan sisi admin
        'tagihan',
        // Poin pelanggaran (sisi admin/guru)
        'pelanggaran',
        // Bacaan/stimulus soal (sisi admin/guru)
        'bacaan'
    );

    // Pengecualian: method yang tetap boleh diakses siswa
    private $dikecualikan_siswa = array(
        'usersiswa/change_password'   // mengganti password sendiri
    );

    // Aksi di User Management yang hanya boleh dilakukan admin
    private $aksi_admin = array(
        'index', 'data', 'list', 'activate', 'deactivate',
        'aktifkansemua', 'nonaktifkansemua', 'delete', 'reset_login'
    );
    private $khusus_admin = array('usersiswa', 'userguru', 'useradmin', 'datatahun', 'kas', 'pembayaran');

    /**
     * Controller yang SELURUH method-nya hanya untuk admin.
     * Datasiswa: tambah/ubah/hapus siswa + ubah username/password siswa.
     * Bukuinduk: buku induk siswa (halaman admin, tidak ada di menu guru).
     */
    private $khusus_admin_semua = array('datasiswa', 'bukuinduk', 'usersiswa');

    /**
     * Pengecualian dari aturan "controller khusus admin" di atas:
     * ganti password sendiri (usersiswa/change_password) tetap boleh untuk
     * semua pengguna yang login Ã¢â‚¬â€ nilainya diambil dari sesi, bukan dari input.
     */
    private $dikecualikan_admin_semua = array('usersiswa/change_password');

    /**
     * Controller dengan method tertentu yang hanya untuk admin,
     * walau controller-nya sendiri boleh diakses guru.
     */
    private $khusus_admin_method = array(
        // Arsip rapor: guru hanya boleh melihat; mengubah/memulihkan nilai = admin
        'bukurapor' => array('backupnilai', 'restorenilai', 'editnilairapor')
    );

    /**
     * Method ujian di controller Siswa yang menerima id_siswa dari luar.
     * Dipakai untuk memastikan siswa hanya mengakses datanya sendiri.
     */
    private $metode_ujian_siswa = array(
        'savejawaban', 'loadnomorsoal', 'gettimer', 'checktimer',
        'resettimer', 'selesaiujian', 'ulangiujian', 'validasisiswa',
        'soalsiswa', 'saveujian', 'savelogujian'
    );

    /** Method yang menghapus file dari POST 'src' / URI (cek path traversal). */
    private $metode_hapus_file = array('deletefile', 'delete_file', 'hapusfile', 'hapusbackup');

    /**
     * Aksi yang mengubah data. Boleh dipanggil lewat AJAX (header
     * X-Requested-With) atau POST, tetapi TIDAK lewat tautan/GET biasa Ã¢â‚¬â€
     * supaya tidak bisa dipicu dari halaman lain (CSRF) atau prefetch browser.
     */
    private $metode_mutasi = array(
        'delete', 'deletebank', 'deleteallbank', 'deletejadwal', 'deletealljadwal',
        'hapus', 'hapuspost', 'hapuskomentar', 'hapusbalasan', 'hapusrunningtext',
        'hapusmateri', 'hapustugas', 'hapussoal', 'hapusjadwal', 'hapuskelompok',
        'hapusrekap', 'hapustahun', 'deleteguru', 'forcedelete', 'hapusbackup',
        'copymateri', 'copytugas', 'copybanksoal',
        'deleteallmateri', 'deletealltugas',
        'aktifkan', 'nonaktifkan', 'aktifkanmateri', 'aktifkantugas',
        'activate', 'deactivate', 'reset_login', 'resetnomor', 'truncate',
        'backupnilai', 'backupdb', 'backupdata', 'restorenilai', 'bulkbackup',
        'generatealumni', 'verifikasi', 'readed',
        'savelogmateri', 'savelogtugas', 'kalkulasi'
    );

    public function periksa() {
        $CI =& get_instance();

        if (!isset($CI->ion_auth)) {
            $CI->load->library('ion_auth');
        }

        /*
         * Installer/updater database ditutup segera setelah aplikasi terpasang.
         * Pemeriksaan ini HARUS dijalankan sebelum cek login, karena kedua
         * controller itu memang tidak memerlukan login (jalur instalasi).
         */
        $kelas_awal = strtolower((string) $CI->router->fetch_class());
        if (in_array($kelas_awal, array('install', 'update')) && $CI->db->table_exists('users')) {
            if ((int) $CI->db->count_all('users') > 0) {
                $this->tolak_terpasang($CI);
            }
        }

        if (!$CI->ion_auth->logged_in()) {
            return; // belum login: biarkan controller yang menangani
        }

        // C-3: akun dengan password hasil reset admin wajib mengganti password.
        static $__punya_flag = null;
        if ($__punya_flag === null) {
            $__punya_flag = $CI->db->field_exists('must_change_password', 'users');
        }
        if ($__punya_flag) {
            $__u = $CI->ion_auth->user()->row();
            if ($__u && (int) $__u->must_change_password === 1) {
                $__uri = strtolower(trim((string) $CI->uri->uri_string(), '/'));
                $__boleh = in_array($__uri, array('akun/password', 'auth/logout', 'auth/cek_login'), true)
                    || strpos($__uri, 'akun/password') === 0;
                if (!$__boleh) {
                    if ($CI->input->is_ajax_request()) {
                        http_response_code(403);
                        header('Content-Type: application/json');
                        echo json_encode(array('status' => false, 'msg' => 'Ganti password terlebih dahulu.'));
                        exit;
                    }
                    redirect('akun/password');
                }
            }
        }

        $nama = array_unique(array(
            strtolower((string) $CI->router->fetch_class()),
            strtolower((string) $CI->uri->segment(1))
        ));
        $metode = strtolower((string) $CI->router->fetch_method());
        // B-5: halaman Data Siswa memakai script eksternal + nonce untuk
        // satu script menu sidebar. Script inline lain akan diblokir.
        if (in_array(strtolower((string) $CI->router->fetch_class()), array('datasiswa', 'dataguru', 'datakelas', 'datamapel', 'dataekstra', 'usersiswa', 'userguru', 'useradmin', 'datatahun', 'kas', 'pembayaran'), true)) {
            $__nonce = base64_encode(random_bytes(16));
            $CI->config->set_item('csp_nonce', $__nonce);
            header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-" . $__nonce . "'; script-src-attr 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; frame-src 'self' blob: data:; object-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'", false);
        }
        // B-5: dashboard siswa memakai script eksternal; beri CSP ketat.
        // Dashboard admin masih punya script inline, jadi tetap longgar.
        if (strtolower((string) $CI->router->fetch_class()) === 'dashboard') {
            if ($CI->ion_auth->in_group('siswa')) {
                header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; script-src-attr 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; frame-src 'self' blob: data:; object-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'", true);
            } else {
                header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' https://oss.maxcdn.com https://app.midtrans.com https://app.sandbox.midtrans.com https://static.cloudflareinsights.com; font-src 'self' data:; connect-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com https://api.midtrans.com https://api.sandbox.midtrans.com https://cloudflareinsights.com https://static.cloudflareinsights.com; media-src 'self' blob:; frame-src 'self' blob: data: https://app.midtrans.com https://app.sandbox.midtrans.com; object-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'", true);
            }
        }
        // B-5 pilot: halaman login memakai script eksternal, jadi bisa memakai
        // CSP script-src 'self' tanpa unsafe-inline. Header ini berlaku bersama
        // CSP global (irisan kebijakan), hanya untuk halaman login.
        if (strtolower((string) $CI->router->fetch_class()) === 'auth' && $metode === 'index') {
            header("Content-Security-Policy: script-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'", false);
        }

        // 0) Upload gambar soal: batasi nama file, ukuran, dan jenisnya.
        //    Tanpa ini, $_POST['name'] bisa dipakai untuk menimpa file sistem.
        if ($metode === 'uploadsoalimage'
            && strtolower((string) $CI->router->fetch_class()) === 'cbtbanksoal') {
            $this->batasi_upload_gambar_soal($CI);
        }

        $admin = $CI->ion_auth->is_admin();

        // File proteksi folder tidak boleh dihapus lewat endpoint apa pun.
        if (in_array($metode, array('deletefile', 'deletefoto'), true)) {
            $__src = urldecode((string) $CI->input->post('src'));
            if (stripos($__src, '.htaccess') !== false || stripos($__src, 'web.config') !== false) {
                $this->tolak($CI, 'mencoba menghapus file proteksi');
            }
        }
        $siswa = !$admin && $CI->ion_auth->in_group(array(3, 'siswa'));

        // 10) Guru hanya boleh mengelola bank soalnya sendiri.
        if (!$admin && $CI->ion_auth->in_group(array(2, 'guru'))) {
            $this->batasi_bank_soal($CI);
        }

        // Backup otomatis sekali sehari saat admin membuka halaman.
        if ($admin) {
            // Tandai aplikasi sudah terpasang, agar index.php tidak lagi
            // membuka koneksi database tambahan pada setiap request.
            $penanda = FCPATH . 'installer/TERPASANG';
            if (!is_file($penanda)) {
                @file_put_contents($penanda, date('c'));
            }

            if (!isset($CI->backup_model)) {
                $CI->load->model('Backup_model', 'backup_model');
            }
            $CI->backup_model->buat_otomatis();
        }

        foreach ($nama as $n) {
            if ($n === '') continue;

            // 1) Siswa dilarang membuka halaman admin/guru
            if ($siswa && in_array($n, $this->dilarang_siswa)
                && !in_array($n . '/' . $metode, $this->dikecualikan_siswa)) {
                $this->tolak($CI, 'siswa membuka ' . $n . '/' . $metode);
            }

            // 2) Aksi sensitif User Management hanya untuk admin
            if (!$admin && in_array($n, $this->khusus_admin) && in_array($metode, $this->aksi_admin)) {
                $this->tolak($CI, 'non-admin membuka ' . $n . '/' . $metode);
            }

            // 3) Controller yang seluruhnya hanya untuk admin
            if (!$admin && in_array($n, $this->khusus_admin_semua)
                && !in_array($n . '/' . $metode, $this->dikecualikan_admin_semua)) {
                $this->tolak($CI, 'non-admin membuka ' . $n . '/' . $metode);
            }

            // 4) Method tertentu yang hanya untuk admin
            if (!$admin && isset($this->khusus_admin_method[$n])
                && in_array($metode, $this->khusus_admin_method[$n])) {
                $this->tolak($CI, 'non-admin membuka ' . $n . '/' . $metode);
            }

            // 4b) Jabatan guru: tolak kelas yang sudah dipegang guru lain.
            //     Tampilan sudah menandainya, ini pengaman jika POST dipalsukan.
            if ($metode === 'savejabatan' && $n === 'dataguru') {
                $this->validasi_jabatan_guru($CI);
            }

            // 5) Siswa tidak boleh mengirim jawaban/timer atas nama siswa lain
            //    (endpoint ujian menerima id_siswa dari POST/URI).
            if ($siswa && $n === 'siswa' && in_array($metode, $this->metode_ujian_siswa)) {
                $id_login = $this->id_siswa_login($CI);
                if ($id_login) {
                    $diminta = $CI->input->post('siswa', true);
                    if ($diminta === null || $diminta === '') {
                        $diminta = $CI->uri->segment(3);
                    }
                    if ($diminta !== null && $diminta !== ''
                        && (string) (int) $diminta !== (string) (int) $id_login) {
                        $this->tolak($CI, 'siswa ' . $id_login . ' memakai id_siswa ' . $diminta . ' di ' . $metode);
                    }
                }
            }

            // 6) Cegah penghapusan file di luar folder uploads (path traversal).
            //    Beberapa controller memakai unlink(str_replace(base_url(),'', $_POST['src'])).
            if (in_array($metode, $this->metode_hapus_file)) {
                // Sebagian controller (mis. Guruview) mengambil 'src' dari GET,
                // jadi keduanya harus diperiksa.
                $src = (string) $CI->input->post('src', true);
                if ($src === '') {
                    $src = (string) $CI->input->get('src', true);
                }
                if ($src !== '' && $this->path_mencurigakan($src)) {
                    $this->tolak($CI, 'path traversal hapus file: ' . $src);
                }
                // Hanya file di dalam folder uploads yang boleh dihapus.
                if ($src !== '' && stripos($src, 'uploads/') === false) {
                    $this->tolak($CI, 'hapus file di luar uploads: ' . $src);
                }

                // Bank soal lebih ketat: hanya satu nama file di uploads/bank_soal.
                // Ini juga mencegah penghapusan uploads/.htaccess yang melindungi
                // folder upload dari eksekusi PHP.
                if ($metode === 'deletefile'
                    && strtolower((string) $CI->router->fetch_class()) === 'cbtbanksoal') {
                    $rel = str_replace('\\', '/', $src);
                    $rel = preg_replace('#^https?://[^/]+#i', '', $rel);
                    $rel = ltrim((string) $rel, '/');
                    if (!preg_match('#^uploads/bank_soal/[^/]+$#i', $rel)) {
                        $this->tolak($CI, 'hapus file bank soal di luar folder: ' . $src);
                    }
                    $basename = basename($rel);
                    if ($basename === '' || $basename[0] === '.'
                        || preg_match('/[^A-Za-z0-9._-]/', $basename)) {
                        $this->tolak($CI, 'nama file bank soal tidak valid: ' . $src);
                    }
                }
                // hapusBackup($src): nama file backup dari URI
                if ($metode === 'hapusbackup') {
                    $seg = (string) $CI->uri->segment(3);
                    if ($seg !== '' && !preg_match('/^[A-Za-z0-9._-]+$/', $seg)) {
                        $this->tolak($CI, 'nama backup tidak valid: ' . $seg);
                    }
                }
            }

            // 7) Aksi pengubah data tidak boleh dipicu tautan/GET biasa (CSRF).
            if (in_array($metode, $this->metode_mutasi)
                && $CI->input->method() !== 'post'
                && !$CI->input->is_ajax_request()) {
                $this->tolak($CI, 'aksi mutasi tanpa POST/AJAX: ' . $n . '/' . $metode);
            }

            // 9) Endpoint ujian: siswa hanya boleh menyentuh datanya sendiri.
            if ($siswa && $n === 'siswa') {
                $id_login = $this->id_siswa_login($CI);
                if ($id_login) {
                    // Kepemilikan lembar soal (id_soal_siswa) pada simpan/muat jawaban
                    if (in_array($metode, array('savejawaban', 'loadnomorsoal'))) {
                        $ids = $CI->input->post('id_soal_siswa');
                        if (is_scalar($ids) && $ids !== '') {
                            $row = $CI->db->select('id_siswa')
                                ->where('id_soal_siswa', (string) $ids)
                                ->get('cbt_soal_siswa')->row();
                            if ($row && (int) $row->id_siswa !== $id_login) {
                                $this->tolak($CI, 'id_soal_siswa milik siswa lain: ' . $ids);
                            }
                        }
                    }

                    // Kepemilikan timer (id_durasi) pada reset/ulangi ujian
                    if (in_array($metode, array('resettimer', 'ulangiujian'))) {
                        $idd = $CI->input->post('id_durasi');
                        if (!is_scalar($idd) || $idd === '') {
                            $idd = $CI->uri->segment(3);
                        }
                        if (is_scalar($idd) && $idd !== '') {
                            $row = $CI->db->select('id_siswa')
                                ->where('id_durasi', (string) $idd)
                                ->get('cbt_durasi_siswa')->row();
                            if ($row && (int) $row->id_siswa !== $id_login) {
                                $this->tolak($CI, 'id_durasi milik siswa lain: ' . $idd);
                            }
                        }
                    }

                    // leavecbt/{jadwal}/{id_siswa}
                    if ($metode === 'leavecbt') {
                        $seg = $CI->uri->segment(4);
                        if ($seg !== null && $seg !== '' && (string) (int) $seg !== (string) $id_login) {
                            $this->tolak($CI, 'leavecbt untuk siswa lain: ' . $seg);
                        }
                    }

                    // olahNilai/{id_siswa}/{id_jadwal}
                    if ($metode === 'olahnilai') {
                        $seg = $CI->uri->segment(3);
                        if ($seg !== null && $seg !== '' && (string) (int) $seg !== (string) $id_login) {
                            $this->tolak($CI, 'olahNilai untuk siswa lain: ' . $seg);
                        }
                    }

                    // Aksi destruktif massal: hanya admin
                    if ($metode === 'applyaction') {
                        $this->tolak($CI, 'applyAction hanya untuk admin');
                    }

                    // saveSoalSiswa: id_siswa bisa disisipkan di dalam JSON shuffle.
                    if ($metode === 'savesoalsiswa') {
                        $p = $CI->input->post('id_siswa');
                        if ($p === null) $p = $CI->input->post('siswa');
                        if ($p !== null && $p !== '' && (string) (int) $p !== (string) $id_login) {
                            $this->tolak($CI, 'saveSoalSiswa untuk siswa lain: ' . $p);
                        }
                        $shuffle = json_decode((string) $CI->input->post('shuffle', false), true);
                        if (is_array($shuffle)) {
                            foreach ($shuffle as $s) {
                                if (!is_array($s) || !isset($s['id_siswa'])) { continue; }
                                if ((string) (int) $s['id_siswa'] !== (string) $id_login) {
                                    $this->tolak($CI, 'saveSoalSiswa shuffle untuk siswa lain: ' . $s['id_siswa']);
                                }
                            }
                        }
                    }

                    // Catatan wali/mapel: detail dan tanda "sudah dibaca" harus
                    // masih menjadi milik siswa yang login.
                    if (in_array($metode, array('detailcatatan', 'readed'), true)) {
                        $table = strtolower((string) $CI->uri->segment(3));
                        $id_catatan = (int) $CI->uri->segment(4);
                        $tabel_db = array('mapel' => 'kelas_catatan_mapel', 'wali' => 'kelas_catatan_wali');
                        if (!isset($tabel_db[$table])) {
                            $this->tolak($CI, 'jenis catatan tidak dikenal: ' . $table);
                        }
                        if ($id_catatan <= 0) {
                            $this->tolak($CI, 'id catatan tidak valid');
                        }
                        $cat = $CI->db->select('id_siswa')
                            ->where('id_catatan', $id_catatan)
                            ->get($tabel_db[$table])->row();
                        if ($cat && (int) $cat->id_siswa !== $id_login) {
                            $this->tolak($CI, 'catatan milik siswa lain: ' . $id_catatan);
                        }
                    }
                }
            }
        }
    }

    /**
     * Validasi server-side untuk Edit Jabatan Guru.
     * Mencegah Admin menyimpan mapel/ekstra/wali kelas yang sudah dipegang guru
     * lain, walau dropdown di-browser dimanipulasi.
     */
    private function validasi_jabatan_guru($CI)
    {
        $id_guru = (int) $CI->input->post('id_guru');
        if ($id_guru <= 0) { return; }

        if (!isset($CI->dashboard)) { $CI->load->model('Dashboard_model', 'dashboard'); }
        $tp = $CI->dashboard->getTahunActive();
        $smt = $CI->dashboard->getSemesterActive();
        if (!$tp || !$smt) { return; }

        $rows = $CI->db->select('id_guru, id_jabatan, id_kelas, mapel_kelas, ekstra_kelas, nama_guru')
            ->where('id_tp', $tp->id_tp)
            ->where('id_smt', $smt->id_smt)
            ->where('id_guru !=', $id_guru)
            ->get('jabatan_guru')->result();

        $mapel_terisi = array();
        $ekstra_terisi = array();
        $wali_terisi = array();
        foreach ($rows as $row) {
            $mpl = @unserialize($row->mapel_kelas ?? '', array('allowed_classes' => array('stdClass')));
            $mpl = json_decode(json_encode($mpl));
            if (is_array($mpl)) {
                foreach ($mpl as $m) {
                    if (!isset($m->id_mapel)) { continue; }
                    foreach (($m->kelas_mapel ?? array()) as $k) {
                        $mapel_terisi[(int) $m->id_mapel][(string) $k->kelas] = $row->nama_guru;
                    }
                }
            }
            $eks = @unserialize($row->ekstra_kelas ?? '', array('allowed_classes' => array('stdClass')));
            $eks = json_decode(json_encode($eks));
            if (is_array($eks)) {
                foreach ($eks as $e) {
                    if (!isset($e->id_ekstra)) { continue; }
                    foreach (($e->kelas_ekstra ?? array()) as $k) {
                        $ekstra_terisi[(int) $e->id_ekstra][(string) $k->kelas] = $row->nama_guru;
                    }
                }
            }
            if ((int) $row->id_jabatan === 4) {
                $wali_terisi[(string) (int) $row->id_kelas] = $row->nama_guru;
            }
        }

        foreach ($_POST as $kunci => $nilai) {
            if (!is_array($nilai)) { continue; }
            if (preg_match('/^kelasmapel(\d+)$/', $kunci, $m)) {
                foreach ($nilai as $kelas) {
                    $kelas = (string) $kelas;
                    if (isset($mapel_terisi[(int) $m[1]][$kelas])) {
                        $this->tolak($CI, 'kelas mapel sudah diampu ' . $mapel_terisi[(int) $m[1]][$kelas]);
                    }
                }
            }
            if (preg_match('/^kelasekstra(\d+)$/', $kunci, $m)) {
                foreach ($nilai as $kelas) {
                    $kelas = (string) $kelas;
                    if (isset($ekstra_terisi[(int) $m[1]][$kelas])) {
                        $this->tolak($CI, 'kelas ekstra sudah diampu ' . $ekstra_terisi[(int) $m[1]][$kelas]);
                    }
                }
            }
        }

        $wali = $CI->input->post('kelas_wali');
        if ($wali !== null && $wali !== '' && isset($wali_terisi[(string) (int) $wali])) {
            $this->tolak($CI, 'kelas wali sudah diampu ' . $wali_terisi[(string) (int) $wali]);
        }
    }

    /**
     * Upload gambar soal hanya boleh menulis satu file di uploads/bank_soal.
     * Nama file dibatasi, ekstensi harus gambar, dan isinya benar-benar gambar.
     */
    private function batasi_upload_gambar_soal($CI)
    {
        $name = (string) $CI->input->post('name');
        if ($name === '' || $name !== basename($name)
            || preg_match('/[^A-Za-z0-9._-]/', $name)
            || strpos($name, '..') !== false
            || !preg_match('/\.(png|jpe?g|gif|webp|bmp)$/i', $name)) {
            $this->tolak($CI, 'nama gambar soal tidak valid: ' . $name);
        }

        $src = (string) $CI->input->post('src', false);
        if ($src === '' || strlen($src) > 12000000) {
            $this->tolak($CI, 'isi gambar soal kosong atau terlalu besar');
        }
        $gambar = base64_decode($src, true);
        if ($gambar === false || strlen($gambar) > 8000000) {
            $this->tolak($CI, 'isi gambar soal bukan base64 yang valid');
        }
        if (function_exists('getimagesizefromstring') && @getimagesizefromstring($gambar) === false) {
            $this->tolak($CI, 'isi gambar soal bukan file gambar');
        }
    }

    /** Path mengandung pola traversal / absolut (dipakai penjaga hapus file). */
    private function path_mencurigakan($path) {
        if (strpos($path, '..') !== false) {
            return true;
        }
        // path absolut Windows (C:\...) atau UNC (\\server)
        if (preg_match('#^[A-Za-z]:[\\\\/]#', $path) || preg_match('#^\\\\\\\\#', $path)) {
            return true;
        }
        return false;
    }
    private function id_siswa_login($CI) {
        static $cache = false;
        if ($cache !== false) {
            return $cache;
        }
        $user = $CI->ion_auth->user()->row();
        if (!$user) {
            return $cache = null;
        }
        $row = $CI->db->select('id_siswa')
            ->where('username', $user->username)
            ->get('master_siswa')->row();
        return $cache = $row ? (int) $row->id_siswa : null;
    }

    /**
     * Guru hanya boleh membuka/mengubah bank soal miliknya sendiri.
     * Admin bebas. Dipanggil untuk controller cbtbanksoal.
     */
    private function batasi_bank_soal($CI) {
        if (strtolower((string) $CI->router->fetch_class()) !== 'cbtbanksoal') {
            return;
        }
        $metode = strtolower((string) $CI->router->fetch_method());
        // B-5: dashboard siswa memakai script eksternal; beri CSP ketat.
        // Dashboard admin masih punya script inline, jadi tetap longgar.
        if (strtolower((string) $CI->router->fetch_class()) === 'dashboard') {
            if ($CI->ion_auth->in_group('siswa')) {
                header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; script-src-attr 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; frame-src 'self' blob: data:; object-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'", true);
            } else {
                header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' https://oss.maxcdn.com https://app.midtrans.com https://app.sandbox.midtrans.com https://static.cloudflareinsights.com; font-src 'self' data:; connect-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com https://api.midtrans.com https://api.sandbox.midtrans.com https://cloudflareinsights.com https://static.cloudflareinsights.com; media-src 'self' blob:; frame-src 'self' blob: data: https://app.midtrans.com https://app.sandbox.midtrans.com; object-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'", true);
            }
        }

        // Admin tidak dibatasi kepemilikan bank.
        if ($CI->ion_auth->is_admin()) {
            return;
        }
        $diawasi = array('buatsoal', 'detail', 'savebank', 'deletebank', 'saveselected',
            'copybanksoal', 'import', 'importsoal', 'savesoal', 'hapussoal',
            'tambahsoal', 'getsoalbynomor', 'getsoalsiswa', 'uploadsoal', 'doimport',
            'deleteallbank');
        if (!in_array($metode, $diawasi, true)) {
            return;
        }

        $user = $CI->ion_auth->user()->row();
        if (!$user) {
            return;
        }
        if (!isset($CI->dashboard)) {
            $CI->load->model('Dashboard_model', 'dashboard');
        }
        $tp  = $CI->dashboard->getTahunActive();
        $smt = $CI->dashboard->getSemesterActive();
        if (!$tp || !$smt) { $this->tolak($CI, 'tahun/semester aktif tidak ditemukan'); }
        $guru = $CI->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        if (!$guru) { $this->tolak($CI, 'guru tidak terdaftar pada tahun/semester aktif'); }
        $gid = (int) $guru->id_guru;

        if ($metode === 'deleteallbank') {
            $this->tolak($CI, 'guru mencoba menghapus semua bank soal');
        }

        if ($metode === 'savebank') {
            $post_guru = (int) $CI->input->post('guru');
            if ($post_guru > 0 && $post_guru !== $gid) {
                $this->tolak($CI, 'guru menyimpan bank soal untuk guru lain');
            }
            return;
        }

        // Nama field tidak seragam di controller lama: ada yang memakai
        // id_bank, bank_id, atau bank. Semua dibaca agar pengecekan tidak bisa
        // dilewati hanya dengan mengganti nama parameter.
        $bank_id = 0;
        foreach (array('id_bank', 'bank_id', 'bank') as $field) {
            $bank_id = (int) $CI->input->post($field);
            if (!$bank_id) { $bank_id = (int) $CI->input->get($field); }
            if ($bank_id) { break; }
        }

        if (!$bank_id && in_array($metode, array('buatsoal', 'detail', 'copybanksoal', 'getsoalsiswa'), true)) {
            // Ambil angka setelah nama method dari path URL. Cara ini aman
            // untuk instalasi di subfolder dan mengabaikan query string.
            $path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
            $bagian = array_values(array_filter(explode('/', (string) $path), 'strlen'));
            $bagian_kecil = array_map('strtolower', $bagian);
            $pos = array_search($metode, $bagian_kecil, true);
            if ($pos !== false && isset($bagian[$pos + 1])) {
                $bank_id = (int) $bagian[$pos + 1];
            }
        }

        // hapusSoal mengirim soal_id, bukan id. Ambil bank dari baris soal itu.
        if ($metode === 'hapussoal') {
            $id_soal = (int) $CI->input->post('soal_id');
            if (!$id_soal) { $id_soal = (int) $CI->input->post('id'); }
            if (!$id_soal) { $id_soal = (int) $CI->input->get('id'); }
            $row = $id_soal > 0
                ? $CI->db->select('bank_id')->where('id_soal', $id_soal)->get('cbt_soal')->row()
                : null;
            if ($row) { $bank_id = (int) $row->bank_id; }
        }

        if ($bank_id <= 0) {
            $this->tolak($CI, 'guru mengakses bank soal tanpa id yang valid: ' . $metode);
        }

        $bank = $CI->db->select('bank_guru_id')->where('id_bank', $bank_id)->get('cbt_bank_soal')->row();
        if (!$bank || (int) $bank->bank_guru_id !== $gid) {
            $this->tolak($CI, 'guru mengakses bank soal guru lain: ' . $bank_id);
        }
    }
    /** Halaman ramah saat installer/updater dibuka pada aplikasi yang sudah terpasang. */
    private function tolak_terpasang($CI) {
        log_message('error', 'AKSES DITOLAK: installer/update setelah aplikasi terpasang');

        if ($CI->input->is_ajax_request()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(array('status' => false, 'msg' => 'Aplikasi sudah terpasang.'));
            exit;
        }

        show_error(
            'Aplikasi ini <b>sudah terpasang</b>, jadi halaman instalasi/update sudah dinonaktifkan.'
            . '<br><br>Silakan masuk lewat halaman login: '
            . '<a href="' . site_url('auth') . '">' . site_url('auth') . '</a>'
            . '<br><br>Bila ingin memasang ulang dari nol, kosongkan dulu database-nya.',
            403,
            'Sudah Terpasang'
        );
    }

    private function tolak($CI, $alasan) {
        $user = $CI->ion_auth->user()->row();
        log_message('error', 'AKSES DITOLAK: ' . $alasan . ' (user: ' . ($user ? $user->username : '?') . ')');

        if ($CI->input->is_ajax_request()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(array('status' => false, 'msg' => 'Akses ditolak.'));
            exit;
        }
        show_error('Anda tidak memiliki izin untuk membuka halaman ini.', 403, 'Akses Ditolak');
    }
}
