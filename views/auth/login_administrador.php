<?php // V: ingreso del administrador. Variables que pone AuthController: $csrf, $error, $dniAnterior ?>
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
    <div class="bars"></div>
    <div class="dots"></div>
  </div>

  <header class="top rise">
    <div class="logo"><b>mass</b><span>Minimarket</span></div>
    <div class="when"><b id="tm">10:42 a. m.</b><span id="dt">Lunes 01/11/2026</span></div>
  </header>


  <main class="center">
    <form class="panel rise" style="--d:.2" id="login" method="post" action="index.php?r=administrador" aria-label="Inicio de sesión del administrador">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <span class="badge"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg>Acceso restringido</span>
      <h1>Panel de administración</h1>
      <p class="sub">Inicia sesión con tu DNI y tu contraseña de administrador.</p>
      <?php if (!empty($error)): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>

      <div class="fg">
        <label for="dni">DNI</label>
        <div class="field">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>
          <input id="dni" name="dni" type="text" inputmode="numeric" maxlength="8" placeholder="8 dígitos" autocomplete="username" required value="<?= htmlspecialchars($dniAnterior) ?>">
        </div>
      </div>

      <div class="fg">
        <label for="pwd">Contraseña</label>
        <div class="field">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
          <input id="pwd" name="contrasena" type="password" placeholder="Tu contraseña" autocomplete="current-password" required>
          <button type="button" class="show" id="eye" aria-pressed="false">Mostrar</button>
        </div>
      </div>

<div class="row">
        <label class="chk"><input type="checkbox" id="rec">Mantener sesión</label>
        <a href="#">¿Olvidó su contraseña?</a>
      </div>

      <button type="submit" class="go" id="go" disabled><span class="sp" aria-hidden="true"></span><span class="tx">Ingresar al panel</span></button>
      <p class="help">Solo personal autorizado. Tus accesos quedan registrados.</p>
    </form>
  </main>

  <footer class="foot">Supermercados Mass · Grupo Intercorp</footer>
</div>




<script src="js/login/administrador/efectos.js" defer></script>
<script>
(function(){
  var dni=document.getElementById('dni'),p=document.getElementById('pwd'),go=document.getElementById('go'),eye=document.getElementById('eye');
  function check(){go.disabled=!(dni.value.length===8&&p.value.length>0);}
  dni.addEventListener('input',function(){dni.value=dni.value.replace(/\D/g,'').slice(0,8);check();});
  p.addEventListener('input',check);
  eye.addEventListener('click',function(){var s=p.type==='password';p.type=s?'text':'password';eye.textContent=s?'Ocultar':'Mostrar';eye.setAttribute('aria-pressed',s);});
  check();
  var D=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
  function p2(n){return n<10?'0'+n:n;}
  function tick(){var d=new Date(),h=d.getHours(),ap=h>=12?'p. m.':'a. m.';h=h%12||12;
    document.getElementById('tm').textContent=h+':'+p2(d.getMinutes())+' '+ap;
    document.getElementById('dt').textContent=D[d.getDay()]+' '+p2(d.getDate())+'/'+p2(d.getMonth()+1)+'/'+d.getFullYear();}
  tick();setInterval(tick,15000);
})();
</script>
</body>
</html>