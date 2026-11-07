'use strict';
const API='index.php?r=stock_api';
const TODAY=(()=>{const d=new Date();return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-')})();
const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
let CAT={},fc='all',SOL=[],CATLOG={categorias:[],marcas:[],unidades:[]},REC=[];
let T={},P={},BC={},stock={},MN={},L=[],ME=[],K=[],LP=[],PC={},TM={},storeId=0,storeName='Mi almacén';
let view='res',fp=0,fr='all',fk=0,loading=false;
let ordM='num-desc',ordK='num-desc',ultimaFirma=null;
const NAV=[['res','Resumen'],['lot','Lotes y vencimientos'],['stk','Stock'],['mer','Mermas'],['kar','Kardex'],['rec','Traslados por recibir'],['nue','Productos nuevos'],['val','Costos y valorización'],['rot','Rotación']];
const dlg=document.getElementById('dlg'),sdlg=document.getElementById('sdlg');
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const r3=x=>Math.round(Number(x)*1000)/1000;
const nf=x=>Number(x||0).toLocaleString('es-PE',{maximumFractionDigits:3});
const sol=x=>'S/ '+Number(x||0).toLocaleString('es-PE',{minimumFractionDigits:2,maximumFractionDigits:2});
const fd=s=>{if(!s)return '—';const d=String(s).slice(0,10);return d.slice(8,10)+'/'+d.slice(5,7)+'/'+d.slice(0,4)};
const days=v=>Math.round((Date.parse(String(v).slice(0,10)+'T00:00:00')-Date.parse(TODAY+'T00:00:00'))/864e5);
const ok=t=>Number(t)===storeId;
const min=(p,t=storeId)=>MN[t+'-'+p]??0;
const stk=p=>Number(stock[storeId+'-'+p]||0);
const bd=(t,c)=>`<span class="bd ${c}">${esc(t)}</span>`;
function toast(m){const e=document.getElementById('toast');e.textContent=m;e.classList.add('on');setTimeout(()=>e.classList.remove('on'),2800)}
function fail(m){const e=document.getElementById('err');if(e)e.textContent=m;else toast(m)}
async function api(data=null){
  const opt=data?{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf,'Accept':'application/json'},body:JSON.stringify(data)}:{headers:{'Accept':'application/json'},cache:'no-store'};
  const res=await fetch(API,opt);let j;try{j=await res.json()}catch(_){throw new Error('El servidor no devolvió JSON. Revisa PHP y la conexión MySQL.')}
  if(!res.ok||!j.ok)throw new Error(j.mensaje||'No se pudo completar la operación.');return j;
}
function hydrate(d){
  T={};P={};BC={};stock={};MN={};L=[];ME=[];K=[];LP=[];PC={};TM={};
  SOL=d.solicitudes||[];CATLOG=d.catalogos||{categorias:[],marcas:[],unidades:[]};REC=d.por_recibir||[];
  storeId=Number(d.tienda.id_tienda);storeName=d.tienda.nombre_tienda||'Mi almacén';
  for(const p of d.productos){const id=Number(p.id_producto);P[id]=[p.nombre_producto,p.unidad||'und','📦'];CAT[id]=p.categoria||'Sin categoría';BC[id]=p.codigo_barras||'';PC[id]=Number(p.precio_venta||0);stock[storeId+'-'+id]=Number(p.stock||0);MN[storeId+'-'+id]=Number(p.stock_minimo||0);T[storeId]=storeName}
  for(const l of d.lotes){const p=Number(l.id_producto);LP.push(p);L.push({id:Number(l.id_lote),cod:l.codigo_lote,v:String(l.fecha_vencimiento).slice(0,10),ini:Number(l.cantidad_inicial),act:Number(l.cantidad_actual),t:storeId,p})}
  const types={};for(const m of d.movimientos){const id=Number(m.id_tipo_movimiento);types[id]=[m.nombre_movimiento,m.efecto];}
  TM=types;
  for(const m of d.movimientos){const ref=m.id_ingreso?'Ingreso N.º '+m.id_ingreso:m.id_merma?'Merma N.º '+m.id_merma:m.id_traslado?'Traslado N.º '+m.id_traslado:m.id_venta?'Venta N.º '+m.id_venta:'Movimiento';K.push({id:Number(m.id_movimiento),f:m.fecha_hora,q:Number(m.cantidad),s:Number(m.stock_nuevo),t:storeId,p:Number(m.id_producto),ty:Number(m.id_tipo_movimiento),ref,obs:m.observacion||''})}
  for(const m of d.mermas){ME.push({id:Number(m.id_merma),f:m.fecha_hora,t:storeId,mo:m.motivo,p:Number(m.id_producto),q:Number(m.cantidad),obs:m.observacion||'',emp:m.empleado||''})}
}
async function load(silencioso=false){
  if(loading)return;loading=true;if(!silencioso)document.getElementById('main').innerHTML='<div class="empty">Cargando información desde MySQL…</div>';
  try{
    const j=await api();
    const firma=JSON.stringify(j.data);
    // si nada cambió, no se vuelve a dibujar (así no se mueve nada mientras miras)
    if(!(silencioso&&firma===ultimaFirma)){ultimaFirma=firma;hydrate(j.data);render()}
    marcarEstado('Actualizado '+new Date().toLocaleTimeString('es-PE'))
  }
  catch(e){if(silencioso){marcarEstado('Sin actualizar: '+e.message);return}document.getElementById('main').innerHTML=`<div class="card empty"><b>No se pudo cargar el almacén</b><p>${esc(e.message)}</p><p>Verifica que Laragon esté iniciado, que la base minimarket_mass exista y que hayas ejecutado la migración de ingresos.</p><button class="pri" onclick="load()">Reintentar</button></div>`}
  finally{loading=false}
}
const NAVMAP={res:vRes,lot:vLot,stk:vStock,mer:vMer,kar:vKar,rec:vRec,nue:vNue,val:vVal,rot:vRot};
function go(v){view=v;fp=0;fr='all';fk=0;fc='all';render()}
function render(){
  const cuenta={rec:REC.length,nue:SOL.filter(x=>x.estado!=='RECHAZADA').length};
  document.getElementById('nav').innerHTML=NAV.map(n=>`<button class="nv" onclick="go('${n[0]}')"${view===n[0]?' aria-current="page"':''}>${n[1]}${cuenta[n[0]]?` <span class="bd warn">${cuenta[n[0]]}</span>`:''}</button>`).join('');
  const name=NAV.find(n=>n[0]===view)?.[1]||'Resumen';document.getElementById('crumb').innerHTML='MASS / <b>'+name+'</b>';
  // conserva la posición de la página y de cada tabla al actualizar
  const main=document.getElementById('main');
  const tablas=[...main.querySelectorAll('.tb')].map(e=>[e.scrollTop,e.scrollLeft]);
  const y=window.scrollY;
  main.innerHTML=(NAVMAP[view]||vRes)();
  main.querySelectorAll('.tb').forEach((e,i)=>{if(tablas[i]){e.scrollTop=tablas[i][0];e.scrollLeft=tablas[i][1]}});
  window.scrollTo(0,y);
}
const head=(t,b='')=>`<div class="ph"><div><h1>${t}</h1><p>${esc(storeName)} · Inventario conectado a MySQL</p></div>${b}</div>`;
const bI='<button class="pri" onclick="openM(\'ing\')">+ Registrar ingreso</button>';
const prodItems=()=>[[0,'Todos los productos'],...Object.keys(P).map(k=>[k,P[k][0]])];
const fS=(v,items,h)=>`<select onchange="${h};render()">${items.map(i=>`<option value="${i[0]}"${String(i[0])===String(v)?' selected':''}>${esc(i[1])}</option>`).join('')}</select>`;
const OPC_ORDEN=[['num-desc','Número: mayor a menor'],['num-asc','Número: menor a mayor'],['fecha-desc','Fecha: más reciente'],['fecha-asc','Fecha: más antigua'],['prod','Producto: A a Z'],['cant-desc','Cantidad: mayor a menor']];
function ordenar(lista,criterio){
  const cmp={
    'num-desc':(a,b)=>b.id-a.id,
    'num-asc':(a,b)=>a.id-b.id,
    'fecha-desc':(a,b)=>String(b.f).localeCompare(String(a.f))||b.id-a.id,
    'fecha-asc':(a,b)=>String(a.f).localeCompare(String(b.f))||a.id-b.id,
    'prod':(a,b)=>(P[a.p]?.[0]||'').localeCompare(P[b.p]?.[0]||'','es')||b.id-a.id,
    'cant-desc':(a,b)=>Math.abs(b.q)-Math.abs(a.q)||b.id-a.id
  }[criterio]||((a,b)=>b.id-a.id);
  return [...lista].sort(cmp);
}
const tbl=(h,rows)=>rows.length?`<div class="card tb"><table><thead><tr>${h.map(x=>`<th class="${x[1]||''}">${x[0]}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table></div>`:'<div class="card empty">No hay registros con estos filtros.</div>';
function card(t,i,n,s){return `<div class="card st"><div>${t}<span class="ic">${i}</span></div><b>${n}</b><small>${s}</small></div>`}
function vRes(){
 const ps=Object.keys(P).map(Number),low=ps.filter(p=>stk(p)<=min(p)),near=L.filter(l=>l.act>0&&days(l.v)<=30),mov=K.slice(0,7);
 const total=ps.reduce((a,p)=>a+stk(p),0),recent=mov.map(k=>`<div class="li"><span class="pi">📦</span><div>${esc(P[k.p]?.[0]||'Producto')}<small>${esc(TM[k.ty]?.[0]||'Movimiento')} · ${fd(k.f)}</small></div><b>${TM[k.ty]?.[1]==='E'?'+':'−'}${nf(k.q)}</b></div>`).join('');
 return head('Resumen general',bI)+`<div class="cards">${card('Productos activos','📦',ps.length,'Catálogo de la tienda')}${card('Unidades en stock','📊',nf(total),'Existencias actuales')}${card('Stock bajo','⚠️',low.length,'Productos por reponer')}${card('Lotes por vencer','⏳',near.length,'Vencidos o vencen en 30 días')}</div>${alerts()}<div class="two"><div class="card"><div class="ch">Productos por reponer</div>${low.length?low.slice(0,8).map(p=>`<div class="li"><span class="pi">📦</span><div>${esc(P[p][0])}<small>${nf(stk(p))} ${P[p][1]} · mínimo ${nf(min(p))}</small></div><button class="sm" onclick="openM('ing',${p})">Ingresar</button></div>`).join(''):'<div class="empty">Todo el stock está sobre el mínimo.</div>'}</div><div class="card"><div class="ch">Actividad reciente<span>Movimientos registrados</span></div>${recent||'<div class="empty">Aún no hay movimientos.</div>'}</div></div>`;
}
function alerts(){const a=L.filter(l=>l.act>0&&days(l.v)<=30).sort((x,y)=>x.v.localeCompare(y.v));if(!a.length)return '';return `<div class="card" style="margin-bottom:20px"><div class="ch">Alertas de vencimiento<span>Lotes vencidos o que vencen en 30 días</span></div>${a.slice(0,10).map(l=>{const d=days(l.v);return `<div class="li"><span class="pi">📦</span><div>${esc(P[l.p]?.[0]||'Producto')}<small>Lote ${esc(l.cod)} · quedan ${nf(l.act)} ${P[l.p]?.[1]||''}</small></div>${d<0?bd('Vencido hace '+(-d)+' d','bad')+` <button class="sm" onclick="mermaLote(${l.id})">Pasar a merma</button>`:bd(d===0?'Vence hoy':'Vence en '+d+' d','warn')}</div>`}).join('')}</div>`}
// Filtro por categoría con botones (chips)
const cats=()=>[...new Set(Object.keys(P).map(p=>CAT[p]||'Sin categoría'))].sort((a,b)=>a.localeCompare(b,'es'));
function chipsCat(){
  const lista=[['all','Todas']].concat(cats().map(c=>[c,c]));
  return `<div class="chips" role="group" aria-label="Categorías">${lista.map(x=>`<button type="button" class="chip" aria-pressed="${String(x[0])===String(fc)}" onclick="setCat('${esc(x[0])}')">${esc(x[1])}</button>`).join('')}</div>`;
}
function setCat(c){fc=c;render()}
function enCat(p){return fc==='all'||(CAT[p]||'Sin categoría')===fc}
function vLot(){const ranges=[['all','Todos los vencimientos'],['venc','Vencidos'],['7','Vencen en 7 días o menos'],['30','Vencen en 8 a 30 días'],['mas','Vencen en más de 30 días']];const inside=l=>{const d=days(l.v);return fr==='venc'?d<0:fr==='7'?d>=0&&d<=7:fr==='30'?d>7&&d<=30:fr==='mas'?d>30:true};const rows=L.filter(l=>(!fp||l.p===fp)&&inside(l)&&enCat(l.p)).sort((a,b)=>a.v.localeCompare(b.v)).map(l=>{const d=days(l.v),state=l.act<=0?['Agotado','mute']:d<0?['Vencido','bad']:d<=30?['Por vencer','warn']:['Vigente','ok'];return `<tr><td>${esc(P[l.p]?.[0]||'')}</td><td>${esc(l.cod)}</td><td>${fd(l.v)}</td><td class="n">${d}</td><td class="n">${nf(l.ini)}</td><td class="n">${nf(l.act)}</td><td>${bd(...state)}</td><td>${d<0&&l.act>0?`<button class="sm" onclick="mermaLote(${l.id})">Registrar merma</button>`:''}</td></tr>`});return head('Lotes y vencimientos',bI)+chipsCat()+`<div class="flt">${fS(fp,prodItems(),'fp=+this.value')}${fS(fr,ranges,'fr=this.value')}</div>`+tbl([['Producto'],['Lote'],['Vence'],['Días','n'],['Inicial','n'],['Queda','n'],['Estado'],['Acción']],rows)+`<p class="note">Los ingresos con código y fecha de vencimiento quedan asociados al lote y se actualizan en MySQL.</p>`}
function vStock(){const ps=Object.keys(P).map(Number).filter(p=>(!fp||p===fp)&&enCat(p));const rows=ps.map(p=>{const q=stk(p),m=min(p);return `<tr><td>📦 ${esc(P[p][0])}<small>${BC[p]?'Código '+esc(BC[p]):'Sin código de barras'}</small></td><td class="n">${nf(q)} ${P[p][1]}</td><td class="n"><input class="mn" type="number" min="0" step="0.001" value="${m}" onchange="setMin(${p},this.value)" aria-label="Stock mínimo"></td><td>${q<=m?bd('Reponer','warn'):bd('Normal','ok')}</td><td><button class="sm" onclick="openM('ing',${p})">Ingreso</button> <button class="sm" onclick="openM('mer',${p})">Merma</button></td></tr>`});return head('Stock',bI)+chipsCat()+`<div class="flt">${fS(fp,prodItems(),'fp=+this.value')}<button onclick="scanGlobal()">📷 Escanear</button></div>`+tbl([['Producto'],['Stock actual','n'],['Mínimo editable','n'],['Situación'],['Acciones']],rows)}
function vMer(){const c={VENCIDO:'bad',DAÑADO:'warn',ROBO:'bad',OTRO:'mute'};const rows=ordenar(ME.filter(m=>!fp||m.p===fp),ordM).map(m=>`<tr><td>N.º ${m.id}</td><td>${fd(m.f)}</td><td>${bd(m.mo[0]+m.mo.slice(1).toLowerCase(),c[m.mo]||'mute')}</td><td>${esc(P[m.p]?.[0]||'')}</td><td class="n">${nf(m.q)} ${P[m.p]?.[1]||''}</td><td class="obs">${esc(m.obs)}</td><td>${esc(m.emp)}</td></tr>`);return head('Mermas',`<button class="pri" onclick="openM('mer')">+ Registrar merma</button>`)+`<div class="flt">${fS(fp,prodItems(),'fp=+this.value')}${fS(ordM,OPC_ORDEN,'ordM=this.value')}</div>`+tbl([['Merma'],['Fecha'],['Motivo'],['Producto'],['Cantidad','n'],['Observación','obs'],['Responsable']],rows)}
function vKar(){const types=[[0,'Todos los movimientos'],...Object.keys(TM).map(k=>[k,TM[k][0]])];const rows=ordenar(K.filter(k=>(!fp||k.p===fp)&&(!fk||k.ty===fk)&&enCat(k.p)),ordK).map(k=>{const entry=TM[k.ty]?.[1]==='E';return `<tr><td>${k.id}</td><td>${fd(k.f)} ${String(k.f).slice(11,16)}</td><td>${esc(P[k.p]?.[0]||'')}</td><td>${bd(TM[k.ty]?.[0]||'Movimiento',entry?'ok':'bad')}</td><td class="n">${entry?'+':'−'}${nf(k.q)}</td><td class="n">${nf(k.s)}</td><td>${esc(k.ref)}</td><td class="obs">${esc(k.obs)}</td></tr>`});return head('Kardex')+chipsCat()+`<div class="flt">${fS(fp,prodItems(),'fp=+this.value')}${fS(fk,types,'fk=+this.value')}${fS(ordK,OPC_ORDEN,'ordK=this.value')}</div>`+tbl([['N.º'],['Fecha'],['Producto'],['Movimiento'],['Cantidad','n'],['Saldo','n'],['Origen'],['Observación','obs']],rows)}
function vVal(){const ps=Object.keys(P).map(Number),rows=ps.map(p=>[p,stk(p),stk(p)*PC[p]]).sort((a,b)=>b[2]-a[2]);const total=rows.reduce((s,x)=>s+x[2],0);return head('Costos y valorización')+`<div class="cards">${card('Valorización referencial','💰',sol(total),'Calculada con el precio de venta del catálogo; no es costo de compra.')}${card('Productos','📦',ps.length,'Productos activos')}</div>`+tbl([['Producto'],['Stock','n'],['Precio de venta','n'],['Valor referencial','n']],rows.map(([p,q,v])=>`<tr><td>${esc(P[p][0])}</td><td class="n">${nf(q)}</td><td class="n">${sol(PC[p])}</td><td class="n">${sol(v)}</td></tr>`))}
function vRot(){const ps=Object.keys(P).map(Number);return head('Rotación de productos')+`<div class="card empty">La rotación basada en ventas se habilitará cuando se conecte el historial de ventas al reporte de almacén. El stock mostrado sí proviene de MySQL.</div>`+tbl([['Producto'],['Stock actual','n']],ps.map(p=>`<tr><td>${esc(P[p][0])}</td><td class="n">${nf(stk(p))} ${P[p][1]}</td></tr>`))}

// ---- Productos nuevos: el administrador permite el código y luego el almacén registra los datos ----
function vNue(){
  const est={PENDIENTE:['Esperando al administrador','warn'],APROBADA:['Permitido: falta registrarlo','ok'],RECHAZADA:['Rechazado','bad']};
  const rows=SOL.map(x=>`<tr><td><b>${esc(x.codigo)}</b></td><td>${esc(x.fecha)}</td><td>${bd(est[x.estado]?.[0]||x.estado,est[x.estado]?.[1]||'mute')}${x.estado==='RECHAZADA'&&x.motivo?`<small>${esc(x.motivo)}</small>`:''}</td><td>${x.estado==='APROBADA'?`<button class="pri sm" onclick="openNuevo(${Number(x.id)})">Registrar producto</button>`:''}</td></tr>`);
  return head('Productos nuevos')+`<div class="card" style="margin-bottom:16px"><div class="ch">¿Cómo funciona?</div><p class="note" style="margin:8px 0 0">Escanea un código que no existe: se envía una solicitud al administrador de ${esc(storeName)}. Cuando la permita, aquí aparece el botón <b>Registrar producto</b> para que llenes sus datos.</p></div>`+tbl([['Código'],['Fecha'],['Estado'],['']],rows);
}
function openNuevo(id){
  const x=SOL.find(s=>Number(s.id)===Number(id));if(!x||x.estado!=='APROBADA')return;
  const o=(arr,v,t)=>arr.map(a=>`<option value="${a[v]}">${esc(a[t])}</option>`).join('');
  dlg.innerHTML=`<h2>Registrar producto nuevo</h2><p class="note" style="margin:0">Código de barras autorizado: <b>${esc(x.codigo)}</b></p>
  <label>Nombre del producto<input id="nnom" maxlength="100"></label>
  <label>Categoría<select id="ncat">${o(CATLOG.categorias,'id_categoria','nombre_categoria')}</select></label>
  <label>…o categoría nueva <small>(opcional)</small><input id="ncatn" maxlength="50"></label>
  <label>Marca<select id="nmar">${o(CATLOG.marcas,'id_marca','nombre_marca')}</select></label>
  <label>…o marca nueva <small>(opcional)</small><input id="nmarn" maxlength="50"></label>
  <label>Unidad<select id="nuni">${o(CATLOG.unidades,'id_unidad','abreviatura')}</select></label>
  <label>Precio de venta (S/)<input id="npre" type="number" min="0.01" step="0.01" inputmode="decimal"></label>
  <label>Se vende por peso<select id="npes"><option value="N">No</option><option value="S">Sí</option></select></label>
  <div class="err" id="err" role="alert"></div>
  <div class="row"><button onclick="dlg.close()">Cancelar</button><button class="pri" onclick="saveNuevo(${Number(id)})">Registrar producto</button></div>`;
  dlg.showModal();
}
async function saveNuevo(id){
  const v=k=>document.getElementById(k).value;
  try{
    const r=await api({accion:'registrar_producto',id_solicitud:id,nombre:v('nnom'),id_categoria:v('ncat'),nueva_categoria:v('ncatn'),id_marca:v('nmar'),nueva_marca:v('nmarn'),id_unidad:v('nuni'),precio:v('npre'),se_vende_por_peso:v('npes')});
    dlg.close();toast(r.mensaje);await load();
  }catch(e){fail(e.message)}
}

// ---- Traslados por recibir: el almacén cuenta lo que llegó y confirma ----
function vRec(){
  if(!REC.length)return head('Traslados por recibir')+'<div class="card empty">No hay traslados en camino hacia tu tienda.</div>';
  return head('Traslados por recibir')+REC.map(t=>{
    const rows=t.lineas.map(l=>`<tr><td>📦 ${esc(l.nombre_producto)}</td><td>${l.codigo_lote?esc(l.codigo_lote)+'<small>Vence '+fd(l.fecha_vencimiento)+'</small>':'—'}</td><td class="n">${nf(l.cantidad_enviada)} ${esc(l.unidad)}</td><td><input class="rcv" data-t="${Number(t.id_traslado)}" id="rc-${Number(l.id_detalle)}" type="number" min="0" max="${Number(l.cantidad_enviada)}" step="${l.se_vende_por_peso==='S'?'0.001':'1'}" value="${Number(l.cantidad_enviada)}" style="width:110px"></td></tr>`).join('');
    return `<div class="card" style="margin-bottom:16px"><div class="ch">Traslado N.º ${Number(t.id_traslado)} · desde ${esc(t.origen)}<span>${esc(t.fecha)}</span></div>
    ${t.observacion?`<p class="note" style="margin:6px 0">${esc(t.observacion)}</p>`:''}
    <div class="tb"><table><thead><tr><th>Producto</th><th>Lote</th><th class="n">Enviado</th><th>Recibido</th></tr></thead><tbody>${rows}</tbody></table></div>
    <label>Observación <small>(obligatoria si llegó menos de lo enviado)</small><input id="rob-${Number(t.id_traslado)}" maxlength="200"></label>
    <div class="row"><button class="pri" onclick="recibirTraslado(${Number(t.id_traslado)})">Confirmar recepción</button></div></div>`;
  }).join('');
}
async function recibirTraslado(id){
  const t=REC.find(x=>Number(x.id_traslado)===Number(id));if(!t)return;
  const cantidades={};t.lineas.forEach(l=>{cantidades[l.id_detalle]=document.getElementById('rc-'+l.id_detalle).value});
  try{
    const r=await api({accion:'recibir_traslado',id_traslado:id,cantidades,observacion:document.getElementById('rob-'+id).value});
    toast(r.mensaje);await load();
  }catch(e){toast(e.message)}
}
function openM(kind,preset=0){
 const options=Object.keys(P).map(p=>`<option value="${p}" ${Number(p)===Number(preset)?'selected':''}>${esc(P[p][0])} (${esc(P[p][1])})</option>`).join('');
 if(kind==='ing')dlg.innerHTML=`<h2>Registrar ingreso</h2><p class="note" style="margin:0">La operación actualizará stock, lote (si corresponde) y Kardex en MySQL.</p><label>Producto<select id="fprod">${options}</select></label><label>Cantidad<input id="fcant" type="number" min="0.001" step="0.001" placeholder="Ej. 12"></label><label>Código de lote <small>(opcional; obligatorio si el producto ya se controla por lotes)</small><input id="flote" maxlength="20" placeholder="Código del empaque"></label><label>Fecha de vencimiento <small>(si ingresas un lote)</small><input id="fvence" type="date"></label><label>Observación <small>(opcional)</small><input id="fobs" maxlength="200" placeholder="Proveedor, documento, detalle…"></label><div class="err" id="err" role="alert"></div><div class="row"><button onclick="dlg.close()">Cancelar</button><button class="pri" onclick="save('ing')">Guardar ingreso</button></div>`;
 if(kind==='mer')dlg.innerHTML=`<h2>Registrar merma</h2><p class="note" style="margin:0">La cantidad se descontará del stock y quedará registrada en el Kardex.</p><label>Producto<select id="fprod">${options}</select></label><label>Motivo<select id="fmotivo"><option value="VENCIDO">Vencido</option><option value="DAÑADO">Dañado</option><option value="ROBO">Robo</option><option value="OTRO">Otro</option></select></label><label>Cantidad<input id="fcant" type="number" min="0.001" step="0.001" placeholder="Ej. 2"></label><label>Observación <small>(opcional)</small><input id="fobs" maxlength="200" placeholder="Detalle de la merma"></label><div class="err" id="err" role="alert"></div><div class="row"><button onclick="dlg.close()">Cancelar</button><button class="pri" onclick="save('mer')">Guardar merma</button></div>`;
 dlg.showModal();
}
async function save(kind){const id=Number(document.getElementById('fprod')?.value),q=document.getElementById('fcant')?.value,obs=document.getElementById('fobs')?.value||'';if(!id||!q||Number(q)<=0)return fail('Selecciona un producto e ingresa una cantidad mayor que cero.');const data={accion:kind==='ing'?'ingreso':'merma',id_producto:id,cantidad:q,observacion:obs};if(kind==='ing'){data.codigo_lote=document.getElementById('flote').value.trim();data.fecha_vencimiento=document.getElementById('fvence').value||''}else data.motivo=document.getElementById('fmotivo').value;const btn=dlg.querySelector('button.pri');btn.disabled=true;btn.textContent='Guardando…';try{const r=await api(data);dlg.close();toast(r.mensaje);await load()}catch(e){fail(e.message);btn.disabled=false;btn.textContent=kind==='ing'?'Guardar ingreso':'Guardar merma'}}
async function setMin(p,x){try{const r=await api({accion:'stock_minimo',id_producto:p,stock_minimo:x});toast(r.mensaje);await load()}catch(e){toast(e.message);await load()}}
async function mermaLote(id){const l=L.find(x=>x.id===id);if(!l||l.act<=0)return;if(!confirm(`¿Registrar como merma las ${nf(l.act)} unidades del lote ${l.cod}?`))return;try{const r=await api({accion:'merma_lote',id_lote:id,observacion:'Baja por vencimiento del lote '+l.cod});toast(r.mensaje);await load()}catch(e){toast(e.message)}}
function openProd(p,c){const l=L.filter(x=>x.p===p&&x.act>0).sort((a,b)=>a.v.localeCompare(b.v))[0];dlg.innerHTML=`<h2>📦 ${esc(P[p][0])}</h2><p class="note">Código de barras: ${esc(c||BC[p]||'Sin código')}</p><div class="card" style="margin-top:12px"><div class="li"><div>Stock actual</div><b>${nf(stk(p))} ${P[p][1]}</b></div><div class="li"><div>Stock mínimo</div><b>${nf(min(p))} ${P[p][1]}</b></div></div>${l?`<p class="note">Vence primero: lote ${esc(l.cod)} · ${fd(l.v)}</p>`:''}<div class="pact"><button class="sm" onclick="dlg.close();openM('ing',${p})">Ingreso</button><button class="sm" onclick="dlg.close();openM('mer',${p})">Merma</button><button class="sm" onclick="dlg.close()">Cerrar</button></div>`;dlg.showModal()}
// Escáner de códigos. Tres formas de leer el código, de la más directa a la de respaldo:
// 1) Cámara en vivo (solo si el navegador la permite: HTTPS o localhost).
// 2) Foto del código con la cámara del celular (funciona en HTTP dentro de la red local).
// 3) Campo manual o lector USB.
let stream=null,detector=null,scanTimer=null,zxingReader=null;
function scanGlobal(){
  document.getElementById('sman').value='';
  document.getElementById('smsg').textContent='Apunta la cámara al código, toma una foto del código o escribe el número.';
  sdlg.showModal();
  startCamera();
}
let zxingControls=null;
async function startCamera(){
  const v=document.getElementById('svid');
  const msg=document.getElementById('smsg');
  if(!navigator.mediaDevices?.getUserMedia){
    v.style.display='none';
    msg.textContent='La cámara en vivo necesita una conexión segura (HTTPS). Mientras tanto usa "Tomar foto del código" o escribe el número.';
    return;
  }
  try{
    stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
    if(!sdlg.open){stopCamera();return}
    v.style.display='';
    v.srcObject=stream;
    await v.play();
    if('BarcodeDetector'in window){
      detector=new BarcodeDetector({formats:['ean_13','ean_8','upc_a','upc_e','code_128','code_39']});
      const loop=async()=>{
        if(!stream)return;
        try{
          const codes=await detector.detect(v);
          if(codes.length){
            if(enviarCodigo(codes[0].rawValue))return;
            await new Promise(r=>setTimeout(r,1200));
          }
        }catch(_){}
        scanTimer=setTimeout(loop,180);
      };
      loop();
    }else if(window.ZXing){
      // Navegadores sin BarcodeDetector: ZXing lee los códigos directamente del video
      zxingReader=zxingReader||new ZXing.BrowserMultiFormatReader();
      zxingControls=await zxingReader.decodeFromVideoElement(v,(res)=>{
        if(res&&sdlg.open)enviarCodigo(res.getText());
      });
    }else{
      msg.textContent='El lector de códigos no se cargó. Escribe el número o usa "Tomar foto del código".';
    }
  }catch(_){
    v.style.display='none';
    msg.textContent='No se pudo abrir la cámara. Revisa el permiso de cámara del navegador o escribe el número.';
  }
}
function stopCamera(){
  if(scanTimer)clearTimeout(scanTimer);
  scanTimer=null;
  if(zxingControls&&typeof zxingControls.stop==='function')zxingControls.stop();
  zxingControls=null;
  if(stream){stream.getTracks().forEach(t=>t.stop());stream=null}
  const v=document.getElementById('svid');
  if(v)v.srcObject=null;
}
sdlg.addEventListener('close',stopCamera);
// Lee una foto tomada con la cámara del celular y decodifica el código con ZXing
async function leerFoto(input){
  const f=input.files&&input.files[0];
  input.value='';
  if(!f)return;
  const msg=document.getElementById('smsg');
  if(!window.ZXing){msg.textContent='No se cargó el lector de códigos. Revisa tu conexión a internet o escribe el número.';return}
  msg.textContent='Leyendo la foto…';
  const url=URL.createObjectURL(f);
  try{
    zxingReader=zxingReader||new ZXing.BrowserMultiFormatReader();
    const res=await zxingReader.decodeFromImageUrl(url);
    const texto=typeof res.getText==='function'?res.getText():res.text;
    enviarCodigo(texto);
  }catch(_){
    msg.textContent='No se pudo leer el código de la foto. Acércate, enfoca bien y con buena luz, o escribe el número.';
  }finally{
    URL.revokeObjectURL(url);
  }
}
// Busca el código en el catálogo. Si existe, abre el ingreso con el producto ya elegido.
let ultimoAvisoCodigo={c:'',t:0};
// Código que no está en el catálogo: se envía la solicitud al administrador de ESTA tienda (una sola vez por código)
async function avisarCodigoNuevo(c){
  const msg=document.getElementById('smsg');
  if(ultimoAvisoCodigo.c===c&&Date.now()-ultimoAvisoCodigo.t<20000)return;
  ultimoAvisoCodigo={c,t:Date.now()};
  msg.textContent='Producto no registrado. Enviando solicitud al administrador…';
  try{
    const r=await api({accion:'solicitar_producto',codigo:c});
    msg.textContent=r.mensaje;
    await load(true);
  }catch(e){msg.textContent=e.message}
}
function enviarCodigo(raw){
  const c=String(raw||'').trim();
  if(!c){document.getElementById('smsg').textContent='Escribe o escanea un código.';return false}
  const p=Object.keys(BC).find(k=>String(BC[k])===c);
  if(p){sdlg.close();openIngresoEscaneado(Number(p),c);return true}
  avisarCodigoNuevo(c);
  return false;
}
function manualScan(){enviarCodigo(document.getElementById('sman').value)}
// Ingreso directo para el producto escaneado: muestra nombre y código en grande y pide solo la cantidad.
// Los campos de lote y vencimiento aparecen solo si el producto ya se controla por lotes en esta tienda.
function openIngresoEscaneado(p,c){
  const codigo=c||BC[p]||'Sin código';
  const usaLotes=L.some(l=>l.p===p);
  const campoLote=usaLotes
    ?`<label>Código de lote <small>(obligatorio: este producto se controla por lotes)</small><input id="flote" maxlength="20" placeholder="Código del empaque"></label><label>Fecha de vencimiento<input id="fvence" type="date"></label>`
    :`<input type="hidden" id="flote" value=""><input type="hidden" id="fvence" value="">`;
  dlg.innerHTML=`<h2>Registrar ingreso</h2>
  <div class="card" style="margin:10px 0 4px;padding:12px">
    <div class="note" style="margin:0">Producto escaneado</div>
    <div style="font-size:20px;font-weight:700;margin-top:4px">${esc(P[p][0])}</div>
    <div style="font-size:16px;margin-top:6px">Código de barras: <b style="letter-spacing:1px">${esc(codigo)}</b></div>
    <div class="note" style="margin:6px 0 0">Stock actual: ${nf(stk(p))} ${esc(P[p][1])}</div>
  </div>
  <input type="hidden" id="fprod" value="${p}">
  <label>Cantidad que entra (${esc(P[p][1])})<input id="fcant" type="number" min="0.001" step="0.001" placeholder="Ej. 12" inputmode="decimal"></label>
  ${campoLote}
  <label>Observación <small>(opcional)</small><input id="fobs" maxlength="200" placeholder="Proveedor, documento, detalle…"></label>
  <div class="err" id="err" role="alert"></div>
  <div class="row"><button onclick="dlg.close()">Cancelar</button><button class="pri" onclick="save('ing')">Guardar ingreso</button></div>`;
  dlg.showModal();
}
// Se actualiza sola: cada 5 s y al volver a la pestaña. No se refresca mientras hay un formulario abierto.
function marcarEstado(t){ const e=document.getElementById('estado-refresco'); if(e) e.textContent=t; }
function refrescar(){
  if(dlg.open || (sdlg && sdlg.open) || (document.activeElement && document.activeElement.closest && document.activeElement.closest('#main') && /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName))) { marcarEstado('Pausado mientras tienes un formulario abierto'); return; }
  load(true);
}
async function init(){
  const e=document.createElement('div'); e.id='estado-refresco';
  e.style.cssText='position:fixed;bottom:8px;right:12px;font:12px system-ui,sans-serif;color:#666;background:#fffffff0;padding:3px 8px;border-radius:6px;z-index:50';
  document.body.appendChild(e);
  await load(); setInterval(refrescar, 5000);
  document.addEventListener('visibilitychange', ()=>{ if(!document.hidden) refrescar(); });
}
init();
// ===== Avisos para el almacén (por ejemplo, producto eliminado por el administrador) =====
const AVISOS={ultimo:-1};
function mostrarAvisoAlmacen(texto){
  let caja=document.getElementById('avisos-almacen');
  if(!caja){
    caja=document.createElement('div');
    caja.id='avisos-almacen';
    caja.style.cssText='position:fixed;top:78px;right:14px;width:min(380px,92vw);z-index:70;display:flex;flex-direction:column;gap:8px';
    document.body.appendChild(caja);
  }
  const item=document.createElement('div');
  item.style.cssText='background:#7f1d1d;color:#fff;padding:12px 14px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.25);font:14px system-ui,sans-serif;display:flex;gap:10px;align-items:flex-start';
  item.innerHTML='<span style="flex:1">🔔 '+esc(texto)+'</span><button style="background:transparent;color:#fff;border:0;font-size:16px;cursor:pointer" aria-label="Cerrar">✕</button>';
  item.querySelector('button').onclick=()=>item.remove();
  caja.prepend(item);
}
async function sondearAvisosAlmacen(){
  try{
    const r=await fetch('index.php?r=stock_notificaciones&after='+AVISOS.ultimo,{cache:'no-store',headers:{'Accept':'application/json'}});
    const j=await r.json();
    if(j.ok){
      if(AVISOS.ultimo<0){
        AVISOS.ultimo=j.data.ultimo;
      }else{
        j.data.avisos.forEach(a=>{AVISOS.ultimo=Math.max(AVISOS.ultimo,a.id);mostrarAvisoAlmacen(a.mensaje)});
        AVISOS.ultimo=Math.max(AVISOS.ultimo,j.data.ultimo);
      }
    }
  }catch(_){}
  setTimeout(sondearAvisosAlmacen,5000);
}
sondearAvisosAlmacen();

// ===== Escáner de celular: el celular lee el código y el código llega al administrador (panel → Escaneos del celular) =====
const CEL={token:'',ultimo:0,activo:false,conectado:false,oculto:false,base:''};
// Dirección que usará el celular. Prioridad: ALMACEN_ESCANER_URL_BASE del .env > la que se pegó en este formulario > la de esta pestaña.
const BASE_LS='mass_almacen_base_publica';
const hostLocal=h=>/^(localhost|127\.|\[::1\]$|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/i.test(h)||/\.(test|local|localhost)$/i.test(h);
function baseGuardada(){try{return localStorage.getItem(BASE_LS)||''}catch(_){return ''}}
function urlCelular(){
  const b=CEL.base||baseGuardada();
  const raiz=b?b.replace(/\/+$/,'')+'/':new URL('./',location.href).href;
  return raiz+'escaner.html?t='+encodeURIComponent(CEL.token);
}
function estadoUrlCelular(){const u=new URL(urlCelular());return{https:u.protocol==='https:',local:hostLocal(u.hostname)}}
function usarBasePublica(){
  const v=(document.getElementById('base-pub').value||'').trim().replace(/\/+$/,'');
  let u;try{u=new URL(v)}catch(_){u=null}
  if(!u||u.protocol!=='https:'||hostLocal(u.hostname)){toast('Pega la dirección https que muestra el túnel (por ejemplo https://algo.trycloudflare.com).');return}
  try{localStorage.setItem(BASE_LS,u.origin+(u.pathname==='/'?'':u.pathname.replace(/\/+$/,'')))}catch(_){}
  mostrarQR();
}
function quitarBasePublica(){try{localStorage.removeItem(BASE_LS)}catch(_){}mostrarQR()}
async function conectarCelular(){
  try{
    const r=await fetch('index.php?r=escaner_vincular',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf,'Accept':'application/json'},body:'{}'});
    const j=await r.json();
    if(!r.ok||!j.ok)throw new Error(j.mensaje||'No se pudo crear el enlace del celular.');
    CEL.token=j.data.token;CEL.ultimo=j.data.desde;CEL.base=j.data.base||'';CEL.oculto=false;
    mostrarQR();
    if(!CEL.activo){CEL.activo=true;sondearCelular()}
    pintarFeed();
  }catch(e){toast(e.message)}
}
function mostrarQR(){
  const est=estadoUrlCelular(),sirve=est.https&&!est.local,conf=!!CEL.base;
  const aviso=sirve?`<p class="note" style="margin:0;color:#157f3b">✔ Dirección segura (https): el celular puede usar la cámara sin cable.${conf?'':` <a href="#" onclick="quitarBasePublica();return false">Cambiar</a>`}</p>`
   :`<div style="background:#fff4e5;border:1px solid #f0b35c;border-radius:10px;padding:10px;margin:0 0 8px;color:#6b4200;font-size:13px">
      <b>Este QR todavía no sirve para un celular sin cable.</b> ${est.local?'Apunta a esta computadora (localhost / red local)':'No es https'}, y la cámara del celular exige https.
      <ol style="margin:6px 0 6px 18px;padding:0"><li>En la PC ejecuta <code>iniciar_tunel_almacen.bat</code> (o <code>cloudflared tunnel --url http://localhost:8000</code>).</li>
      <li>Copia la dirección <code>https://…trycloudflare.com</code> que aparece y pégala aquí:</li></ol>
      <div style="display:flex;gap:6px"><input id="base-pub" placeholder="https://xxxx.trycloudflare.com" style="flex:1;min-width:0"><button class="sm" onclick="usarBasePublica()">Usar</button></div></div>`;
  dlg.innerHTML=`<h2>Conectar celular del almacén</h2>
  <p class="note" style="margin:0 0 8px">Abre la cámara normal del celular y escanea este código. Se abre el escáner del almacén, sin iniciar sesión y sin cable. Los códigos que escanee se registran en el ingreso de almacén.</p>
  ${aviso}
  <div id="qr-celular" style="background:#fff;border-radius:12px;padding:10px;margin:12px auto;width:max-content"></div>
  <p class="note" style="word-break:break-all;margin:0">${esc(urlCelular())}</p>
  <div class="row"><button onclick="dlg.close()">Cerrar</button></div>`;
  if(!dlg.open)dlg.showModal();
  const caja=document.getElementById('qr-celular');
  if(!window.QRCode){caja.innerHTML='<p class="err" style="margin:0">No se cargó el generador de QR. Revisa la conexión a internet y recarga la página.</p>';return}
  caja.innerHTML='';
  new QRCode(caja,{text:urlCelular(),width:240,height:240,correctLevel:QRCode.CorrectLevel.M});
}
async function sondearCelular(){
  if(!CEL.activo)return;
  try{
    const r=await fetch('index.php?r=escaner_pendientes&after='+CEL.ultimo,{cache:'no-store',headers:{'Accept':'application/json'}});
    const j=await r.json();
    if(j.ok){
      CEL.conectado=!!j.data.celular;
      j.data.codigos.forEach(c=>{CEL.ultimo=Math.max(CEL.ultimo,c.id);procesarCodigoCelular(c.codigo)});
      pintarFeed();
    }
  }catch(_){}
  setTimeout(sondearCelular,2000);
}
// Solo muestra si el celular está conectado. Cada código que llega se procesa igual que uno escaneado con la cámara.
function procesarCodigoCelular(c){
  if(dlg.open){toast('Código del celular: '+c+' (cierre el formulario abierto para registrarlo)');return}
  enviarCodigo(c);
}
function pintarFeed(){
  let box=document.getElementById('feed-celular');
  if(!box){
    box=document.createElement('div');
    box.id='feed-celular';
    box.style.cssText='position:fixed;right:12px;bottom:40px;width:min(340px,92vw);z-index:40;background:#fff;color:#111;border:1px solid #ddd;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.15);padding:12px;display:none;font:14px system-ui,sans-serif';
    document.body.appendChild(box);
  }
  if(!CEL.token||CEL.oculto){box.style.display='none';return}
  box.style.display='block';
  box.innerHTML=`<div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
    <div><b>${CEL.conectado?'📱 Celular conectado':'📱 Esperando el celular…'}</b>
    <div class="note" style="margin:2px 0 0">Los códigos escaneados se registran en el ingreso de almacén.</div></div>
    <span><button class="sm" onclick="conectarCelular()">Nuevo QR</button> <button class="sm" onclick="CEL.oculto=true;pintarFeed()">✕</button></span></div>`;
}