@echo off
chcp 65001 >nul
title DeepSeek Harness + Graphify (cbt-sekolah)
cd /d "%~dp0"

if not exist "graphify-out\graph.json" (
    echo Graphify graph belum ada. Jalankan dulu:
    echo   graphify extract . --code-only
    echo   graphify cluster-only .
    echo.
    pause
    exit /b 1
)

echo Menjalankan DeepSeek Harness dengan Graphify MCP...
echo Workspace: %CD%
echo Web UI    : http://127.0.0.1:3080
echo.
dsh web --no-open --patch "%CD%\.dsh\graphify.cordis.yml" %*
pause
