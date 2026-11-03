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
