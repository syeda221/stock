@echo off
echo ================================================
echo Warehouse Management System - Quick Start
echo ================================================
echo.

echo Starting Laravel Development Server...
echo.
echo Server will start on: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
echo ================================================
echo.

cd /d "%~dp0"
php artisan serve --host=0.0.0.0 --port=8000

pause
