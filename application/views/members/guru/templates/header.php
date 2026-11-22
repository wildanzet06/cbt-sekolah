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
    <meta name="renderer" content="webkit"/>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" name="viewport">
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
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet"
          href="<?= base_url() ?>/assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
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
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/scheherazade.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/uthmanic.css">
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
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/font-material.css">

    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/bootstrap-icon/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>/assets/plugins/pignose/css/pignose.calendar.css">

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- jQuery -->
    <script src="<?= base_url() ?>/assets/plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="<?= base_url() ?>/assets/plugins/jquery-ui/jquery-ui.min.js"></script>

    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/jexcel/css/jexcel.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/jexcel/css/jsuites.css">
    <script src="<?= base_url() ?>/assets/plugins/jexcel/js/jexcel.js"></script>
    <script src="<?= base_url() ?>/assets/plugins/jexcel/js/jsuites.js"></script>

    <script defer src="<?= base_url() ?>/assets/plugins/katex/contrib/auto-render.min.js" onload="renderMathInElement(document.body);"></script>

    <style>
        .linker-list p {
            margin-bottom: .5rem;
            margin-top: .5rem;
        }</style>
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/blue-gray-theme.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/ui-rapi.css">
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
            .main-header, .main-sidebar, .main-footer, .content-header,
            .navbar, .breadcrumb { display: none !important; }
            .wrapper { display: block !important; }
            .content-wrapper { margin-left: 0 !important; padding-top: 0 !important; min-height: auto !important; background: #fff !important; }
            body { background: #fff !important; }
        </style>
    <?php endif; ?>
<meta name="cbt-base-url" content="<?= html_escape(base_url()) ?>">
<meta name="cbt-csrf-name" content="<?= html_escape($this->security->get_csrf_token_name()) ?>">
<meta name="cbt-csrf-hash" content="<?= html_escape($this->security->get_csrf_hash()) ?>">
</head>

<script src="<?= base_url() ?>assets/app/js/app-config.js"></script>
<script src="<?= base_url() ?>assets/app/js/guru-header.js"></script>
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

?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed text-sm" spellcheck="false" onload="startTime()">
<div class="wrapper">

    <?php if (!$__embed): ?>
        <!-- Navbar -->
        <?php require_once("navbar.php"); ?>

        <!-- Sidebar -->
        <?php require_once("sidebar.php"); ?>
        <!-- /.sidebar -->
    <?php endif; ?>
