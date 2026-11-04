<?php
// único archivo al que entra el navegador: elige el controlador según ?r=
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/StockController.php';
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$ruta = $_GET['r'] ?? 'login';
switch ($ruta) {
    case 'productos': (new ProductoController())->lista(); break;
    case 'stock':     (new StockController())->lista((int)($_GET['tienda'] ?? 1)); break;
    case 'boleta':    (new VentaController())->boleta($_GET['serie'] ?? 'B001', (int)($_GET['n'] ?? 1)); break;
    case 'caja':
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['empleado'])) { header('Location: index.php'); exit; }
        require __DIR__ . '/../views/caja/pos.php';
        break;
    case 'admin_login':
        $_SERVER['REQUEST_METHOD'] === 'POST' ? (new AdminController())->login() : (new AdminController())->mostrarLogin();
        break;
    case 'admin_panel': (new AdminController())->panel(); break;
    case 'admin_api_ventas':  (new AdminController())->apiVentas(); break;
    case 'admin_api_personal': (new AdminController())->apiPersonal(); break;
    case 'admin_api_resumen': (new AdminController())->apiResumen(); break;
    case 'admin_api_tiendas': (new AdminController())->apiTiendas(); break;
    default:          $_SERVER['REQUEST_METHOD'] === 'POST' ? (new AuthController())->login() : (new AuthController())->mostrar();
}
