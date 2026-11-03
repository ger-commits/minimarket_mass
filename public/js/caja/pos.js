const $=s=>document.querySelector(s),$$=s=>[...document.querySelectorAll(s)];
const m$=x=>'S/ '+x.toFixed(2),r2=x=>Math.round(x*100)/100,pad=n=>String(n).padStart(6,'0');
const CAT=[{c:'7750001',n:'Arroz extra 5 kg',p:24.9},{c:'7750002',n:'Aceite vegetal 1 L',p:9.5},{c:'7750003',n:'Leche evaporada 410 g',p:4.2},{c:'7750004',n:'Azúcar rubia 1 kg',p:4.5},{c:'7750005',n:'Fideos spaghetti 500 g',p:3.8},{c:'7750006',n:'Atún filete 170 g',p:6.9},{c:'7750007',n:'Gaseosa 3 L',p:11.5}];
const NM={ef:'Efectivo',tj:'Tarjeta',sip:'Billetera Sip'};
const S={n:123,cart:[],tipo:'B',dni:'',ruc:'',rs:'',cli:'',medio:'ef',rec:100,busy:false,view:'venta',prev:'venta',sel:null,
 hist:[{num:'B001-000122',tipo:'B',medio:'ef',lineas:3,total:69.1,estado:'vigente'}]};
const T={id:0,estado:'CERRADA',ini:0,ap:null,ci:null,mci:null,list:[]};
const now=()=>new Date().toLocaleString('sv-SE'),pm=v=>parseFloat(String(v).replace(/[^\d.]/g,''));
const efT=()=>r2(S.hist.filter(h=>h.turno===T.id&&h.medio==='ef'&&h.estado==='vigente').reduce((a,h)=>a+h.total,0));
const otT=()=>r2(S.hist.filter(h=>h.turno===T.id&&h.medio!=='ef'&&h.estado==='vigente').reduce((a,h)=>a+h.total,0));
const kv=(a,b)=>`<div class="kv"><span>${a}</span><b>${b}</b></div>`;
function tm(c,t){const m=$('#tmsg');m.className='msg '+c;m.hidden=!t;m.textContent=t}
function rTurno(){const ab=T.estado==='ABIERTA';
 $('#tcard').innerHTML=ab?`<h3>Cierre de caja</h3>${kv('Caja','Caja Nº 01')}${kv('Monto inicial',m$(T.ini))}${kv('Ventas en efectivo',m$(efT()))}${kv('Efectivo esperado en caja',m$(r2(T.ini+efT())))}${kv('Ventas con tarjeta / Sip (no entran a caja)',m$(otT()))}<label style="margin-top:16px">Monto contado al cierre</label><input id="mci" inputmode="decimal" placeholder="S/ 0.00" oninput="rDif()"><div id="dif" class="msg w2" style="margin:12px 0">Ingrese el efectivo contado.</div><button class="b1 full" onclick="cerrar()">Cerrar caja</button>`
 :`<h3>Apertura de caja</h3>${kv('Caja','Caja Nº 01')}${kv('Colaborador','[Nombre]')}${kv('Fecha y hora de apertura','<span id="clk"></span>')}<label style="margin-top:16px">Monto inicial (efectivo con el que inicia la caja)</label><input id="mini" inputmode="decimal" value="S/ 200.00"><div class="g3" style="margin:12px 0">${[100,200,300].map(v=>`<button class="b2" onclick="$('#mini').value='S/ ${v}.00'">S/ ${v}</button>`).join('')}</div><button class="b1 full" onclick="abrir()">Abrir caja</button>`;
 $('#trec').innerHTML=`<h3>Registro del turno · caja_turno</h3>${kv('id_caja_turno',T.id||'—')}${kv('id_caja','1')}${kv('id_empleado','[Nombre]')}${kv('fecha_hora_apertura',T.ap||'—')}${kv('monto_inicial',T.id?m$(T.ini):'—')}${kv('fecha_hora_cierre',T.ci||'—')}${kv('monto_cierre',T.mci==null?'—':m$(T.mci))}${kv('estado',T.estado)}`;
 $('#thist').innerHTML=T.list.length?T.list.map(t=>kv('Turno '+t.id+' · '+t.ci,'Dif. '+m$(t.dif))).join(''):'<p class="hint" style="margin:0">Aún no hay turnos cerrados.</p>';
 const c=$('#clk');if(c)c.textContent=now()}
setInterval(()=>{const c=$('#clk');if(c)c.textContent=now()},1000);
function abrir(){const v=pm($('#mini').value);if(isNaN(v)||v<0){tm('err','Ingrese un monto inicial válido.');return}
 T.id++;T.estado='ABIERTA';T.ini=r2(v);T.ap=now();T.ci=null;T.mci=null;tm('ok','');go('venta')}
function rDif(){const v=pm($('#mci').value),b=$('#dif');if(isNaN(v)){b.className='msg w2';b.textContent='Ingrese el efectivo contado.';return}
 const d=r2(v-(T.ini+efT()));b.className='msg '+(d===0?'ok':d>0?'w2':'err');b.textContent=d===0?'Caja cuadrada':(d>0?'Sobrante ':'Faltante ')+m$(Math.abs(d))}
function cerrar(){const v=pm($('#mci').value);if(isNaN(v)){rDif();return}const esp=r2(T.ini+efT()),d=r2(v-esp);
 T.ci=now();T.mci=r2(v);T.estado='CERRADA';T.list.unshift({id:T.id,ci:T.ci,dif:d});go('turno');tm(d===0?'ok':'w2','Caja cerrada · turno N.º '+T.id+(d===0?' · caja cuadrada':' · diferencia '+m$(d)))}
Object.assign(S.hist[0],{fecha:now().slice(0,10),hora:'08:15:00',cli:'Sin datos'});S.f='todas';
function setF(f){S.f=f;rVentas()}
function anularDe(n){abrirAnular();$('#aq').value=n;buscar()}
function rVentas(){const all=S.hist.filter(h=>h.fecha===now().slice(0,10)),vig=all.filter(h=>h.estado==='vigente'),an=all.filter(h=>h.estado==='anulada'),sm=a=>m$(r2(a.reduce((x,h)=>x+h.total,0)));
 const L=S.f==='todas'?all:S.f==='vigente'?vig:an,ab=T.estado==='ABIERTA';
 $('#vf').innerHTML=[['todas','Todas ('+all.length+')'],['vigente','Vigentes ('+vig.length+')'],['anulada','Anuladas ('+an.length+')']].map(a=>`<button class="${S.f===a[0]?'b1':'b2'}" style="height:42px;font-size:16px" onclick="setF('${a[0]}')">${a[1]}</button>`).join('');
 $('#vt').innerHTML='<thead><tr><th>Hora</th><th>Comprobante</th><th>Cliente</th><th>Medio</th><th class="r">Prod.</th><th class="r">Total</th><th>Estado</th><th></th></tr></thead><tbody>'+L.slice().reverse().map(h=>`<tr><td>${h.hora||'—'}</td><td><b>${h.num}</b></td><td>${h.cli||'—'}</td><td>${NM[h.medio]}</td><td class="r">${h.lineas}</td><td class="r">${m$(h.total)}</td><td><span class="msg ${h.estado==='vigente'?'ok':'err'}" style="padding:4px 10px;font-size:13px;display:inline-block">${h.estado==='vigente'?'Vigente':'Anulada'}</span></td><td>${h.estado==='vigente'&&ab?`<button class="b2" style="height:34px;font-size:14px;padding:0 12px" onclick="anularDe('${h.num}')">Anular</button>`:''}</td></tr>`).join('')+'</tbody>';
 $('#vempty').hidden=L.length>0;
 const med=m=>vig.filter(h=>h.medio===m);
 $('#vsum').innerHTML=`<h3>Resumen del día</h3>${kv('Ventas vigentes',vig.length)}${kv('Total vendido',sm(vig))}${kv('Efectivo',sm(med('ef')))}${kv('Tarjeta',sm(med('tj')))}${kv('Billetera Sip',sm(med('sip')))}${kv('Anuladas',an.length+' · '+sm(an))}`}
function nuevaCart(){S.cart=[{...CAT[0],q:1},{...CAT[1],q:2},{...CAT[2],q:6}]}nuevaCart();
const total=()=>r2(S.cart.reduce((a,i)=>a+i.p*i.q,0)),sub=()=>r2(total()/1.18),igv=()=>r2(total()-sub());
function find(t){t=t.trim().toLowerCase();return t&&CAT.find(p=>p.c===t||p.n.toLowerCase()===t)||CAT.find(p=>p.n.toLowerCase().includes(t))}
function go(v){if(v!=='turno'&&v!=='ventas'&&T.estado!=='ABIERTA')v='turno';if(v!=='anular'&&v!=='turno')S.prev=v;if(v!=='turno')tm('ok','');S.view=v;$$('.view').forEach(e=>e.classList.toggle('on',e.id==='v-'+v));
 const st=['venta','comprobante','cobro','resultado'],i=st.indexOf(v);
 $('#steps').innerHTML=`<span tabindex="0" role="button" onkeydown="if(event.key==='Enter')this.click()" class="pill${v==='turno'?' on':''}" onclick="nav('turno')"><span class="nav-icon">◷</span><span class="nav-label">${T.estado==='ABIERTA'?'Cierre de caja':'Apertura de caja'}</span><span class="nav-arrow">›</span></span>`+['1 Venta','2 Comprobante','3 Cobro','4 Resultado'].map((t,k)=>{const icons=['▣','▤','▰','✓'];return `<span tabindex="0" role="button" onkeydown="if(event.key==='Enter')this.click()" class="pill${k===i?' on':''}" onclick="nav('${st[k]}')"><span class="nav-icon">${icons[k]}</span><span class="nav-label">${t}</span><span class="nav-arrow">›</span></span>`}).join('')+`<span tabindex="0" role="button" onkeydown="if(event.key==='Enter')this.click()" class="pill${v==='anular'?' on':''}" onclick="nav('anular')"><span class="nav-icon">↶</span><span class="nav-label">Anulación de venta</span><span class="nav-arrow">›</span></span><span tabindex="0" role="button" onkeydown="if(event.key==='Enter')this.click()" class="pill${v==='ventas'?' on':''}" onclick="nav('ventas')"><span class="nav-icon">▥</span><span class="nav-label">Ventas del día</span><span class="nav-arrow">›</span></span>`;if(v==='ventas')rVentas();if(v==='resultado')rRes();if(v==='turno')rTurno();const ab=T.estado==='ABIERTA';$('.user').innerHTML=`<span class="user-avatar">C</span><span class="user-kicker">COLABORADOR</span><strong class="user-name">[Nombre del usuario]</strong><span class="user-meta">Personal de caja · Caja Nº 01</span><span class="user-status"><span>ESTADO DE TERMINAL</span><b class="${ab?'':'closed'}">${ab?'CAJA ABIERTA':'CAJA CERRADA'}</b></span>`;
 rSum();if(v==='venta')rCart();if(v==='cobro'){rVuelto();setMedio(S.medio)}scrollTo(0,0);heroUp(v)}
function rSum(){$$('.sum').forEach(e=>e.innerHTML=`<h3>Resumen de la venta</h3><div class="l"><span>Venta N.º</span><b>${pad(S.n)}</b></div><div class="l"><span>Subtotal</span><span>${m$(sub())}</span></div><div class="l"><span>IGV (18%)</span><span>${m$(igv())}</span></div><div>Total a pagar</div><div class="t">${m$(total())}</div>`)}
/* venta */
function rCart(){$('#cart').innerHTML=S.cart.map((i,k)=>`<tr><td><div class="pn"><span class="ic">${i.n[0]}</span><span><b>${i.n}</b><small>Cód. ${i.c}</small></span></div></td><td><span class="q"><button class="b2" onclick="qty(${k},-1)">−</button>${i.q}<button class="b2" onclick="qty(${k},1)">+</button></span></td><td>${m$(i.p)}</td><td>${m$(r2(i.p*i.q))}</td></tr>`).join('');
 $('#empty').hidden=S.cart.length>0;$('#toComp').disabled=!S.cart.length;rSum()}
function qty(k,d){S.cart[k].q+=d;if(S.cart[k].q<1)S.cart.splice(k,1);rCart()}
function consultar(){const p=find($('#q').value),b=$('#qres');b.hidden=false;
 b.innerHTML=p?`<span>${p.n} · Stock disponible en tienda</span><b>${m$(p.p)}</b>`:'<span>Producto no encontrado</span>';return p}
function agregar(){const p=consultar();if(!p)return;const x=S.cart.find(i=>i.c===p.c);x?x.q++:S.cart.push({...p,q:1});$('#q').value='';rCart();$('#q').focus()}
/* comprobante */
function nav(v){v==='anular'?abrirAnular():go(v)}
function setTipo(t){S.tipo=t;$('#tcB').classList.toggle('on',t==='B');$('#tcF').classList.toggle('on',t==='F');$('#dni').disabled=t==='F';$('#ruc').disabled=$('#rs').disabled=t==='B'}
function regCli(){const b=$('#cmsg');if(!S.cli.trim()){b.hidden=false;b.className='msg err';b.textContent='Escriba el nombre del cliente.';return}b.hidden=false;b.className='msg ok';b.textContent='Cliente registrado: '+S.cli.trim()}
function toCobro(){let e='';if(S.tipo==='F'){if(S.ruc.length!==11)e='El RUC debe tener 11 dígitos.';else if(!S.rs.trim())e='Ingrese la razón social.'}else if(S.dni&&S.dni.length!==8)e='El DNI debe tener 8 dígitos.';
 const b=$('#cerr');b.hidden=!e;b.textContent=e;if(!e)go('cobro')}
/* cobro */
function setMedio(m){S.medio=m;$$('.mp').forEach(b=>{const on=b.dataset.m===m;b.classList.toggle('on',on);b.classList.toggle('b1',on);b.classList.toggle('b2',!on)});
 $('#pef').hidden=m!=='ef';$('#ptar').hidden=m==='ef';$('#ptt').textContent=m==='tj'?'Pago con tarjeta · requiere autorización':'Pago con Billetera Sip · requiere autorización'}
function setRec(v){S.rec=v;$('#rec').value=m$(v);rVuelto()}
function rVuelto(){$('#vuelto').textContent=S.rec>=total()?m$(r2(S.rec-total())):'—'}
function stat(c,t){const b=$('#stat');b.className='msg '+c;b.textContent=t}
function confirmar(){if(S.busy)return;
 if(S.medio==='ef'){if(S.rec<total()){stat('err','Monto insuficiente · falta '+m$(r2(total()-S.rec)));return}return fin()}
 S.busy=true;$('#pay').disabled=true;stat('w2','Autorizando con el Sistema de pago...');
 setTimeout(()=>{if(Math.random()<.8){stat('ok','Pago aprobado · se emite el comprobante');setTimeout(()=>{S.busy=false;$('#pay').disabled=false;fin()},700)}
 else{S.busy=false;$('#pay').disabled=false;stat('err','Pago rechazado · elegir otro medio')}},1800)}
function fin(){const num=(S.tipo==='B'?'B001-':'F001-')+pad(S.n),v=S.medio==='ef'?r2(S.rec-total()):0;
 S.last={num,tipo:S.tipo,medio:S.medio,lineas:S.cart.length,total:total(),estado:'vigente',turno:T.id,fecha:now().slice(0,10),hora:now().slice(11),vuelto:v,cli:S.tipo==='F'?S.rs.trim()+' · RUC '+S.ruc:(S.cli.trim()||(S.dni?'DNI '+S.dni:'Sin datos'))};
 S.hist.push(S.last);go('resultado')}
function rRes(){const l=S.last||{num:'B001-'+pad(S.n),tipo:'B',medio:'ef',total:total(),vuelto:Math.max(0,r2(S.rec-total())),cli:'Sin datos'};
 $('#rdet').innerHTML=[['Comprobante',(l.tipo==='B'?'Boleta ':'Factura ')+l.num],['Cliente',l.cli],['Medio de pago',NM[l.medio]],['Total cobrado',m$(l.total)],['Vuelto',m$(l.vuelto)]].map(a=>`<div class="kv"><span>${a[0]}</span><b>${a[1]}</b></div>`).join('')}
function nueva(){S.n++;S.cart=[];S.tipo='B';S.dni=S.ruc=S.rs=S.cli='';S.rec=100;$$('#v-comprobante input').forEach(i=>i.value='');$('#rec').value='S/ 100.00';$('#cmsg').hidden=$('#cerr').hidden=$('#qres').hidden=true;setTipo('B');stat('w2','Aún no se ha confirmado el pago.');go('venta')}
/* anular */
function abrirAnular(){S.sel=null;$('#aq').value=$('#mot').value='';$('#ares').hidden=$('#afin').hidden=$('#demo').hidden=true;$('#astat').className='msg w2';$('#astat').textContent='Aún no se ha solicitado autorización.';S.wait=false;go('anular');rAnular()}
function buscar(){const t=$('#aq').value.trim().toUpperCase(),b=$('#ares');b.hidden=false;
 S.sel=S.hist.find(h=>h.num===t||h.num.endsWith('-'+t.padStart(6,'0')))||null;const h=S.sel;
 b.innerHTML=h?`<span>${h.tipo==='B'?'Boleta':'Factura'} ${h.num} · ${NM[h.medio]} · ${h.lineas} productos${h.estado==='anulada'?' · YA ANULADA':''}</span><b>${m$(h.total)}</b>`:'<span>No se encontró la venta</span>';rAnular()}
function rAnular(){$('#solic').disabled=!(S.sel&&S.sel.estado==='vigente'&&$('#mot').value.trim()&&!S.wait)}
function solicitar(){S.wait=true;rAnular();$('#astat').className='msg warn';$('#astat').style.margin='0';$('#astat').innerHTML='<b>Esperando respuesta del Administrador...</b>';$('#demo').hidden=false;$('#afin').hidden=true}
function resp(ok){S.wait=false;$('#demo').hidden=true;const a=$('#astat'),f=$('#afin');f.hidden=false;
 if(ok){S.sel.estado='anulada';a.className='msg ok';a.textContent='Autorizado por el Administrador';f.className='msg ok';f.textContent='Venta anulada · nota de crédito emitida';$('#ares').firstChild.textContent+=' · ANULADA'}
 else{a.className='msg err';a.textContent='Autorización rechazada';f.className='msg err';f.textContent='Anulación rechazada · la venta sigue vigente'}rAnular()}
const HT={turno:['Caja Nº 01','Abre o cierra el turno y revisa el efectivo'],venta:['Registrar venta','Escanea o busca los productos del cliente'],comprobante:['Comprobante','Elige boleta o factura y registra al cliente'],cobro:['Cobro','Selecciona el medio de pago y confirma'],resultado:['¡Venta completada!','El comprobante ya fue emitido'],anular:['Anular venta','Requiere autorización del administrador'],ventas:['Ventas del día','Revisa y filtra las ventas de este turno']};
function heroUp(v){const h=HT[v],hr=new Date().getHours(),st=['venta','comprobante','cobro','resultado'],i=st.indexOf(v);$('#ht').textContent=h[0];$('#hs').textContent=h[1];$('#hturn').textContent='Turno '+(hr<12?'mañana':hr<19?'tarde':'noche');$('#hven').textContent='Venta N.º '+pad(S.n);$('#hp').hidden=i<0;$('#hp').innerHTML=st.map((_,k)=>`<i class="${k<=i?'on':''}"></i>`).join('');if(v==='resultado')setTimeout(confetti,250)}
function confetti(){const e=$('#v-resultado .msg');if(!e||!e.animate||matchMedia('(prefers-reduced-motion:reduce)').matches)return;const r=e.getBoundingClientRect(),cx=r.left+40,cy=r.top+r.height/2,col=['#E4002B','#FFD100','#fff','#2BD17E'];for(let i=0;i<36;i++){const p=document.createElement('i'),a=Math.random()*Math.PI*2,d=80+Math.random()*160;p.style.cssText=`position:fixed;z-index:50;left:${cx}px;top:${cy}px;width:8px;height:12px;border-radius:2px;pointer-events:none;background:${col[i%4]}`;document.body.appendChild(p);p.animate([{transform:'translate(0,0) rotate(0)',opacity:1},{transform:`translate(${Math.cos(a)*d}px,${Math.sin(a)*d+60}px) rotate(${Math.random()*540}deg)`,opacity:0}],{duration:900+Math.random()*500,easing:'cubic-bezier(.2,.8,.4,1)'}).onfinish=()=>p.remove()}}
function clk(){const d=new Date();let h=d.getHours();const ap=h>=12?'p. m.':'a. m.';h=h%12||12;$('#ctm').innerHTML=h+'<span class="colon">:</span>'+String(d.getMinutes()).padStart(2,'0')+' '+ap;$('#cdt').textContent=d.toLocaleDateString('es-PE',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'})}
clk();setInterval(clk,15000);
setTipo('B');go('venta');
