<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bacaan/stimulus bersama untuk beberapa soal (mis. "Text 1" pada soal
 * reading comprehension). Satu bacaan dapat dipakai oleh banyak soal.
 */
class Bacaan_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->pastikan_skema();
    }

    /** Membuat tabel/kolom yang dibutuhkan bila belum ada. */
    public function pastikan_skema() {
        if (!$this->db->table_exists('cbt_bacaan')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `cbt_bacaan` (
                `id_bacaan` INT NOT NULL AUTO_INCREMENT,
                `judul` VARCHAR(255) NOT NULL,
                `isi` LONGTEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_bacaan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!$this->db->field_exists('id_bacaan', 'cbt_soal')) {
            $this->db->query("ALTER TABLE `cbt_soal` ADD COLUMN `id_bacaan` INT NULL");
        }
    }

    public function semua() {
        return $this->db->order_by('judul', 'ASC')->get('cbt_bacaan')->result();
    }

    public function get($id) {
        return $this->db->where('id_bacaan', (int) $id)->get('cbt_bacaan')->row();
    }

    public function simpan($judul, $isi, $id = 0) {
        $data = array(
            'judul' => trim((string) $judul),
            'isi'   => bacaan_isi_aman($isi),
        );
        if ((int) $id > 0) {
            $this->db->where('id_bacaan', (int) $id)->update('cbt_bacaan', $data);
            return (int) $id;
        }
        $data['created_by'] = (int) $this->session->userdata('user_id');
        $this->db->insert('cbt_bacaan', $data);
        return (int) $this->db->insert_id();
    }

    public function hapus($id) {
        $this->db->where('id_bacaan', (int) $id)->update('cbt_soal', array('id_bacaan' => null));
        return $this->db->where('id_bacaan', (int) $id)->delete('cbt_bacaan');
    }

    /** Dipakai halaman bank soal: daftar pilihan bacaan. */
    public function pilihan() {
        if (!$this->db->table_exists('cbt_bacaan')) {
            $this->pastikan_skema();
        }
        return $this->semua();
    }
}
