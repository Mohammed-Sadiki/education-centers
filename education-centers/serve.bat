@echo off
REM Script de démarrage du projet Education Centers
REM Utilise PHP 8.3 depuis C:\php83

SET PHP_PATH=C:\php83
SET PATH=%PHP_PATH%;%PATH%

echo ====================================
echo  Education Centers - Laravel Server
echo ====================================
echo PHP: %PHP_PATH%\php.exe
echo.

php artisan serve --host=127.0.0.1 --port=8000
