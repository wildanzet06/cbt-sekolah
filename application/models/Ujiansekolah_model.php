<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ujian Sekolah — Daftar Ujian (PRD: docs/PRD-UJIAN-SIMPEL.md)
 *
 * Model ini adalah file BARU. Controller/model GarudaCBT yang sudah ada
 * (dan ter-obfuscate) tidak diubah. Semua penulisan data memakai tabel
 * eksisting ditambah tiga kolom aditif dan satu tabel catatan kecil:
 *
 *   cbt_ruang.kapasitas              (default 35)
 *   cbt_jadwal.token_ujian           (token per ujian)
 *   cbt_jadwal.created_by            (pembuat ujian, NULL = admin)
 *   cbt_jadwal_meta                  (catatan draf tanpa kumpulan soal)
 *
 * Migrasi dijalankan otomatis (idempoten) saat model pertama kali dimuat.
 */
class Ujiansekolah_model extends CI_Model
{
    const T_META = 'cbt_jadwal_meta';

    /** Batas default yang dipakai PRD. */
    const KAPASITAS_DEFAULT = 35;
    const DURASI_MIN = 15;
    const DURASI_MAX = 240;
    const TOKEN_PANJANG = 6;

    const STATUS_LABEL = [
        'draf'        => 'Draf',
        'belum_siap'  => 'Belum siap',
        'siap'        => 'Siap',
        'berlangsung' => 'Berlangsung',
        'selesai'     => 'Selesai',
        'ditutup'     => 'Ditutup'
    ];

    /** Cache sederhana per request. */
    private $cacheStatPeserta = [];
    private $cachePengawas    = [];
    private $cacheSoal        = [];

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    /* ==================================================================
     * FASE 0 — migrasi aditif
     * ================================================================ */

    private function ensureSchema()
    {
        if (!$this->db->table_exists(self::T_META)) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `" . self::T_META . "` (
                `id_jadwal` INT NOT NULL,
                `id_mapel`  INT NULL DEFAULT NULL,
                `kelas`     VARCHAR(255) NULL DEFAULT NULL,
                `catatan`   VARCHAR(255) NULL DEFAULT NULL,
                `updated`   DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_jadwal`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$this->db->field_exists('kapasitas', 'cbt_ruang')) {
            $this->db->query('ALTER TABLE `cbt_ruang` ADD COLUMN `kapasitas` INT NOT NULL DEFAULT ' . self::KAPASITAS_DEFAULT);
        }
        if (!$this->db->field_exists('token_ujian', 'cbt_jadwal')) {
            $this->db->query('ALTER TABLE `cbt_jadwal` ADD COLUMN `token_ujian` VARCHAR(10) NULL DEFAULT NULL');
        }
        if (!$this->db->field_exists('created_by', 'cbt_jadwal')) {
            $this->db->query('ALTER TABLE `cbt_jadwal` ADD COLUMN `created_by` INT NULL DEFAULT NULL');
        }
    }

    /* ==================================================================
     * Master: TP, semester, guru, mapel/kelas
     * ================================================================ */

    public function activeTp()
    {
        return $this->db->get_where('master_tp', ['active' => 1])->row();
    }

    public function activeSmt()
    {
        return $this->db->get_where('master_smt', ['active' => 1])->row();
    }

    public function guruByUser($id_user)
    {
        return $this->db->get_where('master_guru', ['id_user' => $id_user])->row();
    }

    public function guruList()
    {
        return $this->db->select('id_guru, nama_guru, nip')
            ->order_by('nama_guru', 'ASC')->get('master_guru')->result();
    }

    /**
     * Pasangan mapel => [id_kelas, ...] yang diajar guru pada TP/semester aktif.
     * Format sama dengan yang dipakai menu Nilai Manual.
     */
    public function pairsGuru($id_guru, $id_tp, $id_smt)
    {
        $rows = $this->db->get_where('jabatan_guru', [
            'id_guru' => (int)$id_guru, 'id_tp' => (int)$id_tp, 'id_smt' => (int)$id_smt
        ])->result();

        $pairs = [];
        foreach ($rows as $row) {
            if (empty($row->mapel_kelas)) {
                continue;
            }
            $list = @unserialize($row->mapel_kelas, ['allowed_classes' => ['stdClass']]);
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $m) {
                $m = (array)$m;
                if (!isset($m['id_mapel'])) {
                    continue;
                }
                $idm = (int)$m['id_mapel'];
                if (!isset($pairs[$idm])) {
                    $pairs[$idm] = [];
                }
                $kls = isset($m['kelas_mapel']) ? (array)$m['kelas_mapel'] : [];
                foreach ($kls as $k) {
                    $k = (array)$k;
                    if (isset($k['kelas']) && $k['kelas'] !== '' && $k['kelas'] !== null) {
                        $pairs[$idm][] = (int)$k['kelas'];
                    }
                }
                $pairs[$idm] = array_values(array_unique($pairs[$idm]));
            }
        }
        return $pairs;
    }

    public function kelasList($id_tp, $id_smt)
    {
        $rows = $this->db->select('k.id_kelas, k.nama_kelas, k.level_id, l.level')
            ->from('master_kelas k')
            ->join('level_kelas l', 'l.id_level = k.level_id', 'left')
            ->where('k.id_tp', (int)$id_tp)
            ->where('k.id_smt', (int)$id_smt)
            ->order_by('k.nama_kelas', 'ASC')
            ->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->id_kelas] = $r;
        }
        return $out;
    }

    public function mapelList()
    {
        $rows = $this->db->select('id_mapel, nama_mapel')->order_by('nama_mapel', 'ASC')
            ->get('master_mapel')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->id_mapel] = $r->nama_mapel;
        }
        return $out;
    }

    /**
     * Opsi mapel/kelas yang boleh dipakai pembuat ujian.
     * Admin: semua. Guru: hanya mapel+kelas pada jabatan_guru.
     * Return ['mapel' => [id => nama], 'kelas' => [id => nama_kelas], 'pairs' => [id_mapel => [id_kelas]]]
     */
    public function opsi($id_tp, $id_smt, $id_guru = null)
    {
        $kelasObj = $this->kelasList($id_tp, $id_smt);
        $kelas = [];
        foreach ($kelasObj as $id => $k) {
            $kelas[$id] = $k->nama_kelas;
        }
        $mapel = $this->mapelList();

        if ($id_guru === null) {
            $pairs = [];
            foreach ($mapel as $idm => $_) {
                $pairs[$idm] = array_keys($kelas);
            }
            return ['mapel' => $mapel, 'kelas' => $kelas, 'pairs' => $pairs];
        }

        $pairs = [];
        $mapelOut = [];
        foreach ($this->pairsGuru($id_guru, $id_tp, $id_smt) as $idm => $kls) {
            $kls = array_values(array_filter($kls, function ($k) use ($kelas) {
                return isset($kelas[$k]);
            }));
            if (isset($mapel[$idm]) && count($kls) > 0) {
                $mapelOut[$idm] = $mapel[$idm];
                $pairs[$idm] = $kls;
            }
        }
        return ['mapel' => $mapelOut, 'kelas' => $kelas, 'pairs' => $pairs];
    }

    public function guruBoleh($id_guru, $id_tp, $id_smt, $id_mapel, array $kelas)
    {
        $pairs = $this->pairsGuru($id_guru, $id_tp, $id_smt);
        if (!isset($pairs[(int)$id_mapel])) {
            return false;
        }
        foreach ($kelas as $k) {
            if (in_array((int)$k, $pairs[(int)$id_mapel], true)) {
                return true;
            }
        }
        return false;
    }

    public function jenisList()
    {
        return $this->db->order_by('id_jenis', 'ASC')->get('cbt_jenis')->result();
    }

    public function jenisByKode($kode)
    {
        return $this->db->get_where('cbt_jenis', ['kode_jenis' => $kode])->row();
    }

    /* ==================================================================
     * Ruang, sesi, kapasitas
     * ================================================================ */

    public function ruangList()
    {
        return $this->db->order_by('id_ruang', 'ASC')->get('cbt_ruang')->result();
    }

    /** Sesi 1 = sesi aktif dengan waktu mulai paling awal (pola sekolah: serentak). */
    public function sesiSatu()
    {
        $row = $this->db->order_by('waktu_mulai', 'ASC')->order_by('id_sesi', 'ASC')
            ->get_where('cbt_sesi', ['aktif' => 1])->row();
        if (!$row) {
            $row = $this->db->order_by('id_sesi', 'ASC')->get('cbt_sesi')->row();
        }
        return $row;
    }

    /**
     * Pastikan sudah ada sesi dan ruang dasar, supaya sekolah tidak perlu
     * mengatur "Sesi & Jam" / "Ruang" lebih dulu. Cukup klik Bagi Otomatis.
     */
    public function pastikanSesiDanRuang()
    {
        $jml_sesi = (int) $this->db->count_all('cbt_sesi');
        if ($jml_sesi === 0) {
            $this->db->insert('cbt_sesi', [
                'nama_sesi'   => 'Sesi 1',
                'kode_sesi'   => 'S1',
                'waktu_mulai' => '07:30:00',
                'waktu_akhir' => '09:30:00',
                'aktif'       => 1,
            ]);
        }

        $jml_ruang = (int) $this->db->count_all('cbt_ruang');
        if ($jml_ruang === 0) {
            $this->db->insert('cbt_ruang', [
                'nama_ruang' => 'Ruang 1',
                'kode_ruang' => 'R1',
                'kapasitas'  => 35,
            ]);
        }
    }
    public function sesiList()
    {
        return $this->db->order_by('waktu_mulai', 'ASC')->order_by('id_sesi', 'ASC')
            ->get('cbt_sesi')->result();
    }

    public function kapasitasTotal()
    {
        $row = $this->db->select('COUNT(*) jml, IFNULL(SUM(kapasitas),0) total')
            ->get('cbt_ruang')->row();
        return [
            'ruang'    => $row ? (int)$row->jml : 0,
            'kapasitas' => $row ? (int)$row->total : 0
        ];
    }

    public function setKapasitas($id_ruang, $kapasitas)
    {
        $kapasitas = (int)$kapasitas;
        if ($kapasitas < 1 || $kapasitas > 500) {
            return false;
        }
        $this->db->where('id_ruang', (int)$id_ruang)->update('cbt_ruang', ['kapasitas' => $kapasitas]);
        return true;
    }

    /* ==================================================================
     * Kesiapan ujian (dihitung, tidak disimpan)
     * ================================================================ */

    private function jumlahSoal($id_bank)
    {
        $id_bank = (int)$id_bank;
        if ($id_bank <= 0) {
            return 0;
        }
        if (!isset($this->cacheSoal[$id_bank])) {
            $row = $this->db->select('COUNT(*) c')->get_where('cbt_soal', ['bank_id' => $id_bank])->row();
            $this->cacheSoal[$id_bank] = $row ? (int)$row->c : 0;
        }
        return $this->cacheSoal[$id_bank];
    }

    private function statPeserta($id_tp, $id_smt, array $kelas)
    {
        $kelas = array_values(array_filter(array_map('intval', $kelas)));
        if (count($kelas) === 0) {
            return ['total' => 0, 'terdaftar' => 0, 'ruang' => 0];
        }
        $key = md5((int)$id_tp . '-' . (int)$id_smt . '-' . implode(',', $kelas));
        if (isset($this->cacheStatPeserta[$key])) {
            return $this->cacheStatPeserta[$key];
        }
        $in = implode(',', $kelas);

        $row = $this->db->query(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN ss.siswa_id IS NULL THEN 0 ELSE 1 END) AS terdaftar
               FROM kelas_siswa ks
               LEFT JOIN cbt_sesi_siswa ss
                      ON ss.siswa_id = ks.id_siswa
                     AND ss.tp_id = ks.id_tp
                     AND ss.smt_id = ks.id_smt
              WHERE ks.id_tp = ? AND ks.id_smt = ? AND ks.id_kelas IN (' . $in . ')',
            [(int)$id_tp, (int)$id_smt]
        )->row();

        $ruang = $this->db->query(
            'SELECT COUNT(DISTINCT ss.ruang_id) AS r
               FROM cbt_sesi_siswa ss
               JOIN kelas_siswa ks
                 ON ks.id_siswa = ss.siswa_id
                AND ks.id_tp = ss.tp_id
                AND ks.id_smt = ss.smt_id
              WHERE ss.tp_id = ? AND ss.smt_id = ? AND ks.id_kelas IN (' . $in . ')',
            [(int)$id_tp, (int)$id_smt]
        )->row();

        $stat = [
            'total'     => $row ? (int)$row->total : 0,
            'terdaftar' => $row ? (int)$row->terdaftar : 0,
            'ruang'     => $ruang ? (int)$ruang->r : 0
        ];
        $this->cacheStatPeserta[$key] = $stat;
        return $stat;
    }

    private function jumlahRuangPengawas($id_jadwal)
    {
        $id_jadwal = (int)$id_jadwal;
        if (!isset($this->cachePengawas[$id_jadwal])) {
            $row = $this->db->query(
                'SELECT COUNT(DISTINCT id_ruang) AS r FROM cbt_pengawas WHERE id_jadwal = ?',
                [(string)$id_jadwal]
            )->row();
            $this->cachePengawas[$id_jadwal] = $row ? (int)$row->r : 0;
        }
        return $this->cachePengawas[$id_jadwal];
    }

    /** id_kelas ujian: dari bank_kelas (array terserialisasi) atau tabel meta. */
    public function kelasUjian($row)
    {
        $kelas = [];
        if (!empty($row->id_bank) && !empty($row->bank_kelas)) {
            $list = @unserialize($row->bank_kelas, ['allowed_classes' => ['stdClass']]);
            if (is_array($list)) {
                foreach ($list as $k) {
                    $k = (array)$k;
                    if (isset($k['kelas_id']) && $k['kelas_id'] !== null && $k['kelas_id'] !== '') {
                        $kelas[] = (int)$k['kelas_id'];
                    }
                }
            }
        }
        if (count($kelas) === 0 && !empty($row->meta_kelas)) {
            foreach (explode(',', $row->meta_kelas) as $k) {
                if (trim($k) !== '') {
                    $kelas[] = (int)$k;
                }
            }
        }
        return array_values(array_unique($kelas));
    }

    public function mapelUjian($row)
    {
        if (!empty($row->bank_mapel_id)) {
            return (int)$row->bank_mapel_id;
        }
        return !empty($row->meta_mapel) ? (int)$row->meta_mapel : 0;
    }

    /**
     * Hitung 5 chip kesiapan untuk satu baris jadwal.
     * Return array dengan kunci: soal, peserta, ruang, pengawas, token, siap, kurang.
     */
    public function kesiapan($row, $id_tp, $id_smt)
    {
        $kelas = $this->kelasUjian($row);
        $stat = $this->statPeserta($id_tp, $id_smt, $kelas);
        $kap = $this->kapasitasTotal();

        $jmlSoal = $this->jumlahSoal($row->id_bank);
        $ruangPakai = $stat['ruang'];
        $ruangPengawas = $this->jumlahRuangPengawas($row->id_jadwal);
        // "≥ 1 guru per ruang": semua ruang yang dipakai harus punya pengawas.
        // Selama peserta belum dibagi, patokan ruangnya seluruh ruang.
        $butuhPengawas = $ruangPakai > 0 ? $ruangPakai : (int)$kap['ruang'];

        $chip = [
            'soal'      => $jmlSoal >= 1,
            'peserta'   => $stat['total'] > 0 && $stat['terdaftar'] >= $stat['total'],
            'ruang'     => $kap['ruang'] > 0 && $ruangPakai >= $kap['ruang'],
            'pengawas'  => $ruangPengawas > 0 && $ruangPengawas >= $butuhPengawas,
            'token'     => (int)$row->token === 1 && strlen(trim((string)$row->token_ujian)) >= self::TOKEN_PANJANG
        ];

        $kurang = [];
        if (!$chip['soal']) $kurang[] = 'soal';
        if (!$chip['peserta']) $kurang[] = 'peserta';
        if (!$chip['ruang']) $kurang[] = 'ruang';
        if (!$chip['pengawas']) $kurang[] = 'pengawas';
        if (!$chip['token']) $kurang[] = 'token';

        return [
            'chip'            => $chip,
            'siap'            => count($kurang) === 0,
            'kurang'          => $kurang,
            'jumlah_soal'     => $jmlSoal,
            'total_siswa'     => $stat['total'],
            'terdaftar'       => $stat['terdaftar'],
            'ruang_pakai'     => $ruangPakai,
            'ruang_total'     => $kap['ruang'],
            'ruang_pengawas'  => $ruangPengawas,
            'kelas'           => $kelas
        ];
    }

    /**
     * Status kartu: draf / belum_siap / siap / berlangsung / selesai / ditutup.
     * Dihitung dari flag status + tanggal, tanpa kolom baru.
     */
    public function statusKartu($row, $siap, $today = null)
    {
        $today = $today ?: date('Y-m-d');
        $tgl = substr((string)$row->tgl_mulai, 0, 10);
        $aktif = (int)$row->status === 1;

        if ($aktif) {
            if ($tgl > $today) {
                return $siap ? 'siap' : 'belum_siap';
            }
            if ($tgl === $today) {
                return 'berlangsung';
            }
            return 'selesai';
        }
        if ($tgl < $today) {
            return 'ditutup';
        }
        return 'draf';
    }

    /* ==================================================================
     * Daftar ujian
     * ================================================================ */

    private function selectJadwal()
    {
        $this->db->select('j.*, '
            . 'b.bank_kelas, b.bank_mapel_id, b.bank_nama, b.kkm, b.jml_soal, b.jml_esai, b.bank_guru_id, '
            . 'jn.nama_jenis, jn.kode_jenis, '
            . 'IFNULL(m1.nama_mapel, m2.nama_mapel) AS nama_mapel, '
            . 'g.nama_guru AS pembuat, '
            . 'meta.id_mapel AS meta_mapel, meta.kelas AS meta_kelas, meta.catatan AS catatan', false);
        $this->db->from('cbt_jadwal j');
        $this->db->join('cbt_bank_soal b', 'b.id_bank = j.id_bank', 'left');
        $this->db->join('cbt_jenis jn', 'jn.id_jenis = j.id_jenis', 'left');
        $this->db->join('master_mapel m1', 'm1.id_mapel = b.bank_mapel_id', 'left');
        $this->db->join(self::T_META . ' meta', 'meta.id_jadwal = j.id_jadwal', 'left');
        $this->db->join('master_mapel m2', 'm2.id_mapel = meta.id_mapel', 'left');
        $this->db->join('master_guru g', 'g.id_guru = j.created_by', 'left');
    }

    public function getUjian($id_jadwal, $id_tp = null, $id_smt = null)
    {
        $this->selectJadwal();
        $this->db->where('j.id_jadwal', (int)$id_jadwal);
        if ($id_tp !== null) {
            $this->db->where('j.id_tp', (int)$id_tp);
        }
        if ($id_smt !== null) {
            $this->db->where('j.id_smt', (int)$id_smt);
        }
        return $this->db->get()->row();
    }

    /**
     * Daftar ujian satu semester + kesiapan + status kartu.
     * $id_guru != null  → hanya mapel/kelas yang diajar guru tersebut.
     */
    public function listUjian($id_tp, $id_smt, $id_guru = null)
    {
        $this->selectJadwal();
        $this->db->where('j.id_tp', (int)$id_tp);
        $this->db->where('j.id_smt', (int)$id_smt);
        $this->db->order_by('j.tgl_mulai', 'ASC');
        $this->db->order_by('j.id_jadwal', 'ASC');
        $rows = $this->db->get()->result();

        $pairs = $id_guru === null ? null : $this->pairsGuru($id_guru, $id_tp, $id_smt);
        $today = date('Y-m-d');
        $out = [];

        foreach ($rows as $r) {
            $kes = $this->kesiapan($r, $id_tp, $id_smt);

            if ($pairs !== null) {
                $id_mapel = $this->mapelUjian($r);
                $boleh = isset($pairs[$id_mapel]) && count(array_intersect($pairs[$id_mapel], $kes['kelas'])) > 0;
                if (!$boleh) {
                    continue;
                }
            }

            $r->kesiapan = $kes;
            $r->label_kelas = $this->labelKelas($kes['kelas']);
            $r->status_kode = $this->statusKartu($r, $kes['siap'], $today);
            $r->status_label = self::STATUS_LABEL[$r->status_kode];
            $r->milik_saya = $id_guru !== null && (int)$r->created_by === (int)$id_guru;
            $out[] = $r;
        }
        return $out;
    }

    /** Ujian lain pada tanggal yang sama (untuk blok "bersamaan" & salin pengawas). */
    public function ujianPadaTanggal($tanggal, $exclude_id = 0, $id_tp = null, $id_smt = null)
    {
        $this->selectJadwal();
        $this->db->where('j.tgl_mulai', $tanggal);
        if ($exclude_id) {
            $this->db->where('j.id_jadwal !=', (int)$exclude_id);
        }
        if ($id_tp !== null) {
            $this->db->where('j.id_tp', (int)$id_tp);
        }
        if ($id_smt !== null) {
            $this->db->where('j.id_smt', (int)$id_smt);
        }
        $this->db->order_by('j.id_jadwal', 'ASC');
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            $r->kelas = $this->kelasUjian($r);
            $r->label_kelas = $this->labelKelas($r->kelas);
            $r->pengawas = $this->pengawasMap($r->id_jadwal);
        }
        return $rows;
    }

    public function labelKelas(array $kelas)
    {
        if (count($kelas) === 0) {
            return '';
        }
        $nama = [];
        foreach ($kelas as $k) {
            $row = $this->db->select('nama_kelas')->get_where('master_kelas', ['id_kelas' => (int)$k])->row();
            if ($row) {
                $nama[] = $row->nama_kelas;
            }
        }
        $nama = array_values(array_unique($nama));
        return implode(', ', $nama);
    }

    /** Pengawas per ruang: [id_ruang => id_guru] untuk satu jadwal. */
    public function pengawasMap($id_jadwal)
    {
        $rows = $this->db->get_where('cbt_pengawas', ['id_jadwal' => (string)(int)$id_jadwal])->result();
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->id_ruang] = (int)$r->id_guru;
        }
        return $out;
    }

    public function pengawasDetail($id_jadwal)
    {
        $this->db->select('p.id_ruang, p.id_guru, p.id_sesi, r.nama_ruang, r.kode_ruang, g.nama_guru', false);
        $this->db->from('cbt_pengawas p');
        $this->db->join('cbt_ruang r', 'r.id_ruang = p.id_ruang', 'left');
        $this->db->join('master_guru g', 'g.id_guru = p.id_guru', 'left');
        $this->db->where('p.id_jadwal', (string)(int)$id_jadwal);
        $this->db->order_by('p.id_ruang', 'ASC');
        return $this->db->get()->result();
    }

    /* ==================================================================
     * Token per ujian
     * ================================================================ */

    /** Karakter tanpa huruf/angka yang mirip (0/O, 1/I) agar mudah dibaca siswa. */
    private function tokenAlphabet()
    {
        return 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    }

    public function buatToken()
    {
        $ab = $this->tokenAlphabet();
        $maks = strlen($ab) - 1;
        $token = '';
        for ($i = 0; $i < self::TOKEN_PANJANG; $i++) {
            $token .= $ab[random_int(0, $maks)];
        }
        return $token;
    }

    public function tokenDipakai($tanggal, $exclude_id = 0)
    {
        $this->db->select('j.id_jadwal, j.token_ujian, IFNULL(m1.nama_mapel, m2.nama_mapel) AS nama_mapel', false);
        $this->db->from('cbt_jadwal j');
        $this->db->join('cbt_bank_soal b', 'b.id_bank = j.id_bank', 'left');
        $this->db->join('master_mapel m1', 'm1.id_mapel = b.bank_mapel_id', 'left');
        $this->db->join(self::T_META . ' meta', 'meta.id_jadwal = j.id_jadwal', 'left');
        $this->db->join('master_mapel m2', 'm2.id_mapel = meta.id_mapel', 'left');
        $this->db->where('j.tgl_mulai', $tanggal);
        $this->db->where("j.token_ujian IS NOT NULL AND j.token_ujian <> ''", null, false);
        if ($exclude_id) {
            $this->db->where('j.id_jadwal !=', (int)$exclude_id);
        }
        $this->db->order_by('j.id_jadwal', 'ASC');
        return $this->db->get()->result();
    }

    /** Token unik untuk tanggal tertentu (dipakai tombol "Acak ulang"). */
    public function tokenUnik($tanggal, $exclude_id = 0)
    {
        $dipakai = [];
        foreach ($this->tokenDipakai($tanggal, $exclude_id) as $t) {
            $dipakai[strtoupper(trim($t->token_ujian))] = true;
        }
        for ($coba = 0; $coba < 60; $coba++) {
            $token = $this->buatToken();
            if (!isset($dipakai[$token])) {
                return $token;
            }
        }
        return $this->buatToken();
    }

    /** Token yang berlaku pada tanggal tertentu (panel "Token aktif"). */
    public function tokenAktif($tanggal, $id_tp, $id_smt)
    {
        $this->db->select('j.id_jadwal, j.token_ujian, IFNULL(m1.nama_mapel, m2.nama_mapel) AS nama_mapel,
                           jn.kode_jenis, j.status', false);
        $this->db->from('cbt_jadwal j');
        $this->db->join('cbt_bank_soal b', 'b.id_bank = j.id_bank', 'left');
        $this->db->join('cbt_jenis jn', 'jn.id_jenis = j.id_jenis', 'left');
        $this->db->join('master_mapel m1', 'm1.id_mapel = b.bank_mapel_id', 'left');
        $this->db->join(self::T_META . ' meta', 'meta.id_jadwal = j.id_jadwal', 'left');
        $this->db->join('master_mapel m2', 'm2.id_mapel = meta.id_mapel', 'left');
        $this->db->where('j.tgl_mulai', $tanggal);
        $this->db->where('j.id_tp', (int)$id_tp);
        $this->db->where('j.id_smt', (int)$id_smt);
        $this->db->where('j.token', 1);
        $this->db->where("j.token_ujian IS NOT NULL AND j.token_ujian <> ''", null, false);
        $this->db->order_by('j.id_jadwal', 'ASC');
        return $this->db->get()->result();
    }

    /* ==================================================================
     * Kumpulan soal (bank soal)
     * ================================================================ */

    /**
     * Bank soal yang bisa dipilih pembuat ujian.
     * Sekaligus menyertakan jumlah soal, dipakai ujian mana, dan jumlah siswa.
     */
    public function bankPilihan($id_tp, $id_smt, $id_mapel = null, $id_guru = null)
    {
        $this->db->select('b.*, m.nama_mapel, g.nama_guru,
            (SELECT COUNT(*) FROM cbt_soal s WHERE s.bank_id = b.id_bank) AS jml,
            (SELECT COUNT(*) FROM cbt_soal s WHERE s.bank_id = b.id_bank AND s.jenis = 1) AS jml_pg,
            (SELECT COUNT(*) FROM cbt_soal s WHERE s.bank_id = b.id_bank AND s.jenis = 5) AS jml_esai,
            (SELECT COUNT(*) FROM cbt_jadwal j WHERE j.id_bank = b.id_bank) AS dipakai', false);
        $this->db->from('cbt_bank_soal b');
        $this->db->join('master_mapel m', 'm.id_mapel = b.bank_mapel_id', 'left');
        $this->db->join('master_guru g', 'g.id_guru = b.bank_guru_id', 'left');
        $this->db->where('b.id_tp', (int)$id_tp);
        $this->db->where('b.id_smt', (int)$id_smt);
        if ($id_mapel !== null) {
            $this->db->where('b.bank_mapel_id', (int)$id_mapel);
        }
        $this->db->order_by('b.id_bank', 'DESC');
        $rows = $this->db->get()->result();

        $pairs = $id_guru === null ? null : $this->pairsGuru($id_guru, $id_tp, $id_smt);
        $out = [];
        foreach ($rows as $r) {
            $r->kelas = $this->kelasUjian($r);
            $r->label_kelas = $this->labelKelas($r->kelas);
            $r->pakai_label = '';
            if ((int)$r->dipakai > 0) {
                $pakai = $this->pakaiBank($r->id_bank);
                $r->pakai_label = $pakai;
            }
            if ($pairs !== null) {
                $idm = (int)$r->bank_mapel_id;
                if (!isset($pairs[$idm])) {
                    continue;
                }
                if (count(array_intersect($pairs[$idm], $r->kelas)) === 0) {
                    continue;
                }
            }
            $r->jumlah_siswa = $this->jumlahSiswaKelas($id_tp, $id_smt, $r->kelas);
            $out[] = $r;
        }
        return $out;
    }

    public function getBank($id_bank)
    {
        return $this->db->select('b.*, m.nama_mapel')->from('cbt_bank_soal b')
            ->join('master_mapel m', 'm.id_mapel = b.bank_mapel_id', 'left')
            ->where('b.id_bank', (int)$id_bank)->get()->row();
    }

    public function pakaiBank($id_bank)
    {
        $this->db->select('j.id_jadwal, j.tgl_mulai, jn.nama_jenis, jn.kode_jenis')->from('cbt_jadwal j')
            ->join('cbt_jenis jn', 'jn.id_jenis = j.id_jenis', 'left')
            ->where('j.id_bank', (int)$id_bank)->order_by('j.id_jadwal', 'ASC');
        $rows = $this->db->get()->result();
        $label = [];
        foreach ($rows as $r) {
            $label[] = trim(($r->kode_jenis ? $r->kode_jenis . ' ' : '') . date('d M Y', strtotime($r->tgl_mulai)));
        }
        return implode(' · ', $label);
    }

    public function jadwalPakaiBank($id_bank, $exclude_id = 0)
    {
        $this->db->where('id_bank', (int)$id_bank);
        if ($exclude_id) {
            $this->db->where('id_jadwal !=', (int)$exclude_id);
        }
        return $this->db->get('cbt_jadwal')->row();
    }

    public function jumlahSiswaKelas($id_tp, $id_smt, array $kelas)
    {
        $kelas = array_values(array_filter(array_map('intval', $kelas)));
        if (count($kelas) === 0) {
            return 0;
        }
        $row = $this->db->select('COUNT(*) c')->where('id_tp', (int)$id_tp)->where('id_smt', (int)$id_smt)
            ->where_in('id_kelas', $kelas)->get('kelas_siswa')->row();
        return $row ? (int)$row->c : 0;
    }

    /* ==================================================================
     * Simpan ujian + pengawas
     * ================================================================ */

    /**
     * Insert/update cbt_jadwal. $id = null berarti ujian baru.
     * Return id_jadwal atau false.
     */
    public function simpanUjian(array $data, $id = null)
    {
        if ($id === null) {
            if (!isset($data['time_create'])) {
                $data['time_create'] = date('Y-m-d H:i:s');
            }
            $this->db->insert('cbt_jadwal', $data);
            return $this->db->insert_id();
        }
        $this->db->where('id_jadwal', (int)$id)->update('cbt_jadwal', $data);
        return $this->db->affected_rows() >= 0 ? (int)$id : false;
    }

    public function simpanMeta($id_jadwal, $id_mapel, array $kelas, $catatan = null)
    {
        $this->db->replace(self::T_META, [
            'id_jadwal' => (int)$id_jadwal,
            'id_mapel'  => $id_mapel ? (int)$id_mapel : null,
            'kelas'     => count($kelas) ? implode(',', array_map('intval', $kelas)) : null,
            'catatan'   => $catatan !== null && $catatan !== '' ? mb_substr($catatan, 0, 250) : null
        ]);
        return true;
    }

    public function hapusMeta($id_jadwal)
    {
        $this->db->delete(self::T_META, ['id_jadwal' => (int)$id_jadwal]);
    }

    /**
     * Tulis ulang pengawas satu jadwal: satu baris per ruang.
     * $perRuang = [id_ruang => id_guru]
     */
    public function simpanPengawas($id_jadwal, $id_tp, $id_smt, array $perRuang, $id_sesi)
    {
        $id_jadwal = (int)$id_jadwal;
        $this->db->delete('cbt_pengawas', ['id_jadwal' => (string)$id_jadwal]);
        $this->cachePengawas[$id_jadwal] = 0;
        if (count($perRuang) === 0) {
            return 0;
        }
        $n = 0;
        foreach ($perRuang as $id_ruang => $id_guru) {
            if (!$id_guru) {
                continue;
            }
            $this->db->replace('cbt_pengawas', [
                'id_pengawas' => (string)$id_tp . (string)$id_smt . (string)$id_jadwal
                    . (string)(int)$id_ruang . (string)(int)$id_sesi,
                'id_jadwal'   => (string)$id_jadwal,
                'id_tp'       => (int)$id_tp,
                'id_smt'      => (int)$id_smt,
                'id_ruang'    => (string)(int)$id_ruang,
                'id_sesi'     => (string)(int)$id_sesi,
                'id_guru'     => (string)(int)$id_guru
            ]);
            $n++;
        }
        $this->cachePengawas[$id_jadwal] = $n;
        return $n;
    }

    /** Salin pengawas dari ujian lain di tanggal yang sama (ruang yang sama). */
    public function pengawasDariTanggal($tanggal, $id_tp, $id_smt, $exclude_id = 0)
    {
        $rows = $this->ujianPadaTanggal($tanggal, $exclude_id, $id_tp, $id_smt);
        $map = [];
        foreach ($rows as $r) {
            foreach ($this->pengawasDetail($r->id_jadwal) as $p) {
                $id_ruang = (int)$p->id_ruang;
                if (!isset($map[$id_ruang]) && (int)$p->id_guru > 0) {
                    $map[$id_ruang] = (int)$p->id_guru;
                }
            }
        }
        return $map;
    }

    /** Ubah flag aktif (buka/tutup) satu ujian. */
    public function setStatus($id_jadwal, $aktif)
    {
        $this->db->where('id_jadwal', (int)$id_jadwal)->update('cbt_jadwal', ['status' => $aktif ? 1 : 0]);
        return true;
    }

    /** Apakah ujian sudah punya nilai siswa (menentukan boleh/tidak dihapus). */
    public function adaNilai($id_jadwal)
    {
        $row = $this->db->select('COUNT(*) c')->get_where('cbt_nilai', ['id_jadwal' => (int)$id_jadwal])->row();
        return $row && (int)$row->c > 0;
    }

    public function hapusUjian($id_jadwal)
    {
        $id_jadwal = (int)$id_jadwal;
        $this->db->trans_start();
        $this->db->delete('cbt_pengawas', ['id_jadwal' => (string)$id_jadwal]);
        $this->db->delete('cbt_soal_siswa', ['id_jadwal' => $id_jadwal]);
        $this->db->delete('cbt_durasi_siswa', ['id_jadwal' => $id_jadwal]);
        $this->db->delete('cbt_nilai', ['id_jadwal' => $id_jadwal]);
        $this->db->delete('log_ujian', ['id_jadwal' => $id_jadwal]);
        $this->db->delete(self::T_META, ['id_jadwal' => $id_jadwal]);
        $this->db->delete('cbt_jadwal', ['id_jadwal' => $id_jadwal]);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /* ==================================================================
     * Pemberitahuan ujian buatan guru (keputusan #4)
     * ================================================================ */

    public function notifGuru($id_tp, $id_smt, $hari = 7)
    {
        $this->selectJadwal();
        $this->db->where('j.id_tp', (int)$id_tp);
        $this->db->where('j.id_smt', (int)$id_smt);
        $this->db->where('j.created_by IS NOT NULL', null, false);
        $this->db->where('j.time_create >=', date('Y-m-d H:i:s', strtotime('-' . (int)$hari . ' days')));
        $this->db->order_by('j.time_create', 'DESC');
        $this->db->order_by('j.id_jadwal', 'DESC');
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            $r->kelas = $this->kelasUjian($r);
            $r->label_kelas = $this->labelKelas($r->kelas);
        }
        return $rows;
    }

    public function adaNotifBaru($id_tp, $id_smt, $hari = 7)
    {
        return count($this->notifGuru($id_tp, $id_smt, $hari)) > 0;
    }

    /* ==================================================================
     * FASE 3 — Pembagian peserta otomatis
     * ================================================================ */

    /**
     * Data halaman Pembagian Peserta: daftar kelas + siswa, ruang + isi,
     * sebaran kelas per ruang, dan status nomor peserta.
     */
    public function pembagianData($id_tp, $id_smt)
    {
        $kelas = $this->kelasList($id_tp, $id_smt);
        $ruang = $this->ruangList();
        $sesi = $this->sesiSatu();

        $perKelas = [];
        foreach ($kelas as $id => $k) {
            $perKelas[$id] = [
                'kelas'   => $k,
                'siswa'   => [],
                'ruang'   => [],
                'nomor'   => 0,
                'total'   => 0
            ];
        }

        $siswa = $this->db->select('ks.id_kelas, s.id_siswa, s.nis, s.nama, ss.ruang_id, ss.sesi_id, np.nomor_peserta')
            ->from('kelas_siswa ks')
            ->join('master_siswa s', 's.id_siswa = ks.id_siswa')
            ->join('cbt_sesi_siswa ss', 'ss.siswa_id = ks.id_siswa AND ss.tp_id = ks.id_tp AND ss.smt_id = ks.id_smt', 'left')
            ->join('cbt_nomor_peserta np', 'np.id_siswa = ks.id_siswa AND np.id_tp = ks.id_tp AND np.id_smt = ks.id_smt', 'left')
            ->where('ks.id_tp', (int)$id_tp)
            ->where('ks.id_smt', (int)$id_smt)
            ->order_by('s.nama', 'ASC')
            ->get()->result();

        $terdaftar = 0;
        foreach ($siswa as $s) {
            $id_kelas = (int)$s->id_kelas;
            if (!isset($perKelas[$id_kelas])) {
                continue;
            }
            $perKelas[$id_kelas]['siswa'][] = $s;
            $perKelas[$id_kelas]['total']++;
            if (!empty($s->nomor_peserta)) {
                $perKelas[$id_kelas]['nomor']++;
            }
            if (!empty($s->ruang_id)) {
                $terdaftar++;
                $rid = (int)$s->ruang_id;
                if (!isset($perKelas[$id_kelas]['ruang'][$rid])) {
                    $perKelas[$id_kelas]['ruang'][$rid] = 0;
                }
                $perKelas[$id_kelas]['ruang'][$rid]++;
            }
        }

        $perRuang = [];
        foreach ($ruang as $r) {
            $perRuang[(int)$r->id_ruang] = 0;
        }
        foreach ($perKelas as $pk) {
            foreach ($pk['ruang'] as $rid => $n) {
                if (isset($perRuang[$rid])) {
                    $perRuang[$rid] += $n;
                }
            }
        }

        return [
            'kelas'       => $perKelas,
            'ruang'       => $ruang,
            'per_ruang'   => $perRuang,
            'sesi'        => $sesi,
            'total_siswa' => count($siswa),
            'terdaftar'   => $terdaftar,
            'kapasitas'   => $this->kapasitasTotal()
        ];
    }

    /** Semua siswa aktif pada TP/smt, urut kelas lalu nama. */
    public function semuaSiswa($id_tp, $id_smt)
    {
        return $this->db->select('s.id_siswa, s.nis, s.nama, ks.id_kelas, s.jenis_kelamin')
            ->from('kelas_siswa ks')
            ->join('master_siswa s', 's.id_siswa = ks.id_siswa')
            ->where('ks.id_tp', (int)$id_tp)
            ->where('ks.id_smt', (int)$id_smt)
            ->order_by('ks.id_kelas', 'ASC')
            ->order_by('s.nama', 'ASC')
            ->get()->result();
    }

    /**
     * Selang-seling siswa antar kelas: satu siswa kelas A, satu kelas B, dst.
     * Hasilnya, pembagian berurutan ke ruang tetap merata secara total dan
     * setiap ruang berisi campuran kelas.
     */
    private function selangSelingKelas(array $siswa)
    {
        $perKelas = [];
        foreach ($siswa as $s) {
            $perKelas[(int)$s->id_kelas][] = $s;
        }
        if (count($perKelas) <= 1) {
            return $siswa;
        }
        $maks = 0;
        foreach ($perKelas as $list) {
            $maks = max($maks, count($list));
        }
        $hasil = [];
        for ($i = 0; $i < $maks; $i++) {
            foreach ($perKelas as $list) {
                if (isset($list[$i])) {
                    $hasil[] = $list[$i];
                }
            }
        }
        return $hasil;
    }

    private function levelKelas($id_kelas)
    {
        $row = $this->db->select('k.nama_kelas, l.level')
            ->from('master_kelas k')
            ->join('level_kelas l', 'l.id_level = k.level_id', 'left')
            ->where('k.id_kelas', (int)$id_kelas)->get()->row();
        if (!$row) {
            return ['nama' => '', 'level' => 0];
        }
        return [
            'nama'  => $row->nama_kelas,
            'level' => $row->level !== null ? (int)$row->level : (int)$row->nama_kelas
        ];
    }

    /**
     * Bagi semua siswa ke semua ruang secara merata (Fase 3).
     *
     * Aturan PRD 5.3:
     *  - semua ruang wajib terpakai (kecuali siswa < jumlah ruang);
     *  - dibagi merata: 42 siswa / 5 ruang → 9, 9, 8, 8, 8;
     *  - kapasitas ruang hanya batas aman; jika kurang → tolak.
     *  - menulis cbt_sesi_siswa, cbt_kelas_ruang, cbt_nomor_peserta.
     *
     * Return ['ok'=>bool, 'msg'=>string, 'ringkas'=>array]
     */
    public function bagiOtomatis($id_tp, $id_smt, $semuaRuang = true)
    {
        $id_tp = (int)$id_tp;
        $id_smt = (int)$id_smt;

        $sesi = $this->sesiSatu();
        if (!$sesi) {
            return ['ok' => false, 'msg' => 'Sesi ujian belum diatur. Tambahkan sesi terlebih dahulu.'];
        }

        $ruang = $this->ruangList();
        if (count($ruang) === 0) {
            return ['ok' => false, 'msg' => 'Belum ada ruang ujian.'];
        }

        $siswa = $this->semuaSiswa($id_tp, $id_smt);
        $total = count($siswa);
        if ($total === 0) {
            return ['ok' => false, 'msg' => 'Belum ada siswa pada tahun pelajaran dan semester aktif.'];
        }

        // Selang-seling per kelas, supaya tiap ruang berisi siswa dari kelas
        // berbeda (asumsi PRD: ujian paralel berbagi ruang) dan tiap kelas
        // tetap tersebar ke semua ruang.
        $siswa = $this->selangSelingKelas($siswa);

        $kapasitas = 0;
        foreach ($ruang as $r) {
            $kapasitas += max(1, (int)$r->kapasitas);
        }
        if ($total > $kapasitas) {
            return [
                'ok'  => false,
                'msg' => 'Kapasitas ruang kurang (' . $kapasitas . ' kursi untuk ' . $total
                    . ' siswa). Tambah ruang atau naikkan kapasitas.'
            ];
        }

        // Tentukan ruang yang benar-benar dipakai.
        if ($semuaRuang) {
            $dipakai = $ruang;
        } else {
            $dipakai = [];
            $sisa = $total;
            foreach ($ruang as $r) {
                if ($sisa <= 0) {
                    break;
                }
                $dipakai[] = $r;
                $sisa -= max(1, (int)$r->kapasitas);
            }
        }
        if (count($dipakai) > $total) {
            $dipakai = array_slice($dipakai, 0, $total);
        }

        $n = count($dipakai);
        $target = [];
        if ($semuaRuang) {
            // Merata penuh: ceil bertahap (42/5 → 9,9,8,8,8).
            $sisa = $total;
            for ($i = 0; $i < $n; $i++) {
                $jml = (int)ceil($sisa / ($n - $i));
                $maks = max(1, (int)$dipakai[$i]->kapasitas);
                if ($jml > $maks) {
                    $jml = $maks;
                }
                if ($i === $n - 1) {
                    $jml = min($sisa, $maks);
                }
                $target[$i] = $jml;
                $sisa -= $jml;
            }
        } else {
            // Berurutan sampai kapasitas ruang.
            $sisa = $total;
            for ($i = 0; $i < $n; $i++) {
                $maks = max(1, (int)$dipakai[$i]->kapasitas);
                $jml = min($sisa, $maks);
                $target[$i] = $jml;
                $sisa -= $jml;
            }
        }

        $sisaAkhir = $total - array_sum($target);
        if ($sisaAkhir > 0) {
            return [
                'ok'  => false,
                'msg' => 'Kapasitas ruang kurang (' . $kapasitas . ' kursi untuk ' . $total
                    . ' siswa). Tambah ruang atau naikkan kapasitas.'
            ];
        }

        // Sebar siswa ke ruang.
        $penempatan = [];   // [ [id_siswa, id_kelas, id_ruang], ... ]
        $pos = 0;
        foreach ($target as $i => $jml) {
            for ($k = 0; $k < $jml; $k++) {
                if (!isset($siswa[$pos])) {
                    break;
                }
                $penempatan[] = [$siswa[$pos], (int)$dipakai[$i]->id_ruang];
                $pos++;
            }
        }

        $this->db->trans_start();

        // Bersihkan pembagian lama semester ini (menimpa, sudah dikonfirmasi user).
        $this->db->where('tp_id', $id_tp)->where('smt_id', $id_smt)->delete('cbt_sesi_siswa');
        $this->db->where('id_tp', $id_tp)->where('id_smt', $id_smt)->delete('cbt_kelas_ruang');
        $this->db->where('id_tp', $id_tp)->where('id_smt', $id_smt)->delete('cbt_nomor_peserta');

        $perKelasRuang = [];  // [id_kelas][id_ruang] = jumlah
        foreach ($penempatan as $p) {
            $s = $p[0];
            $id_ruang = $p[1];
            $this->db->insert('cbt_sesi_siswa', [
                'siswa_id' => (int)$s->id_siswa,
                'kelas_id' => (int)$s->id_kelas,
                'ruang_id' => $id_ruang,
                'sesi_id'  => (int)$sesi->id_sesi,
                'tp_id'    => $id_tp,
                'smt_id'   => $id_smt
            ]);
            if (!isset($perKelasRuang[(int)$s->id_kelas][$id_ruang])) {
                $perKelasRuang[(int)$s->id_kelas][$id_ruang] = 0;
            }
            $perKelasRuang[(int)$s->id_kelas][$id_ruang]++;
        }

        foreach ($perKelasRuang as $id_kelas => $ruangs) {
            foreach ($ruangs as $id_ruang => $jml) {
                $this->db->replace('cbt_kelas_ruang', [
                    // Satu kelas bisa tersebar ke beberapa ruang, jadi id menyertakan ruang.
                    'id_kelas_ruang' => (string)$id_kelas . (string)$id_tp . (string)$id_smt . (string)(int)$id_ruang,
                    'id_kelas'       => (int)$id_kelas,
                    'id_ruang'       => (int)$id_ruang,
                    'id_sesi'        => (int)$sesi->id_sesi,
                    'id_tp'          => $id_tp,
                    'id_smt'         => $id_smt,
                    'set_siswa'      => (int)$jml
                ]);
            }
        }

        // Nomor peserta: 2627.07.001 (tahun . level . urut per kelas).
        $tp = $this->activeTp();
        $tahun = $tp ? (string)$tp->tahun : '';
        $bagian = explode('/', $tahun);
        $awal = isset($bagian[0]) ? substr(trim($bagian[0]), -2) : '';
        $akhir = isset($bagian[1]) ? substr(trim($bagian[1]), -2) : '';

        $urutPerKelas = [];
        $nomor = 0;
        foreach ($penempatan as $p) {
            $s = $p[0];
            $id_kelas = (int)$s->id_kelas;
            if (!isset($urutPerKelas[$id_kelas])) {
                $urutPerKelas[$id_kelas] = 0;
            }
            $urutPerKelas[$id_kelas]++;
            $info = $this->levelKelas($id_kelas);
            $level = $info['level'] > 0 ? $info['level'] : (int)$info['nama'];
            $nomorPeserta = $awal . $akhir . '.' . str_pad((string)$level, 2, '0', STR_PAD_LEFT)
                . '.' . str_pad((string)$urutPerKelas[$id_kelas], 3, '0', STR_PAD_LEFT);
            $this->db->replace('cbt_nomor_peserta', [
                'id_nomor'      => (int)($s->id_siswa . $id_tp),
                'id_siswa'      => (int)$s->id_siswa,
                'id_tp'         => $id_tp,
                'id_smt'        => $id_smt,
                'nomor_peserta' => $nomorPeserta
            ]);
            $nomor++;
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return ['ok' => false, 'msg' => 'Gagal menyimpan pembagian peserta (kesalahan database).'];
        }

        $ringkas = [];
        foreach ($target as $i => $jml) {
            $ringkas[] = [
                'ruang' => $dipakai[$i]->nama_ruang,
                'kode'  => $dipakai[$i]->kode_ruang,
                'jumlah' => $jml
            ];
        }

        return [
            'ok'      => true,
            'msg'     => 'Pembagian peserta disimpan: ' . $total . ' siswa di ' . $n . ' ruang, '
                . $nomor . ' nomor peserta dibuat.',
            'ringkas' => $ringkas
        ];
    }

    /* ==================================================================
     * FASE 4 — Pantau ujian
     * ================================================================ */

    /**
     * Daftar peserta ujian + status pengerjaan hari-H.
     * status: belum (0), sedang (1), selesai (2) dari cbt_durasi_siswa.
     */
    public function pantauData($row, $id_tp, $id_smt)
    {
        $kelas = $this->kelasUjian($row);
        if (count($kelas) === 0) {
            return ['siswa' => [], 'ringkas' => ['belum' => 0, 'sedang' => 0, 'selesai' => 0, 'total' => 0]];
        }

        $rows = $this->db->select('s.id_siswa, s.nis, s.nama, ks.id_kelas, mk.nama_kelas,
                ss.ruang_id, ss.sesi_id, r.nama_ruang, r.kode_ruang, se.nama_sesi,
                d.status AS durasi_status, d.lama_ujian, d.mulai, d.selesai,
                l.id_log, l.reset AS log_reset, l.log_time,
                n.pg_nilai, n.essai_nilai, n.kompleks_nilai, n.jodohkan_nilai, n.isian_nilai, n.dikoreksi', false)
            ->from('kelas_siswa ks')
            ->join('master_siswa s', 's.id_siswa = ks.id_siswa')
            ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas', 'left')
            ->join('cbt_sesi_siswa ss', 'ss.siswa_id = ks.id_siswa AND ss.tp_id = ks.id_tp AND ss.smt_id = ks.id_smt', 'left')
            ->join('cbt_ruang r', 'r.id_ruang = ss.ruang_id', 'left')
            ->join('cbt_sesi se', 'se.id_sesi = ss.sesi_id', 'left')
            ->join('cbt_durasi_siswa d', 'd.id_siswa = ks.id_siswa AND d.id_jadwal = ' . (int)$row->id_jadwal, 'left')
            ->join('log_ujian l', 'l.id_siswa = ks.id_siswa AND l.id_jadwal = ' . (int)$row->id_jadwal, 'left')
            ->join('cbt_nilai n', 'n.id_siswa = ks.id_siswa AND n.id_jadwal = ' . (int)$row->id_jadwal, 'left')
            ->where('ks.id_tp', (int)$id_tp)
            ->where('ks.id_smt', (int)$id_smt)
            ->where_in('ks.id_kelas', $kelas)
            ->order_by('mk.nama_kelas', 'ASC')
            ->order_by('s.nama', 'ASC')
            ->get()->result();

        $ringkas = ['belum' => 0, 'sedang' => 0, 'selesai' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $st = $r->durasi_status === null ? 0 : (int)$r->durasi_status;
            if ($st === 2) {
                $ringkas['selesai']++;
            } elseif ($st === 1) {
                $ringkas['sedang']++;
            } else {
                $ringkas['belum']++;
            }
            $ringkas['total']++;
        }
        return ['siswa' => $rows, 'ringkas' => $ringkas];
    }

    /** Reset izin login satu siswa pada satu ujian (log_ujian.reset = 1). */
    public function resetIzin($id_jadwal, $id_siswa)
    {
        $this->db->where('id_jadwal', (int)$id_jadwal)
            ->where('id_siswa', (int)$id_siswa)
            ->update('log_ujian', ['reset' => 1]);
        return $this->db->affected_rows() >= 0;
    }

    /** Tambah waktu: geser jam mulai siswa ke belakang sebanyak $menit. */
    public function tambahWaktu($id_jadwal, $id_siswa, $menit)
    {
        $menit = (int)$menit;
        if ($menit < 1 || $menit > 120) {
            return false;
        }
        $row = $this->db->get_where('cbt_durasi_siswa', [
            'id_siswa' => (int)$id_siswa, 'id_jadwal' => (int)$id_jadwal
        ])->row();
        if (!$row || empty($row->mulai)) {
            return false;
        }
        $mulai = strtotime($row->mulai);
        if (!$mulai) {
            return false;
        }
        return $this->db->where('id_durasi', (int)$row->id_durasi)
            ->update('cbt_durasi_siswa', ['mulai' => date('Y-m-d H:i:s', $mulai - ($menit * 60))]);
    }

    /* ==================================================================
     * PENGATURAN UJIAN — tab Sesi & Jam
     * ================================================================ */

    public function getSesi($id_sesi)
    {
        return $this->db->get_where('cbt_sesi', ['id_sesi' => (int)$id_sesi])->row();
    }

    private function validJam($s)
    {
        $s = trim((string)$s);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s)) {
            return $s . ':00';
        }
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $s)) {
            return $s;
        }
        return null;
    }

    /** Simpan satu sesi. Return ['ok'=>bool,'msg'=>string]. */
    public function simpanSesi($id_sesi, $nama, $kode, $mulai, $akhir, $aktif)
    {
        $sesi = $this->getSesi($id_sesi);
        if (!$sesi) {
            return ['ok' => false, 'msg' => 'Sesi tidak ditemukan.'];
        }
        $nama = trim((string)$nama);
        $kode = strtoupper(trim((string)$kode));
        if ($nama === '' || mb_strlen($nama) > 50) {
            return ['ok' => false, 'msg' => 'Nama sesi wajib diisi (maksimal 50 karakter).'];
        }
        if ($kode === '' || mb_strlen($kode) > 10) {
            return ['ok' => false, 'msg' => 'Kode sesi wajib diisi (maksimal 10 karakter).'];
        }
        $mulai = $this->validJam($mulai);
        $akhir = $this->validJam($akhir);
        if ($mulai === null || $akhir === null) {
            return ['ok' => false, 'msg' => 'Waktu harus berformat jam:menit (mis. 07:30).'];
        }
        if (strtotime($akhir) <= strtotime($mulai)) {
            return ['ok' => false, 'msg' => 'Waktu akhir harus setelah waktu mulai.'];
        }

        $this->db->where('id_sesi', (int)$id_sesi)->update('cbt_sesi', [
            'nama_sesi'   => $nama,
            'kode_sesi'   => $kode,
            'waktu_mulai' => $mulai,
            'waktu_akhir' => $akhir,
            'aktif'       => $aktif ? 1 : 0
        ]);
        return ['ok' => true, 'msg' => 'Sesi "' . $nama . '" disimpan.'];
    }

    /** Ubah urutan jam (jam_ke) satu ujian. */
    public function setJamKe($id_jadwal, $jam_ke)
    {
        $jam_ke = (int)$jam_ke;
        if ($jam_ke < 1 || $jam_ke > 10) {
            return false;
        }
        $this->db->where('id_jadwal', (int)$id_jadwal)
            ->update('cbt_jadwal', ['jam_ke' => $jam_ke]);
        return true;
    }

    /* ==================================================================
     * PENGATURAN UJIAN — tab Pengawas
     * ================================================================ */

    /**
     * Matriks pengawas: satu pengawas per ruang per tanggal, dipakai bersama
     * semua ujian pada tanggal itu.
     * Return [['tanggal','ujian'=>[],'pengawas'=>[id_ruang=>id_guru]], ...]
     */
    public function pengawasMatrix($id_tp, $id_smt)
    {
        $tanggals = $this->db->select('tgl_mulai')->distinct()
            ->where('id_tp', (int)$id_tp)->where('id_smt', (int)$id_smt)
            ->order_by('tgl_mulai', 'ASC')->get('cbt_jadwal')->result();

        $out = [];
        foreach ($tanggals as $t) {
            $tgl = substr((string)$t->tgl_mulai, 0, 10);
            $ujian = $this->ujianPadaTanggal($tgl, 0, $id_tp, $id_smt);
            $map = [];
            foreach ($ujian as $u) {
                foreach ($u->pengawas as $id_ruang => $id_guru) {
                    if ((int)$id_guru > 0 && !isset($map[(int)$id_ruang])) {
                        $map[(int)$id_ruang] = (int)$id_guru;
                    }
                }
            }
            $out[] = [
                'tanggal'  => $tgl,
                'ujian'    => $ujian,
                'pengawas' => $map
            ];
        }
        return $out;
    }

    /**
     * Simpan pengawas per ruang untuk SEMUA ujian pada satu tanggal.
     * Return jumlah ujian yang diperbarui.
     */
    public function simpanPengawasTanggal($tanggal, $id_tp, $id_smt, array $perRuang, $id_sesi)
    {
        $ujian = $this->ujianPadaTanggal($tanggal, 0, $id_tp, $id_smt);
        $n = 0;
        foreach ($ujian as $u) {
            $this->simpanPengawas($u->id_jadwal, $id_tp, $id_smt, $perRuang, $id_sesi);
            $n++;
        }
        return $n;
    }

    /* ==================================================================
     * PENGATURAN UJIAN — tab Jenis Ujian
     * ================================================================ */

    public function jenisDenganPemakaian()
    {
        $this->db->select('j.*, (SELECT COUNT(*) FROM cbt_jadwal d WHERE d.id_jenis = j.id_jenis) AS dipakai', false);
        $this->db->order_by('j.id_jenis', 'ASC');
        return $this->db->get('cbt_jenis j')->result();
    }

    public function jenisDipakai($id_jenis)
    {
        $row = $this->db->select('COUNT(*) c')->get_where('cbt_jadwal', ['id_jenis' => (int)$id_jenis])->row();
        return $row ? (int)$row->c : 0;
    }

    public function simpanJenis($id_jenis, $kode, $nama)
    {
        $kode = strtoupper(trim((string)$kode));
        $nama = trim((string)$nama);
        if ($kode === '' || mb_strlen($kode) > 10) {
            return ['ok' => false, 'msg' => 'Kode jenis wajib diisi (maksimal 10 karakter).'];
        }
        if ($nama === '' || mb_strlen($nama) > 50) {
            return ['ok' => false, 'msg' => 'Nama jenis wajib diisi (maksimal 50 karakter).'];
        }
        // Kode tidak boleh kembar dengan jenis lain.
        $this->db->where('kode_jenis', $kode);
        if ((int)$id_jenis > 0) {
            $this->db->where('id_jenis !=', (int)$id_jenis);
        }
        if ($this->db->get('cbt_jenis')->row()) {
            return ['ok' => false, 'msg' => 'Kode "' . $kode . '" sudah dipakai jenis lain.'];
        }

        if ((int)$id_jenis > 0) {
            if (!$this->db->get_where('cbt_jenis', ['id_jenis' => (int)$id_jenis])->row()) {
                return ['ok' => false, 'msg' => 'Jenis ujian tidak ditemukan.'];
            }
            $this->db->where('id_jenis', (int)$id_jenis)->update('cbt_jenis', [
                'kode_jenis' => $kode, 'nama_jenis' => $nama
            ]);
            return ['ok' => true, 'msg' => 'Jenis ujian diperbarui.', 'id' => (int)$id_jenis];
        }

        $this->db->insert('cbt_jenis', ['kode_jenis' => $kode, 'nama_jenis' => $nama]);
        return ['ok' => true, 'msg' => 'Jenis ujian ditambahkan.', 'id' => (int)$this->db->insert_id()];
    }

    public function hapusJenis($id_jenis)
    {
        $id_jenis = (int)$id_jenis;
        if ($this->jenisDipakai($id_jenis) > 0) {
            return ['ok' => false, 'msg' => 'Jenis ini sudah dipakai ujian dan tidak bisa dihapus.'];
        }
        $this->db->where('id_jenis', $id_jenis)->delete('cbt_jenis');
        return ['ok' => true, 'msg' => 'Jenis ujian dihapus.'];
    }

    /* ==================================================================
     * PENGATURAN UJIAN — tab Token
     * ================================================================ */

    /** Token global (baris tunggal cbt_token) — cadangan untuk ujian lama. */
    public function globalToken()
    {
        return $this->db->order_by('id_token', 'ASC')->get('cbt_token')->row();
    }

    public function simpanTokenGlobal($token, $auto = 0, $jarak = 0)
    {
        $token = strtoupper(trim((string)$token));
        if (!preg_match('/^[A-Z0-9]{6}$/', $token)) {
            return ['ok' => false, 'msg' => 'Token harus 6 karakter huruf dan angka.'];
        }
        $row = $this->globalToken();
        $data = [
            'token'   => $token,
            'auto'    => (int)$auto,
            'jarak'   => (int)$jarak,
            'updated' => date('Y-m-d H:i:s')
        ];
        if ($row) {
            $this->db->where('id_token', (int)$row->id_token)->update('cbt_token', $data);
        } else {
            $this->db->insert('cbt_token', $data);
        }
        return ['ok' => true, 'msg' => 'Token global disimpan sebagai cadangan.'];
    }

    /** Simpan token satu ujian (validasi unik per tanggal). */
    public function setTokenUjian($id_jadwal, $token)
    {
        $u = $this->getUjian((int)$id_jadwal);
        if (!$u) {
            return ['ok' => false, 'msg' => 'Ujian tidak ditemukan.'];
        }
        $token = strtoupper(trim((string)$token));
        if (!preg_match('/^[A-Z0-9]{6}$/', $token)) {
            return ['ok' => false, 'msg' => 'Token harus 6 karakter huruf dan angka.'];
        }
        $tgl = substr((string)$u->tgl_mulai, 0, 10);
        foreach ($this->tokenDipakai($tgl, (int)$u->id_jadwal) as $t) {
            if (strtoupper(trim((string)$t->token_ujian)) === $token) {
                return ['ok' => false, 'msg' => 'Token ini sudah dipakai ujian lain di tanggal yang sama. Acak ulang.'];
            }
        }
        $this->db->where('id_jadwal', (int)$id_jadwal)->update('cbt_jadwal', [
            'token_ujian' => $token,
            'token'       => 1
        ]);
        return ['ok' => true, 'token' => $token, 'msg' => 'Token ujian disimpan.'];
    }

    /** Acak + simpan token unik untuk satu ujian. */
    public function acakTokenUjian($id_jadwal)
    {
        $u = $this->getUjian((int)$id_jadwal);
        if (!$u) {
            return ['ok' => false, 'msg' => 'Ujian tidak ditemukan.'];
        }
        $tgl = substr((string)$u->tgl_mulai, 0, 10);
        $token = $this->tokenUnik($tgl, (int)$u->id_jadwal);
        $this->db->where('id_jadwal', (int)$id_jadwal)->update('cbt_jadwal', [
            'token_ujian' => $token,
            'token'       => 1
        ]);
        return ['ok' => true, 'token' => $token, 'msg' => 'Token diacak ulang.'];
    }
}
