(function(){
var host=document.querySelector('.stage')||document.querySelector('.side');
var bgs=host.querySelector('.bgs');
var still=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
/* barras de fondo */
var bars=bgs.querySelector('.bars'),H=[46,62,38,70,54,82,48,66,90,58,74,44,86,60,52,78,40,68,92,56,72,48,84,64,50,76,88,60,70,96];
H.forEach(function(h,i){var b=document.createElement('i');b.style.setProperty('--h',h+'%');b.style.setProperty('--i',i);bars.appendChild(b);});
/* partículas */
var dots=bgs.querySelector('.dots');
for(var i=0;i<18;i++){var d=document.createElement('i'),s=3+Math.random()*4;
d.style.cssText='left:'+(Math.random()*100)+'%;width:'+s+'px;height:'+s+'px;animation-duration:'+(11+Math.random()*12)+'s;animation-delay:-'+(Math.random()*20)+'s';dots.appendChild(d);}
/* entrada escalonada de los campos */
var pn=document.querySelector('.panel');
if(pn)Array.prototype.forEach.call(pn.children,function(c,i){c.style.setProperty('--i',i);});
/* luz que sigue al cursor + parallax */
if(!still&&window.matchMedia&&matchMedia('(pointer:fine)').matches){
  var sp=bgs.querySelector('.spot'),tx=0,ty=0,cx=innerWidth/2,cy=innerHeight/3,run=false;
  function loop(){cx+=(tx-cx)*.12;cy+=(ty-cy)*.12;sp.style.setProperty('--sx',cx+'px');sp.style.setProperty('--sy',cy+'px');
    if(Math.abs(tx-cx)>.5||Math.abs(ty-cy)>.5)requestAnimationFrame(loop);else run=false;}
  host.addEventListener('mousemove',function(e){var r=host.getBoundingClientRect();tx=e.clientX-r.left;ty=e.clientY-r.top;
    host.style.setProperty('--mx',(e.clientX/innerWidth*2-1).toFixed(3));host.style.setProperty('--my',(e.clientY/innerHeight*2-1).toFixed(3));
    if(!run){run=true;requestAnimationFrame(loop);}});
}
/* botón: "pop" al habilitarse y estado de carga al enviar */
var go=document.getElementById('go'),tx2=go.querySelector('.tx'),was=go.disabled;
['usr','pwd'].forEach(function(id){document.getElementById(id).addEventListener('input',function(){
  setTimeout(function(){if(was&&!go.disabled&&go.animate&&!still)go.animate([{transform:'scale(1)'},{transform:'scale(1.04)'},{transform:'scale(1)'}],{duration:450,easing:'ease-out'});was=go.disabled;},0);});});
document.getElementById('login').addEventListener('submit',function(){
  if(go.classList.contains('loading'))return;
  go.classList.add('loading');tx2.textContent='Verificando…';
  /* Demo: reemplaza este temporizador por tu llamada al backend */
  setTimeout(function(){go.classList.remove('loading');tx2.textContent='Ingresar al panel';},1600);
});
})();
