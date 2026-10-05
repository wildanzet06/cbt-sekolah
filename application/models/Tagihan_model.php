<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tagihan_model extends CI_Model {

    public $daftar_bulan = array(
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );

    // =====================================================================
    // JENIS TAGIHAN
    // =====================================================================

    public function jenis_all($hanya_aktif = false) {
        $sql = "SELECT j.*,
                       (SELECT COUNT(*) FROM cbt_tagihan t WHERE t.jenis_id = j.id) AS jml_tagihan
                FROM cbt_jenis_tagihan j";
        if ($hanya_aktif) {
            $sql .= " WHERE j.aktif = 1";
        }
        $sql .= " ORDER BY j.nama ASC";
        return $this->db->query($sql)->result();
    }

    public function jenis_by_id($id) {
        return $this->db->get_where('cbt_jenis_tagihan', array('id' => (int) $id))->row();
    }

    public function kode_dipakai($kode, $kecuali_id = 0) {
        $this->db->where('kode', $kode);
        if ($kecuali_id) {
            $this->db->where('id !=', (int) $kecuali_id);
        }
        return $this->db->count_all_results('cbt_jenis_tagihan') > 0;
    }

    public function jenis_simpan($data, $id = 0) {
        if ($id) {
            $this->db->where('id', (int) $id);
            return $this->db->update('cbt_jenis_tagihan', $data);
        }
        return $this->db->insert('cbt_jenis_tagihan', $data);
    }

    // Hapus jika belum pernah dipakai; jika sudah, hanya dinonaktifkan.
    public function jenis_hapus($id) {
        $id = (int) $id;
        $this->db->where('jenis_id', $id);
        $dipakai = $this->db->count_all_results('cbt_tagihan') > 0;

        if ($dipakai) {
            $this->db->where('id', $id);
            $this->db->update('cbt_jenis_tagihan', array('aktif' => 0));
            return 'dinonaktifkan';
        }

        $this->db->where('id', $id);
        $this->db->delete('cbt_jenis_tagihan');
        return 'dihapus';
    }

    // =====================================================================
    // KERINGANAN SISWA
    // =====================================================================

    public function siswa_all() {
        return $this->db->select('id_siswa, nis, nama')->order_by('nama', 'ASC')->get('master_siswa')->result();
    }

    public function keringanan_all() {
        $sql = "SELECT k.*, s.nis, s.nama, j.nama AS jenis_nama
                FROM cbt_keringanan k
                LEFT JOIN master_siswa s ON s.id_siswa = k.siswa_id
                LEFT JOIN cbt_jenis_tagihan j ON j.id = k.jenis_id
                ORDER BY s.nama ASC, k.id ASC";
        return $this->db->query($sql)->result();
    }

    public function keringanan_by_id($id) {
        return $this->db->get_where('cbt_keringanan', array('id' => (int) $id))->row();
    }

    public function keringanan_simpan($data, $id = 0) {
        if ($id) {
            $this->db->where('id', (int) $id);
            return $this->db->update('cbt_keringanan', $data);
        }
        return $this->db->insert('cbt_keringanan', $data);
    }

    public function keringanan_hapus($id) {
        $this->db->where('id', (int) $id);
        $this->db->delete('cbt_keringanan');
        return $this->db->affected_rows() > 0;
    }

    // Peta keringanan aktif untuk satu jenis tagihan: [siswa_id] => array(aturan, ...)
    // (termasuk aturan "semua jenis" yaitu jenis_id NULL)
    public function muat_keringanan($jenis_id) {
        $this->db->where('aktif', 1);
        $this->db->group_start();
            $this->db->where('jenis_id', (int) $jenis_id);
            $this->db->or_where('jenis_id IS NULL', null, false);
        $this->db->group_end();
        $peta = array();
        foreach ($this->db->get('cbt_keringanan')->result() as $k) {
            $peta[$k->siswa_id][] = $k;
        }
        return $peta;
    }

    // Diskon terbaik (dalam rupiah) dari daftar aturan, tidak melebihi nominal
    public function hitung_diskon($aturan, $nominal) {
        $nominal = (int) $nominal;
        $maks    = 0;
        foreach ((array) $aturan as $k) {
            if ($k->tipe == 'persen') {
                $d = (int) round($nominal * min(100, (int) $k->nilai) / 100);
            } else {
                $d = (int) $k->nilai;
            }
            if ($d > $maks) $maks = $d;
        }
        return min($maks, $nominal);
    }

    // Ubah diskon satu tagihan secara manual (0 s/d nominal tagihan)
    public function ubah_diskon($tagihan_id, $diskon) {
        $t = $this->db->get_where('cbt_tagihan', array('id' => (int) $tagihan_id))->row();
        if (!$t) return false;

        $diskon = max(0, min((int) $diskon, (int) $t->nominal));
        $this->db->where('id', (int) $tagihan_id);
        $this->db->update('cbt_tagihan', array('diskon' => $diskon));
        return true;
    }

    // =====================================================================
    // TARGET SISWA (semua / hanya yang aktif di kelas)
    // =====================================================================

    public function count_siswa() {
        return $this->db->count_all('master_siswa');
    }

    // Apakah tabel kelas_siswa punya kolom yang dibutuhkan?
    public function siswa_aktif_tersedia() {
        return $this->db->table_exists('kelas_siswa')
            && $this->db->field_exists('id_siswa', 'kelas_siswa')
            && $this->db->field_exists('id_tp', 'kelas_siswa')
            && $this->db->field_exists('id_smt', 'kelas_siswa')
            && $this->db->field_exists('id_kelas', 'kelas_siswa');
    }

    public function count_siswa_aktif($id_tp, $id_smt) {
        if (!$this->siswa_aktif_tersedia()) return 0;
        $this->db->distinct();
        $this->db->select('id_siswa');
        $this->db->where('id_tp', (int) $id_tp);
        $this->db->where('id_smt', (int) $id_smt);
        return $this->db->get('kelas_siswa')->num_rows();
    }

    public function daftar_kelas_aktif($id_tp, $id_smt) {
        if (!$this->siswa_aktif_tersedia()) return array();
        $sql = "SELECT DISTINCT mk.id_kelas, mk.nama_kelas
                FROM kelas_siswa ks
                JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                WHERE ks.id_tp = ? AND ks.id_smt = ?
                ORDER BY mk.nama_kelas ASC";
        return $this->db->query($sql, array((int) $id_tp, (int) $id_smt))->result();
    }

    // Daftar id_siswa sasaran. $mode: 'semua' atau 'aktif'
    public function id_siswa_target($mode, $id_kelas, $id_tp, $id_smt) {
        if ($mode == 'aktif' && $this->siswa_aktif_tersedia()) {
            $this->db->distinct();
            $this->db->select('id_siswa');
            $this->db->where('id_tp', (int) $id_tp);
            $this->db->where('id_smt', (int) $id_smt);
            if ($id_kelas) {
                $this->db->where('id_kelas', (int) $id_kelas);
            }
            $rows = $this->db->get('kelas_siswa')->result();
        } else {
            $rows = $this->db->select('id_siswa')->get('master_siswa')->result();
        }

        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (int) $r->id_siswa;
        }
        return $ids;
    }

    // =====================================================================
    // PEMBUATAN TAGIHAN MASSAL
    // =====================================================================

    // Membuat tagihan untuk daftar siswa $ids. Siswa yang sudah punya tagihan
    // yang sama dilewati. Keringanan siswa diterapkan otomatis.
    public function buat_massal($jenis, $bulan, $tahun, $nominal, $jatuh_tempo, $keterangan, $ids) {
        // Siswa yang sudah punya tagihan yang sama
        $this->db->select('siswa_id');
        $this->db->where('jenis_id', (int) $jenis->id);
        if ($jenis->tipe == 'bulanan') {
            $this->db->where('periode_bulan', $bulan);
            $this->db->where('periode_tahun', (int) $tahun);
        } else {
            $this->db->where('periode_bulan IS NULL', null, false);
            if ($keterangan !== '') {
                $this->db->where('keterangan', $keterangan);
            } else {
                $this->db->where('keterangan IS NULL', null, false);
            }
        }
        $sudah = array();
        foreach ($this->db->get('cbt_tagihan')->result() as $r) {
            $sudah[$r->siswa_id] = true;
        }

        $keringanan = $this->muat_keringanan($jenis->id);

        $baris = array();
        $hasil = array('dibuat' => 0, 'dilewati' => 0, 'keringanan' => 0, 'gratis' => 0);

        foreach ($ids as $id_siswa) {
            if (isset($sudah[$id_siswa])) {
                $hasil['dilewati']++;
                continue;
            }

            $diskon = isset($keringanan[$id_siswa])
                ? $this->hitung_diskon($keringanan[$id_siswa], $nominal)
                : 0;
            if ($diskon > 0)             $hasil['keringanan']++;
            if ($diskon >= (int) $nominal) $hasil['gratis']++;

            $baris[] = array(
                'siswa_id'      => $id_siswa,
                'jenis_id'      => $jenis->id,
                'periode_bulan' => ($jenis->tipe == 'bulanan') ? $bulan : null,
                'periode_tahun' => ($jenis->tipe == 'bulanan') ? (int) $tahun : null,
                'keterangan'    => ($keterangan !== '') ? $keterangan : null,
                'nominal'       => (int) $nominal,
                'diskon'        => $diskon,
                'jatuh_tempo'   => $jatuh_tempo ? $jatuh_tempo : null
            );
        }

        foreach (array_chunk($baris, 200) as $paket) {
            $this->db->insert_batch('cbt_tagihan', $paket);
        }
        $hasil['dibuat'] = count($baris);

        return $hasil;
    }

    // =====================================================================
    // DAFTAR TAGIHAN
    // =====================================================================

    // Filter: id, siswa_id, jenis_id, bulan, tahun,
    //         status (belum | cicilan | lunas | tunggakan)
    // "tunggakan" = belum + cicilan (semua yang belum lunas)
    public function list_tagihan($filter = array()) {
        $sql = "SELECT t.*, s.nis, s.nama,
                       j.nama AS jenis_nama, j.kode AS jenis_kode, j.boleh_cicil,
                       (SELECT COALESCE(SUM(p.jumlah_bayar), 0)
                          FROM cbt_pembayaran_spp p
                         WHERE p.tagihan_id = t.id AND p.status_pembayaran = 'success') AS terbayar,
                       (SELECT COALESCE(SUM(p3.jumlah_bayar), 0)
                          FROM cbt_pembayaran_spp p3
                         WHERE p3.tagihan_id = t.id AND p3.status_pembayaran = 'pending'
                           AND p3.metode_pembayaran = 'transfer_manual') AS pending_manual,
                       (SELECT COUNT(*) FROM cbt_pembayaran_spp p2
                         WHERE p2.tagihan_id = t.id) AS jml_pembayaran
                FROM cbt_tagihan t
                JOIN cbt_jenis_tagihan j ON j.id = t.jenis_id
                LEFT JOIN master_siswa s ON s.id_siswa = t.siswa_id
                WHERE 1 = 1";
        $bind = array();

        if (!empty($filter['id'])) {
            $sql .= " AND t.id = ?";
            $bind[] = (int) $filter['id'];
        }
        if (!empty($filter['siswa_id'])) {
            $sql .= " AND t.siswa_id = ?";
            $bind[] = (int) $filter['siswa_id'];
        }
        if (!empty($filter['jenis_id'])) {
            $sql .= " AND t.jenis_id = ?";
            $bind[] = (int) $filter['jenis_id'];
        }
        if (!empty($filter['bulan'])) {
            $sql .= " AND t.periode_bulan = ?";
            $bind[] = $filter['bulan'];
        }
        if (!empty($filter['tahun'])) {
            $sql .= " AND t.periode_tahun = ?";
            $bind[] = (int) $filter['tahun'];
        }
        $sql .= " ORDER BY t.created_at DESC, s.nama ASC";

        $rows  = $this->db->query($sql, $bind)->result();
        $hasil = array();

        foreach ($rows as $r) {
            $r->nominal_bersih = max(0, (int) $r->nominal - (int) $r->diskon);
            $r->terbayar       = (int) $r->terbayar;
            $r->pending_manual = (int) $r->pending_manual;
            $r->sisa           = max(0, $r->nominal_bersih - $r->terbayar);
            $r->gratis         = ($r->nominal_bersih == 0);

            if ($r->terbayar >= $r->nominal_bersih) {
                $r->status = 'lunas';
            } elseif ($r->terbayar > 0) {
                $r->status = 'cicilan';
            } else {
                $r->status = 'belum';
            }

            if (!empty($filter['status'])) {
                if ($filter['status'] == 'tunggakan') {
                    if ($r->status == 'lunas') continue;
                } elseif ($r->status !== $filter['status']) {
                    continue;
                }
            }
            $hasil[] = $r;
        }
        return $hasil;
    }

    // Satu tagihan lengkap dengan perhitungan status (atau NULL)
    public function get_tagihan($id) {
        $rows = $this->list_tagihan(array('id' => (int) $id));
        return $rows ? $rows[0] : null;
    }

    // Tagihan milik satu siswa; yang belum lunas ditampilkan lebih dulu
    public function tagihan_siswa($siswa_id) {
        $rows = $this->list_tagihan(array('siswa_id' => (int) $siswa_id));
        $urut = array('belum' => 0, 'cicilan' => 1, 'lunas' => 2);
        usort($rows, function ($a, $b) use ($urut) {
            if ($urut[$a->status] !== $urut[$b->status]) {
                return $urut[$a->status] - $urut[$b->status];
            }
            return (int) $a->id - (int) $b->id;
        });
        return $rows;
    }

    // Hapus tagihan hanya jika belum ada pembayaran apa pun yang menempel
    public function hapus_tagihan($id) {
        $id = (int) $id;
        $this->db->where('tagihan_id', $id);
        if ($this->db->count_all_results('cbt_pembayaran_spp') > 0) {
            return false;
        }
        $this->db->where('id', $id);
        $this->db->delete('cbt_tagihan');
        return $this->db->affected_rows() > 0;
    }
}
