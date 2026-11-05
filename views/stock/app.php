<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<title>Almacenamiento · MASS</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="css/almacenamiento.css">
</head>
<body data-tienda="<?= htmlspecialchars($nombreTienda, ENT_QUOTES, 'UTF-8') ?>">
<div class="app"><aside class="side"><div class="logo">MASS<i>.</i></div><div class="tag">ALMACENAMIENTO</div><div class="lbl">ALMACÉN</div><nav id="nav"></nav><div class="foot">Minimarket MASS</div></aside>
<div class="main"><header class="bar"><span class="crumb" id="crumb"></span><div class="who"><span><b><?= htmlspecialchars($empleado->nombreCompleto(), ENT_QUOTES, 'UTF-8') ?></b><small><?= htmlspecialchars($nombreTienda, ENT_QUOTES, 'UTF-8') ?></small></span><button class="pri sm" onclick="scanGlobal()">📷 Escanear</button></div></header><main id="main"><div class="empty">Cargando inventario desde MySQL…</div></main></div></div>
<dialog id="dlg"></dialog>
<dialog id="sdlg"><h2>Escanear código de barras</h2><div class="vw"><video id="svid" class="vid" playsinline muted></video></div><div id="smsg" class="note" role="status"></div><label>¿Sin cámara? Escribe el código o usa un lector USB<input id="sman" inputmode="numeric" autocomplete="off"></label><div class="row"><button onclick="sdlg.close()">Terminar</button><button class="pri" onclick="manualScan()">Buscar</button></div></dialog>
<div id="toast" role="status"></div><script src="js/almacenamiento.js" defer></script>
</body></html>
