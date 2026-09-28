@echo off
setlocal
cd /d "%~dp0"

echo =========================================
echo KMU-v2 Docker Tests
echo =========================================
echo.

echo Menjalankan test dalam container testing terpisah...
echo APP_ENV=testing ^| DB=:memory: ^| SESSION=array
echo.

docker compose --profile test run --rm test
set EXIT_CODE=%ERRORLEVEL%

echo.
if "%EXIT_CODE%"=="0" (
   echo =========================================
echo TEST PASS
   echo =========================================
) else (
   echo =========================================
echo TEST GAGAL - lihat output di atas.
   echo =========================================
)

exit /b %EXIT_CODE%
