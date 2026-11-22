<?php
/**
 * Mode "embed": halaman lama ditampilkan di dalam tab Hasil Ujian.
 * Navbar + sidebar tidak dirender agar tidak muncul sidebar ganda di dalam halaman.
 */
$__embed = (bool) $this->input->get('embed');

// Mode embed hanya boleh dipakai oleh permintaan yang benar-benar berasal
// dari iframe tab Hasil Ujian. Browser modern mengirim Sec-Fetch-Dest: iframe;
// tab Hasil Ujian juga membuka halaman dengan ?dari=hasil&embed=1.
// Tanpa syarat ini, sesi embed bisa membuat navbar/sidebar hilang di tab biasa.
$__dari_hasil = strtolower((string) $this->input->get('dari')) === 'hasil';
$__dest = strtolower(isset($_SERVER['HTTP_SEC_FETCH_DEST']) ? (string) $_SERVER['HTTP_SEC_FETCH_DEST'] : '');
$__referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
$__dari_iframe = ($__dest === 'iframe') || ($__referer !== '' && stripos($__referer, 'dari=hasil') !== false);

if ($__embed || $__dari_hasil) {
    if ($__dari_iframe) {
        $this->session->set_userdata('embed_iframe', time());
    }
    $__embed = true;
} else {
    $__embed_ts = (int) $this->session->userdata('embed_iframe');
    $__embed_kelas = strtolower((string) $this->router->fetch_class());
    $__embed_controllers = array('cbtnilai', 'cbtanalisis', 'cbtrekap', 'nilaimanual');
    if ($__dari_iframe && $__embed_ts > 0 && (time() - $__embed_ts) < 7200
        && in_array($__embed_kelas, $__embed_controllers, true)) {
        $__embed = true;
    }
}
?>
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
    <!-- iCheck -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/jqvmap/jqvmap.min.css">
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
    <!-- Datetime picker -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/jquery-datetimepicker/jquery.datetimepicker.min.css">
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet"
          href="<?= base_url() ?>/assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
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

    <!-- fonts -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/poppins.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/calibri.css">
    <!--
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/montserrat.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/fonts.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/uthmanic.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/scheherazade.css">
	-->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/show.toast.css">

    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/adminlte.min.css">

    <!-- textarea editor -->
    <!-- summernote -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/summernote/summernote-bs4.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/summernote/plugin/audio/summernote-audio.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/katex/katex.css">
    <!-- /texarea editor; -->
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/mystyle.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/DualSelectList/css/bala.DualSelectList.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/weekCalendar.css">

    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/bootstrap-icon/bootstrap-icons.css">

    <link href="<?= base_url() ?>/assets/plugins/ios-switch/component-custom-switch.min.css" rel="stylesheet">
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

    <script type="text/javascript"
            src="<?= base_url() ?>/assets/plugins/DualSelectList/js/bala.DualSelectList.jquery.js"></script>

    <script defer src="<?= base_url() ?>/assets/plugins/katex/contrib/auto-render.min.js" onload="renderMathInElement(document.body);"></script>
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>/assets/app/css/stylised.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/contextmenu/jquery.contextmenu.css">
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>/assets/plugins/fields-linker/fieldsLinker.css">
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>/assets/plugins/pignose/css/pignose.calendar.css">

    <style>
        page {
            background: white;
            display: block;
            margin: 0 auto;
            margin-bottom: 0.5cm;
            box-shadow: none;
        }

        page[size="A4"][layout="potrait"] {
            width: 21cm;
            height: 29.7cm;
        }

        page[size="A4"] {
            width: 29.7cm;
            height: 21cm;
        }

        .linker-list p {
            margin-bottom: .5rem;
            margin-top: .5rem;
        }</style>
    <?php
    // Data Siswa & Data Guru memakai tampilan bawaan GarudaCBT (upstream).
    // Tema lokal (blue-gray + ui-rapi) tetap dipakai di halaman lain.
    $__upstream_pages = array('datasiswa', 'dataguru');
    $__upstream_style = in_array(strtolower((string) $this->router->fetch_class()), $__upstream_pages, true);
    if (!$__upstream_style): ?>
        <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/blue-gray-theme.css">
        <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/ui-rapi.css">
    <?php else: ?>
        <style>
            /* Hanya warna sidebar yang dikembalikan ke tema lokal. */
            .main-sidebar,
            .main-sidebar.sidebar-light-teal { background: #20364d; }
            .brand-link,
            .main-sidebar .brand-link.bg-white {
                background: #192c40 !important;
                border-bottom-color: rgba(255, 255, 255, .1);
                color: #fff;
            }
            .main-sidebar.sidebar-light-teal .brand-link .brand-text { color: #fff; }
            .main-sidebar.sidebar-light-teal .user-panel .info a { color: #eef5fc; }
            .main-sidebar.sidebar-light-teal .user-panel .info a:hover,
            .main-sidebar.sidebar-light-teal .user-panel a:hover { color: #fff; }
            .main-sidebar .user-panel { border-bottom-color: rgba(255, 255, 255, .12); }
            .main-sidebar.sidebar-light-teal .user-panel .text-muted { color: #bccde0 !important; }
            .main-sidebar.sidebar-light-teal .nav-sidebar .nav-link { color: #eef5fc; }
            .main-sidebar.sidebar-light-teal .nav-sidebar .nav-header { color: #a8bed6; }
            .main-sidebar.sidebar-light-teal .nav-sidebar .nav-treeview > .nav-item > .nav-link { color: #cbdaea; }
            .main-sidebar.sidebar-light-teal .nav-sidebar > .nav-item:hover > .nav-link,
            .main-sidebar.sidebar-light-teal .nav-sidebar > .nav-item > .nav-link:hover,
            .main-sidebar.sidebar-light-teal .nav-sidebar > .nav-item > .nav-link:focus,
            .main-sidebar.sidebar-light-teal .nav-sidebar > .nav-item > .nav-link:active,
            .main-sidebar.sidebar-light-teal .nav-sidebar .nav-treeview > .nav-item > .nav-link:hover {
                background: rgba(255, 255, 255, .12);
                color: #fff;
            }
            .main-sidebar.sidebar-light-teal .nav-sidebar > .nav-item > .nav-link.active,
            .main-sidebar.sidebar-light-teal .nav-sidebar > .nav-item.menu-open > .nav-link {
                background: #2f6fbd;
                color: #fff;
            }
            .main-sidebar.sidebar-light-teal .nav-sidebar .nav-treeview > .nav-item > .nav-link.active,
            .main-sidebar.sidebar-light-teal .nav-sidebar .nav-treeview > .nav-item > .nav-link.active:hover {
                background: rgba(77, 145, 220, .32);
                color: #fff;
            }
        </style>
    <?php endif; ?>
    <?php if ($__embed): ?>
        <style>
            /* Mode embed: tanpa navbar/sidebar, konten memakai lebar penuh. */
            .content-wrapper { margin-left: 0 !important; min-height: 100vh; }
            .main-footer { display: none !important; }
            body { background: #fff; }
        </style>
    <?php endif; ?>
    <?php if ((string) $this->input->get('embed') === '1'): ?>
        <style>
            /* Mode embed (iframe Hasil Ujian): sembunyikan layout luar */
            .main-header, .main-sidebar, .main-footer, .content-header,
            .navbar, .breadcrumb { display: none !important; }
            .wrapper { display: block !important; }
            .content-wrapper { margin-left: 0 !important; padding-top: 0 !important; min-height: auto !important; background: #fff !important; }
            body { background: #fff !important; }
            .content { padding: 0 !important; }
            .content > .container-fluid { padding: 12px !important; }
        </style>
    <?php endif; ?>
<meta name="cbt-base-url" content="<?= html_escape(base_url()) ?>">
<meta name="cbt-tp" content="<?= html_escape($tp_active->tahun) ?>">
<meta name="cbt-smt" content="<?= html_escape($smt_active->smt) ?>">
<meta name="cbt-id-tp" content="<?= html_escape($tp_active->id_tp) ?>">
<meta name="cbt-id-smt" content="<?= html_escape($smt_active->id_smt) ?>">
</head>
<script src="<?= base_url() ?>assets/app/js/app-config.js"></script>
<script src="<?= base_url() ?>/assets/app/js/generate.js"></script>
<script src="<?= base_url() ?>assets/app/js/app-helpers.js"></script>

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
    $str = str_replace("Fri", "Jum'at", $str);
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
    $str = str_replace("Aug", "Agt", $str);
    $str = str_replace("Sep", "Sep", $str);
    $str = str_replace("Oct", "Okt", $str);
    $str = str_replace("Nov", "Nov", $str);
    $str = str_replace("Dec", "Des", $str);
    $str = str_replace("Mon", "Senin", $str);
    $str = str_replace("Tue", "Selasa", $str);
    $str = str_replace("Wed", "Rabu", $str);
    $str = str_replace("Thu", "Kamis", $str);
    $str = str_replace("Fri", "Jum'at", $str);
    $str = str_replace("Sat", "Sabtu", $str);
    $str = str_replace("Sun", "Minggu", $str);
    return $str;
}

?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed text-sm" onload="startTime()">
<div class="wrapper">

    <?php if (!$__embed): ?>
        <!-- Navbar -->
        <?php require_once("navbar.php"); ?>

        <!-- Sidebar -->
        <?php require_once("sidebar.php"); ?>
        <!-- /.sidebar -->
    <?php endif; ?>
