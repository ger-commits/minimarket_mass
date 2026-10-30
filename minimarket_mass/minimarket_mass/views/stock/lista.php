<!-- V: stock de la tienda con aviso de reposición -->
<h1>Stock de la tienda</h1>
<table border="1" cellpadding="4">
  <tr><th>Producto</th><th>Stock actual</th><th>Stock mínimo</th><th>Situación</th></tr>
  <?php foreach ($stock as $s): ?>
  <tr>
    <td><?= htmlspecialchars($s['nombre_producto']) ?></td>
    <td><?= $s['cantidad_actual'] ?></td>
    <td><?= $s['stock_minimo'] ?></td>
    <td><?= $s['situacion'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
