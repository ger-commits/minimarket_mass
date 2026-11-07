/* Login de caja: teclado numérico del DNI, validación del formulario, mostrar contraseña, reloj y envío.
   El formulario se envía al PHP con un POST normal (index.php?r=login); aquí no hay ninguna clave ni regla de seguridad:
   la validación real (contraseña, caja, rol, intentos) la hace el servidor. */
(function () {
  var form = document.getElementById('login');
  var dni = document.getElementById('dni');
  var clave = document.getElementById('contrasena');
  var go = document.getElementById('go');
  var eye = document.getElementById('eye');
  var pad = document.getElementById('pad');
  var tx = go.querySelector('.tx');
  var textoBoton = tx.textContent;
  var apagado = form.hasAttribute('data-off');      // caja inactiva o sin configurar: el formulario no se usa

  function limpiarDni() { dni.value = dni.value.replace(/\D/g, '').slice(0, 8); }
  function revisar() { go.disabled = apagado || !(dni.value.length === 8 && clave.value.length > 0); }

  dni.addEventListener('input', function () {
    limpiarDni();
    if (dni.value.length === 8) clave.focus();
    revisar();
  });
  clave.addEventListener('input', revisar);

  /* el teclado en pantalla escribe solo el DNI; la contraseña se escribe con el teclado normal */
  pad.addEventListener('mousedown', function (e) { e.preventDefault(); });
  pad.addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b || apagado) return;
    var k = b.getAttribute('data-k');
    if (k === 'back') dni.value = dni.value.slice(0, -1);
    else if (k === 'clear') dni.value = '';
    else if (dni.value.length < 8) dni.value += k;
    if (dni.value.length === 8) clave.focus();
    revisar();
  });

  eye.addEventListener('click', function () {
    var oculta = clave.type === 'password';
    clave.type = oculta ? 'text' : 'password';
    eye.textContent = oculta ? 'Ocultar' : 'Mostrar';
    eye.setAttribute('aria-pressed', oculta);
  });

  /* envío: se muestra "Ingresando…" y el navegador manda el formulario al PHP */
  form.addEventListener('submit', function (e) {
    if (go.disabled || go.classList.contains('loading')) { e.preventDefault(); return; }
    go.classList.add('loading');
    tx.textContent = 'Ingresando…';
  });
  /* si se vuelve con "atrás", el navegador puede restaurar la página con el botón en carga */
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { go.classList.remove('loading'); tx.textContent = textoBoton; revisar(); }
  });

  /* reloj y fecha (la hora de este equipo) */
  var DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
  function dos(n) { return n < 10 ? '0' + n : n; }
  function reloj() {
    var d = new Date(), h = d.getHours(), ap = h >= 12 ? 'p. m.' : 'a. m.';
    h = h % 12 || 12;
    document.getElementById('tm').innerHTML = h + '<span class="colon">:</span>' + dos(d.getMinutes()) + ' ' + ap;
    document.getElementById('dt').textContent =
      DIAS[d.getDay()] + ' ' + dos(d.getDate()) + '/' + dos(d.getMonth() + 1) + '/' + d.getFullYear();
  }
  reloj();
  setInterval(reloj, 15000);
  revisar();
})();
