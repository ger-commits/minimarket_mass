<?php
// único archivo al que entra el navegador: elige el controlador según ?r=
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/StockController.php';
require_once __DIR__ . '/../controllers/VentaController.php';

$ruta = $_GET['r'] ?? 'login';
switch ($ruta) {
    case 'productos': (new ProductoController())->lista(); break;
    case 'stock':     (new StockController())->lista((int)($_GET['tienda'] ?? 1)); break;
    case 'boleta':    (new VentaController())->boleta($_GET['serie'] ?? 'B001', (int)($_GET['n'] ?? 1)); break;
    default:          $_SERVER['REQUEST_METHOD'] === 'POST' ? (new AuthController())->login() : require __DIR__ . '/../views/auth/login.php';
}
