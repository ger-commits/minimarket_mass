(function(){
var u=document.getElementById('usr'),p=document.getElementById('pwd'),go=document.getElementById('go'),eye=document.getElementById('eye');
function check(){go.disabled=!(u.value.trim().length>=3&&p.value.length>=4);}
u.addEventListener('input',check);p.addEventListener('input',check);
eye.addEventListener('click',function(){var s=p.type==='password';p.type=s?'text':'password';eye.textContent=s?'Ocultar':'Mostrar';eye.setAttribute('aria-pressed',s);});
document.getElementById('login').addEventListener('submit',function(e){e.preventDefault();/* Aquí va la validación con el backend */});

var D=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
function p2(n){return n<10?'0'+n:n;}
function tick(){var d=new Date(),h=d.getHours(),ap=h>=12?'p. m.':'a. m.';h=h%12||12;
document.getElementById('tm').textContent=h+':'+p2(d.getMinutes())+' '+ap;
document.getElementById('dt').textContent=D[d.getDay()]+' '+p2(d.getDate())+'/'+p2(d.getMonth()+1)+'/'+d.getFullYear();}
tick();setInterval(tick,15000);

var still=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
function fmt(n){return Math.round(n).toLocaleString('en-US');}
document.querySelectorAll('[data-n]').forEach(function(el){
  var to=+el.getAttribute('data-n');
  if(still){el.textContent=fmt(to);return;}
  var t0=null,dur=1800;
  function step(t){if(!t0)t0=t+600;var k=Math.min(Math.max((t-t0)/dur,0),1),e=1-Math.pow(1-k,3);el.textContent=fmt(to*e);if(k<1)requestAnimationFrame(step);}
  requestAnimationFrame(step);
});
})();
