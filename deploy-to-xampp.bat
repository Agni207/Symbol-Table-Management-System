@echo off
echo ============================================
echo  Symbol Table Management System - Deployer
echo ============================================
echo.

set "SOURCE=%~dp0"
set "DEST=C:\xampp\htdocs\symbol-table-system"

echo Deploying to: %DEST%
echo.

if not exist "C:\xampp\htdocs\" (
    echo ERROR: XAMPP not found at C:\xampp\
    echo Please install XAMPP or update the path in this script.
    pause
    exit /b 1
)

echo Creating destination folder...
mkdir "%DEST%" 2>nul
mkdir "%DEST%\api" 2>nul
mkdir "%DEST%\database" 2>nul

echo Copying files...
xcopy "%SOURCE%index.html" "%DEST%\" /Y /Q
xcopy "%SOURCE%api\*.*" "%DEST%\api\" /Y /Q
xcopy "%SOURCE%database\*.*" "%DEST%\database\" /Y /Q

echo.
echo ============================================
echo  DONE! Files copied to XAMPP.
echo ============================================
echo.
echo NEXT STEPS:
echo 1. Start XAMPP (Apache + MySQL)
echo 2. Open phpMyAdmin: http://localhost/phpmyadmin
echo 3. Import: database/schema.sql
echo 4. Open app: http://localhost/symbol-table-system/
echo.
pause
