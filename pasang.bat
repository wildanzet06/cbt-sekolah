@echo off
chcp 65001 >nul
title Pasang CBT Sekolah
cd /d "%~dp0.."

where php >nul 2>nul
if errorlevel 1 (
    echo PHP tidak ditemukan di PATH.
    echo Buka lewat terminal Laragon, atau jalankan: C:\laragon\bin\php\php-8.3.35-Win32-vs16-x64\php.exe tools\pasang.php
    pause
    exit /b 1
)

echo ============================================
echo  PEMASANG CEPAT CBT SEKOLAH
echo  Database default: cbt (user root, tanpa sandi)
echo  Admin default   : admin / Admin12345
echo ============================================
echo.
set /p DB="Nama database [cbt]: "
if "%DB%"=="" set DB=cbt
set /p ADM="Username admin [admin]: "
if "%ADM%"=="" set ADM=admin

php tools\pasang.php --db=%DB% --admin=%ADM%
echo.
pause
