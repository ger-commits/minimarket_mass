@echo off
chcp 65001 >nul
setlocal
rem ============================================================================
rem  Escaner del ALMACEN sin cable: crea una direccion https publica y temporal
rem  hacia esta PC (Cloudflare Tunnel, gratis, sin cuenta).
rem
rem  Antes (una sola vez):  winget install Cloudflare.cloudflared
rem  Uso:  iniciar_tunel_almacen.bat [puerto]     (por defecto 8000)
rem
rem  1) En otra ventana, inicia el sistema:  php -S localhost:8000 -t public
rem  2) Ejecuta este archivo y copia la direccion  https://xxxx.trycloudflare.com
rem  3) En Almacenamiento > Conectar celular, pegala en el formulario y escanea el QR.
rem  Por esta direccion SOLO se aceptan los codigos del escaner del almacen.
rem  Deja esta ventana abierta mientras el celular escanea. La direccion cambia cada vez.
rem ============================================================================
set "PUERTO=%~1"
if "%PUERTO%"=="" set "PUERTO=8000"
where cloudflared >nul 2>nul
if errorlevel 1 (
  echo No se encontro cloudflared. Instalalo con:  winget install Cloudflare.cloudflared
  echo y vuelve a abrir esta ventana.
  pause
  exit /b 1
)
echo Creando la direccion publica hacia http://localhost:%PUERTO% ...
echo Busca abajo la linea con https://....trycloudflare.com
echo.
cloudflared tunnel --url http://localhost:%PUERTO%
