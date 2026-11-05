<?php
// único archivo al que entra el navegador: elige el controlador según ?r=
$ruta = $_GET['r'] ?? 'inicio';

// Cada perfil tiene su propia sesión (cookie distinta), así cajero, almacenero y admin
// pueden estar abiertos a la vez en el mismo navegador sin cerrarse entre sí.
$perfil = match (true) {
    str_starts_with($ruta, 'caja') || $ruta === 'login' || $ruta === 'boleta' => 'CAJA',
    str_starts_with($ruta, 'stock') || $ruta === 'almacenero'                 => 'ALMACEN',
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

$metodo = $_SERVER['REQUEST_METHOD'];
switch ($ruta) {
    case 'inicio':        require __DIR__ . '/../views/auth/seleccion.php'; break;
    case 'login':         $metodo === 'POST' ? (new AuthController())->login()              : (new AuthController())->mostrar(); break;
    case 'almacenero':    $metodo === 'POST' ? (new AuthController())->loginAlmacenero()     : (new AuthController())->mostrarAlmacenero(); break;
    case 'administrador': $metodo === 'POST' ? (new AuthController())->loginAdministrador() : (new AuthController())->mostrarAdministrador(); break;
    case 'productos':     (new ProductoController())->lista(); break;
    case 'stock':         (new StockController())->lista(); break;
    case 'stock_api':     (new StockController())->api(); break;
    case 'stock_accion':  (new StockController())->accion(); break;
    case 'boleta':        (new VentaController())->boleta($_GET['serie'] ?? 'B001', (int)($_GET['n'] ?? 1)); break;
    case 'admin':         (new AdminController())->panel(); break;
    case 'admin_accion':  (new AdminController())->accion(); break;
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
    default:              header('Location: index.php?r=inicio'); exit;
}