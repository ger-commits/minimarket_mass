<?php
// V: página de error genérica (no muestra detalles técnicos). Variables: $codigo, $mensaje
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error <?= (int)$codigo ?> · Minimarket Mass</title>
  <link rel="stylesheet" href="css/mass.css">
</head>
<body class="login">
  <div class="tarjeta">
    <p class="marca"><span>MINIMARKET</span> <strong>Mass</strong></p>
    <h1>Error <?= (int)$codigo ?></h1>
    <p><?= htmlspecialchars($mensaje) ?></p>
    <p><a href="index.php?r=login">Volver al inicio</a></p>
  </div>
</body>
</html>
