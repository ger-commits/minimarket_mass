const nav=document.getElementById('nav'),sections=[...document.querySelectorAll('.section')],crumb=document.getElementById('crumb'),toast=document.getElementById('toast');
let timer, activeModalAction='';
function showToast(msg){toast.textContent=msg;toast.classList.add('show');clearTimeout(timer);timer=setTimeout(()=>toast.classList.remove('show'),2600)}
function go(id){
  sections.forEach(s=>s.classList.toggle('active',s.id===id));
  nav.querySelectorAll('button').forEach(b=>b.classList.toggle('active',b.dataset.page===id));
  let b=nav.querySelector(`[data-page="${id}"]`);crumb.textContent=b?b.textContent.trim():'Resumen general';
  document.getElementById('sidebar').classList.remove('open');window.scrollTo({top:0,behavior:'smooth'});
}
nav.addEventListener('click',e=>{let b=e.target.closest('[data-page]');if(b)go(b.dataset.page)});
document.getElementById('menu').onclick=()=>document.getElementById('sidebar').classList.toggle('open');
document.getElementById('bell').onclick=()=>{go('solicitudes');showToast('Mostrando solicitudes pendientes de revisión')};
document.getElementById('search').addEventListener('input',e=>{
  const q=e.target.value.toLowerCase().trim(); if(!q)return;
  const b=[...nav.querySelectorAll('button')].find(x=>x.textContent.toLowerCase().includes(q));
  if(b && q.length>2) { /* navegación al pulsar Enter */ }
});
document.getElementById('search').addEventListener('keydown',e=>{
  if(e.key==='Enter'){let q=e.target.value.toLowerCase();let b=[...nav.querySelectorAll('button')].find(x=>x.textContent.toLowerCase().includes(q));if(b)go(b.dataset.page);else showToast('No se encontró una sección con ese nombre.')}
});
document.getElementById('storeFilter').addEventListener('change',e=>showToast('Vista filtrada: '+e.target.value+' · cifras de demostración'));
document.getElementById('new').onclick=()=>openModal('¿Qué deseas registrar?','quick');
document.getElementById('export').onclick=()=>exportCSV();
document.addEventListener('click',e=>{
  const g=e.target.closest('[data-goto]');if(g){e.preventDefault();go(g.dataset.goto)}
  const a=e.target.closest('[data-action]');if(a)openModal(a.dataset.action);
});
const backdrop=document.getElementById('modalBackdrop'),fields=document.getElementById('modalFields'),title=document.getElementById('modalTitle');
function field(label,name,type='text',placeholder='',options=[]){
  let control=type==='select'?`<select name="${name}" required>${options.map(x=>`<option>${x}</option>`).join('')}</select>`:`<input name="${name}" type="${type}" placeholder="${placeholder}" required>`;
  return `<div class="field"><label>${label}</label>${control}</div>`;
}
function openModal(action){
  activeModalAction=action; title.textContent=action; let content='';
  if(action==='quick'){
    content=field('Tipo de registro','tipo','select','',['Producto','Empleado','Traslado','Merma']);
  } else if(action.toLowerCase().includes('producto')){
    content=field('Nombre del producto','nombre')+field('Categoría','categoria','select','',['Abarrotes','Lácteos','Bebidas','Limpieza'])+field('Marca','marca')+field('Precio (S/)','precio','number');
  } else if(action.toLowerCase().includes('empleado')){
    content=field('Nombre completo','nombre')+field('Rol','rol','select','',['Cajero','Almacenero','Administrador'])+field('Tienda','tienda','select','',['Cayma','Cerro Colorado','Yanahuara'])+field('Correo','correo','email');
  } else if(action.toLowerCase().includes('traslado')){
    content=field('Tienda de origen','origen','select','',['Cayma','Cerro Colorado','Yanahuara'])+field('Tienda de destino','destino','select','',['Cayma','Cerro Colorado','Yanahuara'])+field('Producto','producto')+field('Cantidad','cantidad','number');
  } else if(action.toLowerCase().includes('merma')){
    content=field('Producto','producto')+field('Tienda','tienda','select','',['Cayma','Cerro Colorado','Yanahuara'])+field('Cantidad','cantidad','number')+field('Motivo','motivo','select','',['Vencido','Dañado','Pérdida']);
  } else if(action.toLowerCase().includes('revisar')){
    content='<p style="margin:0;color:#666;line-height:1.6">Revisa la solicitud antes de decidir. En esta maqueta la decisión solo cambia el estado visual, no se conecta a una base de datos.</p>'+field('Observación','observacion');
  } else {
    content=field('Nombre o descripción','nombre')+field('Detalle','detalle');
  }
  fields.innerHTML=content;backdrop.classList.add('open');
}
function closeModal(){backdrop.classList.remove('open')}
document.getElementById('modalClose').onclick=closeModal;document.getElementById('modalCancel').onclick=closeModal;
backdrop.addEventListener('click',e=>{if(e.target===backdrop)closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
document.getElementById('modalForm').addEventListener('submit',e=>{
  e.preventDefault();const data=Object.fromEntries(new FormData(e.target).entries());
  if(activeModalAction==='quick'){
    const map={Producto:'Nuevo producto',Empleado:'Registrar empleado',Traslado:'Nuevo traslado',Merma:'Registrar merma'};
    closeModal();openModal(map[data.tipo]||'Nuevo registro');return;
  }
  if(activeModalAction.toLowerCase().includes('revisar')){
    const row=[...document.querySelectorAll('tr')].find(r=>r.textContent.includes(activeModalAction.replace('Revisar ','')));
    if(row){const cell=row.lastElementChild;cell.innerHTML='<span class="pill ok">Revisada</span>';}
  } else if(activeModalAction.toLowerCase().includes('producto') && data.nombre){
    const tbody=document.querySelector('#productos tbody');
    if(tbody){const tr=document.createElement('tr');tr.innerHTML=`<td>${safe(data.nombre)}</td><td>${safe(data.categoria||'—')}</td><td>${safe(data.marca||'—')}</td><td>unid.</td><td>S/ ${safe(data.precio||'0.00')}</td><td><span class="pill ok">Activo · demo</span></td>`;tbody.appendChild(tr);}
  }
  closeModal();showToast('Registro agregado en la vista de demostración');
  e.target.reset();
});
function safe(v){return String(v).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function exportCSV(){
  const active=document.querySelector('.section.active');const table=active?.querySelector('table');
  if(!table){showToast('Esta sección no tiene una tabla para exportar');return}
  const rows=[...table.querySelectorAll('tr')].map(r=>[...r.children].map(c=>'"'+c.innerText.replace(/"/g,'""')+'"').join(',')).join('\n');
  const blob=new Blob(['\ufeff'+rows],{type:'text/csv;charset=utf-8;'}),url=URL.createObjectURL(blob),a=document.createElement('a');
  a.href=url;a.download=(active.id||'reporte')+'.csv';a.click();URL.revokeObjectURL(url);showToast('Archivo CSV generado');
}
// Search within visible tables as the user types.
document.querySelectorAll('.section table').forEach(table=>{
  const tools=document.createElement('div');tools.className='table-tools';
  const input=document.createElement('input');input.placeholder='Buscar en esta tabla…';input.setAttribute('aria-label','Buscar en esta tabla');
  input.addEventListener('input',()=>{const q=input.value.toLowerCase();table.querySelectorAll('tbody tr').forEach(row=>row.style.display=row.innerText.toLowerCase().includes(q)?'':'none')});
  tools.appendChild(input);table.parentElement.insertBefore(tools,table);
});
// Small interaction: clicking a notification indicator opens pending requests.
