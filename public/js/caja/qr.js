/* Generador de códigos QR para la caja Mass. Sin librerías ni internet.
   Modo byte (UTF-8), corrección de errores M, versiones 1 a 10 (hasta 213 bytes).
   Uso:  QR.svg('texto', 4)  → cadena <svg> lista para insertar (el 4 es el margen en módulos).
         QR.matriz('texto')  → arreglo de filas con true = módulo oscuro. */
const QR = (() => {
  const ECC_POR_BLOQUE = [0, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26];   // nivel M
  const BLOQUES        = [0, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5];             // nivel M
  const MAX_VER = 10;

  const bit = (x, i) => ((x >>> i) & 1) !== 0;

  function modulosDatos(v) {
    let r = (16 * v + 128) * v + 64;
    if (v >= 2) { const n = Math.floor(v / 7) + 2; r -= (25 * n - 10) * n - 55; if (v >= 7) r -= 36; }
    return r;
  }
  const totalCodewords = v => Math.floor(modulosDatos(v) / 8);
  const datosCodewords = v => totalCodewords(v) - ECC_POR_BLOQUE[v] * BLOQUES[v];

  // ---------- Reed-Solomon en GF(256) ----------
  function gfMul(x, y) { let z = 0; for (let i = 7; i >= 0; i--) { z = (z << 1) ^ ((z >>> 7) * 0x11D); z ^= ((y >>> i) & 1) * x; } return z & 0xFF; }
  function divisor(grado) {
    const r = new Array(grado).fill(0); r[grado - 1] = 1; let raiz = 1;
    for (let i = 0; i < grado; i++) {
      for (let j = 0; j < r.length; j++) { r[j] = gfMul(r[j], raiz); if (j + 1 < r.length) r[j] ^= r[j + 1]; }
      raiz = gfMul(raiz, 0x02);
    }
    return r;
  }
  function resto(datos, div) {
    const r = new Array(div.length).fill(0);
    for (const b of datos) { const f = b ^ r.shift(); r.push(0); div.forEach((c, i) => { r[i] ^= gfMul(c, f); }); }
    return r;
  }

  // ---------- datos → codewords ----------
  function codificar(texto) {
    const bytes = [...new TextEncoder().encode(texto)];
    let ver = 1;
    for (; ver <= MAX_VER; ver++) {
      const cc = ver < 10 ? 8 : 16;
      if (4 + cc + 8 * bytes.length <= datosCodewords(ver) * 8) break;
    }
    if (ver > MAX_VER) throw new RangeError('El texto es demasiado largo para el QR (máx. 213 bytes).');
    const bits = [];
    const poner = (val, n) => { for (let i = n - 1; i >= 0; i--) bits.push((val >>> i) & 1); };
    poner(0x4, 4); poner(bytes.length, ver < 10 ? 8 : 16); bytes.forEach(b => poner(b, 8));
    const cap = datosCodewords(ver) * 8;
    poner(0, Math.min(4, cap - bits.length));
    poner(0, (8 - bits.length % 8) % 8);
    for (let pad = 0xEC; bits.length < cap; pad ^= 0xEC ^ 0x11) poner(pad, 8);
    const cw = new Array(bits.length / 8).fill(0);
    bits.forEach((b, i) => { cw[i >>> 3] |= b << (7 - (i & 7)); });
    return { ver, cw };
  }

  function intercalar(datos, ver) {
    const nb = BLOQUES[ver], eb = ECC_POR_BLOQUE[ver], raw = totalCodewords(ver);
    const cortos = nb - raw % nb, largoCorto = Math.floor(raw / nb);
    const div = divisor(eb), bloques = [];
    for (let i = 0, k = 0; i < nb; i++) {
      const d = datos.slice(k, k + largoCorto - eb + (i < cortos ? 0 : 1)); k += d.length;
      const e = resto(d, div);
      if (i < cortos) d.push(0);
      bloques.push(d.concat(e));
    }
    const out = [];
    for (let i = 0; i < bloques[0].length; i++)
      bloques.forEach((b, j) => { if (i !== largoCorto - eb || j >= cortos) out.push(b[i]); });
    return out;
  }

  // ---------- matriz ----------
  function construir(ver, cw) {
    const n = ver * 4 + 17;
    const m = Array.from({ length: n }, () => new Array(n).fill(false));
    const f = Array.from({ length: n }, () => new Array(n).fill(false));
    const set = (x, y, v) => { m[y][x] = v; f[y][x] = true; };

    for (let i = 0; i < n; i++) { set(6, i, i % 2 === 0); set(i, 6, i % 2 === 0); }
    const buscador = (cx, cy) => {
      for (let dy = -4; dy <= 4; dy++) for (let dx = -4; dx <= 4; dx++) {
        const d = Math.max(Math.abs(dx), Math.abs(dy)), x = cx + dx, y = cy + dy;
        if (x >= 0 && x < n && y >= 0 && y < n) set(x, y, d !== 2 && d !== 4);
      }
    };
    buscador(3, 3); buscador(n - 4, 3); buscador(3, n - 4);

    const pos = [];
    if (ver > 1) {
      const na = Math.floor(ver / 7) + 2, paso = Math.ceil((ver * 4 + 4) / (na * 2 - 2)) * 2;
      pos.push(6);
      for (let p = n - 7; pos.length < na; p -= paso) pos.splice(1, 0, p);
    }
    pos.forEach((cy, i) => pos.forEach((cx, j) => {
      if ((i === 0 && j === 0) || (i === 0 && j === pos.length - 1) || (i === pos.length - 1 && j === 0)) return;
      for (let dy = -2; dy <= 2; dy++) for (let dx = -2; dx <= 2; dx++) set(cx + dx, cy + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
    }));

    const formato = mask => {
      const data = (0 << 3) | mask;                       // nivel M = 00
      let r = data; for (let i = 0; i < 10; i++) r = (r << 1) ^ ((r >>> 9) * 0x537);
      const bits = ((data << 10) | r) ^ 0x5412;
      for (let i = 0; i <= 5; i++) set(8, i, bit(bits, i));
      set(8, 7, bit(bits, 6)); set(8, 8, bit(bits, 7)); set(7, 8, bit(bits, 8));
      for (let i = 9; i < 15; i++) set(14 - i, 8, bit(bits, i));
      for (let i = 0; i < 8; i++) set(n - 1 - i, 8, bit(bits, i));
      for (let i = 8; i < 15; i++) set(8, n - 15 + i, bit(bits, i));
      set(8, n - 8, true);
    };
    formato(0);                                            // reserva el espacio
    if (ver >= 7) {
      let r = ver; for (let i = 0; i < 12; i++) r = (r << 1) ^ ((r >>> 11) * 0x1F25);
      const bits = (ver << 12) | r;
      for (let i = 0; i < 18; i++) { const a = n - 11 + i % 3, b = Math.floor(i / 3); set(a, b, bit(bits, i)); set(b, a, bit(bits, i)); }
    }

    // colocar los datos en zigzag
    let k = 0;
    for (let der = n - 1; der >= 1; der -= 2) {
      if (der === 6) der = 5;
      for (let v = 0; v < n; v++) for (let j = 0; j < 2; j++) {
        const x = der - j, arriba = ((der + 1) & 2) === 0, y = arriba ? n - 1 - v : v;
        if (!f[y][x] && k < cw.length * 8) { m[y][x] = bit(cw[k >>> 3], 7 - (k & 7)); k++; }
      }
    }
    return { n, m, f, formato };
  }

  const MASCARAS = [
    (x, y) => (x + y) % 2 === 0, (x, y) => y % 2 === 0, (x, y) => x % 3 === 0, (x, y) => (x + y) % 3 === 0,
    (x, y) => (Math.floor(x / 3) + Math.floor(y / 2)) % 2 === 0, (x, y) => x * y % 2 + x * y % 3 === 0,
    (x, y) => (x * y % 2 + x * y % 3) % 2 === 0, (x, y) => ((x + y) % 2 + x * y % 3) % 2 === 0,
  ];
  const aplicar = (q, mask) => { for (let y = 0; y < q.n; y++) for (let x = 0; x < q.n; x++) if (!q.f[y][x] && MASCARAS[mask](x, y)) q.m[y][x] = !q.m[y][x]; };

  function penalizacion(m, n) {
    let p = 0, oscuros = 0;
    const lineas = [];
    for (let i = 0; i < n; i++) { lineas.push(m[i]); lineas.push(m.map(r => r[i])); }
    for (const l of lineas) {
      let corrida = 1;
      for (let i = 1; i <= n; i++) {
        if (i < n && l[i] === l[i - 1]) corrida++;
        else { if (corrida >= 5) p += 3 + (corrida - 5); corrida = 1; }
      }
      const s = l.map(b => (b ? '1' : '0')).join('');
      for (const pat of ['10111010000', '00001011101']) for (let i = s.indexOf(pat); i !== -1; i = s.indexOf(pat, i + 1)) p += 40;
    }
    for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) {
      if (m[y][x]) oscuros++;
      if (x < n - 1 && y < n - 1 && m[y][x] === m[y][x + 1] && m[y][x] === m[y + 1][x] && m[y][x] === m[y + 1][x + 1]) p += 3;
    }
    const total = n * n;
    p += (Math.ceil(Math.abs(oscuros * 20 - total * 10) / total) - 1) * 10;
    return p;
  }

  function matriz(texto) {
    const { ver, cw } = codificar(String(texto));
    const datos = intercalar(cw, ver);
    let mejor = null, mejorP = Infinity;
    for (let mask = 0; mask < 8; mask++) {
      const q = construir(ver, datos);
      aplicar(q, mask); q.formato(mask);
      const p = penalizacion(q.m, q.n);
      if (p < mejorP) { mejorP = p; mejor = q; }
    }
    return mejor.m;
  }

  function svg(texto, margen = 4) {
    const m = matriz(texto), n = m.length, t = n + margen * 2;
    let d = '';
    m.forEach((fila, y) => fila.forEach((o, x) => { if (o) d += `M${x + margen},${y + margen}h1v1h-1z`; }));
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${t} ${t}" shape-rendering="crispEdges" role="img" aria-label="Código QR de pago">` +
           `<rect width="${t}" height="${t}" fill="#fff"/><path d="${d}" fill="#000"/></svg>`;
  }

  return { svg, matriz };
})();
if (typeof module !== 'undefined') module.exports = QR;   // solo para pruebas con Node
