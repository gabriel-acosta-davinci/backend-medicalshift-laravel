@echo off
echo ========================================
echo Iniciando servidor Laravel con regenerador de tokens
echo ========================================
echo.
echo Iniciando servidor en http://localhost:8000
echo Iniciando regenerador de tokens cada 30 segundos
echo.
echo Presiona Ctrl+C para detener ambos procesos
echo ========================================
echo.

REM Iniciar el daemon de tokens en segundo plano
start "Token Regenerator" cmd /c "php regenerate-tokens-daemon.php"

REM Esperar un momento para que el daemon inicie
timeout /t 2 /nobreak >nul

REM Iniciar el servidor Laravel (esto bloqueará hasta que se detenga)
php artisan serve

REM Cuando se detenga el servidor, también detener el daemon
taskkill /FI "WINDOWTITLE eq Token Regenerator*" /T /F >nul 2>&1

echo.
echo Servidor y regenerador de tokens detenidos.

