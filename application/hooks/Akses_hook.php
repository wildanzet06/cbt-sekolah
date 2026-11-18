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
        'pelanggaran'
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
    private $khusus_admin = array('usersiswa', 'userguru', 'useradmin');

    /**
     * Controller yang SELURUH method-nya hanya untuk admin.
     * Datasiswa: tambah/ubah/hapus siswa + ubah username/password siswa.
     * Bukuinduk: buku induk siswa (halaman admin, tidak ada di menu guru).
     */
    private $khusus_admin_semua = array('datasiswa', 'bukuinduk', 'usersiswa');

    /**
     * Pengecualian dari aturan "controller khusus admin" di atas:
     * ganti password sendiri (usersiswa/change_password) tetap boleh untuk
     * semua pengguna yang login — nilainya diambil dari sesi, bukan dari input.
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
     * X-Requested-With) atau POST, tetapi TIDAK lewat tautan/GET biasa —
     * supaya tidak bisa dipicu dari halaman lain (CSRF) atau prefetch browser.
     */
    private $metode_mutasi = array(
        'delete', 'deletebank', 'deletejadwal', 'hapus', 'hapuspost', 'hapuskomentar',
        'hapusbalasan', 'hapusrunningtext', 'hapusjadwal', 'activate', 'deactivate',
        'reset_login', 'truncate', 'hapusbackup', 'backupnilai', 'generatealumni',
        'aktifkan', 'nonaktifkan', 'verifikasi'
    );

    public function periksa() {
        $CI =& get_instance();

        if (!isset($CI->ion_auth)) {
            $CI->load->library('ion_auth');
        }
        if (!$CI->ion_auth->logged_in()) {
            return; // belum login: biarkan controller yang menangani
        }

        $nama = array_unique(array(
            strtolower((string) $CI->router->fetch_class()),
            strtolower((string) $CI->uri->segment(1))
        ));
        $metode = strtolower((string) $CI->router->fetch_method());

        $admin = $CI->ion_auth->is_admin();
        $siswa = !$admin && $CI->ion_auth->in_group(array(3, 'siswa'));

        // Backup otomatis sekali sehari saat admin membuka halaman.
        if ($admin) {
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
                $src = (string) $CI->input->post('src', true);
                if ($src !== '' && $this->path_mencurigakan($src)) {
                    $this->tolak($CI, 'path traversal hapus file: ' . $src);
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
