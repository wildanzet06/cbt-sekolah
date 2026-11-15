<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pembayaran_model extends CI_Model {

    // Nama bulan sesuai yang tersimpan di kolom `bulan`
    public $daftar_bulan = array(
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    public function get_all_pembayaran() {
        $this->db->select('cbt_pembayaran_spp.*, master_siswa.nis, master_siswa.nama');
        $this->db->select($this->sql_untuk, FALSE);
        $this->db->from('cbt_pembayaran_spp');
        $this->db->join('master_siswa', 'master_siswa.id_siswa = cbt_pembayaran_spp.siswa_id', 'left');
        $this->db->order_by('cbt_pembayaran_spp.created_at', 'DESC');
        return $this->db->get()->result();
    }
    
    public function get_riwayat_siswa($siswa_id) {
        $this->db->select('cbt_pembayaran_spp.*');
        $this->db->select($this->sql_untuk, FALSE);
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
    
    public function get_pembayaran_by_id($id) {
        $this->db->select('cbt_pembayaran_spp.*, master_siswa.nis, master_siswa.nama');
        $this->db->select($this->sql_untuk, FALSE);
        $this->db->from('cbt_pembayaran_spp');
        $this->db->join('master_siswa', 'master_siswa.id_siswa = cbt_pembayaran_spp.siswa_id', 'left');
        $this->db->where('cbt_pembayaran_spp.id', $id);
        return $this->db->get()->row();
    }
    
    public function get_laporan($bulan = null, $tahun = null, $status = null) {
        $this->db->select('cbt_pembayaran_spp.*, master_siswa.nis, master_siswa.nama');
        $this->db->select($this->sql_untuk, FALSE);
        $this->db->from('cbt_pembayaran_spp');
        $this->db->join('master_siswa', 'master_siswa.id_siswa = cbt_pembayaran_spp.siswa_id', 'left');
        
        if ($bulan) {
            $this->db->where('cbt_pembayaran_spp.bulan', $bulan);
        }
        if ($tahun) {
            $this->db->where('cbt_pembayaran_spp.tahun', $tahun);
        }
        if ($status) {
            $this->db->where('cbt_pembayaran_spp.status_pembayaran', $status);
        }
        
        $this->db->order_by('cbt_pembayaran_spp.tanggal_bayar', 'DESC');
        return $this->db->get()->result();
    }
    
    public function get_total_by_bulan($tahun) {
        $this->db->select('bulan, SUM(jumlah_bayar) as total, COUNT(*) as jumlah');
        $this->db->where('tahun', $tahun);
        $this->db->where('status_pembayaran', 'success');
        $this->db->group_by('bulan');
        // Parameter ketiga FALSE: jangan escape, karena ini ekspresi SQL
        // (kalau di-escape, CI memecahnya per koma dan query jadi tidak valid).
        $this->db->order_by('FIELD(bulan, "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember")', '', FALSE);
        return $this->db->get('cbt_pembayaran_spp')->result();
    }
    
    // Fungsi: dapatkan nama kelas siswa - VERSI FINAL
    public function get_nama_kelas_siswa($siswa_id) {
        // Ambil data siswa dulu
        $siswa = $this->db->get_where('master_siswa', array('id_siswa' => $siswa_id))->row();
        if (!$siswa) return '-';
        
        // Coba 1: Kolom id_kelas di master_siswa
        if (isset($siswa->id_kelas) && !empty($siswa->id_kelas)) {
            $kelas = $this->db->get_where('master_kelas', array('id_kelas' => $siswa->id_kelas))->row();
            if ($kelas && !empty($kelas->nama_kelas)) return $kelas->nama_kelas;
        }
        
        // Coba 2: Tabel kelas_siswa (paling umum di GarudaCBT)
        if ($this->db->table_exists('kelas_siswa')) {
            $this->db->select('master_kelas.nama_kelas');
            $this->db->from('kelas_siswa');
            $this->db->join('master_kelas', 'master_kelas.id_kelas = kelas_siswa.id_kelas', 'left');
            $this->db->where('kelas_siswa.id_siswa', $siswa_id);
            $this->db->limit(1);
            $result = $this->db->get()->row();
            if ($result && !empty($result->nama_kelas)) return $result->nama_kelas;
        }
        
        // Coba 3: Tabel cbt_siswa_kelas
        if ($this->db->table_exists('cbt_siswa_kelas')) {
            $this->db->select('master_kelas.nama_kelas');
            $this->db->from('cbt_siswa_kelas');
            $this->db->join('master_kelas', 'master_kelas.id_kelas = cbt_siswa_kelas.id_kelas', 'left');
            $this->db->where('cbt_siswa_kelas.id_siswa', $siswa_id);
            $this->db->limit(1);
            $result = $this->db->get()->row();
            if ($result && !empty($result->nama_kelas)) return $result->nama_kelas;
        }
        
        // Coba 4: Tabel cbt_peserta
        if ($this->db->table_exists('cbt_peserta')) {
            $this->db->select('master_kelas.nama_kelas');
            $this->db->from('cbt_peserta');
            $this->db->join('master_kelas', 'master_kelas.id_kelas = cbt_peserta.id_kelas', 'left');
            $this->db->where('cbt_peserta.id_siswa', $siswa_id);
            $this->db->limit(1);
            $result = $this->db->get()->row();
            if ($result && !empty($result->nama_kelas)) return $result->nama_kelas;
        }
        
        // Coba 5: Tabel master_siswa_kelas
        if ($this->db->table_exists('master_siswa_kelas')) {
            $this->db->select('master_kelas.nama_kelas');
            $this->db->from('master_siswa_kelas');
            $this->db->join('master_kelas', 'master_kelas.id_kelas = master_siswa_kelas.id_kelas', 'left');
            $this->db->where('master_siswa_kelas.id_siswa', $siswa_id);
            $this->db->limit(1);
            $result = $this->db->get()->row();
            if ($result && !empty($result->nama_kelas)) return $result->nama_kelas;
        }
        
        // Fallback: return "-"
        return '-';
    }

    // =====================================================================
    // TAHAP 2: cegah pembayaran ganda, bersihkan pending, tunggakan
    // =====================================================================

    // Nominal SPP dari pengaturan admin
    public function get_nominal_spp() {
        $row = $this->db->get('cbt_setting_spp')->row();
        return $row ? (int) $row->nominal_spp : 0;
    }

    // Cari pembayaran yang "masih berlaku" untuk siswa + bulan + tahun:
    //  - yang sudah Lunas, atau
    //  - transfer manual yang masih menunggu verifikasi admin
    // Mengembalikan 1 baris data, atau NULL jika tidak ada.
    public function get_pembayaran_aktif($siswa_id, $bulan, $tahun) {
        $this->db->where('siswa_id', $siswa_id);
        $this->db->where('bulan', $bulan);
        $this->db->where('tahun', $tahun);
        $this->db->group_start();
            $this->db->where('status_pembayaran', 'success');
            $this->db->or_group_start();
                $this->db->where('status_pembayaran', 'pending');
                $this->db->where('metode_pembayaran', 'transfer_manual');
            $this->db->group_end();
        $this->db->group_end();
        $this->db->limit(1);
        return $this->db->get('cbt_pembayaran_spp')->row();
    }

    // Transaksi Midtrans yang tidak dibayar lebih dari $jam jam dianggap gagal
    public function expire_pending_midtrans($jam = 24) {
        $batas = date('Y-m-d H:i:s', strtotime('-' . (int) $jam . ' hours'));
        $this->db->where('metode_pembayaran', 'midtrans');
        $this->db->where('status_pembayaran', 'pending');
        $this->db->where('created_at <', $batas);
        $this->db->update('cbt_pembayaran_spp', array('status_pembayaran' => 'failed'));
    }

    // Saat siswa membuat percobaan Midtrans baru untuk bulan yang sama,
    // percobaan lama yang masih pending dianggap gagal
    public function batalkan_midtrans_pending($siswa_id, $bulan, $tahun) {
        $this->db->where('siswa_id', $siswa_id);
        $this->db->where('bulan', $bulan);
        $this->db->where('tahun', $tahun);
        $this->db->where('metode_pembayaran', 'midtrans');
        $this->db->where('status_pembayaran', 'pending');
        $this->db->update('cbt_pembayaran_spp', array('status_pembayaran' => 'failed'));
    }

    // Daftar siswa yang belum lunas dari Januari sampai bulan ke-$sampai_bulan
    // pada tahun $tahun. Setiap baris berisi:
    //   ->belum    : bulan yang belum dibayar sama sekali
    //   ->menunggu : bulan yang sedang menunggu verifikasi/pembayaran
    public function get_tunggakan($tahun, $sampai_bulan) {
        $siswa = $this->db->select('id_siswa, nis, nama')
                          ->order_by('nama', 'ASC')
                          ->get('master_siswa')->result();

        $this->db->select('siswa_id, bulan, status_pembayaran');
        $this->db->where('tahun', $tahun);
        $this->db->where_in('status_pembayaran', array('success', 'pending'));
        $rows = $this->db->get('cbt_pembayaran_spp')->result();

        // peta[siswa][bulan] = status ("success" menang atas "pending")
        $peta = array();
        foreach ($rows as $r) {
            if (!isset($peta[$r->siswa_id][$r->bulan]) || $r->status_pembayaran == 'success') {
                $peta[$r->siswa_id][$r->bulan] = $r->status_pembayaran;
            }
        }

        $hasil = array();
        foreach ($siswa as $s) {
            $belum = array();
            $menunggu = array();
            for ($i = 0; $i < $sampai_bulan; $i++) {
                $b  = $this->daftar_bulan[$i];
                $st = isset($peta[$s->id_siswa][$b]) ? $peta[$s->id_siswa][$b] : null;
                if ($st === 'success') continue;
                if ($st === 'pending') {
                    $menunggu[] = $b;
                } else {
                    $belum[] = $b;
                }
            }
            if (!empty($belum) || !empty($menunggu)) {
                $s->belum    = $belum;
                $s->menunggu = $menunggu;
                $hasil[] = $s;
            }
        }
        return $hasil;
    }

    // =====================================================================
    // TAHAP 7: label "untuk pembayaran" & pembatalan percobaan Midtrans
    // =====================================================================

    // Teks keterangan pembayaran. Pembayaran yang terhubung ke tagihan memakai nama
    // jenis + periode; pembayaran lama (tanpa tagihan) tetap tampil "SPP Bulan Tahun".
    public $sql_untuk = "COALESCE((SELECT CONCAT(j.nama, CASE WHEN t.periode_bulan IS NOT NULL THEN CONCAT(' ', t.periode_bulan, ' ', t.periode_tahun) WHEN t.keterangan IS NOT NULL THEN CONCAT(' - ', t.keterangan) ELSE '' END) FROM cbt_tagihan t JOIN cbt_jenis_tagihan j ON j.id = t.jenis_id WHERE t.id = cbt_pembayaran_spp.tagihan_id), CONCAT('SPP ', cbt_pembayaran_spp.bulan, ' ', cbt_pembayaran_spp.tahun)) AS untuk";

    // Saat siswa memulai percobaan Midtrans baru untuk tagihan yang sama,
    // percobaan lama yang masih pending dianggap gagal
    public function batalkan_midtrans_pending_tagihan($tagihan_id) {
        $this->db->where('tagihan_id', (int) $tagihan_id);
        $this->db->where('metode_pembayaran', 'midtrans');
        $this->db->where('status_pembayaran', 'pending');
        $this->db->update('cbt_pembayaran_spp', array('status_pembayaran' => 'failed'));
    }

    // =====================================================================
    // PEMBAYARAN TUNAI (dicatat admin/guru di sekolah)
    // =====================================================================

    /**
     * Pastikan kolom metode_pembayaran menerima nilai 'tunai'.
     * Idempoten: hanya mengubah enum bila 'tunai' belum ada.
     */
    public function pastikan_metode_tunai() {
        $kolom = $this->db->query("SHOW COLUMNS FROM cbt_pembayaran_spp LIKE 'metode_pembayaran'")->row();
        if (!$kolom) {
            return false;
        }
        if (stripos((string) $kolom->Type, "'tunai'") !== false) {
            return true;
        }
        return (bool) $this->db->query(
            "ALTER TABLE cbt_pembayaran_spp
             MODIFY COLUMN metode_pembayaran ENUM('transfer_manual','midtrans','tunai') NOT NULL"
        );
    }

    /** Cari siswa (nama/NIS) pada tahun pelajaran & semester aktif. */
    public function cari_siswa($q, $id_tp, $id_smt, $limit = 25) {
        $q = trim((string) $q);
        $sql = "SELECT s.id_siswa, s.nis, s.nama, ks.id_kelas, mk.nama_kelas
                FROM master_siswa s
                LEFT JOIN kelas_siswa ks
                       ON ks.id_siswa = s.id_siswa AND ks.id_tp = ? AND ks.id_smt = ?
                LEFT JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                WHERE 1 = 1";
        $bind = array((int) $id_tp, (int) $id_smt);

        if ($q !== '') {
            $sql .= " AND (s.nama LIKE ? OR s.nis LIKE ?)";
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }
        $sql .= " ORDER BY s.nama ASC LIMIT " . (int) $limit;

        return $this->db->query($sql, $bind)->result();
    }

    /** Data satu siswa + kelasnya pada periode tertentu. */
    public function siswa_detail($id_siswa, $id_tp = null, $id_smt = null) {
        $this->db->select('s.id_siswa, s.nis, s.nama');
        $this->db->from('master_siswa s');
        $this->db->where('s.id_siswa', (int) $id_siswa);
        $siswa = $this->db->get()->row();
        if (!$siswa) {
            return null;
        }
        $siswa->nama_kelas = null;
        if ($id_tp !== null && $id_smt !== null) {
            $k = $this->db->select('mk.nama_kelas')
                ->from('kelas_siswa ks')
                ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas', 'left')
                ->where('ks.id_siswa', (int) $id_siswa)
                ->where('ks.id_tp', (int) $id_tp)
                ->where('ks.id_smt', (int) $id_smt)
                ->get()->row();
            if ($k) {
                $siswa->nama_kelas = $k->nama_kelas;
            }
        }
        return $siswa;
    }

    /**
     * Simpan pembayaran tunai (langsung lunas/tercatat).
     * $data: siswa_id, bulan, tahun, jumlah_bayar, tanggal (Y-m-d),
     *        tagihan_id (opsional), catatan (opsional), oleh (username)
     * Return insert id atau false.
     */
    public function simpan_tunai(array $data) {
        $this->pastikan_metode_tunai();

        $isi = array(
            'siswa_id'          => (int) $data['siswa_id'],
            'bulan'             => (string) $data['bulan'],
            'tahun'             => (int) $data['tahun'],
            'jumlah_bayar'      => (float) $data['jumlah_bayar'],
            'metode_pembayaran' => 'tunai',
            'status_pembayaran' => 'success',
            'tanggal_bayar'     => $data['tanggal'] . ' ' . date('H:i:s'),
            'created_at'        => date('Y-m-d H:i:s')
        );

        // Kolom opsional: hanya diisi bila memang ada di tabel.
        if ($this->db->field_exists('tagihan_id', 'cbt_pembayaran_spp')) {
            $isi['tagihan_id'] = !empty($data['tagihan_id']) ? (int) $data['tagihan_id'] : null;
        }
        if ($this->db->field_exists('catatan_admin', 'cbt_pembayaran_spp')) {
            $isi['catatan_admin'] = !empty($data['catatan'])
                ? mb_substr((string) $data['catatan'], 0, 255) : 'Pembayaran tunai di sekolah';
        }
        if ($this->db->field_exists('diverifikasi_oleh', 'cbt_pembayaran_spp')) {
            $isi['diverifikasi_oleh'] = isset($data['oleh']) ? (string) $data['oleh'] : null;
        }
        if ($this->db->field_exists('diverifikasi_pada', 'cbt_pembayaran_spp')) {
            $isi['diverifikasi_pada'] = date('Y-m-d H:i:s');
        }

        $this->db->insert('cbt_pembayaran_spp', $isi);
        $id = $this->db->insert_id();
        return $id ? (int) $id : false;
    }

    /** Hapus (batalkan) satu pembayaran tunai. Hanya metode tunai. */
    public function hapus_tunai($id) {
        $row = $this->db->get_where('cbt_pembayaran_spp', array('id' => (int) $id))->row();
        if (!$row || $row->metode_pembayaran !== 'tunai') {
            return false;
        }
        $this->db->where('id', (int) $id);
        $this->db->where('metode_pembayaran', 'tunai');
        $this->db->delete('cbt_pembayaran_spp');
        return $this->db->affected_rows() > 0;
    }
}
