@echo off
title QC Lab Server
cd /d "c:\qc"

set APP_HOST=127.0.0.1
set APP_PORT=8000
set VITE_HOST=127.0.0.1
set VITE_PORT=5173

echo ============================================
echo   QC Lab Tracking Server
echo ============================================
echo.
echo   Working Directory: %cd%
echo   App URL          : http://%APP_HOST%:%APP_PORT%
echo.

echo [1/5] Starting Redis...
powershell -ExecutionPolicy Bypass -File "c:\qc\tools\redis\start-redis.ps1"
echo [OK] Redis started.
echo.

echo [2/5] Checking PHP...
if not exist "C:\xampp\php\php.exe" (
    echo [ERROR] PHP not found at C:\xampp\php\php.exe
    pause
    exit /b 1
)
echo [OK] PHP found.
echo.

echo [3/5] Starting Vite Dev Server...
start /MIN "QC Vite" cmd /c "npm run dev -- --host %VITE_HOST% --port %VITE_PORT% --clearScreen false"
echo [OK] Vite started in background window.
echo.

echo [4/5] Starting Scheduler (Auto-updater)...
start /MIN "QC Scheduler" C:\xampp\php\php.exe artisan schedule:work
echo [OK] Scheduler started in background window.
echo.

echo [5/5] Starting Laravel Server...
echo   URL: http://%APP_HOST%:%APP_PORT%
echo   Press Ctrl+C to stop
echo ============================================
echo.

C:\xampp\php\php.exe artisan serve --host=%APP_HOST% --port=%APP_PORT%

echo.
echo [ERROR] Server stopped unexpectedly.
pause
