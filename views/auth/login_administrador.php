<?php
// V: login del administrador (diseño de login_administrador.html). Recibe $csrf, $dniAnterior y $error desde AdminController.
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Panel de administración – Minimarket Mass</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">


<link rel="stylesheet" href="css/login/administrador.css">
</head>
<body>
<div class="stage">
  <div class="blob b1" aria-hidden="true"></div><div class="blob b2" aria-hidden="true"></div><div class="blob b3" aria-hidden="true"></div><div class="mesh" aria-hidden="true"></div>
  <div class="bgs" aria-hidden="true">
    <div class="spot"></div>
    <svg class="bg-line" viewBox="0 0 1200 400" preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id="bl1" x1="0" x2="1"><stop offset="0" stop-color="#FF4D6D"/><stop offset="1" stop-color="#F5B800"/></linearGradient>
        <linearGradient id="bl2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#E4002B" stop-opacity=".30"/><stop offset="1" stop-color="#E4002B" stop-opacity="0"/></linearGradient>
      </defs>
      <path class="gl" d="M0 100H1200M0 200H1200M0 300H1200"/>
      <path class="ar" d="M0 300 C100 280 160 230 260 250 S420 330 520 260 S700 110 800 170 S1000 80 1200 60 V400 H0Z"/>
      <path class="ln2" pathLength="1" d="M0 345 C120 325 220 300 320 312 S520 250 640 272 S900 205 1200 192"/>
      <path class="ln" pathLength="1" d="M0 300 C100 280 160 230 260 250 S420 330 520 260 S700 110 800 170 S1000 80 1200 60"/>
      <circle class="trav" r="6" opacity="0"><animateMotion dur="7s" begin="3s" repeatCount="indefinite" path="M0 300 C100 280 160 230 260 250 S420 330 520 260 S700 110 800 170 S1000 80 1200 60"/><animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.06;.94;1" dur="7s" begin="3s" repeatCount="indefinite"/></circle>
    </svg>
    <svg class="donut" viewBox="0 0 160 160">
      <circle class="tr" cx="80" cy="80" r="60" pathLength="100"/>
      <circle class="s1" cx="80" cy="80" r="60" pathLength="100"/>
      <circle class="s2" cx="80" cy="80" r="60" pathLength="100"/>
      <circle class="s3" cx="80" cy="80" r="60" pathLength="100"/>
    </svg>
    <span class="bg-tag"><b>▲ 18%</b> vs. semana anterior</span>
    <div class="bars"></div>
    <div class="dots"></div>
  </div>

  <header class="top rise">
    <div class="logo"><b>mass</b><span>Minimarket</span></div>
    <div class="when"><b id="tm">10:42 a. m.</b><span id="dt">Lunes 01/11/2026</span></div>
  </header>

  <aside class="fc f1 rise" style="--d:.5" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l2-5h14l2 5"/><path d="M4 10v10h16V10"/></svg><small>Sucursal 1</small><h3>Ventas de hoy</h3><strong>S/ <span data-n="4210">0</span></strong><span class="st"><i></i>En línea</span></aside>
  <aside class="fc f2 rise" style="--d:.65" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l2-5h14l2 5"/><path d="M4 10v10h16V10"/></svg><small>Sucursal 2</small><h3>Ventas de hoy</h3><strong>S/ <span data-n="5330">0</span></strong><span class="st"><i></i>En línea</span></aside>
  <aside class="fc f3 rise" style="--d:.8" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l2-5h14l2 5"/><path d="M4 10v10h16V10"/></svg><small>Sucursal 3</small><h3>Ventas de hoy</h3><strong>S/ <span data-n="2940">0</span></strong><span class="st"><i></i>En línea</span></aside>
  <aside class="fc f4 rise" style="--d:.95" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg><small>Inventario</small><h3>Productos con stock bajo</h3><strong><span data-n="14">0</span></strong><span class="st" style="color:#FFE08A"><i style="background:var(--gold)"></i>Por revisar</span></aside>

  <main class="center">
    <form class="panel rise" style="--d:.2" id="login" method="post" action="index.php?r=admin_login" aria-label="Inicio de sesión del administrador">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <span class="badge"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg>Acceso restringido</span>
      <h1>Panel de administración</h1>
      <p class="sub">Inicia sesión con tu DNI y tu contraseña de administrador.</p>
      <?php if (!empty($error)): ?><p class="help" style="color:#b91c1c;font-weight:700"><?= htmlspecialchars($error) ?></p><?php endif; ?>

      <div class="fg">
        <label for="usr">DNI</label>
        <div class="field">
          <input id="usr" name="dni" type="text" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" placeholder="8 dígitos" value="<?= htmlspecialchars($dniAnterior) ?>" autocomplete="username" required>
        </div>
      </div>

      <div class="fg">
        <label for="pwd">Contraseña</label>
        <div class="field">
          <input id="pwd" name="contrasena" type="password" placeholder="Tu contraseña" autocomplete="current-password" required>
          <button type="button" class="show" id="eye" aria-pressed="false">Mostrar</button>
        </div>
      </div>

      <button type="submit" class="go" id="go" disabled><span class="sp" aria-hidden="true"></span><span class="tx">Ingresar al panel</span></button>
      <p class="help">Solo personal autorizado. Tus accesos quedan registrados.</p>
    </form>
  </main>

  <footer class="foot">Supermercados Mass · Grupo Intercorp</footer>
</div>




<script src="js/login/administrador/login.js" defer></script>
<script src="js/login/administrador/efectos.js" defer></script>
</body>
</html>
