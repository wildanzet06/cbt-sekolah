<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pembayaran_model extends CI_Model {
    
    public function get_all_pembayaran() {
        $this->db->select('cbt_pembayaran_spp.*, master_siswa.nis, master_siswa.nama');
        $this->db->from('cbt_pembayaran_spp');
        $this->db->join('master_siswa', 'master_siswa.id_siswa = cbt_pembayaran_spp.siswa_id', 'left');
        $this->db->order_by('cbt_pembayaran_spp.created_at', 'DESC');
        return $this->db->get()->result();
    }
    
    public function get_riwayat_siswa($siswa_id) {
        $this->db->where('siswa_id', $siswa_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('cbt_pembayaran_spp')->result();
    }
    
    public function count_by_status($status) {
        $this->db->where('status_pembayaran', $status);
        return $this->db->count_all_results('cbt_pembayaran_spp');
    }
    
    public function get_total_revenue() {
        $this->db->select_sum('jumlah_bayar');
        $this->db->where('status_pembayaran', 'success');
        $query = $this->db->get('cbt_pembayaran_spp');
        return $query->row()->jumlah_bayar ? $query->row()->jumlah_bayar : 0;
    }
}