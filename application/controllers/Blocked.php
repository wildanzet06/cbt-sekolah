<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penutup route untuk alat internal (install / update / compare) yang
 * dipanggil dari luar komputer server. Lihat application/config/routes.php.
 */
class Blocked extends CI_Controller
{
    public function index()
    {
        show_404();
    }
}
