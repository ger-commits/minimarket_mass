<!-- V: boleta o factura (cabecera + detalle + total) -->
<?php if (!$lineas): ?>
  <p>No existe un comprobante con esa serie y correlativo.</p>
<?php else: $c = $lineas[0]; ?>
  <h1><?= htmlspecialchars($c['tipo_comprobante']) ?> <?= htmlspecialchars($c['comprobante']) ?></h1>
  <p><?= htmlspecialchars($c['tienda']) ?> · Caja <?= $c['caja'] ?> · Cajero: <?= htmlspecialchars($c['cajero']) ?><br>
     Fecha: <?= $c['fecha_hora'] ?> · Cliente: <?= htmlspecialchars($c['cliente']) ?> · Estado: <?= $c['estado'] ?></p>
  <table border="1" cellpadding="4">
    <tr><th>Ítem</th><th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Importe</th></tr>
    <?php foreach ($lineas as $l): ?>
    <tr>
      <td><?= $l['item'] ?></td><td><?= htmlspecialchars($l['producto']) ?></td>
      <td><?= $l['cantidad'] . ' ' . $l['unidad'] ?></td>
      <td><?= number_format($l['precio_unitario'], 2) ?></td><td><?= number_format($l['importe'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><th colspan="4" align="right">TOTAL S/</th><th><?= number_format($c['total'], 2) ?></th></tr>
  </table>
<?php endif; ?>
