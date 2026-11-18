<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
//$route['default_controller'] = 'welcome';
//$route['404_override'] = '';
//$route['translate_uri_dashes'] = FALSE;

$route['default_controller'] = 'auth'; //'install';
$route['login'] = 'Auth/index';
// Halaman publik cek keaslian kwitansi: /cekkwitansi/{id}/{kode}
$route['cekkwitansi/(:num)/(:any)'] = 'cekkwitansi/index/$1/$2';
$route['cekkwitansi'] = 'cekkwitansi/index';

$route['logout'] = 'Auth/logout';
$route['404_override'] = 'auth';
$route['translate_uri_dashes'] = FALSE;

/*
 * Alat instalasi / update / pembanding database hanya berguna di komputer
 * server sendiri. Dari akses jaringan luar, route-nya ditutup (404) agar
 * tidak bisa dipakai menimpa atau mengubah struktur database.
 */
$__remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$__lokal = ($__remote === '')
    || in_array($__remote, ['127.0.0.1', '::1'], true);
if (!$__lokal) {
    foreach (['install', 'update', 'compare'] as $__alat) {
        $route[$__alat] = 'blocked';
        $route[$__alat . '/(:any)'] = 'blocked';
        $route[$__alat . '/(:any)/(:any)'] = 'blocked';
    }
    unset($__alat);
}
unset($__remote, $__lokal);

/*
 * 'compare' (pembanding database) bisa MENGHAPUS tabel yang tidak terdaftar di
 * assets/app/db/database.json. Alat ini hanya untuk pengembang, jadi ditutup
 * total — termasuk dari komputer server sendiri.
 */
foreach (['compare'] as $__alat) {
    $route[$__alat] = 'blocked';
    $route[$__alat . '/(:any)'] = 'blocked';
    $route[$__alat . '/(:any)/(:any)'] = 'blocked';
}
unset($__alat);
