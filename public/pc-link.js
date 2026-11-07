/* Celular → almacén (versión PHP + MySQL). Reemplaza al pc-link.js del prototipo con servidor Node.
   Usa los endpoints del sistema: escaner_vincular (crea el QR) y escaner_pendientes (códigos que manda el celular).
   Es solo del almacén: no toca el escáner de la caja (caja_escaner*). */
(()=>{
'use strict';
const LS='mass_almacen_base_publica';
const CEL={token:'',ultimo:0,base:'',conectado:false,activo:false,dest:''};
let feed=[],n=0;
const csrf=()=>window.MASS_CSRF||'';
const hostLocal=h=>/^(localhost|127\.|\[::1\]$|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/i.test(h)||/\.(test|local|localhost)$/i.test(h);
const guardada=()=>{try{return localStorage.getItem(LS)||''}catch(e){return ''}};
function urlCel(){
  const b=CEL.base||guardada();
  const raiz=b?b.replace(/\/+$/,'')+'/':new URL('./',location.href).href;
  return raiz+'escaner.html?t='+encodeURIComponent(CEL.token);
}
function estado(){const u=new URL(urlCel());return{https:u.protocol==='https:',local:hostLocal(u.hostname)}}

/* ---- dirección https pública (túnel) ---- */
window.pcUsarBase=()=>{
  const i=document.getElementById('base-pub'),v=(i&&i.value||'').trim().replace(/\/+$/,'');
  let u=null;try{u=new URL(v)}catch(e){}
  if(!u||u.protocol!=='https:'||hostLocal(u.hostname)){toast('Pega la dirección https que muestra el túnel (por ejemplo https://algo.trycloudflare.com).');return}
  try{localStorage.setItem(LS,u.origin+(u.pathname==='/'?'':u.pathname.replace(/\/+$/,'')))}catch(e){}
  repintar();
};
window.pcQuitarBase=()=>{try{localStorage.removeItem(LS)}catch(e){}repintar()};

/* ---- QR ---- */
function cargarQR(){return new Promise((ok,no)=>{
  if(window.QRCode)return ok();
  const s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
  s.onload=ok;s.onerror=()=>no(new Error('qr'));document.head.appendChild(s);
})}
function bloque(){
  const e=estado(),sirve=e.https&&!e.local,conf=!!CEL.base;
  const aviso=sirve
    ?`<p class="note" style="margin:8px 0 0;color:#157f3b">✔ Dirección segura (https): el celular usa la cámara sin cable.${conf?'':' <a href="#" onclick="pcQuitarBase();return false">Cambiar</a>'}</p>`
    :`<div style="background:#fff4e5;border:1px solid #f0b35c;border-radius:10px;padding:10px;margin:8px 0;color:#6b4200;font-size:13px">
      <b>Este QR aún no sirve para un celular sin cable.</b> ${e.local?'Apunta a esta computadora (localhost / red local)':'No es https'} y la cámara del celular exige https.
      <ol style="margin:6px 0 6px 18px;padding:0"><li>En la PC ejecuta <code>iniciar_tunel_almacen.bat</code> (o <code>cloudflared tunnel --url http://localhost:8000</code>).</li>
      <li>Copia la dirección <code>https://…trycloudflare.com</code> y pégala aquí:</li></ol>
      <div style="display:flex;gap:6px"><input id="base-pub" placeholder="https://xxxx.trycloudflare.com" style="flex:1;min-width:0"><button class="sm" onclick="pcUsarBase()">Usar</button></div></div>`;
  return `${aviso}<div id="qr-celular" style="background:#fff;border-radius:12px;padding:10px;margin:10px auto;width:max-content"></div><p class="note" style="word-break:break-all;margin:0">${esc(urlCel())}</p>`;
}
async function dibujar(){
  const c=document.getElementById('qr-celular');if(!c)return;
  try{await cargarQR()}catch(e){c.innerHTML='<p style="margin:0;color:#b42318">No se cargó el generador de QR. Revisa la conexión a internet y recarga.</p>';return}
  c.innerHTML='';new QRCode(c,{text:urlCel(),width:220,height:220,correctLevel:QRCode.CorrectLevel.M});
}
function repintar(){const w=document.getElementById('pc-qr');if(!w)return;w.innerHTML=bloque();dibujar()}

/* ---- enlace con el servidor ---- */
async function vincular(){
  if(!csrf()){toast('Falta el token de seguridad: recarga la página.');throw new Error('csrf')}
  const r=await fetch('index.php?r=escaner_vincular',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf(),'Accept':'application/json'},body:'{}'});
  const j=await r.json().catch(()=>({}));
  if(!r.ok||!j.ok){toast(j.mensaje||'No se pudo crear el enlace del celular.');throw new Error('vincular')}
  CEL.token=j.data.token;CEL.ultimo=j.data.desde;CEL.base=j.data.base||'';CEL.conectado=false;
  if(!CEL.activo){CEL.activo=true;sondear()}
  paint();
}
window.pcShowQR=async()=>{
  try{await vincular()}catch(e){return}
  CEL.dest='dlg';
  dlg.innerHTML=`<h2>Conectar celular del almacén</h2><p class="note" style="margin:0">Abre la cámara normal del celular y apunta a este código. Se abre el escáner del almacén, sin iniciar sesión. Lo que escanees aparece aquí.</p><div id="pc-qr">${bloque()}</div><div class="row"><button onclick="dlg.close()">Cerrar</button></div>`;
  if(!dlg.open)dlg.showModal();
  dibujar();
};
/* botón «📱 Escáner» dentro de los formularios de ingreso y merma */
window.ingQR=async()=>{
  const e=document.getElementById('iq');if(!e)return;
  if(e.innerHTML){e.innerHTML='';return}
  try{await vincular()}catch(x){return}
  CEL.dest='inline';
  e.innerHTML=`<div id="pc-qr">${bloque()}</div><p class="note" style="margin:6px 0 0">Escanea este QR con la cámara del celular. Luego escanea el producto y se elegirá aquí.</p>`;
  dibujar();
};

/* ---- códigos que llegan del celular ---- */
async function sondear(){
  if(!CEL.activo)return;
  try{
    const r=await fetch('index.php?r=escaner_pendientes&after='+CEL.ultimo,{cache:'no-store',headers:{'Accept':'application/json'}});
    if(r.status===401){CEL.activo=false;toast('Tu sesión de almacenero venció. Vuelve a iniciar sesión.');return}
    const j=await r.json();
    if(j.ok){
      const antes=CEL.conectado;CEL.conectado=!!j.data.celular;
      if(CEL.conectado&&!antes){toast('📱 Celular conectado');if(CEL.dest==='dlg'&&dlg.open&&document.getElementById('pc-qr'))dlg.close()}
      j.data.codigos.forEach(c=>{CEL.ultimo=Math.max(CEL.ultimo,c.id);onCode(c)});
      paint();
    }
  }catch(e){}
  setTimeout(sondear,2000);
}
function onCode(c){
  beep();
  if(window.onPhoneCode&&window.onPhoneCode(c.codigo))return;
  feed.unshift({i:++n,c:c.codigo,h:c.hora});feed=feed.slice(0,30);
}
const box=document.createElement('div');
box.style.cssText='position:fixed;right:16px;bottom:16px;width:min(360px,92vw);z-index:5;display:none';
document.body.appendChild(box);
function paint(){
  feed.forEach(x=>x.p=byCode(x.c));
  box.style.display=(feed.length||CEL.activo)?'block':'none';
  box.innerHTML=`<div class="card"><div class="ch">${CEL.conectado?'📱 Celular conectado':'📱 Esperando el celular…'}<span><button class="sm" onclick="pcShowQR()">Nuevo QR</button> <button class="sm" onclick="pcClear()">Limpiar</button></span></div><div style="max-height:50vh;overflow:auto">${feed.map(x=>x.p
    ?`<div class="li"><span class="pi">${P[x.p][2]}</span><div>${esc(P[x.p][0])}<small>${esc(x.c)} · stock ${nf(stk(x.p))} ${P[x.p][1]} · ${esc(x.h)}</small></div><button class="sm" onclick="pcOpen(${x.i})">Abrir</button></div>`
    :`<div class="li"><span class="pi">❓</span><div>Código sin registrar<small>${esc(x.c)} · ${esc(x.h)}</small></div><button class="sm pri" onclick="pcOpen(${x.i})">Agregar</button></div>`).join('')}</div></div>`;
}
window.pcClear=()=>{feed=[];paint()};
window.pcOpen=i=>{const x=feed.find(y=>y.i===i);if(x)x.p?openProd(x.p,x.c):openLink(x.c)};

/* ---- botón visible en la barra superior ---- */
const who=document.querySelector('.who');
if(who){const b=document.createElement('button');b.className='sm';b.textContent='📱 Conectar celular';b.onclick=()=>window.pcShowQR();who.insertBefore(b,who.firstChild)}
})();
