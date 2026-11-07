<?php
// V: ingreso del cajero (diseño de caja con bolsa 3D). Estilos y scripts en public/css/login/caja.css y public/js/login/.
// Variables que pone AuthController: $caja (array|null), $error (string|null), $csrf, $dniAnterior
$cajaActiva = $caja !== null && $caja['estado'] === 'ACTIVA';
$numero = $caja !== null ? str_pad((string)(int)$caja['numero_caja'], 2, '0', STR_PAD_LEFT) : '--';
if ($caja === null)  { $estadoTxt = 'Caja sin configurar'; $estadoClase = 'off'; }
elseif ($cajaActiva) { $estadoTxt = 'Caja activa';          $estadoClase = 'open'; }
else                 { $estadoTxt = 'Caja inactiva';        $estadoClase = 'off'; }
$turnoTxt = $caja === null ? '' : ($caja['turno_abierto'] ? 'Turno abierto' : 'Sin turno abierto');
$dniCompleto = strlen($dniAnterior) === 8;      // si volvió con el DNI ya escrito, el cursor va a la contraseña
$apagado = $cajaActiva ? '' : 'disabled';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Ingreso a caja – Minimarket Mass</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@800&display=swap">
<link rel="stylesheet" href="css/login/caja.css">
</head>
<body>
<main class="app">
  <section class="brand" aria-label="Minimarket Mass">
    <div class="logo"><b>mass</b><span>Minimarket</span></div>
    <div class="caja">
      <h1><?= $caja !== null ? 'Bienvenido a la caja Nº ' . htmlspecialchars($numero) : 'Caja sin configurar' ?></h1>
      <p>Ingresa con tu DNI y tu contraseña.</p>
    </div>
    <div class="stage" id="stage" role="img" aria-label="Bolsa de compras Mass en 3D, arrastra para girarla"><div class="halo"></div><div class="ring"></div><div class="ring r2"></div>
      <i class="star" style="left:24%;top:20%"></i><i class="star" style="right:22%;top:14%;animation-delay:-.9s"></i><i class="star" style="left:16%;top:52%;animation-delay:-1.6s"></i><i class="star" style="right:14%;top:58%;animation-delay:-.4s"></i><i class="star" style="left:46%;top:6%;animation-delay:-2s"></i></div>
    <div class="scan" aria-hidden="true">
      <div class="bc"><div class="bars"></div><div class="laser"></div><small>CAJA <?= htmlspecialchars($numero) ?></small></div>
      <div class="pay"><span>Efectivo</span><span>Tarjeta</span><span>Sip</span></div>
    </div>
    <div class="meta">
      <div class="clock"><span class="t" id="tm"></span><span class="d" id="dt"></span></div>
    </div>
  </section>

  <section class="access">
    <form class="panel" id="login" method="post" action="index.php?r=login" aria-label="Ingreso del cajero" <?= $cajaActiva ? '' : 'data-off' ?>>
      <div class="head">
        <h2>Ingresar</h2>
        <span class="state <?= $estadoClase ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg><span><?= $estadoTxt ?></span></span>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      </div>
      <p class="sub">Acceso para cajeros<?= $turnoTxt !== '' ? ' · ' . $turnoTxt : '' ?></p>
      <?php if (!empty($error)): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>

      <div class="fg">
        <div class="lr"><label for="dni">DNI del empleado</label><span class="cnt" id="cd">0/8</span></div>
        <div class="field">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.6 1.8-2.4 3-2.4s2.4.8 3 2.4"/><path d="M15 10h3M15 14h3"/></svg>
          <input id="dni" name="dni" type="text" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" placeholder="8 dígitos" autocomplete="username" required value="<?= htmlspecialchars($dniAnterior) ?>" <?= $apagado ?> <?= $dniCompleto ? '' : 'autofocus' ?>><i class="pg" id="pgd"></i>
        </div>
      </div>

      <div class="fg">
        <div class="lr"><label for="contrasena">Contraseña</label></div>
        <div class="field">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
          <input id="contrasena" name="contrasena" type="password" placeholder="Tu contraseña" autocomplete="current-password" required <?= $apagado ?> <?= $dniCompleto ? 'autofocus' : '' ?>>
          <button type="button" class="show" id="eye" aria-pressed="false" <?= $apagado ?>>Mostrar</button>
        </div>
      </div>

      <div class="pad" id="pad" aria-label="Teclado numérico para el DNI">
        <button type="button" data-k="1">1</button><button type="button" data-k="2">2</button><button type="button" data-k="3">3</button>
        <button type="button" data-k="4">4</button><button type="button" data-k="5">5</button><button type="button" data-k="6">6</button>
        <button type="button" data-k="7">7</button><button type="button" data-k="8">8</button><button type="button" data-k="9">9</button>
        <button type="button" class="fn" data-k="clear">Borrar todo</button><button type="button" data-k="0">0</button>
        <button type="button" class="fn" data-k="back" aria-label="Borrar último dígito"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 5H9l-6 7 6 7h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1z"/><path d="M17 9l-4 6M13 9l4 6"/></svg></button>
      </div>

      <button type="submit" class="go" id="go" disabled><span class="sp" aria-hidden="true"></span><span class="tx">Ingresar</span></button>
      <p class="help">¿Olvidaste tu contraseña? Consulta con el administrador.</p>
    </form>
  </section>
</main>

<script src="js/login/caja/login-teclado.js"></script>
<!-- Three.js desde CDN; para no depender de internet, guárdalo en public/js/vendor/three.min.js (r128) y cambia esta ruta -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="js/login/compartido/bolsa3d.js"></script>
<script src="js/login/caja/login-efectos.js"></script>
</body>
</html>
