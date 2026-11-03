<?php
// V: pantalla de caja (punto de venta). Estilos en public/css/caja/pos.css y lógica en public/js/caja/pos.js
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>MASS · Terminal de Caja</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/caja/pos.css">
</head>
<body>
<header><div class="logo"><b>mass</b><span>Minimarket</span></div><div class="side-kicker"><span class="side-kicker-dot"></span> PANEL DE OPERACIONES</div><div class="steps" id="steps"></div><div class="side-activity"><div class="side-activity-head"><span>ESTADO DEL SISTEMA</span><b>EN LÍNEA</b></div><div class="side-activity-line"><span class="side-activity-icon">⌁</span><span><strong>Terminal activa</strong><small>Registro de ventas habilitado</small></span></div><div class="side-activity-line"><span class="side-activity-icon">▦</span><span><strong>Inventario</strong><small>Consulta de productos disponible</small></span></div><div class="side-footer"><span>●</span> MASS POS <i>·</i> VERSIÓN 3.1</div></div><div class="clock"><span class="t" id="ctm"></span><span class="d" id="cdt"></span></div><div class="user" id="userCard"></div></header>
<main><div class="hero"><div><h1 id="ht"></h1><p id="hs"></p></div><div class="hr"><span class="chip" id="hturn"></span><span class="chip y" id="hven"></span></div><div class="prog" id="hp"></div></div>

<!-- TURNO -->
<div class="view" id="v-turno">
<div class="col"><div id="tmsg" class="msg ok" hidden></div><div class="card" id="tcard"></div></div>
<div class="col"><div class="card" id="trec"></div><div class="card"><h3>Turnos cerrados</h3><div id="thist"></div></div></div>
</div>

<!-- VENTAS DEL DIA -->
<div class="view" id="v-ventas">
<div class="col"><div class="card"><h3>Ventas del día · Caja Nº 01</h3><div class="row" id="vf" style="margin-bottom:14px;flex-wrap:wrap"></div><div style="overflow-x:auto"><table class="tbl" id="vt"></table></div><p id="vempty" class="hint" style="margin:14px 0 0" hidden>No hay ventas para mostrar.</p></div></div>
<div class="col"><div class="card" id="vsum"></div><button class="b1 full" onclick="go('venta')">Volver a la venta</button></div>
</div>

<!-- 1 VENTA -->
<div class="view" id="v-venta">
<div class="col">
 <div class="card"><h3>Registrar venta</h3><label>Código de barras o nombre del producto</label>
  <div class="row"><div class="grow"><input id="q" placeholder="Ej. Aceite vegetal 1 L" onkeydown="if(event.key==='Enter')agregar()"></div>
  <button class="b2" onclick="consultar()">Consultar precio</button><button class="b1" onclick="agregar()">Agregar</button></div>
  <div id="qres" class="msg warn" hidden></div></div>
 <div class="card"><h3>Detalle de la venta</h3><table class="tbl"><thead><tr><th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Subtotal</th></tr></thead><tbody id="cart"></tbody></table><div id="empty" class="hint" style="padding:24px 8px" hidden>Aún no hay productos. Escanee o escriba un producto para comenzar.</div></div>
</div>
<div class="col"><div class="card sum"></div>
 <button class="b1 full" id="toComp" onclick="go('comprobante')">Continuar a comprobante</button>
 <button class="b2 full" onclick="abrirAnular()">Anular una venta</button><button class="b2 full" onclick="go('ventas')">Ventas del día</button><button class="b2 full" onclick="go('turno')">Cierre de caja</button></div>
</div>

<!-- 2 COMPROBANTE -->
<div class="view" id="v-comprobante">
<div class="col">
 <div class="card"><h3>Tipo de comprobante</h3><div class="g2">
  <div class="tc on" id="tcB" onclick="setTipo('B')"><h2>Boleta</h2><label>DNI del cliente (opcional)</label><input id="dni" inputmode="numeric" maxlength="8" oninput="S.dni=this.value.replace(/\D/g,'');this.value=S.dni"><p class="hint">Si el cliente no da su DNI, la boleta se emite sin datos.</p></div>
  <div class="tc" id="tcF" onclick="setTipo('F')"><h2>Factura</h2><label>RUC (obligatorio, 11 dígitos)</label><input id="ruc" inputmode="numeric" maxlength="11" oninput="S.ruc=this.value.replace(/\D/g,'');this.value=S.ruc"><label style="margin-top:14px">Razón social</label><input id="rs" oninput="S.rs=this.value"></div></div></div>
 <div class="card"><h3>Cliente</h3><label>Nombre del cliente</label><div class="row"><div class="grow"><input id="cli" oninput="S.cli=this.value"></div><button class="b2" onclick="regCli()">Registrar cliente</button></div><div id="cmsg" class="msg ok" style="margin-top:12px" hidden></div></div>
</div>
<div class="col"><div class="card sum"></div><div id="cerr" class="msg err" hidden></div>
 <button class="b1 full" onclick="toCobro()">Continuar a cobro</button><button class="b2 full" onclick="go('venta')">Volver a la venta</button></div>
</div>

<!-- 3 COBRO -->
<div class="view" id="v-cobro">
<div class="col">
 <div class="card"><h3>Medio de pago</h3><div class="g3">
  <button class="mp b2 on" data-m="ef" onclick="setMedio('ef')">Efectivo</button><button class="mp b2" data-m="tj" onclick="setMedio('tj')">Tarjeta</button><button class="mp b2" data-m="sip" onclick="setMedio('sip')">Billetera Sip</button></div></div>
 <div class="card"><div id="pef"><h3>Pago en efectivo · sin autorización externa</h3>
  <div class="row"><div class="grow"><label>Monto recibido</label><input id="rec" value="S/ 100.00" inputmode="decimal" oninput="S.rec=parseFloat(this.value.replace(/[^\d.]/g,''))||0;rVuelto()"></div><div class="grow"><label>Vuelto</label><div class="vuelto" id="vuelto"></div></div></div>
  <div class="g3" style="margin-top:12px"><button class="b2" onclick="setRec(70)">S/ 70</button><button class="b2" onclick="setRec(100)">S/ 100</button><button class="b2" onclick="setRec(200)">S/ 200</button></div></div>
  <div id="ptar" hidden><h3 id="ptt">Pago con tarjeta · requiere autorización</h3><p style="margin:0">Se enviará el monto total al Sistema de pago y se esperará su respuesta.</p></div>
  <p class="hint" style="margin:14px 0 0">Tarjeta y Sip: la caja envía la solicitud al Sistema de pago y espera su respuesta.</p></div>
 <div class="card"><h3>Resultado del pago</h3><div id="stat" class="msg w2">Aún no se ha confirmado el pago.</div></div>
</div>
<div class="col"><div class="card sum"></div><button class="b1 full" id="pay" onclick="confirmar()">Confirmar pago</button><button class="b2 full" onclick="go('comprobante')">Volver a comprobante</button></div>
</div>

<!-- 4 RESULTADO -->
<div class="view" id="v-resultado">
<div class="col"><div class="card"><h3>Resultado</h3><div class="msg ok big" style="margin-bottom:12px">Pago aprobado · comprobante emitido</div><div id="rdet"></div></div></div>
<div class="col"><div class="card sum"></div><button class="b1 full" onclick="nueva()">Nueva venta</button><button class="b2 full" onclick="go('ventas')">Ventas del día</button><button class="b2 full" onclick="abrirAnular()">Anular una venta</button></div>
</div>

<!-- ANULAR -->
<div class="view" id="v-anular">
<div class="col">
 <div class="card"><h3>Buscar la venta</h3><label>N.º de venta o comprobante</label>
  <div class="row"><div class="grow"><input id="aq" placeholder="Ej. B001-000122" onkeydown="if(event.key==='Enter')buscar()"></div><button class="b1" onclick="buscar()">Buscar</button></div>
  <div id="ares" class="msg warn" hidden></div></div>
 <div class="card"><h3>Motivo de la anulación</h3><label>Motivo</label><input id="mot" placeholder="Ej. Error al registrar el producto" oninput="rAnular()"></div>
 <div><button class="b1" id="solic" style="width:360px;max-width:100%" onclick="solicitar()">Solicitar autorización</button> <button class="b2" onclick="go(S.prev)" style="margin-left:8px">Volver a caja</button></div>
</div>
<div class="col">
 <div class="card"><h3>Autorización del Administrador</h3><div id="astat" class="msg w2">Aún no se ha solicitado autorización.</div>
  <p class="hint" style="margin:10px 0 0">Si la rechaza, la venta sigue vigente y se avisa en caja.</p>
  <div id="demo" class="row" style="margin-top:12px" hidden><button class="b2" style="height:40px;font-size:15px" onclick="resp(true)">Simular: autoriza</button><button class="b2" style="height:40px;font-size:15px" onclick="resp(false)">Simular: rechaza</button></div></div>
 <div class="card"><h3>Al autorizar, el sistema</h3><ol><li>Revierte el stock</li><li>Emite la nota de crédito</li><li>Registra la anulación en el Sistema central</li></ol></div>
 <div id="afin" class="msg ok" hidden>Venta anulada · nota de crédito emitida</div>
</div>
</div>
</main>
<script src="js/caja/pos.js"></script>
</body>
</html>
