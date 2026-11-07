@echo off
chcp 65001 >nul
setlocal
rem ============================================================================
rem  Conecta el celular Android a la caja por cable USB (adb reverse).
rem  Despues de ejecutar esto, el celular ve la caja como "localhost" y puede
rem  usar la camara sin https ni Wi-Fi.
rem
rem  Antes (una sola vez): en el celular activar Opciones de desarrollador y
rem  Depuracion USB; y tener adb (Android platform-tools) en el PATH, en
rem  %LOCALAPPDATA%\Android\Sdk\platform-tools o en una carpeta platform-tools
rem  junto a este archivo.
rem
rem  Uso: conectar_celular_usb.bat [puerto_PC] [puerto_celular]
rem    puerto_PC      = puerto de Apache en esta PC (por defecto 80, el de Laragon)
rem    puerto_celular = puerto que usara el celular. Android no permite puertos
rem                     menores a 1024, asi que con el 80 se usa el 8080.
rem  Si el celular usa un puerto distinto al de la PC, agregue en .env la linea
rem  ESCANER_PUERTO=8080 para que el QR de la caja salga con ese puerto.
rem ============================================================================
set "PC=%~1"
if "%PC%"=="" set "PC=80"
set "CEL=%~2"
if "%CEL%"=="" (
  if %PC% LSS 1024 (set "CEL=8080") else (set "CEL=%PC%")
)

where adb >nul 2>nul
if not errorlevel 1 goto hayadb
if exist "%~dp0platform-tools\adb.exe" set "PATH=%PATH%;%~dp0platform-tools"
if exist "%LOCALAPPDATA%\Android\Sdk\platform-tools\adb.exe" set "PATH=%PATH%;%LOCALAPPDATA%\Android\Sdk\platform-tools"
where adb >nul 2>nul
if errorlevel 1 goto noadb

:hayadb
adb start-server >nul 2>nul
adb devices | findstr /R "device$" >nul
if errorlevel 1 goto nocel

adb reverse tcp:%CEL% tcp:%PC% >nul
if errorlevel 1 goto fallo

echo.
echo  Listo: el celular ya llega a la caja por USB.
echo  El celular usa localhost:%CEL% y llega al puerto %PC% de esta PC.
echo  Ahora lea el QR de la caja con la camara del celular.
echo  Si desconecta el cable, vuelva a ejecutar este archivo.
echo.
pause
exit /b 0

:noadb
echo.
echo  No se encontro adb. Descargue "SDK Platform-Tools" de Android y descomprima
echo  la carpeta platform-tools junto a este archivo.
echo.
pause
exit /b 1

:nocel
echo.
echo  No hay un celular autorizado. Revise que:
echo   - el cable sea de datos y el celular este conectado
echo   - la Depuracion USB este activada
echo   - haya aceptado en el celular el mensaje "Permitir depuracion USB"
echo.
pause
exit /b 1

:fallo
echo.
echo  No se pudo crear el enlace del puerto %CEL% del celular al %PC% de la PC. Pruebe: adb kill-server  y vuelva a ejecutar.
echo.
pause
exit /b 1
