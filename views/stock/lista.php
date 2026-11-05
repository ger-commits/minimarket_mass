<?php
$titulo = 'Almacén e inventario';
require __DIR__ . '/../partials/cabecera.php';
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<style>
.stock-wrap{max-width:1200px;margin:24px auto;padding:0 18px}.stock-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin:18px 0}.stock-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px}.stock-card h2{font-size:19px;margin:0 0 14px}.stock-card label{display:block;font-weight:600;margin:10px 0}.stock-card input,.stock-card select,.stock-card textarea{display:block;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;margin-top:5px}.stock-card button{padding:10px 15px;background:#166534;color:#fff;border:0;border-radius:7px;cursor:pointer}.stock-table{width:100%;border-collapse:collapse;background:#fff}.stock-table th,.stock-table td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}.stock-table th{background:#f8fafc}.stock-alert{padding:12px;border-radius:8px;margin:14px 0;background:#ecfdf5}.stock-alert.error{background:#fef2f2}.stock-section{margin:24px 0;overflow:auto}.stock-muted{color:#64748b;font-size:13px}@media(max-width:600px){.stock-table{font-size:13px}}
</style>
<div class="stock-wrap">
  <h1>Almacén e inventario</h1>
  <p class="stock-muted">Las operaciones se guardan en la base de datos y pertenecen únicamente a tu tienda.</p>
  <?php if ($aviso): ?><div class="stock-alert <?= ($aviso['tipo'] ?? '') === 'error' ? 'error' : '' ?>"><?= $h($aviso['texto'] ?? '') ?></div><?php endif; ?>

  <div class="stock-grid">
    <section class="stock-card">
      <h2>Registrar ingreso</h2>
      <form method="post" action="index.php?r=stock_accion">
        <input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><input type="hidden" name="accion" value="ingreso_registrar">
        <label>Producto<select name="id_producto" required><option value="">Selecciona</option><?php foreach ($productos as $p): ?><option value="<?= (int)$p['id_producto'] ?>"><?= $h($p['nombre_producto']) ?></option><?php endforeach; ?></select></label>
        <label>Cantidad<input type="number" name="cantidad" min="0.001" step="0.001" required></label>
        <label>Observación<input type="text" name="observacion" maxlength="200" placeholder="Compra, reposición, recepción..."></label>
        <label>Código de lote (opcional)<input type="text" name="codigo_lote" maxlength="20"></label>
        <label>Fecha de vencimiento (si registras lote)<input type="date" name="fecha_vencimiento"></label>
        <button type="submit">Guardar ingreso</button>
      </form>
      <p class="stock-muted">El ingreso actualiza stock y kardex en una sola transacción. Si el producto tiene lote, completa ambos campos.</p>
    </section>

    <section class="stock-card">
      <h2>Registrar merma</h2>
      <form method="post" action="index.php?r=stock_accion">
        <input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><input type="hidden" name="accion" value="merma_registrar">
        <label>Producto<select name="id_producto" required><option value="">Selecciona</option><?php foreach ($stock as $p): ?><option value="<?= (int)$p['id_producto'] ?>"><?= $h($p['nombre_producto']) ?> (stock: <?= $h($p['cantidad_actual']) ?>)</option><?php endforeach; ?></select></label>
        <label>Motivo<select name="motivo" required><option value="VENCIDO">Vencido</option><option value="DAÑADO">Dañado</option><option value="ROBO">Robo</option><option value="OTRO">Otro</option></select></label>
        <label>Cantidad<input type="number" name="cantidad" min="0.001" step="0.001" required></label>
        <label>Observación<input type="text" name="observacion" maxlength="200"></label>
        <button type="submit">Guardar merma</button>
      </form>
      <p class="stock-muted">Se valida el stock disponible y se descuentan primero los lotes que vencen antes.</p>
    </section>
  </div>

  <section class="stock-section"><h2>Stock actual</h2><table class="stock-table"><thead><tr><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Unidad</th><th>Estado</th></tr></thead><tbody>
  <?php foreach ($stock as $s): ?><tr><td><?= $h($s['nombre_producto']) ?></td><td><?= $h($s['cantidad_actual']) ?></td><td><?= $h($s['stock_minimo']) ?></td><td><?= $h($s['abreviatura']) ?></td><td><?= $h($s['situacion']) ?></td></tr><?php endforeach; ?>
  <?php if (!$stock): ?><tr><td colspan="5">No hay productos en el inventario de esta tienda.</td></tr><?php endif; ?>
  </tbody></table></section>

  <section class="stock-section"><h2>Lotes y vencimientos</h2><table class="stock-table"><thead><tr><th>Producto</th><th>Lote</th><th>Vencimiento</th><th>Disponible</th></tr></thead><tbody>
  <?php foreach ($lotes as $l): ?><tr><td><?= $h($l['nombre_producto']) ?></td><td><?= $h($l['codigo_lote']) ?></td><td><?= $h($l['fecha_vencimiento']) ?></td><td><?= $h($l['cantidad_actual']) ?> <?= $h($l['abreviatura']) ?></td></tr><?php endforeach; ?>
  <?php if (!$lotes): ?><tr><td colspan="4">No hay lotes activos registrados.</td></tr><?php endif; ?>
  </tbody></table></section>

  <section class="stock-section"><h2>Historial de mermas</h2><table class="stock-table"><thead><tr><th>Fecha</th><th>Motivo</th><th>Observación</th><th>Empleado</th><th>Productos</th></tr></thead><tbody>
  <?php foreach ($mermas as $m): ?><tr><td><?= $h($m['fecha_hora']) ?></td><td><?= $h($m['motivo']) ?></td><td><?= $h($m['observacion'] ?? '') ?></td><td><?= $h($m['empleado']) ?></td><td><?= (int)$m['productos'] ?></td></tr><?php endforeach; ?>
  <?php if (!$mermas): ?><tr><td colspan="5">No hay mermas registradas.</td></tr><?php endif; ?>
  </tbody></table></section>

  <section class="stock-section"><h2>Traslados de la tienda</h2><p class="stock-muted">Los traslados se confirman automáticamente cuando el administrador los registra: el stock destino ya queda actualizado.</p><table class="stock-table"><thead><tr><th>Fecha</th><th>Origen</th><th>Destino</th><th>Productos</th><th>Registró</th></tr></thead><tbody>
  <?php foreach ($traslados as $t): ?><tr><td><?= $h($t['fecha_hora']) ?></td><td><?= $h($t['origen']) ?></td><td><?= $h($t['destino']) ?></td><td><?= (int)$t['productos'] ?></td><td><?= $h($t['empleado']) ?></td></tr><?php endforeach; ?>
  <?php if (!$traslados): ?><tr><td colspan="5">No hay traslados registrados.</td></tr><?php endif; ?>
  </tbody></table></section>

  <section class="stock-section"><h2>Kardex: últimos movimientos</h2><table class="stock-table"><thead><tr><th>Fecha</th><th>Producto</th><th>Movimiento</th><th>Cantidad</th><th>Nuevo stock</th><th>Empleado</th></tr></thead><tbody>
  <?php foreach ($movimientos as $m): ?><tr><td><?= $h($m['fecha_hora']) ?></td><td><?= $h($m['nombre_producto']) ?></td><td><?= $h($m['nombre_movimiento']) ?></td><td><?= $m['efecto'] === 'S' ? '-' : '+' ?><?= $h($m['cantidad']) ?></td><td><?= $h($m['stock_nuevo']) ?></td><td><?= $h($m['empleado']) ?></td></tr><?php endforeach; ?>
  <?php if (!$movimientos): ?><tr><td colspan="6">No hay movimientos registrados.</td></tr><?php endif; ?>
  </tbody></table></section>
</div>
<?php require __DIR__ . '/../partials/pie.php'; ?>
