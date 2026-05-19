@echo off
echo ================================================
echo XAMPP PHP 8.2 Configuration Script
echo ================================================
echo.

echo [Step 1] Stopping Apache...
taskkill /F /IM httpd.exe >nul 2>&1
echo Apache stopped (if it was running)
echo.

echo [Step 2] Backing up old PHP folder...
if exist "C:\xampp\php" (
    if not exist "C:\xampp\php_8.1.17_backup" (
        echo Creating backup: C:\xampp\php_8.1.17_backup
        move "C:\xampp\php" "C:\xampp\php_8.1.17_backup" >nul 2>&1
        echo Backup created successfully!
    ) else (
        echo Backup already exists, removing old php folder...
        rmdir /S /Q "C:\xampp\php" >nul 2>&1
    )
) else (
    echo No existing PHP folder found.
)
echo.

echo [Step 3] Finding PHP 8.2 installation...
if exist "C:\xampp\php-8.2.29-nts-Win32-vs16-x64" (
    echo Found: C:\xampp\php-8.2.29-nts-Win32-vs16-x64
    echo Creating symbolic link...
    mklink /D "C:\xampp\php" "C:\xampp\php-8.2.29-nts-Win32-vs16-x64"
    echo Symbolic link created successfully!
) else (
    echo ERROR: PHP 8.2.29 folder not found!
    echo Please download XAMPP 8.2 from: https://www.apachefriends.org/
    pause
    exit /b 1
)
echo.

echo [Step 4] Verifying PHP version...
C:\xampp\php\php.exe -v
echo.

echo ================================================
echo Configuration Complete!
echo ================================================
echo.
echo Next Steps:
echo 1. Open XAMPP Control Panel
echo 2. Start Apache
echo 3. Open browser: http://localhost/Warehouse-Management-System/public
echo.
echo If Apache doesn't start, check:
echo - Port 80 is not used by another program
echo - Run XAMPP Control Panel as Administrator
echo.
pause
