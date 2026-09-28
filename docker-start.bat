@echo off
setlocal
cd /d "%~dp0"

echo =========================================
echo KMU-v2 Docker
echo =========================================

if not exist ".env" (
    echo [ERROR] File .env belum ada.
    echo Copy .env lama dari project Laragon ke folder ini.
    echo Jangan generate APP_KEY/NIK_ENCRYPTION_KEY baru jika ingin tetap membaca data lama.
    exit /b 1
)

echo [1/3] Build image...
docker compose build
if errorlevel 1 exit /b 1

echo [2/3] Start app + Vite...
docker compose up -d
if errorlevel 1 exit /b 1

echo [3/3] Clear Laravel cache...
docker compose exec app php artisan optimize:clear

echo.
echo =========================================
echo APP   : http://localhost:8000
echo VITE  : http://localhost:5173
echo =========================================
echo.
docker compose ps
