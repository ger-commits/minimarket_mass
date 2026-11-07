/* Conecta la pantalla de almacenamiento (views/stock/app.php) con MySQL a través de index.php?r=stock_api.
   No cambia la base de datos: usa las acciones que ya existen (ingreso, merma, merma_lote, stock_minimo,
   solicitar_producto, registrar_producto, recibir_traslado). Todo lo que se ve sale de MySQL y todo lo que se guarda va a MySQL.
   Los costos unitarios de la pantalla «Costos y valorización» NO existen en la base: se guardan solo en este navegador. */
(()=>{
'use strict';
const API='index.php?r=stock_api';
const LS_COSTOS='mass_almacen_costos';
let busy=false,ultimo='',cargado=false;
const MAIN=()=>document.getElementById('main');
const num=x=>{const n=Number(x);return Number.isFinite(n)?n:0};

/* ---------- 1. se descartan los datos de ejemplo del prototipo (así nunca se confunden con los reales) ---------- */
function vaciar(){
  [P,BC,stock,MN,PCAT,T,PC].forEach(o=>{for(const k in o)delete o[k]});
  [L,K,ME,REC,SOL,LP,CATS,MARCAS,TR].forEach(a=>{a.length=0});
}
vaciar();
MAIN().innerHTML='<div class="card empty">Cargando los datos desde MySQL…</div>';

/* ---------- 2. comunicación con el servidor ---------- */
async function api(accion,datos){
  const r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':window.MASS_CSRF||'','Accept':'application/json'},body:JSON.stringify(Object.assign({accion},datos||{}))});
  const j=await r.json().catch(()=>({}));
  if(r.status===401){toast('Tu sesión de almacenero venció. Vuelve a iniciar sesión.');setTimeout(()=>{location.href='index.php?r=almacenero'},1500);throw new Error('sesion')}
  if(!r.ok||!j.ok)throw new Error(j.mensaje||'No se pudo completar la operación.');
  return j;
}
async function traer(){
  const r=await fetch(API,{headers:{'Accept':'application/json'},cache:'no-store'});
  const j=await r.json().catch(()=>({}));
  if(r.status===401){location.href='index.php?r=almacenero';throw new Error('sesion')}
  if(!r.ok||!j.ok)throw new Error(j.mensaje||'No se pudieron leer los datos.');
  return j.data;
}
const costos=()=>{try{return JSON.parse(localStorage.getItem(LS_COSTOS)||'{}')}catch(e){return {}}};

/* ---------- 3. MySQL -> estructuras que usa la pantalla ---------- */
function aplicar(d){
  vaciar();
  const t=num(d.tienda.id_tienda);st=t;T[t]=d.tienda.nombre_tienda;
  const sh=document.querySelector('.shop');if(sh&&sh.lastChild)sh.lastChild.textContent=d.tienda.nombre_tienda;
  const cat=d.catalogos||{};
  window.MASS_CATS=cat.categorias||[];window.MASS_MARCAS=cat.marcas||[];
  window.MASS_UNID={};(cat.unidades||[]).forEach(u=>{window.MASS_UNID[u.abreviatura]=u.id_unidad});
  (cat.categorias||[]).forEach(c=>CATS.push(c.nombre_categoria));
  (cat.marcas||[]).forEach(m=>MARCAS.push(m.nombre_marca));
  d.productos.forEach(p=>{
    const id=num(p.id_producto);
    P[id]=[p.nombre_producto,p.se_vende_por_peso==='S'?'kg':p.unidad,picHtml(p.nombre_producto)];
    if(p.codigo_barras)BC[id]=String(p.codigo_barras);
    let ci=CATS.indexOf(p.categoria);if(ci<0){CATS.push(p.categoria||'Otros');ci=CATS.length-1}
    PCAT[id]=ci;
    stock[t+'-'+id]=num(p.stock);MN[t+'-'+id]=num(p.stock_minimo);
  });
  const co=costos();Object.keys(P).forEach(id=>{PC[id]=num(co[id])});
  d.lotes.forEach(l=>{if(!P[l.id_producto])return;
    L.push({id:num(l.id_lote),cod:l.codigo_lote,v:String(l.fecha_vencimiento).slice(0,10),ini:num(l.cantidad_inicial),act:num(l.cantidad_actual),t,p:num(l.id_producto)});
    if(!LP.includes(num(l.id_producto)))LP.push(num(l.id_producto));
  });
  const letra={1:'B',2:'B',3:'T',4:'T',5:'M',6:'D',7:'I'};
  d.movimientos.slice().reverse().forEach(m=>{const p=num(m.id_producto);if(!P[p])return;
    const ty=num(m.id_tipo_movimiento),ref=(letra[ty]||'I')+(num(m.id_venta||m.id_traslado||m.id_merma||m.id_ingreso)||'');
    K.push({f:String(m.fecha_hora).slice(0,16),q:num(m.cantidad),s:num(m.stock_nuevo),t,p,ty,ref,det:m.observacion||''});
  });
  d.mermas.forEach(m=>{const p=num(m.id_producto);if(!P[p])return;
    ME.push({id:num(m.id_merma),f:String(m.fecha_hora).slice(0,16),t,mo:String(m.motivo||'OTRO').toUpperCase(),p,q:num(m.cantidad),obs:m.observacion||'',emp:m.empleado||''});
  });
  (d.por_recibir||[]).forEach(x=>{
    const f=String(x.fecha||'').replace(/^(\d{2})\/(\d{2})\/(\d{4})/,'$3-$2-$1');
    REC.push({id:num(x.id_traslado),f,o:x.origen,obs:x.observacion||'',l:(x.lineas||[]).map(l=>{
      const p=num(l.id_producto);
      if(!P[p]){P[p]=[l.nombre_producto,l.se_vende_por_peso==='S'?'kg':l.unidad,picHtml(l.nombre_producto)];PCAT[p]=0;stock[t+'-'+p]=0;PC[p]=0}
      return{p,q:num(l.cantidad_enviada),cod:l.codigo_lote||'',v:l.fecha_vencimiento?String(l.fecha_vencimiento).slice(0,10):'',d:num(l.id_detalle)};
    })});
  });
  (d.solicitudes||[]).forEach(s=>SOL.push({id:num(s.id),codigo:String(s.codigo),fecha:s.fecha,estado:s.estado,motivo:s.motivo||''}));
}
async function recargar(silencioso){
  try{
    const d=await traer();
    const firma=JSON.stringify(d);
    if(silencioso&&cargado&&firma===ultimo)return;
    aplicar(d);ultimo=firma;cargado=true;render();
  }catch(e){
    if(!cargado)MAIN().innerHTML='<div class="card empty">No se pudieron cargar los datos de MySQL: '+esc(e.message)+'<br><br><button class="pri" onclick="massRecargar()">Reintentar</button></div>';
    else if(!silencioso)toast(e.message);
  }
}
window.massRecargar=()=>recargar(false);

/* ---------- 4. operaciones: cada una se guarda en MySQL y luego se vuelve a leer ---------- */
async function operar(fn){
  if(busy)return;busy=true;
  try{await fn()}catch(e){if(e.message!=='sesion'){if(document.getElementById('err')&&dlg.open)fail(e.message);else toast(e.message)}}
  finally{busy=false}
}
window.save=k=>operar(async()=>{
  if(k==='ing'){
    const tx=v('b'),q=r3(+v('c')),cod=v('d'),ven=v('e'),obs=v('ob');
    if(!tx)return fail('Escribe o elige un producto.');
    if(!(q>0))return fail('Escribe una cantidad mayor que 0.');
    const p=prodByName(tx);
    if(!p){
      if(pendNew){const c=pendNew;await solicitar(c);return}
      return fail('Ese producto no está registrado. Escanea su código de barras para pedir permiso al administrador.');
    }
    const lv=LP.includes(p)||document.getElementById('lv').checked;
    if(lv&&(!cod||!ven))return fail('Este producto lleva lote: completa el código y la fecha de vencimiento.');
    const j=await api('ingreso',Object.assign({id_producto:p,cantidad:String(q),observacion:obs},lv?{codigo_lote:cod,fecha_vencimiento:ven}:{}));
    dlg.close();toast('Ingreso registrado');
  }else if(k==='mer'){
    const q=r3(+v('d'));
    if(!(q>0))return fail('Escribe una cantidad mayor que 0.');
    await api('merma',{id_producto:+v('b'),motivo:v('c'),cantidad:String(q),observacion:v('e')});
    dlg.close();view='mer';toast('Merma registrada');
  }else{
    return fail('Los traslados de salida los registra el administrador.');
  }
  await recargar(false);
});
window.mermaLote=id=>operar(async()=>{
  const l=L.find(x=>x.id===id);if(!l||l.act<=0)return;
  await api('merma_lote',{id_lote:id,observacion:'Lote '+l.cod+' vencido'});
  toast('Lote '+l.cod+' pasado a merma');await recargar(false);
});
window.setMin=(t,p,x)=>operar(async()=>{
  x=+x;if(!(x>=0))return toast('El mínimo debe ser 0 o más.');
  await api('stock_minimo',{id_producto:p,stock_minimo:String(x)});
  toast('Stock mínimo actualizado');await recargar(false);
});
window.setCost=(p,x)=>{x=+x;if(!(x>=0))return toast('El costo debe ser 0 o más.');
  const c=costos();c[p]=x;try{localStorage.setItem(LS_COSTOS,JSON.stringify(c))}catch(e){}
  PC[p]=x;render();toast('Costo actualizado en este navegador')};
window.recibirTraslado=id=>operar(async()=>{
  const t=REC.find(x=>x.id===id);if(!t)return;
  const q=t.l.map((l,i)=>r3(+document.getElementById('rc-'+id+'-'+i).value));
  if(q.some((x,i)=>!(x>=0)||x>t.l[i].q))return toast('Revisa las cantidades: no pueden ser negativas ni mayores a lo enviado.');
  const obs=document.getElementById('rob-'+id).value.trim();
  if(q.some((x,i)=>x<t.l[i].q)&&!obs)return toast('Llegó menos de lo enviado: escribe una observación.');
  const cant={};t.l.forEach((l,i)=>{cant[l.d]=String(q[i])});
  await api('recibir_traslado',{id_traslado:id,cantidades:cant,observacion:obs});
  toast('Recepción confirmada · traslado N.º '+id);await recargar(false);
});

/* ---------- 5. código que no existe: se pide permiso al administrador (no se crea directo) ---------- */
async function solicitar(c){
  const j=await api('solicitar_producto',{codigo:c});
  if(dlg.open)dlg.close();toast(j.mensaje||'Solicitud enviada al administrador');
  await recargar(false);return j;
}
window.pedirAdmin=()=>operar(async()=>{await solicitar(pend)});
window.openLink=c=>{
  pend=c;
  dlg.innerHTML=`<h2>Código no registrado</h2><p style="color:var(--mute);margin:0">El código <b>${esc(c)}</b> no está en el sistema. Para agregarlo hay que pedir permiso al administrador; cuando lo permita, lo registras en <b>Productos nuevos</b>.</p><div class="err" id="err" role="alert"></div><div class="row"><button onclick="dlg.close()">Cancelar</button><button class="pri" onclick="pedirAdmin()">Pedir permiso al administrador</button></div>`;
  if(!dlg.open)dlg.showModal();
};
window.saveNew=()=>window.pedirAdmin();
window.saveLink=()=>fail('Pide al administrador que asocie ese código al producto.');
window.openLinkExisting=()=>window.openLink(pend);
window.askNew=c=>{
  cam.hold=true;cam.nc=c;
  document.getElementById('snew').innerHTML=`<div class="card" style="padding:14px;margin-top:12px;border-color:var(--red)"><b>Código nuevo</b><div class="note" style="margin:2px 0 8px">${esc(c)} no está en la lista. Para agregarlo hay que pedir permiso al administrador.</div><div class="row"><button class="sm" onclick="skipScanNew()">Omitir</button><button class="pri sm" onclick="pedirDesdeScan()">Pedir permiso</button></div></div>`;
  scanMsg('Código nuevo: pide permiso al administrador u omítelo');
};
window.pedirDesdeScan=()=>operar(async()=>{
  const c=cam.nc,j=await api('solicitar_producto',{codigo:c});
  cam.hold=false;cam.lt=Date.now();cam.lc=c;document.getElementById('snew').innerHTML='';
  scanMsg('✔ '+(j.mensaje||'Solicitud enviada')+' · sigue escaneando');await recargar(false);
});
window.saveNuevo=id=>operar(async()=>{
  const x=SOL.find(s=>s.id===id),n=v('nnom'),pr=+v('npre');
  if(!n)return fail('Escribe el nombre del producto.');
  if(!(pr>0))return fail('Pon un precio de venta mayor que 0.');
  const cats=(window.MASS_CATS||[]),mars=(window.MASS_MARCAS||[]),uni=v('nuni')==='kg'?'kg':'und';
  const marN=v('nmarn'),d={id_solicitud:id,nombre:n,precio:String(pr),id_unidad:(window.MASS_UNID||{})[uni],se_vende_por_peso:uni==='kg'?'S':'N'};
  const cg=cats[+v('ncat')];d.id_categoria=cg?cg.id_categoria:0;
  if(marN)d.nueva_marca=marN;else{const m=mars.find(y=>y.nombre_marca===v('nmar'));d.id_marca=m?m.id_marca:0}
  await api('registrar_producto',d);
  dlg.close();toast('Producto registrado: '+n+'. Ahora registra su ingreso.');await recargar(false);
});

/* ---------- 6. formulario de ingreso: sin proveedor, guía ni costo de compra; productos nuevos solo con permiso ---------- */
const _openM=window.openM;
window.openM=function(k,t0,p0){
  _openM(k,t0,p0);
  if(k==='ing'){
    const a=document.getElementById('pv'),c=document.getElementById('cc');
    if(a)a.closest('div[style*="grid"]').remove();
    if(c)c.closest('label').remove();
  }
};
const _sug=window.sugLote;
window.sugLote=function(){
  _sug();
  const np=document.getElementById('np'),lw=document.getElementById('lvw');
  if(np)np.style.display='none';
  const tx=document.getElementById('b').value.trim();
  if(lw&&tx&&!prodByName(tx))lw.style.display='none';
};

/* ---------- 7. arranque y actualización automática (para ver al instante lo que responde el administrador) ---------- */
recargar(false);
setInterval(()=>{if(!busy&&!dlg.open&&!sdlg.open)recargar(true)},20000);
})();
