<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Peringatan - <?= html_escape($siswa->nama); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; color: #111; margin: 0; padding: 28px; background: #fff; }
        .lembar { max-width: 820px; margin: 0 auto; }
        .kop { display: flex; align-items: center; gap: 16px; border-bottom: 3px double #111; padding-bottom: 12px; }
        .kop img { max-height: 78px; }
        .kop-teks { flex: 1; text-align: center; }
        .kop-teks h2 { margin: 0; font-size: 20px; letter-spacing: .5px; }
        .kop-teks p { margin: 2px 0; font-size: 12px; }
        .judul { text-align: center; margin: 26px 0 6px; }
        .judul h3 { margin: 0; font-size: 18px; text-decoration: underline; letter-spacing: 1.5px; }
        .judul p { margin: 4px 0 0; font-size: 13px; }
        .nomor { text-align: center; font-size: 13px; margin-bottom: 22px; }
        table.identitas { width: 100%; font-size: 14px; margin: 0 0 18px; }
        table.identitas td { padding: 3px 0; vertical-align: top; }
        table.identitas td.label { width: 150px; }
        .isi-surat { font-size: 14px; line-height: 1.65; text-align: justify; }
        table.rincian { width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0; }
        table.rincian th, table.rincian td { border: 1px solid #444; padding: 6px 8px; }
        table.rincian th { background: #f0f0f0; }
        .total { font-size: 14px; margin-top: 6px; }
        .tanda { margin-top: 40px; display: flex; justify-content: space-between; font-size: 14px; }
        .tanda .kolom { width: 44%; text-align: center; }
        .tanda .ruang { height: 74px; }
        .kaki { margin-top: 34px; font-size: 11px; color: #666; border-top: 1px dashed #bbb; padding-top: 10px; }
        .cetak { text-align: center; margin-bottom: 18px; }
        .cetak button { font-family: Arial, sans-serif; padding: 8px 18px; border: 0; border-radius: 6px; background: #1f6feb; color: #fff; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .cetak { display: none; }
            .lembar { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="lembar">
        <div class="cetak">
            <button onclick="window.print()">Cetak / Simpan PDF</button>
        </div>

        <div class="kop">
            <?php if (!empty($setting->logo_kiri)): ?>
                <img src="<?= base_url($setting->logo_kiri); ?>" alt="Logo">
            <?php endif; ?>
            <div class="kop-teks">
                <h2><?= html_escape(isset($setting->sekolah) ? $setting->sekolah : 'SEKOLAH'); ?></h2>
                <p>
                    <?= html_escape(isset($setting->alamat) ? $setting->alamat : ''); ?>
                    <?= !empty($setting->kecamatan) ? ', Kec. ' . html_escape($setting->kecamatan) : ''; ?>
                    <?= !empty($setting->kota) ? ', ' . html_escape($setting->kota) : ''; ?>
                </p>
                <p>
                    <?= !empty($setting->telp) ? 'Telp. ' . html_escape($setting->telp) : ''; ?>
                    <?= !empty($setting->email) ? ' &middot; ' . html_escape($setting->email) : ''; ?>
                    <?= !empty($setting->web) ? ' &middot; ' . html_escape($setting->web) : ''; ?>
                </p>
            </div>
        </div>

        <div class="judul">
            <h3>SURAT PERINGATAN <?= (int) $level; ?></h3>
            <p><?= $level === 1 ? 'Peringatan Pertama' : ($level === 2 ? 'Peringatan Kedua' : 'Peringatan Ketiga'); ?></p>
        </div>
        <div class="nomor">
            Nomor: <?= sprintf('%03d', (int) $siswa->id_siswa); ?>/SP-<?= (int) $level; ?>/<?= date('Y'); ?>
        </div>

        <div class="isi-surat">
            Berdasarkan hasil pemantauan kedisiplinan siswa pada
            <?= html_escape(isset($tp_active->tahun) ? $tp_active->tahun : '-'); ?>
            Semester <?= html_escape(isset($smt_active->nama_smt) ? $smt_active->nama_smt : '-'); ?>,
            dengan ini kami sampaikan Surat Peringatan <b><?= (int) $level; ?></b> kepada:
        </div>

        <table class="identitas">
            <tr><td class="label">Nama</td><td>: <b><?= html_escape($siswa->nama); ?></b></td></tr>
            <tr><td class="label">NIS / NISN</td><td>: <?= html_escape(isset($siswa->nis) ? $siswa->nis : '-'); ?> / <?= html_escape(isset($siswa->nisn) ? $siswa->nisn : '-'); ?></td></tr>
            <tr><td class="label">Kelas</td><td>: <?= html_escape(is_object($kelas) ? (isset($kelas->nama_kelas) ? $kelas->nama_kelas : '') : (string) $kelas); ?></td></tr>
        </table>

        <div class="isi-surat">
            Siswa tersebut telah mengumpulkan <b><?= (int) $total_poin; ?> poin pelanggaran</b>
            dengan rincian sebagai berikut:
        </div>

        <table class="rincian">
            <thead>
                <tr>
                    <th style="width:36px">No</th>
                    <th style="width:90px">Tanggal</th>
                    <th>Jenis Pelanggaran</th>
                    <th style="width:70px">Poin</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; foreach ($riwayat as $r): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= date('d/m/Y', strtotime($r->tanggal)); ?></td>
                    <td><?= html_escape($r->jenis_pelanggaran); ?></td>
                    <td class="text-center"><?= (int) $r->poin; ?></td>
                    <td><?= html_escape(isset($r->keterangan) && $r->keterangan ? $r->keterangan : '-'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total">
            Total poin: <b><?= (int) $total_poin; ?></b>
            (ambang SP <?= (int) $level; ?>: <?= (int) ($level === 1 ? $setting_sp->sp1 : ($level === 2 ? $setting_sp->sp2 : $setting_sp->sp3)); ?> poin)
        </div>

        <div class="isi-surat" style="margin-top:16px;">
            Demikian surat peringatan ini disampaikan. Kami mohon perhatian Bapak/Ibu wali untuk
            membimbing dan mengarahkan siswa agar tidak mengulangi pelanggaran. Apabila pelanggaran
            kembali terjadi, sekolah akan memberikan sanksi sesuai ketentuan yang berlaku.
        </div>

        <div class="tanda">
            <div class="kolom">
                <p>Orang Tua / Wali</p>
                <div class="ruang"></div>
                <p>________________________</p>
            </div>
            <div class="kolom">
                <p><?= html_escape(isset($setting->kota) ? $setting->kota : ''); ?>, <?= date('d F Y'); ?></p>
                <p>Kepala Sekolah</p>
                <div class="ruang"></div>
                <p><b><?= html_escape(isset($setting->kepsek) ? $setting->kepsek : '________________'); ?></b></p>
                <?php if (!empty($setting->nip)): ?>
                    <p style="font-size:12px;margin-top:0">NIP. <?= html_escape($setting->nip); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="kaki">
            Surat ini dibuat otomatis oleh sistem <?= html_escape(isset($setting->nama_aplikasi) ? $setting->nama_aplikasi : ''); ?>
            pada <?= date('d/m/Y H:i'); ?>.
        </div>
    </div>
</body>
</html>
