<?php
// V: pantalla del celular que escanea para la caja. Variables de EscanerController::movil(): $valido, $idCaja, $token
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="referrer" content="no-referrer">
<meta name="robots" content="noindex">
<title>MASS · Escáner</title>
<link rel="stylesheet" href="css/escaner/movil.css">
</head>
<body>
<header><b>mass</b><span>Escáner de caja</span><i id="con" class="con" title="Conexión con la caja"></i></header>
<?php if (!$valido): ?>
<main><div class="aviso err">Este enlace no es válido o ya venció.<br>En la caja pulse <b>Escanear con celular</b> y lea el QR nuevo.</div></main>
<?php else: ?>
<main id="cfg" data-c="<?= (int)$idCaja ?>" data-t="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
  <div class="visor" id="visor">
    <video id="cam" playsinline muted></video>
    <div class="mira"></div>
    <button class="luz" id="luz" hidden type="button">Linterna</button>
  </div>
  <button class="ini" id="ini" type="button">Iniciar cámara</button>
  <div class="est" id="est">Pulse «Iniciar cámara» para escanear.</div>
  <form class="man" id="man" autocomplete="off">
    <input id="cod" inputmode="numeric" placeholder="O digite el código de barras" maxlength="40">
    <button type="submit">Enviar</button>
  </form>
  <ul class="lista" id="lista"></ul>
</main>
<script src="js/escaner/movil.js"></script>
<?php endif; ?>
</body>
</html>
