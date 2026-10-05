<!DOCTYPE html>
<html>

<head>

    <!-- Meta Tag -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $judul ?></title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <?php $logo_app = $setting->logo_kiri == null ? base_url() . 'assets/img/favicon.png' : base_url() . $setting->logo_kiri; ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <!-- Required CSS -->
    <!-- v3 -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet"
          href="<?= base_url() ?>/assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/v4-shims.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/Ionicons/css/ionicons.min.css">
    <!-- pace-progress -->
    <link rel="stylesheet"
          href="<?= base_url() ?>/assets/plugins/pace-progress/themes/silver/pace-theme-center-circle.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- multi select -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/multiselect/css/multi-select.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- summernote -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/summernote/summernote-bs4.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/jquery.toast.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/toastr/toastr.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/dropify/css/dropify.min.css">

    <!-- Datatables Buttons -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

    <!-- textarea editor -->
    <!-- summernote -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/summernote/summernote-bs4.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/katex/katex.css">
    <!-- /texarea editor; -->

    <!-- fonts -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/poppins.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/calibri.css">

    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/adminlte.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/mystyle.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/show.toast.css">
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>/assets/plugins/fields-linker/fieldsLinker.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- jQuery -->
    <script src="<?= base_url() ?>/assets/plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="<?= base_url() ?>/assets/plugins/jquery-ui/jquery-ui.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="<?= base_url() ?>/assets/plugins/sweetalert2/sweetalert2.min.js"></script>

    <script defer src="<?= base_url() ?>/assets/plugins/katex/contrib/auto-render.min.js" onload="renderMathInElement(document.body);"></script>

    <style>
        .linker-list p {
            margin-bottom: .5rem;
            margin-top: .5rem;
        }
    </style>
    <style>
        /* ---- Navbar siswa: satu baris, rapi di HP ---- */
        .navbar-siswa { flex-wrap: nowrap; padding-left: .5rem; padding-right: .5rem; }
        .navbar-siswa .judul-wrap { min-width: 0; line-height: 1.15; }
        .navbar-siswa .judul-app { font-size: 1.15rem; font-weight: 500; }
        .navbar-siswa .judul-sub { font-size: .75rem; opacity: .9; }
        .navbar-siswa .btn { white-space: nowrap; }
        @media (max-width: 575.98px) {
            .navbar-siswa .judul-app { font-size: .95rem; }
            .navbar-siswa .judul-sub { font-size: .68rem; }
        }
    </style>
</head>

<script type="text/javascript">
    let base_url = '<?=base_url()?>';
</script>

<?php

function buat_tanggal($str)
{
    $str = str_replace("Jan", "Januari", $str);
    $str = str_replace("Feb", "Februari", $str);
    $str = str_replace("Mar", "Maret", $str);
    $str = str_replace("Apr", "April", $str);
    $str = str_replace("May", "Mei", $str);
    $str = str_replace("Jun", "Juni", $str);
    $str = str_replace("Jul", "Juli", $str);
    $str = str_replace("Aug", "Agustus", $str);
    $str = str_replace("Sep", "September", $str);
    $str = str_replace("Oct", "Oktober", $str);
    $str = str_replace("Nov", "Nopember", $str);
    $str = str_replace("Dec", "Desember", $str);
    $str = str_replace("Mon", "Senin", $str);
    $str = str_replace("Tue", "Selasa", $str);
    $str = str_replace("Wed", "Rabu", $str);
    $str = str_replace("Thu", "Kamis", $str);
    $str = str_replace("Fri", "Jumat", $str);
    $str = str_replace("Sat", "Sabtu", $str);
    $str = str_replace("Sun", "Minggu", $str);
    return $str;
}

function singkat_tanggal($str)
{
    $str = str_replace("Jan", "Jan", $str);
    $str = str_replace("Feb", "Feb", $str);
    $str = str_replace("Mar", "Mar", $str);
    $str = str_replace("Apr", "Apr", $str);
    $str = str_replace("May", "Mei", $str);
    $str = str_replace("Jun", "Jun", $str);
    $str = str_replace("Jul", "Jul", $str);
    $str = str_replace("Aug", "Aug", $str);
    $str = str_replace("Sep", "Sep", $str);
    $str = str_replace("Oct", "Okt", $str);
    $str = str_replace("Nov", "Nov", $str);
    $str = str_replace("Dec", "Des", $str);
    $str = str_replace("Mon", "Sen", $str);
    $str = str_replace("Tue", "Sel", $str);
    $str = str_replace("Wed", "Rab", $str);
    $str = str_replace("Thu", "Kam", $str);
    $str = str_replace("Fri", "Jum", $str);
    $str = str_replace("Sat", "Sab", $str);
    $str = str_replace("Sun", "Min", $str);
    return $str;
}

$dash = $this->uri->segment(1);
$cbt = $this->uri->segment(2);
$exludes = ["dashboard", "penilaian"];
$dnone = in_array($dash, $exludes) || in_array($cbt, $exludes) ? 'd-none' : '';

$display_clock = $this->uri->segment(2) == "penilaian" ? '' : 'd-none';
$display_logout = $this->uri->segment(2) == "penilaian" ? 'd-none' : '';

// Tombol pindah antara Menu Utama (dashboard) dan Ujian/Ulangan (siswa/cbt).
// Hanya tampil pada dua halaman itu (jumlah segmen alamat dicek agar tidak
// muncul di tengah ujian yang sedang berlangsung).
$jml_segmen    = $this->uri->total_segments();
$di_menu       = ($dash == 'dashboard' && $jml_segmen == 1);
$di_ujian      = ($dash == 'siswa' && $cbt == 'cbt' && $jml_segmen == 2);
$tampil_tab    = $di_menu || $di_ujian;
if ($tampil_tab) {
    $dnone = 'd-none'; // tombol Beranda lama tidak diperlukan di sini
}
?>

<body class="layout-top-nav layout-navbar-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-dark navbar-green border-bottom-0 navbar-siswa">
        <ul class="navbar-nav <?= $dnone ?>" id="back">
            <li class="nav-item">
                <a href="<?= base_url('dashboard') ?>" type="button" class="btn btn-success btn-sm" id="url-back">
                    <i class="fas fa-arrow-left"></i><span class="d-none d-sm-inline-block ml-2">Beranda</span>
                </a>
            </li>
        </ul>

        <?php if ($tampil_tab): ?>
        <ul class="navbar-nav" id="tab-halaman">
            <li class="nav-item">
                <div class="btn-group btn-group-sm" role="group" aria-label="Pindah halaman">
                    <a href="<?= base_url('dashboard') ?>"
                       class="btn <?= $di_menu ? 'btn-light font-weight-bold' : 'btn-outline-light' ?>">
                        <i class="fas fa-th-large"></i><span class="d-none d-sm-inline-block ml-1">Menu</span>
                    </a>
                    <a href="<?= base_url('siswa/cbt') ?>"
                       class="btn <?= $di_ujian ? 'btn-light font-weight-bold' : 'btn-outline-light' ?>">
                        <i class="fas fa-edit"></i><span class="d-none d-sm-inline-block ml-1">Ujian</span>
                    </a>
                </div>
            </li>
        </ul>
        <?php endif; ?>

        <div class="mx-2 flex-grow-1 text-white text-center judul-wrap">
            <span class="d-block text-truncate judul-app"><?= $setting->nama_aplikasi ?></span>
            <small class="d-block text-truncate judul-sub">TP <?= $tp_active->tahun ?> &bull; Smt <?= $smt_active->smt ?></small>
        </div>

        <ul class="navbar-nav ml-auto <?= $display_clock ?>">
            <li class="nav-item">
                <div id="live-clock" class="text-right text-white"></div>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto <?= $display_logout ?>">
            <li class="nav-item dropdown">
                <a href="#" class="btn btn-outline-light btn-sm dropdown-toggle" id="menuPengaturan"
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-cog"></i><span class="d-none d-sm-inline-block ml-2">Pengaturan</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="menuPengaturan">
                    <a class="dropdown-item py-2" href="<?= base_url('pembayaran/bayar_spp'); ?>">
                        <i class="fas fa-money-bill-wave fa-fw mr-2 text-warning"></i> Bayar SPP
                    </a>
                    <a class="dropdown-item py-2" href="<?= base_url('akun/kehadiran'); ?>">
                        <i class="fas fa-user-check fa-fw mr-2 text-success"></i> Kehadiran Saya
                    </a>
                    <a class="dropdown-item py-2" href="<?= base_url('akun/password'); ?>">
                        <i class="fas fa-key fa-fw mr-2 text-secondary"></i> Ganti Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item py-2 text-danger" href="#" onclick="logout(); return false;">
                        <i class="fas fa-sign-out-alt fa-fw mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <script type="text/javascript">
        const isLogin = localStorage.getItem('garudaCBT.login')
        const isCbtMode = isLogin ? isLogin === '1' : false
        if (isCbtMode) {
            $("a#url-back").attr("href", base_url + 'siswa/cbt');
            if ('<?= $cbt ?>' === 'cbt') {
                $('#back').addClass('d-none')
            }
        }
    </script>
