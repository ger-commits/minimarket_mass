<?php $titulo = 'Catálogo de productos'; require __DIR__ . '/../partials/cabecera.php'; ?>
<!-- V: tabla HTML del catálogo -->
<h1>Catálogo de productos</h1>
<table>
  <tr><th>Producto</th><th>Categoría</th><th>Marca</th><th>Unidad</th><th>Precio (S/)</th></tr>
  <?php foreach ($productos as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['nombre_producto']) ?></td>
    <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
    <td><?= htmlspecialchars($p['nombre_marca']) ?></td>
    <td><?= htmlspecialchars($p['abreviatura']) ?></td>
    <td><?= number_format((float)$p['precio_venta'], 2) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../partials/pie.php'; ?>
