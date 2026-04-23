@echo off
title QC Lab Server
cd /d "c:\qc"

echo ============================================
echo   QC Lab Tracking Server
echo ============================================
echo.

echo [1/2] Starting Redis...
powershell -ExecutionPolicy Bypass -File ".\tools\redis\start-redis.ps1"
echo [OK] Redis started.
echo.

echo [2/2] Starting Laravel Server...
echo.
echo   URL: http://10.22.0.23:443
echo   Press Ctrl+C to stop
echo ============================================
echo.

php artisan serve --host=0.0.0.0 --port=443
