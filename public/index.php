<?php
// único archivo al que entra el navegador: elige el controlador según ?r=
$ruta = $_GET['r'] ?? 'inicio';

// Celular SIN cable: llega por una dirección pública https (túnel). Por esa vía SOLO se permite enviar códigos del
// escáner del ALMACÉN (escaner_enviar). Login, caja, administrador y el escáner de caja quedan solo en la PC / red local.
require_once __DIR__ . '/../models/Config.php';
$hostPeticion = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$esLocal = $hostPeticion === 'localhost' || $hostPeticion === '[::1]'
    || preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $hostPeticion) === 1
    || preg_match('/\.(test|local|localhost)$/', $hostPeticion) === 1;
if (!$esLocal && $ruta !== 'escaner_enviar' && Config::get('ACCESO_PUBLICO_COMPLETO', '0') !== '1') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('No encontrado');
}

// Cada perfil tiene su propia sesión (cookie distinta), así cajero, almacenero y admin
// pueden estar abiertos a la vez en el mismo navegador sin cerrarse entre sí.
$perfil = match (true) {
    str_starts_with($ruta, 'caja') || $ruta === 'login' || $ruta === 'boleta' => 'CAJA',
    str_starts_with($ruta, 'stock') || str_starts_with($ruta, 'escaner_') || $ruta === 'almacenero' => 'ALMACEN',
    str_starts_with($ruta, 'admin') || $ruta === 'administrador'              => 'ADMIN',
    default                                                                    => 'MASS',
};
session_name('MASS_' . $perfil);

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/StockController.php';
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/CajaController.php';
require_once __DIR__ . '/../controllers/EscanerCajaController.php';
require_once __DIR__ . '/../controllers/EscanerController.php';
require_once __DIR__ . '/../controllers/NotificacionController.php';
require_once __DIR__ . '/../controllers/TurnoController.php';

$metodo = $_SERVER['REQUEST_METHOD'];
switch ($ruta) {
    case 'inicio':        require __DIR__ . '/../views/auth/seleccion.php'; break;
    case 'login':         $metodo === 'POST' ? (new AuthController())->login()              : (new AuthController())->mostrar(); break;
    case 'almacenero':    $metodo === 'POST' ? (new AuthController())->loginAlmacenero()     : (new AuthController())->mostrarAlmacenero(); break;
    case 'administrador': $metodo === 'POST' ? (new AuthController())->loginAdministrador() : (new AuthController())->mostrarAdministrador(); break;
    case 'productos':     (new ProductoController())->lista(); break;
    case 'stock':         (new StockController())->lista(); break;
    case 'stock_api':     (new StockController())->api(); break;
    case 'stock_notificaciones': (new NotificacionController())->almacen(); break;
    case 'stock_accion':  (new StockController())->accion(); break;
    case 'boleta':        (new VentaController())->boleta($_GET['serie'] ?? 'B001', (int)($_GET['n'] ?? 1)); break;
    case 'admin':         (new AdminController())->panel(); break;
    case 'admin_accion':  (new AdminController())->accion(); break;
    case 'admin_turno_cancelar': (new TurnoController())->cancelar(); break;
    case 'admin_api_pendientes': (new AdminController())->pendientesJson(); break;
    case 'caja':               (new CajaController())->pos(); break;
    case 'caja_api_estado':    (new CajaController())->estado(); break;
    case 'caja_api_abrir':     (new CajaController())->abrir(); break;
    case 'caja_api_cerrar':    (new CajaController())->cerrar(); break;
    case 'caja_api_productos': (new CajaController())->productos(); break;
    case 'caja_api_vender':    (new CajaController())->vender(); break;
    case 'caja_api_ventas':    (new CajaController())->ventas(); break;
    case 'caja_api_buscar':    (new CajaController())->buscarVenta(); break;
    case 'caja_api_solicitar': (new CajaController())->solicitar(); break;
    case 'caja_api_solicitud': (new CajaController())->solicitud(); break;
    case 'caja_api_movimiento':  (new CajaController())->movimiento(); break;
    case 'caja_api_movimientos': (new CajaController())->movimientos(); break;
    case 'caja_api_escaner_vincular':    (new CajaController())->escanerVincular(); break;
    case 'caja_api_escaner_desvincular': (new CajaController())->escanerDesvincular(); break;
    case 'caja_api_escaner_leer':        (new CajaController())->escanerLeer(); break;
    case 'caja_escaner':        (new EscanerCajaController())->movil(); break;
    case 'caja_escaner_enviar': (new EscanerCajaController())->enviar(); break;
    case 'caja_salir':         (new AuthController())->salir('login'); break;
    case 'escaner_vincular':   (new EscanerController())->vincular(); break;
    case 'escaner_pendientes': (new EscanerController())->pendientes(); break;
    case 'escaner_enviar':     (new EscanerController())->enviar(); break;
    case 'stock_salir':        (new AuthController())->salir('almacenero'); break;
    case 'admin_salir':        (new AuthController())->salir('administrador'); break;
    default:              header('Location: index.php?r=inicio'); exit;
}
