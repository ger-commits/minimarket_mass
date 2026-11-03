(function(){
  var $=function(i){return document.getElementById(i)};
  var usr=$('usr'),pwd=$('pwd'),msg=$('msg'),tg=$('tg'),sheet=$('sheet'),go=$('go'),bt=$('bt'),app=$('app'),busy=false;
  var calm=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  function show(t,c){msg.textContent=t;msg.className='msg '+c;}
  tg.addEventListener('click',function(){var h=pwd.type==='password';pwd.type=h?'text':'password';tg.classList.toggle('on',h);tg.setAttribute('aria-label',h?'Ocultar contraseña':'Mostrar contraseña');});
  app.addEventListener('focusin',function(e){if(e.target.tagName==='INPUT'&&e.target.type!=='checkbox')app.classList.add('kb');});
  app.addEventListener('focusout',function(){setTimeout(function(){var a=document.activeElement;if(!a||a.tagName!=='INPUT'||a.type==='checkbox')app.classList.remove('kb');},120);});
  [usr,pwd].forEach(function(i){i.addEventListener('input',function(){i.parentNode.classList.remove('bad');});});
  go.addEventListener('pointerdown',function(e){
    var r=go.getBoundingClientRect(),s=Math.max(r.width,r.height)/4,el=document.createElement('span');
    el.className='rip';el.style.cssText='width:'+s+'px;height:'+s+'px;left:'+(e.clientX-r.left-s/2)+'px;top:'+(e.clientY-r.top-s/2)+'px';
    go.appendChild(el);setTimeout(function(){el.remove();},650);
  });
  function confeti(){
    if(calm||!document.body.animate)return;
    var r=go.getBoundingClientRect(),x=r.left+r.width/2,y=r.top+r.height/2,cols=['#ffd100','#ffffff','#1b56c4','#16a34a'];
    for(var i=0;i<26;i++){
      var d=document.createElement('i');d.className='cf';d.style.left=x+'px';d.style.top=y+'px';d.style.background=cols[i%4];
      document.body.appendChild(d);
      var a=Math.random()*Math.PI-Math.PI,v=90+Math.random()*130;
      d.animate([{transform:'translate(0,0) rotate(0)',opacity:1},{transform:'translate('+Math.cos(a)*v+'px,'+(Math.sin(a)*v-40)+'px) rotate('+(Math.random()*540)+'deg)',opacity:1,offset:.55},{transform:'translate('+Math.cos(a)*v*1.2+'px,'+(Math.sin(a)*v+120)+'px) rotate(720deg)',opacity:0}],{duration:1100+Math.random()*500,easing:'ease-out'}).onfinish=(function(n){return function(){n.remove();};})(d);
    }
  }
  function enviar(){
    if(busy)return;
    var f1=usr.value.trim(),f2=pwd.value;
    if(!f1||!f2){
      if(!f1)$('f1').classList.add('bad');if(!f2)$('f2').classList.add('bad');
      show('Ingresa tu usuario y contraseña.','err');
      sheet.classList.remove('shake');void sheet.offsetWidth;sheet.classList.add('shake');
      if(navigator.vibrate)navigator.vibrate(60);return;
    }
    busy=true;
    if(document.activeElement&&document.activeElement.blur)document.activeElement.blur();
    go.classList.add('load');bt.textContent='Verificando…';show('','');
    setTimeout(function(){
      go.classList.remove('load');go.classList.add('ok');bt.textContent='¡Bienvenido!';
      show('Acceso correcto (boceto). Redirigiendo al panel…','ok');
      app.classList.add('done');confeti();if(navigator.vibrate)navigator.vibrate([40,40,40]);
      setTimeout(function(){app.classList.remove('done');go.classList.remove('ok');bt.textContent='Ingresar';show('','');busy=false;},3600);
    },1200);
  }
  go.addEventListener('click',enviar);
  usr.addEventListener('keydown',function(k){if(k.key==='Enter'){k.preventDefault();pwd.focus();}});
  pwd.addEventListener('keydown',function(k){if(k.key==='Enter')enviar();});
})();
