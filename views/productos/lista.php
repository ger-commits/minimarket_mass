<!-- V: tabla HTML del catálogo -->
<h1>Catálogo de productos</h1>
<table border="1" cellpadding="4">
  <tr><th>Producto</th><th>Categoría</th><th>Marca</th><th>Unidad</th><th>Precio (S/)</th></tr>
  <?php foreach ($productos as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['nombre_producto']) ?></td>
    <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
    <td><?= htmlspecialchars($p['nombre_marca']) ?></td>
    <td><?= htmlspecialchars($p['abreviatura']) ?></td>
    <td><?= number_format($p['precio_venta'], 2) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
