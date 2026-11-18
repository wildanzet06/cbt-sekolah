@echo off
chcp 65001 >nul
title Pasang CBT Sekolah

rem Folder aplikasi = folder tempat pasang.bat ini berada
cd /d "%~dp0"

rem --- Cari PHP: PATH dulu, lalu lokasi umum Laragon/XAMPP ---
set "PHP="
where php >nul 2>nul
if not errorlevel 1 set "PHP=php"

if not defined PHP (
    for /d %%i in (C:\laragon\bin\php\php-*) do (
        if exist "%%i\php.exe" set "PHP=%%i\php.exe"
    )
)
if not defined PHP (
    if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
)
if not defined PHP (
    echo.
    echo PHP tidak ditemukan.
    echo Buka aplikasi ini lewat Terminal Laragon ^(menu Laragon ^> Terminal^),
    echo lalu jalankan: php tools\pasang.php
    echo.
    pause
    exit /b 1
)

if not exist "tools\pasang.php" (
    echo.
    echo Berkas tools\pasang.php tidak ditemukan di folder:
    echo   %CD%
    echo Pastikan pasang.bat dijalankan dari folder aplikasi cbt-sekolah.
    echo.
    pause
    exit /b 1
)

echo ============================================
echo  PEMASANG CEPAT CBT SEKOLAH
echo  Database default: cbt ^(user root, tanpa sandi^)
echo  Admin default   : admin / Admin12345
echo --------------------------------------------
echo  PHP : %PHP%
echo ============================================
echo.
set /p DB="Nama database [cbt]: "
if "%DB%"=="" set DB=cbt
set /p ADM="Username admin [admin]: "
if "%ADM%"=="" set ADM=admin

echo.
"%PHP%" "tools\pasang.php" --db=%DB% --admin=%ADM%
set HASIL=%ERRORLEVEL%
echo.
if not "%HASIL%"=="0" (
    echo Pemasangan GAGAL ^(kode %HASIL%^). Periksa pesan di atas.
) else (
    echo Pemasangan selesai. Buka http://localhost/cbt-sekolah/auth
)
echo.
pause
