<?php $titulo = 'Stock de la tienda'; require __DIR__ . '/../partials/cabecera.php'; ?>
<!-- V: stock de la tienda con aviso de reposición -->
<h1>Stock de la tienda</h1>
<table>
  <tr><th>Producto</th><th>Stock actual</th><th>Stock mínimo</th><th>Situación</th></tr>
  <?php foreach ($stock as $s): ?>
  <tr>
    <td><?= htmlspecialchars($s['nombre_producto']) ?></td>
    <td><?= htmlspecialchars((string)$s['cantidad_actual']) ?></td>
    <td><?= htmlspecialchars((string)$s['stock_minimo']) ?></td>
    <td><?= htmlspecialchars($s['situacion']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../partials/pie.php'; ?>
