/* Bolsa de compras Mass en 3D (Three.js r128). Arrastra para girarla. Copia del diseño original. */
(function(){
if(!window.THREE)return;   /* sin Three.js (p. ej. sin internet) la bolsa 3D simplemente no se dibuja */
var stage=document.getElementById('stage');
var renderer=new THREE.WebGLRenderer({antialias:true,alpha:true});
renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,2));
renderer.outputEncoding=THREE.sRGBEncoding;
renderer.shadowMap.enabled=true;renderer.shadowMap.type=THREE.PCFSoftShadowMap;
stage.appendChild(renderer.domElement);
var scene=new THREE.Scene();
var camera=new THREE.PerspectiveCamera(34,1,0.1,100);
camera.position.set(0,4.2,9.5);
scene.add(new THREE.HemisphereLight(0xffffff,0x8a8a94,0.85));
var key=new THREE.DirectionalLight(0xffffff,0.9);
key.position.set(3,6,5);key.castShadow=true;key.shadow.mapSize.set(1024,1024);key.shadow.radius=4;key.shadow.bias=-0.0008;key.shadow.normalBias=0.02;
var sc=key.shadow.camera;sc.left=-4;sc.right=4;sc.top=4;sc.bottom=-4;
scene.add(key);
var rim=new THREE.DirectionalLight(0xffffff,0.35);rim.position.set(-4,3,-5);scene.add(rim);

var gcv=document.createElement('canvas');gcv.width=gcv.height=256;
(function(){var c=gcv.getContext('2d');var g=c.createRadialGradient(128,128,10,128,128,128);g.addColorStop(0,'rgba(0,0,0,0.45)');g.addColorStop(1,'rgba(0,0,0,0)');c.fillStyle=g;c.fillRect(0,0,256,256);})();
var contact=new THREE.Mesh(new THREE.PlaneGeometry(4.8,3.6),new THREE.MeshBasicMaterial({map:new THREE.CanvasTexture(gcv),transparent:true,depthWrite:false}));
contact.rotation.x=-Math.PI/2;contact.position.y=-1.405;scene.add(contact);
var floor=new THREE.Mesh(new THREE.PlaneGeometry(14,14),new THREE.ShadowMaterial({opacity:0.28}));
floor.rotation.x=-Math.PI/2;floor.position.y=-1.41;floor.receiveShadow=true;scene.add(floor);

var FONT="'Poppins','Arial Black',Arial,sans-serif";
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath();}
function tag(c,text,size,bg,fg,w,h,sp){
rr(c,-w/2,-h/2,w,h,26);c.fillStyle=bg;c.fill();c.lineWidth=5;c.strokeStyle='#FFFFFF';c.stroke();
c.font='800 '+size+'px '+FONT;if('letterSpacing' in c)c.letterSpacing=sp+'px';
c.fillStyle=fg;c.textAlign='center';c.textBaseline='middle';c.fillText(text,sp/2,size*0.04);
}
var fcv=document.createElement('canvas');fcv.width=880;fcv.height=1120;
function drawFront(){
var c=fcv.getContext('2d');
c.fillStyle='#D6001C';c.fillRect(0,0,880,1120);
c.fillStyle='#FFD100';c.beginPath();c.arc(900,1180,330,0,7);c.fill();
c.lineWidth=6;c.strokeStyle='rgba(255,255,255,0.28)';c.beginPath();c.arc(900,1180,385,0,7);c.stroke();
c.fillStyle='#B30017';c.fillRect(0,0,880,86);
c.fillStyle='#FFD100';c.fillRect(0,86,880,6);
c.save();c.translate(440,0);
c.save();c.translate(0,390);tag(c,'MINIMARKET',54,'#1A1A1A','#FFD100',560,98,12);c.restore();
c.save();c.translate(0,584);tag(c,'Mass',190,'#FFD100','#D6001C',560,236,-4);c.restore();
c.restore();
c.fillStyle='rgba(255,255,255,0.55)';c.fillRect(300,985,280,3);
c.fillStyle='#FFFFFF';c.font='600 34px '+FONT;if('letterSpacing' in c)c.letterSpacing='4px';c.textAlign='center';c.textBaseline='alphabetic';
c.fillText('GRACIAS POR TU COMPRA',440+2,1050);
var cg=c.createLinearGradient(0,1060,0,1120);cg.addColorStop(0,'rgba(0,0,0,0)');cg.addColorStop(1,'rgba(0,0,0,0.18)');c.fillStyle=cg;c.fillRect(0,1060,880,60);
c.fillStyle='rgba(0,0,0,0.12)';c.fillRect(0,1058,880,3);c.fillStyle='rgba(255,255,255,0.12)';c.fillRect(0,1061,880,2);
c.fillStyle='rgba(0,0,0,0.10)';c.fillRect(60,92,3,966);c.fillRect(817,92,3,966);
c.fillStyle='rgba(255,255,255,0.10)';c.fillRect(63,92,2,966);c.fillRect(815,92,2,966);
c.fillStyle='rgba(255,255,255,0.03)';for(var q=0;q<2500;q++){c.fillRect(Math.random()*880,Math.random()*1120,2+Math.random()*3,1+Math.random()*2);}
c.fillStyle='rgba(0,0,0,0.04)';for(q=0;q<2500;q++){c.fillRect(Math.random()*880,Math.random()*1120,2+Math.random()*3,1+Math.random()*2);}
}
drawFront();
var scv=document.createElement('canvas');scv.width=256;scv.height=1120;
(function(){var c=scv.getContext('2d');c.fillStyle='#B80020';c.fillRect(0,0,256,1120);
var g=c.createLinearGradient(0,0,256,0);g.addColorStop(0,'rgba(0,0,0,0.12)');g.addColorStop(0.5,'rgba(0,0,0,0.55)');g.addColorStop(1,'rgba(0,0,0,0.12)');
c.fillStyle=g;c.fillRect(0,0,256,1120);
c.fillStyle='#8F0014';c.fillRect(0,0,256,86);c.fillStyle='#FFD100';c.fillRect(0,86,256,6);})();
function tex(cv){var t=new THREE.CanvasTexture(cv);t.encoding=THREE.sRGBEncoding;t.anisotropy=8;return t;}
var ft=tex(fcv),stx=tex(scv);
function mat(o){return new THREE.MeshStandardMaterial(o);}

function roundedBox(w,h,d,r,sx,sy,sz){
var g=new THREE.BoxGeometry(w,h,d,sx,sy,sz),p=g.attributes.position,n=g.attributes.normal;
var hx=w/2-r,hy=h/2-r,hz=d/2-r,v=new THREE.Vector3(),i=new THREE.Vector3(),dir=new THREE.Vector3();
for(var k=0;k<p.count;k++){
v.fromBufferAttribute(p,k);
i.set(Math.max(-hx,Math.min(hx,v.x)),Math.max(-hy,Math.min(hy,v.y)),Math.max(-hz,Math.min(hz,v.z)));
dir.subVectors(v,i);
if(dir.lengthSq()>1e-9){dir.normalize();v.copy(i).addScaledVector(dir,r);n.setXYZ(k,dir.x,dir.y,dir.z);}
var fx=1-Math.pow(v.x/(w/2),2),fy=1-Math.pow(v.y/(h/2),2);
if(Math.abs(v.z)>0.2)v.z+=(v.z>0?1:-1)*0.05*fx*fy;
p.setXYZ(k,v.x,v.y,v.z);}
return g;}

var fm=mat({map:ft,roughness:0.72,metalness:0}),sm=mat({map:stx,roughness:0.85});
var topM=mat({color:0x2a0007,roughness:1}),botM=mat({color:0x9a0016,roughness:1});
var bag=new THREE.Group();
var innerM=mat({color:0x8a0013,roughness:1}),edgeM=mat({color:0xa80019,roughness:0.9});
function panel(s){var g=new THREE.BoxGeometry(2.2,2.8,0.04,24,32,1),p=g.attributes.position;
for(var k=0;k<p.count;k++){var x=p.getX(k),y=p.getY(k),fx=1-Math.pow(x/1.1,2),fy=1-Math.pow(y/1.4,2);p.setZ(k,p.getZ(k)+s*0.06*fx*fy);}
g.computeVertexNormals();
var m=new THREE.Mesh(g,s>0?[edgeM,edgeM,edgeM,edgeM,fm,innerM]:[edgeM,edgeM,edgeM,edgeM,innerM,fm]);
m.position.z=s*0.6;m.castShadow=true;m.receiveShadow=true;bag.add(m);}
panel(1);panel(-1);
var gm=mat({map:stx,roughness:0.9,side:THREE.DoubleSide});
function gusset(x1,z1,x2,z2){var L=Math.sqrt(Math.pow(x2-x1,2)+Math.pow(z2-z1,2));var m=new THREE.Mesh(new THREE.PlaneGeometry(L,2.8),gm);m.position.set((x1+x2)/2,0,(z1+z2)/2);m.rotation.y=-Math.atan2(z2-z1,x2-x1);m.castShadow=true;m.receiveShadow=true;bag.add(m);}
[1,-1].forEach(function(s){gusset(s*1.1,0.6,s*0.7,0);gusset(s*0.7,0,s*1.1,-0.6);});
var bot=new THREE.Mesh(new THREE.PlaneGeometry(2.2,1.2),mat({color:0x9a0016,roughness:1,side:THREE.DoubleSide}));
bot.rotation.x=-Math.PI/2;bot.position.y=-1.39;bot.receiveShadow=true;bag.add(bot);
var cord=mat({color:0x1a1a1a,roughness:0.8});
[0.5,-0.5].forEach(function(z){
var cur=new THREE.CatmullRomCurve3([new THREE.Vector3(-0.6,1.15,z),new THREE.Vector3(-0.52,1.85,z),new THREE.Vector3(0,2.25,z),new THREE.Vector3(0.52,1.85,z),new THREE.Vector3(0.6,1.15,z)]);
var h=new THREE.Mesh(new THREE.TubeGeometry(cur,60,0.04,10,false),cord);h.castShadow=true;bag.add(h);});
var cream=mat({color:0xefe0bd,roughness:1,side:THREE.DoubleSide});
[0.575,-0.575].forEach(function(z){var s=new THREE.Mesh(new THREE.PlaneGeometry(1.8,0.3),cream);s.position.set(0,1.22,z);s.receiveShadow=true;bag.add(s);});
[0.5,-0.5].forEach(function(z){[-0.6,0.6].forEach(function(x){var k=new THREE.Mesh(new THREE.SphereGeometry(0.06,12,10),cream);k.position.set(x,1.15,z);k.castShadow=true;bag.add(k);});});
var goods=new THREE.Group();bag.add(goods);
function gmesh(geo,o,x,y,z){var m=new THREE.Mesh(geo,mat(o));m.position.set(x,y,z);m.castShadow=true;m.receiveShadow=true;goods.add(m);return m;}
var bgr=new THREE.Group(),bcol=mat({color:0xc98a3d,roughness:0.9}),slm=mat({color:0x8b5a1c,roughness:1});
var bcv=document.createElement('canvas');bcv.width=512;bcv.height=1024;
(function(){var c=bcv.getContext('2d');var g=c.createLinearGradient(0,0,512,0);g.addColorStop(0,'#a8651f');g.addColorStop(0.5,'#d9a053');g.addColorStop(1,'#a8651f');c.fillStyle=g;c.fillRect(0,0,512,1024);
for(var n=0;n<1800;n++){c.fillStyle=Math.random()<0.5?'rgba(255,240,210,0.12)':'rgba(80,40,10,0.12)';c.fillRect(Math.random()*512,Math.random()*1024,2+Math.random()*3,2+Math.random()*3);}
[0.18,0.31,0.44,0.57,0.70].forEach(function(v){[0,512].forEach(function(x){c.save();c.translate(x,(1-v)*1024);c.rotate(-0.9);c.fillStyle='#f3dcae';c.beginPath();c.ellipse(0,0,95,16,0,0,7);c.fill();c.strokeStyle='#7a4614';c.lineWidth=5;c.stroke();c.restore();});});})();
var bpts=[];for(var bi=0;bi<=40;bi++){var bq=bi/40;bpts.push(new THREE.Vector2(0.125*Math.pow(1-Math.pow(Math.abs(2*bq-1),5),0.5)+0.001,-1.3+2.6*bq));}
var bmesh=new THREE.Mesh(new THREE.LatheGeometry(bpts,32),mat({map:tex(bcv),roughness:0.85}));bmesh.castShadow=true;bmesh.receiveShadow=true;bgr.add(bmesh);
bgr.position.set(-0.15,0.3,0.05);bgr.rotation.set(0.08,0,0.2);goods.add(bgr);
var btl=new THREE.Group();
function v2(a){return a.map(function(p){return new THREE.Vector2(p[0],p[1]);});}
var liq=new THREE.Mesh(new THREE.LatheGeometry(v2([[0,-1.33],[0.1,-1.33],[0.14,-1.3],[0.155,-1.22],[0.155,0.4],[0.14,0.48],[0,0.48]]),32),mat({color:0xf29b1a,roughness:0.3}));liq.castShadow=true;btl.add(liq);
var gls=new THREE.Mesh(new THREE.LatheGeometry(v2([[0,-1.35],[0.1,-1.35],[0.15,-1.32],[0.17,-1.25],[0.17,0.55],[0.165,0.7],[0.14,0.85],[0.1,0.98],[0.075,1.1],[0.07,1.5],[0.08,1.52]]),32),mat({color:0xdfeeee,roughness:0.05,metalness:0.1,transparent:true,opacity:0.28,side:THREE.DoubleSide,depthWrite:false}));gls.renderOrder=2;btl.add(gls);
var lbc=document.createElement('canvas');lbc.width=512;lbc.height=160;
(function(){var c=lbc.getContext('2d');c.fillStyle='#FFF3D6';c.fillRect(0,0,512,160);c.fillStyle='#D6001C';c.fillRect(0,112,512,48);c.fillStyle='#F29B1A';c.fillRect(0,0,512,14);
[0,256,512].forEach(function(x){c.fillStyle='#F29B1A';c.beginPath();c.arc(x,62,34,0,7);c.fill();c.fillStyle='#FFD100';c.beginPath();c.arc(x,62,20,0,7);c.fill();c.fillStyle='#4caf50';c.beginPath();c.ellipse(x+34,30,22,10,-0.6,0,7);c.fill();});})();
var lbl=new THREE.Mesh(new THREE.CylinderGeometry(0.173,0.173,0.75,32,1,true),mat({map:tex(lbc),roughness:0.6}));lbl.position.y=-0.45;btl.add(lbl);
var bcap=new THREE.Mesh(new THREE.CylinderGeometry(0.085,0.085,0.14,24),mat({color:0xffc400,metalness:0.3,roughness:0.35}));bcap.position.y=1.59;bcap.castShadow=true;btl.add(bcap);
btl.position.set(0.42,0,-0.05);goods.add(btl);
var ccv=document.createElement('canvas');ccv.width=310;ccv.height=1000;
(function(){var c=ccv.getContext('2d');var g=c.createLinearGradient(0,0,0,1000);g.addColorStop(0,'#FFD84D');g.addColorStop(1,'#FFB800');c.fillStyle=g;c.fillRect(0,0,310,1000);
c.fillStyle='#D6001C';c.fillRect(0,0,310,170);c.fillStyle='#FFFFFF';c.fillRect(0,170,310,10);
c.fillStyle='#FFD100';c.beginPath();c.arc(155,85,52,0,7);c.fill();c.fillStyle='#D6001C';c.beginPath();c.arc(155,85,34,0,7);c.fill();
c.strokeStyle='rgba(255,255,255,0.35)';c.lineWidth=8;for(var r=0;r<14;r++){var a=r/14*Math.PI*2;c.beginPath();c.moveTo(155+Math.cos(a)*70,520+Math.sin(a)*70);c.lineTo(155+Math.cos(a)*200,520+Math.sin(a)*200);c.stroke();}
c.fillStyle='#FFFFFF';c.beginPath();c.arc(155,540,110,0,Math.PI);c.fill();
c.fillStyle='#EDEDED';c.beginPath();c.ellipse(155,540,110,28,0,0,7);c.fill();c.fillStyle='#FFFFFF';c.beginPath();c.ellipse(155,538,98,20,0,0,7);c.fill();
var s=7;for(var n=0;n<34;n++){s=(s*9301+49297)%233280;var px=155+((s/233280)-0.5)*180;s=(s*9301+49297)%233280;var py=520-(s/233280)*70;c.strokeStyle='#C98A3D';c.lineWidth=9;c.beginPath();c.arc(px,py,11,0,7);c.stroke();}
c.fillStyle='#D6001C';c.fillRect(0,800,310,200);c.fillStyle='#FFFFFF';for(var b=0;b<3;b++){c.fillRect(30,830+b*50,250,14);}
})();
var cyel=mat({color:0xffd100,roughness:0.8});
var cbx=new THREE.Mesh(new THREE.BoxGeometry(0.62,2.0,0.2),[cyel,cyel,cyel,cyel,mat({map:tex(ccv),roughness:0.7}),cyel]);
cbx.position.set(-0.1,0.5,-0.3);cbx.rotation.set(0,0.15,-0.05);cbx.castShadow=true;goods.add(cbx);
var lcv=document.createElement('canvas');lcv.width=128;lcv.height=256;
(function(){var c=lcv.getContext('2d');var g=c.createLinearGradient(0,0,128,0);g.addColorStop(0,'#5a9e2f');g.addColorStop(0.5,'#8bc34a');g.addColorStop(1,'#5a9e2f');c.fillStyle=g;c.fillRect(0,0,128,256);
c.strokeStyle='rgba(235,245,200,0.8)';c.lineWidth=5;c.beginPath();c.moveTo(64,256);c.lineTo(64,0);c.stroke();
c.lineWidth=2;c.strokeStyle='rgba(235,245,200,0.5)';for(var v=1;v<9;v++){var y=256-v*28;c.beginPath();c.moveTo(64,y);c.lineTo(10,y-30);c.moveTo(64,y);c.lineTo(118,y-30);c.stroke();}})();
var lmat=mat({map:tex(lcv),roughness:0.55,side:THREE.DoubleSide});
for(var lk=0;lk<10;lk++){
var lg=new THREE.PlaneGeometry(0.42,0.7,6,10),lp=lg.attributes.position;
for(var q2=0;q2<lp.count;q2++){var lx=lp.getX(q2),ly=lp.getY(q2),lu=lx/0.21,lv=(ly+0.35)/0.7;
lp.setX(q2,lx*(0.55+0.45*Math.sin(Math.PI*(0.1+0.9*lv))));
lp.setZ(q2,0.1*lu*lu+0.035*Math.sin(lv*11+lu*3)*Math.abs(lu)+0.12*lv*lv);}
lg.translate(0,0.35,0);lg.computeVertexNormals();
var lm=new THREE.Mesh(lg,lmat);lm.rotation.order='YXZ';lm.rotation.y=lk/10*Math.PI*2;lm.rotation.x=0.15+0.2*((lk%3)/2);
lm.position.set(0.12+Math.sin(lm.rotation.y)*0.05,1.0,0.28+Math.cos(lm.rotation.y)*0.05);lm.castShadow=true;lm.receiveShadow=true;goods.add(lm);}
var acv=document.createElement('canvas');acv.width=256;acv.height=128;
(function(){var c=acv.getContext('2d');var g=c.createLinearGradient(0,0,0,128);g.addColorStop(0,'#b71c1c');g.addColorStop(0.55,'#d32f2f');g.addColorStop(1,'#9f1717');c.fillStyle=g;c.fillRect(0,0,256,128);
for(var n=0;n<70;n++){c.fillStyle=n%2?'rgba(255,214,90,0.28)':'rgba(120,10,10,0.25)';c.fillRect(n*3.7,0,2+(n%3),128);}})();
var ag=new THREE.SphereGeometry(0.17,32,24),ap=ag.attributes.position;
for(var ai=0;ai<ap.count;ai++){var ax=ap.getX(ai),ay=ap.getY(ai),az=ap.getZ(ai),ar=Math.sqrt(ax*ax+az*az);
var sc2=1+0.08*Math.max(0,ay/0.17);ap.setX(ai,ax*sc2);ap.setZ(ai,az*sc2);
ay*=0.92;if(ay>0&&ar<0.07)ay-=0.05*(1-ar/0.07);if(ay<0&&ar<0.05)ay+=0.02*(1-ar/0.05);ap.setY(ai,ay);}
ag.computeVertexNormals();
var apple=new THREE.Mesh(ag,mat({map:tex(acv),roughness:0.3}));apple.position.set(0.55,1.3,0.2);apple.castShadow=true;goods.add(apple);
var stem=new THREE.Mesh(new THREE.CylinderGeometry(0.01,0.014,0.08,6),mat({color:0x5d4037,roughness:1}));stem.position.set(0.555,1.42,0.2);stem.rotation.z=-0.25;goods.add(stem);
var alf=new THREE.Mesh(new THREE.PlaneGeometry(0.1,0.05,4,1),mat({color:0x4caf50,roughness:0.6,side:THREE.DoubleSide}));alf.position.set(0.6,1.44,0.2);alf.rotation.set(-1.2,0,-0.3);goods.add(alf);
scene.add(bag);
if(document.fonts&&document.fonts.load){document.fonts.load('800 64px Poppins').then(function(){drawFront();ft.needsUpdate=true;}).catch(function(){});}
var st={ty:0.5,tx:0,drag:false,px:0,py:0};
var el=renderer.domElement;
el.addEventListener('pointerdown',function(e){st.drag=true;st.px=e.clientX;st.py=e.clientY;try{el.setPointerCapture(e.pointerId);}catch(x){}});
el.addEventListener('pointermove',function(e){if(!st.drag)return;st.ty+=(e.clientX-st.px)*0.01;st.tx=Math.max(-0.5,Math.min(0.5,st.tx+(e.clientY-st.py)*0.006));st.px=e.clientX;st.py=e.clientY;});
function up(){st.drag=false;}
el.addEventListener('pointerup',up);el.addEventListener('pointercancel',up);
var still=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
function resize(){var w=stage.clientWidth||600,h=stage.clientHeight||420;
renderer.setSize(w,h,false);camera.aspect=w/h;camera.fov=(w/h)<0.9?52:34;camera.updateProjectionMatrix();}
if(window.ResizeObserver){new ResizeObserver(resize).observe(stage);}
window.addEventListener('resize',resize);resize();
var t0=0,pulse=0;window.bagKick=function(s){pulse=1;st.ty+=(s||0.35);};
function frame(t){requestAnimationFrame(frame);
if(!st.drag&&!still)st.ty+=0.006;
bag.rotation.y+=(st.ty-bag.rotation.y)*0.12;
bag.rotation.x+=(st.tx-bag.rotation.x)*0.12;
pulse*=0.88;bag.scale.setScalar(1+pulse*0.07);
bag.position.y=still?0:Math.sin(t/900)*0.06;
camera.lookAt(0,0.2,0);
renderer.render(scene,camera);}
requestAnimationFrame(frame);
})();
