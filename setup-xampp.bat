@echo off
echo ================================================
echo Warehouse Management System - XAMPP Setup
echo ================================================
echo.

echo [1/5] Clearing Laravel Cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo Cache cleared successfully!
echo.

echo [2/5] Checking .env file...
if not exist .env (
    echo .env file not found! Copying from .env.example...
    copy .env.example .env
    echo Please edit .env file with your database credentials
    pause
)
echo .env file exists!
echo.

echo [3/5] Generating Application Key...
php artisan key:generate
echo Application key generated!
echo.

echo [4/5] Setting Permissions...
icacls storage /grant Everyone:(OI)(CI)F /T >nul 2>&1
icacls bootstrap\cache /grant Everyone:(OI)(CI)F /T >nul 2>&1
echo Permissions set successfully!
echo.

echo [5/5] Checking Database Connection...
php artisan migrate:status
if errorlevel 1 (
    echo.
    echo WARNING: Database connection failed!
    echo Please check your .env file and ensure MySQL is running in XAMPP
    echo.
) else (
    echo Database connection successful!
)
echo.

echo ================================================
echo Setup Complete!
echo ================================================
echo.
echo Your application is ready to use with XAMPP
echo.
echo Access URLs:
echo   - http://localhost/Warehouse-Management-System/public
echo   - http://warehouse.local (if virtual host configured)
echo.
echo Next Steps:
echo   1. Start Apache and MySQL in XAMPP Control Panel
echo   2. Open browser and visit the URL above
echo   3. Login with your credentials
echo.
pause
