<?php
// V: panel del administrador. Estilos en public/css/panel.css y comportamiento en public/js/panel-administrador.js
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Mass | Panel de administración</title>
<link rel="stylesheet" href="css/panel.css">
</head>
<body>
<div class="app">
<aside class="sidebar" id="sidebar">
  <div class="brand"><div class="brand-mark">M</div><div><strong>MASS</strong><small>ADMINISTRACIÓN</small></div></div>
  <div class="store-label">Panel principal</div>
  <nav class="nav" id="nav">
    <button class="active" data-page="inicio"><span class="ico">▦</span>Resumen general</button>
    <button data-page="ventas"><span class="ico">▤</span>Ventas</button>
    <button data-page="inventario"><span class="ico">▣</span>Inventario <span class="count">8</span></button>
    <button data-page="productos"><span class="ico">▧</span>Productos</button>
    <button data-page="empleados"><span class="ico">♙</span>Personal y turnos</button>
    <button data-page="tiendas"><span class="ico">⌂</span>Tiendas</button>
    <button data-page="traslados"><span class="ico">⇄</span>Traslados</button>
    <button data-page="mermas"><span class="ico">⌁</span>Mermas</button>
    <button data-page="solicitudes"><span class="ico">◷</span>Anulaciones <span class="count">3</span></button>
    <button data-page="reportes"><span class="ico">▥</span>Reportes</button>
  </nav>
  <div class="sidebar-bottom"><div class="user-card"><div class="avatar">AD</div><div><b>Administrador</b><small>Administrador de tienda</small></div></div></div>
</aside>
<main class="main">
<header class="topbar">
  <div style="display:flex;align-items:center;gap:12px"><button class="icon-btn mobile-menu" id="menu">☰</button><div class="crumb">Mass / <b id="crumb">Resumen general</b></div></div>
  <div class="top-actions"><div class="search"><input id="search" placeholder="Buscar en el panel..."></div><button class="icon-btn" id="bell" title="Notificaciones">♧<i class="dot"></i></button><div class="avatar" style="background:#222;color:#ffd21f">AD</div></div>
</header>
<section class="section active" id="inicio">
  <div class="page-head"><div><div class="eyebrow">Panel de control · Tienda Mass</div><h1>Resumen general</h1><p>Visualiza el movimiento de tus tiendas y la operación del negocio.</p></div><div class="head-actions"><button class="btn" id="export">↓ Exportar</button><button class="btn primary" id="new">＋ Nueva acción</button></div></div>

  <div class="welcome-banner">
    <div class="welcome-copy">
      <span class="welcome-tag"><i></i> CENTRO DE OPERACIONES MASS</span>
      <h2>¡Hola, administrador! <span>👋</span></h2>
      <p>Todo lo que sucede en tus tiendas, en un solo lugar.</p>
      <div class="welcome-meta"><span>◷ Jornada en curso</span><span>•</span><span>3 tiendas conectadas</span></div>
    </div>
    <div class="welcome-art" aria-hidden="true">
      <div class="art-circle circle-one"></div><div class="art-circle circle-two"></div>
      <div class="art-bag">M<span>ASS</span></div><div class="art-spark spark-one">✦</div><div class="art-spark spark-two">✳</div>
    </div>
    <div class="banner-stamp">MASS<br><small>SIEMPRE CERCA</small></div>
  </div>
  <div class="quick-strip">
    <button data-goto="ventas"><span class="quick-ico">▤</span><span><b>Ver ventas</b><small>Actividad comercial</small></span><em>↗</em></button>
    <button data-goto="inventario"><span class="quick-ico">▣</span><span><b>Revisar stock</b><small>Existencias actuales</small></span><em>↗</em></button>
    <button data-goto="empleados"><span class="quick-ico">♙</span><span><b>Personal</b><small>Roles y turnos</small></span><em>↗</em></button>
    <button data-goto="reportes"><span class="quick-ico">▥</span><span><b>Reportes</b><small>Analítica del negocio</small></span><em>↗</em></button>
  </div>
  <div class="filters"><select class="filter" id="storeFilter"><option>Todas las tiendas</option><option>Cayma</option><option>Cerro Colorado</option><option>Yanahuara</option></select><select class="filter"><option>Hoy</option><option>Últimos 7 días</option><option>Este mes</option></select><span class="period">Martes, 03 de noviembre de 2026</span></div>
  <div class="stats">
    <article class="stat"><div class="stat-top">Ventas del día <span class="stat-icon">◉</span></div><div class="stat-value">S/ 8,462.50</div><div class="stat-foot"><span class="up">↗ 12.8%</span> vs. día anterior</div></article>
    <article class="stat"><div class="stat-top">Transacciones <span class="stat-icon">▤</span></div><div class="stat-value">386</div><div class="stat-foot"><span class="up">↗ 8.2%</span> vs. día anterior</div></article>
    <article class="stat"><div class="stat-top">Productos activos <span class="stat-icon">▣</span></div><div class="stat-value">1,248</div><div class="stat-foot">En el catálogo general</div></article>
    <article class="stat"><div class="stat-top">Alertas de stock <span class="stat-icon">⚠</span></div><div class="stat-value">08</div><div class="stat-foot"><span class="down">Requieren atención</span> en tiendas</div></article>
  </div>
  <div class="grid-main">
    <article class="panel"><div class="panel-head"><div><h3>Rendimiento de ventas</h3><div class="sub">Comparación de ventas por día · monto en soles</div></div><select class="select"><option>Esta semana</option><option>Este mes</option></select></div>
      <div class="chart" aria-label="Gráfico ilustrativo de ventas semanales">
        <div class="bar-group"><i class="bar" style="height:48%"></i><i class="bar dark" style="height:58%"></i><label>Lun</label></div>
        <div class="bar-group"><i class="bar" style="height:65%"></i><i class="bar dark" style="height:53%"></i><label>Mar</label></div>
        <div class="bar-group"><i class="bar" style="height:54%"></i><i class="bar dark" style="height:70%"></i><label>Mié</label></div>
        <div class="bar-group"><i class="bar" style="height:78%"></i><i class="bar dark" style="height:65%"></i><label>Jue</label></div>
        <div class="bar-group"><i class="bar" style="height:61%"></i><i class="bar dark" style="height:84%"></i><label>Vie</label></div>
        <div class="bar-group"><i class="bar" style="height:88%"></i><i class="bar dark" style="height:76%"></i><label>Sáb</label></div>
        <div class="bar-group"><i class="bar" style="height:68%"></i><i class="bar dark" style="height:92%"></i><label>Dom</label></div>
      </div><div class="chart-legend"><span><i class="legend-dot yellow"></i>Semana anterior</span><span><i class="legend-dot"></i>Semana actual</span></div>
    </article>
    <article class="panel"><div class="panel-head"><div><h3>Ventas por tienda</h3><div class="sub">Distribución del día</div></div><span class="pill ok">En operación</span></div>
      <div class="store-list">
        <div class="store-row"><b>Cayma</b><strong>S/ 3,480.00</strong><small>41% del total</small><div class="track"><i style="width:41%"></i></div></div>
        <div class="store-row"><b>Cerro Colorado</b><strong>S/ 2,910.50</strong><small>34% del total</small><div class="track"><i style="width:34%"></i></div></div>
        <div class="store-row"><b>Yanahuara</b><strong>S/ 2,072.00</strong><small>25% del total</small><div class="track"><i style="width:25%"></i></div></div>
      </div>
    </article>
  </div>
  <div class="lower">
    <article class="panel"><div class="panel-head"><div><h3>Productos con stock bajo</h3><div class="sub">Productos que necesitan reposición</div></div><button class="btn" data-goto="inventario">Ver inventario →</button></div>
      <div class="table-wrap"><table><thead><tr><th>Producto</th><th>Tienda</th><th>Stock actual</th><th>Mínimo</th><th>Estado</th></tr></thead><tbody>
      <tr><td><span class="product"><i class="prod-icon">🥛</i>Leche evaporada 400 g</span></td><td>Cayma</td><td>6 unid.</td><td>15</td><td><span class="pill bad">Crítico</span></td></tr>
      <tr><td><span class="product"><i class="prod-icon">🍚</i>Arroz extra 1 kg</span></td><td>Yanahuara</td><td>9 unid.</td><td>20</td><td><span class="pill warn">Bajo</span></td></tr>
      <tr><td><span class="product"><i class="prod-icon">🧴</i>Aceite vegetal 1 L</span></td><td>Cerro Colorado</td><td>11 unid.</td><td>18</td><td><span class="pill warn">Bajo</span></td></tr>
      <tr><td><span class="product"><i class="prod-icon">🥤</i>Gaseosa  por 1.5 L</span></td><td>Cayma</td><td>4 unid.</td><td>12</td><td><span class="pill bad">Crítico</span></td></tr>
      </tbody></table></div>
    </article>
    <article class="panel"><div class="panel-head"><div><h3>Actividad pendiente</h3><div class="sub">Acciones que requieren revisión</div></div><span class="pill warn">5 pendientes</span></div>
      <div class="alert-list">
        <div class="alert"><div class="alert-ico">↶</div><div><b>Solicitud de anulación #AN-008</b><small>Venta B001-00248 · Cayma · hace 12 min</small><br><a href="#" data-goto="solicitudes">Revisar solicitud →</a></div></div>
        <div class="alert"><div class="alert-ico">⇄</div><div><b>Traslado pendiente de validación</b><small>Cerro Colorado → Yanahuara · hace 35 min</small><br><a href="#" data-goto="traslados">Ver traslado →</a></div></div>
        <div class="alert"><div class="alert-ico">⚠</div><div><b>Stock crítico en 2 productos</b><small>Revisar disponibilidad por tienda</small><br><a href="#" data-goto="inventario">Ver alertas →</a></div></div>
      </div>
    </article>
  </div>
</section>
<section class="section" id="ventas"><div class="page-head"><div><div class="eyebrow">Operación comercial</div><h1>Ventas</h1><p>Consulta las ventas, comprobantes y medios de pago registrados.</p></div><button class="btn primary" data-action="Reporte de ventas">↓ Exportar ventas</button></div><div class="panel"><div class="panel-head"><div><h3>Últimas ventas registradas</h3><div class="sub">Boletas, facturas y medios de pago</div></div><select class="select"><option>Todas las tiendas</option><option>Cayma</option><option>Cerro Colorado</option><option>Yanahuara</option></select></div><div class="table-wrap"><table><thead><tr><th>Comprobante</th><th>Fecha</th><th>Tienda</th><th>Cajero</th><th>Medio de pago</th><th>Total</th><th>Estado</th></tr></thead><tbody><tr><td>B001-00248</td><td>03/11/2026 08:15</td><td>Cayma</td><td>J. Quispe</td><td>Tarjeta</td><td>S/ 86.50</td><td><span class="pill ok">Pagada</span></td></tr><tr><td>F001-00091</td><td>03/11/2026 08:09</td><td>Yanahuara</td><td>M. Rojas</td><td>Efectivo</td><td>S/ 214.90</td><td><span class="pill ok">Pagada</span></td></tr><tr><td>B001-00247</td><td>03/11/2026 07:58</td><td>Cerro Colorado</td><td>A. Flores</td><td>Billetera SIP</td><td>S/ 32.00</td><td><span class="pill ok">Pagada</span></td></tr><tr><td>B001-00246</td><td>03/11/2026 07:42</td><td>Cayma</td><td>J. Quispe</td><td>Efectivo</td><td>S/ 48.70</td><td><span class="pill warn">Por revisar</span></td></tr></tbody></table></div></div></section>
<section class="section" id="inventario"><div class="page-head"><div><div class="eyebrow">Control de existencias</div><h1>Inventario</h1><p>Stock por tienda, movimientos, recepción y reposición de mercancía.</p></div><button class="btn primary" data-action="Registrar movimiento de stock">＋ Registrar movimiento</button></div><div class="stats"><article class="stat"><div class="stat-top">Unidades registradas</div><div class="stat-value">18,420</div><div class="stat-foot">Stock consolidado de las 3 tiendas</div></article><article class="stat"><div class="stat-top">Stock bajo</div><div class="stat-value">08</div><div class="stat-foot"><span class="down">Atención requerida</span></div></article><article class="stat"><div class="stat-top">Recepciones del mes</div><div class="stat-value">42</div><div class="stat-foot">Movimientos de entrada</div></article><article class="stat"><div class="stat-top">Mermas registradas</div><div class="stat-value">13</div><div class="stat-foot">Movimientos de salida</div></article></div><div class="panel" style="margin-top:16px"><h3>Existencias por producto y tienda</h3><p class="sub">Vista de demostración; datos de ejemplo.</p><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Categoría</th><th>Cayma</th><th>Cerro Colorado</th><th>Yanahuara</th><th>Estado</th></tr></thead><tbody><tr><td>Arroz extra 1 kg</td><td>Abarrotes</td><td>32</td><td>28</td><td>9</td><td><span class="pill warn">Bajo</span></td></tr><tr><td>Leche evaporada 400 g</td><td>Lácteos</td><td>6</td><td>25</td><td>31</td><td><span class="pill bad">Crítico</span></td></tr><tr><td>Aceite vegetal 1 L</td><td>Abarrotes</td><td>27</td><td>11</td><td>22</td><td><span class="pill warn">Bajo</span></td></tr></tbody></table></div></div></section>
<section class="section" id="productos"><div class="page-head"><div><div class="eyebrow">Catálogo general</div><h1>Productos</h1><p>Administra productos, categorías, marcas, unidades y precios.</p></div><button class="btn primary" data-action="Nuevo producto">＋ Nuevo producto</button></div><div class="panel"><div class="panel-head"><div><h3>Catálogo de productos</h3><div class="sub">El precio de venta es único para todas las tiendas según el diseño de datos.</div></div></div><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Categoría</th><th>Marca</th><th>Unidad</th><th>Precio</th><th>Estado</th></tr></thead><tbody><tr><td>Arroz extra 1 kg</td><td>Abarrotes</td><td>Mass</td><td>unid.</td><td>S/ 4.80</td><td><span class="pill ok">Activo</span></td></tr><tr><td>Leche evaporada 400 g</td><td>Lácteos</td><td>Gloria</td><td>unid.</td><td>S/ 4.20</td><td><span class="pill ok">Activo</span></td></tr><tr><td>Aceite vegetal 1 L</td><td>Abarrotes</td><td>Primor</td><td>unid.</td><td>S/ 8.90</td><td><span class="pill ok">Activo</span></td></tr></tbody></table></div></div></section>
<section class="section" id="empleados"><div class="page-head"><div><div class="eyebrow">Gestión del equipo</div><h1>Personal y turnos</h1><p>Consulta empleados, roles, cajas y turnos de trabajo.</p></div><button class="btn primary" data-action="Registrar empleado">＋ Nuevo empleado</button></div><div class="panel"><h3>Personal por rol</h3><p class="sub">Roles contemplados en el sistema: Administrador, Cajero y Almacenero.</p><div class="table-wrap"><table><thead><tr><th>Empleado</th><th>DNI</th><th>Rol</th><th>Tienda</th><th>Estado</th><th>Turno</th></tr></thead><tbody><tr><td>Juan Quispe</td><td>••••••21</td><td>Cajero</td><td>Cayma</td><td><span class="pill ok">Activo</span></td><td>08:00 – 16:00</td></tr><tr><td>María Rojas</td><td>••••••45</td><td>Almacenero</td><td>Yanahuara</td><td><span class="pill ok">Activo</span></td><td>07:00 – 15:00</td></tr><tr><td>Administrador</td><td>••••••08</td><td>Administrador</td><td>Cerro Colorado</td><td><span class="pill ok">Activo</span></td><td>08:00 – 17:00</td></tr></tbody></table></div></div></section>
<section class="section" id="tiendas"><div class="page-head"><div><div class="eyebrow">Sucursales</div><h1>Tiendas</h1><p>Seguimiento de las tres tiendas contempladas en el proyecto de Arequipa.</p></div><button class="btn" data-action="Editar tiendas">⚙ Configuración</button></div><div class="stats"><article class="stat"><div class="stat-top">Cayma <span class="stat-icon">⌂</span></div><div class="stat-value" style="font-size:20px">Activa</div><div class="stat-foot">Distrito de Cayma</div></article><article class="stat"><div class="stat-top">Cerro Colorado <span class="stat-icon">⌂</span></div><div class="stat-value" style="font-size:20px">Activa</div><div class="stat-foot">Distrito de Cerro Colorado</div></article><article class="stat"><div class="stat-top">Yanahuara <span class="stat-icon">⌂</span></div><div class="stat-value" style="font-size:20px">Activa</div><div class="stat-foot">Distrito de Yanahuara</div></article></div></section>
<section class="section" id="traslados"><div class="page-head"><div><div class="eyebrow">Movimiento entre sucursales</div><h1>Traslados</h1><p>Registra y valida movimientos de mercadería entre tiendas distintas.</p></div><button class="btn primary" data-action="Nuevo traslado">＋ Nuevo traslado</button></div><div class="panel"><h3>Traslados recientes</h3><p class="sub">El sistema contempla origen, destino y actualización de stock.</p><table><thead><tr><th>Código</th><th>Origen</th><th>Destino</th><th>Fecha</th><th>Estado</th></tr></thead><tbody><tr><td>TR-0018</td><td>Cerro Colorado</td><td>Yanahuara</td><td>03/11/2026</td><td><span class="pill warn">Por validar</span></td></tr><tr><td>TR-0017</td><td>Cayma</td><td>Yanahuara</td><td>02/11/2026</td><td><span class="pill ok">Registrado</span></td></tr></tbody></table></div></section>
<section class="section" id="mermas"><div class="page-head"><div><div class="eyebrow">Control de pérdidas</div><h1>Mermas</h1><p>Registra productos dañados, vencidos u otras causas y descuenta stock.</p></div><button class="btn primary" data-action="Registrar merma">＋ Registrar merma</button></div><div class="panel"><h3>Últimas mermas</h3><p class="sub">Cada registro contempla producto, cantidad y motivo.</p><table><thead><tr><th>Fecha</th><th>Producto</th><th>Tienda</th><th>Cantidad</th><th>Motivo</th></tr></thead><tbody><tr><td>03/11/2026</td><td>Yogurt 1 L</td><td>Cayma</td><td>2 unid.</td><td>Producto vencido</td></tr><tr><td>02/11/2026</td><td>Gaseosa 1.5 L</td><td>Yanahuara</td><td>1 unid.</td><td>Envase dañado</td></tr></tbody></table></div></section>
<section class="section" id="solicitudes"><div class="page-head"><div><div class="eyebrow">Control y autorización</div><h1>Solicitudes de anulación</h1><p>El administrador revisa y autoriza o rechaza solicitudes de anulación.</p></div></div><div class="panel"><h3>Solicitudes pendientes</h3><p class="sub">Una anulación aprobada revierte stock y emite nota de crédito.</p><table><thead><tr><th>Solicitud</th><th>Venta</th><th>Tienda</th><th>Motivo</th><th>Fecha</th><th>Acción</th></tr></thead><tbody><tr><td>AN-008</td><td>B001-00248</td><td>Cayma</td><td>Error en compra</td><td>08:03</td><td><button class="btn" data-action="Revisar AN-008">Revisar</button></td></tr><tr><td>AN-007</td><td>B001-00231</td><td>Yanahuara</td><td>Producto duplicado</td><td>Ayer</td><td><button class="btn" data-action="Revisar AN-007">Revisar</button></td></tr></tbody></table></div></section>
<section class="section" id="reportes"><div class="page-head"><div><div class="eyebrow">Análisis del negocio</div><h1>Reportes</h1><p>Consulta indicadores de ventas, stock, movimientos y operación.</p></div><button class="btn primary" data-action="Generar reporte">＋ Generar reporte</button></div><div class="stats"><article class="stat"><div class="stat-top">Reporte de ventas</div><div class="stat-value">Ventas</div><div class="stat-foot">Por fecha, tienda y comprobante</div></article><article class="stat"><div class="stat-top">Reporte de inventario</div><div class="stat-value">Stock</div><div class="stat-foot">Existencias y movimientos</div></article><article class="stat"><div class="stat-top">Reporte de mermas</div><div class="stat-value">Mermas</div><div class="stat-foot">Por producto y motivo</div></article><article class="stat"><div class="stat-top">Reporte de turnos</div><div class="stat-value">Turnos</div><div class="stat-foot">Personal y cajas</div></article></div></section>
</main></div>
<div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <div class="modal-head"><h2 id="modalTitle">Nuevo registro</h2><button class="modal-close" id="modalClose" aria-label="Cerrar">×</button></div>
    <form id="modalForm"><div class="modal-body" id="modalFields"></div>
      <div class="modal-foot"><button type="button" class="btn" id="modalCancel">Cancelar</button><button type="submit" class="btn primary">Guardar registro</button></div>
    </form>
  </div>
</div>
<div class="toast" id="toast">Acción de demostración</div>
<script src="js/panel-administrador.js"></script>
<script src="js/panel-datos.js"></script>
</body>
</html>
