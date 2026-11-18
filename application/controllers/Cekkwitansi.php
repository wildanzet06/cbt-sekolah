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

        if ($id > 0 && $kode !== '') {
            foreach ($this->kunci_kandidat() as $kunci) {
                if ($kunci !== '' && hash_equals($this->kode($id, $kunci), $kode)) {
                    $bayar = $this->Pembayaran_model->get_pembayaran_by_id($id);
                    $sah   = (bool) $bayar;
                    break;
                }
            }
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

    /**
     * Kunci yang diterima untuk memverifikasi kode kwitansi:
     * 1) kunci khusus di database (dipakai sejak perbaikan ini),
     * 2) encryption_key saat ini (kwitansi yang dicetak sebelum perbaikan),
     * 3) encryption_key lama (kwitansi yang dicetak sebelum rotasi kunci).
     * Dengan begitu kwitansi lama tetap sah.
     */
    private function kunci_kandidat() {
        $kandidat = array();
        try {
            $kandidat[] = (string) $this->Pembayaran_model->kunci_kwitansi();
        } catch (Throwable $e) {
            // abaikan
        }
        $kandidat[] = (string) config_item('encryption_key');
        $kandidat[] = '498f8140edc311eb94b9c83dd4416dc8'; // kunci lama (sebelum rotasi)
        return array_unique(array_filter($kandidat, function ($k) { return $k !== ''; }));
    }

    private function kode($id, $kunci = null) {
        if ($kunci === null) {
            $kunci = (string) config_item('encryption_key');
        }
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
