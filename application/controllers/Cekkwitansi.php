<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman PUBLIK untuk memeriksa keaslian kwitansi pembayaran SPP.
 *
 * Dibuka dengan memindai QR di kwitansi:
 *   /cekkwitansi/{id_pembayaran}/{kode}
 *
 * Kode = HMAC(id, encryption_key) sehingga tidak bisa ditebak/dipalsukan.
 * Halaman ini hanya menampilkan data ringkas (nama disamarkan), tidak
 * menampilkan data pribadi lengkap.
 */
class Cekkwitansi extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('url', 'text'));
        $this->load->model('Pembayaran_model');
        $this->load->model('Dashboard_model', 'dashboard');
    }

    public function index($id = 0, $kode = '') {
        $id   = (int) $id;
        $kode = (string) $kode;

        $sah  = false;
        $bayar = null;

        if ($id > 0 && $kode !== '' && hash_equals($this->kode($id), $kode)) {
            $bayar = $this->Pembayaran_model->get_pembayaran_by_id($id);
            $sah   = (bool) $bayar;
        }

        $nama = '-';
        if ($bayar && !empty($bayar->nama)) {
            $nama = $this->samarkan($bayar->nama);
        }

        $data = array(
            'judul'    => 'Cek Keaslian Kwitansi',
            'sah'      => $sah,
            'bayar'    => $bayar,
            'nama'     => $nama,
            'setting'  => $this->dashboard->getSetting(),
            'kode'     => $kode
        );

        $this->load->view('pembayaran/cekkwitansi', $data);
    }

    private function kode($id) {
        $kunci = (string) config_item('encryption_key');
        return substr(hash_hmac('sha256', 'kwitansi-' . (int) $id, $kunci), 0, 16);
    }

    /** Samarkan nama: "Ahmad Sinabil" -> "A***d S****l". */
    private function samarkan($nama) {
        $kata = preg_split('/\s+/', trim((string) $nama));
        $hasil = array();
        foreach ($kata as $k) {
            $n = mb_strlen($k, 'UTF-8');
            if ($n <= 2) {
                $hasil[] = mb_substr($k, 0, 1, 'UTF-8') . '***';
            } else {
                $hasil[] = mb_substr($k, 0, 1, 'UTF-8') . '***' . mb_substr($k, $n - 1, 1, 'UTF-8');
            }
        }
        return implode(' ', $hasil);
    }
}
