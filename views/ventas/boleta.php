<?php $titulo = 'Comprobante'; require __DIR__ . '/../partials/cabecera.php'; ?>
<!-- V: boleta o factura (cabecera + detalle + total) -->
<?php if (!$lineas): ?>
  <p>No existe un comprobante con esa serie y correlativo en esta tienda.</p>
<?php else: $c = $lineas[0]; ?>
  <h1><?= htmlspecialchars($c['tipo_comprobante']) ?> <?= htmlspecialchars($c['comprobante']) ?></h1>
  <p><?= htmlspecialchars($c['tienda']) ?> · Caja <?= htmlspecialchars((string)$c['caja']) ?> · Cajero: <?= htmlspecialchars($c['cajero']) ?><br>
     Fecha: <?= htmlspecialchars((string)$c['fecha_hora']) ?> · Cliente: <?= htmlspecialchars($c['cliente']) ?> · Estado: <?= htmlspecialchars($c['estado']) ?></p>
  <table>
    <tr><th>Ítem</th><th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Importe</th></tr>
    <?php foreach ($lineas as $l): ?>
    <tr>
      <td><?= htmlspecialchars((string)$l['item']) ?></td><td><?= htmlspecialchars($l['producto']) ?></td>
      <td><?= htmlspecialchars($l['cantidad'] . ' ' . $l['unidad']) ?></td>
      <td><?= number_format((float)$l['precio_unitario'], 2) ?></td><td><?= number_format((float)$l['importe'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><th colspan="4" align="right">TOTAL S/</th><th><?= number_format((float)$c['total'], 2) ?></th></tr>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../partials/pie.php'; ?>
