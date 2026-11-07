# Escáner del almacén por celular, sin cable

Copia estos archivos encima de los de tu proyecto (mismas rutas):

- `public/index.php` — por una dirección pública solo se acepta `escaner_enviar`
- `controllers/EscanerController.php` — lee `ALMACEN_ESCANER_URL_BASE`
- `public/js/almacenamiento.js` — QR con la dirección https y formulario para pegarla
- `public/escaner.html` — pantalla del celular (aviso de http y señal cada 20 s)
- `.env.example` — variables nuevas (no pisa tu `.env`)
- `iniciar_tunel_almacen.bat` — abre el túnel https con Cloudflare

## Uso (cada vez)
1. Inicia el sistema desde la raíz del proyecto: `php -S localhost:8000 -t public` (siempre con `-t public`, así `.env` y `database/` no quedan expuestos).
2. En otra ventana: `iniciar_tunel_almacen.bat` (primera vez: `winget install Cloudflare.cloudflared`). Copia la dirección `https://xxxx.trycloudflare.com`.
3. Entra como almacenero en `http://localhost:8000/index.php?r=almacenero` → **Conectar celular** → pega esa dirección → **Usar**.
4. Escanea el QR con la cámara del celular (Wi-Fi o datos móviles). Escanea productos.

## Por qué no se cruza con el escáner del cajero
- Tablas distintas: almacén `escaner_vinculo` / `escaner_codigo`; caja `escaner_caja_vinculo` / `escaner_caja_codigo`. Un token de una no sirve en la otra.
- Rutas distintas: `escaner_*` (almacén) y `caja_escaner*` (caja).
- Variables distintas: `ALMACEN_ESCANER_URL_BASE` (almacén) y `ESCANER_*` (caja).
- Por la dirección del túnel solo se acepta `escaner_enviar`; el login, la caja y el escáner de caja siguen solo en la PC o la red local.

Se necesitan las tablas del escáner del almacén (`escaner_vinculo`, `escaner_codigo`) ya creadas.
