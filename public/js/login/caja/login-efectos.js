/* Login de caja: detalles visuales (entrada escalonada, contador y barra del DNI, onda del teclado, giro de la bolsa, Esc). */
(function () {
  var still = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var dni = document.getElementById('dni');
  var clave = document.getElementById('contrasena');
  var pad = document.getElementById('pad');

  /* índices para las entradas escalonadas */
  Array.prototype.forEach.call(document.querySelector('.panel').children, function (c, i) { c.style.setProperty('--i', i); });
  Array.prototype.forEach.call(pad.children, function (c, i) { c.style.setProperty('--i', i); });

  /* contador y barra de progreso del DNI (8 dígitos) */
  function actualizar() {
    var n = dni.value.length, c = document.getElementById('cd'), estaba = c.classList.contains('ok');
    document.getElementById('pgd').style.setProperty('--p', n / 8);
    c.textContent = (n === 8 ? '✓ ' : '') + n + '/8';
    c.classList.toggle('ok', n === 8);
    if (n === 8 && !estaba && !still) { c.style.animation = 'none'; void c.offsetWidth; c.style.animation = ''; }
  }
  dni.addEventListener('input', function () { setTimeout(actualizar, 0); });
  pad.addEventListener('click', function () { setTimeout(actualizar, 0); });
  actualizar();

  /* teclado: onda al presionar y giro de la bolsa 3D */
  pad.addEventListener('pointerdown', function (e) {
    var b = e.target.closest('button');
    if (!b) return;
    if (!still) {
      var r = b.getBoundingClientRect(), s = document.createElement('span');
      s.className = 'rip';
      s.style.left = (e.clientX - r.left) + 'px';
      s.style.top = (e.clientY - r.top) + 'px';
      b.appendChild(s);
      setTimeout(function () { s.remove(); }, 600);
    }
    if (/^[0-9]$/.test(b.getAttribute('data-k')) && window.bagKick) bagKick(0.32);
  });

  /* Esc limpia los campos */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !dni.disabled) {
      dni.value = '';
      clave.value = '';
      dni.dispatchEvent(new Event('input'));
      clave.dispatchEvent(new Event('input'));
      dni.focus();
    }
  });
})();
