<?php
// V: pantalla de selección de perfil. Cada perfil tiene su propio login.
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mass · Ingreso al sistema</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #15140f; font-family: system-ui, "Segoe UI", sans-serif; color: #222; padding: 24px; }
  .wrap { width: 100%; max-width: 880px; text-align: center; }
  .marca { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 16px; background: #ffd21f; display: grid; place-items: center; font: 800 30px system-ui; color: #111; }
  h1 { color: #fff; margin: 0 0 6px; font-size: 26px; }
  .sub { color: #bdbab0; margin: 0 0 32px; }
  .perfiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; }
  .perfil { display: block; background: #fff; border-radius: 16px; padding: 28px 22px; text-decoration: none; color: #222; transition: transform .15s, box-shadow .15s; box-shadow: 0 6px 18px rgba(0,0,0,.25); }
  .perfil:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(0,0,0,.35); }
  .perfil .ico { font-size: 34px; margin-bottom: 10px; }
  .perfil b { display: block; font-size: 18px; margin-bottom: 6px; }
  .perfil small { color: #777; line-height: 1.4; }
  .pie { margin-top: 28px; color: #888; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="marca">M</div>
  <h1>Mass · Ingreso al sistema</h1>
  <p class="sub">Elige tu perfil para iniciar sesión</p>

  <div class="perfiles">
    <a class="perfil" href="index.php?r=login">
      <div class="ico">🧾</div>
      <b>Cajero</b>
      <small>Ventas y cobros en la caja de tu terminal.</small>
    </a>
    <a class="perfil" href="index.php?r=almacenero">
      <div class="ico">📦</div>
      <b>Almacenero</b>
      <small>Stock, ingresos, mermas y lotes de tu tienda.</small>
    </a>
    <a class="perfil" href="index.php?r=administrador">
      <div class="ico">📊</div>
      <b>Administrador</b>
      <small>Panel de tu tienda: ventas, personal, traslados y reportes.</small>
    </a>
  </div>

  <p class="pie">Cada perfil entra con su propio DNI y contraseña.</p>
</div>
</body>
</html>