<?php
// V: cabecera común de las pantallas internas (usa $empleado y $titulo)
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo) ?> · Minimarket Mass</title>
  <link rel="stylesheet" href="css/mass.css">
</head>
<body>
<header class="barra">
  <strong>Mass</strong>
  <nav>
    <a href="index.php?r=productos">Productos</a>
    <a href="index.php?r=stock">Stock</a>
  </nav>
  <form method="post" action="index.php?r=logout">
    <span><?= htmlspecialchars($empleado->nombreCompleto()) ?> · <?= htmlspecialchars($empleado->rol()) ?></span>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Sesion::token()) ?>">
    <button>Salir</button>
  </form>
</header>
<main class="contenido">
