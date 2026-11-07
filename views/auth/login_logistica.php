<!DOCTYPE html>
<?php // V: ingreso del almacenero (logística). Variables que pone AuthController: $csrf, $error, $dniAnterior ?>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b2f6b">
<title>Mass · Acceso de Logística</title>
<style>
:root{
  box-sizing:border-box;
  --bg:#f3f4f6; --card:#ffffff; --text:#111827; --muted:#6b7280; --line:#d1d5db;
  --brand:#ffd100; --brand-d:#e6b800; --ink:#0b2f6b; --soft:#fff4bf; --err:#b3261e; --ok:#16a34a;
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    --bg:#111318; --card:#1b1e25; --text:#f3f4f6; --muted:#9ca3af; --line:#374151;
    --ink:#9bbcff; --soft:#3a3200; --err:#ff8a80;
  }
}
:root[data-theme="dark"]{
  --bg:#111318; --card:#1b1e25; --text:#f3f4f6; --muted:#9ca3af; --line:#374151;
  --ink:#9bbcff; --soft:#3a3200; --err:#ff8a80;
}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{margin:0;min-height:100%}
body{background:var(--card);color:var(--text);font-family:"Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
.app{max-width:430px;margin:0 auto;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;background:var(--card);overflow:hidden;position:relative}

/* ---------- Cabecera ---------- */
.hero{position:relative;background:linear-gradient(165deg,#0a2757 0%,#14439b 60%,#1b56c4 100%);color:#fff;
  padding:calc(18px + env(safe-area-inset-top,0px)) 22px 34px;overflow:hidden}
.bub{position:absolute;bottom:-20px;width:8px;height:8px;border-radius:50%;background:#ffd100;opacity:0;animation:bub 9s linear infinite;pointer-events:none}
@keyframes bub{0%{transform:translateY(0) scale(.6);opacity:0}15%{opacity:.5}100%{transform:translateY(-330px) scale(1.3);opacity:0}}
.top{display:flex;align-items:center;gap:10px;position:relative}
.top b{background:var(--brand);color:#0b2f6b;font-size:22px;font-weight:900;border-radius:10px;padding:1px 12px;animation:pop 3s ease-in-out infinite}
.top span{font-weight:800;letter-spacing:1px;font-size:12px;text-transform:uppercase;opacity:.9}
.hero h2{margin:16px 0 4px;font-size:26px;line-height:1.15;position:relative}
.hero h2 em{font-style:normal;background:linear-gradient(90deg,#ffd100,#fff6b0,#ffd100);background-size:200% 100%;
  -webkit-background-clip:text;background-clip:text;color:transparent;animation:shim 3s linear infinite}
@keyframes shim{to{background-position:-200% 0}}
.hero p{margin:0;font-size:13.5px;opacity:.85;line-height:1.4;position:relative}
.art{max-height:230px;opacity:1;overflow:hidden;transition:max-height .35s ease,opacity .25s ease;position:relative}
.art svg{display:block;width:100%;height:auto;margin-top:6px}
.app.kb .art{max-height:0;opacity:0}
.app.kb .hero p{display:none}
.wave{height:34px;margin-top:-34px;overflow:hidden;position:relative;z-index:2}
.wave svg{display:block;width:200%;height:34px;animation:wv 8s linear infinite;will-change:transform}
.wave svg.w2{position:absolute;left:0;top:0;animation-duration:12s}
.wave .a{fill:#ffd100}.wave .b{fill:var(--card)}
@keyframes wv{to{transform:translateX(-50%)}}
@keyframes pop{0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}

/* ---------- Escena ---------- */
.tw1{animation:tw 3s ease-in-out infinite}
@keyframes tw{0%,100%{opacity:.25}50%{opacity:1}}
.moon{animation:glow 5s ease-in-out infinite}
@keyframes glow{0%,100%{opacity:.12}50%{opacity:.28}}
.shoot{animation:shoot 9s linear infinite}
@keyframes shoot{0%,80%{transform:translate(0,0);opacity:0}82%{opacity:1}92%,100%{transform:translate(-170px,70px);opacity:0}}
.win{animation:tw 4s steps(2) infinite}
.lite{animation:glow 3s ease-in-out infinite}
.tw{animation:arrive 1.6s cubic-bezier(.2,.7,.2,1) .2s both}
@keyframes arrive{from{transform:translateX(-640px)}to{transform:none}}
@keyframes leave{to{transform:translateX(780px)}}
.app.done .tw{animation:leave 1.3s cubic-bezier(.5,0,1,.6) forwards}
.truck{animation:bump .5s ease-in-out infinite}
@keyframes bump{0%,100%{transform:translateY(0)}50%{transform:translateY(-1.2px)}}
.wh{transform-box:fill-box;transform-origin:center;animation:spin .5s linear 3}
.app.done .wh{animation:spin .3s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.haz{animation:hz .8s steps(2) infinite}
@keyframes hz{50%{opacity:.1}}
.puff{animation:puff 2.4s ease-out infinite;transform-box:fill-box;transform-origin:center}
@keyframes puff{0%{transform:translate(0,0) scale(.4);opacity:.7}100%{transform:translate(-14px,-40px) scale(1.6);opacity:0}}
.beam{animation:glow 2.4s ease-in-out infinite;opacity:.25}
.bxm{fill:#ffd100;stroke:#0b2f6b;stroke-width:3}

.cl{animation:drift 46s linear infinite}
.cl.b{animation-duration:64s;animation-delay:-30s}
@keyframes drift{from{transform:translateX(-180px)}to{transform:translateX(780px)}}
.drn{animation:drn 28s linear infinite}
@keyframes drn{from{transform:translateX(-80px)}to{transform:translateX(700px)}}
.bobd{animation:bobd 1.6s ease-in-out infinite}
@keyframes bobd{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
.prop{transform-box:fill-box;transform-origin:center;animation:prop .12s linear infinite alternate}
@keyframes prop{from{transform:scaleX(1)}to{transform:scaleX(.35)}}
.vent{transform-box:fill-box;transform-origin:center;animation:spin 1.1s linear infinite}
.fk{animation:fk 9s ease-in-out 2s infinite;transform-box:fill-box;transform-origin:center}
@keyframes fk{0%{transform:translateX(0) scaleX(1)}30%,35%{transform:translateX(-150px) scaleX(1)}36%{transform:translateX(-150px) scaleX(-1)}66%{transform:translateX(0) scaleX(-1)}67%,100%{transform:translateX(0) scaleX(1)}}
.fkb{animation:fkb 9s step-end 2s infinite}
@keyframes fkb{0%{opacity:1}34%{opacity:0}66%{opacity:0}67%{opacity:1}}
.chk{animation:chk 9s linear 2s infinite;transform-box:fill-box;transform-origin:center;opacity:0}
@keyframes chk{0%,32%{opacity:0;transform:scale(0)}37%{opacity:1;transform:scale(1.3)}43%{opacity:1;transform:scale(1)}52%{opacity:1;transform:scale(1) translateY(-4px)}58%,100%{opacity:0;transform:scale(1) translateY(-10px)}}
.fl{animation:glow 2.6s ease-in-out infinite}

/* ---------- Formulario ---------- */
.sheet{flex:1;padding:12px 22px calc(22px + env(safe-area-inset-bottom,0px));position:relative}
.sheet.shake{animation:shake .4s}
.sheet>*{animation:up .6s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*70ms + .3s)}
@keyframes up{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-8px)}75%{transform:translateX(8px)}}
h1{margin:0 0 2px;font-size:25px}
.sub{margin:0 0 4px;color:var(--muted);font-size:14.5px}
.grp{margin-top:16px}
.grp label.l{display:block;font-size:14px;font-weight:700;margin:0 0 8px;transition:color .2s}
.grp:focus-within label.l{color:var(--ink)}
.field{position:relative}
.ic{position:absolute;left:14px;top:50%;width:20px;height:20px;transform:translateY(-50%);color:var(--muted);transition:.25s;pointer-events:none}
.grp:focus-within .ic{color:var(--ink);transform:translateY(-50%) scale(1.15)}
input[type=text],input[type=password]{width:100%;height:54px;padding:0 54px 0 46px;font-size:16px;color:var(--text);background:var(--bg);
  border:1.5px solid var(--line);border-radius:14px;outline:none;font-family:inherit;transition:border-color .2s,box-shadow .2s,background .2s}
input:focus{border-color:var(--brand-d);box-shadow:0 0 0 4px var(--soft);background:var(--card)}
.field.bad input{border-color:var(--err);animation:flash .5s}
@keyframes flash{0%,100%{box-shadow:0 0 0 0 transparent}50%{box-shadow:0 0 0 5px rgba(179,38,30,.25)}}
.toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:48px;height:46px;background:none;border:0;color:var(--muted);cursor:pointer;display:grid;place-items:center}
.toggle svg{width:22px;height:22px}
.toggle .off{display:none}.toggle.on .off{display:block}.toggle.on .eye{display:none}
.row{display:flex;justify-content:space-between;align-items:center;margin-top:10px;font-size:14px;gap:10px}
.rem{display:flex;gap:10px;align-items:center;min-height:44px;font-weight:500;cursor:pointer}
.rem input{width:22px;height:22px;accent-color:#0b2f6b;margin:0}
.row a{color:var(--ink);text-decoration:none;font-weight:700;min-height:44px;display:flex;align-items:center}
.btn{position:relative;overflow:hidden;margin-top:12px;width:100%;height:56px;border:0;border-radius:16px;
  background:linear-gradient(180deg,#ffdf33,#ffd100);color:#0b2f6b;font-size:17px;font-weight:800;cursor:pointer;font-family:inherit;
  box-shadow:0 6px 0 var(--brand-d),0 12px 22px rgba(255,209,0,.35);transition:transform .1s,box-shadow .1s,background .3s,color .3s}
.btn:active{transform:translateY(4px);box-shadow:0 2px 0 var(--brand-d)}
.btn::after{content:"";position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.65),transparent);animation:shine 3.2s infinite}
@keyframes shine{0%{left:-60%}40%,100%{left:130%}}
.btn.ok{background:var(--ok);color:#fff;box-shadow:0 6px 0 #0f7a36}
.rip{position:absolute;border-radius:50%;background:rgba(11,47,107,.25);transform:scale(0);animation:rip .6s ease-out forwards;pointer-events:none}
@keyframes rip{to{transform:scale(4);opacity:0}}
.spn{display:none;width:18px;height:18px;border:3px solid #0b2f6b;border-right-color:transparent;border-radius:50%;margin-right:8px;vertical-align:-3px;animation:spin .7s linear infinite}
.btn.load .spn{display:inline-block}
.msg{min-height:22px;margin-top:14px;font-size:14px;font-weight:700;text-align:center}
.msg.err{color:var(--err)}.msg.ok{color:var(--ok)}
.foot{margin-top:6px;color:var(--muted);font-size:12px;text-align:center}
.cf{position:fixed;width:9px;height:9px;border-radius:2px;pointer-events:none;z-index:50}

@media (min-width:520px){
  body{background:linear-gradient(160deg,#ffe14d,#ffd100 50%,#ffb300);padding:20px}
  .app{min-height:calc(100vh - 40px);border-radius:34px;box-shadow:0 26px 60px rgba(11,47,107,.4)}
}
@media (max-height:640px){.art{max-height:150px}.hero h2{font-size:21px}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}
</style>
</head>
<body>
<div class="app" id="app">
  <header class="hero">
    <i class="bub" style="left:8%;animation-delay:0s"></i><i class="bub" style="left:24%;animation-delay:-3s;width:6px;height:6px"></i><i class="bub" style="left:46%;animation-delay:-6s"></i>
    <i class="bub" style="left:63%;animation-delay:-1.5s;width:10px;height:10px"></i><i class="bub" style="left:80%;animation-delay:-4.5s;width:6px;height:6px"></i><i class="bub" style="left:92%;animation-delay:-7.5s"></i>
    <div class="top"><b>Mass</b><span>Logística</span></div>
    <h2>Bienvenido a <em>logística</em></h2>
    <p>Recibe, ordena y despacha para que la tienda siempre esté abastecida.</p>
    <div class="art" aria-hidden="true">
      <svg viewBox="0 205 600 280" preserveAspectRatio="xMidYMax meet">
        <defs>
          <linearGradient id="bm" x1="0" x2="1"><stop offset="0" stop-color="#fff" stop-opacity=".7"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
          <linearGradient id="lc" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff6b0" stop-opacity=".55"/><stop offset="1" stop-color="#fff6b0" stop-opacity="0"/></linearGradient>
          <radialGradient id="hg"><stop offset="0" stop-color="#ffd100" stop-opacity=".45"/><stop offset="1" stop-color="#ffd100" stop-opacity="0"/></radialGradient>
        </defs>
        <ellipse cx="300" cy="430" rx="420" ry="120" fill="url(#hg)"/>
        <!-- cielo -->
        <g fill="#fff"><circle class="tw1" cx="60" cy="228" r="1.8"/><circle class="tw1" cx="150" cy="243" r="1.4" style="animation-delay:-1s"/><circle class="tw1" cx="262" cy="222" r="1.8" style="animation-delay:-2s"/><circle class="tw1" cx="340" cy="244" r="1.4" style="animation-delay:-.5s"/><circle class="tw1" cx="430" cy="226" r="1.8" style="animation-delay:-1.6s"/><circle class="tw1" cx="580" cy="218" r="1.4" style="animation-delay:-2.4s"/><circle class="tw1" cx="20" cy="262" r="1.4" style="animation-delay:-2.1s"/></g>
        <circle class="moon" cx="520" cy="250" r="40" fill="#fff6c8"/>
        <circle cx="520" cy="250" r="18" fill="#fff3b0"/><circle cx="528" cy="244" r="16" fill="#14439b"/>
        <path class="shoot" d="M330 214l-26 10" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        <g fill="#fff" fill-opacity=".09"><g class="cl"><ellipse cx="60" cy="262" rx="52" ry="11"/><ellipse cx="92" cy="254" rx="32" ry="11"/></g><g class="cl b"><ellipse cx="40" cy="292" rx="44" ry="9"/><ellipse cx="68" cy="285" rx="26" ry="9"/></g></g>
        <!-- dron -->
        <g class="drn"><g class="bobd" transform="translate(0 240)"><path d="M-6 -3h36" stroke="#fff" stroke-width="2"/><rect x="2" y="-2" width="20" height="8" rx="4" fill="#fff"/>
          <ellipse class="prop" cx="-6" cy="-6" rx="8" ry="1.6" fill="#fff" fill-opacity=".8"/><ellipse class="prop" cx="30" cy="-6" rx="8" ry="1.6" fill="#fff" fill-opacity=".8"/>
          <path d="M12 6v10" stroke="#fff" stroke-width="1.5"/><rect x="4" y="16" width="16" height="12" rx="2" fill="#ffd100" stroke="#0b2f6b" stroke-width="2"/><circle class="haz" cx="12" cy="2" r="1.8" fill="#ff3b3b"/></g></g>
        <!-- ciudad: capa lejana y cercana -->
        <g fill="#1a4aa0" fill-opacity=".42"><rect x="0" y="300" width="30" height="125"/><rect x="150" y="285" width="26" height="140"/><rect x="215" y="270" width="28" height="155"/><rect x="330" y="296" width="30" height="129"/><rect x="420" y="276" width="26" height="149"/><rect x="505" y="294" width="34" height="131"/></g>
        <g fill="#0c2a62" fill-opacity=".92"><rect x="210" y="285" width="40" height="140"/><rect x="255" y="262" width="34" height="163"/><rect x="295" y="295" width="50" height="130"/><rect x="350" y="255" width="38" height="170"/><rect x="392" y="280" width="44" height="145"/><rect x="440" y="268" width="36" height="157"/><rect x="480" y="290" width="50" height="135"/><rect x="535" y="272" width="40" height="153"/><rect x="578" y="285" width="30" height="140"/><rect x="368" y="244" width="2" height="12"/></g>
        <circle class="haz" cx="369" cy="243" r="2.6" fill="#ff3b3b"/>
        <g fill="#ffd100"><rect class="win" x="222" y="292" width="6" height="8"/><rect class="win" x="266" y="270" width="6" height="8" style="animation-delay:-1s"/><rect class="win" x="266" y="284" width="6" height="8" style="animation-delay:-2s"/><rect class="win" x="362" y="262" width="6" height="8" style="animation-delay:-.6s"/><rect class="win" x="374" y="276" width="6" height="8" style="animation-delay:-1.7s"/><rect class="win" x="452" y="276" width="6" height="8" style="animation-delay:-2.6s"/><rect class="win" x="548" y="280" width="6" height="8" style="animation-delay:-1.2s"/><rect class="win" x="492" y="298" width="6" height="8" style="animation-delay:-3s"/></g>
        <!-- almacén -->
        <rect x="0" y="332" width="210" height="93" fill="#1b3f86"/>
        <path d="M-5 334L105 294L215 334Z" fill="#102f6b"/>
        <g stroke="#fff" stroke-opacity=".18" stroke-width="1.5"><path d="M0 345h210M0 358h210"/></g>
        <g class="vent" stroke="#fff" stroke-width="2" fill="none"><circle cx="150" cy="312" r="6"/><path d="M150 306v12M144 312h12"/></g><rect x="148" y="318" width="4" height="8" fill="#0b2f6b"/>
        <rect x="52" y="336" width="108" height="22" rx="4" fill="#ffd100"/>
        <text x="106" y="352" text-anchor="middle" font-family="Segoe UI,Arial" font-weight="900" font-size="11.5" fill="#0b2f6b">LOGÍSTICA MASS</text>
        <rect x="40" y="372" width="116" height="53" fill="#ffd100" fill-opacity=".92"/><rect class="lite" x="40" y="372" width="116" height="53" fill="#fff"/>
        <rect x="40" y="372" width="116" height="14" fill="#8fa6d6"/><g stroke="#0b2f6b" stroke-opacity=".5" stroke-width="1.5"><path d="M40 379h116M40 386h116"/></g>
        <g fill="#ffd100"><rect class="win" x="10" y="344" width="18" height="12"/><rect class="win" x="176" y="344" width="18" height="12" style="animation-delay:-2s"/></g>
        <g><rect class="bxm" x="4" y="404" width="28" height="21"/><rect class="bxm" x="4" y="383" width="28" height="21"/><rect class="bxm" x="170" y="408" width="24" height="17"/></g>
        <!-- suelo -->
        <rect y="425" width="600" height="60" fill="#071c42"/><rect y="425" width="600" height="4" fill="#ffd100" fill-opacity=".55"/>
        <rect y="429" width="600" height="14" fill="#fff" fill-opacity=".05"/>
        <line x1="0" y1="458" x2="600" y2="458" stroke="#ffd100" stroke-width="4" stroke-dasharray="30 24" stroke-opacity=".8"/>
        <!-- poste de luz -->
        <rect x="592" y="300" width="5" height="125" fill="#0b2f6b"/><rect x="570" y="296" width="28" height="6" rx="3" fill="#0b2f6b"/>
        <path class="fl" d="M572 302h24l14 123h-52z" fill="url(#lc)"/>
        <!-- conos -->
        <g><path d="M232 452l8-20 8 20z" fill="#ff8a00"/><rect x="229" y="451" width="22" height="4" rx="1" fill="#0b2f6b"/><path d="M235 441h10l1.6 5h-13.2z" fill="#fff"/>
        <path d="M118 452l8-20 8 20z" fill="#ff8a00"/><rect x="115" y="451" width="22" height="4" rx="1" fill="#0b2f6b"/><path d="M121 441h10l1.6 5h-13.2z" fill="#fff"/></g>
        <!-- montacargas -->
        <g class="fk"><g>
          <rect x="242" y="404" width="40" height="18" rx="3" fill="#ffd100" stroke="#0b2f6b" stroke-width="2"/>
          <path d="M250 404v-18h26v18" fill="none" stroke="#0b2f6b" stroke-width="3"/><rect x="276" y="408" width="10" height="14" rx="2" fill="#0b2f6b"/>
          <rect x="237" y="380" width="5" height="44" fill="#0b2f6b"/><rect x="221" y="418" width="19" height="4" fill="#0b2f6b"/>
          <g class="fkb"><rect class="bxm" x="221" y="393" width="26" height="25"/><path d="M234 393v25" stroke="#0b2f6b" stroke-opacity=".5" stroke-width="2"/></g>
          <circle cx="252" cy="424" r="7" fill="#0b2f6b"/><circle cx="274" cy="424" r="7" fill="#0b2f6b"/><circle cx="252" cy="424" r="2.5" fill="#ffd100"/><circle cx="274" cy="424" r="2.5" fill="#ffd100"/>
          <circle class="haz" cx="262" cy="384" r="3" fill="#ff8a00"/>
        </g></g>
        <g class="chk"><circle cx="98" cy="362" r="10" fill="#22c55e"/><path d="M93 362l4 4 8-9" stroke="#fff" stroke-width="2.8" fill="none" stroke-linecap="round"/></g>
        <!-- camión -->
        <g class="tw"><g transform="translate(555 426) scale(.8) translate(-555 -426)">
          <ellipse cx="400" cy="427" rx="190" ry="7" fill="#000" fill-opacity=".35"/>
          <polygon class="beam" points="556,388 610,356 610,426" fill="url(#bm)"/>
          <g class="truck">
            <rect x="225" y="290" width="235" height="120" rx="8" fill="#fff"/>
            <rect x="225" y="290" width="235" height="22" rx="8" fill="#ffd100"/>
            <path d="M262 318v84M300 318v84" stroke="#0b2f6b" stroke-opacity=".12" stroke-width="2"/>
            <text x="342" y="372" text-anchor="middle" font-family="Segoe UI,Arial" font-weight="900" font-size="46" fill="#0b2f6b">MASS</text>
            <rect x="450" y="268" width="7" height="44" fill="#0b2f6b"/>
            <circle class="puff" cx="453" cy="266" r="6" fill="#fff"/><circle class="puff" cx="453" cy="266" r="6" fill="#fff" style="animation-delay:-.8s"/><circle class="puff" cx="453" cy="266" r="6" fill="#fff" style="animation-delay:-1.6s"/>
            <path d="M462 330h52l32 38v42H462z" fill="#ffd100"/><path d="M474 340h34l22 26h-56z" fill="#9ed0ff"/>
            <rect x="225" y="402" width="330" height="10" fill="#0b2f6b"/>
            <circle class="haz" cx="230" cy="330" r="5" fill="#ff8a00"/><circle class="haz" cx="548" cy="392" r="5" fill="#fff6b0"/>
          </g>
          <g><circle cx="290" cy="418" r="24" fill="#0b2f6b"/><circle cx="395" cy="418" r="24" fill="#0b2f6b"/><circle cx="505" cy="418" r="24" fill="#0b2f6b"/></g>
          <g fill="none" stroke="#ffd100" stroke-width="4"><g class="wh"><circle cx="290" cy="418" r="11"/><path d="M290 407v22M279 418h22"/></g>
          <g class="wh"><circle cx="395" cy="418" r="11"/><path d="M395 407v22M384 418h22"/></g>
          <g class="wh"><circle cx="505" cy="418" r="11"/><path d="M505 407v22M494 418h22"/></g></g>
        </g></g>
      </svg>
    </div>
  </header>
  <div class="wave" aria-hidden="true">
    <svg class="w2" viewBox="0 0 2400 34" preserveAspectRatio="none"><path class="a" d="M0 16Q150 0 300 16T600 16T900 16T1200 16T1500 16T1800 16T2100 16T2400 16V34H0Z"/></svg>
    <svg viewBox="0 0 2400 34" preserveAspectRatio="none"><path class="b" d="M0 22Q100 8 200 22T400 22T600 22T800 22T1000 22T1200 22T1400 22T1600 22T1800 22T2000 22T2200 22T2400 22V34H0Z"/></svg>
  </div>

  <main class="sheet" id="sheet">
    <h1 style="--i:0">Iniciar sesión</h1>
    <p class="sub" style="--i:1">Ingresa tu DNI y tu contraseña.</p>

    <form method="post" action="index.php?r=almacenero" id="form-almacenero">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="grp" style="--i:2">
        <label class="l" for="dni">DNI del empleado</label>
        <div class="field" id="f1">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.6 1.8-2.4 3-2.4s2.4.8 3 2.4"/><path d="M15 10h3M15 14h3"/></svg>
          <input id="dni" name="dni" type="text" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" placeholder="8 dígitos" autocomplete="username" required value="<?= htmlspecialchars($dniAnterior) ?>">
        </div>
      </div>

      <div class="grp" style="--i:3">
        <label class="l" for="pwd">Contraseña</label>
        <div class="field" id="f2">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
          <input id="pwd" name="contrasena" type="password" placeholder="Tu contraseña" autocomplete="current-password" required>
          <button class="toggle" type="button" id="tg" aria-label="Mostrar contraseña">
            <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.9 10.9 0 0112 19C5 19 1 12 1 12a18.5 18.5 0 015.1-5.9M9.9 5.1A9.7 9.7 0 0112 5c7 0 11 7 11 7a18.5 18.5 0 01-2.2 3.2M1 1l22 22"/></svg>
          </button>
        </div>
      </div>

      <?php if (!empty($error)): ?><div class="msg err" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <button class="btn" id="go" type="submit" style="--i:5"><span class="spn"></span><span id="bt">Ingresar</span></button>
    </form>

    <div class="foot" style="--i:7">Mass · Grupo Intercorp</div>
  </main>
</div>

<script>
(function(){
  var $=function(i){return document.getElementById(i)};
  var pwd=$('pwd'),tg=$('tg'),app=$('app'),go=$('go'),bt=$('bt');
  tg.addEventListener('click',function(){var h=pwd.type==='password';pwd.type=h?'text':'password';tg.classList.toggle('on',h);tg.setAttribute('aria-label',h?'Ocultar contraseña':'Mostrar contraseña');});
  app.addEventListener('focusin',function(e){if(e.target.tagName==='INPUT')app.classList.add('kb');});
  app.addEventListener('focusout',function(){setTimeout(function(){var a=document.activeElement;if(!a||a.tagName!=='INPUT')app.classList.remove('kb');},120);});
  $('form-almacenero').addEventListener('submit',function(){go.classList.add('load');bt.textContent='Verificando…';});
})();
</script>
</body>
</html>