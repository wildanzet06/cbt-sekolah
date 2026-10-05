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
        'tagihan'
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
        }
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
