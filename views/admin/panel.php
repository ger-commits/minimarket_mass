<?php
// V: panel del administrador con datos reales de su tienda.
// Variables desde AdminController::panel(): $empleado, $tiendaNombre, $hoy, $stockBajo, $ventas,
// $inventario, $personal, $pendientes, $turnos, $aviso, $titulo
if (!function_exists('h')) {
    function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
function cant($x): string { return rtrim(rtrim(number_format((float)$x, 3, '.', ''), '0'), '.'); }

$csrf        = Sesion::token();
$nombre      = $empleado->nombres . ' ' . $empleado->apellidos;
$iniciales   = strtoupper(mb_substr($empleado->nombres, 0, 1) . mb_substr($empleado->apellidos, 0, 1));
$fechaHoy    = date('d/m/Y');
$stockLista  = array_values(array_filter($inventario, fn($f) => (float)$f['cantidad_actual'] <= (float)$f['stock_minimo']));
$pendientesN = count($pendientes);
$productosN  = count($productosPendientes ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($titulo) ?> · Mass</title>
<link rel="stylesheet" href="css/panel-administrador.css">
<style>
  .aviso-ok,.aviso-error{padding:12px 15px;border-radius:10px;margin:0 0 16px;font-weight:600}
  .aviso-ok{background:#e6f5ed;color:#16865b}
  .aviso-error{background:#fde9e7;color:#c33d35}
  .acciones{display:grid;gap:7px;min-width:240px}
  .acciones form{display:flex;gap:6px;margin:0}
  .acciones input,.form-turno input,.form-turno select{border:1px solid #deded8;border-radius:8px;padding:9px 10px;font:inherit;background:#fff;min-width:0}
  .acciones input[name=motivo]{flex:1}
  .form-turno{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;align-items:end}
  .form-turno label{display:grid;gap:6px;font-size:11px;font-weight:700;color:#666}
  .btn.danger{color:#c33d35;border-color:#f3c9c5}
  .btn.sm{padding:7px 11px;font-size:12px}
  .placeholder{background:#fff;border:1px solid #ecece7;border-radius:13px;padding:40px;text-align:center;color:#888}
  .placeholder strong{display:block;color:#222;font:800 18px Manrope;margin-bottom:8px}
  .vacio{color:#888;padding:18px 0;margin:0}
  .section .page-head .btn{align-self:flex-start}
</style>
</head>
<body>
<div class="app">

<aside class="sidebar" id="sidebar">
  <div class="brand"><div class="brand-mark">M</div><div><strong>MASS</strong><small>ADMINISTRACIÓN</small></div></div>
  <div class="store-label">Panel principal</div>
  <nav class="nav" id="nav">
    <button class="active" data-page="inicio"><span class="ico">▦</span>Resumen general</button>
    <button data-page="ventas"><span class="ico">▤</span>Ventas</button>
    <button data-page="inventario"><span class="ico">▣</span>Inventario <span class="count"><?= (int)count($stockLista) ?></span></button>
    <button data-page="empleados"><span class="ico">♙</span>Personal y turnos</button>
    <button data-page="solicitudes"><span class="ico">◷</span>Anulaciones <span class="count"><?= (int)$pendientesN ?></span></button>
    <button data-page="productos"><span class="ico">▧</span>Productos</button>
    <button data-page="productosnuevos"><span class="ico">✚</span>Productos nuevos <span class="count"><?= (int)$productosN ?></span></button>
    <button data-page="traslados"><span class="ico">⇄</span>Traslados</button>
    <button data-page="mermas"><span class="ico">⌁</span>Mermas</button>
    <button data-page="reportes"><span class="ico">▥</span>Reportes</button>
  </nav>
  <div class="sidebar-bottom"><div class="user-card"><div class="avatar"><?= h($iniciales) ?></div><div><b><?= h($nombre) ?></b><small><?= h($tiendaNombre) ?></small></div></div><form method="post" action="index.php?r=admin_salir" style="margin-top:12px"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button class="btn" style="width:100%">Cerrar sesión</button></form></div>
</aside>

<main class="main">
<header class="topbar">
  <div style="display:flex;align-items:center;gap:12px"><button class="icon-btn mobile-menu" id="menu" aria-label="Menú">☰</button><div class="crumb">Mass / <b id="crumb">Resumen general</b></div></div>
  <div class="top-actions"><div class="avatar" style="background:#222;color:#ffd21f"><?= h($iniciales) ?></div></div>
</header>

<?php if ($aviso): ?>
  <p class="<?= $aviso['tipo'] === 'error' ? 'aviso-error' : 'aviso-ok' ?>" role="status" style="margin-top:20px"><?= h($aviso['texto']) ?></p>
<?php endif; ?>

<!-- ============ RESUMEN ============ -->
<section class="section active" id="inicio">
  <div class="page-head"><div><div class="eyebrow">Panel de control · <?= h($tiendaNombre) ?></div><h1>Resumen general</h1><p>Movimiento de hoy (<?= h($fechaHoy) ?>) de tu tienda.</p></div></div>

  <div class="welcome-banner">
    <div class="welcome-copy">
      <span class="welcome-tag"><i></i> CENTRO DE OPERACIONES MASS</span>
      <h2>¡Hola, <?= h($empleado->nombres) ?>! <span>👋</span></h2>
      <p>Lo que pasa hoy en <?= h($tiendaNombre) ?>, en un solo lugar.</p>
      <div class="welcome-meta"><span>◷ <?= h($fechaHoy) ?></span><span>•</span><span><?= (int)$pendientesN ?> anulaciones por revisar</span></div>
    </div>
    <div class="welcome-art" aria-hidden="true">
      <div class="art-circle circle-one"></div><div class="art-circle circle-two"></div>
      <div class="art-bag">M<span>ASS</span></div><div class="art-spark spark-one">✦</div><div class="art-spark spark-two">✳</div>
    </div>
  </div>

  <div class="quick-strip">
    <button data-goto="ventas"><span class="quick-ico">▤</span><span><b>Ver ventas</b><small>Comprobantes recientes</small></span><em>↗</em></button>
    <button data-goto="inventario"><span class="quick-ico">▣</span><span><b>Revisar stock</b><small>Existencias de la tienda</small></span><em>↗</em></button>
    <button data-goto="empleados"><span class="quick-ico">♙</span><span><b>Personal</b><small>Turnos y programación</small></span><em>↗</em></button>
    <button data-goto="solicitudes"><span class="quick-ico">◷</span><span><b>Anulaciones</b><small><?= (int)$pendientesN ?> pendientes</small></span><em>↗</em></button>
  </div>

  <div class="stats">
    <article class="stat"><div class="stat-top">Ventas del día <span class="stat-icon">◉</span></div><div class="stat-value">S/ <?= number_format((float)$hoy['total'], 2) ?></div><div class="stat-foot">Comprobantes emitidos hoy</div></article>
    <article class="stat"><div class="stat-top">Transacciones <span class="stat-icon">▤</span></div><div class="stat-value"><?= (int)$hoy['n'] ?></div><div class="stat-foot">Ventas de hoy</div></article>
    <article class="stat"><div class="stat-top">Turnos de hoy <span class="stat-icon">♙</span></div><div class="stat-value"><?= count($turnos) ?></div><div class="stat-foot">Personal programado</div></article>
    <article class="stat"><div class="stat-top">Alertas de stock <span class="stat-icon">⚠</span></div><div class="stat-value"><?= (int)count($stockLista) ?></div><div class="stat-foot"><span class="down">Requieren atención</span> en tu tienda</div></article>
  </div>

  <div class="lower">
    <article class="panel">
      <div class="panel-head"><div><h3>Productos con stock bajo</h3><div class="sub">Stock igual o menor al mínimo</div></div><button class="btn sm" data-goto="inventario">Ver inventario →</button></div>
      <?php if (!$stockLista): ?>
        <p class="vacio">No hay productos con stock bajo.</p>
      <?php else: ?>
      <div class="table-wrap"><table><thead><tr><th>Producto</th><th>Stock actual</th><th>Mínimo</th><th>Estado</th></tr></thead><tbody>
        <?php foreach (array_slice($stockLista, 0, 6) as $f): ?>
        <tr>
          <td><span class="product"><i class="prod-icon">▣</i><?= h($f['nombre_producto']) ?></span></td>
          <td><?= h(cant($f['cantidad_actual'])) ?></td>
          <td><?= h(cant($f['stock_minimo'])) ?></td>
          <td><?= (float)$f['cantidad_actual'] <= 0 ? '<span class="pill bad">Agotado</span>' : '<span class="pill warn">Bajo</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <?php endif; ?>
    </article>

    <article class="panel">
      <div class="panel-head"><div><h3>Anulaciones pendientes</h3><div class="sub">Requieren tu decisión</div></div><span class="pill <?= $pendientesN ? 'warn' : 'ok' ?>"><?= (int)$pendientesN ?> pendientes</span></div>
      <?php if (!$pendientes): ?>
        <p class="vacio">No hay solicitudes pendientes en tu tienda.</p>
      <?php else: ?>
      <div class="alert-list">
        <?php foreach (array_slice($pendientes, 0, 3) as $s): ?>
        <div class="alert"><div class="alert-ico">↶</div><div><b>Anulación de <?= h($s['serie'] . '-' . str_pad((string)$s['numero_correlativo'], 8, '0', STR_PAD_LEFT)) ?></b><small>S/ <?= number_format((float)$s['total'], 2) ?> · <?= h($s['solicitante']) ?></small><br><a href="#" data-goto="solicitudes">Revisar solicitud →</a></div></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </article>
  </div>

  <div class="lower">
    <article class="panel">
      <div class="panel-head"><div><h3>Últimas ventas</h3><div class="sub">Comprobantes de tu tienda</div></div><button class="btn sm" data-goto="ventas">Ver todas →</button></div>
      <?php if (!$ventas): ?>
        <p class="vacio">Todavía no hay ventas registradas en esta tienda.</p>
      <?php else: ?>
      <div class="table-wrap"><table><thead><tr><th>Comprobante</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead><tbody>
        <?php foreach (array_slice($ventas, 0, 5) as $v): ?>
        <tr>
          <td><?= h($v['serie'] . '-' . str_pad((string)$v['numero_correlativo'], 8, '0', STR_PAD_LEFT)) ?></td>
          <td><?= h($v['fecha_hora']) ?></td>
          <td>S/ <?= number_format((float)$v['total'], 2) ?></td>
          <td><span class="pill <?= $v['estado'] === 'EMITIDA' ? 'ok' : 'warn' ?>"><?= h(ucfirst(strtolower($v['estado']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <?php endif; ?>
    </article>

    <article class="panel">
      <div class="panel-head"><div><h3>Turnos de hoy</h3><div class="sub">Quién trabaja hoy</div></div><button class="btn sm" data-goto="empleados">Programar →</button></div>
      <?php if (!$turnos): ?>
        <p class="vacio">No hay turnos programados para hoy.</p>
      <?php else: ?>
      <div class="table-wrap"><table><thead><tr><th>Empleado</th><th>Entrada</th><th>Salida</th></tr></thead><tbody>
        <?php foreach ($turnos as $t): ?>
        <tr><td><?= h($t['empleado']) ?><br><small style="color:#999"><?= h($t['nombre_rol']) ?></small></td><td><?= h(substr($t['hora_inicio'], 0, 5)) ?></td><td><?= h(substr($t['hora_fin'], 0, 5)) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <?php endif; ?>
    </article>
  </div>
</section>

<!-- ============ VENTAS ============ -->
<section class="section" id="ventas">
  <div class="page-head"><div><div class="eyebrow">Operación comercial</div><h1>Ventas</h1><p>Últimos comprobantes registrados en <?= h($tiendaNombre) ?>.</p></div></div>
  <div class="panel">
    <div class="panel-head"><div><h3>Últimas ventas</h3><div class="sub">Hasta 15 comprobantes más recientes</div></div></div>
    <?php if (!$ventas): ?>
      <p class="vacio">Todavía no hay ventas registradas en esta tienda.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Comprobante</th><th>Fecha y hora</th><th>Total</th><th>Estado</th></tr></thead><tbody>
      <?php foreach ($ventas as $v): ?>
      <tr>
        <td><?= h($v['serie'] . '-' . str_pad((string)$v['numero_correlativo'], 8, '0', STR_PAD_LEFT)) ?></td>
        <td><?= h($v['fecha_hora']) ?></td>
        <td>S/ <?= number_format((float)$v['total'], 2) ?></td>
        <td><span class="pill <?= $v['estado'] === 'EMITIDA' ? 'ok' : 'warn' ?>"><?= h(ucfirst(strtolower($v['estado']))) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ INVENTARIO ============ -->
<section class="section" id="inventario">
  <div class="page-head"><div><div class="eyebrow">Control de existencias</div><h1>Inventario</h1><p>Stock actual de <?= h($tiendaNombre) ?>.</p></div></div>
  <div class="panel">
    <div class="panel-head"><div><h3>Existencias por producto</h3><div class="sub">Los productos con stock bajo aparecen primero</div></div></div>
    <?php if (!$inventario): ?>
      <p class="vacio">Esta tienda todavía no tiene stock registrado.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Producto</th><th>Stock actual</th><th>Stock mínimo</th><th>Estado</th></tr></thead><tbody>
      <?php foreach ($inventario as $f):
        $agotado = (float)$f['cantidad_actual'] <= 0;
        $bajo    = (float)$f['cantidad_actual'] <= (float)$f['stock_minimo']; ?>
      <tr>
        <td><span class="product"><i class="prod-icon">▣</i><?= h($f['nombre_producto']) ?></span></td>
        <td><?= h(cant($f['cantidad_actual'])) ?></td>
        <td><?= h(cant($f['stock_minimo'])) ?></td>
        <td><?= $agotado ? '<span class="pill bad">Agotado</span>' : ($bajo ? '<span class="pill warn">Bajo</span>' : '<span class="pill ok">Normal</span>') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ PERSONAL Y TURNOS ============ -->
<section class="section" id="empleados">
  <div class="page-head"><div><div class="eyebrow">Gestión del equipo</div><h1>Personal y turnos</h1><p>Empleados de <?= h($tiendaNombre) ?> y sus turnos de trabajo.</p></div></div>

  <div class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div><h3>Programar turno</h3><div class="sub">Un turno por empleado y día</div></div></div>
    <form method="post" action="index.php?r=admin_accion" class="form-turno">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="accion" value="programar">
      <label>Empleado
        <select name="id_empleado" required>
          <?php foreach ($personal as $p): ?>
            <option value="<?= (int)$p['id_empleado'] ?>"><?= h($p['nombres'] . ' ' . $p['apellidos'] . ' · ' . $p['nombre_rol']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Fecha <input type="date" name="fecha" value="<?= h(date('Y-m-d')) ?>" required></label>
      <label>Entrada <input type="time" name="hora_inicio" required></label>
      <label>Salida <input type="time" name="hora_fin" required></label>
      <button class="btn primary">Programar turno</button>
    </form>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div><h3>Personal de la tienda</h3><div class="sub">Empleados activos</div></div></div>
    <?php if (!$personal): ?>
      <p class="vacio">No hay personal activo en esta tienda.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Empleado</th><th>Rol</th></tr></thead><tbody>
      <?php foreach ($personal as $p): ?>
      <tr><td><?= h($p['nombres'] . ' ' . $p['apellidos']) ?></td><td><?= h($p['nombre_rol']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>

  <?php
    $hoyTurnos = date('Y-m-d');
    $turnosSemana = (new TurnoTrabajoRepository())->semana($empleado->idTienda, $hoyTurnos);
  ?>
  <div class="panel">
    <div class="panel-head"><div><h3>Turnos de la semana</h3><div class="sub">Desde hoy (<?= h(date('d/m/Y')) ?>) por 7 días</div></div></div>
    <?php if (!$turnosSemana): ?>
      <p class="vacio">No hay turnos programados en los próximos 7 días.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Empleado</th><th>Rol</th><th>Entrada</th><th>Salida</th><th></th></tr></thead><tbody>
      <?php foreach ($turnosSemana as $tu): ?>
      <tr>
        <td><?= h(date('d/m/Y', strtotime($tu['fecha']))) ?></td>
        <td><?= h($tu['empleado']) ?></td>
        <td><?= h($tu['nombre_rol']) ?></td>
        <td><?= h(substr($tu['hora_inicio'], 0, 5)) ?></td>
        <td><?= h(substr($tu['hora_fin'], 0, 5)) ?></td>
        <td>
          <?php if ($tu['fecha'] >= $hoyTurnos): ?>
          <form method="post" action="index.php?r=admin_turno_cancelar" onsubmit="return confirm('¿Cancelar este turno?');">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="id_turno" value="<?= (int)$tu['id_turno_trabajo'] ?>">
            <button class="btn" style="color:#b00020">Cancelar</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ ANULACIONES ============ -->
<section class="section" id="solicitudes">
  <div class="page-head"><div><div class="eyebrow">Control y autorización</div><h1>Solicitudes de anulación</h1><p>Autoriza o rechaza las anulaciones pedidas por los cajeros de <?= h($tiendaNombre) ?>. Si es error del cliente, el producto vuelve al stock. Si es vencido o dañado, pasa a merma.</p></div></div>
  <div class="panel">
    <div class="panel-head"><div><h3>Solicitudes pendientes</h3><div class="sub">Decisión del administrador</div></div></div>
    <?php if (!$pendientes): ?>
      <p class="vacio">No hay solicitudes pendientes en tu tienda.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Comprobante</th><th>Total</th><th>Motivo</th><th>La pidió</th><th>Fecha</th><th>Resolver</th></tr></thead><tbody>
      <?php foreach ($pendientes as $s): ?>
      <tr>
        <td><?= h($s['serie'] . '-' . str_pad((string)$s['numero_correlativo'], 8, '0', STR_PAD_LEFT)) ?></td>
        <td>S/ <?= number_format((float)$s['total'], 2) ?></td>
        <td style="white-space:normal;max-width:220px"><?= h($s['motivo']) ?></td>
        <td><?= h($s['solicitante']) ?></td>
        <td><?= h($s['fecha_hora_solicitud']) ?></td>
        <td>
          <div class="acciones">
            <form method="post" action="index.php?r=admin_accion">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="accion" value="aprobar">
              <input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
              <button class="btn primary sm">Aprobar</button>
            </form>
            <form method="post" action="index.php?r=admin_accion">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="accion" value="rechazar">
              <input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
              <input name="motivo" placeholder="Motivo del rechazo" maxlength="150" required>
              <button class="btn danger sm">Rechazar</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ PRODUCTOS ============ -->
<?php
if (!function_exists('campos_producto')) {
    function campos_producto(?array $p, array $cat, string $csrf): void { ?>
      <form method="post" action="index.php?r=admin_accion" class="form-turno">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="accion" value="producto_guardar">
        <?php if ($p): ?><input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>"><?php endif; ?>
        <label>Nombre <input type="text" name="nombre" maxlength="100" required value="<?= h($p['nombre_producto'] ?? '') ?>"></label>
        <label>Código de barras <input type="text" name="codigo_barras" maxlength="14" inputmode="numeric" value="<?= h($p['codigo_barras'] ?? '') ?>"></label>
        <label>Categoría
          <select name="id_categoria">
            <?php foreach ($cat['categorias'] as $c): ?>
              <option value="<?= (int)$c['id_categoria'] ?>" <?= ($p && (int)$p['id_categoria'] === (int)$c['id_categoria']) ? 'selected' : '' ?>><?= h($c['nombre_categoria']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>…o nueva categoría
          <input type="text" name="nueva_categoria" maxlength="50" placeholder="Escribe una nueva">
        </label>
        <label>Marca
          <select name="id_marca">
            <?php foreach ($cat['marcas'] as $m): ?>
              <option value="<?= (int)$m['id_marca'] ?>" <?= ($p && (int)$p['id_marca'] === (int)$m['id_marca']) ? 'selected' : '' ?>><?= h($m['nombre_marca']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>…o nueva marca
          <input type="text" name="nueva_marca" maxlength="50" placeholder="Escribe una nueva">
        </label>
        <label>Unidad
          <select name="id_unidad" required>
            <?php foreach ($cat['unidades'] as $u): ?>
              <option value="<?= (int)$u['id_unidad'] ?>" <?= ($p && (int)$p['id_unidad'] === (int)$u['id_unidad']) ? 'selected' : '' ?>><?= h($u['abreviatura']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Precio S/ <input type="number" name="precio" step="0.01" min="0.01" required value="<?= h($p['precio_venta'] ?? '') ?>"></label>
        <label>Se vende por peso
          <select name="se_vende_por_peso">
            <option value="N" <?= (($p['se_vende_por_peso'] ?? 'N') === 'N') ? 'selected' : '' ?>>No</option>
            <option value="S" <?= (($p['se_vende_por_peso'] ?? 'N') === 'S') ? 'selected' : '' ?>>Sí</option>
          </select>
        </label>
        <button class="btn primary"><?= $p ? 'Guardar cambios' : 'Crear producto' ?></button>
      </form>
    <?php }
}
?>
<section class="section" id="productos">
  <div class="page-head"><div><div class="eyebrow">Catálogo</div><h1>Productos</h1><p>Catálogo general: el nombre y el precio son los mismos en las tres tiendas. Descontinuar o reactivar un producto solo afecta a tu tienda.</p></div></div>

  <div class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div><h3>Nuevo producto</h3><div class="sub">Se crea con stock 0 en cada tienda</div></div></div>
    <?php campos_producto(null, $catalogos, $csrf); ?>
  </div>

  <div class="panel">
    <div class="panel-head"><div><h3>Catálogo</h3><div class="sub"><?= count($productos) ?> productos</div></div></div>
    <?php foreach ($productos as $p): ?>
      <details style="border-bottom:1px solid #eee;padding:10px 0">
        <summary><b><?= h($p['nombre_producto']) ?></b> · <?= h($p['nombre_categoria']) ?> · <?= h($p['nombre_marca']) ?> · S/ <?= number_format((float)$p['precio_venta'], 2) ?> · <?= h($p['estado']) ?></summary>
        <?php if ($p['estado'] !== 'ELIMINADO'): ?>
          <?php campos_producto($p, $catalogos, $csrf); ?>
        <?php else: ?>
          <p class="note">Eliminado del inventario. Su código de barras quedó libre: para volver a venderlo, regístralo de nuevo en Productos.</p>
        <?php endif; ?>
        <?php if ($p['estado'] !== 'ELIMINADO'): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
          <?php if ($p['estado'] === 'ACTIVO'): ?>
          <form method="post" action="index.php?r=admin_accion" onsubmit="return confirm('¿Descontinuar este producto en <?= h($tiendaNombre) ?>? Deja de venderse solo aquí y conserva su stock. Las otras tiendas no cambian (puedes reactivarlo después).');">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="accion" value="producto_descontinuar">
            <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
            <button class="btn">Descontinuar en mi tienda</button>
          </form>
          <?php else: ?>
          <form method="post" action="index.php?r=admin_accion">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="accion" value="producto_reactivar">
            <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
            <button class="btn primary">Reactivar en mi tienda</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ TRASLADOS ============ -->
<section class="section" id="productosnuevos">
  <div class="page-head"><div><div class="eyebrow">Control y autorización</div><h1>Productos nuevos</h1><p>El almacenero de <?= h($tiendaNombre) ?> escaneó un código que no está en el catálogo. Si lo permites, él llena los datos y registra el producto. Solo ves las solicitudes de tu tienda.</p></div></div>
  <div class="panel">
    <div class="panel-head"><div><h3>Solicitudes pendientes</h3><div class="sub">Requieren tu decisión</div></div><span class="pill <?= $productosN ? 'warn' : 'ok' ?>"><?= (int)$productosN ?></span></div>
    <?php if (!$productosPendientes): ?>
      <p class="vacio">No hay productos nuevos por autorizar en tu tienda.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Código de barras</th><th>Lo escaneó</th><th>Fecha</th><th>Resolver</th></tr></thead><tbody>
      <?php foreach ($productosPendientes as $sp): ?>
      <tr>
        <td><b><?= h($sp['codigo_barras']) ?></b></td>
        <td><?= h($sp['solicitante']) ?></td>
        <td><?= h($sp['fecha_hora_solicitud']) ?></td>
        <td>
          <div class="acciones">
            <form method="post" action="index.php?r=admin_accion">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="accion" value="producto_permitir">
              <input type="hidden" name="id_solicitud_producto" value="<?= (int)$sp['id_solicitud_producto'] ?>">
              <button class="btn primary sm">Permitir</button>
            </form>
            <form method="post" action="index.php?r=admin_accion">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="accion" value="producto_rechazar">
              <input type="hidden" name="id_solicitud_producto" value="<?= (int)$sp['id_solicitud_producto'] ?>">
              <input name="motivo" placeholder="Motivo del rechazo" maxlength="150" required>
              <button class="btn danger sm">Rechazar</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
</section>

<section class="section" id="traslados">
  <div class="page-head"><div><div class="eyebrow">Movimientos entre tiendas</div><h1>Traslados</h1><p>Envía mercadería de <?= h($tiendaNombre) ?> a otra tienda. El stock sale de aquí al enviar y queda «En camino»; entra a la tienda destino cuando su almacenero confirma lo que recibió.</p></div></div>

  <div class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div><h3>Nuevo traslado</h3><div class="sub">Escribe la cantidad solo en los productos que envías</div></div></div>
    <?php if (!$stockOrigen): ?>
      <p class="vacio">Tu tienda no tiene productos con stock para trasladar.</p>
    <?php elseif (!$tiendasDestino): ?>
      <p class="vacio">No hay otra tienda activa a la cual trasladar.</p>
    <?php else: ?>
    <form method="post" action="index.php?r=admin_accion" class="form-turno">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="accion" value="traslado_registrar">
      <label>Tienda destino
        <select name="id_tienda_destino" required>
          <?php foreach ($tiendasDestino as $td): ?>
            <option value="<?= (int)$td['id_tienda'] ?>"><?= h($td['nombre_tienda']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Observación <input type="text" name="observacion" maxlength="200"></label>
      <div class="table-wrap" style="grid-column:1/-1"><table><thead><tr><th>Producto</th><th>Stock actual</th><th>Cantidad a enviar</th></tr></thead><tbody>
        <?php foreach ($stockOrigen as $s): ?>
        <tr>
          <td><?= h($s['nombre_producto']) ?></td>
          <td><?= h(cant($s['cantidad_actual'])) ?></td>
          <td><input type="number" name="cantidad[<?= (int)$s['id_producto'] ?>]" min="0" max="<?= h((string)$s['cantidad_actual']) ?>" step="<?= $s['se_vende_por_peso'] === 'S' ? '0.001' : '1' ?>" placeholder="0"></td>
        </tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <button class="btn primary">Enviar traslado</button>
    </form>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="panel-head"><div><h3>Traslados recientes</h3><div class="sub">Enviados y recibidos por esta tienda</div></div></div>
    <?php if (!$historialTraslados): ?>
      <p class="vacio">Todavía no hay traslados.</p>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Origen</th><th>Destino</th><th>Productos</th><th>Estado</th><th>Recepción</th><th>Observación</th></tr></thead><tbody>
      <?php foreach ($historialTraslados as $tr): ?>
      <tr>
        <td><?= h($tr['fecha_hora']) ?></td>
        <td><?= h($tr['origen']) ?></td>
        <td><?= h($tr['destino']) ?></td>
        <td><?= (int)$tr['productos'] ?></td>
        <td><?php if ($tr['estado'] === 'EN_CAMINO'): ?><span class="pill warn">En camino</span><?php elseif ($tr['estado'] === 'ANULADO'): ?><span class="pill bad">Anulado</span><?php else: ?><span class="pill ok">Recibido</span><?php endif; ?></td>
        <td style="white-space:normal;max-width:240px">
          <?php if ($tr['estado'] === 'EN_CAMINO'): ?>
            <?php if ((int)$tr['id_tienda_origen'] === (int)$empleado->idTienda): ?>
              Esperando confirmación de <?= h($tr['destino']) ?>
              <form method="post" action="index.php?r=admin_accion" style="display:flex;gap:6px;margin:6px 0 0" onsubmit="return confirm('¿Anular este envío? El stock volverá a tu tienda.');">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="accion" value="traslado_anular">
                <input type="hidden" name="id_traslado" value="<?= (int)$tr['id_traslado'] ?>">
                <input name="motivo" placeholder="Motivo" maxlength="150" required>
                <button class="btn danger sm">Anular envío</button>
              </form>
            <?php else: ?>
              Por recibir en tu almacén
            <?php endif; ?>
          <?php elseif ($tr['estado'] === 'ANULADO'): ?>
            Anulado <?= h($tr['fecha_recepcion'] ?? '') ?><?= $tr['receptor'] ? ' · ' . h($tr['receptor']) : '' ?>
            <?php if (!empty($tr['observacion_recepcion'])): ?><br><small><?= h($tr['observacion_recepcion']) ?></small><?php endif; ?>
          <?php else: ?>
            <?= h($tr['fecha_recepcion'] ?? '') ?><?= $tr['receptor'] ? ' · ' . h($tr['receptor']) : '' ?>
            <?php if ((float)$tr['diferencia'] > 0): ?><br><b style="color:#c33d35">Faltaron <?= h(cant($tr['diferencia'])) ?> u.</b><?php endif; ?>
            <?php if (!empty($tr['observacion_recepcion'])): ?><br><small><?= h($tr['observacion_recepcion']) ?></small><?php endif; ?>
          <?php endif; ?>
        </td>
        <td><?= h($tr['observacion'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ MERMAS: DATOS REALES ============ -->
<section class="section" id="mermas">
  <div class="page-head"><div><div class="eyebrow">Control de pérdidas</div><h1>Mermas</h1><p>Registros de productos vencidos, dañados, robados u otras pérdidas de <?= h($tiendaNombre) ?>.</p></div></div>
  <div class="panel" style="margin-bottom:16px">
    <div class="panel-head"><div><h3>Registrar merma</h3><div class="sub">Actualiza stock y kardex dentro de una transacción</div></div></div>
    <?php if (!$stockOrigen): ?><p class="vacio">No hay productos con stock disponible.</p><?php else: ?>
    <form method="post" action="index.php?r=admin_accion" class="form-turno">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="merma_registrar">
      <label>Producto<select name="id_producto" required><?php foreach ($stockOrigen as $s): ?><option value="<?= (int)$s['id_producto'] ?>"><?= h($s['nombre_producto']) ?> · stock <?= h(cant($s['cantidad_actual'])) ?></option><?php endforeach; ?></select></label>
      <label>Motivo<select name="motivo" required><option value="VENCIDO">Vencido</option><option value="DAÑADO">Dañado</option><option value="ROBO">Robo</option><option value="OTRO">Otro</option></select></label>
      <label>Cantidad<input type="number" name="cantidad" min="0.001" step="0.001" required></label>
      <label>Observación<input type="text" name="observacion" maxlength="200"></label>
      <button class="btn primary">Registrar merma</button>
    </form><?php endif; ?>
  </div>
  <div class="panel"><div class="panel-head"><div><h3>Historial de mermas</h3><div class="sub">Últimos 50 registros de esta tienda</div></div></div>
    <?php if (!$mermas): ?><p class="vacio">No hay mermas registradas.</p><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Motivo</th><th>Observación</th><th>Empleado</th><th>Productos</th></tr></thead><tbody>
    <?php foreach ($mermas as $m): ?><tr><td><?= h($m['fecha_hora']) ?></td><td><?= h($m['motivo']) ?></td><td><?= h($m['observacion'] ?? '') ?></td><td><?= h($m['empleado']) ?></td><td><?= (int)$m['productos'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div>
</section>

<!-- ============ REPORTES: CONSULTAS REALES ============ -->
<section class="section" id="reportes">
  <div class="page-head"><div><div class="eyebrow">Indicadores</div><h1>Reportes</h1><p>Información de ventas, productos, anulaciones, mermas e inventario de <?= h($tiendaNombre) ?>.</p></div></div>
  <div class="panel" style="margin-bottom:16px"><div class="panel-head"><div><h3>Ventas por día</h3><div class="sub">Últimos 30 días</div></div></div>
    <?php if (!$reportes['ventasPorDia']): ?><p class="vacio">No hay ventas en el periodo.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Día</th><th>Comprobantes</th><th>Total</th></tr></thead><tbody><?php foreach ($reportes['ventasPorDia'] as $r): ?><tr><td><?= h($r['dia']) ?></td><td><?= (int)$r['n'] ?></td><td>S/ <?= number_format((float)$r['total'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
  <div class="panel" style="margin-bottom:16px"><div class="panel-head"><div><h3>Productos más vendidos</h3><div class="sub">Ordenados por importe vendido</div></div></div>
    <?php if (!$reportes['topProductos']): ?><p class="vacio">No hay ventas de productos.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Importe</th></tr></thead><tbody><?php foreach ($reportes['topProductos'] as $r): ?><tr><td><?= h($r['nombre_producto']) ?></td><td><?= h(cant($r['cantidad'])) ?></td><td>S/ <?= number_format((float)$r['total'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
  <div class="lower">
    <article class="panel"><div class="panel-head"><div><h3>Mermas por motivo</h3><div class="sub">Registros por categoría</div></div></div><?php if (!$reportes['mermasPorMotivo']): ?><p class="vacio">Sin mermas.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Motivo</th><th>Registros</th><th>Líneas</th></tr></thead><tbody><?php foreach ($reportes['mermasPorMotivo'] as $r): ?><tr><td><?= h($r['motivo']) ?></td><td><?= (int)$r['registros'] ?></td><td><?= (int)$r['lineas'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></article>
    <article class="panel"><div class="panel-head"><div><h3>Stock crítico</h3><div class="sub">Igual o menor al mínimo</div></div></div><?php if (!$reportes['stockCritico']): ?><p class="vacio">No hay stock crítico.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Actual</th><th>Mínimo</th></tr></thead><tbody><?php foreach ($reportes['stockCritico'] as $r): ?><tr><td><?= h($r['nombre_producto']) ?></td><td><?= h(cant($r['cantidad_actual'])) ?></td><td><?= h(cant($r['stock_minimo'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></article>
  </div>
  <div class="panel" style="margin-top:16px"><div class="panel-head"><div><h3>Turnos por empleado</h3><div class="sub">Últimos 30 días</div></div></div><?php if (!$reportes['turnosPorEmpleado']): ?><p class="vacio">No hay turnos en el periodo.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Empleado</th><th>Rol</th><th>Turnos</th><th>Horas</th></tr></thead><tbody><?php foreach ($reportes['turnosPorEmpleado'] as $r): ?><tr><td><?= h($r['empleado']) ?></td><td><?= h($r['nombre_rol']) ?></td><td><?= (int)$r['turnos'] ?></td><td><?= h($r['horas']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
</section>

</main>
</div>

<script>
(function () {
  var sections = document.querySelectorAll('.section');
  var navBtns = document.querySelectorAll('#nav button');
  var crumb = document.getElementById('crumb');
  var sidebar = document.getElementById('sidebar');
  var pendientesInicial = <?= (int)($pendientesN + $productosN) ?>;
  var firmaInicial = null;

  function ir(id) {
    var sec = document.getElementById(id);
    if (!sec) return;
    sections.forEach(function (s) { s.classList.toggle('active', s === sec); });
    navBtns.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-page') === id); });
    crumb.textContent = sec.querySelector('h1') ? sec.querySelector('h1').textContent : '';
    sidebar.classList.remove('open');
    history.replaceState(null, '', '#' + id);
    window.scrollTo(0, 0);
  }

  navBtns.forEach(function (b) {
    b.addEventListener('click', function () { ir(b.getAttribute('data-page')); });
  });
  document.querySelectorAll('[data-goto]').forEach(function (el) {
    el.addEventListener('click', function (e) { e.preventDefault(); ir(el.getAttribute('data-goto')); });
  });
  var menu = document.getElementById('menu');
  if (menu) menu.addEventListener('click', function () { sidebar.classList.toggle('open'); });

  // al recargar, vuelve a la sección en la que estabas
  if (location.hash && document.getElementById(location.hash.slice(1))) {
    ir(location.hash.slice(1));
  }

  // cada 5 segundos revisa si llegó una anulación nueva o si una se resolvió
  setInterval(async function () {
    try {
      var r = await fetch('index.php?r=admin_api_pendientes', { credentials: 'same-origin' });
      var j = await r.json();
      if (j.ok && j.firma !== undefined) {
        if (firmaInicial === null) firmaInicial = j.firma;
        else if (j.firma !== firmaInicial) { location.reload(); return; }
      }
      if (j.ok && j.total !== pendientesInicial) location.reload();
    } catch (e) { /* sin conexión: se reintenta en el siguiente ciclo */ }
  }, 5000);
})();
</script>
</body>
</html>