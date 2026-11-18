<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nilai Manual (ujian kertas / offline).
 *
 * Model ini sengaja ditulis sebagai file baru (tidak mengubah model GarudaCBT
 * yang diobfuskasi). Tabel dibuat otomatis saat model pertama kali dimuat.
 *
 * Nilai dapat "dikirim ke rapor" ke tabel yang sama yang dipakai menu
 * Rapor > Input Nilai (harian / PTS / PAS), sehingga logikanya sama.
 */
class Nilaimanual_model extends CI_Model
{
    const T_HEAD = 'cbt_nilai_manual';
    const T_DET = 'cbt_nilai_manual_detail';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    private function ensureTables()
    {
        if (!$this->db->table_exists(self::T_HEAD)) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `cbt_nilai_manual` (
                `id_nm` int NOT NULL AUTO_INCREMENT,
                `id_tp` int NOT NULL,
                `id_smt` int NOT NULL,
                `id_mapel` int NOT NULL,
                `id_kelas` int NOT NULL,
                `kode_jenis` varchar(10) NOT NULL DEFAULT 'PH',
                `aspek` tinyint NOT NULL DEFAULT 1,
                `nama_ujian` varchar(100) NOT NULL,
                `tanggal` date DEFAULT NULL,
                `nilai_maks` int NOT NULL DEFAULT 100,
                `id_guru` int DEFAULT NULL,
                `slot_rapor` varchar(4) DEFAULT NULL,
                `rapor_terkirim` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_nm`),
                KEY `idx_kelas_mapel` (`id_tp`,`id_smt`,`id_kelas`,`id_mapel`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!$this->db->table_exists(self::T_DET)) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `cbt_nilai_manual_detail` (
                `id_nmd` int NOT NULL AUTO_INCREMENT,
                `id_nm` int NOT NULL,
                `id_siswa` int NOT NULL,
                `nilai` decimal(6,2) DEFAULT NULL,
                PRIMARY KEY (`id_nmd`),
                UNIQUE KEY `uniq_nm_siswa` (`id_nm`,`id_siswa`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /* ------------------------------------------------------------------
     * Tahun pelajaran, semester, guru
     * ---------------------------------------------------------------- */

    public function activeTp()
    {
        return $this->db->get_where('master_tp', ['active' => 1])->row();
    }

    public function activeSmt()
    {
        return $this->db->get_where('master_smt', ['active' => 1])->row();
    }

    /** Daftar seluruh TP/semester — dibutuhkan navbar dashboard. */
    public function allTp()
    {
        return $this->db->order_by('id_tp', 'ASC')->get('master_tp')->result();
    }

    public function allSmt()
    {
        return $this->db->order_by('id_smt', 'ASC')->get('master_smt')->result();
    }

    public function guruByUser($id_user)
    {
        return $this->db->get_where('master_guru', ['id_user' => $id_user])->row();
    }

    /**
     * Pasangan mapel => [id_kelas, ...] yang diajar guru pada TP/semester aktif.
     */
    public function pairsGuru($id_guru, $id_tp, $id_smt)
    {
        $rows = $this->db->get_where('jabatan_guru', [
            'id_guru' => $id_guru, 'id_tp' => $id_tp, 'id_smt' => $id_smt
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

    /**
     * Data untuk dropdown mapel/kelas.
     * Admin: semua mapel x semua kelas. Guru: sesuai jabatan_guru.
     * Return: ['mapel' => [id => nama], 'kelas' => [id => nama], 'pairs' => [id_mapel => [id_kelas...]]]
     */
    public function opsi($id_tp, $id_smt, $id_guru = null)
    {
        $kelasRows = $this->db->order_by('nama_kelas', 'ASC')
            ->get_where('master_kelas', ['id_tp' => $id_tp, 'id_smt' => $id_smt])->result();
        $kelas = [];
        foreach ($kelasRows as $k) {
            $kelas[(int)$k->id_kelas] = $k->nama_kelas;
        }

        $mapelRows = $this->db->order_by('nama_mapel', 'ASC')->get('master_mapel')->result();
        $mapel = [];
        foreach ($mapelRows as $m) {
            $mapel[(int)$m->id_mapel] = $m->nama_mapel;
        }

        if ($id_guru === null) {
            $pairs = [];
            foreach ($mapel as $idm => $nm) {
                $pairs[$idm] = array_keys($kelas);
            }
            return ['mapel' => $mapel, 'kelas' => $kelas, 'pairs' => $pairs];
        }

        $pairsGuru = $this->pairsGuru($id_guru, $id_tp, $id_smt);
        $pairs = [];
        $mapelOut = [];
        foreach ($pairsGuru as $idm => $kls) {
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

    public function guruBolehAkses($id_guru, $id_tp, $id_smt, $id_mapel, $id_kelas)
    {
        $pairs = $this->pairsGuru($id_guru, $id_tp, $id_smt);
        return isset($pairs[(int)$id_mapel]) && in_array((int)$id_kelas, $pairs[(int)$id_mapel], true);
    }

    /* ------------------------------------------------------------------
     * CRUD ujian offline
     * ---------------------------------------------------------------- */

    public function listUjian($id_tp, $id_smt, $id_guru = null)
    {
        $this->db->select('h.*, m.nama_mapel, k.nama_kelas,
            (SELECT COUNT(*) FROM ' . self::T_DET . ' d WHERE d.id_nm = h.id_nm AND d.nilai IS NOT NULL) AS terisi,
            (SELECT COUNT(*) FROM kelas_siswa ks WHERE ks.id_kelas = h.id_kelas AND ks.id_tp = h.id_tp AND ks.id_smt = h.id_smt) AS total_siswa', false);
        $this->db->from(self::T_HEAD . ' h');
        $this->db->join('master_mapel m', 'm.id_mapel = h.id_mapel', 'left');
        $this->db->join('master_kelas k', 'k.id_kelas = h.id_kelas', 'left');
        $this->db->where('h.id_tp', $id_tp);
        $this->db->where('h.id_smt', $id_smt);
        if ($id_guru !== null) {
            $this->db->where('h.id_guru', $id_guru);
        }
        $this->db->order_by('h.tanggal', 'DESC');
        $this->db->order_by('h.id_nm', 'DESC');
        return $this->db->get()->result();
    }

    public function getUjian($id_nm)
    {
        $this->db->select('h.*, m.nama_mapel, k.nama_kelas');
        $this->db->from(self::T_HEAD . ' h');
        $this->db->join('master_mapel m', 'm.id_mapel = h.id_mapel', 'left');
        $this->db->join('master_kelas k', 'k.id_kelas = h.id_kelas', 'left');
        $this->db->where('h.id_nm', (int)$id_nm);
        return $this->db->get()->row();
    }

    public function createUjian(array $data)
    {
        $this->db->insert(self::T_HEAD, $data);
        return $this->db->insert_id();
    }

    public function updateUjian($id_nm, array $data)
    {
        $this->db->where('id_nm', (int)$id_nm);
        return $this->db->update(self::T_HEAD, $data);
    }

    public function deleteUjian($id_nm)
    {
        $this->db->trans_start();
        $this->db->where('id_nm', (int)$id_nm)->delete(self::T_DET);
        $this->db->where('id_nm', (int)$id_nm)->delete(self::T_HEAD);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /* ------------------------------------------------------------------
     * Siswa & nilai
     * ---------------------------------------------------------------- */

    public function siswaKelas($id_kelas, $id_tp, $id_smt)
    {
        $this->db->select('s.id_siswa, s.nis, s.nisn, s.nama');
        $this->db->from('kelas_siswa ks');
        $this->db->join('master_siswa s', 's.id_siswa = ks.id_siswa');
        $this->db->where('ks.id_kelas', (int)$id_kelas);
        $this->db->where('ks.id_tp', (int)$id_tp);
        $this->db->where('ks.id_smt', (int)$id_smt);
        $this->db->order_by('s.nama', 'ASC');
        return $this->db->get()->result();
    }

    public function nilaiMap($id_nm)
    {
        $rows = $this->db->get_where(self::T_DET, ['id_nm' => (int)$id_nm])->result();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->id_siswa] = $r->nilai;
        }
        return $map;
    }

    /**
     * Simpan nilai. $nilai = [id_siswa => nilai|''|null]. Hanya siswa yang
     * terdaftar di kelas tersebut yang diterima.
     * Return: jumlah baris tersimpan.
     */
    public function simpanNilai($u, array $nilai)
    {
        $valid = [];
        foreach ($this->siswaKelas($u->id_kelas, $u->id_tp, $u->id_smt) as $s) {
            $valid[(int)$s->id_siswa] = true;
        }

        $saved = 0;
        $this->db->trans_start();
        foreach ($nilai as $id_siswa => $v) {
            $id_siswa = (int)$id_siswa;
            if (!isset($valid[$id_siswa])) {
                continue;
            }
            $v = is_string($v) ? trim(str_replace(',', '.', $v)) : $v;
            $val = ($v === '' || $v === null) ? null : (float)$v;
            $this->db->query(
                'INSERT INTO ' . self::T_DET . ' (id_nm, id_siswa, nilai) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)',
                [(int)$u->id_nm, $id_siswa, $val]
            );
            $saved++;
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $saved : -1;
    }

    /* ------------------------------------------------------------------
     * Rekap per kelas + mapel
     * ---------------------------------------------------------------- */

    public function rekap($id_tp, $id_smt, $id_kelas, $id_mapel)
    {
        $ujian = $this->db->order_by('tanggal', 'ASC')->order_by('id_nm', 'ASC')
            ->get_where(self::T_HEAD, [
                'id_tp' => $id_tp, 'id_smt' => $id_smt,
                'id_kelas' => $id_kelas, 'id_mapel' => $id_mapel
            ])->result();
        $siswa = $this->siswaKelas($id_kelas, $id_tp, $id_smt);
        $nilai = [];
        foreach ($ujian as $u) {
            $nilai[(int)$u->id_nm] = $this->nilaiMap($u->id_nm);
        }
        return ['ujian' => $ujian, 'siswa' => $siswa, 'nilai' => $nilai];
    }

    /* ------------------------------------------------------------------
     * Integrasi rapor
     * ---------------------------------------------------------------- */

    /** Rumus predikat sama persis dengan halaman Input Nilai Rapor. */
    private function predikat($n, $kkm)
    {
        $dsd = $kkm - 1;
        $c = $kkm;
        $csd = floor($kkm + (100 - $kkm) / 3);
        $b = $csd + 1;
        $bsd = floor($b + (100 - $b) / 2);
        if ($n > $bsd) {
            return 'A';
        }
        if ($n > $csd) {
            return 'B';
        }
        if ($n > $dsd) {
            return 'C';
        }
        if ($n < $c) {
            return 'D';
        }
        return '';
    }

    /**
     * Ambil KKM & bobot untuk mapel/kelas. Return array atau string (pesan error).
     */
    private function paramRapor($u)
    {
        $set = $this->db->get_where('rapor_admin_setting', [
            'id_tp' => $u->id_tp, 'id_smt' => $u->id_smt
        ])->row();
        if (!$set) {
            return 'Setting Rapor untuk tahun pelajaran/semester ini belum diisi. Isi dulu di menu Rapor > Setting Rapor.';
        }
        if ((string)$set->kkm_tunggal === '1') {
            return [
                'kkm' => (int)$set->kkm, 'ph' => (int)$set->bobot_ph,
                'pts' => (int)$set->bobot_pts, 'pas' => (int)$set->bobot_pas
            ];
        }
        $idKkm = $u->id_mapel . $u->id_kelas . $u->id_tp . $u->id_smt . '1';
        $row = $this->db->get_where('rapor_kkm', ['id_kkm' => $idKkm])->row();
        if (!$row) {
            return 'KKM untuk mapel dan kelas ini belum diatur di Rapor > Data Rapor > KKM dan Bobot.';
        }
        return [
            'kkm' => (int)$row->kkm, 'ph' => (int)$row->bobot_ph,
            'pts' => (int)$row->bobot_pts, 'pas' => (int)$row->bobot_pas
        ];
    }

    private function skala($nilai, $maks)
    {
        $maks = $maks > 0 ? $maks : 100;
        $v = (int)round(((float)$nilai) / $maks * 100);
        return max(0, min(100, $v));
    }

    private function idRapor($u, $id_siswa)
    {
        return $u->id_mapel . $u->id_kelas . $id_siswa . $u->id_tp . $u->id_smt;
    }

    private function terisi($v)
    {
        return $v !== null && $v !== '' && (string)$v !== '0';
    }

    /**
     * Kirim nilai ujian ke tabel rapor.
     * Return ['ok' => bool, 'msg' => string, 'slot' => string|null, 'jumlah' => int]
     */
    public function kirimRapor($u, $slotManual = null)
    {
        $param = $this->paramRapor($u);
        if (is_string($param)) {
            return ['ok' => false, 'msg' => $param, 'slot' => null, 'jumlah' => 0];
        }

        $siswa = $this->siswaKelas($u->id_kelas, $u->id_tp, $u->id_smt);
        $nilai = $this->nilaiMap($u->id_nm);

        $target = [];
        foreach ($siswa as $s) {
            $sid = (int)$s->id_siswa;
            if (isset($nilai[$sid]) && $nilai[$sid] !== null) {
                $target[$sid] = $this->skala($nilai[$sid], (int)$u->nilai_maks);
            }
        }
        if (count($target) === 0) {
            return ['ok' => false, 'msg' => 'Belum ada nilai yang terisi.', 'slot' => null, 'jumlah' => 0];
        }

        $slot = null;
        $this->db->trans_start();

        if ($u->kode_jenis === 'PH') {
            $res = $this->kirimHarian($u, $siswa, $target, $param, $slotManual);
            if (isset($res['err'])) {
                $this->db->trans_complete();
                return ['ok' => false, 'msg' => $res['err'], 'slot' => null, 'jumlah' => 0];
            }
            $slot = $res['slot'];
        } elseif ($u->kode_jenis === 'PTS') {
            $this->kirimPts($u, $target, $param);
        } elseif ($u->kode_jenis === 'PAS') {
            $this->kirimPas($u, $target, $param);
        } else {
            $this->db->trans_complete();
            return ['ok' => false, 'msg' => 'Jenis ujian ini tidak dikirim ke rapor.', 'slot' => null, 'jumlah' => 0];
        }

        // Jika PH/PTS berubah dan nilai PAS sudah ada, nilai akhir ikut dihitung ulang.
        if ($u->kode_jenis !== 'PAS') {
            foreach (array_keys($target) as $sid) {
                $this->hitungUlangAkhir($u, $sid, $param);
            }
        }

        $this->updateUjian($u->id_nm, [
            'slot_rapor' => $slot,
            'rapor_terkirim' => date('Y-m-d H:i:s')
        ]);
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return ['ok' => false, 'msg' => 'Gagal menyimpan ke rapor (kesalahan database).', 'slot' => null, 'jumlah' => 0];
        }
        return ['ok' => true, 'msg' => 'Berhasil', 'slot' => $slot, 'jumlah' => count($target)];
    }

    private function kirimHarian($u, $siswa, $target, $param, $slotManual)
    {
        $pre = ((int)$u->aspek === 2) ? 'k' : 'p';

        // Ambil seluruh baris rapor harian kelas ini sekaligus.
        $existing = [];
        foreach ($siswa as $s) {
            $row = $this->db->get_where('rapor_nilai_harian', [
                'id_nilai_harian' => $this->idRapor($u, $s->id_siswa)
            ])->row_array();
            if ($row) {
                $existing[(int)$s->id_siswa] = $row;
            }
        }

        // Slot yang sudah dipakai ujian offline lain pada mapel/kelas/aspek yang sama.
        $dipakai = [];
        $lain = $this->db->get_where(self::T_HEAD, [
            'id_tp' => $u->id_tp, 'id_smt' => $u->id_smt,
            'id_kelas' => $u->id_kelas, 'id_mapel' => $u->id_mapel,
            'kode_jenis' => 'PH', 'aspek' => (int)$u->aspek
        ])->result();
        foreach ($lain as $o) {
            if ((int)$o->id_nm !== (int)$u->id_nm && !empty($o->slot_rapor)) {
                $dipakai[$o->slot_rapor] = true;
            }
        }

        $slot = null;
        if (!empty($u->slot_rapor)) {
            $slot = $u->slot_rapor;               // kirim ulang: pakai slot yang sama
        } elseif ($slotManual !== null && preg_match('/^' . $pre . '[1-8]$/', $slotManual)) {
            if (isset($dipakai[$slotManual])) {
                return ['err' => 'Kolom ' . strtoupper($slotManual) . ' sudah dipakai ujian offline lain.'];
            }
            foreach (array_keys($target) as $sid) {
                if (isset($existing[$sid]) && $this->terisi($existing[$sid][$slotManual] ?? null)) {
                    return ['err' => 'Kolom ' . strtoupper($slotManual) . ' di rapor sudah berisi nilai siswa lain/sebelumnya. '
                        . 'Pilih kolom lain atau gunakan Otomatis agar tidak menimpa nilai yang ada.'];
                }
            }
            $slot = $slotManual;
        } else {
            for ($i = 1; $i <= 8; $i++) {
                $col = $pre . $i;
                if (isset($dipakai[$col])) {
                    continue;
                }
                $bebas = true;
                foreach ($existing as $row) {
                    if ($this->terisi($row[$col] ?? null)) {
                        $bebas = false;
                        break;
                    }
                }
                if ($bebas) {
                    $slot = $col;
                    break;
                }
            }
            if ($slot === null) {
                return ['err' => 'Semua kolom nilai harian (' . strtoupper($pre) . '1-' . strtoupper($pre) . '8) sudah terisi. '
                    . 'Kosongkan satu kolom di Rapor > Input Nilai terlebih dahulu.'];
            }
        }

        foreach ($target as $sid => $val) {
            $row = $existing[$sid] ?? null;

            $p = [];
            $k = [];
            for ($i = 1; $i <= 8; $i++) {
                $p[$i] = $row ? $row['p' . $i] : null;
                $k[$i] = $row ? $row['k' . $i] : null;
            }
            if ($pre === 'p') {
                $p[(int)substr($slot, 1)] = (string)$val;
            } else {
                $k[(int)substr($slot, 1)] = (string)$val;
            }

            $grp = ($pre === 'p') ? $p : $k;
            $jumlah = 0;
            $n = 0;
            foreach ($grp as $x) {
                if ($this->terisi($x)) {
                    $jumlah += (int)$x;
                    $n++;
                }
            }
            $rata = $n < 2 ? '' : (string)round($jumlah / $n);
            $pred = $rata === '' ? '' : $this->predikat((int)$rata, $param['kkm']);

            $sumP = 0;
            foreach ($p as $x) {
                $sumP += $this->terisi($x) ? (int)$x : 0;
            }
            $sumK = 0;
            foreach ($k as $x) {
                $sumK += $this->terisi($x) ? (int)$x : 0;
            }

            $set = [
                $slot => (string)$val,
                $pre . '_rata_rata' => $rata,
                $pre . '_predikat' => $pred,
                'jml' => $sumP + $sumK
            ];

            if ($row) {
                $this->db->where('id_nilai_harian', $this->idRapor($u, $sid))->update('rapor_nilai_harian', $set);
            } else {
                $set['id_nilai_harian'] = $this->idRapor($u, $sid);
                $set['id_siswa'] = $sid;
                $set['id_tp'] = $u->id_tp;
                $set['id_smt'] = $u->id_smt;
                $set['id_mapel'] = $u->id_mapel;
                $set['id_kelas'] = $u->id_kelas;
                $this->db->insert('rapor_nilai_harian', $set);
            }
        }
        return ['slot' => $slot];
    }

    private function kirimPts($u, $target, $param)
    {
        foreach ($target as $sid => $val) {
            $id = $this->idRapor($u, $sid);
            $set = ['nilai' => $val, 'predikat' => $this->predikat($val, $param['kkm'])];
            $ada = $this->db->get_where('rapor_nilai_pts', ['id_nilai_pts' => $id])->row();
            if ($ada) {
                $this->db->where('id_nilai_pts', $id)->update('rapor_nilai_pts', $set);
            } else {
                $set += [
                    'id_nilai_pts' => $id, 'id_siswa' => $sid, 'id_tp' => $u->id_tp,
                    'id_smt' => $u->id_smt, 'id_mapel' => $u->id_mapel, 'id_kelas' => $u->id_kelas
                ];
                $this->db->insert('rapor_nilai_pts', $set);
            }
        }
    }

    private function hitungAkhir($u, $sid, $npas, $param)
    {
        $id = $this->idRapor($u, $sid);
        $har = $this->db->get_where('rapor_nilai_harian', ['id_nilai_harian' => $id])->row();
        $pts = $this->db->get_where('rapor_nilai_pts', ['id_nilai_pts' => $id])->row();
        $nhar = ($har && is_numeric($har->p_rata_rata)) ? (float)$har->p_rata_rata : 0;
        $npts = ($pts && is_numeric($pts->nilai)) ? (float)$pts->nilai : 0;
        $akhir = (int)round(
            ($nhar * $param['ph'] / 100) + ($npts * $param['pts'] / 100) + ($npas * $param['pas'] / 100)
        );
        return ['akhir' => $akhir, 'predikat' => $this->predikat($akhir, $param['kkm'])];
    }

    private function kirimPas($u, $target, $param)
    {
        foreach ($target as $sid => $val) {
            $id = $this->idRapor($u, $sid);
            $h = $this->hitungAkhir($u, $sid, $val, $param);
            $set = ['nilai' => $val, 'akhir' => $h['akhir'], 'predikat' => $h['predikat']];
            $ada = $this->db->get_where('rapor_nilai_akhir', ['id_nilai_akhir' => $id])->row();
            if ($ada) {
                $this->db->where('id_nilai_akhir', $id)->update('rapor_nilai_akhir', $set);
            } else {
                $set += [
                    'id_nilai_akhir' => $id, 'id_siswa' => $sid, 'id_tp' => $u->id_tp,
                    'id_smt' => $u->id_smt, 'id_mapel' => $u->id_mapel, 'id_kelas' => $u->id_kelas
                ];
                $this->db->insert('rapor_nilai_akhir', $set);
            }
        }
    }

    private function hitungUlangAkhir($u, $sid, $param)
    {
        $id = $this->idRapor($u, $sid);
        $akhir = $this->db->get_where('rapor_nilai_akhir', ['id_nilai_akhir' => $id])->row();
        if (!$akhir || !is_numeric($akhir->nilai)) {
            return;
        }
        $h = $this->hitungAkhir($u, $sid, (float)$akhir->nilai, $param);
        $this->db->where('id_nilai_akhir', $id)->update('rapor_nilai_akhir', [
            'akhir' => $h['akhir'], 'predikat' => $h['predikat']
        ]);
    }
}
