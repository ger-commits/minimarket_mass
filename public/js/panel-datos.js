/* Llena las tablas de Ventas y Personal con datos reales de MySQL (solo lectura).
   Si la consulta falla, se mantiene el contenido de referencia del diseño. */
(function () {
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function pill(texto, clase) { return '<span class="pill ' + clase + '">' + esc(texto) + '</span>'; }

  function cargar(seccionId, url, fila) {
    var sec = document.getElementById(seccionId);
    if (!sec) return;
    var tabla = sec.querySelector('table');
    if (!tabla) return;
    var columnas = tabla.rows[0] ? tabla.rows[0].cells.length : 1;
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'error');
        var tb = tabla.tBodies[0];
        tb.innerHTML = j.datos.length
          ? j.datos.map(fila).join('')
          : '<tr><td colspan="' + columnas + '">Sin registros para esta tienda.</td></tr>';
      })
      .catch(function () { /* se mantiene el contenido de referencia */ });
  }

  cargar('ventas', 'index.php?r=admin_api_ventas', function (v) {
    return '<tr><td>' + esc(v.comprobante) + '</td><td>' + esc(v.fecha) + '</td><td>' + esc(v.tienda) +
      '</td><td>' + esc(v.cajero) + '</td><td>' + esc(v.medio || '—') + '</td><td>S/ ' + esc(v.total) +
      '</td><td>' + pill(v.estado, v.clase) + '</td></tr>';
  });

  cargar('empleados', 'index.php?r=admin_api_personal', function (p) {
    return '<tr><td>' + esc(p.nombre) + '</td><td>' + esc(p.dni) + '</td><td>' + esc(p.rol) +
      '</td><td>' + esc(p.tienda) + '</td><td>' + pill(p.estado, p.clase) + '</td><td>' +
      esc(p.turno || 'Sin turno') + '</td></tr>';
  });
})();

/* Resumen general y Tiendas: solo lectura. Si falla la consulta, se mantienen los valores de referencia. */
(function () {
  function pct(hoy, ayer) {
    if (!ayer || Number(ayer) === 0) return '<span>Sin dato de ayer</span>';
    var v = ((Number(hoy) - Number(ayer)) / Number(ayer)) * 100;
    var clase = v >= 0 ? 'up' : 'down';
    return '<span class="' + clase + '">' + (v >= 0 ? '↗ ' : '↘ ') + Math.abs(v).toFixed(1) + '%</span> vs. día anterior';
  }

  fetch('index.php?r=admin_api_resumen', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j.ok) throw new Error('error');
      var d = j.datos;
      var st = document.querySelectorAll('#inicio .stats .stat');
      if (st.length < 4) return;
      st[0].querySelector('.stat-value').textContent = 'S/ ' + Number(d.ventas_hoy).toLocaleString('en-US', { minimumFractionDigits: 2 });
      st[0].querySelector('.stat-foot').innerHTML = pct(d.ventas_hoy, d.ventas_ayer);
      st[1].querySelector('.stat-value').textContent = d.transacciones;
      st[1].querySelector('.stat-foot').innerHTML = pct(d.transacciones, d.trans_ayer);
      st[2].querySelector('.stat-value').textContent = Number(d.productos).toLocaleString('en-US');
      st[3].querySelector('.stat-value').textContent = String(d.alertas).padStart(2, '0');
    })
    .catch(function () { /* se mantienen los valores de referencia */ });

  fetch('index.php?r=admin_api_tiendas', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j.ok) throw new Error('error');
      var tarjetas = document.querySelectorAll('#tiendas .stats .stat');
      j.datos.forEach(function (t) {
        tarjetas.forEach(function (card) {
          var nombre = card.querySelector('.stat-top');
          if (!nombre || nombre.textContent.trim().indexOf(t.nombre) !== 0) return;
          var valor = card.querySelector('.stat-value');
          valor.textContent = t.estado;
          valor.className = 'stat-value ' + (t.clase === 'ok' ? '' : 'down');
          card.querySelector('.stat-foot').textContent = 'Distrito de ' + t.distrito;
        });
      });
    })
    .catch(function () { /* se mantienen los valores de referencia */ });
})();
