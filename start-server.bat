@echo off
title QC Lab Server
cd /d "c:\qc"

echo ============================================
echo   QC Lab Tracking Server
echo ============================================
echo.
echo   Working Directory: %cd%
echo.

echo [1/4] Starting Redis...
powershell -ExecutionPolicy Bypass -File "c:\qc\tools\redis\start-redis.ps1"
echo [OK] Redis started.
echo.

echo [2/4] Checking PHP...
if not exist "C:\xampp\php\php.exe" (
    echo [ERROR] PHP not found at C:\xampp\php\php.exe
    pause
    exit /b 1
)
echo [OK] PHP found.
echo.

echo [3/4] Starting Scheduler (Auto-updater)...
start /MIN "QC Scheduler" C:\xampp\php\php.exe artisan schedule:work
echo [OK] Scheduler started in background window.
echo.

echo [4/4] Starting Laravel Server...
echo   URL: http://10.22.0.23:443
echo   Press Ctrl+C to stop
echo ============================================
echo.

C:\xampp\php\php.exe artisan serve --host=0.0.0.0 --port=443

echo.
echo [ERROR] Server stopped unexpectedly.
pause
