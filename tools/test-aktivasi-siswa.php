<?php
/**
 * Uji mandiri alur aktivasi akun siswa tanpa menyentuh database.
 *
 * Pemakaian: php tools/test-aktivasi-siswa.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Skrip ini hanya boleh dijalankan dari command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
define('BASEPATH', $root . '/system/');

class CI_Controller
{
    public $db;
    public $ion_auth;
    public $users;
    public $output;
}

class FakeQuery
{
    public function __construct(private $rowValue)
    {
    }

    public function row()
    {
        return $this->rowValue;
    }
}

class FakeDb
{
    public function get_where($table, $where)
    {
        return new FakeQuery(null);
    }
}

class FakeIonAuth
{
    public int $registerCalls = 0;
    public int $deleteCalls = 0;

    public function register(...$args)
    {
        $this->registerCalls++;
        return 77;
    }

    public function delete_user($id)
    {
        $this->deleteCalls++;
        return true;
    }
}

class FakeUsersModel
{
    public function getSiswaAktif()
    {
        return [
            (object) ['nis' => '900001', 'nama' => 'Siswa Tanpa Password', 'username' => 'tanpa.password', 'password' => '', 'aktif' => 0],
            (object) ['nis' => '900002', 'nama' => 'Siswa Tanpa Username', 'username' => '', 'password' => '12341234', 'aktif' => 0],
            (object) ['nis' => '900003', 'nama' => 'Siswa Siap Aktif', 'username' => 'siap.aktif', 'password' => '12341234', 'aktif' => 0],
        ];
    }
}

class FakeOutput
{
    public ?string $output = null;

    public function set_content_type($type)
    {
        return $this;
    }

    public function set_output($data)
    {
        $this->output = $data;
        return $this;
    }
}

require $root . '/application/controllers/Usersiswa.php';

$ref = new ReflectionClass('Usersiswa');
$controller = $ref->newInstanceWithoutConstructor();

$prop = static function (string $name, $value) use ($ref, $controller): void {
    $ref->getProperty($name)->setValue($controller, $value);
};
$prop('db', new FakeDb());
$ionAuth = new FakeIonAuth();
$prop('ion_auth', $ionAuth);
$prop('users', new FakeUsersModel());
$prop('output', new FakeOutput());

$aktifkan = $ref->getMethod('aktifkan');
$aktifkan->setAccessible(true);

$cases = [
    'password kosong' => [
        (object) ['nis' => '900001', 'nama' => 'Siswa Tanpa Password', 'username' => 'tanpa.password', 'password' => ''],
        false,
        'Password siswa',
    ],
    'username kosong' => [
        (object) ['nis' => '900002', 'nama' => 'Siswa Tanpa Username', 'username' => '', 'password' => '12341234'],
        false,
        'Username siswa',
    ],
    'data lengkap' => [
        (object) ['nis' => '900003', 'nama' => 'Siswa Siap Aktif', 'username' => 'siap.aktif', 'password' => '12341234'],
        true,
        'diaktifkan',
    ],
];

$gagal = 0;
foreach ($cases as $label => [$siswa, $expectedStatus, $expectedText]) {
    $result = $aktifkan->invoke($controller, $siswa);
    $statusOk = ($result['status'] === $expectedStatus);
    $msgOk = str_contains((string) $result['msg'], $expectedText);
    printf("%-18s status=%s pesan=%s\n", $label, $statusOk ? 'OK' : 'SALAH', $msgOk ? 'OK' : 'SALAH');
    if (!$statusOk || !$msgOk) {
        $gagal++;
        echo '  Hasil: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$registerCallsSebelum = $ionAuth->registerCalls;
$aktifkanSemua = $ref->getMethod('aktifkanSemua');
$aktifkanSemua->setAccessible(true);
$aktifkanSemua->invoke($controller);

$outputProp = $ref->getProperty('output')->getValue($controller);
$bulk = json_decode((string) $outputProp->output, true);
$bulkOk = (
    is_array($bulk)
    && ($bulk['status'] ?? null) === true
    && ($bulk['jumlah'] ?? null) === 1
    && $ionAuth->registerCalls === $registerCallsSebelum + 1
);
echo 'aktivasi massal     ' . ($bulkOk ? 'OK' : 'SALAH') . ' -> ' . json_encode($bulk, JSON_UNESCAPED_UNICODE) . "\n";
if (!$bulkOk) {
    $gagal++;
}

echo $gagal === 0 ? "\nSEMUA UJI LOLOS\n" : "\n$gagal UJI GAGAL\n";
exit($gagal === 0 ? 0 : 1);
