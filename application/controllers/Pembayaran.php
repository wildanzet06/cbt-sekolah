<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pembayaran extends CI_Controller {

    // Cicilan minimal per pembayaran (Rp). Jika sisa tagihan lebih kecil, boleh melunasi sisanya.
    const MIN_CICILAN = 10000;

    // Method yang boleh diakses TANPA login (dipanggil server Midtrans, bukan manusia)
    private $public_methods = array('midtrans_notification');

    public function __construct() {
        parent::__construct();
        $this->load->model('Pembayaran_model');
        $this->load->model('Tagihan_model');
        $this->load->helper(array('form', 'url', 'text'));
        $this->load->library(array('ion_auth', 'session'));

        // Kunci & pengaturan Midtrans dibaca dari application/config/midtrans.php
        // Berkas midtrans.php bersifat OPSIONAL: tanpa berkas itu, halaman keuangan tetap
        // bisa dibuka dan pembayaran lewat transfer manual tetap jalan.
        $this->config->load('midtrans', FALSE, TRUE);

        $method = $this->router->fetch_method();
        if (!in_array($method, $this->public_methods) && !$this->ion_auth->logged_in()) {
            redirect('auth');
        }

        // Model dashboard untuk sidebar & navbar
        $this->load->model('Dashboard_model', 'dashboard');
    }

    // =====================================================================
    // HELPER PRIVATE
    // =====================================================================

    /**
     * Data global yang dibutuhkan header, navbar, dan sidebar admin.
     * Sama seperti yang dikirim controller Dashboard.
     */
    private function _admin_data($title) {
        $user = $this->ion_auth->user()->row();

        $data['title']      = $title;
        $data['judul']      = $title; // dipakai _header.php untuk <title>
        $data['user']       = $user;
        $data['setting']    = $this->dashboard->getSetting();
        $data['profile']    = $this->dashboard->getProfileAdmin($user->id);
        $data['tp']         = $this->dashboard->getTahun();
        $data['tp_active']  = $this->dashboard->getTahunActive();
        $data['smt']        = $this->dashboard->getSemester();
        $data['smt_active'] = $this->dashboard->getSemesterActive();

        return $data;
    }

    /**
     * Render halaman admin dengan header + sidebar + footer yang sama dengan dashboard.
     */
    private function _render_admin($view, $data) {
        // Sidebar & navbar sudah di-include di dalam _header.php,
        // jadi TIDAK perlu load view sidebar lagi di sini.
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    /**
     * Ambil data siswa yang sedang login.
     */
    private function _get_siswa() {
        $user = $this->ion_auth->user()->row();
        $this->load->model('Cbt_model', 'cbt');

        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $siswa = $this->cbt->getDataSiswa($user->username, $tp->id_tp, $smt->id_smt);

        if (!$siswa) {
            $siswa = $this->db->get_where('master_siswa', array('username' => $user->username))->row();
        }

        return $siswa;
    }

    /**
     * Cek apakah siswa boleh membayar SPP bulan/tahun tersebut.
     * Mengisi flashdata 'error' dan mengembalikan FALSE jika tidak boleh.
     */
    // Midtrans dianggap siap jika kunci server sudah diisi (bukan tulisan contoh)
    // dan library Midtrans tersedia.
    private function _midtrans_siap() {
        $kunci = (string) $this->config->item('midtrans_server_key');
        return $kunci !== ''
            && strpos($kunci, 'ISI_') !== 0
            && file_exists(APPPATH . 'third_party/midtrans/Midtrans.php');
    }

    /**
     * Validasi pembayaran sebuah tagihan oleh siswa yang sedang login.
     * Mengembalikan array(tagihan, jumlah) jika boleh, atau FALSE (flashdata 'error' terisi).
     */
    private function _validasi_bayar($siswa) {
        $t = $this->Tagihan_model->get_tagihan((int) $this->input->post('tagihan_id'));

        // Tagihan harus ada dan milik siswa ini (bukan milik siswa lain)
        if (!$t || (int) $t->siswa_id !== (int) $siswa->id_siswa) {
            $this->session->set_flashdata('error', 'Tagihan tidak ditemukan.');
            return false;
        }
        if ($t->status == 'lunas') {
            $this->session->set_flashdata('error', 'Tagihan ini sudah lunas.');
            return false;
        }

        // Bagian yang masih boleh dibayar = sisa dikurangi transfer yang menunggu verifikasi
        $maks = $t->sisa - $t->pending_manual;
        if ($maks <= 0 || (!$t->boleh_cicil && $t->pending_manual > 0)) {
            $this->session->set_flashdata('error', 'Pembayaran untuk tagihan ini sedang menunggu verifikasi admin.');
            return false;
        }

        if ($t->boleh_cicil) {
            $jumlah = (int) preg_replace('/\D/', '', (string) $this->input->post('jumlah_bayar'));
            $min    = min(self::MIN_CICILAN, $maks);
            if ($jumlah < $min || $jumlah > $maks) {
                $this->session->set_flashdata('error',
                    'Jumlah cicilan harus antara Rp ' . number_format($min, 0, ',', '.') .
                    ' dan Rp ' . number_format($maks, 0, ',', '.') . '.');
                return false;
            }
        } else {
            // Tagihan yang tidak boleh dicicil harus dibayar penuh (nominal dari server)
            $jumlah = $t->sisa;
        }

        return array($t, $jumlah);
    }

    // Bulan & tahun yang dicatat pada baris pembayaran
    private function _periode_pembayaran($t) {
        if (!empty($t->periode_bulan)) {
            return array($t->periode_bulan, (int) $t->periode_tahun);
        }
        return array('-', (int) date('Y'));
    }

    // Teks "untuk apa" pembayaran ini (dipakai di Midtrans)
    private function _label_tagihan($t) {
        $label = $t->jenis_nama;
        if (!empty($t->periode_bulan)) {
            $label .= ' ' . $t->periode_bulan . ' ' . $t->periode_tahun;
        } elseif (!empty($t->keterangan)) {
            $label .= ' - ' . $t->keterangan;
        }
        return mb_substr($label, 0, 50);
    }

    // =====================================================================
    // ADMIN
    // =====================================================================
    public function index() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        // Transaksi Midtrans yang tidak dibayar > 24 jam otomatis dianggap gagal
        $this->Pembayaran_model->expire_pending_midtrans(24);

        $data = $this->_admin_data('Pembayaran SPP');
        $data['pembayaran']    = $this->Pembayaran_model->get_all_pembayaran();
        $data['total_pending'] = $this->Pembayaran_model->count_by_status('pending');
        $data['total_success'] = $this->Pembayaran_model->count_by_status('success');
        $data['total_revenue'] = $this->Pembayaran_model->get_total_revenue();

        $this->_render_admin('pembayaran/index', $data);
    }

    public function setting_spp() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $data = $this->_admin_data('Setting SPP');
        $data['setting_spp'] = $this->db->get('cbt_setting_spp')->row();

        $this->_render_admin('pembayaran/setting_spp', $data);
    }

    public function update_setting_spp() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $data = array(
            'nominal_spp' => $this->input->post('nominal_spp'),
            'keterangan'  => $this->input->post('keterangan')
        );
        $this->db->update('cbt_setting_spp', $data);
        $this->session->set_flashdata('success', 'Setting SPP berhasil diupdate');
        redirect('pembayaran/setting_spp');
    }

    public function verifikasi($id) {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $update = array(
            'status_pembayaran' => 'success',
            'tanggal_bayar'     => date('Y-m-d H:i:s')
        );
        $update = array_merge($update, $this->_audit_fields());

        // Hanya yang masih pending yang bisa diverifikasi
        $this->db->where('id', $id);
        $this->db->where('status_pembayaran', 'pending');
        $this->db->update('cbt_pembayaran_spp', $update);

        if ($this->db->affected_rows() > 0) {
            $this->session->set_flashdata('success', 'Pembayaran berhasil diverifikasi');
        } else {
            $this->session->set_flashdata('error', 'Pembayaran tidak ditemukan atau sudah diproses.');
        }
        redirect('pembayaran');
    }

    // Admin menolak pembayaran (mis. bukti transfer tidak sesuai).
    // Statusnya jadi "Gagal", sehingga siswa bisa mengajukan ulang bulan itu.
    public function tolak($id) {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        // Penolakan wajib lewat POST (dikirim dari tombol Tolak di halaman Pembayaran)
        if ($this->input->method() !== 'post') {
            redirect('pembayaran');
            return;
        }

        $alasan = trim((string) $this->input->post('alasan'));
        $update = array('status_pembayaran' => 'failed');
        $update = array_merge($update, $this->_audit_fields($alasan));

        $this->db->where('id', $id);
        $this->db->where('status_pembayaran', 'pending');
        $this->db->update('cbt_pembayaran_spp', $update);

        if ($this->db->affected_rows() > 0) {
            $this->session->set_flashdata('success', 'Pembayaran ditolak. Siswa dapat mengajukan ulang.');
        } else {
            $this->session->set_flashdata('error', 'Pembayaran tidak ditemukan atau sudah diproses.');
        }
        redirect('pembayaran');
    }

    // Catatan siapa & kapan memproses pembayaran. Hanya mengisi kolom yang
    // memang ada di tabel, jadi aman walau SQL penambahan kolom belum dijalankan.
    private function _audit_fields($catatan = null) {
        $user  = $this->ion_auth->user()->row();
        $isi   = array(
            'diverifikasi_oleh' => $user ? $user->username : null,
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
            'catatan_admin'     => ($catatan !== null && $catatan !== '') ? mb_substr($catatan, 0, 255) : null
        );

        $hasil = array();
        foreach ($isi as $kolom => $nilai) {
            if ($this->db->field_exists($kolom, 'cbt_pembayaran_spp')) {
                $hasil[$kolom] = $nilai;
            }
        }
        return $hasil;
    }

    // =====================================================================
    // KWITANSI
    // =====================================================================
    public function kwitansi($id) {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $pembayaran = $this->Pembayaran_model->get_pembayaran_by_id($id);
        if (!$pembayaran) {
            show_error('Data pembayaran tidak ditemukan');
        }

        $data = $this->_admin_data('Kwitansi Pembayaran');
        $data['pembayaran'] = $pembayaran;

        $this->_render_admin('pembayaran/kwitansi', $data);
    }

    public function cetak_kwitansi($id) {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $data['pembayaran'] = $this->Pembayaran_model->get_pembayaran_by_id($id);
        $data['setting']    = $this->dashboard->getSetting();

        if (!$data['pembayaran']) {
            show_error('Data pembayaran tidak ditemukan');
        }

        $this->load->view('pembayaran/cetak_kwitansi', $data);
    }

    // =====================================================================
    // LAPORAN
    // =====================================================================
    public function laporan() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $bulan  = $this->input->get('bulan');
        $tahun  = $this->input->get('tahun');
        $status = $this->input->get('status');

        $data = $this->_admin_data('Laporan Pembayaran SPP');
        $data['laporan']        = $this->Pembayaran_model->get_laporan($bulan, $tahun, $status);
        $data['total_by_bulan'] = $this->Pembayaran_model->get_total_by_bulan($tahun ?: date('Y'));
        $data['filter']         = array('bulan' => $bulan, 'tahun' => $tahun, 'status' => $status);

        $this->_render_admin('pembayaran/laporan', $data);
    }

    // =====================================================================
    // SISWA
    // =====================================================================
    public function bayar_spp() {
        $user  = $this->ion_auth->user()->row();
        $siswa = $this->_get_siswa();

        if (!$siswa) {
            show_error('Data siswa tidak ditemukan.<br><br>Username: ' . $user->username . '<br>User ID: ' . $user->id);
        }

        $data['user']        = $user;
        $data['siswa']       = $siswa;
        $data['setting']     = $this->dashboard->getSetting();
        $data['tp']          = $this->dashboard->getTahun();
        $data['tp_active']   = $this->dashboard->getTahunActive();
        $data['smt']         = $this->dashboard->getSemester();
        $data['smt_active']  = $this->dashboard->getSemesterActive();
        $data['title']       = 'Pembayaran Sekolah';
        $data['setting_spp'] = $this->db->get('cbt_setting_spp')->row();
        $data['tagihan']     = $this->Tagihan_model->tagihan_siswa($siswa->id_siswa);
        $data['riwayat']     = $this->Pembayaran_model->get_riwayat_siswa($siswa->id_siswa);
        $data['min_cicilan'] = self::MIN_CICILAN;
        $data['midtrans_aktif'] = $this->_midtrans_siap();

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('pembayaran/bayar_spp', $data);
        $this->load->view('members/siswa/templates/footer');
    }

    public function submit_pembayaran_manual() {
        $siswa = $this->_get_siswa();

        if (!$siswa) {
            show_error('Data siswa tidak ditemukan.');
        }

        // Cek tagihan & jumlah (dari server) SEBELUM menerima upload
        $cek = $this->_validasi_bayar($siswa);
        if (!$cek) {
            redirect('pembayaran/bayar_spp');
            return;
        }
        list($t, $jumlah) = $cek;
        list($bulan, $tahun) = $this->_periode_pembayaran($t);

        $upload_path = './uploads/bukti_transfer/';
        if (!file_exists($upload_path)) mkdir($upload_path, 0777, true);

        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $config['max_size']      = 2048;
        $config['file_name']     = 'bukti_' . $siswa->id_siswa . '_' . time();

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('bukti_transfer')) {
            $this->session->set_flashdata('error', 'Gagal upload: ' . $this->upload->display_errors());
            redirect('pembayaran/bayar_spp');
            return;
        }

        $upload_data = $this->upload->data();
        $data = array(
            'siswa_id'          => $siswa->id_siswa,
            'tagihan_id'        => $t->id,
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'jumlah_bayar'      => $jumlah,
            'metode_pembayaran' => 'transfer_manual',
            'status_pembayaran' => 'pending',
            'bukti_transfer'    => $upload_data['file_name']
        );

        $this->db->insert('cbt_pembayaran_spp', $data);
        $this->session->set_flashdata('success', 'Pembayaran diajukan. Tunggu verifikasi admin.');
        redirect('pembayaran/bayar_spp');
    }

    public function bayar_midtrans() {
        $siswa = $this->_get_siswa();

        if (!$siswa) {
            show_error('Data siswa tidak ditemukan.');
        }

        $cek = $this->_validasi_bayar($siswa);
        if (!$cek) {
            redirect('pembayaran/bayar_spp');
            return;
        }
        list($t, $jumlah) = $cek;
        list($bulan, $tahun) = $this->_periode_pembayaran($t);

        if (!$this->_midtrans_siap()) {
            $this->session->set_flashdata('error', 'Pembayaran online (Midtrans) belum diaktifkan. Silakan gunakan transfer manual.');
            redirect('pembayaran/bayar_spp');
            return;
        }

        if (file_exists(APPPATH . 'third_party/midtrans/Midtrans.php')) {
            require_once APPPATH . 'third_party/midtrans/Midtrans.php';

            \Midtrans\Config::$serverKey        = $this->config->item('midtrans_server_key');
            \Midtrans\Config::$isProduction     = $this->config->item('midtrans_is_production');
            \Midtrans\Config::$isSanitized      = true;
            \Midtrans\Config::$is3ds            = true;
            \Midtrans\Config::$overrideNotifUrl = $this->config->item('midtrans_notif_url');

            $order_id = 'TGH' . $t->id . '-' . $siswa->id_siswa . '-' . date('YmdHis');

            $params = array(
                'transaction_details' => array(
                    'order_id'     => $order_id,
                    'gross_amount' => (int) $jumlah,
                ),
                'item_details' => array(
                    array(
                        'id'       => 'TGH' . $t->id,
                        'price'    => (int) $jumlah,
                        'quantity' => 1,
                        'name'     => $this->_label_tagihan($t),
                    ),
                ),
                'customer_details' => array(
                    'first_name' => $siswa->nama,
                    'email'      => $siswa->email,
                ),
                'callbacks' => array(
                    'finish' => base_url('pembayaran/bayar_spp'),
                    'error'  => base_url('pembayaran/bayar_spp'),
                ),
            );

            try {
                $snapToken = \Midtrans\Snap::getSnapToken($params);

                // Percobaan Midtrans lama untuk tagihan yang sama dianggap gagal
                $this->Pembayaran_model->batalkan_midtrans_pending_tagihan($t->id);

                $this->db->insert('cbt_pembayaran_spp', array(
                    'siswa_id'          => $siswa->id_siswa,
                    'tagihan_id'        => $t->id,
                    'bulan'             => $bulan,
                    'tahun'             => $tahun,
                    'jumlah_bayar'      => $jumlah,
                    'metode_pembayaran' => 'midtrans',
                    'status_pembayaran' => 'pending',
                    'order_id'          => $order_id
                ));

                $data_view['snapToken'] = $snapToken;
                $this->load->view('pembayaran/midtrans_payment', $data_view);
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
                redirect('pembayaran/bayar_spp');
            }
        } else {
            $this->session->set_flashdata('error', 'Library Midtrans belum diinstall.');
            redirect('pembayaran/bayar_spp');
        }
    }

    public function midtrans_notification() {
        if (!$this->_midtrans_siap()) {
            $this->output->set_status_header(503);
            echo 'Midtrans belum dikonfigurasi';
            return;
        }

        if (file_exists(APPPATH . 'third_party/midtrans/Midtrans.php')) {
            require_once APPPATH . 'third_party/midtrans/Midtrans.php';
            $server_key = $this->config->item('midtrans_server_key');
            \Midtrans\Config::$serverKey    = $server_key;
            \Midtrans\Config::$isProduction = $this->config->item('midtrans_is_production');

            // --- Verifikasi signature: pastikan notifikasi benar-benar dari Midtrans ---
            $raw  = json_decode(file_get_contents('php://input'), true);
            $sign = hash('sha512',
                (isset($raw['order_id']) ? $raw['order_id'] : '') .
                (isset($raw['status_code']) ? $raw['status_code'] : '') .
                (isset($raw['gross_amount']) ? $raw['gross_amount'] : '') .
                $server_key
            );
            if (empty($raw['signature_key']) || !hash_equals($sign, $raw['signature_key'])) {
                log_message('error', 'Midtrans notif DITOLAK: signature tidak cocok');
                $this->output->set_status_header(403);
                echo 'invalid signature';
                return;
            }
            log_message('info', 'Midtrans notif OK: ' . $raw['order_id'] . ' => ' . $raw['transaction_status']);

            $notif = new \Midtrans\Notification();

            $status = 'failed';
            if ($notif->transaction_status == 'capture' || $notif->transaction_status == 'settlement') {
                $status = 'success';
            } elseif ($notif->transaction_status == 'pending') {
                $status = 'pending';
            }

            $update = array(
                'status_pembayaran' => $status,
                'transaction_id'    => $notif->transaction_id
            );
            if ($status == 'success') {
                $update['tanggal_bayar'] = date('Y-m-d H:i:s');
            }

            $this->db->where('order_id', $notif->order_id);
            $this->db->update('cbt_pembayaran_spp', $update);
        }
    }

    // =====================================================================
    // TUNGGAKAN (admin)
    // =====================================================================
    // Tunggakan kini dilihat lewat Daftar Tagihan (status: belum lunas)
    public function tunggakan() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');
        redirect('tagihan?status=tunggakan');
    }

    // =====================================================================
    // EKSPOR EXCEL (CSV)
    // =====================================================================

    // Ekspor daftar transaksi. Bisa difilter: ?bulan=Juni&tahun=2026&status=success
    public function export_laporan() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $bulan  = $this->input->get('bulan');
        $tahun  = $this->input->get('tahun');
        $status = $this->input->get('status');

        $rows = $this->Pembayaran_model->get_laporan($bulan, $tahun, $status);

        $label_status = array('success' => 'Lunas', 'pending' => 'Pending');
        $label_metode = array('transfer_manual' => 'Transfer Manual', 'midtrans' => 'Midtrans');

        $baris = array();
        $no = 1;
        foreach ($rows as $r) {
            $baris[] = array(
                $no++,
                $r->nis,
                $r->nama,
                $this->Pembayaran_model->get_nama_kelas_siswa($r->siswa_id),
                $r->untuk,
                (int) $r->jumlah_bayar,
                isset($label_metode[$r->metode_pembayaran]) ? $label_metode[$r->metode_pembayaran] : $r->metode_pembayaran,
                isset($label_status[$r->status_pembayaran]) ? $label_status[$r->status_pembayaran] : 'Gagal',
                $r->tanggal_bayar ? date('d/m/Y H:i', strtotime($r->tanggal_bayar)) : ''
            );
        }

        $this->_kirim_csv(
            'laporan_spp_' . date('Ymd_His') . '.csv',
            array('No', 'NIS', 'Nama Siswa', 'Kelas', 'Untuk Pembayaran', 'Jumlah (Rp)', 'Metode', 'Status', 'Tanggal'),
            $baris
        );
    }

    // Ekspor daftar tunggakan. Parameter sama dengan halaman Tunggakan.
    // Ekspor Daftar Tagihan (filter sama dengan halaman Daftar Tagihan)
    public function export_tagihan() {
        if (!$this->ion_auth->is_admin()) show_error('Akses Ditolak');

        $filter = array(
            'jenis_id' => $this->input->get('jenis_id'),
            'bulan'    => $this->input->get('bulan'),
            'tahun'    => $this->input->get('tahun'),
            'status'   => $this->input->get('status')
        );
        $rows = $this->Tagihan_model->list_tagihan($filter);

        $label_status = array('lunas' => 'Lunas', 'cicilan' => 'Cicilan', 'belum' => 'Belum dibayar');

        $baris = array();
        $no = 1;
        foreach ($rows as $t) {
            if (!empty($t->periode_bulan)) {
                $periode = $t->periode_bulan . ' ' . $t->periode_tahun;
            } else {
                $periode = $t->keterangan ? $t->keterangan : '-';
            }
            $baris[] = array(
                $no++,
                $t->nis,
                $t->nama,
                $this->Pembayaran_model->get_nama_kelas_siswa($t->siswa_id),
                $t->jenis_nama,
                $periode,
                (int) $t->nominal,
                (int) $t->diskon,
                (int) $t->terbayar,
                (int) $t->sisa,
                $t->gratis ? 'Gratis' : $label_status[$t->status],
                $t->jatuh_tempo ? date('d/m/Y', strtotime($t->jatuh_tempo)) : ''
            );
        }

        $this->_kirim_csv(
            'tagihan_' . date('Ymd_His') . '.csv',
            array('No', 'NIS', 'Nama Siswa', 'Kelas', 'Jenis', 'Periode/Keterangan', 'Tagihan (Rp)', 'Diskon (Rp)', 'Terbayar (Rp)', 'Sisa (Rp)', 'Status', 'Jatuh Tempo'),
            $baris
        );
    }

    // Mengirim file CSV ke browser. Dibuat agar langsung rapi saat dibuka di Excel:
    //  - BOM UTF-8      : huruf/tanda baca Indonesia tampil benar
    //  - "sep=;"        : Excel memisah kolom dengan titik koma (di semua pengaturan bahasa)
    private function _kirim_csv($nama_file, $header, $baris) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo "sep=;\r\n";

        $out = fopen('php://output', 'w');
        fputcsv($out, $header, ';');
        foreach ($baris as $b) {
            fputcsv($out, array_map(array($this, '_csv_aman'), $b), ';');
        }
        fclose($out);
        exit;
    }

    // Mencegah "formula injection": teks yang diawali = + - @ bisa dijalankan
    // sebagai rumus oleh Excel. Angka asli tidak diubah.
    private function _csv_aman($nilai) {
        if (is_string($nilai) && $nilai !== '' && strpos('=+-@', $nilai[0]) !== false) {
            return "'" . $nilai;
        }
        return $nilai;
    }
}