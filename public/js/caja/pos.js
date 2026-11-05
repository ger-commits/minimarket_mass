/* Terminal de caja Mass. Todo se lee y se guarda en MySQL a través de index.php?r=caja_api_*.
   El navegador solo muestra y envía: precios, stock, correlativos, turnos y anulaciones los decide el servidor. */
const $=s=>document.querySelector(s),$$=s=>[...document.querySelectorAll(s)];
const m$=x=>'S/ '+Number(x).toFixed(2),r2=x=>Math.round(x*100)/100;
const esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const NM={ef:'Efectivo',tj:'Tarjeta',sip:'Billetera Sip'};
const CSRF=(document.querySelector('meta[name="csrf-token"]')||{}).content||'';
const S={cart:[],tipo:'B',dni:'',ruc:'',medio:'ef',rec:0,busy:false,view:'venta',prev:'venta',sel:null,sol:null,timer:null,last:null,f:'todas',ventas:[],res:[]};
const T={info:null,turno:null,cerrados:[]};   // info = empleado, tienda, caja (vienen del servidor)

/* ---------- servidor ---------- */
async function api(ruta,cuerpo){
 const o=cuerpo===undefined?{credentials:'same-origin'}:{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify(cuerpo)};
 let r;try{r=await fetch('index.php?r='+ruta,o)}catch(e){return{ok:false,error:'Sin conexión con el servidor.'}}
 if(r.status===401){location.href='index.php';return{ok:false,error:'Sesión terminada.'}}
 try{return await r.json()}catch(e){return{ok:false,error:'Respuesta no válida del servidor.'}}
}
async function cargarEstado(){
 const j=await api('caja_api_estado');if(!j.ok){tm('err',j.error||'No se pudo leer el estado de la caja.');return false}
 const d=j.datos;T.info={empleado:d.empleado,tienda:d.tienda,caja:d.caja,activa:d.caja_activa};T.turno=d.turno;T.cerrados=d.cerrados;return true
}
const cajaTxt=()=>'Caja Nº '+String((T.info&&T.info.caja)||0).padStart(2,'0');
const fmtF=t=>{if(!t)return '—';const m=String(t).match(/^(\d{4})-(\d\d)-(\d\d)[ T](\d\d:\d\d)/);return m?`${m[3]}/${m[2]}/${m[1]} ${m[4]}`:t};
const abierta=()=>!!T.turno;
const kv=(a,b)=>`<div class="kv"><span>${a}</span><b>${b}</b></div>`;
function tm(c,t){const m=$('#tmsg');m.className='msg '+c;m.hidden=!t;m.textContent=t}

/* ---------- turno de caja ---------- */
function regTurno(){ // registro formal del turno (el abierto o, si no hay, el último cerrado)
 const t=T.turno,u=T.cerrados[0];
 const r=t?{id:t.id,ap:t.apertura,ini:t.inicial,ci:null,mci:null,est:'ABIERTA'}:u?{id:u.id,ap:u.apertura,ini:u.inicial,ci:u.cierre,mci:u.contado,est:'CERRADA'}:null;
 const f=(a,b)=>`<tr><th scope="row">${a}</th><td>${b}</td></tr>`;
 return `<div class="reg"><div class="reg-h"><div><span class="reg-k">Registro del turno</span><b class="reg-n">${r?'N.º '+String(r.id).padStart(6,'0'):'Sin turno registrado'}</b></div><span class="reg-b ${r&&r.est==='ABIERTA'?'on':''}">${r?(r.est==='ABIERTA'?'Abierta':'Cerrada'):'Cerrada'}</span></div>
 <table class="reg-t"><tbody>${f('Caja',esc(cajaTxt()+(T.info&&T.info.tienda?' · '+T.info.tienda:'')))}${f('Cajero responsable',esc(T.info?T.info.empleado:'—'))}${f('Apertura',r?fmtF(r.ap):'—')}${f('Monto inicial',r?m$(r.ini):'—')}${f('Cierre',r&&r.ci?fmtF(r.ci):'—')}${f('Monto contado al cierre',r&&r.mci!=null?m$(r.mci):'—')}${f('Estado',`<span class="reg-st ${r&&r.est==='ABIERTA'?'on':''}">${r?r.est:'CERRADA'}</span>`)}</tbody></table></div>`
}
function rTurno(){
 const t=T.turno;
 $('#tcard').innerHTML=t?`<h3>Cierre de caja</h3>${kv('Caja',esc(cajaTxt()))}${kv('Monto inicial',m$(t.inicial))}${kv('Ventas en efectivo',m$(t.efectivo))}${t.movimientos?kv('Movimientos de caja (neto)',m$(t.movimientos)):''}${kv('Efectivo esperado en caja',m$(t.esperado))}${kv('Ventas con tarjeta / Sip (no entran a caja)',m$(t.otros))}<label style="margin-top:16px">Monto contado al cierre</label><input id="mci" inputmode="decimal" placeholder="S/ 0.00" oninput="rDif()"><div id="dif" class="msg w2" style="margin:12px 0">Ingrese el efectivo contado.</div><button class="b1 full" onclick="cerrar()">Cerrar caja</button>`
 :`<h3>Apertura de caja</h3>${kv('Caja',esc(cajaTxt()))}${kv('Colaborador',esc(T.info?T.info.empleado:'—'))}${kv('Fecha y hora de apertura','<span id="clk"></span>')}<label style="margin-top:16px">Monto inicial (efectivo con el que inicia la caja)</label><input id="mini" inputmode="decimal" value="S/ 200.00"><div class="g3" style="margin:12px 0">${[100,200,300].map(v=>`<button class="b2" onclick="$('#mini').value='S/ ${v}.00'">S/ ${v}</button>`).join('')}</div><button class="b1 full" id="bAbrir" onclick="abrir()">Abrir caja</button>`;
 $('#trec').innerHTML=regTurno();
 $('#thist').innerHTML=T.cerrados.length?`<table class="tbl reg-hist"><thead><tr><th>Turno</th><th>Cierre</th><th class="r">Esperado</th><th class="r">Contado</th><th class="r">Diferencia</th></tr></thead><tbody>${T.cerrados.map(x=>`<tr><td>N.º ${String(x.id).padStart(6,'0')}</td><td>${fmtF(x.cierre)}</td><td class="r">${m$(x.esperado)}</td><td class="r">${m$(x.contado)}</td><td class="r"><span class="dif ${x.dif===0?'z':x.dif>0?'p':'n'}">${x.dif===0?'Cuadrada':(x.dif>0?'+':'−')+m$(Math.abs(x.dif))}</span></td></tr>`).join('')}</tbody></table>`:'<p class="hint" style="margin:0">Aún no hay turnos cerrados.</p>';
 const c=$('#clk');if(c)c.textContent=fmtF(new Date().toLocaleString('sv-SE'))
}
setInterval(()=>{const c=$('#clk');if(c)c.textContent=fmtF(new Date().toLocaleString('sv-SE'))},1000);
const pm=v=>parseFloat(String(v).replace(/[^\d.]/g,''));
async function abrir(){const v=pm($('#mini').value);if(isNaN(v)||v<0){tm('err','Ingrese un monto inicial válido.');return}
 $('#bAbrir').disabled=true;const j=await api('caja_api_abrir',{monto_inicial:v});
 if(!j.ok){tm('err',j.error);$('#bAbrir').disabled=false;return}
 await cargarEstado();tm('ok','');go('venta')}
function rDif(){const v=pm($('#mci').value),b=$('#dif');if(isNaN(v)){b.className='msg w2';b.textContent='Ingrese el efectivo contado.';return}
 const d=r2(v-T.turno.esperado);b.className='msg '+(d===0?'ok':d>0?'w2':'err');b.textContent=d===0?'Caja cuadrada':(d>0?'Sobrante ':'Faltante ')+m$(Math.abs(d))}
async function cerrar(){const v=pm($('#mci').value);if(isNaN(v)){rDif();return}
 const j=await api('caja_api_cerrar',{contado:v});if(!j.ok){tm('err',j.error);return}
 S.cart=[];await cargarEstado();go('turno');const d=j.cierre.diferencia;
 tm(d===0?'ok':'w2','Caja cerrada · turno N.º '+String(j.turno).padStart(6,'0')+(d===0?' · caja cuadrada':' · diferencia '+m$(d)))}

/* ---------- ventas del día ---------- */
function setF(f){S.f=f;rVentas()}
function anularDe(n){abrirAnular();$('#aq').value=n;buscar()}
async function cargarVentas(){const j=await api('caja_api_ventas');if(j.ok)S.ventas=j.ventas;rVentas()}
function rVentas(){const all=S.ventas,vig=all.filter(h=>h.estado==='vigente'),an=all.filter(h=>h.estado==='anulada'),sm=a=>m$(r2(a.reduce((x,h)=>x+h.total,0)));
 const L=S.f==='todas'?all:S.f==='vigente'?vig:an,ab=abierta();
 $('#vf').innerHTML=[['todas','Todas ('+all.length+')'],['vigente','Vigentes ('+vig.length+')'],['anulada','Anuladas ('+an.length+')']].map(a=>`<button class="${S.f===a[0]?'b1':'b2'}" style="height:42px;font-size:16px" onclick="setF('${a[0]}')">${a[1]}</button>`).join('');
 $('#vt').innerHTML='<thead><tr><th>Hora</th><th>Comprobante</th><th>Cliente</th><th>Medio</th><th class="r">Prod.</th><th class="r">Total</th><th>Estado</th><th></th></tr></thead><tbody>'+L.map(h=>`<tr><td>${esc(h.hora)}</td><td><b>${esc(h.num)}</b></td><td>${esc(h.cli)}</td><td>${NM[h.medio]}</td><td class="r">${h.lineas}</td><td class="r">${m$(h.total)}</td><td><span class="msg ${h.estado==='vigente'?(h.pendiente?'w2':'ok'):'err'}" style="padding:4px 10px;font-size:13px;display:inline-block">${h.estado==='vigente'?(h.pendiente?'Anulación pendiente':'Vigente'):'Anulada'}</span></td><td>${h.estado==='vigente'&&!h.pendiente&&ab?`<button class="b2" style="height:34px;font-size:14px;padding:0 12px" onclick="anularDe('${h.num}')">Anular</button>`:''}</td></tr>`).join('')+'</tbody>';
 $('#vempty').hidden=L.length>0;
 const med=m=>vig.filter(h=>h.medio===m);
 $('#vsum').innerHTML=`<h3>Resumen del día</h3>${kv('Ventas vigentes',vig.length)}${kv('Total vendido',sm(vig))}${kv('Efectivo',sm(med('ef')))}${kv('Tarjeta',sm(med('tj')))}${kv('Billetera Sip',sm(med('sip')))}${kv('Anuladas',an.length+' · '+sm(an))}`}

/* ---------- navegación ---------- */
const imp=i=>Math.floor((Math.round(i.q*1000)*Math.round(i.p*100)+500)/1000)/100;   // igual que el servidor
const total=()=>r2(S.cart.reduce((a,i)=>a+imp(i),0)),sub=()=>r2(total()/1.18),igv=()=>r2(total()-sub());
function go(v){if(v!=='turno'&&v!=='ventas'&&!abierta())v='turno';if(v!=='anular'&&v!=='turno')S.prev=v;if(v!=='turno')tm('ok','');S.view=v;$$('.view').forEach(e=>e.classList.toggle('on',e.id==='v-'+v));
 const st=['venta','comprobante','cobro','resultado'],i=st.indexOf(v),pill=(on,fn,ic,t)=>`<span tabindex="0" role="button" onkeydown="if(event.key==='Enter')this.click()" class="pill${on?' on':''}" onclick="${fn}"><span class="nav-icon">${ic}</span><span class="nav-label">${t}</span><span class="nav-arrow">›</span></span>`;
 $('#steps').innerHTML=pill(v==='turno',"nav('turno')",'◷',abierta()?'Cierre de caja':'Apertura de caja')+['1 Venta','2 Comprobante','3 Cobro','4 Resultado'].map((t,k)=>pill(k===i,`nav('${st[k]}')`,['▣','▤','▰','✓'][k],t)).join('')+pill(v==='anular',"nav('anular')",'↶','Anulación de venta')+pill(v==='ventas',"nav('ventas')",'▥','Ventas del día');
 const ab=abierta();$('.user').innerHTML=`<span class="user-avatar">${esc((T.info?T.info.empleado:'C')[0])}</span><span class="user-kicker">COLABORADOR</span><strong class="user-name">${esc(T.info?T.info.empleado:'')}</strong><span class="user-meta">Personal de caja · ${esc(cajaTxt())}</span><span class="user-status"><span>ESTADO DE TERMINAL</span><b class="${ab?'':'closed'}">${ab?'CAJA ABIERTA':'CAJA CERRADA'}</b></span>`;
 rSum();if(v==='venta')rCart();if(v==='cobro'){rVuelto();setMedio(S.medio)}if(v==='resultado')rRes();
 if(v==='ventas')cargarVentas();
 if(v==='turno'){rTurno();cargarEstado().then(ok=>{if(ok&&S.view==='turno')rTurno()})}
 scrollTo(0,0);heroUp(v)}
function nav(v){v==='anular'?abrirAnular():go(v)}
function rSum(){$$('.sum').forEach(e=>e.innerHTML=`<h3>Resumen de la venta</h3><div class="l"><span>Comprobante</span><b>${S.tipo==='B'?'Boleta':'Factura'}</b></div><div class="l"><span>Subtotal</span><span>${m$(sub())}</span></div><div class="l"><span>IGV (18%)</span><span>${m$(igv())}</span></div><div>Total a pagar</div><div class="t">${m$(total())}</div>`)}

/* ---------- venta ---------- */
function rCart(){$('#cart').innerHTML=S.cart.map((i,k)=>`<tr><td><div class="pn"><span class="ic">${esc(i.n[0])}</span><span><b>${esc(i.n)}</b><small>${i.c?'Cód. '+esc(i.c):'Por peso'} · Stock ${i.stock} ${esc(i.u)}</small></span></div></td><td><span class="q"><button class="b2" onclick="qty(${k},-1)">−</button>${i.peso?i.q.toFixed(1):i.q}<button class="b2" onclick="qty(${k},1)">+</button></span></td><td>${m$(i.p)}</td><td>${m$(imp(i))}</td></tr>`).join('');
 $('#empty').hidden=S.cart.length>0;$('#toComp').disabled=!S.cart.length;rSum()}
function qty(k,d){const i=S.cart[k],paso=i.peso?0.5:1;let q=r2(i.q+d*paso);
 if(q>i.stock){qmsg('Stock disponible de «'+i.n+'»: '+i.stock+' '+i.u);return}
 if(q<=0)S.cart.splice(k,1);else i.q=q;rCart()}
function qmsg(t){const b=$('#qres');b.hidden=false;b.innerHTML=`<div class="msg warn">${esc(t)}</div>`}
async function buscarProd(){const t=$('#q').value.trim();if(!t)return[];const j=await api('caja_api_productos&q='+encodeURIComponent(t));
 if(!j.ok){qmsg(j.error);return null}return j.productos}
function lista(ps){const b=$('#qres');b.hidden=false;
 b.innerHTML=ps.length?ps.map(p=>`<button class="qrow" onclick="addP(${p.id})"><span><b>${esc(p.nombre)}</b><small>${p.codigo?'Cód. '+esc(p.codigo)+' · ':''}Stock ${p.stock} ${esc(p.unidad)}</small></span><b>${m$(p.precio)}</b></button>`).join(''):'<div class="msg warn">Producto no encontrado</div>';S.res=ps}
async function consultar(){const ps=await buscarProd();if(ps)lista(ps)}
async function agregar(){const ps=await buscarProd();if(!ps)return;
 const t=$('#q').value.trim().toLowerCase(),ex=ps.filter(p=>p.codigo===t||p.nombre.toLowerCase()===t);
 if(ex.length===1||ps.length===1){S.res=ps;addP((ex[0]||ps[0]).id)}else lista(ps)}
function addP(id){const p=S.res.find(x=>x.id===id);if(!p)return;
 if(p.stock<=0){qmsg('«'+p.nombre+'» no tiene stock en esta tienda.');return}
 const x=S.cart.find(i=>i.id===p.id),paso=p.peso?0.5:1;
 if(x){if(r2(x.q+paso)>p.stock){qmsg('Stock disponible de «'+p.nombre+'»: '+p.stock+' '+p.unidad);return}x.q=r2(x.q+paso)}
 else S.cart.push({id:p.id,n:p.nombre,c:p.codigo,p:p.precio,peso:p.peso,u:p.unidad,stock:p.stock,q:paso});
 $('#q').value='';$('#qres').hidden=true;rCart();$('#q').focus()}

/* ---------- comprobante ---------- */
function setTipo(t){S.tipo=t;$('#tcB').classList.toggle('on',t==='B');$('#tcF').classList.toggle('on',t==='F');$('#dni').disabled=t==='F';$('#ruc').disabled=t==='B';rSum()}
function toCobro(){let e='';if(S.tipo==='F'){if(S.ruc.length!==11)e='El RUC debe tener 11 dígitos.'}else if(S.dni&&S.dni.length!==8)e='El DNI debe tener 8 dígitos.';
 const b=$('#cerr');b.hidden=!e;b.textContent=e;if(!e)go('cobro')}

/* ---------- cobro ---------- */
function setMedio(m){S.medio=m;$$('.mp').forEach(b=>{const on=b.dataset.m===m;b.classList.toggle('on',on);b.classList.toggle('b1',on);b.classList.toggle('b2',!on)});
 $('#pef').hidden=m!=='ef';$('#ptar').hidden=m==='ef';$('#ftj').hidden=m!=='tj';$('#fsip').hidden=m!=='sip';$('#ptt').textContent=m==='tj'?'Pago con tarjeta':'Pago con Billetera Sip'}
function setRec(v){S.rec=v;$('#rec').value=m$(v);rVuelto()}
function rVuelto(){$('#vuelto').textContent=S.rec>=total()&&total()>0?m$(r2(S.rec-total())):'—'}
function stat(c,t){const b=$('#stat');b.className='msg '+c;b.textContent=t}
async function confirmar(){if(S.busy)return;
 if(!S.cart.length){stat('err','La venta no tiene productos.');return}
 const pago={medio:S.medio.toUpperCase()};
 if(S.medio==='ef'){if(S.rec<total()){stat('err','Monto insuficiente · falta '+m$(r2(total()-S.rec)));return}pago.recibido=S.rec}
 else if(S.medio==='tj'){Object.assign(pago,{tipo_tarjeta:$('#tjTipo').value,marca:$('#tjMarca').value,ultimos4:$('#tjU4').value,autorizacion:$('#tjAut').value})}
 else Object.assign(pago,{modalidad:$('#spMod').value,operacion:$('#spOp').value});
 S.busy=true;$('#pay').disabled=true;stat('w2','Registrando la venta…');
 const j=await api('caja_api_vender',{items:S.cart.map(i=>({id_producto:i.id,cantidad:i.q})),tipo:S.tipo,dni:S.dni,ruc:S.ruc,pago});
 S.busy=false;$('#pay').disabled=false;
 if(!j.ok){stat('err',j.error);return}
 S.last=j.venta;stat('ok','Pago aprobado · comprobante '+j.venta.comprobante);await cargarEstado();go('resultado')}
function rRes(){const l=S.last;if(!l){$('#rdet').innerHTML='';return}
 $('#rdet').innerHTML=[['Comprobante',(l.tipo==='B'?'Boleta ':'Factura ')+esc(l.comprobante)],['Cliente',esc(l.cliente)],['Medio de pago',NM[l.medio.toLowerCase()]],['Total cobrado',m$(l.total)],['Vuelto',m$(l.vuelto)]].map(a=>kv(a[0],a[1])).join('')}
function nueva(){S.cart=[];S.tipo='B';S.dni=S.ruc='';S.rec=0;S.last=null;$$('#v-comprobante input').forEach(i=>i.value='');$('#rec').value='S/ 0.00';$('#qres').hidden=$('#cerr').hidden=true;
 ['tjMarca','tjU4','tjAut','spOp'].forEach(id=>$('#'+id).value='');setTipo('B');stat('w2','Aún no se ha confirmado el pago.');go('venta')}

/* ---------- anulación (autorización real del administrador) ---------- */
function pararEspera(){if(S.timer){clearInterval(S.timer);S.timer=null}}
function abrirAnular(){pararEspera();S.sel=null;S.sol=null;$('#aq').value=$('#mot').value='';$('#mot').disabled=false;$('#ares').hidden=$('#afin').hidden=true;
 $('#astat').className='msg w2';$('#astat').textContent='Aún no se ha solicitado autorización.';rLista(null);go('anular');rAnular()}
function rLista(s){const base=['Revierte el stock','Emite la nota de crédito','Registra la anulación en el Sistema central'];
 base.forEach((t,k)=>{const li=$('#al'+(k+1));let x=t;
  if(s&&s.estado==='APROBADA'){x='✓ '+t+(k===0?' · '+s.movimientos_stock+' movimiento(s) de stock':k===1?' · '+esc(s.nota_credito):' · '+esc(s.codigo_central))}
  li.innerHTML=x;li.style.color=s&&s.estado==='APROBADA'?'var(--ok)':''})}
async function buscar(){const t=$('#aq').value.trim(),b=$('#ares');b.hidden=false;b.className='msg warn';pararEspera();S.sel=null;
 const j=await api('caja_api_buscar&q='+encodeURIComponent(t));
 if(!j.ok){b.innerHTML='<span>'+esc(j.error)+'</span>';rAnular();return}
 const h=j.venta;S.sel=h;
 if(!h){b.innerHTML='<span>No se encontró la venta</span>';rAnular();return}
 const aviso=h.estado==='ANULADA'?' · YA ANULADA':!h.propia?' · NO ES DE ESTE CAJERO':h.id_pendiente?' · ANULACIÓN PENDIENTE':'';
 b.innerHTML=`<span>${esc(h.tipo.charAt(0)+h.tipo.slice(1).toLowerCase())} ${esc(h.num)} · ${esc(h.medio?h.medio.charAt(0)+h.medio.slice(1).toLowerCase():'')} · ${h.lineas} productos${aviso}</span><b>${m$(h.total)}</b>`;
 if(h.id_pendiente){S.sol=h.id_pendiente;esperar()}
 rAnular()}
function rAnular(){const h=S.sel;$('#solic').disabled=!(h&&h.estado==='EMITIDA'&&h.propia&&!h.id_pendiente&&!S.sol&&$('#mot').value.trim())}
async function solicitar(){const h=S.sel;if(!h)return;$('#solic').disabled=true;
 const j=await api('caja_api_solicitar',{id_venta:h.id_venta,motivo:$('#mot').value.trim()});
 if(!j.ok){const a=$('#astat');a.className='msg err';a.textContent=j.error;rAnular();return}
 S.sol=j.id_solicitud;$('#mot').disabled=true;esperar()}
function esperar(){const a=$('#astat');a.className='msg warn';a.style.margin='0';a.innerHTML='<b>Esperando respuesta del Administrador...</b>';$('#afin').hidden=true;rAnular();pararEspera();consultarSol();S.timer=setInterval(consultarSol,3000)}
async function consultarSol(){if(!S.sol)return;const j=await api('caja_api_solicitud&id='+S.sol);if(!j.ok)return;const s=j.solicitud,a=$('#astat'),f=$('#afin');
 if(s.estado==='PENDIENTE')return;
 pararEspera();f.hidden=false;
 if(s.estado==='APROBADA'){a.className='msg ok';a.textContent='Autorizado por '+(s.administrador||'el Administrador');f.className='msg ok';f.textContent='Venta anulada · nota de crédito '+s.nota_credito+' emitida';rLista(s);if(S.sel)S.sel.estado='ANULADA';const r=$('#ares span');if(r&&!/ANULADA/.test(r.textContent))r.textContent+=' · ANULADA'}
 else{a.className='msg err';a.textContent='Autorización rechazada'+(s.motivo_rechazo?' · '+s.motivo_rechazo:'');f.className='msg err';f.textContent='Anulación rechazada · la venta sigue vigente';S.sol=null;$('#mot').disabled=false;if(S.sel)S.sel.id_pendiente=null}
 rAnular();if(S.view==='ventas')cargarVentas()}

/* ---------- cabecera ---------- */
const HT={turno:['Caja','Abre o cierra el turno y revisa el efectivo'],venta:['Registrar venta','Escanea o busca los productos del cliente'],comprobante:['Comprobante','Elige boleta o factura'],cobro:['Cobro','Selecciona el medio de pago y confirma'],resultado:['¡Venta completada!','El comprobante ya fue emitido'],anular:['Anular venta','Requiere autorización del administrador'],ventas:['Ventas del día','Revisa y filtra las ventas de hoy en esta caja']};
function heroUp(v){const h=HT[v],hr=new Date().getHours(),st=['venta','comprobante','cobro','resultado'],i=st.indexOf(v);$('#ht').textContent=v==='turno'?cajaTxt():h[0];$('#hs').textContent=h[1];$('#hturn').textContent='Turno '+(hr<12?'mañana':hr<19?'tarde':'noche');$('#hven').textContent=S.last&&v==='resultado'?S.last.comprobante:'Venta en curso';$('#hp').hidden=i<0;$('#hp').innerHTML=st.map((_,k)=>`<i class="${k<=i?'on':''}"></i>`).join('');if(v==='resultado')setTimeout(confetti,250)}
function confetti(){const e=$('#v-resultado .msg');if(!e||!e.animate||matchMedia('(prefers-reduced-motion:reduce)').matches)return;const r=e.getBoundingClientRect(),cx=r.left+40,cy=r.top+r.height/2,col=['#E4002B','#FFD100','#fff','#2BD17E'];for(let i=0;i<36;i++){const p=document.createElement('i'),a=Math.random()*Math.PI*2,d=80+Math.random()*160;p.style.cssText=`position:fixed;z-index:50;left:${cx}px;top:${cy}px;width:8px;height:12px;border-radius:2px;pointer-events:none;background:${col[i%4]}`;document.body.appendChild(p);p.animate([{transform:'translate(0,0) rotate(0)',opacity:1},{transform:`translate(${Math.cos(a)*d}px,${Math.sin(a)*d+60}px) rotate(${Math.random()*540}deg)`,opacity:0}],{duration:900+Math.random()*500,easing:'cubic-bezier(.2,.8,.4,1)'}).onfinish=()=>p.remove()}}
function clk(){const d=new Date();let h=d.getHours();const ap=h>=12?'p. m.':'a. m.';h=h%12||12;$('#ctm').innerHTML=h+'<span class="colon">:</span>'+String(d.getMinutes()).padStart(2,'0')+' '+ap;$('#cdt').textContent=d.toLocaleDateString('es-PE',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'})}
clk();setInterval(clk,15000);
setTipo('B');
cargarEstado().then(()=>go(abierta()?'venta':'turno'));
