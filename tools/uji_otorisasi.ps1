param(
    [string]$BaseUrl = 'http://localhost/cbt-sekolah',
    [string]$AdminUser = 'wildanzet06',
    [string]$AdminPass = '',
    [string]$GuruUser = 'nabil123',
    [string]$GuruPass = '',
    [string]$SiswaUser = 'sinabil',
    [string]$SiswaPass = ''
)

$ErrorActionPreference = 'Stop'

function New-LoginSession([string]$user, [string]$pass) {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login = Invoke-WebRequest -Uri "$BaseUrl/auth" -WebSession $session -UseBasicParsing
    $csrf = ([regex]::Match($login.Content, 'name="csrf_token"[^>]*value="([^"]+)"')).Groups[1].Value
    if (-not $csrf) { throw "CSRF tidak ditemukan di halaman login" }
    Invoke-WebRequest -Uri "$BaseUrl/auth/cek_login" -Method Post -WebSession $session -UseBasicParsing -Body @{
        identity = $user; password = $pass; csrf_token = $csrf
    } | Out-Null
    $cookie = ($session.Cookies.GetCookies($BaseUrl) | Where-Object { $_.Name -eq 'csrf_cookie' }).Value
    if (-not $cookie) { throw "Login gagal untuk $user" }
    return @{ Session = $session; Token = $cookie }
}

function Get-HttpStatus {
    param([string]$Uri, [string]$Method = 'GET', $Session = $null, [hashtable]$Body = @{})
    try {
        $params = @{ Uri = $Uri; Method = $Method; UseBasicParsing = $true; MaximumRedirection = 0 }
        if ($Session) { $params.WebSession = $Session }
        if ($Method -eq 'POST') { $params.Body = $Body }
        $r = Invoke-WebRequest @params
        return [int]$r.StatusCode
    } catch {
        $resp = $_.Exception.Response
        if ($resp) { return [int]$resp.StatusCode }
        return -1
    }
}

function Invoke-Case {
    param([string]$Name, [string]$Expected, [scriptblock]$Action)
    try {
        $actual = & $Action
        $ok = ($Expected -eq '403' -and $actual -eq 403) -or
              ($Expected -eq 'BUKAN200' -and $actual -ne 200) -or
              ($Expected -eq '200' -and $actual -eq 200) -or
              ($Expected -eq 'JSON_TOLAK' -and ($actual -eq 'tolak' -or $actual -eq 403))
        [pscustomobject]@{ Uji = $Name; Harapan = $Expected; Hasil = $actual; Status = if ($ok) { 'LULUS' } else { 'GAGAL' } }
    } catch {
        [pscustomobject]@{ Uji = $Name; Harapan = $Expected; Hasil = 'ERROR: ' + $_.Exception.Message; Status = 'GAGAL' }
    }
}

$admin = $null; $guru = $null; $siswa = $null
if ($AdminPass) { $admin = New-LoginSession $AdminUser $AdminPass }
if ($GuruPass)  { $guru  = New-LoginSession $GuruUser $GuruPass }
if ($SiswaPass) { $siswa = New-LoginSession $SiswaUser $SiswaPass }

$results = @()

# Anonim tidak boleh membuka halaman nilai ujian.
$results += Invoke-Case 'anonim: GET cbtnilai' 'BUKAN200' {
    Get-HttpStatus -Uri "$BaseUrl/cbtnilai"
}

if ($siswa) {
    $results += Invoke-Case 'siswa: POST rapor/saveRaporAdmin' '403' {
        Get-HttpStatus -Uri "$BaseUrl/rapor/saveRaporAdmin" -Method POST -Session $siswa.Session -Body @{ csrf_token = $siswa.Token }
    }
    $results += Invoke-Case 'siswa: POST cbtnilai/simpanKoreksi' 'BUKAN200' {
        Get-HttpStatus -Uri "$BaseUrl/cbtnilai/simpanKoreksi" -Method POST -Session $siswa.Session -Body @{ csrf_token = $siswa.Token; siswa = 1; jadwal = 1; jenis = 'pg_nilai'; nilai = '[]' }
    }
}

if ($guru) {
    $results += Invoke-Case 'guru lain: POST guruview/save id_guru=999' '403' {
        Get-HttpStatus -Uri "$BaseUrl/guruview/save" -Method POST -Session $guru.Session -Body @{ csrf_token = $guru.Token; id_guru = 999; nip = '00000000'; nama_guru = 'Uji' }
    }
    $results += Invoke-Case 'guru: POST rapor/saveRaporAdmin' '403' {
        Get-HttpStatus -Uri "$BaseUrl/rapor/saveRaporAdmin" -Method POST -Session $guru.Session -Body @{ csrf_token = $guru.Token }
    }
    $results += Invoke-Case 'guru: POST rapor/importHarian data palsu' 'JSON_TOLAK' {
        try {
            $r = Invoke-WebRequest -Uri "$BaseUrl/rapor/importHarian" -Method POST -Session $guru.Session -UseBasicParsing -Body @{
                csrf_token = $guru.Token
                siswa = '[{"id_siswa":1,"id_mapel":999,"id_kelas":999}]'
            }
            if ($r.Content -match '"status"\s*:\s*false') { 'tolak' } else { 'diterima' }
        } catch {
            if ($_.Exception.Response -and [int]$_.Exception.Response.StatusCode -eq 403) { 'tolak' } else { throw }
        }
    }
    $results += Invoke-Case 'guru: POST kelasmateri/hapusMateri id palsu' 'JSON_TOLAK' {
        try {
            $r = Invoke-WebRequest -Uri "$BaseUrl/kelasmateri/hapusMateri" -Method POST -Session $guru.Session -UseBasicParsing -Body @{
                csrf_token = $guru.Token; id_materi = 999999
            }
            if ($r.Content -match '"status"\s*:\s*false') { 'tolak' } else { 'diterima' }
        } catch {
            if ($_.Exception.Response -and [int]$_.Exception.Response.StatusCode -eq 403) { 'tolak' } else { throw }
        }
    }
}

if ($admin) {
    $results += Invoke-Case 'admin: GET rapor' '200' {
        Get-HttpStatus -Uri "$BaseUrl/rapor" -Session $admin.Session
    }
    $results += Invoke-Case 'admin: GET cbtnilai' '200' {
        Get-HttpStatus -Uri "$BaseUrl/cbtnilai" -Session $admin.Session
    }
}

$results | Format-Table -AutoSize
$gagal = ($results | Where-Object { $_.Status -eq 'GAGAL' }).Count
Write-Host ("Total: {0}, gagal: {1}" -f $results.Count, $gagal)
exit $(if ($gagal -gt 0) { 1 } else { 0 })
