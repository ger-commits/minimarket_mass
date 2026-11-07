/* Celular como lector de la caja. Lee el código con la cámara y lo manda a index.php?r=caja_escaner_enviar.
   Qué se agrega a la venta, a qué precio y con qué stock lo decide la caja (servidor); aquí solo se envía el código. */
(function(){
'use strict';
const cfg=document.getElementById('cfg').dataset,$=s=>document.querySelector(s);
const video=$('#cam'),est=$('#est'),lista=$('#lista'),con=$('#con');
const FORMATOS=['ean_13','ean_8','upc_a','upc_e','code_128','code_39'];
let flujo=null,lector=null,tiempo=null,ocupado=false,ultimo='',visto=0,vivo=true,luzOn=false;

function msg(t,c){est.textContent=t;est.className='est'+(c?' '+c:'')}
function nube(estado){con.className='con '+estado}

async function post(cuerpo){
 try{
  const r=await fetch('index.php?r=caja_escaner_enviar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(Object.assign({c:+cfg.c,t:cfg.t},cuerpo))});
  return await r.json();
 }catch(e){return{ok:false,error:'Sin conexión con la caja.'}}
}
function pitar(){
 try{const A=window.AudioContext||window.webkitAudioContext;pitar.a=pitar.a||new A();const o=pitar.a.createOscillator(),g=pitar.a.createGain();
  o.frequency.value=1000;g.gain.value=.15;o.connect(g);g.connect(pitar.a.destination);o.start();o.stop(pitar.a.currentTime+.12)}catch(e){}
 if(navigator.vibrate)navigator.vibrate(60);
}
function anotar(codigo,ok){
 const li=document.createElement('li'),a=document.createElement('span'),b=document.createElement('small');
 a.textContent=codigo;b.textContent=ok?new Date().toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit',second:'2-digit'}):'no enviado';
 if(!ok)li.className='bad';li.append(a,b);lista.prepend(li);while(lista.children.length>6)lista.lastChild.remove();
}
function terminar(){vivo=false;nube('off');if(tiempo)clearInterval(tiempo);if(lector){try{lector.reset()}catch(e){}}if(flujo)flujo.getTracks().forEach(t=>t.stop())}

async function enviar(codigo){
 const j=await post({codigo});
 if(j.ok){pitar();anotar(codigo,true);msg('Enviado a la caja ✓  '+codigo,'ok');nube('on')}
 else{anotar(codigo,false);msg(j.error||'No se pudo enviar.','err');if(j.vencido)terminar()}
}
/* Un mismo código no se repite mientras siga frente a la cámara: hay que perderlo de vista ~0,6 s (cambiar de producto). */
function alLeer(codigo){
 const ahora=Date.now();
 if(codigo===ultimo&&ahora-visto<600){visto=ahora;return}
 ultimo=codigo;visto=ahora;enviar(codigo);
}

async function conDetector(){
 if(!('BarcodeDetector' in window))return false;
 let f=[];try{const s=await BarcodeDetector.getSupportedFormats();f=FORMATOS.filter(x=>s.includes(x))}catch(e){}
 if(!f.length)return false;
 flujo=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280},height:{ideal:720}},audio:false});
 video.srcObject=flujo;await video.play();
 const det=new BarcodeDetector({formats:f});
 tiempo=setInterval(async()=>{
  if(ocupado||video.readyState<2)return;ocupado=true;
  try{const r=await det.detect(video);if(r.length&&r[0].rawValue)alLeer(r[0].rawValue)}catch(e){}
  ocupado=false;
 },150);
 return true;
}
function cargarZXing(){
 return new Promise((ok,no)=>{
  if(window.ZXing)return ok();
  const s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
  s.onload=ok;s.onerror=()=>no(new Error('zxing'));document.head.appendChild(s);
 });
}
async function conZXing(){
 await cargarZXing();
 const Z=window.ZXing,h=new Map();
 h.set(Z.DecodeHintType.POSSIBLE_FORMATS,[Z.BarcodeFormat.EAN_13,Z.BarcodeFormat.EAN_8,Z.BarcodeFormat.UPC_A,Z.BarcodeFormat.UPC_E,Z.BarcodeFormat.CODE_128,Z.BarcodeFormat.CODE_39]);
 h.set(Z.DecodeHintType.TRY_HARDER,true);
 lector=new Z.BrowserMultiFormatReader(h,150);
 await lector.decodeFromConstraints({video:{facingMode:{ideal:'environment'}},audio:false},video,r=>{if(r)alLeer(r.getText())});
 flujo=video.srcObject;
}
function prepararLinterna(){
 const t=flujo&&flujo.getVideoTracks&&flujo.getVideoTracks()[0],cap=t&&t.getCapabilities&&t.getCapabilities();
 if(!(cap&&cap.torch))return;
 const b=$('#luz');b.hidden=false;
 b.onclick=async()=>{luzOn=!luzOn;try{await t.applyConstraints({advanced:[{torch:luzOn}]})}catch(e){}};
}
async function pantallaEncendida(){try{if(navigator.wakeLock)await navigator.wakeLock.request('screen')}catch(e){}}

async function iniciar(){
 $('#ini').hidden=true;pantallaEncendida();
 try{pitar.a=pitar.a||new (window.AudioContext||window.webkitAudioContext)()}catch(e){}   // el sonido necesita un toque previo
 if(!(window.isSecureContext&&navigator.mediaDevices&&navigator.mediaDevices.getUserMedia)){
  $('#visor').hidden=true;
  msg('Este navegador no permite la cámara en conexiones http. Abra la caja con https o digite el código abajo.','err');return;
 }
 try{
  if(!(await conDetector()))await conZXing();
  prepararLinterna();msg('Apunte al código de barras.','');
 }catch(e){
  $('#ini').hidden=false;
  msg(e&&e.name==='NotAllowedError'?'Permita el acceso a la cámara y pulse «Iniciar cámara».':'No se pudo abrir la cámara. Puede digitar el código abajo.','err');
 }
}
async function aviso(){
 if(!vivo)return;
 const j=await post({ping:1});
 if(j.ok)nube('on');else{nube('off');if(j.vencido){msg(j.error,'err');terminar()}}
}

$('#ini').onclick=iniciar;
$('#man').onsubmit=e=>{e.preventDefault();const v=$('#cod').value.trim();if(!v)return;$('#cod').value='';enviar(v)};
aviso();setInterval(aviso,8000);
window.addEventListener('pagehide',()=>{if(flujo)flujo.getTracks().forEach(t=>t.stop())});
})();
